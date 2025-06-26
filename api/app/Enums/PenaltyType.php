<?php

namespace App\Enums;

enum PenaltyType: string
{
    case NONE = 'none';
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';
}


