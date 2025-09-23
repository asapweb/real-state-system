<?php

namespace App\Enums;

enum ContractStatus: string
{
    case DRAFT = 'draft';            // En borrador, editable antes de la firma
    case ACTIVE = 'active';          // Vigente y generando rentas/liquidaciones
    case CANCELLED = 'cancelled';    // Cancelado antes de entrar en vigencia
    case TERMINATED = 'terminated';  // Rescindido anticipadamente (acuerdo o incumplimiento)
    case EXPIRED = 'expired';        // Llegó a su fecha de fin natural sin renovación

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Borrador',
            self::ACTIVE => 'Activo',
            self::CANCELLED => 'Cancelado',
            self::TERMINATED => 'Rescindido',
            self::EXPIRED => 'Vencido',
        };
    }
}
