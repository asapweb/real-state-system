<?php

namespace App\Exceptions;

use Exception;

class PendingAdjustmentException extends Exception
{
    public function __construct(string $message = 'El contrato tiene un ajuste pendiente de aplicación.')
    {
        parent::__construct($message);
    }
} 