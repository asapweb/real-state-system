# Migración del Sistema de Collections a Vouchers

## Resumen

Este documento describe la migración completa del sistema de `Collection` al sistema unificado de `Voucher`. Esta migración unifica todos los comprobantes del sistema en una única entidad, simplificando la arquitectura y centralizando la lógica.

## Arquitectura Nueva

### Voucher como Entidad Central

- **Voucher**: Entidad principal que representa cualquier comprobante
- **VoucherItem**: Items dentro de un voucher
- **Booklet**: Define el tipo de voucher (COB, PAY, LIQ, etc.)
- **VoucherType**: Define las características del tipo de voucher

### Tipos de Voucher

- `COB`: Cobranzas (reemplaza Collection)
- `PAY`: Pagos
- `LIQ`: Liquidaciones
- `FAC`: Facturas
- `REC`: Recibos

## Proceso de Migración

### 1. Preparación

Antes de ejecutar la migración, asegúrate de:

1. **Crear un Booklet compatible**:
   ```sql
   INSERT INTO booklets (name, voucher_type_id, active) 
   VALUES ('Cobranzas', 'COB', true);
   ```

2. **Verificar que no hay transacciones en curso**

### 2. Ejecutar Migración

```bash
# Modo dry-run (recomendado primero)
php artisan system:migrate-to-vouchers --dry-run

# Migración real
php artisan system:migrate-to-vouchers --force
```

### 3. Verificar Migración

```bash
# Verificar que se crearon los vouchers
php artisan tinker
>>> App\Models\Voucher::whereHas('booklet.voucherType', fn($q) => $q->where('code', 'COB'))->count()

# Verificar que los items se migraron
>>> App\Models\VoucherItem::whereHas('voucher.booklet.voucherType', fn($q) => $q->where('code', 'COB'))->count()
```

### 4. Actualizar Frontend

Cambiar todas las referencias de `/api/collections` a `/api/vouchers` con filtro `voucher_type=COB`.

### 5. Eliminar Tablas Obsoletas

```bash
php artisan migrate
```

## Cambios en el Código

### Nuevos Archivos

- `app/Services/VoucherGenerationService.php` - Generación de vouchers
- `app/Services/CollectionMigrationService.php` - Migración de datos
- `app/Http/Controllers/VoucherController.php` - Controlador unificado
- `app/Http/Resources/VoucherResource.php` - Resource para vouchers
- `app/Http/Resources/VoucherItemResource.php` - Resource para items
- `app/Enums/VoucherItemType.php` - Tipos de items unificados

### Archivos Modificados

- `routes/api.php` - Nuevas rutas para vouchers
- `app/Models/Voucher.php` - Actualizado con relaciones
- `app/Models/VoucherItem.php` - Actualizado con enum

### Archivos a Eliminar (después de migración)

- `app/Models/Collection.php`
- `app/Models/CollectionItem.php`
- `app/Http/Controllers/CollectionController.php`
- `app/Http/Resources/CollectionResource.php`
- `app/Http/Resources/CollectionItemResource.php`
- `app/Enums/CollectionItemType.php`
- `app/Services/CollectionService.php`
- `app/Services/CollectionGenerationService.php`

## Mapeo de Datos

### Collection → Voucher

| Collection Field | Voucher Field | Notas |
|------------------|---------------|-------|
| `id` | `id` | Nuevo ID |
| `client_id` | `client_id` | Directo |
| `contract_id` | `contract_id` | Directo |
| `status` | `status` | Mapeado: pending→draft, paid→issued |
| `currency` | `currency` | Directo |
| `issue_date` | `issue_date` | Directo |
| `due_date` | `due_date` | Directo |
| `period` | `period` | Directo |
| `total_amount` | `total` | Directo |
| `notes` | `notes` | Directo |
| `paid_at` | `meta.paid_at` | Movido a meta |
| `paid_by_user_id` | `meta.paid_by_user_id` | Movido a meta |

### CollectionItem → VoucherItem

