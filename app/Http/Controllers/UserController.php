<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;

class UserController extends Controller
{
    public function create($data, $name) {
        User::create([
            "name" => $name,
            "email" => $data["email"],
            "password" => bcrypt($data["password"]),
        ]);
    }

    //login
    public function authenticate(Request $request) {
        $credentials = $request->validate([
            "email" => "required|email",
            "password" => ["required", "string"]
        ]);

        if (!Auth::attempt($credentials)) {
            return response([
                'error' => 'Невірні данні аутентифікації'
            ], 422);
        }

        $user = Auth::user();
        $token = $user->createToken('main')->plainTextToken;
        unset($user["created_at"]);
        unset($user["updated_at"]);
        return [
            "user" => $user,
            "token" => $token
        ];
    }
}
