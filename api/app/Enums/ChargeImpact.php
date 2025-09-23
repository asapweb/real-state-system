<?php

namespace App\Enums;

enum ChargeImpact: string
{
    case ADD = 'add';
    case SUBTRACT = 'subtract';
    case INFO = 'info';
    case HIDDEN = 'hidden';
}
