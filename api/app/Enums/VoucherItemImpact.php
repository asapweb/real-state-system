<?php

namespace App\Enums;

enum VoucherItemImpact: string
{
    case Add      = 'add';
    case Subtract = 'subtract';

    public function sign(): int
    {
        return $this === self::Add ? 1 : -1;
    }

    public static function fromSignedAmount(float $amount): self
    {
        return $amount < 0 ? self::Subtract : self::Add;
    }
}
