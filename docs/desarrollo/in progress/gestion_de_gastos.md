# Gestión y Validación de Contract Expenses

## Objetivo
Establecer el flujo completo para la gestión, validación y control de los **gastos asociados a contratos de alquiler** (`contract_expenses`), considerando su impacto en:
- Facturación a inquilinos (FAC/N/D/NC).
- Liquidaciones a propietarios (LIQ).
- Validación de pagos (tenant → tenant).

---

## Entidades y Campos Clave

### Tabla: `contract_expenses`
| Campo                   | Descripción                                                                 |
|------------------------- |-----------------------------------------------------------------------------|
| `contract_id`           | Contrato al que está asociado el gasto.                                     |
| `service_type_id`       | Tipo de servicio (agua, luz, gas, expensas, etc.).                         |
| `amount`                | Monto del gasto.                                                           |
| `currency`              | Moneda (ej. ARS, USD).                                                     |
| `effective_date`        | Fecha de aplicación contable: período al que corresponde el gasto.         |
| `due_date`              | Fecha de vencimiento según proveedor.                                      |
| `paid_by`               | Quién pagó el gasto: `tenant`, `owner`, `agency`.                         |
| `responsible_party`     | A quién corresponde el gasto: `tenant`, `owner`.                           |
| `is_paid`               | Flag si el gasto está marcado como pagado (por comprobante validado).      |
| `paid_at`               | Fecha de pago validada en el sistema.                                      |
| `voucher_id`            | FAC/N/D en el que fue imputado el gasto.                                   |
| `generated_credit_note_id` | Nota de crédito generada (tenant→owner).                                 |
| `liquidation_voucher_id`| LIQ en el que fue liquidado al propietario.                                |
| `status`                | Estado actual del gasto. (ver más abajo).                                  |
| `description`           | Notas adicionales.                                                         |

---

## Estados de `ContractExpense`

- **`pending`**: Registrado sin validación o comprobante.
- **`validated`**: Validado con comprobante de pago (tenant → tenant).
- **`billed`**: Asociado a factura o nota de débito (FAC/N/D).
- **`credited`**: Compensado con nota de crédito (NC).
- **`liquidated`**: Incluido en liquidación al propietario (LIQ).
- **`canceled`**: Anulado, sin impacto posterior.

---

## Flujo General de Gestión

### 1️⃣ **Registro del gasto**
- Se carga manualmente desde el frontend.
- Campos obligatorios: `contract_id`, `service_type_id`, `amount`, `currency`, `effective_date`, `paid_by`, `responsible_party`.

---

### 2️⃣ **Adjuntar comprobante de pago (tenant→tenant)**
- Para gastos **pagados por el inquilino y que le corresponden al mismo inquilino** (`tenant → tenant`):
  - Se adjunta comprobante.
  - El usuario valida el pago (marca `is_paid = true`, setea `paid_at`).
  - Estado cambia a `validated`.

---

### 3️⃣ **Bloqueos de edición**
- **Si estado = validated**:
  - No se pueden modificar `paid_by` ni `responsible_party`.
- **Si estado = billed, credited, liquidated**:
  - `is_locked = true`: El gasto completo es ineditable.

---

### 4️⃣ **Facturación al inquilino (FAC/N/D/NC)**
- Gastos **tenant → owner**:
  - Se incluyen en la FAC/N/D del inquilino.
  - Luego se liquidan al propietario (LIQ).
- Gastos **tenant → tenant**:
  - Solo control interno. No generan FAC/N/D.
- Gastos **agency → tenant**:
  - Se incluyen en FAC/N/D al inquilino (para recuperar el importe).
- Gastos **tenant → owner (con pago previo)**:
  - Generan NC automática al inquilino (compensación).

---

### 5️⃣ **Liquidación al propietario (LIQ)**
- Gastos **owner → owner**:
  - Se descuentan en LIQ.
- Gastos **agency → owner**:
  - Se liquidan en LIQ.
- Gastos **tenant → owner**:
  - Si fueron facturados al inquilino y cobrados, luego se incluyen en LIQ.

---

### 6️⃣ **Campos derivados**
- `is_locked`: true si status ∈ {`billed`, `credited`, `liquidated`}.
- `effective_date`: usado para agrupar gastos por período contable.
- `voucher_id` / `generated_credit_note_id` / `liquidation_voucher_id`: optimizan consultas sin joins extensos.

---

## Validaciones API
- **Store**:
  - Requiere todos los campos clave.
- **Update**:
  - Si `is_locked`, rechaza edición (422).
  - Si `validated`, permite solo campos descriptivos (no `paid_by` ni `responsible_party`).
- **Registrar pago**:
  - Solo válido para `tenant → tenant`.
  - Cambia `status = validated`.

---

## Plan Técnico

1️⃣ **Backend:**
- ✅ Migración con campos `generated_credit_note_id` y `liquidation_voucher_id`.
- ✅ Modelo `ContractExpense` con `is_locked` y scopes.
- ✅ Enum `ContractExpenseStatus`.
- ✅ Requests (`Store` y `Update`) con reglas condicionales.
- ✅ Endpoint para registrar pago (tenant→tenant) + adjuntos.

2️⃣ **Frontend:**
- ✅ `ContractExpenseManager.vue` reutilizable.
- ✅ `ContractExpenseTable.vue` con modal de registrar pago y adjuntos.
- ✅ `ContractExpenseForm.vue` con `ContractAutocomplete` si no hay contrato fijo.
- ⏳ Mostrar campos bloqueados si `validated`.
- ⏳ Deshabilitar edición completa si `is_locked`.

3️⃣ **Próximos pasos:**
- Facturación inquilinos (FAC/N/D/NC).
- Liquidación propietarios (LIQ).
- Generación automática de NC para tenant→owner.

---