| CollectionItem Field | VoucherItem Field | Notas |
|---------------------|-------------------|-------|
| `id` | `id` | Nuevo ID |
| `collection_id` | `voucher_id` | Directo |
| `type` | `type` | Mapeado a VoucherItemType |
| `description` | `description` | Directo |
| `quantity` | `quantity` | Directo |
| `unit_price` | `unit_price` | Directo |
| `amount` | `subtotal` | Renombrado |
| `currency` | - | Movido al voucher padre |
| `meta` | `meta` | Directo |

### Mapeo de Tipos

| CollectionItemType | VoucherItemType |
|-------------------|-----------------|
| `RENT` | `RENT` |
| `INSURANCE` | `INSURANCE` |
| `COMMISSION` | `COMMISSION` |
| `SERVICE` | `SERVICE` |
| `PENALTY` | `PENALTY` |
| `PRODUCT` | `PRODUCT` |
| `ADJUSTMENT` | `ADJUSTMENT` |
| `LATE_FEE` | `LATE_FEE` |

## Nuevas Rutas API

### Vouchers (Reemplaza Collections)

```
GET    /api/vouchers                    # Listar vouchers
GET    /api/vouchers/{voucher}         # Ver voucher
POST   /api/vouchers                   # Crear voucher
PUT    /api/vouchers/{voucher}         # Actualizar voucher
DELETE /api/vouchers/{voucher}         # Eliminar voucher
POST   /api/vouchers/{voucher}/mark-as-paid  # Marcar como pagado
POST   /api/vouchers/{voucher}/cancel  # Cancelar voucher
POST   /api/vouchers/generate-collections    # Generar cobranzas
POST   /api/vouchers/preview-collections     # Preview de cobranzas
```

### Filtros Disponibles

- `voucher_type=COB` - Solo cobranzas
- `status=draft|issued|canceled` - Por estado
- `client_id=123` - Por cliente
- `contract_id=456` - Por contrato
- `period=2024-01` - Por período

## Comandos Disponibles

```bash
# Migración completa
php artisan system:migrate-to-vouchers --dry-run
php artisan system:migrate-to-vouchers --force

# Migración individual
php artisan collections:migrate-to-vouchers --dry-run

# Generación de vouchers
php artisan vouchers:generate --period=2024-01
```

## Verificación Post-Migración

### 1. Verificar Datos Migrados

```bash
# Contar vouchers creados
php artisan tinker
>>> App\Models\Voucher::whereHas('booklet.voucherType', fn($q) => $q->where('code', 'COB'))->count()

# Verificar items
>>> App\Models\VoucherItem::whereHas('voucher.booklet.voucherType', fn($q) => $q->where('code', 'COB'))->count()

# Verificar totales
>>> App\Models\Collection::sum('total_amount')
>>> App\Models\Voucher::whereHas('booklet.voucherType', fn($q) => $q->where('code', 'COB'))->sum('total')
```

### 2. Verificar Funcionalidad

- Generar nuevas cobranzas
- Marcar como pagadas
- Cancelar vouchers
- Verificar items y totales

### 3. Actualizar Frontend

- Cambiar endpoints de `/api/collections` a `/api/vouchers`
- Agregar filtro `voucher_type=COB`
- Actualizar componentes para usar nueva estructura

## Rollback

Si necesitas revertir la migración:

1. **Restaurar tablas**:
   ```bash
   php artisan migrate:rollback
   ```

2. **Eliminar vouchers migrados**:
   ```sql
   DELETE FROM voucher_items WHERE voucher_id IN (
     SELECT id FROM vouchers WHERE meta->>'migrated_from_collection_id' IS NOT NULL
   );
   DELETE FROM vouchers WHERE meta->>'migrated_from_collection_id' IS NOT NULL;
   ```

## Beneficios de la Migración

1. **Arquitectura Unificada**: Todos los comprobantes usan la misma estructura
2. **Flexibilidad**: Fácil agregar nuevos tipos de vouchers
3. **Consistencia**: Misma lógica para todos los comprobantes
4. **Mantenibilidad**: Menos código duplicado
5. **Escalabilidad**: Fácil extender funcionalidad

## Notas Importantes

- La migración es **irreversible** una vez ejecutada
- Siempre hacer backup antes de migrar
- Probar en ambiente de desarrollo primero
- Verificar que no hay transacciones en curso
- Actualizar documentación y tests después de migrar 
