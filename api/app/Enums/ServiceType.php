<?php

namespace App\Enums;

enum ServiceType: string
{
    case ELECTRICITY = 'electricity';
    case PHONE = 'phone';
    case GAS = 'gas';
    case WATER = 'water';
    case INTERNET = 'internet';
    case CABLE_TV = 'cable_tv';
    case MUNICIPAL_TAX = 'municipal_tax';
    case ARBA = 'arba';
    case OTHER = 'other';
}
