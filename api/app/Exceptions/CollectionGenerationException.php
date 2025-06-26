<?php

namespace App\Exceptions;

use Exception;

class CollectionGenerationException extends Exception
{
    public array $errors;

    public function __construct(array $errors)
    {
        parent::__construct('No se pudo generar cobranzas para algunos contratos.');
        $this->errors = $errors;
    }
}
