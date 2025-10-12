<?php

namespace App\Helpers;

use App\Models\AccessToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AuthAPI
{
    //minutes
    const TOKEN_LIFE_TIME = 99999;
    const AWAY_FROM_KEYBOARD_TIME = 99999;

    public User $user;
    public int $tokenTime;
    public string $ip;

    function __construct(User $user, string $ip, int $tokenTime = self::TOKEN_LIFE_TIME)
    {
        $this->user = $user;
        $this->tokenTime = $tokenTime;
        $this->ip = $ip;
    }

    /**
     * If user authenticated, return an instance of a current class. If not - return false
     *
     * @param $bearerToken
     * @param $ip
     *
     * @return AuthAPI|false
     */
    public static function isAuthenticated($bearerToken, $ip): AuthAPI|false
    {
        $token = AccessToken::query()->firstWhere('token', $bearerToken);

        if (isset($token)) {
            return new self($token->user, ip: $ip);
        }

        return false;
    }

    /**
     * Returns token if it exists. If not - false
     *
     * @return string|false
     */
    public function hasToken(): AccessToken|false
    {
        $token = $this->user->accessToken;
        return $token ?? false;
    }

    public function isTokenExpired(): bool
    {
        $now = Carbon::now();
        $tokenExpiringTime = new Carbon($this->hasToken()->expired_at);

        return $now > $tokenExpiringTime;
    }

    public function isUserAway($afkTime = self::AWAY_FROM_KEYBOARD_TIME): bool
    {
        $now = Carbon::now();
        $tokenLastUsedTime = new Carbon($this->hasToken()->last_used);

        return $now > $tokenLastUsedTime->addMinutes($afkTime);
    }

    public function createAccessToken($tokenLifeTime = self::TOKEN_LIFE_TIME): AccessToken|false
    {
        $accessToken = AccessToken::create([
            'user_id' => $this->user->id,
            'token' => Hash::make(today()),
            'ip_address' => $this->ip,
            'last_used' => Carbon::now(),
            'expired_at' => Carbon::now()->addMinutes($tokenLifeTime),
        ]);

        return $accessToken ?? false;
    }


    public function refreshAccessToken($tokenLifeTime = self::TOKEN_LIFE_TIME): AccessToken
    {
        $accessToken = $this->user->accessToken;

        $accessToken->last_used = Carbon::now();
        $accessToken->expired_at = Carbon::now()->addMinutes($tokenLifeTime);

        $accessToken->save();

        return $accessToken;
    }

    public function recreateAccessToken($tokenLifeTime = self::TOKEN_LIFE_TIME): AccessToken
    {
        $accessToken = $this->user->accessToken;

        $accessToken->token = Hash::make(today());
        $accessToken->last_used = Carbon::now();
        $accessToken->expired_at = Carbon::now()->addMinutes($tokenLifeTime);

        $accessToken->save();

        return $accessToken;
    }

    /**
     * Checks if current user authorized for particular action in the section.
     *
     * @param $action
     * @param $section
     *
     * @return bool
     */
    public function isAuthorizedFor($action, $section): bool
    {
        $allowensesList = $this->getAllowenses();

        $isAuthorized = false;
        foreach ($allowensesList as $allowense) {
            if ($allowense->action == $action && $allowense->section == $section) {
                $isAuthorized = true;
                break;
            }
        }

        return $isAuthorized;
    }

    /**
     * Receives allowenses that belongs to current user
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllowenses(): \Illuminate\Support\Collection
    {
        return DB::table('users')
            ->select('allowenses.action', 'allowenses.section')
            ->join('users_roles', 'users.id', '=', 'users_roles.user_id')
            ->join('roles_allowenses', 'users_roles.role_id', '=', 'roles_allowenses.role_id')
            ->join('allowenses', 'roles_allowenses.allowense_id', '=', 'allowenses.id')
            ->where('users.id', '=', $this->user->id)
            ->distinct()->get();
    }
}
