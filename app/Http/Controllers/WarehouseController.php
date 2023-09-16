<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\Warehouse;
use App\Models\UsersFavoriteWarehouses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\ErrorHandler;
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
    public $createValidationRulesForFields = ["required|numeric|exists:countries,id", "required|numeric|exists:cities,id", "required|string|max:250", "required|string|max:100", "required|string|max:1000"];

    public $updateFieldsFromFrontend = ["country_id", "city_id", "address", "name", "description"];
    public $updateValidationRulesForFields = ["required|numeric|exists:countries,id", "required|numeric|exists:cities,id", "required|string|max:250", "required|string|max:100", "required|string|max:1000"];

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

    /**
     * Display a list of warehouses depends on city id and warehouse name
     * (use for "select" item (<select></select>))
     *
     * @param Request
     * @param integer $id City ID passed through URL, if -1 = ignore city
     *
     * @return \Illuminate\Http\Response
     */
    public function simpleRead(Request $request, $id) {
        $data = $request->validate([
            "nameFilterValue" => "string|nullable",
        ]);

        //if we are using city_id
        if ($id != -1) {
            $city = City::find($id);
            $warehouses = [];

            if ($city == null)
                return ErrorHandler::responseWith("Міста не знайдено", 404);

            if (empty($data["nameFilterValue"]) || $data["nameFilterValue"] == null) {
                $query = $city->warehouses()->orderBy('name', 'asc');
            } else {
                $query = $city->warehouses()
                    ->where('name', 'like', "%{$data["nameFilterValue"]}%")
                    ->orderBy('name', 'asc');
            }
        }

        //if we are not using city_id
        if ($id == -1) {
            $query = DB::table("warehouses")
            ->select(
                "warehouses.id AS id",
                "countries.name AS country_name",
                "cities.name AS city_name",
                "warehouses.name AS name",
                "warehouses.address AS address",
                "warehouses.description AS description",
            )
            ->join("countries", "countries.id", "=", "warehouses.country_id")
            ->join("cities", "cities.id", "=", "warehouses.city_id")
            ->where("countries.name", "like", "%{$data["nameFilterValue"]}%")
            ->orWhere("cities.name", "like", "%{$data["nameFilterValue"]}%")
            ->orWhere("warehouses.name", "like", "%{$data["nameFilterValue"]}%")
            ->orWhere("warehouses.address", "like", "%{$data["nameFilterValue"]}%")
            ->orWhere("warehouses.description", "like", "%{$data["nameFilterValue"]}%")
            ->orderBy("countries.name", "asc")
            ->orderBy("cities.name", "asc")
            ->orderBy("warehouses.name", "asc")
            ->orderBy("warehouses.address", "asc")
            ->orderBy("warehouses.description", "asc");
        }

        $warehouses = $query->limit(5)->get();

        return response($warehouses);
    }

    public function create(Request $request) {
        $sectionModel = $this->getSectionModel();
        $rules = [];

        foreach ($this->createFieldsFromFrontend as $key => $field) {
            $rules[$field] = $this->createValidationRulesForFields[$key];
        }

        $data = $request->validate($rules);

        if ($this->isItemExists($data))
            return ErrorHandler::responseWith("Неможливо створити: такий склад вже існує");

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
            if ($this->isItemExists($data, $id))
                return ErrorHandler::responseWith("Неможливо оновити: такий склад вже існує");

            foreach ($this->updateFieldsFromFrontend as $key => $field) {
                $section->{$field} = $data[$field];
            }

            $section->save();
            $section->country;
            $section->city;

            return response($section);
        }

        return ErrorHandler::responseWith("Склад не знайдено", 404);
    }

    /**
     * Delete section (row) from DB by ID
     *
     * @param Request $request
     * @param integer $id Passed through URL
     *
     * @return Response
     */
    public function delete(Request $request, $id) {
        $sectionModel = $this->getSectionModel();
        $section = $sectionModel::find($id);

        if ($section == null)
            return ErrorHandler::responseWith("Склад не знайдено");
        if (count($section->items()) > 0)
            return ErrorHandler::responseWith("Неможливо видалити: склад повинен бути пустим");

        $section->delete();

        return response("OK", 200);
    }

    /**
     * Set user favorite warehouses
     *
     * @param Request $request
     */
    public function setUserFavoriteWarehouses(Request $request) {
        $userId = AuthAPI::isAuthenticated($request->bearerToken(), $request->ip())->user->id;

        $data = $request->validate([
            "warehouses" => "nullable",
            "warehouses.*" => "numeric|exists:warehouses,id",
        ]);

        UsersFavoriteWarehouses::where("user_id", $userId)->delete();

        if ($data["warehouses"] === null) return [];

        $warehousesIds = array_unique($data["warehouses"]);
        foreach ($warehousesIds as $key => $warehouseId) {
            UsersFavoriteWarehouses::create([
                "user_id" => $userId,
                "warehouse_id" => $warehouseId,
            ]);
        }

        return response()->json(UsersFavoriteWarehouses::where("user_id", $userId)->get()->toArray());
    }

    /**
     * Get user favorite warehouses
     *
     * @param Request $request
     */
    public function getUserFavoriteWarehouses(Request $request) {
        $userId = AuthAPI::isAuthenticated($request->bearerToken(), $request->ip())->user->id;

        $warehousesInfo = [];
        $userFavoriteWarehouses = UsersFavoriteWarehouses::where("user_id", $userId)->get()->toArray();

        foreach ($userFavoriteWarehouses as $key => $userFavoriteWarehouse) {
            $warehouse = Warehouse::find($userFavoriteWarehouse["warehouse_id"]);
            $country = $warehouse->country;
            $city = $warehouse->city;
            unset($warehouse["country"]);
            unset($warehouse["city"]);

            $warehousesInfo[] = [
                "country" => $country,
                "city" => $city,
                "warehouse" => $warehouse,
            ];
        }

        return response()->json($warehousesInfo);
    }

    /**
     * Checks if item with same characteristic does
     * exist in "warehouses" table. If passing ID - ignore its value while
     * filtering (where id <> $ID) to avoid update item collision
     * (looks for similarity in items wich are different from item
     * with passed ID)
     *
     * @param integer  $id    Item ID (for update case), null - default value
     * @param array    $itemData  Contains validated values from request
     *
     * @return boolean Is item with passed params exists in "warehouses" table
     */
    private function isItemExists($itemData, $id = null) {
        $sectionModel = $this->getSectionModel();
        $matchingItems = $sectionModel::
          where("country_id", $itemData["country_id"])
        ->where("city_id", $itemData["city_id"])
        ->where("address", $itemData["address"])
        ->where("name", $itemData["name"]);

        if ($id != null) $matchingItems->where("id", "<>", $id);

        $matchingItems = $matchingItems->get();

        return count($matchingItems) > 0 ? true : false;
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
