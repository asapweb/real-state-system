<?php

namespace App\Exceptions;

use DomainException;

class VoucherCancellationConflictException extends DomainException
{
    /**
     * @param array<int, string> $reasons
     */
    public function __construct(string $message, protected array $reasons = [])
    {
        parent::__construct($message);
    }

    /**
     * @return array<int, string>
     */
    public function reasons(): array
    {
        return $this->reasons;
    }
}

