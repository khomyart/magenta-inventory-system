<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        AccessToken::create([
            'user_id' => $user->id,
            'token' => Hash::make(today()),
            'last_used' => Carbon::now(),
            'expired_at' => Carbon::now()->addHours(3),
        ]);
    }

    //login
    public function authenticate(Request $request) {
        $credentials = $request->validate([
            // "email" => "required|email|unique:users", for registration
            "email" => "required|email",
            "password" => "required",
        ]);
        $user = User::firstWhere("email", $credentials["email"]);

        if (isset($user) && Hash::check($credentials["password"], $user->password)) {
            return response()->json(["user" => $user->toArray(), "auth" => $user->accessToken->toArray()]);
        } else {
            return response("Невірні данні аутентифікації", 403);
        }

    }
}
