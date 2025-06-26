<?php

namespace App\Enums;

enum ContractStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Finished = 'finished';

}
