<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\ContractExpense;
use App\Models\Contract;
use App\Services\ContractBillingService;
use Carbon\Carbon;

class CollectionResource extends JsonResource
{
    protected $period;

    public function __construct($resource, Carbon $period)
    {
        parent::__construct($resource);
        $this->period = normalizePeriodOrFail($period);
    }

    public function toArray($request)
    {
        $contract = $this->resource;

        $billing = app(\App\Services\ContractBillingService::class)->getBillingPreview($contract, $this->period);

        return [
            'contract_id' => $contract->id,
            'contract_code' => sprintf("CON-%04d", $contract->id),
            'tenant_name' => $contract->mainTenant?->client?->full_name,
            'period' => $this->period->format('Y-m'),
            'currency' => $contract->currency,
            'rent_amount' => $billing['rent'],
            'expenses' => $billing['expenses'],
            'has_pending_adjustment' => $billing['pending_adjustment'],
            'status' => app(ContractBillingService::class)->determineStatus($contract, $this->period, $billing['pending_adjustment'])
        ];
            

    }
}