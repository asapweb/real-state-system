<?php

namespace App\Events;

use App\Models\ContractCharge;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractChargeCanceled
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public ContractCharge $contractCharge)
    {
    }
}
