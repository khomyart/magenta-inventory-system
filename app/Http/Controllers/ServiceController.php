<?php

namespace App\Http\Controllers;

use App\Helpers\AuthAPI;
use App\Helpers\ErrorHandler;
use App\Helpers\NbuCurrencyExchangeCoursesService;
use App\Http\Resources\ServiceResource;
use App\Models\OrderService;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ServiceController extends Controller
{
    public string $section = 'services';

    public AuthAPI|false $authAPI;

    public function __construct(Request $request)
    {
        $this->authAPI = AuthAPI::isAuthenticated($request->bearerToken(), $request->ip());
    }

    private function getFieldsAndRulesForCreatingOrUpdatingSection(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'gte:0.01'],
            'currency' => ['required', Rule::in(['UAH', 'USD', 'EUR'])],
        ];
    }

    private function getFieldRulesForOrderingSection(): array
    {
        return [
            'orderField' => ['string', Rule::in(['id', 'title', 'price']), 'nullable'],
        ];
    }

    private function getFieldsAndRulesForFilteringSection(): array
    {
        $stringFilters = ['include', 'exclude', 'equal', 'notequal'];
        $numericFilters = ['more', 'less', 'equal', 'notequal'];

        return [
            'title_filter_value' => ['string', 'nullable'],
            'title_filter_mode' => ['string', Rule::in($stringFilters), 'nullable'],
            'price_filter_value' => ['string', 'nullable'],
            'price_filter_mode' => ['string', Rule::in($numericFilters), 'nullable'],
        ];
    }

    private function getListOfFilteringFields(): array
    {
        return ['title', 'price'];
    }

    public function getSectionModel(): Service
    {
        return new Service();
    }

    public function read(Request $request, NbuCurrencyExchangeCoursesService $coursesService): AnonymousResourceCollection|Response
    {
        $today = Carbon::today()->format('Ymd');
        $rules = [
            'itemsPerPage' => 'required|numeric',
            'page' => 'required|numeric',
            'orderValue' => ['string', Rule::in(['desc', 'asc']), 'nullable'],
            ...$this->getFieldsAndRulesForFilteringSection(),
            ...$this->getFieldRulesForOrderingSection(),
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

        $section = Service::query();

        foreach ($this->getListOfFilteringFields() as $field) {
            $searchValue = $data["{$field}_filter_value"];
            $searchOperator = $this->getWhereOperator($data["{$field}_filter_mode"]);

            if ($searchValue !== null) {
                if ($field === 'price') {
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
                            $section->where('price', $searchOperator, $amountOfMoney);
                        }

                        continue;
                    }
                }

                if ($searchOperator === 'like') {
                    $section->where($field, 'like', "%{$searchValue}%");
                } else {
                    $section->where($field, $searchOperator, $searchValue);
                }
            }
        }

        if (! empty($data['orderField']) && ! empty($data['orderValue'])) {
            $section = $section->orderBy($data['orderField'], $data['orderValue']);
        } else {
            $section = $section->latest();
        }

        $paginatedSection = $section->paginate($data['itemsPerPage']);

        ServiceResource::$dollarCurrencyExchangeCoefficient = $coursesService->getCourses($today, 'USD');
        ServiceResource::$euroCurrencyExchangeCoefficient = $coursesService->getCourses($today, 'EUR');

        return ServiceResource::collection($paginatedSection);
    }

    public function simpleSearch(Request $request): Response
    {
        $title = $request->query('title', '');
        $limit = (int) $request->query('limit', 50);

        try {
            $services = Service::query()
                ->select('id', 'title', 'price AS unconverted_price', 'currency')
                ->where('title', 'LIKE', "%{$title}%")
                ->orderBy('title', 'asc')
                ->limit($limit)
                ->get();

            return response()->json([
                'data' => $services,
                'count' => $services->count(),
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::responseWith($e->getMessage(), 500);
        }
    }

    public function create(Request $request): Response
    {
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

        $service = $this->getSectionModel()::create($data);

        return response()->json(['service' => ServiceResource::make($service)]);
    }

    public function update(Request $request, int $id): Response
    {
        $section = Service::find($id);
        if (! $section) {
            return ErrorHandler::responseWith('Послугу не знайдено', 404);
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

        $section->update($data);

        $today = Carbon::today()->format('Ymd');
        $coursesService = new NbuCurrencyExchangeCoursesService();
        ServiceResource::$dollarCurrencyExchangeCoefficient = $coursesService->getCourses($today, 'USD');
        ServiceResource::$euroCurrencyExchangeCoefficient = $coursesService->getCourses($today, 'EUR');

        return response()->json(['service' => ServiceResource::make($section)]);
    }

    public function delete(Request $request, int $id): Response
    {
        $section = $this->getSectionModel()::find($id);
        if ($section == null) {
            return ErrorHandler::responseWith('Послугу не знайдено', 404);
        }

        // Check if item is referenced in orders
        if (OrderService::where('service_id', $section->id)->exists()) {
            return ErrorHandler::responseWith('Неможливо видалити: послуга використовується в замовленнях');
        }

        $section->delete();

        return response('OK', 200);
    }

    private function getWhereOperator(string $operatorName = null): string
    {
        $equality = [
            'include' => 'like',
            'exclude' => 'not like',
            'more' => '>',
            'less' => '<',
            'equal' => '=',
            'notequal' => '<>',
        ];

        return $equality[$operatorName] ?? '=';
    }
}
