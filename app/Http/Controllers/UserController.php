<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{

    public function create($request) {
        $userData = $request->all();
        $userData.push(["name"=>"value"]);
    }

    //login
    public function authenticate(Request $request) {
        $this->create($request);
    }
}
