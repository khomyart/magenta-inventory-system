<?php

namespace App\Http\Controllers;

use App\Helpers\AuthAPI;
use App\Helpers\NbuCurrencyExchangeCoursesService;
use App\Http\Resources\SpendResource;
use App\Models\Spend;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Helpers\ErrorHandler;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SpendController extends Controller
{
    public string $section = "spends";
    public AuthAPI|false $authAPI;

    public function __construct(Request $request)
    {
        $this->authAPI = AuthAPI::isAuthenticated($request->bearerToken(), $request->ip());
    }

    private function getFieldsAndRulesForCreatingOrUpdatingSection(bool $includeIsHiddenField): array
    {
        $fields = [
            "title" => ["required", "string", "max:255"],
            "price" => ["required", "numeric", "gte:0.1"],
            "currency" => ["required", Rule::in(['UAH', 'USD', 'EUR'])],
            "happened_at" => ["required", "date_format:Y-m-d H:i"]
        ];

        if ($includeIsHiddenField) {
            $fields["is_hidden"] = ["required", "bool"];
        }

        return $fields;
    }

    private function getFieldRulesForOrderingSection(): array
    {
        return [
            "orderField" => ["string", Rule::in(["id", "title", "price", "happened_at", "created_at", "created_by_user"]), "nullable"]
        ];
    }

    private function getFieldsAndRulesForFilteringSection(): array
    {
        $stringFilters = ["include", "exclude", "more", "less", "equal", "notequal"];
        $numericFilters = ["more", "less", "equal", "notequal"];
        $dateFilters = ["include", "more", "less"];
        return [
            "title_filter_value" => ["string", "nullable"],
            "title_filter_mode" => ["string", Rule::in($stringFilters), "nullable"],
            "price_filter_value" => ["string", "nullable"],
            "price_filter_mode" => ["string", Rule::in($numericFilters), "nullable"],
            "happened_at_from_filter_value" => ["string", "nullable"],
            "happened_at_from_filter_mode" => ["string", Rule::in($dateFilters), "nullable"],
            "happened_at_to_filter_value" => ["string", "nullable"],
            "happened_at_to_filter_mode" => ["string", Rule::in($dateFilters), "nullable"],
            "created_at_from_filter_value" => ["string", "nullable"],
            "created_at_from_filter_mode" => ["string", Rule::in($dateFilters), "nullable"],
            "created_at_to_filter_value" => ["string", "nullable"],
            "created_at_to_filter_mode" => ["string", Rule::in($dateFilters), "nullable"],
            "created_by_user_filter_value" => ["string", "nullable"],
            "created_by_user_filter_mode" => ["string", Rule::in($stringFilters), "nullable"],
        ];
    }

    private function getListOfFilteringFields(): array
    {
        $fieldsModesAndRules = $this->getFieldsAndRulesForFilteringSection();
        $fields = [];

        foreach ($fieldsModesAndRules as $key => $value) {
            $fieldName = str_replace(["_filter_mode", "_filter_value"], "", $key);
            $fields[] = $fieldName;
        }

        return array_unique($fields);
    }

    /**
     * Receive a current section model
     *
     * @return Spend
     */
    public function getSectionModel(): Spend
    {
        return new Spend();
    }

    /**
     * Display a listing of the resources.
     *
     * @param Request $request
     *
     * @return AnonymousResourceCollection|Response
     */
    public function read(Request $request, NbuCurrencyExchangeCoursesService $coursesService): AnonymousResourceCollection|Response
    {
        $today = Carbon::today()->format("Ymd");
        $isAbleToSeeHiddenSpends = $this->authAPI->isAuthorizedFor("see_hidden", "spends");

        $rules = [
            "itemsPerPage" => "required|numeric",
            "page" => "required|numeric",
            "orderValue" => ["string", Rule::in(["desc", "asc"]), "nullable"],
            ...$this->getFieldsAndRulesForFilteringSection(),
            ...$this->getFieldRulesForOrderingSection()
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ErrorHandler::responseWith(json_encode($validator->errors()));
        }

        try {
            $data = $validator->validated();
        } catch (ValidationException $e) {
            return ErrorHandler::responseWith($e->getMessage());
        }
        $section = Spend::query()->with("user");
        $section->when(!$isAbleToSeeHiddenSpends, function ($query) {
            $query->where("is_hidden", "<>", true);
        });

        //forming where query
        foreach ($this->getListOfFilteringFields() as $field) {
            $searchValue = $data["{$field}_filter_value"];
            $filterMode = match ($field) {
                "happened_at_from", "created_at_from" => "more",
                "happened_at_to", "created_at_to" => "less",
                default => $data["{$field}_filter_mode"]
            };
            $searchOperator = $this->getWhereOperator($filterMode);

            if ($searchValue != null) {
                if ($field === "happened_at_from" || $field === "happened_at_to") {
                    $field = "happened_at";
                }
                if ($field === "created_at_from" || $field === "created_at_to") {
                    $field = "created_at";
                }

                if ($field === "created_by_user") {
                    if (empty($searchValue)) {
                        continue;
                    }
                    $section->whereHas("user", function ($query) use ($searchValue) {
                        $query->where("name", "like", "%$searchValue%");
                    });
                    continue;
                }

                if ($field === "price") {
                    if (empty($searchValue)) {
                        continue;
                    }

                    if (preg_match('/(^₴|^\$|^€)(\d*)/', $searchValue, $matches)) {
                        $currencySymbol = $matches[1] ?? null;
                        $amountOfMoney = (float)($matches[2] ?? 0);
                        $currencyForSearching = match ($currencySymbol) {
                            "₴" => "UAH",
                            "$" => "USD",
                            "€" => "EUR",
                            default => null,
                        };

                        if ($currencyForSearching !== null) {
                            $section->where("currency", "=", $currencyForSearching);
                        }
                        if ($amountOfMoney > 0) {
                            $section->where("price", $searchOperator, $amountOfMoney);
                        }
                        continue;
                    }
                }

                if ($searchOperator === "like") {
                    $section->where($field, "like", "%{$searchValue}%");
                    continue;
                }
                if ($searchOperator === "notLike") {
                    $section->whereNot(function ($query) use ($searchValue, $field) {
                        $query->where($field, "like", "%{$searchValue}%");
                    });
                    continue;
                }
                $section->where($field, $searchOperator, $searchValue);
            }
        }

        //ordering a query
        if (!empty($data["orderField"]) && !empty($data["orderValue"])) {
            $section = $section->orderBy($data["orderField"], $data["orderValue"]);
        } else {
            $section = $section->latest();
        }

        $paginatedSection = $section->paginate($data["itemsPerPage"]);

        SpendResource::$dollarCurrencyExchangeCoefficient = $coursesService->getCourses($today, "USD");
        SpendResource::$euroCurrencyExchangeCoefficient = $coursesService->getCourses($today, "EUR");
        SpendResource::$isUserAllowedToSeeHidden = $isAbleToSeeHiddenSpends;
        return SpendResource::collection($paginatedSection);

    }

    public function create(Request $request): Response
    {
        $sectionModel = $this->getSectionModel();

        $isAbleToHideSpends = $this->authAPI->isAuthorizedFor('hide', 'spends');
        $isAbleToSeeHiddenSpends = $this->authAPI->isAuthorizedFor('see_hidden', 'spends');
        SpendResource::$isUserAllowedToSeeHidden = $isAbleToSeeHiddenSpends;

        $rules = $this->getFieldsAndRulesForCreatingOrUpdatingSection($isAbleToHideSpends);
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ErrorHandler::responseWith(json_encode($validator->errors()));
        }

        try {
            $data = $validator->validated();
        } catch (ValidationException $e) {
            return ErrorHandler::responseWith($e->getMessage());
        }

        $data["created_by_user_id"] = $this->authAPI->user->id;
        $spend = $sectionModel::create($data);

        return response()->json(["spend" => SpendResource::make($spend->load("user"))]);
    }

    public function update(Request $request, int $id): Response
    {
        $isAbleToUpdate = $this->authAPI->isAuthorizedFor("update", "spends");
        if (!$isAbleToUpdate) {
            return ErrorHandler::responseWith("Оновлення не дозволено");
        }

        $isAbleToHideSpends = $this->authAPI->isAuthorizedFor("hide", "spends");
        $isAbleToSeeHiddenSpends = $this->authAPI->isAuthorizedFor("see_hidden", "spends");
        $isAllowedToEditNotOwned = $this->authAPI->isAuthorizedFor("update_not_owned", "spends");

        SpendResource::$isUserAllowedToSeeHidden = $isAbleToSeeHiddenSpends;

        $section = Spend::where("id", $id)->first();
        if (!$section) {
            return ErrorHandler::responseWith("Витрату не знайдено");
        }
        if (!$isAllowedToEditNotOwned && $section->user->id !== $this->authAPI->user->id) {
            return ErrorHandler::responseWith("Редагування заборонено");
        }

        $rules = $this->getFieldsAndRulesForCreatingOrUpdatingSection($isAbleToHideSpends);
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ErrorHandler::responseWith(json_encode($validator->errors()));
        }

        try {
            $data = $validator->validated();
        } catch (ValidationException $e) {
            return ErrorHandler::responseWith($e->getMessage());
        }

        foreach ($data as $field => $value) {
            $section->$field = $value;
        }
        $section->save();

        return response()->json(["spend" => SpendResource::make($section->load("user"))]);
    }

    /**
     * Delete section (row) from DB by ID
     *
     * @param Request $request
     * @param int $id Passed through URL
     *
     * @return Response
     */
    public function delete(Request $request, int $id): Response
    {
        $isAllowedToDelete = $this->authAPI->isAuthorizedFor("delete", "spends");
        if (!$isAllowedToDelete) {
            return ErrorHandler::responseWith("Видалення не дозволено");
        }

        $isAllowedToDeleteNotOwned = $this->authAPI->isAuthorizedFor("delete_not_owned", "spends");

        $sectionModel = $this->getSectionModel();
        $section = $sectionModel::query()->find($id);

        if ($section == null) {
            return ErrorHandler::responseWith("Витрату не знайдено");
        }
        if (!$isAllowedToDeleteNotOwned && $section->user->id !== $this->authAPI->user->id) {
            return ErrorHandler::responseWith("Видалення заборонено");
        }

        $section->delete();
        return response("OK", 200);
    }

    /**
     * Returns nbu currency exchange currency rate for today
     *
     * @param string $date - yyyymm
     * @param string $currencyCode - USD|EUR|etc...
     * @param
     *
     * @return float currency exchange index
     */
    private function getNbuCurrencyExchangeCourses(string $date, string $currencyCode): float {
        $nbuService = new NbuCurrencyExchangeCoursesService();
        return $nbuService->getCourses($date, $currencyCode);
    }

    private function getWhereOperator($operatorName): string
    {
        $equality = [
            "include" => "like",
            "exclude" => "notLike",
            "more" => ">",
            "less" => "<",
            "equal" => "=",
            "notequal" => "<>"
        ];

        return $equality[$operatorName];
    }
}
