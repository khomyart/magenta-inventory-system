<?php

namespace App\Http\Controllers;

use App\Helpers\AuthAPI;
use App\Helpers\ErrorHandler;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public int $queryResultLimiter = 5;

    public function create(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:255|string',
            'email' => 'required|max:255|string',
            'password' => 'required|max:255|string',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    //login
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            // "email" => "required|email|unique:users", for registration
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $user = User::firstWhere('email', $credentials['email']);

        if (isset($user) && Hash::check($credentials['password'], $user->password)) {
            $auth = new AuthAPI(user: $user, ip: $request->ip());

            $token = $auth->hasToken() ? $auth->recreateAccessToken() : $auth->createAccessToken();

            $userData = $user->toArray();
            unset($userData['access_token']);

            return response()->json(['user' => $userData, 'auth' => $token->toArray(), 'allowenses' => $auth->getAllowenses()]);
        } else {
            return ErrorHandler::responseWith('Невірні дані автентифікації');
        }
    }

    public function logout(Request $request)
    {
        $auth = AuthAPI::isAuthenticated($request->bearerToken(), $request->ip());

        if ($auth) {
            $auth->hasToken()->delete();
        }

        return response('OK', 200);
    }

    /**
     * Display a listing of the users.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|Response
     */
    public function read(Request $request): AnonymousResourceCollection|Response
    {
        $users = User::orderBy('name', 'asc')->get();

        return UserResource::collection($users);
    }

    /**
     * Display a listing of the users with search functionality.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|Response
     */
    public function simpleRead(Request $request): AnonymousResourceCollection|Response
    {
        $data = $request->validate([
            'search_filter_value' => 'string|nullable',
        ]);

        if (empty($data['search_filter_value'])) {
            $query = User::orderBy('name', 'asc');
        } else {
            $query = User::query()
                ->where('name', 'like', "%{$data['search_filter_value']}%")
                ->orWhere('email', 'like', "%{$data['search_filter_value']}%")
                ->orderBy('name');
        }

        $items = $this->queryResultLimiter != 0
            ? $query->limit($this->queryResultLimiter)->get()
            : $query->get();

        return UserResource::collection($items);
    }
}
