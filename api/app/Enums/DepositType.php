<?php

namespace App\Enums;

enum DepositType: string
{
    case NONE = 'none';
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';
}


