<?php

namespace App\Enums;

enum AttachmentCategoryType: string
{
    case CONTRACT = 'contract';
    case PROPERTY = 'property';
    case APPLICATION = 'application';
    case MAINTENANCE = 'maintenance';
}


