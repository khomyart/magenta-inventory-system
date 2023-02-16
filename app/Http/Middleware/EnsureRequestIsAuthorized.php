<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\AuthAPI;

class EnsureRequestIsAuthorized
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $action, $section)
    {
        $auth = AuthAPI::isAuthenticated($request->bearerToken(), $request->ip());

        if (!$auth) {
            return response("Користувач не автентифікований", 403);
        }

        if (!$auth->isAuthorizedFor($action, $section)) {
            return response("Недостатньо повноважень", 401);
        }

        if ($auth->isTokenExpired()) {
            return response("tokenexpired", 422);
        }

        if ($auth->isUserAway()) {
            return response("userisafk", 422);
        }

        $reqreatedToken = $auth->refreshAccessToken();

        return $next($request);
    }
}
