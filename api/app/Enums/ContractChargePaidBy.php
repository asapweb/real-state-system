<?php

namespace App\Enums;

enum ContractChargePaidBy: string
{
    case TENANT = 'tenant';
    case OWNER  = 'owner';
    case AGENCY = 'agency';
}
