<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Helpers\AuthAPI;

class WarehouseController extends Controller
{
    public $section = "warehouses";
    //field names used for making changes in db
    public $readFieldsInDB = ["countries.name", "cities.name", "warehouses.address", "warehouses.name", "warehouses.description"];
    //field names used for validating incoming data
    public $readFieldsFromFrontend = ["country_name", "city_name", "address", "name", "description"];

    public $createFieldsFromFrontend = ["country_id", "city_id", "address", "name", "description"];
    //rules, which are used to validate incoming data, according to field names sequence
    public $createValidationRulesForFields = ["required|numeric|exists:countries,id", "required|numeric|exists:cities,id", "required|string|max:250", "required|string|max:100", "required|string|max:250"];

    public $updateFieldsFromFrontend = ["country_id", "city_id", "address", "name", "description"];
    public $updateValidationRulesForFields = ["required|numeric|exists:countries,id", "required|numeric|exists:cities,id", "required|string|max:250", "required|string|max:100", "required|string|max:250"];


    /**
     * Templated access to section model
     *
     * @return \App\Models\Warehouse
     */
    public function getSectionModel() {
        return new Warehouse;
    }

    /**
     * Display a list of items.
     *
     * @return \Illuminate\Http\Response
     */
    public function read(Request $request)
    {
        $compiledRegexRule = "";
        $validationRules = [
            "itemsPerPage" => "required|numeric",
            "page" => "required|numeric",
            "orderValue" => ["string", "regex:/^desc$|^asc$/i", "nullable"],
        ];

        foreach ($this->readFieldsFromFrontend as $key => $field) {
            /**
             * forming regular exp. for validation of ordering depending on fields name
             * (makes possible to set order only with existing fields)
             */
            $compiledRegexRule .= "^{$field}$";
            $key != count($this->readFieldsFromFrontend) - 1 ? $compiledRegexRule .= "|" : "";

            /**
             * setting validation rule for each field
             * (filter value of its field and filter mode)
             */
            $validationRules["{$field}FilterValue"] = "string|nullable";
            $validationRules["{$field}FilterMode"] = ["string", "regex:/^include$|^exclude$|^more$|^less$|^equal$|^notequal$/i", "nullable"];
        }

        $validationRules["orderField"] = ["string", "regex:/^id$|{$compiledRegexRule}/i", "nullable"];
        $data = $request->validate($validationRules);

        $section = DB::table($this->section);
        $section->select(
            'warehouses.id',
            'countries.id as country_id',
            'countries.name as country_name',
            'cities.id as city_id',
            'cities.name as city_name',
            'warehouses.address',
            'warehouses.name',
            'warehouses.description'
        )
        ->join('cities', 'warehouses.city_id', '=', 'cities.id')
        ->join('countries', 'warehouses.country_id', '=', 'countries.id');

        //forming 'WHERE' query for each field
        foreach ($this->readFieldsInDB as $key => $field) {
            $searchValue = $data["{$this->readFieldsFromFrontend[$key]}FilterValue"];
            //'WHERE' operator like: '>', '<>', etc
            $searchOperator = $this->getWhereOperator($data["{$this->readFieldsFromFrontend[$key]}FilterMode"]);

            //special conditions in case of 'like' or 'notLike' operator
            if ($searchValue != null) {
                if ($searchOperator === "like") {
                    $section->where($field, "like", "%{$searchValue}%");
                } elseif ($searchOperator === "notLike") {
                    $section->whereNot(function ($query) use ($searchValue, $field) {
                        $query->where($field, "like", "%{$searchValue}%");
                    });
                } else {
                    $section->where($field, $searchOperator, $searchValue);
                }
            }
        }

        //forming 'ORDER BY' params
        if (!empty($data["orderField"]) && !empty($data["orderValue"])) {
            $section = $section->orderBy($data["orderField"], $data["orderValue"]);
        } else {
            $section = $section->orderBy("{$this->section}.created_at", "desc");
        }

        return response($section->paginate($data["itemsPerPage"]));
    }

    public function create(Request $request) {
        $sectionModel = $this->getSectionModel();
        $rules = [];

        foreach ($this->createFieldsFromFrontend as $key => $field) {
            $rules[$field] = $this->createValidationRulesForFields[$key];
        }

        $data = $request->validate($rules);

        return $sectionModel::create($data);
    }

    public function update(Request $request, $id) {
        $sectionModel = $this->getSectionModel();
        $rules = [];

        foreach ($this->updateFieldsFromFrontend as $key => $field) {
            $rules[$field] = $this->updateValidationRulesForFields[$key];
        }

        $data = $request->validate($rules);
        $section = $sectionModel::find($id);

        if ($section) {
            foreach ($this->updateFieldsFromFrontend as $key => $field) {
                $section->{$field} = $data[$field];
            }
            $section->save();
            $section->country;
            $section->city;


            return response($section);
        }

        return response("Не знайдено", 404);
    }

    public function delete(Request $request, $id) {
        $sectionModel = $this->getSectionModel();
        $section = $sectionModel::find($id);

        if ($section) {
            $section->delete();
            return response("OK", 200);
        }

        return response("Не знайдено", 404);
    }

    private function getWhereOperator($operatorName) {
        $equality = [
            "include" => "like",
            "exclude" => "notLike",
            "more" => ">",
            "less" => "<",
            "equal"=> "=",
            "notequal" => "<>"
        ];

        return $equality[$operatorName];
    }

}
