<?php

namespace App\Enums;

enum RentalOfferStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Expired = 'expired';
    case Withdrawn = 'withdrawn';
}
