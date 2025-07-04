<?php

namespace App\Enums;

enum ContractStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled';
    case FINISHED = 'finished';

}
