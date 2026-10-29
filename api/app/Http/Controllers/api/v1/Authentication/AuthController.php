<?php

namespace App\Http\Controllers\api\v1\Authentication;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\RegisterRequest;
use App\Models\Authentication\Access;
use App\Models\User\Password;
use App\Models\User\User;
use App\Services\Authentication\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    protected AuthService $authService;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        $data = array_merge([
                'locality'                => null,
                'additional_info'         => null,
                'italian_municipality_id' => null,
            ],
            $request->validated(),
            ['role' => User::USER],
        );
        $resource = $this->authService->registerUser($data)->toArray($request);
        $user = User::getUserByUsername($resource['username']);

        $tokenArr = $this->authService->loginUser($user->username, $user->currentPassword->password, $request->ip());

        // Same shape as login: data.token
        return AppHelpers::jsonResponse('Registered successfully', 201, ['token' => $tokenArr['token']]);
    }

    /** Returns the user's salt (a deterministic fake one for unknown users) */
    public function getLoginSalt(Request $request, string $userHash)
    {
        Access::startAttempt($request->ip(), $userHash);

        $user = User::getUserByUsername($userHash);
        $salt = $user?->currentPassword?->salt
            ?? substr(hash_hmac('sha256', $userHash, config('app.key')), 0, strlen(AppHelpers::generateSalt()));

        return AppHelpers::jsonResponse('Salt generated successfully', 200, ['salt' => $salt]);
    }

    public function newLogin(Request $request, string $userHash, string $passwordHash)
    {
        $tokenArr = $this->authService->loginUser($userHash, $passwordHash, $request->ip());

        return AppHelpers::jsonResponse('Logged in successfully', 200, ['token' => $tokenArr['token']]);
    }

    /** Local/testing only (the route is not registered elsewhere) */
    public function testLogin(Request $request, string $username, string $password)
    {
        $user = User::getUserByUsernameHash($username)
            ?? abort(404, 'TEST ERROR: User does not exist');

        $passwordHash = Password::getPasswordHashDouble($password, $user->currentPassword->salt);

        return $this->newLogin($request, $user->username, $passwordHash);
    }

    public function logout()
    {
        $this->authService->endUserSession(Auth::id());
        return AppHelpers::jsonResponse('Logged out successfully', 200);
    }

    /** Admin only (route middleware) */
    public function forceLogout(int $userId)
    {
        $user = User::withTrashed()->findOrFail($userId);
        abort_unless($user->session()->exists(), 404, 'User Is not Logged In');

        $this->authService->endUserSession($user->id);
        return AppHelpers::jsonResponse("User {$user->username} Session has been Terminated", 200);
    }
}