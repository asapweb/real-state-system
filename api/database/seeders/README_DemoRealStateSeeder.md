# DemoRealStateSeeder

Seeder completo para poblar datos de prueba del sistema inmobiliario con datos coherentes y realistas.

## Características

- **Idempotente**: Se puede ejecutar múltiples veces sin duplicar datos
- **Determinístico**: Usa semilla fija para generar datos consistentes
- **Completo**: Cubre todos los módulos principales del sistema
- **Realista**: Genera datos que reflejan escenarios reales de gestión inmobiliaria

## Datos Generados

### Volumen
- **100 contratos** con fechas distribuidas entre 2024-01-01 y 2025-02-01
- **200 clientes** (inquilinos y propietarios) de **Bahía Blanca, Buenos Aires**
- **80 propiedades** con datos completos
- **Duración variable**: 12-36 meses por contrato

### Clientes (Bahía Blanca)
- **Nombres**: Argentinos típicos (Carlos, María, Juan, Ana, etc.)
- **Apellidos**: González, Rodríguez, Fernández, López, etc.
- **Teléfonos**: +54 291 XXXX-XXXX (código de área de Bahía Blanca)
- **Direcciones**: Calles reales de Bahía Blanca (Donado, España, Zeballos, etc.)
- **DNIs**: 20M-99M (rango válido argentino)
- **CUITs**: Formato 20-XXXXXXXX-9
- **Emails**: Nombres con dominios argentinos (.com.ar, gmail.com, etc.)

### Propiedades (Bahía Blanca)
- **Calles**: Calles reales de la ciudad
- **Código postal**: 8000 (Bahía Blanca)
- **Ubicación**: Buenos Aires, Argentina
- **Características**: 60% con cochera, 40% permite mascotas

### Contratos
- **Monedas**: 70% ARS, 30% USD
- **Montos**: ARS 150k-600k, USD 200-1200
- **Estados**: Todos activos con datos completos
- **Configuración**: Comisiones, seguros, depósitos, penalidades

### Clientes por Contrato
- **Inquilinos**: 60% 1, 30% 2, 10% 3-4 (uno principal)
- **Propietarios**: 50% 1, 35% 2, 15% 3-4
- **Ownership**: Porcentajes normalizados que suman 100%

### Ajustes (45% de contratos)
- **45% ICL**: Ajustes de índice VACÍOS (sin factor, valor, fechas) - para probar asignación manual
- **25% Porcentaje**: 10-35% de incremento, pendientes de aplicación
- **10% Fijo**: Monto absoluto, pendientes de aplicación
- **15% Sin ajuste**: Contratos sin modificaciones
- **5% Negociado**: Ajustes pendientes de aplicación
- **Estado**: Todos los ajustes están PENDIENTES (no aplicados)
- **Fechas**: Los ajustes se generan 3-6 meses después del inicio del contrato (realista)
- **Propósito**: Probar asignación y aplicación manual/en lote

### Cargos y Cuotas
- **Rentas mensuales**: Una por mes del período del contrato
- **Cargos adicionales**: 40% de contratos con servicios extra
- **Bonificaciones**: 15% de contratos con descuentos
- **Estados variados**: Draft, pending, validated, billed, etc.

### Liquidaciones y Cobranzas
- **Liquidaciones**: 6 períodos alternados por contrato
- **Cobranzas**: 60% de liquidaciones tienen recibo
- **Estados**: Mezcla de draft, issued, paid
- **Referencias**: Cargos vinculados a vouchers

## Uso

### Ejecución Completa
```bash
php artisan db:seed --class=DemoRealStateSeeder
```

### Ejecución Individual (después de otros seeders)
```bash
php artisan db:seed --class=DemoRealStateSeeder
```

### Verificación
```bash
php artisan tinker
>>> App\Models\Contract::count()
>>> App\Models\ContractAdjustment::count()
>>> App\Models\ContractCharge::count()
>>> App\Models\Voucher::count()
```

## Requisitos Previos

El seeder requiere que se ejecuten primero:
- `VoucherTypeSeeder`
- `BookletSeeder`
- `ChargeTypeSeeder`
- `ServiceTypeSeeder`
- Seeders de datos maestros (países, ciudades, etc.)

## Estructura de Datos

### Contratos
- Fechas realistas con prorrateo
- Configuración completa de comisiones y seguros
- Penalidades y depósitos configurados
- Estados coherentes

### Ajustes
- Fechas no solapadas
- Factores realistas para índices
- Estados de aplicación variados
- Notas descriptivas

### Cargos
- Montos calculados correctamente
- Fechas de vigencia coherentes
- Referencias a liquidaciones
- Estados de procesamiento

### Vouchers
- Numeración secuencial
- Totales calculados correctamente
- Items vinculados a cargos
- Estados de flujo realistas

## Validaciones

El seeder incluye validaciones automáticas:
- Ownership percentages suman 100%
- Un único tenant principal por contrato
- Fechas de cargos dentro del período del contrato
- Referencias correctas entre entidades
- Estados coherentes en el flujo

## Personalización

Para modificar el volumen o distribución:
1. Editar las constantes en `createContracts()`
2. Ajustar porcentajes en `createContractClients()`
3. Modificar políticas en `createAdjustments()`
4. Cambiar frecuencias en `createCharges()`

## Troubleshooting

### Error de Foreign Key
- Verificar que se ejecutaron los seeders previos
- Revisar que las tablas de referencia tienen datos

### Error de Enum
- Verificar que los enums están definidos correctamente
- Revisar las importaciones en el seeder

### Datos Inconsistentes
- El seeder usa semilla fija, los datos deben ser consistentes
- Si hay inconsistencias, verificar la lógica de normalización

## Notas Técnicas

- Usa `DB::transaction()` para atomicidad
- Desactiva `FOREIGN_KEY_CHECKS` temporalmente
- Usa `mt_srand(12345)` para determinismo
- Limpia tablas antes de crear datos
- Incluye validaciones de integridad
