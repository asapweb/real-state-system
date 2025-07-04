<?php

namespace App\Enums;

enum ContractAdjustmentType: string
{
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';
    case INDEX = 'index';
    case NEGOTIATED = 'negotiated';
}

