<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\Rules\Unique;

/**
 * Funzioni statiche Helper
 */
class AppHelpers {

    /**
     * Cambia le regole delle richieste passate eliminando i 'required'
     *
     * @param array $rules Le regole
     * @param integer|string|null|null $ignoreId L'ID delle entità da ignorare per le regole passate
     * @param array $keepRequired I campi da mantenere invariati
     * @return array Il nuovo array di regole
     */
    public static function updateRequestRules(
        array $rules,
        int|string|null $ignoreId = null,
        array $keepRequired = [],
    ) : array
    {
        //Ritorna array --> per ogni regola passata
        return Arr::map($rules, function ($ruleset, $key) use ($ignoreId, $keepRequired) {

            // I campi in $keepRequired mantengono il loro ruleset invariato
            if (in_array($key, $keepRequired)) {
                return $ruleset;
            }

            // Oggetto 'Rule' singolo non incapsulato in un array
            if ($ruleset instanceof Unique) {
                //Mantieni unique, ignorando l'ID in $ignoreId se presente
                return $ignoreId !== null ? $ruleset->ignore($ignoreId) : $ruleset;
            }

            // Se le regole sono di tipo array
            if (is_array($ruleset)) {
                //Filtra i 'required' ma NON i 'required_if'
                $ruleset = array_values(array_filter(
                    $ruleset, 
                    fn ($r) => $r !== 'required' && !($r instanceof RequiredIf)
                ));

                //Se presente $ignoreId, ignora l'ID passato nelle regole 'Unique'
                if ($ignoreId !== null) {
                    foreach ($ruleset as $rule) {
                        if ($rule instanceof Unique) {
                            $rule->ignore($ignoreId);
                        }
                    }
                }

                //Ritorna regole modificate
                return $ruleset;
            }

            //Se le regole sono in forma stringa
            if (is_string($ruleset)) {
                //Separa stringa e rimuove 'required' 
                return array_filter(explode('|', $ruleset), fn ($r) => $r !== 'required');
            }

            //Ritorna regole invariate di default
            return $ruleset;
        });
    }

    /**
     * Genera un sale casuale esadecimale
     *
     * @return string Il sale generato
     */
    public static function generateSalt() : string
    {
        //Genera byte randomici e converte in esadecimale
        return bin2hex(random_bytes(32));
    }

    /**
     * Estrae il codice di stato da un Throwable e lo ritorna se rientra nei codici di stato HTTP, 500 altrimenti
     *
     * @param \Throwable $th
     * @return integer Lo stato se tra 100 e 600, 500 di default
     */
    public static function safeHttpStatus(\Throwable $th) : int 
    {
        //Estrai il codice di stato
        $code = (int) $th->getCode();
        //Ritorna lo stato se tra 100 (incluso) e 600 (escluso), altrimenti 500
        return ($code >= 100 && $code < 600) ? $code : 500;
    }

    /**
     * Formatta una risposta di tipo JSON con il messaggio, lo stato e i dati passati
     *
     * @param string $message Il messaggio principale da visualizzare, ineriti nel campo 'message' oppure 'error' se lo stato è di errore
     * @param integer $status Lo stato della risposta. Se il codice è di errore (tra 400 e 599) il messaggio passato è inserito in un campo 'error'
     * @param mixed $extra (OPZIONALE) I dati extra, inseriti all'interno del campo 'data' 
     * @return \Illuminate\Http\JsonResponse
     */
    public static function jsonResponse(string $message, int $status = 200, mixed $extra = []) : \Illuminate\Http\JsonResponse
    {
        //controlla se ha extra passati
        $hasData = func_num_args() >= 3;

        //Se $extra non è array converti in array
        if (!is_array($extra)) {
            try {
                $extra = Arr::from($extra);
            } catch (\Throwable $th) {
                $extra = (array) $extra;
            }
        }

        //Inserisce il messaggio passato in 'error' oppure 'message' in base a $status
        $msg = ($status >= 400 && $status < 600) 
            ? ['error' => $message]
            : ['message' => $message];

        //Inserisce $extra nel campo 'data'
        $extraArr = $hasData ? ['data' => $extra] : [];

        //Crea l'oggetto JSON risposta e unisci i tre campi
        return response()->json(array_merge($msg, $extraArr), $status);
    }

    /**
     * Ritorna una risposta JSON di 'operazione non autorizzata' (403 Not Authorized) con il messaggio e i dati passati
     *
     * @param string $message Il messaggio principale inseriti nel campo 'error'
     * @param mixed $extra I dati extra inseriti nel campo 'data'
     * @return \Illuminate\Http\JsonResponse
     */
    public static function jsonResponseForbidden(string $message = "You aren't authorized to do this!", mixed $extra = []) : \Illuminate\Http\JsonResponse
    {
        return static::jsonResponse($message, 403, $extra);
    }

    public static function isInFutureAfter(Carbon|int|string $currTime, int|string $duration) : bool
    {
        if (!$currTime instanceof Carbon) {
            $currTime = Carbon::parse($currTime);
        }

        $expirationTime = $currTime->addSeconds($duration);
        return $expirationTime->isFuture();
    }

    public static function isFuture(Carbon|int|string $time)
    {
        if (!$time instanceof Carbon) {
            $time = Carbon::parse($time);
        }

        return $time->isFuture();
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