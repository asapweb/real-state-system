<?php

namespace App\Enums;

enum VoucherItemType: string
{
    // Tipos heredados de CollectionItemType
    case RENT = 'rent';
    case INSURANCE = 'insurance';
    case COMMISSION = 'commission';
    case SERVICE = 'service';
    case PENALTY = 'penalty';
    case PRODUCT = 'product';
    case ADJUSTMENT = 'adjustment';
    case LATE_FEE = 'late_fee';

    // Tipos específicos de voucher
    case TAX = 'tax';
    case DISCOUNT = 'discount';
    case CHARGE = 'charge';
    case CREDIT = 'credit';
    case DEBIT = 'debit';
}
