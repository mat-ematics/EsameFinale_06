<?php

namespace App\Exceptions;

use Exception;

class InvalidCredentialsException extends Exception
{
    protected $message = 'Username and/or Password are Incorrect';
    protected $code = 401;
}