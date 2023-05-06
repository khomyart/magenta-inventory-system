<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Helpers\AuthAPI;
use App\Helpers\ErrorHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
use App\Models\AccessToken;

class UserController extends Controller
{
    public function create($data, $name) {
        $user = User::create([
            "name" => $name,
            "email" => $data["email"],
            "password" => Hash::make($data["password"]),
        ]);

    }

    //login
    public function authenticate(Request $request) {
        $credentials = $request->validate([
            // "email" => "required|email|unique:users", for registration
            "email" => "required|email",
            "password" => "required|string",
        ]);
        $user = User::firstWhere("email", $credentials["email"]);

        if (isset($user) && Hash::check($credentials["password"], $user->password)) {
            $auth = new AuthAPI(user: $user, ip: $request->ip());

            $token = $auth->hasToken() ? $auth->recreateAccessToken() : $auth->createAccessToken();

            $userData = $user->toArray();
            unset($userData["access_token"]);

            return response()->json(["user" => $userData, "auth" => $token->toArray(), "allowenses" => $auth->getAllowenses()]);
        } else {
            return ErrorHandler::responseWith("Невірні дані автентифікації");
        }
    }

    public function logout(Request $request) {
        $auth = AuthAPI::isAuthenticated($request->bearerToken(), $request->ip());

        if ($auth) {
            $auth->hasToken()->delete();
        }

        return response("OK", 200);
    }
}
