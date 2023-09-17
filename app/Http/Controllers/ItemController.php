<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Image;
use App\Models\Income;
use App\Models\Outcome;
use App\Models\Move;
use App\Models\Type;
use App\Models\Gender;
use App\Models\ItemWarehouseAmount;
use App\Helpers\ErrorHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Query\Builder;

class ItemController extends Controller
{
    public $section = "items";
    public $currencyForSearching = null;
    //field names are used for accepting filters while searching in db
    public $readFieldsInDB = ["items.group_id", "items.article", "items.title", "model", "converted_price_to_uah", "types.name", "genders.name", "sizes.value", "colors.description", "income.amount_of_items", "units.name"];
    //field names are used for validating incoming data
    public $readFieldsFromFrontend = ["group_id", "article", "title", "model", "price", "type", "gender", "size", "color", "amount", "units"];
    //if 0 - unlimited
    public $defaultQueryResultLimit = 0;

    private $nbuCurrencyExchangeResponse = null;

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
            "warehouseId" => "nullable|numeric|gte:0",
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
            "items.group_id AS group_id",
            "items.article AS article",
            "items.title AS title",
            "items.model AS model",
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
            "genders.name AS gender",
            "sizes.value AS size_name",
            "sizes.number_in_row AS size", //extra field for ordering
            "sizes.description AS size_description",
            "colors.article AS color_article",
            "colors.description AS color_name",
            "colors.description AS color", //extra field for ordering
            "colors.value AS color_value",
            "colors.text_color_value AS text_color_value",
            DB::raw('SUM(item_warehouse_amounts.amount) AS amount'),
            "units.name AS unit_name",
            "units.name AS units", //extra field for ordering
            "units.description AS unit_description",
        )
        ->join('types', 'items.type_id', '=', 'types.id')
        ->join('units', 'items.unit_id', '=', 'units.id')
        ->leftJoin('genders', 'items.gender_id', '=', 'genders.id')
        ->leftJoin('sizes', 'items.size_id', '=', 'sizes.id')
        ->leftJoin('colors', 'items.color_id', '=', 'colors.id')
        ->leftJoin('item_warehouse_amounts', 'items.id', '=', 'item_warehouse_amounts.item_id');

        //forming 'WHERE' query for each field
        foreach ($this->readFieldsInDB as $key => $field) {
            $searchValue = $data["{$this->readFieldsFromFrontend[$key]}FilterValue"];
            //'WHERE' operator: '>', '<>', etc
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

        /**
         * special WHERE condition in case of activating
         * a "searching by warehouse" mode
         */
        if (!empty($data["warehouseId"])) {
            $section->where("item_warehouse_amounts.warehouse_id", $data["warehouseId"]);
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
            $section->havingRaw("SUM(item_warehouse_amounts.amount_of_items) {$amountSearchOperator} ?", [$amountFilterValue]);
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
        $sectionModel = $this->getSectionModel();

        $itemData = $request->validate([
            "article" => "required|string|max:10",
            "group_id" => "required|string|max:36",
            "title" => "required|string|max:255",
            "model" => "required|string|max:255",
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
            "warehouses.*.id" => "required|numeric|exists:warehouses,id",
            "warehouses.*.batches" => "required",
            "warehouses.*.batches.*.amount" => "required|numeric|integer|gte:1",
            "warehouses.*.batches.*.price" => "required|numeric|gte:1",
            "warehouses.*.batches.*.currency" => ["required", "regex:/^UAH$|^USD$|^EUR$/i"],
        ]);

        $imagesData = $request->validate([
            "images" => "nullable",
            "images.*" => "required|file|mimes:jpeg,jpg,png|max:5000",
        ]);

        $warehousesData = !empty($warehousesData) ? $warehousesData["warehouses"] : [];
        $imagesData = !empty($imagesData) ? $imagesData["images"] : [];

        /**
         * Is item creation allowed:
         */
        $specialValidationResult = $this->getItemDataSpecialValidationResult($itemData);
        if ($specialValidationResult != null)
            return ErrorHandler::responseWith($specialValidationResult);
        if ($this->isItemExists($itemData))
            return ErrorHandler::responseWith("Такий предмет вже існує");

        /**
         * Create an item
         */
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

            //processing warehouses info
            foreach ($warehousesData as $key => $warehouse) {
                $amountOfItemPerWarehouse = 0;

                foreach ($warehouse["batches"] as $key => $batch) {
                    Income::create([
                        "item_id" => $item->id,
                        "warehouse_id" => $warehouse["id"],
                        "price_per_item" => $batch["price"],
                        "currency" => $batch["currency"],
                        "amount_of_items" => $batch["amount"],
                    ]);
                    $amountOfItemPerWarehouse += $batch["amount"];
                }

                $matchingItemWarehouseCombination = ItemWarehouseAmount::where("item_id", $item->id)
                ->where("warehouse_id", $warehouse["id"])->first();

                if ($matchingItemWarehouseCombination != null) {
                    $matchingItemWarehouseCombination->amount += $amountOfItemPerWarehouse;
                    $matchingItemWarehouseCombination->save();
                } else {
                    ItemWarehouseAmount::create([
                        "item_id" => $item->id,
                        "warehouse_id" => $warehouse["id"],
                        "amount" => $amountOfItemPerWarehouse,
                    ]);
                }
            }
        }

        return response($itemData);
    }

    public function createMultiple(Request $request) {
        $sectionModel = $this->getSectionModel();

        $itemsData = $request->validate([
            "items.*.article" => "required|string|max:10",
            "items.*.group_id" => "required|string|max:36",
            "items.*.title" => "required|string|max:255",
            "items.*.model" => "required|string|max:255",
            "items.*.price" => "required|numeric|gte:1",
            "items.*.currency" => ["required", "string", "regex:/^UAH$|^USD$|^EUR$/i"],
            "items.*.lack" => "required|numeric|integer|gte:1",
            "items.*.type_id" => ["required", "exists:types,id"],
            "items.*.unit_id" => ["required", "exists:units,id"],
            "items.*.gender_id" => ["nullable", "exists:genders,id"],
            "items.*.size_id" => ["nullable", "exists:sizes,id"],
            "items.*.color_id" => ["nullable", "exists:colors,id"],
        ]);

        $itemsWarehousesData = $request->validate([
            "items.*.warehouses" => "nullable",
            "items.*.warehouses.*.id" => "required|numeric|exists:warehouses,id",
            "items.*.warehouses.*.batches" => "required",
            "items.*.warehouses.*.batches.*.amount" => "required|numeric|integer|gte:1",
            "items.*.warehouses.*.batches.*.price" => "required|numeric|gte:1",
            "items.*.warehouses.*.batches.*.currency" => ["required", "regex:/^UAH$|^USD$|^EUR$/i"],
        ]);

        $itemsImagesData = $request->validate([
            "items.*.images" => "nullable",
            "items.*.images.*" => "required|file|mimes:jpeg,jpg,png|max:5000",
        ]);

        foreach ($itemsData["items"] as $itemIndex => $itemData) {
            $warehousesData =
                isset($itemsWarehousesData["items"][$itemIndex]) ?
                    $itemsWarehousesData["items"][$itemIndex]["warehouses"] : [];

            $imagesData =
                isset($itemsImagesData["items"][$itemIndex]) ?
                    $itemsImagesData["items"][$itemIndex]["images"] : [];

            /**
             * Is item creation allowed:
             */
            $specialValidationResult = $this->getItemDataSpecialValidationResult($itemData);
            if ($specialValidationResult != null)
                return ErrorHandler::responseWith($specialValidationResult);
            if ($this->isItemExists($itemData))
                return ErrorHandler::responseWith("Такий предмет вже існує");

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

                //processing warehouses info
                foreach ($warehousesData as $key => $warehouse) {
                    $amountOfItemPerWarehouse = 0;

                    foreach ($warehouse["batches"] as $key => $batch) {
                        Income::create([
                            "item_id" => $item->id,
                            "warehouse_id" => $warehouse["id"],
                            "price_per_item" => $batch["price"],
                            "currency" => $batch["currency"],
                            "amount_of_items" => $batch["amount"],
                        ]);
                        $amountOfItemPerWarehouse += $batch["amount"];
                    }

                    $matchingItemWarehouseCombination = ItemWarehouseAmount::where("item_id", $item->id)
                    ->where("warehouse_id", $warehouse["id"])->first();

                    if ($matchingItemWarehouseCombination != null) {
                        $matchingItemWarehouseCombination->amount += $amountOfItemPerWarehouse;
                        $matchingItemWarehouseCombination->save();
                    } else {
                        ItemWarehouseAmount::create([
                            "item_id" => $item->id,
                            "warehouse_id" => $warehouse["id"],
                            "amount" => $amountOfItemPerWarehouse,
                        ]);
                    }
                }
            }
        }

        return response("OK", 200);
    }

    public function setIncome(Request $request) {
        $data = $request->validate([
            "warehouses" => "required",
            "warehouses.*.id" => "required|numeric|exists:warehouses,id",
            "warehouses.*.batches" => "required",
            "warehouses.*.batches.*.amount" => "required|integer|gte:1",
            "warehouses.*.batches.*.price" => "required|numeric|gte:1",
            "warehouses.*.batches.*.currency" => ["required", "regex:/^UAH$|^USD$|^EUR$/i"],
            "warehouses.*.batches.*.items" => "required",
            "warehouses.*.batches.*.items.*.id" => "required|numeric|exists:items,id",
        ]);

        //processing warehouses info
        foreach ($data["warehouses"] as $key => $warehouse) {
            foreach ($warehouse["batches"] as $key => $batch) {
                foreach ($batch["items"] as $key => $item) {
                    Income::create([
                        "item_id" => $item["id"],
                        "warehouse_id" => $warehouse["id"],
                        "price_per_item" => $batch["price"],
                        "currency" => $batch["currency"],
                        "amount_of_items" => $batch["amount"],
                    ]);

                    //check if item has records in item_warehouse_amounts table about its amount
                    //if has - add batch["amount"] to its amount
                    //if not - create record with batch["amount"] number as its amount
                    $itemWarehouseAmounts = ItemWarehouseAmount::where("item_id", $item["id"])
                    ->where("warehouse_id", $warehouse["id"])->first();

                    if ($itemWarehouseAmounts != null) {
                        $itemWarehouseAmounts->amount += $batch["amount"];
                        $itemWarehouseAmounts->save();
                    } else {
                        ItemWarehouseAmount::create([
                            "item_id" => $item["id"],
                            "warehouse_id" => $warehouse["id"],
                            "amount" => $batch["amount"],
                        ]);
                    }
                }
            }
        }

        return response("OK", 200);
    }

    public function setOutcome(Request $request) {
        $data = $request->validate([
            "warehouseId" => "required|integer|numeric|exists:warehouses,id",
            "items" => "required",
            "items.*.id" => "required|integer|numeric|exists:items,id",
            "items.*.additionalReason" => "nullable|string|max:255",
            "items.*.reason" => "required|string|max:255",
            "items.*.reasonDetail" => "nullable|string|max:1000",
            "items.*.amount" => "required|numeric|gte:1|max:999999",
        ]);

        $isItemsExistsInWarehouse = true;
        $isOutcomeAmountOfItemIsLessThanActualAmount = true;

        foreach ($data["items"] as $key => $item) {
            $currentItem = ItemWarehouseAmount::where("item_id", $item["id"])
                ->where("warehouse_id", $data["warehouseId"])->first();

            if ($currentItem == null || $currentItem->amount == 0) {
                $isItemsExistsInWarehouse = false;
                break;
            }

            if ($item["amount"] > $currentItem->amount) {
                $isOutcomeAmountOfItemIsLessThanActualAmount = false;
                break;
            }
        }

        if ($isItemsExistsInWarehouse === false) {
            return ErrorHandler::responseWith("Предмета не існує на складах", 404);
        }
        if ($isOutcomeAmountOfItemIsLessThanActualAmount === false) {
            return ErrorHandler::responseWith("Списання перевищує наявність");
        }

        foreach ($data["items"] as $key => $item) {
            $currentItem = ItemWarehouseAmount::where("item_id", $item["id"])
                ->where("warehouse_id", $data["warehouseId"])->first();

            //create outcome
            Outcome::create([
                "item_id" => $item["id"],
                "warehouse_id" => $data["warehouseId"],
                "amount" => $item["amount"],
                "reason_name" => $item["reason"],
                "additional_reason_name" =>
                    $item["reason"] === "other" ? $item["additionalReason"] : null,
                "detail" => $item["reasonDetail"],
            ]);

            $differenceBetweenAmounts = $currentItem->amount - $item["amount"];

            if ($differenceBetweenAmounts == 0) {
                $currentItem->delete();
            } else {
                $currentItem->amount = $differenceBetweenAmounts;
                $currentItem->save();
            }
        }

        return response("OK", 200);
    }

    /**
     * Register moving of the items between warehouses
     *
     * @param Request $request
     */
    public function move(Request $request) {
        $data = $request->validate([
            "fromWarehouseId" => "required|integer|numeric|exists:warehouses,id",
            "toWarehouseId" => "required|integer|numeric|exists:warehouses,id",
            "items" => "required",
            "items.*.id" => "required|integer|numeric|exists:items,id",
            "items.*.additionalReason" => "nullable|string|max:255",
            "items.*.reason" => "required|string|max:255",
            "items.*.reasonDetail" => "nullable|string|max:1000",
            "items.*.amount" => "required|numeric|gte:1|max:999999",
        ]);

        if ($data["fromWarehouseId"] == $data["toWarehouseId"]) {
            return ErrorHandler::responseWith(
                "Склад \"звідки\" і склад \"куди\" не можуть бути однаковими"
            );
        }

        $isItemsExistsInWarehouse = true;
        $isOutcomeAmountOfItemIsLessThanActualAmount = true;

        foreach ($data["items"] as $key => $item) {
            $currentItem = ItemWarehouseAmount::where("item_id", $item["id"])
                ->where("warehouse_id", $data["fromWarehouseId"])->first();

            if ($currentItem == null || $currentItem->amount == 0) {
                $isItemsExistsInWarehouse = false;
                break;
            }

            if ($item["amount"] > $currentItem->amount) {
                $isOutcomeAmountOfItemIsLessThanActualAmount = false;
                break;
            }
        }

        if ($isItemsExistsInWarehouse === false) {
            return ErrorHandler::responseWith("Предмета не існує на складах", 404);
        }
        if ($isOutcomeAmountOfItemIsLessThanActualAmount === false) {
            return ErrorHandler::responseWith("Списання перевищує наявність");
        }

        foreach ($data["items"] as $key => $item) {
            //create move
            Move::create([
                "item_id" => $item["id"],
                "from_warehouse_id" => $data["fromWarehouseId"],
                "to_warehouse_id" => $data["toWarehouseId"],
                "amount" => $item["amount"],
                "reason_name" => $item["reason"],
                "additional_reason_name" =>
                    $item["reason"] === "other" ? $item["additionalReason"] : null,
                "detail" => $item["reasonDetail"],
            ]);

            $fromWarehouseItem = ItemWarehouseAmount::where("item_id", $item["id"])
                ->where("warehouse_id", $data["fromWarehouseId"])->first();
            $toWarehouseItem = ItemWarehouseAmount::where("item_id", $item["id"])
                ->where("warehouse_id", $data["toWarehouseId"])->first();

            //subtract item amount of fromWarehouseItem
            $differenceBetweenAmounts = $fromWarehouseItem->amount - $item["amount"];
            if ($differenceBetweenAmounts == 0) {
                $fromWarehouseItem->delete();
            } else {
                $fromWarehouseItem->amount = $differenceBetweenAmounts;
                $fromWarehouseItem->save();
            }

            //adding item amount for toWarehouseItem
            if ($toWarehouseItem != null) {
                $toWarehouseItem->amount += $item["amount"];
                $toWarehouseItem->save();
            } else {
                ItemWarehouseAmount::create([
                    "item_id" => $item["id"],
                    "warehouse_id" => $data["toWarehouseId"],
                    "amount" => $item["amount"],
                ]);
            }
        }

        return response("OK", 200);
    }

    /**
     * Give an item, or array of items (depends on request parameters)
     * as a response
     *
     * @param Request $request Contains three variables: "mode", "value", "warehouse_id":
     * mode -           way of building item search query (search by id, group_id or article)
     * value -          value, which is used for query forming
     * warehouse_id -   id of warehouse where searching need to be done (works in pair with article mode)
     *
     * @return array of items, or a single item as array
     */
    public function getItemsWithPreparedData(Request $request) {
        $filterData = $request->validate([
            "mode" => ["required", "string", "regex:/^id$|^group_id$|^article$/i"],
            "value" => "nullable|string|max:36",
            "warehouse_id" => "integer"
        ]);

        if ($filterData["value"] === null) return [];

        if ($filterData["mode"] == "id") {
            $item = Item::find($filterData["value"]);

            if ($item === null) return ErrorHandler::responseWith("Предмет не знайдено", 404);

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

        if ($filterData["mode"] == "group_id") {
            $items = Item::where("group_id", "=", $filterData['value'])
                ->orderBy("updated_at", "desc")
                ->get();
            if (count($items) === 0) return 0;

            foreach ($items as $key => &$item) {
                $item->type;
                $item->gender;
                $item->size;
                $item->color;
                $item->unit;
                $item->images;
            }

            $items = json_decode(json_encode($items), true);

            foreach ($items as $key => &$item) {
                foreach ($item["images"] as $key => $image) {
                    $imageFile = Storage::disk("images")->get($image["src"]);
                    $mimeType = Storage::disk("images")->mimeType($image["src"]);

                    $base64StringPrefix = "data:{$mimeType};base64,";
                    $base64File = base64_encode($imageFile);

                    $item["images"][$key]["base64"] = "{$base64StringPrefix}{$base64File}";
                    $item["images"][$key]["mimeType"] = $mimeType;
                }
            }

            return response()->json($items);
        }

        //article color_article size
        if ($filterData["mode"] == "article") {
            $params = explode(" ",  trim($filterData["value"]));

            $section = DB::table($this->section);

            //select forming
            $section->select(
                "items.id AS id",
                "items.article AS article",
                "sizes.value AS size",
                "colors.article AS color_article",
                "colors.value AS color_value",
                "colors.text_color_value AS text_color_value",
                "items.title AS title",

                DB::raw("(SELECT src FROM images i WHERE items.id = i.item_id LIMIT 1) AS image"),
            );

            if ($filterData["warehouse_id"] != 0) {
                $section->addSelect(
                    "item_warehouse_amounts.warehouse_id AS warehouse_id",
                    "item_warehouse_amounts.amount AS amount",
                );
            }

            //join forming
            $section->leftJoin("colors", "items.color_id", "=", "colors.id")
            ->leftJoin("sizes", "items.size_id", "=", "sizes.id");

            if ($filterData["warehouse_id"] != 0) {
                $section->rightJoin("item_warehouse_amounts", "items.id", "=", "item_warehouse_amounts.item_id");
            }

            //ordering forming
            $section->orderBy("items.article", "asc")
            ->orderBy("colors.article", "asc")
            ->orderBy("sizes.number_in_row", "asc");

            //"WHERE" statement forming
            if (count($params) == 1) {
                $section->orWhere(function(Builder $query) use ($params, $filterData) {
                    $query->where("items.article", "LIKE", "%{$params[0]}%");
                    if ($filterData["warehouse_id"] != 0)
                        $query->where("item_warehouse_amounts.warehouse_id", $filterData["warehouse_id"]);
                });
                $section->orWhere(function(Builder $query) use ($params, $filterData) {
                    $query->where("colors.article", "LIKE", "%{$params[0]}%");
                    if ($filterData["warehouse_id"] != 0)
                        $query->where("item_warehouse_amounts.warehouse_id", $filterData["warehouse_id"]);
                });
                $section->orWhere(function(Builder $query) use ($params, $filterData) {
                    $query->where("sizes.value", "LIKE", "%{$params[0]}%");
                    if ($filterData["warehouse_id"] != 0)
                        $query->where("item_warehouse_amounts.warehouse_id", $filterData["warehouse_id"]);
                });
            }

            if (count($params) == 2) {
                $section->orWhere(function(Builder $query) use ($params, $filterData) {
                    $query->where("items.article", "LIKE", "%{$params[0]}%")
                        ->where("colors.article", "LIKE", "%{$params[1]}%");
                    if ($filterData["warehouse_id"] != 0)
                        $query->where("item_warehouse_amounts.warehouse_id", $filterData["warehouse_id"]);
                });
                $section->orWhere(function(Builder $query) use ($params, $filterData) {
                    $query->where("items.article", "LIKE", "%{$params[0]}%")
                        ->where("sizes.value", "LIKE", "%{$params[1]}%");
                    if ($filterData["warehouse_id"] != 0)
                        $query->where("item_warehouse_amounts.warehouse_id", $filterData["warehouse_id"]);
                });
                $section->orWhere(function(Builder $query) use ($params, $filterData) {
                    $query->where("colors.article", "LIKE", "%{$params[0]}%")
                        ->where("sizes.value", "LIKE", "%{$params[1]}%");
                    if ($filterData["warehouse_id"] != 0)
                        $query->where("item_warehouse_amounts.warehouse_id", $filterData["warehouse_id"]);
                });
            }

            if (count($params) == 3) {
                $section->where("items.article", "LIKE", "%{$params[0]}%")
                ->where("colors.article", "LIKE", "%{$params[1]}%")
                ->where("sizes.value", "LIKE", "%{$params[2]}%");

                if ($filterData["warehouse_id"] != 0)
                        $section->where("item_warehouse_amounts.warehouse_id", $filterData["warehouse_id"]);
            }


            //additional query tweaks
            $amountOfItems = $section->count();
            if ($this->defaultQueryResultLimit != 0)
                $section->limit($this->defaultQueryResultLimit);

            $items = $section->get();

            return response()->json([
                "data" => $items,
                "amountOfItems" => $amountOfItems,
                "limitation" => $this->defaultQueryResultLimit
            ]);
        }

        ErrorHandler::responseWith("Неможливо виконати пошук: режим не знайдений");
    }

    public function update(Request $request, $id) {
        $sectionModel = $this->getSectionModel();
        $item = $sectionModel::find($id);

        if ($item === null) return ErrorHandler::responseWith("Предмет не знайдено");

        $itemData = $request->validate([
            "article" => "required|string|max:10",
            "title" => "required|string|max:255",
            "model" => "required|string|max:255",
            "price" => "required|numeric|gte:1",
            "currency" => ["required", "string", "regex:/^UAH$|^USD$|^EUR$/i"],
            "lack" => "required|numeric|integer|gte:1",
            "gender_id" => ["nullable", "exists:genders,id"],
            "size_id" => ["nullable", "exists:sizes,id"],
            "color_id" => ["nullable", "exists:colors,id"],
        ]);
        $imagesData = $request->validate([
            "images" => "nullable",
            "images.*" => "required|file|mimes:jpeg,jpg,png|max:5000",
        ]);
        $imagesData = !empty($imagesData) ? $imagesData["images"] : [];

        /**
         * Inserting data to itemData array not from request
         * to avoid unexpected data collisions
         */
        $itemData["group_id"] = $item->group_id;
        $itemData["type_id"] = $item->type_id;
        $itemData["unit_id"] = $item->unit_id;

        $specialValidationResult = $this->getItemDataSpecialValidationResult($itemData, $id);
        if ($specialValidationResult != null)
            return ErrorHandler::responseWith($specialValidationResult);
        if ($this->isItemExists($itemData, $id))
            return ErrorHandler::responseWith("Такий предмет вже існує");

        $item->article = $itemData["article"];
        $item->title = $itemData["title"];
        $item->model = $itemData["model"];
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

        return response($this->getUpdatedItem($item->id));
    }

    public function delete(Request $request, $id) {
        $sectionModel = $this->getSectionModel();
        $item = $sectionModel::find($id);

        if ($item === null) return ErrorHandler::responseWith("Предмет не знайдено");

        $amountOfItem = $this->getAmountOfItem($item->id);

        if ($amountOfItem != null || $amountOfItem > 0) {
            return ErrorHandler::responseWith("Неможливо видалити: предмет присутній на складах");
        }

        foreach($item->images as $key => $image) {
            Storage::disk('images')->delete($image->src);
            $image->delete();
        }

        $item->delete();

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
     * Returns nbu currency exchange currency rate
     *
     * @param $date - yyyymm
     * @param $currencyCode - USD|EUR|etc...
     *
     * @return array with currencies (EUR, USD)
     */
    private function getNbuCurrencyExchangeCourse($date, $currencyCode) {
        $neededCurrencyRate = null;

        if (empty($this->nbuCurrencyExchangeResponse)) {
            $response = file_get_contents("https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?date={$date}&json");
            $this->nbuCurrencyExchangeResponse = json_decode($response, true);
        }

        for ($i = 0; $i < count($this->nbuCurrencyExchangeResponse); $i++) {
            if (!in_array($currencyCode, $this->nbuCurrencyExchangeResponse[$i])) continue;
            $neededCurrencyRate = $this->nbuCurrencyExchangeResponse[$i]["rate"];
        }

        return $neededCurrencyRate;
    }

    private function getUpdatedItem($id) {
        $today = Carbon::now()->format("Ymd");
        $section = DB::table($this->section);
        $section->select(
            "items.id AS id",
            "items.group_id AS group_id",
            "items.article AS article",
            "items.title AS title",
            "items.model AS model",
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
            "genders.name AS gender",
            "sizes.value AS size_name",
            "sizes.number_in_row AS size", //extra field for ordering
            "sizes.description AS size_description",
            "colors.article AS color_article",
            "colors.description AS color_name",
            "colors.description AS color", //extra field for ordering
            "colors.value AS color_value",
            "colors.text_color_value AS text_color_value",
            DB::raw('SUM(income.amount_of_items) AS amount'),
            "units.name AS unit_name",
            "units.name AS units", //extra field for ordering
            "units.description AS unit_description",
        )
        ->join('types', 'items.type_id', '=', 'types.id')
        ->join('units', 'items.unit_id', '=', 'units.id')
        ->leftJoin('genders', 'items.gender_id', '=', 'genders.id')
        ->leftJoin('sizes', 'items.size_id', '=', 'sizes.id')
        ->leftJoin('colors', 'items.color_id', '=', 'colors.id')
        ->leftJoin('income', 'items.id', '=', 'income.item_id')
        ->where("items.id", $id);
        $updatedItem = $section->get()[0];
        $updatedItem = json_decode(json_encode($updatedItem), true);

        //binding images to updated item
        $updatedItem["images"] = Image::where("item_id", $updatedItem["id"])->orderBy("number_in_row", "asc")->get();
        return $updatedItem;
    }

    /**
     * Calculates how much item do we have in general,
     * or in particular warehouse
     *
     * @param integer  $itemId        ID of item from "items" table
     * @param integer  $warehouseId   ID of warehouse from "warehouses" table
     *
     * @return null    No records found in "item_warehouse_amount" table
     * @return integer Amount of items according to records in "item_warehouse_amount" table
     */
    private function getAmountOfItem($itemId, $warehouseId = null) {
        $amountOfItem = DB::table($this->section)
        ->selectRaw("SUM(item_warehouse_amounts.amount) as amount")
        ->leftJoin("item_warehouse_amounts", "item_warehouse_amounts.item_id", "=", "items.id")
        ->where("items.id", $itemId);

        if ($warehouseId != null) {
            $amountOfItem->where("item_warehouse_amounts.warehouse_id", $warehouseId);
        } else {
            $amountOfItem->groupBy("item_warehouse_amounts.item_id");
        }

        $amountOfItem =
            $amountOfItem->get()[0]->amount != null
            ? intval($amountOfItem->get()[0]->amount) : null;

        return $amountOfItem;
    }

    /**
     * Checks if an item with same characteristic
     * exists in the "items" table. If passing ID - ignore its value while
     * filtering (where id <> $ID) to avoid update item collision
     * (looks for similarity in items wich are different from item
     * with passed ID)
     *
     * @param integer  $id        Item ID (for update case), null - default value
     * @param array    $itemData  Contains validated values from request
     *
     * @return boolean Is item with passed params exists in "items" table
     */
    private function isItemExists($itemData, $id = null) {
        $sectionModel = $this->getSectionModel();
        $matchingItems = $sectionModel::
          where("article", $itemData["article"])
        ->where("group_id", $itemData["group_id"])
        ->where("type_id", $itemData["type_id"]);

        /**
         * Nullable items additional validation
         */
        $itemData["gender_id"] = !empty($itemData["gender_id"]) ? $itemData["gender_id"] : null;
        $itemData["size_id"] = !empty($itemData["size_id"]) ? $itemData["size_id"] : null;
        $itemData["color_id"] = !empty($itemData["color_id"]) ? $itemData["color_id"] : null;
        $matchingItems->where("gender_id", $itemData["gender_id"]);
        $matchingItems->where("size_id", $itemData["size_id"]);
        $matchingItems->where("color_id", $itemData["color_id"]);

        /**
         * if ID != null -> item "update" mode
         */
        if ($id != null) $matchingItems->where("id", "<>", $id);

        $matchingItems = $matchingItems->get();

        return count($matchingItems) > 0 ? true : false;
    }

    /**
     * Do specific validation of item data.
     * Checking for uniqueness of item params
     *
     * @param array   $itemData Validated item data, received from request
     * @param integer $id       ID of item. Use this param if checking for item
     *                          updating data
     *
     * @return string|null String, if error does exists. Null, if everything is good
     */
    private function getItemDataSpecialValidationResult($itemData, $id = null) {
        $action = $id != null ? "оновити" : "створити";

        $validationItem = Item::where("group_id", $itemData["group_id"]);
        $validationItem = $id != null
            ? $validationItem->where("id", "<>", $id)->get()
            : $validationItem->get();
        if (count($validationItem) > 0) {
            $validationItem = $validationItem[0];

            if ($validationItem["type_id"] != $itemData["type_id"]) {
                $itemData["type_id"] = !empty($itemData["type_id"]) ? $itemData["type_id"] : null;
                $typeName = $validationItem["type_id"] != null ?
                    Type::find($validationItem["type_id"])->name : "відсутнє значення";

                return "Неможливо {$action}: невірне значення
                    характеристики \"вид\" для предмету з даним
                    ідентифікатором групи. (Пропозиція: \"{$typeName}\")";
            }
        }

        $validationItem = Item::where("article", $itemData["article"]);
        $validationItem = $id != null
            ? $validationItem->where("id", "<>", $id)->get()
            : $validationItem->get();
        if (count($validationItem) > 0) {
            $validationItem = $validationItem[0];

            if ($validationItem["group_id"] != $itemData["group_id"]) {
                return "Неможливо {$action}: невірне значення
                    характеристики \"ідентифікатор групи\" для предмету з
                    даним артиклем. (Пропозиція: \"{$validationItem['group_id']}\")";
            }

            if ($validationItem["type_id"] != $itemData["type_id"]) {
                $itemData["type_id"] = !empty($itemData["type_id"]) ? $itemData["type_id"] : null;
                $typeName = $validationItem["type_id"] != null ?
                    Type::find($validationItem["type_id"])->name : "відсутнє значення";

                return "Неможливо {$action}: невірне значення
                    характеристики \"вид\" для предмету з даним артиклем.
                    (Пропозиція: \"{$typeName}\")";
            }

            $itemData["gender_id"] = !empty($itemData["gender_id"]) ? $itemData["gender_id"] : null;
            if ($validationItem["gender_id"] != $itemData["gender_id"]) {
                $genderName = $validationItem["gender_id"] != null ?
                    Gender::find($validationItem["gender_id"])->name : "відсутнє значення";

                return "Неможливо {$action}: невірне значення
                    характеристики \"гендер\" для предмету з
                    даним артиклем. (Пропозиція: \"{$genderName}\")";
            }
        }

        $validationItem = Item::where("group_id", $itemData["group_id"]);
        $validationItem = $id != null
            ? $validationItem->where("id", "<>", $id)->get()
            : $validationItem->get();
        if (count($validationItem) > 0) {

            foreach ($validationItem as $key => $row) {

                if ($row["gender_id"] == $itemData["gender_id"]
                    && $row["article"] != $itemData["article"])
                    return "Неможливо {$action}: невірне значення
                        характеристики \"артикль\" для предмету з
                        даним гендером. (Пропозиція: \"{$row['article']}\")";
            }
        }

        return null;
    }

}
