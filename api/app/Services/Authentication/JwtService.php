<?php

namespace App\Services\Authentication;

use App\Models\Authentication\JwtSecret;
use App\Models\Authentication\Session;
use App\Models\Global\Config;
use App\Models\User\User;
use App\Models\User\UserProfile;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JwtService {

    public function generateJwtToken(int $userId, string $secretJWT)
    {
        $maxDuration = 15 * 24 * 60 * 60;   //Token scade sempre dopo 15gg max
        $duration = Config::getConfig('max_token_duration') ?? $maxDuration;

        $iss = config('app.url');
        $sub = $userId;
        $issuedAt = now();
        $expiresAt = now()->addSeconds($duration);
        $jti = (string) Str::uuid();
        
        $user = User::getUser($userId);
        $userProfile = UserProfile::getProfileByUserId($userId);
        $primaryRole = $user->roles()->orderByPivot('role_id')->first()->role;
        $roles = $user->roles()->pluck('name')->toArray();

        $payload = [
            'iss' => $iss,                      //Emittente
            'sub' => $sub,                      //Soggetto - User ID
            'iat' => $issuedAt->timestamp,      //Emissione
            'exp' => $expiresAt->timestamp,     //Scadenza
            'jti' => $jti,                      //Token UUID

            'data' => [
                'username' => $user->username,
                'name' => $userProfile->name,
                'primary_role' => $primaryRole,
                'roles' => $roles,
            ]
        ];

        // Encode with the custom secret and HS256 algorithm
        return [
            'token' => JWT::encode($payload, $secretJWT, 'HS256'),
            'iss' => $iss,
            'sub' => $sub,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'jti' => $jti,
        ];
    }

    public function decodeJWTPayload(string $token) :?array {
        $parts = explode('.', $token);

        $payload = null;

        if (!empty($token) && isset($parts) && count($parts) == 3) {

            $payloadEnc = $parts[1];

            $payload = base64_decode(strtr($payloadEnc, '-_', '+/'));
        }

        return $payload ? json_decode($payload, true) : null;
    }

    public function validateToken(string $token) :?bool {
        //Controllo Token
        try {
            $tkPayload = $this->decodeJWTPayload($token);
            $jti = $tkPayload['jti'];

            //Controllo della Corretta traduzione del Payload
            if (!$tkPayload || !$jti) {
                throw new Exception('Malformed Token Payload');
            }

            //Controllo Esistenza della Sessione
            $session = Session::getSession($jti);

            if (!$session) {
                throw new Exception('Session Not Found');
            }

            //Ottenimento e Controllo della SecretJWT
            $secretJWT = JwtSecret::getSecret($session->user_id);

            if (!$secretJWT) {
                throw new Exception('SecretJWT Not Found');
            }

            //Decodifica e Verifica del Token con Secret
            $payload = JWT::decode($token, new Key($secretJWT, 'HS256'));

            //Controllo Scadenza del Token
            $currTime = now();
            if (
                    $session->user_id !== $tkPayload['sub'] 
                    ||
                    $currTime->lessThan($session->issued_at)
                    ||
                    $currTime->greaterThan($session->expires_at)
                ) 
                {
                    throw new \Exception ('Inconsistent Token Database Header');
            }

            if ($session->revoked == 1) {
                throw new \Exception ('Token Revoked');
            }

            return true;
        } catch (\Throwable $th) {
            if (app()->environment('testing')) Log::error($th->getMessage());
            return false;
        }
    }
}