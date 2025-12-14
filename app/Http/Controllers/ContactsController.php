<?php

namespace App\Http\Controllers;

use App\Enums\ContactPreferredPlatform;
use App\Helpers\AuthAPI;
use App\Helpers\ErrorHandler;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ContactsController extends Controller
{
    public string $section = 'contacts';

    public AuthAPI|false $authAPI;

    public int $queryResultLimiter = 5;

    public function __construct(Request $request)
    {
        $this->authAPI = AuthAPI::isAuthenticated($request->bearerToken(), $request->ip());
    }

    private function getFieldsAndRulesForCreatingOrUpdatingSection(): array
    {
        $allowedPlatforms = ContactPreferredPlatform::getValues();

        return [
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'min:4', 'max:20'],
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
            'preferred_platforms' => 'required|array|min:1',
            'preferred_platforms.*' => ['required', 'string', Rule::in($allowedPlatforms)],
            'additional_info' => 'nullable|string|max:255',
        ];
    }

    private function getFieldRulesForOrderingSection(): array
    {
        $fields = ['name', 'phone', 'email', 'address', 'preferred_platforms', 'additional_info'];

        return [
            'orderField' => ['string', Rule::in($fields), 'nullable'],
        ];
    }

    private function getFieldsAndRulesForFilteringSection(): array
    {
        $stringFilters = ['include', 'exclude', 'more', 'less', 'equal', 'notequal'];

        return [
            'name_or_phone_filter_value' => ['string', 'nullable'],
            'name_or_phone_filter_mode' => ['string', Rule::in($stringFilters), 'nullable'],
            'name_filter_value' => ['string', 'nullable'],
            'name_filter_mode' => ['string', Rule::in($stringFilters), 'nullable'],
            'phone_filter_value' => ['string', 'nullable'],
            'phone_filter_mode' => ['string', Rule::in($stringFilters), 'nullable'],
            'email_filter_value' => ['string', 'nullable'],
            'email_filter_mode' => ['string', Rule::in($stringFilters), 'nullable'],
            'address_filter_value' => ['string', 'nullable'],
            'address_filter_mode' => ['string', Rule::in($stringFilters), 'nullable'],
            'preferred_platforms_filter_value' => ['string', 'nullable'],
            'preferred_platforms_filter_mode' => ['string', Rule::in($stringFilters), 'nullable'],
            'additional_info_filter_value' => ['string', 'nullable'],
            'additional_info_filter_mode' => ['string', Rule::in($stringFilters), 'nullable'],
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

    /**
     * Receive a current section model
     *
     * @return Contact
     */
    public function getSectionModel(): Contact
    {
        return new Contact();
    }

    /**
     * Display a listing of the resources.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|Response
     */
    public function read(Request $request): AnonymousResourceCollection|Response
    {
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
        $section = Contact::query();

        //forming where query
        foreach ($this->getListOfFilteringFields() as $field) {
            $searchValue = $data["{$field}_filter_value"];
            $filterMode = $data["{$field}_filter_mode"];
            $searchOperator = $this->getWhereOperator($filterMode);

            if ($field === 'name_or_phone' && ! empty($searchValue)) {
                $section->where('name', 'like', "%$searchValue%")
                    ->orWhere('phone', 'like', "%$searchValue%");

                continue;
            }

            if ($field === 'preferred_platforms' && ! empty($searchValue)) {
                $searchValues = explode(',', $searchValue);
                $searchValues = array_map(fn ($el) => strtolower(trim($el)), $searchValues);

                foreach ($searchValues as $preferredPlatform) {
                    if ($searchOperator === 'like') {
                        $section->where($field, 'like', "%{$preferredPlatform}%");

                        continue;
                    }
                    if ($searchOperator === 'notLike') {
                        $section->whereNot(function ($query) use ($preferredPlatform, $field) {
                            $query->where($field, 'like', "%{$preferredPlatform}%");
                        });

                        continue;
                    }
                    $section->where($field, $searchOperator, $preferredPlatform);
                }

                continue;
            }

            if ($searchValue != null) {
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

        //ordering a query
        if (! empty($data['orderField']) && ! empty($data['orderValue'])) {
            $section = $section->orderBy($data['orderField'], $data['orderValue']);
        } else {
            $section = $section->latest();
        }

        $paginatedSection = $section->paginate($data['itemsPerPage']);

        return ContactResource::collection($paginatedSection);
    }

    /**
     * Display a listing of the resources.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|Response
     */
    public function simpleRead(Request $request): AnonymousResourceCollection|Response
    {
        $data = $request->validate([
            'search_filter_value' => 'string|nullable',
        ]);
        $sectionModel = $this->getSectionModel();

        if (empty($data['search_filter_value'])) {
            $query = $sectionModel::orderBy('id', 'asc');
        } else {
            $query = $sectionModel::query()
                ->where('name', 'like', "%{$data['search_filter_value']}%")
                ->orWhere('phone', 'like', "%{$data['search_filter_value']}%")
                ->orderBy('id');
        }

        $items = $this->queryResultLimiter != 0
            ? $query->limit($this->queryResultLimiter)->get()
            : $query->get();

        return ContactResource::collection($items);
    }

    public function create(Request $request): Response
    {
        $sectionModel = $this->getSectionModel();

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

        if (Contact::query()->where([['phone', $data['phone']]])->exists()) {
            return ErrorHandler::responseWith('Контакт з таким номером вже існує');
        }

        $contact = $sectionModel::create($data);

        return response()->json(['contact' => ContactResource::make($contact)]);
    }

    public function update(Request $request, int $id): Response
    {
        $section = Contact::where('id', $id)->first();
        if (! $section) {
            return ErrorHandler::responseWith('Контакт не знайдено');
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

        if (Contact::query()->where('phone', $data['phone'])->whereNot('id', $id)->exists()) {
            return ErrorHandler::responseWith('Контакт з таким номером вже існує');
        }

        foreach ($data as $field => $value) {
            $section->$field = $value;
        }
        $section->save();

        return response()->json(['contact' => ContactResource::make($section)]);
    }

    /**
     * Delete section (row) from DB by ID
     *
     * @param  Request  $request
     * @param  int  $id Passed through URL
     * @return Response
     */
    public function delete(Request $request, int $id): Response
    {
        $sectionModel = $this->getSectionModel();
        $section = $sectionModel::query()->find($id);

        if ($section == null) {
            return ErrorHandler::responseWith('Контакт не знайдено');
        }

        // Check if contact is used in any orders
        if ($section->orders()->exists()) {
            return ErrorHandler::responseWith('Неможливо видалити контакт, оскільки він використовується в замовленнях');
        }

        $section->delete();

        return response('OK', 200);
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

        return $equality[$operatorName];
    }
}
