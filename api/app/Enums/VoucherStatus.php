<?php

namespace App\Enums;

enum VoucherStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Cancelled = 'cancelled';

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    public function isIssued(): bool
    {
        return $this === self::Issued;
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }
}

