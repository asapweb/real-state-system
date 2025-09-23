<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ChargeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now()->toDateTimeString();

        $rows = [
            [
                'code'=>'RENT',
                'name'=>'Alquiler Mensual',
                'is_active'=>true,
                'tenant_impact'=>'add',
                'owner_impact'=>'add',
                'requires_service_period'=>false,
                'requires_service_type'=>false,
                'requires_counterparty'=>null,
                'currency_policy'=>'CONTRACT_CURRENCY',
                'created_at'=>$now,'updated_at'=>$now
            ],
            [
                'code'=>'ADJ_DIFF_DEBIT',
                'name'=>'Diferencia de Ajuste (Débito)',
                'is_active'=>true,
                'tenant_impact'=>'add',
                'owner_impact'=>'add',
                'requires_service_period'=>true,
                'requires_service_type'=>false, // ajuste general; si quisieras por servicio, ponelo en true
                'requires_counterparty'=>null,
                'currency_policy'=>'CONTRACT_CURRENCY',
                'created_at'=>$now,'updated_at'=>$now
            ],
            [
                'code'=>'ADJ_DIFF_CREDIT',
                'name'=>'Diferencia de Ajuste (Crédito)',
                'is_active'=>true,
                'tenant_impact'=>'subtract',
                'owner_impact'=>'subtract',
                'requires_service_period'=>true,
                'requires_service_type'=>false,
                'requires_counterparty'=>null,
                'currency_policy'=>'CONTRACT_CURRENCY',
                'created_at'=>$now,'updated_at'=>$now
            ],
            [
                'code'=>'RECUP_TENANT_AGENCY',
                'name'=>'Recupero de Inmobiliaria al Inquilino',
                'is_active'=>true,
                'tenant_impact'=>'add',
                'owner_impact'=>'hidden',
                'requires_service_period'=>false,
                'requires_service_type'=>true,  // recupero de un servicio concreto (luz/expensas/etc.)
                'requires_counterparty'=>'tenant',
                'currency_policy'=>'CONTRACT_CURRENCY',
                'created_at'=>$now,'updated_at'=>$now
            ],
            [
                'code'=>'RECUP_OWNER_AGENCY',
                'name'=>'Recupero de Inmobiliaria al Propietario',
                'is_active'=>true,
                'tenant_impact'=>'hidden',
                'owner_impact'=>'subtract',
                'requires_service_period'=>false,
                'requires_service_type'=>true,  // retención por concepto específico (ABL/consorcio/etc.)
                'requires_counterparty'=>'owner', // si querés permitir pool, podés dejar null
                'currency_policy'=>'CONTRACT_CURRENCY',
                'created_at'=>$now,'updated_at'=>$now
            ],
            [
                'code'=>'RECUP_TENANT_OWNER',
                'name'=>'Recupero de Inquilino a Propietario',
                'is_active'=>true,
                'tenant_impact'=>'add',
                'owner_impact'=>'add',
                'requires_service_period'=>false,
                'requires_service_type'=>false, // suele ser redistribución general
                'requires_counterparty'=>null,
                'currency_policy'=>'CONTRACT_CURRENCY',
                'created_at'=>$now,'updated_at'=>$now
            ],
            [
                'code'=>'RECUP_OWNER_TENANT',
                'name'=>'Recupero de Propietario a Inquilino',
                'is_active'=>true,
                'tenant_impact'=>'subtract',
                'owner_impact'=>'subtract',
                'requires_service_period'=>false,
                'requires_service_type'=>false,
                'requires_counterparty'=>null,
                'currency_policy'=>'CONTRACT_CURRENCY',
                'created_at'=>$now,'updated_at'=>$now
            ],
            [
                'code'=>'BONIFICATION',
                'name'=>'Bonificación / Descuento',
                'is_active'=>true,
                'tenant_impact'=>'subtract',
                'owner_impact'=>'subtract',
                'requires_service_period'=>false,
                'requires_service_type'=>false,
                'requires_counterparty'=>null,
                'currency_policy'=>'CONTRACT_CURRENCY',
                'created_at'=>$now,'updated_at'=>$now
            ],
            [
                'code'=>'SELF_PAID_INFO',
                'name'=>'Pagado Directamente por Inquilino (Informativo)',
                'is_active'=>true,
                'tenant_impact'=>'info',
                'owner_impact'=>'info',
                'requires_service_period'=>true,
                'requires_service_type'=>true, // clave para conciliación por servicio
                'requires_counterparty'=>null,
                'currency_policy'=>'CONTRACT_CURRENCY',
                'created_at'=>$now,'updated_at'=>$now
            ],
        ];
        

        DB::table('charge_types')->upsert(
            $rows,
            ['code'],
            ['name','is_active','tenant_impact','owner_impact','requires_service_period','requires_counterparty','currency_policy','updated_at']
        );
    }
}
