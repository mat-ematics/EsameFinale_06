<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Eccezione HTTP credenziali invalide (errore 401)
 */
class InvalidCredentialsException extends HttpException
{
    public function __construct() {
        parent::__construct(401, 'Username and/or Password are Incorrect');
    }
}