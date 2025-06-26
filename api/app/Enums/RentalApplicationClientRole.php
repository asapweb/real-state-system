<?php

namespace App\Enums;

enum RentalApplicationClientRole: string
{
    case APPLICANT = 'applicant';
    case GUARANTOR = 'guarantor';
    case CO_APPLICANT = 'co-applicant';
    case SPOUSE = 'spouse';
    case OTHER = 'other';
}


