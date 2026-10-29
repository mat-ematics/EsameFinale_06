<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Unique;
use Throwable;

use function Laravel\Prompts\error;

class AppHelpers {

    public static function updateRequestRules(
        array $rules,
        int|string|null $ignoreId = null,
        array $keepRequired = [],
    ) : array
    {
        return Arr::map($rules, function ($ruleset, $key) use ($ignoreId, $keepRequired) {

            // I campi in $keepRequired mantengono il loro ruleset invariato
            if (in_array($key, $keepRequired)) {
                return $ruleset;
            }

            // Oggetto Rule singolo non incapsulato in un array
            if ($ruleset instanceof Unique) {
                return $ignoreId !== null ? $ruleset->ignore($ignoreId) : $ruleset;
            }

            if (is_array($ruleset)) {
                $ruleset = array_values(array_filter($ruleset, fn ($r) => $r !== 'required'));

                if ($ignoreId !== null) {
                    foreach ($ruleset as $rule) {
                        if ($rule instanceof Unique) {
                            $rule->ignore($ignoreId);
                        }
                    }
                }

                return $ruleset;
            }

            if (is_string($ruleset)) {
                return implode('|', array_filter(explode('|', $ruleset), fn ($r) => $r !== 'required'));
            }

            return $ruleset;
        });
    }

    public static function generateSalt() : string
    {
        return bin2hex(random_bytes(32));
    }

    public static function customHash (#[\SensitiveParameter] string $string, string $salt = '') : string
    {
        $combined = hash('sha256', $salt . $string);
        return Hash::make($combined);
    }

    public static function checkHash(#[\SensitiveParameter] string $string, string $hash, string $salt = '') : bool
    {
        return Hash::check($salt . $string, $hash);
    }

    public static function safeHttpStatus(\Throwable $th) : int 
    {
        $code = (int) $th->getCode();
        return ($code >= 100 && $code < 600) ? $code : 500;
    }

    public static function jsonResponse(string $message, int $status = 200, mixed $extra = []) : \Illuminate\Http\JsonResponse
    {
        //controlla se ha extra passati
        $hasData = func_num_args() >= 3;

        if (!is_array($extra)) {
            try {
                $extra = Arr::from($extra);
            } catch (\Throwable $th) {
                $extra = (array) $extra;
            }
        }

        $msg = ($status >= 400 && $status < 600) 
            ? ['error' => $message]
            : ['message' => $message];

        $extraArr = $hasData ? ['data' => $extra] : [];

        return response()->json(array_merge($msg, $extraArr), $status);
    }

    public static function jsonResponseForbidden(string $message = "You aren't authorized to do this!", mixed $extra = []) : \Illuminate\Http\JsonResponse
    {
        return static::jsonResponse($message, 403, $extra);
    }

    public static function timestampFromDateTime(string $datetime) : int
    {
        return Carbon::parse($datetime)->timestamp;
    }

    public static function carbonFromTimestamp(int $timestamp) : Carbon
    {
        return Carbon::createFromTimestamp($timestamp);
    }

    public static function isInFutureAfter(Carbon|int|string $currTime, int|string $duration) : bool
    {
        if (!$currTime instanceof Carbon) {
            $currTime = Carbon::parse($currTime);
        }

        $expirationTime = $currTime->addSeconds($duration);
        return $expirationTime->isFuture();
    }

    public function isFuture(Carbon|int|string $time)
    {
        if (!$time instanceof Carbon) {
            $time = Carbon::parse($time);
        }

        return $time->isFuture();
    }

    public static function getBaseNameFromPath(string $path) : string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    public static function getDirNameFromPath(string $path) : string
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    public static function fileHash(string $filepath) : string
    {
        return md5_file($filepath);
    }

    public static function isArrayOfArrays($array): bool 
    {
        return is_array($array) && !empty($array) && is_array(reset($array));
    }

    public static function extractColumnFromArray(array $array, int|string $columnKey)
    {
        return array_column($array, $columnKey);
    }


    public static function collectToArray(Collection|array|string|int|float $elements) : array
    {
        return collect($elements)->toArray();
    }

    public static function keysToValues(array $keys, array $values) : array
    {
        return array_combine($keys, $values);
    }

    public static function toSingleKeyValueArray(string $key, array $values) : array
    {
        return array_map(function($value) use ($key) {
            return [$key => $value];
        }, $values);
    }

    public static function countValuesInArray(array $array) : array
    {
        return array_count_values($array);
    }

    /**
     * Trova un record tramite ID numerico o per nome/label utilizzando il motore di ricerca statico del modello stesso.
     * Genera un'eccezione ModelNotFoundException, che il gestore globale trasforma in "<Model> Not Found" (404).
     */
    public static function findByKey(string $model, int|string $key, string $byName)
    {
        $record = ctype_digit((string) $key)
            ? $model::find((int) $key)
            : $model::$byName($key);

        return $record ?? throw (new ModelNotFoundException())->setModel($model);
    }
}