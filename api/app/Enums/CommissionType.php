<?php

namespace App\Enums;

enum CommissionType: string
{
    case NONE = 'none';
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';
}

