<?php

namespace App\Http\Controllers;

use App\Models\Type;
use App\Http\Resources\TypeResource;
use App\Http\Resources\TypeResourceCollection;
use App\Http\Requests\StoreTypeRequest;
use App\Http\Requests\UpdateTypeRequest;
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
    public function show(Request $request)
    {
        $user = AuthAPI::isAuthenticated($request->bearerToken(), $request->ip());
        $data = $request->validate([
            'itemsPerPage' => 'required|numeric',
        ]);
        return response(DB::table('types')->paginate($data['itemsPerPage']));
    }

    public function create(Request $request) {
        $data = $request->validate([
            "article" => "required|string|max:8",
            "name" => "required|string|max:155"
        ]);

        return Type::create($data);
    }

}
