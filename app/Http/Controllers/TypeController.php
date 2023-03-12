<?php

namespace App\Http\Controllers;

use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Helpers\AuthAPI;

class TypeController extends Controller
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
            'orderField'=> ['string', 'regex:/^id$|^article$|^name$/i', 'nullable'],
            'orderValue' => ['string', 'regex:/^desc$|^asc$/i', 'nullable'],
            'articleFilterValue' => 'string|nullable',
            'articleFilterMode' => ['string', 'regex:/^include$|^exclude$|^more$|^less$|^equal$|^notequal$/i', 'nullable'],
            'nameFilterValue' => 'string|nullable',
            'nameFilterMode' => ['string', 'regex:/^include$|^exclude$|^more$|^less$|^equal$|^notequal$/i', 'nullable'],
        ]);

        $types = DB::table('types');

        //forming where query
        $articleSearchValue = $data["articleFilterValue"];
        $articleSearchOperator = $this->getWhereOperator($data["articleFilterMode"]);
        $nameSearchValue = $data["nameFilterValue"];
        $nameSearchOperator = $this->getWhereOperator($data["nameFilterMode"]);

        if ($articleSearchValue != null) {
            if ($articleSearchOperator === 'like') {
                $types->where('article', 'like', "%{$articleSearchValue}%");
            } elseif ($articleSearchOperator === 'notLike') {
                $types->whereNot(function ($query) use ($articleSearchValue) {
                    $query->where('article', 'like', "%{$articleSearchValue}%");
                });
            } else {
                $types->where('article', $articleSearchOperator, $articleSearchValue);
            }
        }

        if ($nameSearchValue != null) {
            if ($nameSearchOperator === 'like') {
                $types->where('name', 'like', "%{$nameSearchValue}%");
            } elseif ($nameSearchOperator === 'notLike') {
                $types->whereNot(function ($query) use ($nameSearchValue) {
                    $query->where('name', 'like', "%{$nameSearchValue}%");
                });
            } else {
                $types->where('name', $nameSearchOperator, $nameSearchValue);
            }
        }

        //ordering query
        if (!empty($data["orderField"]) && !empty($data["orderValue"])) {
            $types = $types->orderBy($data["orderField"], $data["orderValue"]);
        } else {
            $types = $types->latest();
        }

        return response($types->paginate($data['itemsPerPage']));

    }

    public function create(Request $request) {
        $data = $request->validate([
            "article" => "required|string|max:8",
            "name" => "required|string|max:155"
        ]);

        return Type::create($data);
    }

    public function update(Request $request, $id) {
        $data = $request->validate([
            "article" => "required|string|max:8",
            "name" => "required|string|max:155"
        ]);

        $type = Type::find($id);

        if ($type) {
            $type->article = $data["article"];
            $type->name = $data["name"];
            $type->save();

            return response($type);
        }

        return response("Не знайдено", 404);
    }

    public function delete(Request $request, $id) {
        $type = Type::find($id);

        if ($type) {
            $type->delete();
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
