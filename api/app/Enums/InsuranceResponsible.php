<?php

namespace App\Enums;

enum InsuranceResponsible: string
{
    case TENANT = 'tenant';
    case OWNER = 'owner';
    case BOTH = 'both';
}
