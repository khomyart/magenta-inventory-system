<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitController extends Controller
{
    public $section = "units";
    public $fields = ["name", "description"];
    public $fieldsValidationRules = ["required|string|max:20", "required|string|max:50"];

    //templated access to section model
    public function getSectionModel() {
        return new Unit;
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
            $section = $section->latest();
        }

        return response($section->paginate($data["itemsPerPage"]));
    }

    public function simpleRead(Request $request) {
        $data = $request->validate([
            'nameFilterValue' => 'string|nullable',
        ]);
        $items = [];
        $sectionModel = $this->getSectionModel();

        if (empty($data["nameFilterValue"]) || $data["nameFilterValue"] == null) {
            $query = $sectionModel::orderBy('name', 'asc');
        } else {
            $query = $sectionModel::where('name', 'like', "%{$data["nameFilterValue"]}%")->orderBy('name', 'asc');
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
            foreach ($this->fields as $key => $field) {
                $section->{$field} = $data[$field];
            }
            $section->save();

            return response($section);
        }

        return response("Не знайдено", 404);
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

        if ($section == null) return response("Not found", 404);
        if ($section->items != null) return response("unit_is_used", 422);

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
}
