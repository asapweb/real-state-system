<?php

namespace App\Enums;

enum ContractChargeStatus: string
{
    case DRAFT      = 'draft';
    case PENDING    = 'pending';
    case VALIDATED  = 'validated';
    case BILLED     = 'billed';
    case CREDITED   = 'credited';
    case LIQUIDATED = 'liquidated';
    case CANCELED   = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT      => 'Borrador',
            self::PENDING    => 'Pendiente',
            self::VALIDATED  => 'Validado',
            self::BILLED     => 'Facturado/Cobrado',
            self::CREDITED   => 'Acreditado/Bonificado',
            self::LIQUIDATED => 'Liquidado',
            self::CANCELED   => 'Cancelado',
        };
    }
}
