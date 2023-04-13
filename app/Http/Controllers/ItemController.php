<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Image;
use App\Models\ItemsPricesWarehouses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public $section = "items";
    //field names used for using filters in db
    public $readFieldsInDB = ["items.article", "items.title", "types.name", "genders.name", "sizes.value", "colors.description", "items_prices_warehouses.amount_of_items", "units.name"];
    //field names used for validating incoming data
    public $readFieldsFromFrontend = ["article", "title", "type", "gender", "size", "color", "amount", "units"];

    /**
     * Templated access to section model
     *
     * @return \App\Models\Item
     */
    public function getSectionModel() {
        return new Item;
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
            "items.id AS id",
            "items.article AS article",
            "items.title AS title",
            "types.name AS type_name",
            "types.number_in_row AS type", //extra field for ordering
            "types.article AS type_article",
            "genders.name AS gender",
            "sizes.value AS size_name",
            "sizes.number_in_row AS size", //extra field for ordering
            "sizes.description AS size_description",
            "colors.article AS color_article",
            "colors.description AS color_name",
            "colors.description AS color", //extra field for ordering
            "colors.value AS color_value",
            "colors.text_color_value AS text_color_value",
            DB::raw('SUM(items_prices_warehouses.amount_of_items) AS amount'),
            "units.name AS unit_name",
            "units.name AS units", //extra field for ordering
            "units.description AS unit_description",
        )
        ->join('types', 'items.type_id', '=', 'types.id')
        ->join('genders', 'items.gender_id', '=', 'genders.id')
        ->join('sizes', 'items.size_id', '=', 'sizes.id')
        ->join('colors', 'items.color_id', '=', 'colors.id')
        ->join('units', 'items.unit_id', '=', 'units.id')
        ->join('items_prices_warehouses', 'items.id', '=', 'items_prices_warehouses.item_id');

        //forming 'WHERE' query for each field
        foreach ($this->readFieldsInDB as $key => $field) {
            $searchValue = $data["{$this->readFieldsFromFrontend[$key]}FilterValue"];
            //'WHERE' operator like: '>', '<>', etc
            $searchOperator = $this->getWhereOperator($data["{$this->readFieldsFromFrontend[$key]}FilterMode"]);

            //skip WHERE condition for "amount" param, HAVING will be used later
            if ($this->readFieldsFromFrontend[$key] === "amount") {
                continue;
            }

            if ($searchValue != null) {
                //special condition for "color" param with "#" value
                if ($this->readFieldsFromFrontend[$key] === "color" && $searchValue[0] === "#") {
                    $field = "colors.value";
                    $searchValue = substr($searchValue, 1);

                    if ($data["orderField"] === "color") {
                        $data["orderField"] = "color_value";
                    }
                }

                //special condition for "color" param with "!" value
                elseif ($this->readFieldsFromFrontend[$key] === "color" && $searchValue[0] === "!") {
                    $field = "colors.article";
                    $searchValue = substr($searchValue, 1);

                    if ($data["orderField"] === "color") {
                        $data["orderField"] = "color_article";
                    }
                }

                if (strlen($searchValue) > 0) {
                    //special conditions in case of 'like' or 'notLike' operator
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
        }

        $section->groupBy('items.id');

        //forming 'HAVING' query for "amount" field
        $amountFilterValue = $data["amountFilterValue"];
        $amountSearchOperator = $this->getWhereOperator($data["amountFilterMode"]);

        if (
            $amountFilterValue != null
            && $amountSearchOperator !== "like"
            && $amountSearchOperator !== "notLike"
        ) {
            $section->havingRaw("SUM(items_prices_warehouses.amount_of_items) {$amountSearchOperator} ?", [$amountFilterValue]);
        }

        //ordering query
        if (!empty($data["orderField"]) && !empty($data["orderValue"])) {
            $section = $section->orderBy($data["orderField"], $data["orderValue"]);
        } else {
            $section = $section->orderBy("article", "asc");
        }

        //receiving all data from formed query, paginate it and transform to array
        $items = $section->paginate($data["itemsPerPage"]);
        $items = json_decode(json_encode($items), true);

        //binding addational data (images) to paginated and transformed query result
        foreach ($items["data"] as $key => &$item) {
            $item["images"] = Image::where("item_id", $item["id"])->orderBy("number_in_row", "asc")->get();
        }

        return response($items);
    }

    public function create(Request $request) {
        $sectionModel = $this->getSectionModel();

        $itemData = $request->validate([
            "article" => "required|string|max:10",
            "title" => "required|string|max:255",
            "type_id" => "required|exists:App\Models\Type,id",
            "gender_id" => "nullable|exists:App\Models\Gender,id",
            "size_id" => "nullable|exists:App\Models\Size,id",
            "color_id" => "nullable|exists:App\Models\Color,id",
            "unit_id" => "nullable|exists:App\Models\Unit,id",
        ]);

        $warehousesData = $request->validate([
            "warehouses" => "nullable",
            "warehouses.*.id" => "required|numeric",
            "warehouses.*.batches" => "required",
            "warehouses.*.batches.*.amount" => "required|numeric",
            "warehouses.*.batches.*.price" => "required|numeric",
            "warehouses.*.batches.*.currency" => ["required", "regex:/^UAH$|^USD$|^EUR$/i"],
        ]);

        $imagesData = $request->validate([
            "images" => "nullable",
            "images.*" => "required|file|mimes:jpeg,jpg,png|max:5000",
        ]);

        $warehousesData = !empty($warehousesData) ? $warehousesData["warehouses"] : [];
        $imagesData = !empty($imagesData) ? $imagesData["images"] : [];

        //create an item
        $item = Item::create($itemData);

        if ($item != null) {
            //saving images in db and on disk
            foreach ($imagesData as $key => $file) {
                $image = Image::create([
                    "item_id" => $item->id,
                    "src" => $file->store('/', 'images'),
                    "number_in_row" => $key + 1
                ]);
            }

            //pricessing warehouses info
            foreach ($warehousesData as $key => $warehouse) {
                foreach ($warehouse["batches"] as $key => $batch) {
                    ItemsPricesWarehouses::create([
                        "item_id" => $item->id,
                        "warehouse_id" => $warehouse["id"],
                        "price_per_item" => $batch["price"],
                        "currency" => $batch["currency"],
                        "amount_of_items" => $batch["amount"],
                    ]);
                }
            }
        }

        return response($itemData);
    }

    public function update(Request $request, $id) {
        return;
    }

    public function delete(Request $request, $id) {
        return;
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
