<?php

namespace App\Http\Middleware;

use App\Helpers\AppHelpers;
use App\Models\User\User;
use App\Services\Authentication\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireAuth
{

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Auth::check(), 401, 'Missing Token!');
        return $next($request);
    }
}
