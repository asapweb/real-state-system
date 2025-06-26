<?php

namespace App\Enums;

enum DepositHolder: string
{
    case OWNER = 'owner';
    case AGENCY = 'agency';
    case THIRD_PARTY = 'third_party';
}



