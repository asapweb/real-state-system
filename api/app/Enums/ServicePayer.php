<?php

namespace App\Enums;

enum ServicePayer: string
{
    case TENANT = 'tenant';
    case OWNER = 'owner';
    case AGENCY = 'agency';

}
