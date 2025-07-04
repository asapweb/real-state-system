<?php

namespace App\Enums;

enum PropertyStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case RENTED = 'rented';
    case WITHDRAWN = 'withdrawn';
}
