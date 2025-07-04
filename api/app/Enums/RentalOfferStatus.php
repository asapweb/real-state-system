<?php

namespace App\Enums;

enum RentalOfferStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case EXPIRED = 'expired';
    case WITHDRAWN = 'withdrawn';
}
