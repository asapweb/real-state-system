# Ejemplo de Uso - DemoRealStateSeeder

## Comandos Disponibles

### 1. Ejecutar Seeder Completo
```bash
# Ejecutar con confirmación
php artisan demo:seed

# Ejecutar limpiando datos previos
php artisan demo:seed --clean

# Forzar en producción (¡CUIDADO!)
php artisan demo:seed --force
```

### 2. Limpiar Datos de Demo
```bash
# Limpiar con confirmación
php artisan demo:clean

# Limpiar sin confirmación
php artisan demo:clean --confirm

# Forzar en producción
php artisan demo:clean --force
```

### 3. Ejecutar Seeder Individual
```bash
# Solo el seeder de demo
php artisan db:seed --class=DemoRealStateSeeder

# Todos los seeders (incluye demo)
php artisan db:seed
```

## Verificación de Datos

### Consultas Útiles
```php
// En tinker: php artisan tinker

// Contratos creados
App\Models\Contract::count()
App\Models\Contract::with('clients')->first()

// Ajustes por tipo
App\Models\ContractAdjustment::groupBy('type')->selectRaw('type, COUNT(*) as count')->get()

// Cargos de renta
App\Models\ContractCharge::whereHas('chargeType', fn($q) => $q->where('code', 'RENT'))->count()

// Vouchers creados
App\Models\Voucher::with('voucherType')->get()

// Liquidaciones vs Cobranzas
App\Models\Voucher::whereHas('voucherType', fn($q) => $q->where('short_name', 'LIQ'))->count()
App\Models\Voucher::whereHas('voucherType', fn($q) => $q->where('short_name', 'RCB'))->count()
```

### Verificaciones de Integridad
```php
// Ownership percentages suman 100%
App\Models\ContractClient::where('role', 'owner')
    ->selectRaw('contract_id, SUM(ownership_percentage) as total')
    ->groupBy('contract_id')
    ->having('total', '!=', 100)
    ->get()

// Un tenant principal por contrato
App\Models\ContractClient::where('role', 'tenant')
    ->where('is_primary', true)
    ->groupBy('contract_id')
    ->havingRaw('COUNT(*) > 1')
    ->get()

// Cargos dentro del período del contrato
App\Models\ContractCharge::whereHas('contract', function($q) {
    $q->whereRaw('contract_charges.effective_date < contracts.start_date')
      ->orWhereRaw('contract_charges.effective_date > contracts.end_date');
})->count()
```

## Escenarios de Prueba

### 1. Contratos con Ajustes Pendientes
```php
// Contratos con ajustes sin aplicar
App\Models\Contract::whereHas('adjustments', function($q) {
    $q->whereNull('applied_at');
})->with('adjustments')->get()
```

### 2. Liquidaciones sin Cobranza
```php
// Liquidaciones que no tienen cobranza asociada
App\Models\Voucher::whereHas('voucherType', fn($q) => $q->where('short_name', 'LIQ'))
    ->whereDoesntHave('associatedVouchers', function($q) {
        $q->whereHas('voucherType', fn($q) => $q->where('short_name', 'RCB'));
    })->get()
```

### 3. Cargos con Referencias
```php
// Cargos vinculados a liquidaciones
App\Models\ContractCharge::whereNotNull('tenant_liquidation_voucher_id')
    ->with('tenantLiquidationVoucher')
    ->get()
```

## Personalización

### Modificar Volumen
```php
// En DemoRealStateSeeder.php, método createContracts()
for ($i = 0; $i < 200; $i++) { // Cambiar de 100 a 200
```

### Cambiar Distribución de Monedas
```php
// En createContracts()
$currency = $this->faker->randomFloat(1, 0, 1) <= 0.8 ? 'ARS' : 'USD'; // 80% ARS, 20% USD
```

### Ajustar Políticas de Ajuste
```php
// En createAdjustments()
$adjustmentPolicy = $this->faker->randomElement([
    'index' => 0.6,      // 60% ICL
    'percentage' => 0.3, // 30% porcentaje
    'none' => 0.1        // 10% sin ajuste
]);
```

## Troubleshooting

### Error: "No se puede ejecutar en producción"
```bash
# Usar --force para forzar en producción
php artisan demo:seed --force
```

### Error: "Foreign key constraint fails"
```bash
# Verificar que se ejecutaron seeders previos
php artisan db:seed --class=VoucherTypeSeeder
php artisan db:seed --class=BookletSeeder
php artisan db:seed --class=ChargeTypeSeeder
```

### Datos Inconsistentes
```bash
# Limpiar y volver a ejecutar
php artisan demo:clean --confirm
php artisan demo:seed
```

### Error de Memoria
```bash
# Aumentar límite de memoria
php -d memory_limit=512M artisan demo:seed
```

## Monitoreo

### Logs de Ejecución
```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Filtrar logs del seeder
grep "DemoRealStateSeeder" storage/logs/laravel.log
```

### Performance
```bash
# Medir tiempo de ejecución
time php artisan demo:seed

# Con profiling
php artisan demo:seed --profile
```
