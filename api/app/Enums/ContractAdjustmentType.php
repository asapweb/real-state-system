<?php

namespace App\Enums;

enum ContractAdjustmentType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
    case Index = 'index';
    case Negotiated = 'negotiated';
}

