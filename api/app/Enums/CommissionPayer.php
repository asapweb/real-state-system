<?php

namespace App\Enums;

enum CommissionPayer: string
{
    case TENANT = 'tenant';
    case OWNER = 'owner';
    case BOTH = 'both';
}


