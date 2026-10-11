<?php

namespace App\Http\Middleware;

use App\Helpers\AppHelpers;
use App\Models\Authentication\Access;
use App\Models\Global\Config;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class ProtectLogin
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
        $loginLockDuration = 5;
        $ip = $request->ip();
        
        $attempt = Access::getAccessByIp($ip);
            
        if ($attempt && $attempt->attempts > $maxAttempts) {
            if (AppHelpers::isInFutureAfter($attempt->last_attempt_at, $loginLockDuration)) {
                return AppHelpers::jsonResponse('Too many failed attempts. Please Try again Later', 429);
            }

            $attempt->resetAttempts();
        }

        return $next($request);
    }
}
