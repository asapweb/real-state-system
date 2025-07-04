<?php

namespace App\Enums;

enum CollectionItemType: string
{
    case RENT = 'rent';
    case INSURANCE = 'insurance';
    case COMMISSION = 'commission';
    case SERVICE = 'service';
    case PENALTY = 'penalty';
    case PRODUCT = 'product';
    case ADJUSTMENT = 'adjustment';
    case LATE_FEE = 'late_fee'; // nuevo tipo para intereses por mora
}
