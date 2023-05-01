<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Image;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    public $section = "items";
    public $currencyForSearching = null;
    //field names are used for accepting filters while searching db
    public $readFieldsInDB = ["items.article", "items.title", "converted_price_to_uah", "types.name", "genders.name", "sizes.value", "colors.description", "batches.amount_of_items", "units.name"];
    //field names are used for validating incoming data
    public $readFieldsFromFrontend = ["article", "title", "price", "type", "gender", "size", "color", "amount", "units"];

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
        $today = Carbon::now()->format("Ymd");

        $compiledRegexRule = "";
        $validationRules = [
            "itemsPerPage" => "required|numeric",
            "page" => "required|numeric",
            "orderValue" => ["string", "regex:/^desc$|^asc$/i", "nullable"],
        ];

        foreach ($this->readFieldsFromFrontend as $key => $field) {
            /**
             * forming regular exp. for validation of ordering, depending on fields name
             * (makes possible to set order only for existing ("prepeared") fields)
             */
            $compiledRegexRule .= "^{$field}$";
            $key != count($this->readFieldsFromFrontend) - 1 ? $compiledRegexRule .= "|" : "";

            /**
             * setting validation rule for each field
             * (field filter value and field filter mode)
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
            "items.price AS unconverted_price",
            "items.currency AS currency",
        )
        ->selectRaw('
            IF(items.currency = "USD", items.price * ?,
                IF(items.currency = "EUR", items.price * ?,
                    IF(items.currency = "UAH", items.price, NULL)
                )
            )
            AS converted_price_to_uah
            ',
            [
                $this->getNbuCurrencyExchangeCourse($today, "USD"),
                $this->getNbuCurrencyExchangeCourse($today, "EUR")
            ]
        )
        ->addSelect(
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
            DB::raw('SUM(batches.amount_of_items) AS amount'),
            "units.name AS unit_name",
            "units.name AS units", //extra field for ordering
            "units.description AS unit_description",
        )
        ->join('types', 'items.type_id', '=', 'types.id')
        ->join('units', 'items.unit_id', '=', 'units.id')
        ->leftJoin('genders', 'items.gender_id', '=', 'genders.id')
        ->leftJoin('sizes', 'items.size_id', '=', 'sizes.id')
        ->leftJoin('colors', 'items.color_id', '=', 'colors.id')
        ->leftJoin('batches', 'items.id', '=', 'batches.item_id');

        //forming 'WHERE' query for each field
        foreach ($this->readFieldsInDB as $key => $field) {
            $searchValue = $data["{$this->readFieldsFromFrontend[$key]}FilterValue"];
            //'WHERE' operator like: '>', '<>', etc
            $searchOperator = $this->getWhereOperator($data["{$this->readFieldsFromFrontend[$key]}FilterMode"]);

            //skip WHERE condition for "amount" param, HAVING will be used later
            if ($this->readFieldsFromFrontend[$key] === "amount") {
                continue;
            }

            //skip general WHERE condition cycle for "price" param, HAVING will be used later
            if ($this->readFieldsFromFrontend[$key] === "price") {
                if ($data["orderField"] === "price") {
                    $data["orderField"] = "converted_price_to_uah";
                }

                if (strlen($searchValue) === 0) {
                    continue;
                }

                //add WHERE condition for price, as a exception (with special symbols):
                $matches = [];
                preg_match('/(^₴|^\$|^€)(\d*)/', $searchValue, $matches);

                //if matches is not empty:
                if (count($matches) !== 0) {
                    $currencySymbol = $matches[1];
                    $amountOfMoney = $matches[2];

                    switch ($currencySymbol) {
                        case "₴":
                            $this->currencyForSearching = "UAH";
                            break;
                        case "$":
                            $this->currencyForSearching = "USD";
                            break;
                        case "€":
                            $this->currencyForSearching = "EUR";
                            break;
                    }
                }

                if (
                    $this->currencyForSearching !== null
                    && $searchOperator !== "like"
                    && $searchOperator !== "notLike"
                ) {
                    $searchValue = $amountOfMoney;
                    $section->where("items.currency", "=", $this->currencyForSearching);
                    //search by price only when $searchValue is not empty
                    if (strlen($searchValue) !== 0) {
                        $section->where("items.price", $searchOperator, $searchValue);
                    }
                }

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
            $section->havingRaw("SUM(batches.amount_of_items) {$amountSearchOperator} ?", [$amountFilterValue]);
        }

        //forming 'HAVING' query for "price" field
        $priceFilterValue = $data["priceFilterValue"];
        $priceSearchOperator = $this->getWhereOperator($data["priceFilterMode"]);

        if (
            $priceFilterValue != null
            && $priceSearchOperator !== "like"
            && $priceSearchOperator !== "notLike"
            && $this->currencyForSearching === null
        ) {
            $section->havingRaw("converted_price_to_uah {$priceSearchOperator} ?", [$priceFilterValue]);
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

        //binding additional data (images) to paginated and transformed query result
        foreach ($items["data"] as $key => &$item) {
            $item["images"] = Image::where("item_id", $item["id"])->orderBy("number_in_row", "asc")->get();
        }

        return response($items);
    }

    public function create(Request $request) {
        // return response()->json($request->input());
        $sectionModel = $this->getSectionModel();

        $itemData = $request->validate([
            "article" => "required|string|max:10",
            "title" => "required|string|max:255",
            "price" => "required|numeric|gte:1",
            "currency" => ["required", "string", "regex:/^UAH$|^USD$|^EUR$/i"],
            "lack" => "required|numeric|integer|gte:1",
            "type_id" => ["required", "exists:types,id"],
            "unit_id" => ["required", "exists:units,id"],
            "gender_id" => ["nullable", "exists:genders,id"],
            "size_id" => ["nullable", "exists:sizes,id"],
            "color_id" => ["nullable", "exists:colors,id"],
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
                    Batch::create([
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

    public function getItemPreparedToUpdate(Request $request, $id) {
        $item = Item::find($id);

        if ($item === null) return response("предмет не знайдено", 404);

        $item->type;
        $item->gender;
        $item->size;
        $item->color;
        $item->unit;
        $item->images;

        $item = json_decode(json_encode($item), true);

        foreach ($item["images"] as $key => $image) {
            $imageFile = Storage::disk("images")->get($image["src"]);
            $mimeType = Storage::disk("images")->mimeType($image["src"]);

            $base64StringPrefix = "data:{$mimeType};base64,";
            $base64File = base64_encode($imageFile);

            $item["images"][$key]["base64"] = "{$base64StringPrefix}{$base64File}";
            $item["images"][$key]["mimeType"] = $mimeType;
        }

        return response()->json($item);
    }

    public function update(Request $request, $id) {
        $sectionModel = $this->getSectionModel();
        $item = $sectionModel::find($id);

        if ($item === null) return response("Предмет не знайдено", 404);

        $itemData = $request->validate([
            "article" => "required|string|max:10",
            "title" => "required|string|max:255",
            "price" => "required|numeric|gte:1",
            "currency" => ["required", "string", "regex:/^UAH$|^USD$|^EUR$/i"],
            "lack" => "required|numeric|integer|gte:1",
            "type_id" => ["required", "exists:types,id"],
            "unit_id" => ["required", "exists:units,id"],
            "gender_id" => ["nullable", "exists:genders,id"],
            "size_id" => ["nullable", "exists:sizes,id"],
            "color_id" => ["nullable", "exists:colors,id"],
        ]);
        $imagesData = $request->validate([
            "images" => "nullable",
            "images.*" => "required|file|mimes:jpeg,jpg,png|max:5000",
        ]);
        $imagesData = !empty($imagesData) ? $imagesData["images"] : [];

        $item->article = $itemData["article"];
        $item->title = $itemData["title"];
        $item->price = $itemData["price"];
        $item->currency = $itemData["currency"];
        $item->lack = $itemData["lack"];
        $item->type_id = $itemData["type_id"];
        $item->unit_id = $itemData["unit_id"];

        $item->color_id = !empty($itemData["color_id"]) ? $itemData["color_id"] : null;
        $item->size_id = !empty($itemData["size_id"]) ? $itemData["size_id"] : null;
        $item->gender_id = !empty($itemData["gender_id"]) ? $itemData["gender_id"] : null;

        $item->save();

        //removing old images
        foreach($item->images as $key => $image){
            Storage::disk('images')->delete($image->src);
            $image->delete();
        }

        //saving images in db and on disk
        foreach ($imagesData as $key => $file) {
            $image = Image::create([
                "item_id" => $item->id,
                "src" => $file->store('/', 'images'),
                "number_in_row" => $key + 1
            ]);
        }

        $today = Carbon::now()->format("Ymd");
        $section = DB::table($this->section);
        $section->select(
            "items.id AS id",
            "items.article AS article",
            "items.title AS title",
            "items.price AS unconverted_price",
            "items.currency AS currency",
        )
        ->selectRaw('
            IF(items.currency = "USD", items.price * ?,
                IF(items.currency = "EUR", items.price * ?,
                    IF(items.currency = "UAH", items.price, NULL)
                )
            )
            AS converted_price_to_uah
            ',
            [
                $this->getNbuCurrencyExchangeCourse($today, "USD"),
                $this->getNbuCurrencyExchangeCourse($today, "EUR")
            ]
        )
        ->addSelect(
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
            DB::raw('SUM(batches.amount_of_items) AS amount'),
            "units.name AS unit_name",
            "units.name AS units", //extra field for ordering
            "units.description AS unit_description",
        )
        ->join('types', 'items.type_id', '=', 'types.id')
        ->join('units', 'items.unit_id', '=', 'units.id')
        ->leftJoin('genders', 'items.gender_id', '=', 'genders.id')
        ->leftJoin('sizes', 'items.size_id', '=', 'sizes.id')
        ->leftJoin('colors', 'items.color_id', '=', 'colors.id')
        ->leftJoin('batches', 'items.id', '=', 'batches.item_id')
        ->where("items.id", $item->id);
        $updatedItem = $section->get()[0];
        $updatedItem = json_decode(json_encode($updatedItem), true);

        //binding images to updated item
        $updatedItem["images"] = Image::where("item_id", $updatedItem["id"])->orderBy("number_in_row", "asc")->get();

        return response($updatedItem);
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
    /**
     * @return array with currencies (EUR, USD)
     *
     * date - yyyymm
     * currencyCode - USD|EUR|etc...
     */
    private function getNbuCurrencyExchangeCourse($date, $currencyCode) {
        $response = file_get_contents("https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?valcode={$currencyCode}&date={$date}&json");
        return json_decode($response, true)[0]["rate"];
    }

}
