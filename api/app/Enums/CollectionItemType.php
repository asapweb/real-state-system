<?php

namespace App\Enums;

enum CollectionItemType: string
{
    case Rent = 'rent';
    case Insurance = 'insurance';
    case Commission = 'commission';
    case Service = 'service';
    case Penalty = 'penalty';
    case Product = 'product';
    case Adjustment = 'adjustment';
}
