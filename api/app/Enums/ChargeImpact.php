<?php

namespace App\Enums;

enum ChargeImpact: string
{
    case ADD = 'add';
    case SUBTRACT = 'subtract';
    case INFO = 'info';
    case HIDDEN = 'hidden';

    public function sign(): int
    {
        return match ($this) {
            self::ADD      =>  1,
            self::SUBTRACT => -1,
            self::INFO,
            self::HIDDEN   =>  0,
        };
    }

    public function isIncluded(): bool
    {
        return $this === self::ADD || $this === self::SUBTRACT;
    }

}
