<?php

namespace App\Enums;

enum RentalApplicationStatus: string
{
    case DRAFT = 'draft';           // Iniciada pero sin enviar
    case SUBMITTED = 'submitted';   // Enviada y en revisión
    case REVIEWED = 'reviewed';     // Evaluada pero no decidida
    case APPROVED = 'approved';     // Aprobada para avanzar al contrato
    case REJECTED = 'rejected';     // Desestimada
}
