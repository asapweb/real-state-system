<?php

namespace App\Enums;

enum PropertyStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Rented = 'rented';
    case Withdrawn = 'withdrawn';
}
