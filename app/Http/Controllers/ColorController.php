<?php

namespace App\Http\Controllers;

use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Helpers\ErrorHandler;

class ColorController extends Controller
{
    public $section = "colors";
    public $fields = ["value", "article", "description", "text_color_value"];
    public $fieldsValidationRules = ["required|string|max:10", "required|string|max:10", "required|string|max:128", "required|string|max:10"];

    //templated access to section model
    public function getSectionModel() {
        return new Color;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function read(Request $request) {
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
            $section = $section->latest();
        }

        return response($section->paginate($data["itemsPerPage"]));
    }

    public function simpleRead(Request $request) {
        $data = $request->validate([
            "nameFilterValue" => "string|nullable",
        ]);
        $items = [];
        $sectionModel = $this->getSectionModel();

        if (empty($data["nameFilterValue"]) || $data["nameFilterValue"] == null) {
            $query = $sectionModel::orderBy("description", "asc");
        } else {
            //if input param starts with "#" or with "!"
            switch ($data["nameFilterValue"][0]) {
                case "#":
                    $colorSearchValue = substr($data["nameFilterValue"], 1);
                    $query = $sectionModel::where("value", "like", "%{$colorSearchValue}%")->orderBy("description", "asc");
                    break;
                case "!":
                    $colorSearchValue = substr($data["nameFilterValue"], 1);
                    $query = $sectionModel::where("article", "like", "%{$colorSearchValue}%")->orderBy("description", "asc");
                    break;
                default:
                    $query = $sectionModel::where("description", "like", "%{$data["nameFilterValue"]}%")->orderBy("description", "asc");
                    break;
            }
        }

        $items = $query->limit(5)->get();

        return response($items);
    }

    public function create(Request $request) {
        $sectionModel = $this->getSectionModel();
        $rules = [];

        foreach ($this->fields as $key => $field) {
            $rules[$field] = $this->fieldsValidationRules[$key];
        }

        $data = $request->validate($rules);

        /**
         * Validate input data for uniqueness
         */
        if ($this->isItemExists($data))
            return ErrorHandler::responseWith("Неможливо створити: такий колір вже існує");

        $dataForSecondaryValidation = [
            ["value", "головний колір", $data["value"]],
            ["article", "артикль", $data["article"]],
            ["description", "опис", $data["description"]],
        ];

        foreach ($this->isItemFieldsValuesAreUnique($dataForSecondaryValidation) as $key => $field) {
            if ($field["isUnique"] == false)
                return ErrorHandler::responseWith("Неможливо створити: значення поля \"{$field['name']['translation']}\" повинно бути унікальним");
        }

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
            if ($this->isItemExists($data, $id))
                return ErrorHandler::responseWith("Неможливо оновити: такий колір вже існує");

            $dataForSecondaryValidation = [
                ["value", "головний колір", $data["value"]],
                ["article", "артикль", $data["article"]],
                ["description", "опис", $data["description"]],
            ];

            foreach ($this->isItemFieldsValuesAreUnique($dataForSecondaryValidation, $id) as $key => $field) {
                if ($field["isUnique"] == false)
                    return ErrorHandler::responseWith("Неможливо оновити: значення поля \"{$field['name']['translation']}\" повинно бути унікальним");
            }

            foreach ($this->fields as $key => $field) {
                $section->{$field} = $data[$field];
            }
            $section->save();

            return response($section);
        }

        return ErrorHandler::responseWith("Колір не знайдено", 404);
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
            return ErrorHandler::responseWith("Колір не знайдено", 404);
        if ($section->items != null)
            return ErrorHandler::responseWith("Неможливо видалити: колір використовується в існуючих предметах", 422);

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
          where("value", $itemData["value"])
        ->where("article", $itemData["article"])
        ->where("description", $itemData["description"]);

        if ($id != null) $matchingItems->where("id", "<>", $id);

        $matchingItems = $matchingItems->get();

        return count($matchingItems) > 0 ? true : false;
    }

    /**
     * Helps to get more control on fields uniqueness validation
     *
     * @param array    $data  Structure - [ [fieldName, fieldNameTranslation, fieldValue], [...] ]
     * @param integer  $id    Item ID (in case of updating)
     *
     * @return array   Structure - [ [isUnique: boolean
     *                                name: [
     *                                        original: string
     *                                        translation: string
     *                                      ]
     *                                value: mixed], [...] ]
     */
    private function isItemFieldsValuesAreUnique($data, $id = null) {
        $result = [];
        $sectionModel = $this->getSectionModel();

        foreach ($data as $key => $field) {
            $query =
                $sectionModel::where($field[0], $field[2]);
            /**
             * Exclude item itself from validation, if $id
             * is passed through
             */
            if ($id != null) $query->where("id", "<>", $id);
            $queryResult = $query->get();
            /**
             * Deciding is field value unique
             */
            $isUnique = true;
            if (count($queryResult) > 0) $isUnique = false;

            $result[] = [
                "isUnique" => $isUnique,
                "name" => [
                    "original" => $field[0],
                    "translation" => $field[1],
                ],
                "value" => $field[2]
            ];
        }

        return $result;
    }
}
