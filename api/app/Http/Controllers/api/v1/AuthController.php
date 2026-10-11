<?php

namespace App\Http\Controllers\api\v1;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Authentication\Access;
use App\Models\Global\Config;
use App\Models\User\Password;
use App\Models\User\User;
use App\Services\Authentication\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthController extends Controller
{

    protected AuthService $authService;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

    /**
     * Crea e salva in database un nuovo utente
     */
    public function register(RegisterRequest $request)
    {
        //Validzione dei Dati in input
        $data = $request->validated();
        try {
            $userResource = ($this->authService->registerUser($data))->toArray($request);

            $user = User::getUserByUsername($userResource['username']);

            $tokenArr = $this->authService->loginUser($user->username, $user->currentPassword->password, $request->ip());

            return AppHelpers::jsonResponse($tokenArr['token'], 201);
        } catch (\Throwable $th) {
            return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
        }
    }

    /**
     * Funzione che ritorna il sale per la Password da ripassare nel Login
     */
    public function getLoginSalt(Request $request, string $userHash)
    {
        $attemptDuration = Config::getConfig('login_attempt_duration') ?? 60;
        $ip = $request->ip();
        $attempt = Access::getAccess($ip, $userHash);

        if ($attempt && $attempt->hasActiveAttempt()) {
            return Apphelpers::jsonResponse('Please wait before requesting a new salt', 429);
        }

        Access::startAttempt($ip, $userHash);

        $user = User::getUserByUsername($userHash);
        $salt = AppHelpers::generateSalt();

        if ($user) {
            $salt = $user->currentPassword->salt;
        }

        // Log::alert('Salt returned for ' . $userHash . ': ' . $salt);
        return AppHelpers::jsonResponse('Salt generated successfully', 200, ['salt' => $salt]);
    }

    /**
     * Funzione di Login (NUOVA)
     */
    public function newLogin(Request $request, string $userHash, string $passwordHash)
    {
        try {
            $tokenArr = $this->authService->loginUser($userHash, $passwordHash, $request->ip());

            return AppHelpers::jsonResponse("Logged in succesfully", 200, ['token' => $tokenArr['token']]);
        } catch (\Throwable $th) {
            return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
        }
    }

    public function testLogin(Request $request, string $username, string $password)
    {
        try {
            //GENERA HASH UTENTE
            $user = User::getUserByUsernameHash($username);

            if (!$user) {
                throw new HttpException('PRODUCTION TEST ERROR: User does not exist');
            }

            // SIMULA L'UNIONE TRA SALE E PASSWORD
            $passwordHash = Password::getPasswordHash($password, $user->currentPassword->salt);

            // RITORNO ALLA LOGIN INIZIALE
            return $this->newLogin($request, $user->username, $passwordHash);
        } catch (\Throwable $th) {
            return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
        }
    }

    /**
     * Funzione di Login (VECCHIA)
     */
    // public function login(LoginRequest $request)
    // {
    //     $data = $request->validated();
    //     try {
    //         $tokenArr = $this->authService->loginUser($data['username'], $data['password'], $request->ip());

    //         return AppHelpers::jsonResponse($tokenArr['token'], 200);
    //     } catch (\Throwable $th) {
    //         return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
    //     }
    // }

    public function logout()
    {
        $this->authService->endUserSession(Auth::user()->id);

        return AppHelpers::jsonResponse('Logged out successfully', 204);
    }

    public function forceLogout(string $userId)
    {
        if (Gate::allows('isAdmin')) {

            try {
                $user = User::getUser($userId, true);

                if (!$user) {
                    throw new HttpException(404, 'User Not Found');
                }

                if (!$user->session()->exists()) {
                    throw new HttpException(404, 'User Is not Logged In');
                }

                $this->authService->endUserSession($user->id);
                return AppHelpers::jsonResponse("User {$user->username} Session has been Terminated", 204);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return  AppHelpers::jsonResponseForbidden();
    }
}