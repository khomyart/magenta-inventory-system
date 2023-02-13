<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\AuthAPI;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

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

            return response()->json(["user" => $user->toArray(), "auth" => $token->toArray()]);
        } else {
            return response("Невірні данні аутентифікації", 403);
        }
    }
}
