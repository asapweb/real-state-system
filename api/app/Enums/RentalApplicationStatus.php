<?php

namespace App\Enums;

enum RentalApplicationStatus: string
{
    case Draft = 'draft';           // Iniciada pero sin enviar
    case Submitted = 'submitted';   // Enviada y en revisión
    case Reviewed = 'reviewed';     // Evaluada pero no decidida
    case Approved = 'approved';     // Aprobada para avanzar al contrato
    case Rejected = 'rejected';     // Desestimada
}
