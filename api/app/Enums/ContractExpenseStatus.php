<?php
namespace App\Enums;
/**
 * Estados posibles para ContractExpense:
 * - pending: Registrado, aún sin impacto financiero.
 * - validated: Validado, ya tiene un comprobante asociado.
 * - billed: Imputado a una factura o nota de débito (FAC/N/D).
 * - credited: Compensado mediante una nota de crédito (NC).
 * - liquidated: Incluido en una liquidación al propietario (LIQ).
 * - canceled: Anulado (sin impacto posterior).
 */

enum ContractExpenseStatus: string
{
    case PENDING = 'pending';
    case VALIDATED = 'validated';
    case BILLED = 'billed';
    case CREDITED = 'credited';
    case LIQUIDATED = 'liquidated';
    case CANCELED = 'canceled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::VALIDATED => 'Validado',
            self::BILLED => 'Facturado / ND',
            self::CREDITED => 'Compensado (NC)',
            self::LIQUIDATED => 'Liquidado',
            self::CANCELED => 'Anulado',
        };
    }
}

