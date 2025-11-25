<?php

namespace App\Http\Middleware;

use App\Helpers\AuthAPI;
use Closure;
use Illuminate\Http\Request;

class EnsureRequestIsAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (AuthAPI::isAuthenticated($request->bearerToken(), $request->ip())) {
            return $next($request);
        }

        return response('Невірні данні аутентифікації', 403);
    }
}
