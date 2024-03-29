<?php

namespace App\Http\Controllers;

use App\Models\Gender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\ErrorHandler;

use App\Helpers\AuthAPI;

class GenderController extends Controller
{
    public $section = "genders";
    public $fields = ["name"];
    public $fieldsValidationRules = ["required|string|max:150"];
    public $queryResultLimiter = 15;

    //templated access to section model
    public function getSectionModel() {
        return new Gender;
    }

    /**
     * Display a listing of the resource.
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

        foreach ($this->fields as $key => $field) {
            //forming regular exp. for orderField validation
            $compiledRegexRule .= "^{$field}$";
            $key != count($this->fields) - 1 ? $compiledRegexRule .= "|" : "";

            //setting rules for each field
            $validationRules["{$field}FilterValue"] = "string|nullable";
            $validationRules["{$field}FilterMode"] = ["string", "regex:/^include$|^exclude$|^more$|^less$|^equal$|^notequal$/i", "nullable"];
        }

        $validationRules["orderField"] = ["string", "regex:/^id$|{$compiledRegexRule}/i", "nullable"];

        $data = $request->validate($validationRules);

        $section = DB::table($this->section);

        //forming where query
        foreach ($this->fields as $key => $field) {
            $searchValue = $data["{$field}FilterValue"];
            $searchOperator = $this->getWhereOperator($data["{$field}FilterMode"]);

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

        //ordering query
        if (!empty($data["orderField"]) && !empty($data["orderValue"])) {
            $section = $section->orderBy($data["orderField"], $data["orderValue"]);
        } else {
            $section = $section->orderBy("number_in_row", "asc");
        }

        $firstItemInRow = $this->getSectionModel()::orderBy("number_in_row", "asc")->limit(1)->get();
        $lastItemInRow = $this->getSectionModel()::orderBy("number_in_row", "desc")->limit(1)->get();

        $response = json_decode(json_encode($section->paginate($data["itemsPerPage"])), true);
        $response["first_item_number_in_row"] =
            count($firstItemInRow) === 0 ? null : $firstItemInRow[0]["number_in_row"];
        $response["last_item_number_in_row"] =
            count($lastItemInRow) === 0 ? null : $lastItemInRow[0]["number_in_row"];

        return response($response);
    }

    public function simpleRead(Request $request) {
        $data = $request->validate([
            'nameFilterValue' => 'string|nullable',
        ]);
        $items = [];
        $sectionModel = $this->getSectionModel();

        if (empty($data["nameFilterValue"]) || $data["nameFilterValue"] == null) {
            $query = $sectionModel::orderBy('number_in_row', 'asc');
        } else {
            $query = $sectionModel::where('name', 'like', "%{$data["nameFilterValue"]}%")->orderBy('number_in_row', 'asc');
        }

        $items = $this->queryResultLimiter != 0
            ? $query->limit($this->queryResultLimiter)->get()
            : $query->get();

        return response($items);
    }

    public function create(Request $request) {
        $sectionModel = $this->getSectionModel();
        $rules = [];

        foreach ($this->fields as $key => $field) {
            $rules[$field] = $this->fieldsValidationRules[$key];
        }

        $data = $request->validate($rules);
        $data["number_in_row"] = $this->getLastNumberInRow() + 1;

        if ($this->isItemExists($data)) return ErrorHandler::responseWith("Неможливо створити: такий гендер вже існує");

        return $sectionModel::create($data);
    }

    public function update(Request $request, $id) {
        $sectionModel = $this->getSectionModel();
        $rules = [];

        foreach ($this->fields as $key => $field) {
            $rules[$field] = $this->fieldsValidationRules[$key];
        }

        $data = $request->validate($rules);
        $section = $sectionModel::find($id);

        if ($section) {
            if ($this->isItemExists($data, $id)) return ErrorHandler::responseWith("Неможливо оновити: такий гендер вже існує");

            foreach ($this->fields as $key => $field) {
                $section->{$field} = $data[$field];
            }
            $section->save();

            return response($section);
        }

        return ErrorHandler::responseWith("Гендер не знайдено", 404);
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
            return ErrorHandler::responseWith("Гендер не знайдено", 404);
        if ($section->items != null)
            return ErrorHandler::responseWith("Неможливо видалити: гендер використовується в існуючих предметах");

        $section->delete();
        return response("OK", 200);
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

    private function getLastNumberInRow() {
        $sectionModel = $this->getSectionModel();
        $queryResult = $sectionModel::orderBy("number_in_row", "desc")->limit(1)->get();

        return count($queryResult) != 0 ? $queryResult[0]["number_in_row"] : 0;
    }

    public function moveInRow(Request $request, $id) {
        $data = $request->validate([
            "direction" => ["required", "string", "regex:/^up$|^down$/i"],
        ]);
        $direction = $data["direction"];

        $sectionModel = $this->getSectionModel();
        $currentElement = $sectionModel::find($id);

        if ($currentElement === null)
            return ErrorHandler::responseWith("Гендер не знайдено", 404);

        $previousElementInRow = $sectionModel
            ::where("number_in_row", ">", $currentElement["number_in_row"])
            ->orderBy("number_in_row", "asc")->limit(1)->get();
        $previousElementInRow = count($previousElementInRow) === 0 ? null : $previousElementInRow[0];

        $nextElementInRow = $sectionModel
            ::where("number_in_row", "<", $currentElement["number_in_row"])
            ->orderBy("number_in_row", "desc")->limit(1)->get();
        $nextElementInRow = count($nextElementInRow) === 0 ? null : $nextElementInRow[0];

        if ($direction === "up" && $nextElementInRow !== null) {
            $currentElementNumberInRow = $currentElement["number_in_row"];
            $nextElementNumberInRow = $nextElementInRow["number_in_row"];

            $currentElement->number_in_row = $nextElementNumberInRow;
            $nextElementInRow->number_in_row = $currentElementNumberInRow;

            $currentElement->save();
            $nextElementInRow->save();

            return response()->json([
                "previousElementInRow" => $previousElementInRow,
                "currentElement" => $currentElement,
                "nextElementInRow" => $nextElementInRow,
            ]);
        }

        if ($direction === "down" && $previousElementInRow !== null) {
            $currentElementNumberInRow = $currentElement["number_in_row"];
            $previousElementNumberInRow = $previousElementInRow["number_in_row"];

            $currentElement->number_in_row = $previousElementNumberInRow;
            $previousElementInRow->number_in_row = $currentElementNumberInRow;

            $currentElement->save();
            $previousElementInRow->save();

            return response()->json([
                "previousElementInRow" => $previousElementInRow,
                "currentElement" => $currentElement,
                "nextElementInRow" => $nextElementInRow,
            ]);
        }

        return ErrorHandler::responseWith("Неможливо виконати рух");
    }

    /**
     * Checks if item with same characteristic does
     * exist in "types" table. If passing ID - ignore its value while
     * filtering (where id <> $ID) to avoid update item collision
     * (looks for similarity in items wich are different from item
     * with passed ID)
     *
     * @param integer  $id        Item ID (for update case), null - default value
     * @param array    $itemData  Contains validated values from request
     *
     * @return boolean Is item with passed params exists in "types" table
     */
    private function isItemExists($itemData, $id = null) {
        $sectionModel = $this->getSectionModel();
        $matchingItems = $sectionModel::
          where("name", $itemData["name"]);

        if ($id != null) $matchingItems->where("id", "<>", $id);

        $matchingItems = $matchingItems->get();

        return count($matchingItems) > 0 ? true : false;
    }
}
