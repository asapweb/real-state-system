<?php

namespace App\Enums;

enum ContractChargeResponsibleParty: string
{
    case TENANT = 'tenant';
    case OWNER  = 'owner';
}
