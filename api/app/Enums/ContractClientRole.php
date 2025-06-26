<?php

namespace App\Enums;

enum ContractClientRole: string
{
    case TENANT = 'tenant';
    case GUARANTOR = 'guarantor';
    case OWNER = 'owner';
}
