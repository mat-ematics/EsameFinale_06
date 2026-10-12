<?php

namespace App\Http\Middleware;

use App\Helpers\AppHelpers;
use App\Models\User\User;
use App\Services\Authentication\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TokenAuth
{

    protected JwtService $jwtService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param string $mode The mode of the Authentication, either 'required' or 'optional' (other values treated as required)
     */
    public function handle(Request $request, Closure $next, string $mode = 'required'): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            if ($mode === 'optional') {
                return $next($request); //guest, no user set
            }
            return AppHelpers::jsonResponse('Missing Token!', 401);
        }

        if (!$this->jwtService->validateToken($token)) {
            return AppHelpers::jsonResponse('Invalid Token!', 401);
        }

        $tkPayload = $this->jwtService->decodeJWTPayload($token);
        $user = User::getUser($tkPayload['sub']);

        if (!$user) {
            return AppHelpers::jsonResponse('Invalid Token!', 401);
        }

        Auth::login($user);

        return $next($request);
    }
}
