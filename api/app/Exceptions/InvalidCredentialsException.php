<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidCredentialsException extends HttpException
{
    protected int $code = 401;
    protected string $message = 'Username and/or Password are Incorrect';

    public function __construct() {
        parent::__construct($this->code, $this->message);
    }
}