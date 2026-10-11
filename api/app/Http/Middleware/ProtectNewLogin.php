<?php

namespace App\Http\Middleware;

use App\Helpers\AppHelpers;
use App\Models\Authentication\Access;
use App\Models\Global\Config;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class ProtectNewLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $maxAttempts = Config::getConfig('max_login_attempts') ?? 5;
        $loginLockDuration = Config::getConfig('login_lock_duration') ?? 300;
        $attemptDuration = Config::getConfig('login_attempt_duration') ?? 60;

        $ip = $request->ip(); 
        $userHash = Route::input('userHash');
        $attempt = Access::getAccess($ip, $userHash);

        if ($attempt && $attempt->maxAttemptsReached($maxAttempts)) {
            if ($attempt->isLocked($loginLockDuration)) {
                return AppHelpers::jsonResponse('Too many failed attempts. Please Try again Later', 429);
            }

            $attempt->resetAttempts();
        }

        if ($attempt && $attempt->hasActiveAttempt($attemptDuration)) {
            return AppHelpers::jsonResponse('Login exceeded max duration: request a new salt', 429);
        }

        return $next($request);
    }
}
