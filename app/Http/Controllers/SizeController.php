<?php

namespace App\Http\Controllers;

use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Helpers\AuthAPI;

class SizeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function read(Request $request)
    {
        $user = AuthAPI::isAuthenticated($request->bearerToken(), $request->ip());
        $data = $request->validate([
            'itemsPerPage' => 'required|numeric',
            'page' => 'required|numeric',
            'orderField'=> ['string', 'regex:/^id$|^value$|^description$/i', 'nullable'],
            'orderValue' => ['string', 'regex:/^desc$|^asc$/i', 'nullable'],
            'valueFilterValue' => 'string|nullable',
            'valueFilterMode' => ['string', 'regex:/^include$|^exclude$|^more$|^less$|^equal$|^notequal$/i', 'nullable'],
            'descriptionFilterValue' => 'string|nullable',
            'descriptionFilterMode' => ['string', 'regex:/^include$|^exclude$|^more$|^less$|^equal$|^notequal$/i', 'nullable'],
        ]);

        $sizes = DB::table('sizes');

        //forming where query
        $valueSearchValue = $data["valueFilterValue"];
        $valueSearchOperator = $this->getWhereOperator($data["valueFilterMode"]);
        $descriptionSearchValue = $data["descriptionFilterValue"];
        $descriptionSearchOperator = $this->getWhereOperator($data["descriptionFilterMode"]);

        if ($valueSearchValue != null) {
            if ($valueSearchOperator === 'like') {
                $sizes->where('value', 'like', "%{$valueSearchValue}%");
            } elseif ($valueSearchOperator === 'notLike') {
                $sizes->whereNot(function ($query) use ($valueSearchValue) {
                    $query->where('value', 'like', "%{$valueSearchValue}%");
                });
            } else {
                $sizes->where('value', $valueSearchOperator, $valueSearchValue);
            }
        }

        if ($descriptionSearchValue != null) {
            if ($descriptionSearchOperator === 'like') {
                $sizes->where('description', 'like', "%{$descriptionSearchValue}%");
            } elseif ($descriptionSearchOperator === 'notLike') {
                $sizes->whereNot(function ($query) use ($descriptionSearchValue) {
                    $query->where('description', 'like', "%{$descriptionSearchValue}%");
                });
            } else {
                $sizes->where('description', $descriptionSearchValue, $descriptionSearchValue);
            }
        }

        //ordering query
        if (!empty($data["orderField"]) && !empty($data["orderValue"])) {
            $sizes = $sizes->orderBy($data["orderField"], $data["orderValue"]);
        } else {
            $sizes = $sizes->latest();
        }

        return response($sizes->paginate($data['itemsPerPage']));

    }

    public function create(Request $request) {
        $data = $request->validate([
            "value" => "required|string|max:8",
            "description" => "required|string|max:250"
        ]);

        return Size::create($data);
    }

    public function update(Request $request, $id) {
        $data = $request->validate([
            "value" => "required|string|max:8",
            "description" => "required|string|max:250"
        ]);

        $size = Size::find($id);

        if ($size) {
            $size->value = $data["value"];
            $size->description = $data["description"];
            $size->save();

            return response($size);
        }

        return response("Не знайдено", 404);
    }

    public function delete(Request $request, $id) {
        $size = Size::find($id);

        if ($size) {
            $size->delete();
            return response("OK", 200);
        }

        return response("Не знайдено", 404);
    }

    private function getWhereOperator($operatorName) {
        $equality = [
            'include' => 'like',
            'exclude' => 'notLike',
            'more' => '>',
            'less' => '<',
            'equal'=> '=',
            'notequal' => '<>'
        ];

        return $equality[$operatorName];
    }
}
