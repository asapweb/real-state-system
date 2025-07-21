# Documentación Técnica - Módulo de Índices Oficiales

Este documento detalla los componentes técnicos involucrados en el manejo de índices oficiales que alimentan los ajustes contractuales automáticos por tipo `index`.

---

## 1. Modelos

### 1.1. `IndexType`
- Tabla: `index_types`
- Campos:
  - `id`
  - `name`
  - `description` (nullable)
- Relaciones:
  - `values()` → hasMany `IndexValue`

### 1.2. `IndexValue`
- Tabla: `index_values`
- Campos:
  - `id`
  - `index_type_id` → foreign key
  - `month` (formato 'YYYY-MM')
  - `percentage` (decimal: 5,2)
  - Timestamps
- Restricciones:
  - Clave única en (`index_type_id`, `month`)
- Relaciones:
  - `indexType()` → belongsTo `IndexType`

---

## 2. Servicios

### `ContractAdjustmentService`
- Método `assignIndexValue(ContractAdjustment $adjustment): bool`
  - Obtiene el mes del ajuste
  - Busca `IndexValue` coincidente
  - Si lo encuentra, lo asigna al campo `value`
  - Retorna `true` si se asignó, `false` si no

### `AssignIndexValuesToAdjustmentsService`
- Método `assign(): int`
  - Recorre todos los `ContractAdjustment` tipo `index` con `value = null` y `applied_at = null`
  - Llama a `ContractAdjustmentService::assignIndexValue()` por cada uno
  - Devuelve el total de ajustes que fueron actualizados

---

## 3. Comando Artisan

### `adjustments:assign-index-values`
- Ejecuta `AssignIndexValuesToAdjustmentsService::assign()`
- Puede ejecutarse manualmente o mediante cron
- Configurado para correr el día 1 de cada mes a las 02:00 AM en `App\Console\Kernel`

---

## 4. Validaciones

- No se permite duplicar valores por índice y mes (`unique` en `index_type_id` + `month`)
- El campo `percentage` debe estar entre 0 y 100, típicamente validado al momento de carga

---

## 5. Recursos API

### `IndexValueResource`
- Devuelve:
  - `id`
  - `index_type_id`
  - `month`
  - `percentage`

---

## 6. Observaciones

- Los valores de índice no aplican ajustes directamente. Solo completan el campo `value` del ajuste.
- La aplicación de un ajuste requiere acción explícita (manual o mediante flujo validado).

