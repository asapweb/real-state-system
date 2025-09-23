# DemoRealStateSeeder - Resumen Completo

## Archivos Creados

### 1. Seeder Principal
- **`api/database/seeders/DemoRealStateSeeder.php`** - Seeder completo con 100 contratos y datos relacionados

### 2. Factories
- **`api/database/factories/IndexTypeFactory.php`** - Factory para tipos de índice

### 3. Comandos Artisan
- **`api/app/Console/Commands/SeedDemoData.php`** - Comando para ejecutar el seeder
- **`api/app/Console/Commands/CleanDemoData.php`** - Comando para limpiar datos de demo

### 4. Documentación
- **`api/database/seeders/README_DemoRealStateSeeder.md`** - Documentación completa
- **`api/database/seeders/example_usage.md`** - Ejemplos de uso y troubleshooting
- **`DEMO_SEEDER_SUMMARY.md`** - Este archivo de resumen

### 5. Configuración
- **`api/database/seeders/DatabaseSeeder.php`** - Actualizado para incluir el seeder de demo

## Características Implementadas

### ✅ Requisitos Generales
- [x] PHP 8.2+, Laravel 11
- [x] Faker con es_AR
- [x] Aleatoriedad determinística (mt_srand(12345))
- [x] DB::transaction() para atomicidad
- [x] Idempotente con limpieza de tablas
- [x] Protección contra producción

### ✅ Volumen y Fechas
- [x] 100 contratos mínimo
- [x] Fechas distribuidas entre 2024-01-01 y 2025-02-01
- [x] Duración 12-36 meses
- [x] 70% ARS, 30% USD
- [x] Montos realistas por moneda

### ✅ Inquilinos y Propietarios
- [x] Inquilinos: 60% 1, 30% 2, 10% 3-4
- [x] Propietarios: 50% 1, 35% 2, 15% 3-4
- [x] Un inquilino principal por contrato
- [x] Ownership percentages normalizados (suman 100%)

### ✅ Rentas y Cuotas
- [x] Plan de cuotas mensuales completo
- [x] Cargos tipo RENT con fechas correctas
- [x] Montos base iniciales
- [x] Aplicación de ajustes en cuotas posteriores
- [x] 3 meses pendientes + 3 meses liquidados

### ✅ Ajustes
- [x] 45% ICL, 25% porcentaje, 10% fijo, 15% sin ajuste, 5% negociado
- [x] Frecuencias: cuatrimestral, semestral, anual
- [x] Factores realistas (1.03-1.12)
- [x] Estados de aplicación variados
- [x] Fechas no solapadas

### ✅ Cargos Extra
- [x] 40% de contratos con cargos adicionales
- [x] Tipos: RECUP_TENANT_AGENCY, RECUP_OWNER_AGENCY, BONIFICATION
- [x] Políticas de moneda respetadas
- [x] Estados variados: pending, validated, billed, etc.
- [x] 15% con bonificaciones

### ✅ Liquidaciones
- [x] 6 períodos alternados por contrato
- [x] Vouchers tipo LIQ con items correctos
- [x] Referencias entre cargos y vouchers
- [x] Estados variados: draft, issued, paid

### ✅ Cobranzas
- [x] 60% de liquidaciones tienen cobranza
- [x] Vouchers tipo RCB
- [x] Aplicaciones parciales/totales
- [x] Estados de flujo realistas

### ✅ Calidad de Datos
- [x] Validaciones de integridad
- [x] Ownership percentages = 100%
- [x] Un tenant principal
- [x] Cargos en período correcto
- [x] Ajustes aplicados correctamente
- [x] Liquidaciones con al menos un item RENT

## Comandos de Uso

### Ejecución Básica
```bash
# Ejecutar seeder completo
php artisan demo:seed

# Limpiar datos de demo
php artisan demo:clean

# Ejecutar con limpieza previa
php artisan demo:seed --clean
```

### Ejecución Avanzada
```bash
# Forzar en producción
php artisan demo:seed --force

# Sin confirmación
php artisan demo:clean --confirm

# Seeder individual
php artisan db:seed --class=DemoRealStateSeeder
```

## Datos Generados

### Contratos (100)
- Fechas: 2024-01-01 a 2025-02-01
- Duración: 12-36 meses
- Monedas: 70% ARS, 30% USD
- Montos: ARS 150k-600k, USD 200-1200

### Clientes (200) - Bahía Blanca
- Inquilinos y propietarios de Bahía Blanca, Buenos Aires
- Nombres y apellidos argentinos típicos
- Teléfonos con código de área 291
- Direcciones en calles reales de Bahía Blanca
- DNIs argentinos válidos (20M-99M)
- CUITs con formato argentino

### Propiedades (80) - Bahía Blanca
- Ubicadas en Bahía Blanca, Buenos Aires
- Calles reales de la ciudad
- Código postal 8000
- Características variadas (cochera, mascotas)
- Estados disponibles

### Ajustes (~200)
- 45% ICL VACÍOS (sin factor, valor, fechas) - para asignación manual
- 25% porcentaje (10-35%, pendientes)
- 10% fijo (pendientes)
- 15% sin ajuste
- 5% negociado (pendientes)
- **Estado**: Todos PENDIENTES de aplicación
- **Fechas**: Generados 3-6 meses después del inicio del contrato
- **Propósito**: Probar asignación y aplicación manual/en lote

### Cargos (~3000)
- Rentas mensuales completas
- Cargos adicionales (40% contratos)
- Bonificaciones (15% contratos)
- Estados variados

### Vouchers (~600)
- Liquidaciones (LIQ)
- Cobranzas (RCB)
- Items vinculados correctamente
- Estados de flujo realistas

## Validaciones Incluidas

### Integridad Referencial
- Foreign keys correctas
- Referencias entre entidades
- Estados coherentes

### Lógica de Negocio
- Ownership percentages suman 100%
- Un tenant principal por contrato
- Fechas de cargos en período
- Ajustes aplicados correctamente

### Calidad de Datos
- Montos calculados correctamente
- Fechas no solapadas
- Estados válidos
- Referencias consistentes

## Estructura de Archivos

```
api/
├── database/
│   ├── seeders/
│   │   ├── DemoRealStateSeeder.php
│   │   ├── README_DemoRealStateSeeder.md
│   │   └── example_usage.md
│   └── factories/
│       └── IndexTypeFactory.php
├── app/
│   └── Console/
│       └── Commands/
│           ├── SeedDemoData.php
│           └── CleanDemoData.php
└── DEMO_SEEDER_SUMMARY.md
```

## Próximos Pasos

1. **Ejecutar el seeder** en un entorno de desarrollo
2. **Verificar los datos** con las consultas de ejemplo
3. **Personalizar** según necesidades específicas
4. **Integrar** con tests automatizados
5. **Documentar** casos de uso específicos

## Soporte

Para problemas o preguntas:
1. Revisar `README_DemoRealStateSeeder.md`
2. Consultar `example_usage.md`
3. Verificar logs en `storage/logs/laravel.log`
4. Ejecutar validaciones de integridad

---

**¡El seeder está listo para usar!** 🚀

Ejecuta `php artisan demo:seed` para comenzar a trabajar con datos de prueba realistas del sistema inmobiliario.
