<?php

namespace App\Http\Controllers;

use App\Helpers\AuthAPI;
use App\Helpers\ErrorHandler;
use App\Helpers\NbuCurrencyExchangeCoursesService;
use App\Http\Resources\BusinessAccountTransactionResource;
use App\Models\BusinessAccountTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class BusinessAccountTransactionController extends Controller
{
    public string $section = 'business_account_transactions';

    public AuthAPI|false $authAPI;

    public function __construct(Request $request)
    {
        $this->authAPI = AuthAPI::isAuthenticated($request->bearerToken(), $request->ip());
    }

    private function getFieldsAndRulesForCreatingOrUpdatingSection(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'amount_on_card' => ['required', 'numeric', 'gte:0'],
            'amount_via_terminal' => ['required', 'numeric', 'gte:0'],
            'amount_as_cash' => ['required', 'numeric', 'gte:0'],
            'currency' => ['required', Rule::in(['UAH', 'USD', 'EUR'])],
            'type' => ['required', Rule::in(['income', 'outcome'])],
            'happened_at' => ['required', 'date_format:Y-m-d H:i'],
        ];
    }

    private function getFieldsAndRulesForFilteringSection(): array
    {
        $stringFilters = ['include', 'exclude', 'more', 'less', 'equal', 'notequal'];
        $dateFilters = ['include', 'more', 'less'];
        $numberFilters = ['more', 'less', 'equal', 'notequal'];
        $selectFilters = ['equal', 'notequal'];

        return [
            'title_filter_value' => ['string', 'nullable'],
            'title_filter_mode' => ['string', Rule::in($stringFilters), 'nullable'],
            'happened_at_from_filter_value' => ['string', 'nullable'],
            'happened_at_from_filter_mode' => ['string', Rule::in($dateFilters), 'nullable'],
            'happened_at_to_filter_value' => ['string', 'nullable'],
            'happened_at_to_filter_mode' => ['string', Rule::in($dateFilters), 'nullable'],
            'created_at_from_filter_value' => ['string', 'nullable'],
            'created_at_from_filter_mode' => ['string', Rule::in($dateFilters), 'nullable'],
            'created_at_to_filter_value' => ['string', 'nullable'],
            'created_at_to_filter_mode' => ['string', Rule::in($dateFilters), 'nullable'],
            'type_filter_value' => ['string', 'nullable'],
            'type_filter_mode' => ['string', Rule::in($selectFilters), 'nullable'],
            'total_price_filter_value' => ['string', 'nullable'],
            'total_price_filter_mode' => ['string', Rule::in($numberFilters), 'nullable'],
            'created_by_user_filter_value' => ['string', 'nullable'],
            'created_by_user_filter_mode' => ['string', Rule::in($stringFilters), 'nullable'],
        ];
    }

    private function getListOfFilteringFields(): array
    {
        $fieldsModesAndRules = $this->getFieldsAndRulesForFilteringSection();
        $fields = [];

        foreach ($fieldsModesAndRules as $key => $value) {
            $fieldName = str_replace(['_filter_mode', '_filter_value'], '', $key);
            $fields[] = $fieldName;
        }

        return array_unique($fields);
    }

    public function read(Request $request, NbuCurrencyExchangeCoursesService $coursesService): AnonymousResourceCollection|Response
    {
        $today = Carbon::today()->format('Ymd');
        $isAuthorized = $this->authAPI->isAuthorizedFor('read', $this->section);
        if (!$isAuthorized) {
            return ErrorHandler::responseWith('Доступ заборонено', 403);
        }

        $rules = [
            'itemsPerPage' => 'required|numeric',
            'page' => 'required|numeric',
            'orderValue' => ['string', Rule::in(['desc', 'asc']), 'nullable'],
            'orderField' => ['string', Rule::in(['id', 'title', 'happened_at', 'created_at', 'total_price', 'type', 'created_by_user']), 'nullable'],
            ...$this->getFieldsAndRulesForFilteringSection(),
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

        $section = BusinessAccountTransaction::query()->with('user');

        foreach ($this->getListOfFilteringFields() as $field) {
            $searchValue = $data["{$field}_filter_value"] ?? null;
            $filterMode = match ($field) {
                'happened_at_from', 'created_at_from' => 'more',
                'happened_at_to', 'created_at_to' => 'less',
                default => $data["{$field}_filter_mode"] ?? null
            };
            $searchOperator = $this->getWhereOperator($filterMode);

            if ($searchValue != null) {
                if ($field === 'happened_at_from' || $field === 'happened_at_to') {
                    $field = 'happened_at';
                }
                if ($field === 'created_at_from' || $field === 'created_at_to') {
                    $field = 'created_at';
                }

                if ($field === 'created_by_user') {
                    if (empty($searchValue)) {
                        continue;
                    }

                    if ($searchOperator === 'like') {
                        $section->whereHas('user', function ($query) use ($searchValue) {
                            $query->where('name', 'like', "%$searchValue%");
                        });

                        continue;
                    }
                    if ($searchOperator === 'notLike') {
                        $section->whereHas('user', function ($query) use ($searchValue) {
                            $query->whereNot(function ($query) use ($searchValue) {
                                $query->where('name', 'like', "%{$searchValue}%");
                            });
                        });

                        continue;
                    }

                    continue;
                }

                if ($field === 'total_price') {
                    if (empty($searchValue)) {
                        continue;
                    }

                    if (preg_match('/(^₴|^\$|^€)(\d*)/', $searchValue, $matches)) {
                        $currencySymbol = $matches[1] ?? null;
                        $amountOfMoney = (float) ($matches[2] ?? 0);
                        $currencyForSearching = match ($currencySymbol) {
                            '₴' => 'UAH',
                            '$' => 'USD',
                            '€' => 'EUR',
                            default => null,
                        };

                        if ($currencyForSearching !== null) {
                            $section->where('currency', '=', $currencyForSearching);
                        }
                        if ($amountOfMoney > 0) {
                            $section->where('total_price', $searchOperator, $amountOfMoney);
                        }

                        continue;
                    } else {
                        // If no currency symbol, just search by amount
                        $section->where('total_price', $searchOperator, $searchValue);
                        continue;
                    }
                }

                if ($searchOperator === 'like') {
                    $section->where($field, 'like', "%{$searchValue}%");

                    continue;
                }
                if ($searchOperator === 'notLike') {
                    $section->whereNot(function ($query) use ($searchValue, $field) {
                        $query->where($field, 'like', "%{$searchValue}%");
                    });

                    continue;
                }
                $section->where($field, $searchOperator, $searchValue);
            }
        }

        if (!empty($data['orderField']) && !empty($data['orderValue'])) {
            $field = $data['orderField'];
            $section->orderBy($field, $data['orderValue']);
        } else {
            $section->latest('happened_at');
        }

        $paginated = $section->paginate($data['itemsPerPage']);

        BusinessAccountTransactionResource::$dollarCurrencyExchangeCoefficient = $coursesService->getCourses($today, 'USD');
        BusinessAccountTransactionResource::$euroCurrencyExchangeCoefficient = $coursesService->getCourses($today, 'EUR');

        return BusinessAccountTransactionResource::collection($paginated);
    }

    public function create(Request $request): Response
    {
        $isAuthorized = $this->authAPI->isAuthorizedFor('create', $this->section);
        if (!$isAuthorized) {
            return ErrorHandler::responseWith('Доступ заборонено', 403);
        }

        $rules = $this->getFieldsAndRulesForCreatingOrUpdatingSection();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ErrorHandler::responseWith(json_encode($validator->errors()));
        }

        try {
            $data = $validator->validated();
        } catch (ValidationException $e) {
            return ErrorHandler::responseWith($e->getMessage());
        }

        $data['total_price'] = $data['amount_on_card'] + $data['amount_via_terminal'] + $data['amount_as_cash'];
        $data['created_by_user_id'] = $this->authAPI->user->id;

        $transaction = BusinessAccountTransaction::create($data);

        $today = Carbon::today()->format('Ymd');
        BusinessAccountTransactionResource::$dollarCurrencyExchangeCoefficient = $this->getNbuCurrencyExchangeCourses($today, 'USD');
        BusinessAccountTransactionResource::$euroCurrencyExchangeCoefficient = $this->getNbuCurrencyExchangeCourses($today, 'EUR');

        return response()->json(['transaction' => BusinessAccountTransactionResource::make($transaction->load('user'))]);
    }

    public function update(Request $request, int $id): Response
    {
        $isAuthorized = $this->authAPI->isAuthorizedFor('update', $this->section);
        if (!$isAuthorized) {
            return ErrorHandler::responseWith('Доступ заборонено', 403);
        }

        $transaction = BusinessAccountTransaction::find($id);
        if (!$transaction) {
            return ErrorHandler::responseWith('Транзакцію не знайдено', 404);
        }

        $rules = $this->getFieldsAndRulesForCreatingOrUpdatingSection();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ErrorHandler::responseWith(json_encode($validator->errors()));
        }

        try {
            $data = $validator->validated();
        } catch (ValidationException $e) {
            return ErrorHandler::responseWith($e->getMessage());
        }

        $data['total_price'] = $data['amount_on_card'] + $data['amount_via_terminal'] + $data['amount_as_cash'];

        $transaction->update($data);

        $today = Carbon::today()->format('Ymd');
        BusinessAccountTransactionResource::$dollarCurrencyExchangeCoefficient = $this->getNbuCurrencyExchangeCourses($today, 'USD');
        BusinessAccountTransactionResource::$euroCurrencyExchangeCoefficient = $this->getNbuCurrencyExchangeCourses($today, 'EUR');

        return response()->json(['transaction' => BusinessAccountTransactionResource::make($transaction->load('user'))]);
    }

    public function delete(Request $request, int $id): Response
    {
        $isAuthorized = $this->authAPI->isAuthorizedFor('delete', $this->section);
        if (!$isAuthorized) {
            return ErrorHandler::responseWith('Доступ заборонено', 403);
        }

        $transaction = BusinessAccountTransaction::find($id);
        if (!$transaction) {
            return ErrorHandler::responseWith('Транзакцію не знайдено', 404);
        }

        $transaction->delete();

        return response('OK', 200);
    }

    private function getNbuCurrencyExchangeCourses(string $date, string $currencyCode): float
    {
        $nbuService = new NbuCurrencyExchangeCoursesService();
        return $nbuService->getCourses($date, $currencyCode);
    }

    private function getWhereOperator($operatorName): string
    {
        $equality = [
            'include' => 'like',
            'exclude' => 'notLike',
            'more' => '>',
            'less' => '<',
            'equal' => '=',
            'notequal' => '<>',
        ];

        return $equality[$operatorName] ?? 'like';
    }
}
