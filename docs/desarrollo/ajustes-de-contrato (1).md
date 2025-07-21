# Documentación Técnica - Ajustes de Contrato

Este documento describe los componentes técnicos involucrados en el módulo de ajustes de contrato, tanto para la asignación de índices como para la aplicación efectiva de los ajustes.

---

## 1. Modelos

### `ContractAdjustment`
- Campos clave:
  - `contract_id`
  - `type` (enum: index, percentage, fixed, negotiated)
  - `index_type_id`
  - `effective_date`
  - `value`
  - `applied_amount`
  - `applied_at`
- Relaciones:
  - `contract()`
  - `indexType()`
  - `attachments()`

### `IndexValue`
- Campos:
  - `index_type_id`
  - `month` (formato YYYY-MM)
  - `percentage`
- Relaciones:
  - `indexType()`

---

## 2. Servicios

### `ContractAdjustmentService`
- `apply(ContractAdjustment $adjustment)`
  - Calcula el nuevo alquiler (`applied_amount`)
  - Marca `applied_at`
  - Opcionalmente actualiza `contract->monthly_amount`
  - Valida:
    - Que no exista un ajuste aplicado en el mismo mes
    - Que no exista un voucher emitido tipo COB en ese mes

- `assignIndexValue(ContractAdjustment $adjustment)`
  - Busca `IndexValue` según `index_type_id` y `effective_date`
  - Asigna el `percentage` al campo `value`

### `AssignIndexValuesToAdjustmentsService`
- Método `assign()`: recorre todos los ajustes de tipo `index` con `value = null` y `applied_at = null` y les asigna el porcentaje del índice si existe.

---

## 3. Comando Artisan

### `adjustments:assign-index-values`
- Ejecuta el servicio `AssignIndexValuesToAdjustmentsService`
- Se programa en `Kernel.php` para correr el día 1 de cada mes a las 02:00 AM

---

## 4. Validaciones implementadas

- No se puede aplicar un ajuste:
  - Si ya tiene `applied_at`
  - Si ya existe otro ajuste aplicado en el mismo mes/año
  - Si existe un `Voucher` emitido (`status = issued`) tipo “COB” para ese contrato y mes

- No se puede editar ni eliminar un ajuste ya aplicado

---

## 5. Endpoints

- `POST /contracts/{contract}/adjustments/{adjustment}/apply`
  - Aplica el ajuste
- `PUT /contracts/{contract}/adjustments/{adjustment}/update-value`
  - Permite editar `value` y `notes` solo si el ajuste no fue aplicado
- `DELETE /contracts/{contract}/adjustments/{adjustment}`
  - Bloqueado si `applied_at` no es null

---

## 6. Recursos API

### `ContractAdjustmentResource`
- Devuelve: id, type, effective_date, value, applied_amount, applied_at, notes, index_type_id

### `IndexValueResource`
- Devuelve: id, index_type_id, month, percentage

---

## 7. Observaciones

- Los ajustes aplicados se usan al calcular el valor del alquiler vigente.
- El campo `applied_amount` garantiza trazabilidad aún si cambian las reglas de redondeo o el cálculo.
