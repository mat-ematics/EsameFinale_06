<?php

namespace App\Services\Authentication;

use App\Enums\StateEnum;
use App\Exceptions\InvalidCredentialsException;
use App\Helpers\AppHelpers;
use App\Http\Resources\User\UserResource;
use App\Models\Authentication\Access;
use App\Models\Authentication\JwtSecret;
use App\Models\Authentication\Session;
use App\Models\User\User;
use App\Services\User\UserService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService {

    protected UserService $userService;
    protected JwtService $jwtService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(UserService $userService, JwtService $jwtService)
    {
        $this->userService = $userService;
        $this->jwtService = $jwtService;
    }

    /**
     * Ritorna la Risorsa del Profilo dell'utente passato
     */
    public function registerUser(array $data) : UserResource 
    {
        $user = $this->userService->createUser($data);
        return $user;
    }

    public function loginUser(string $username, string $password, string $ip) : array
    {
        $access = Access::getAccess($ip, $username);
        if (!$access) {
            $access = Access::createAccess($ip, $username);
        }
        
        $user = User::getUserByUsername($username);

        // if (!isset($user)) {
        //     Log::alert('user does not exist');
        // }

        if (!$user->currentPassword->checkPasswordLogin($password)) {
            Log::alert('Password is wrong! Given: ' . $password . ' Actual: ' . $user->currentPassword->password);
        }

        if (!isset($user) || !$user->currentPassword->checkPasswordLogin($password)) {
            $access->addError();
            throw new InvalidCredentialsException();
        }

        if (!$user->isActive()) {

            $until = Carbon::make($user->state_until);

            //Controllo Sospensione Con Scadenza
            if ($until && $until->isFuture()) {    
                throw new AccessDeniedHttpException(
                    'This account is ' .
                    ucfirst($user->getState()->name) .
                    ' until ' .
                    $until->format('l jS \\of F Y h:i:s A')
                );
            }

            //Controllo Sospensione Senza Scadenza
            if (!$until) {
                throw new AccessDeniedHttpException('This Account is ' . ucfirst($user->getState()->name) . 'Permanently');
            }

            //Sospensione Scaduta, Riattivazione dell'Utente
            $this->userService->activateUser($user);
        }

        return DB::transaction(function () use ($user, $access) {
            $secret = JwtSecret::generateSecret($user->id);
            $tokenArr = $this->jwtService->generateJwtToken($user->id, $secret);

            $access->delete();

            Session::deleteSession($user->id);
            Session::startSession($user->id, $tokenArr['jti'], $tokenArr['iat'], $tokenArr['exp']);

            return $tokenArr;
        });
    }

    public function endUserSession(int $userId) : void
    {
        Session::deleteSession($userId);
        JwtSecret::revokeSecret($userId);
    }
}