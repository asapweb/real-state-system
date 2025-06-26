## Lógica Funcional: Generación de Cobranzas Mensuales

### Objetivo
Generar, por cada contrato activo, los registros mensuales que representen las obligaciones de pago del inquilino, según las condiciones pactadas contractualmente.

---

### Disparadores de generación

- **Automático:** Por job programado cada mes.
- **Manual:** Desde la interfaz de administración del contrato o acción masiva.

---

### Requisitos para generar una cobranza

- El contrato debe estar en estado `active`.
- El mes a generar debe estar entre `start_date` y `end_date` del contrato.
- No debe existir una cobranza previa para ese mes.
- El contrato debe tener definidos:
  - `monthly_amount`
  - `payment_day`
  - `currency`

---

### Datos de entrada (desde `contracts` y relaciones)

| Campo | Descripción |
|-------|-------------|
| `monthly_amount` | Monto mensual base del contrato |
| `payment_day` | Día del mes en que vence la cobranza |
| `currency` | Moneda de la operación |
| `start_date` / `end_date` | Vigencia del contrato |
| `insurance_required`, `insurance_amount` | Datos de seguro |
| `has_penalty`, `penalty_value`, `penalty_grace_days` | Punitorios |
| `commission_type`, `commission_amount`, `commission_payer`, `is_one_time` | Comisiones |
| `contract_adjustments` | Posibles ajustes activos |
| `prorate_first_month`, `prorate_last_month` | Flags para prorrateo |

---

### Campos calculados en `collections`

| Campo | Fuente / Cálculo |
|-------|------------------|
| `due_date` | Primer día del mes + `payment_day` |
| `month`, `year` | Mes y año correspondiente |
| `base_amount` | `monthly_amount` o valor ajustado |
| `adjusted_amount` | Si hay ajuste (`contract_adjustments`) |
| `insurance_amount` | Desde contrato si corresponde |
| `commission_amount` | Si `commission_payer == 'tenant'` |
| `prorated_amount` | Cálculo proporcional si aplica |
| `penalty_amount` | Solo si hay mora y punitorios habilitados |
| `total_amount` | Suma de todos los conceptos |
| `status` | `pending`, `paid`, `overdue` según pagos |

---

### Ajustes contractuales

- Se toma el último `contract_adjustment` con `effective_date <= primer día del mes`
- Tipos:
  - `fixed`: reemplaza el monto mensual
  - `percentage`: se aplica sobre el monto mensual
  - `negotiated` / `index`: tratados como casos especiales

---

### Comisiones

- Si `commission_payer === 'tenant'`:
  - Se incluye en la cobranza mensual
  - Si `is_one_time === true`: solo en la primera cobranza
  - Si `is_one_time === false`: se agrega todos los meses

---

### Prorrateo

- Si `prorate_first_month === true`:
  - Se calcula el monto proporcional según día de inicio (`start_date`)
- Si `prorate_last_month === true`:
  - Se calcula proporcional desde el 1 hasta `end_date`
- Si ninguno está activo: todos los meses se cobran completos

---

### Punitorios

- Si hay mora y `has_penalty === true`:
  - Se aplica a partir de `due_date + penalty_grace_days`
  - Puede ser:
    - Fijo (`penalty_type = fixed`)
    - Porcentaje sobre deuda (`penalty_type = percentage`)
- El sistema debe calcular la mora automáticamente en función de la fecha actual y actualizar el `penalty_amount` y el `status` de la cobranza según corresponda.

---

### Cobranzas manuales

El sistema permite generar cobranzas manuales (tipo `manual`) asociadas a un contrato, por montos no previstos en la lógica mensual. Estas cobranzas:

- Se crean manualmente desde la vista del contrato
- Requieren: descripción, monto, vencimiento y moneda
- No tienen prorrateo, ajustes, ni punitorios automáticos
- Se vinculan a pagos como cualquier otra cobranza

---

### Controles funcionales sobre cobranzas

#### Al crear:
- Validar que no exista una cobranza previa del mismo tipo, mes y año.
- Validar que el contrato esté en estado `active`.
- Validar que se hayan completado los campos obligatorios.

#### Al modificar:
- No permitir cambiar el contrato asociado ni el tipo (`monthly` / `manual`).
- Solo permitir edición si la cobranza no está saldada.

#### Al eliminar:
- Solo permitir si no hay pagos vinculados.
- Registrar el motivo de eliminación o dejar traza en log.

---

### Ejemplo 1: Contrato con ajuste y seguro

- `monthly_amount = 120000`, ajuste 10% desde junio
- `insurance_amount = 5000`
- Junio:
  - Ajustado: $132.000
  - Seguro: $5.000
  - Total: $137.000

---

### Ejemplo 2: Contrato con prorrateo inicial

- `start_date = 15/03/2025`
- `prorate_first_month = true`
- Marzo tiene 31 días
- Prorrateo: 17 días de 31
- Monto proporcional: `(monthly_amount / 31) * 17`

---

### Estados posibles de la cobranza

- `pending`: generada, sin pagos registrados
- `partial`: tiene pagos incompletos
- `paid`: saldada completamente
- `overdue`: vencida según fecha y punitorio aplicado

---

### Observaciones

- El sistema podrá incluir una vista resumen mensual para control de cobranzas generadas y pendientes.
- Se podrán generar cobranzas masivas desde un panel mensual.
- Las cobranzas estarán vinculadas a los `payments` recibidos, ya sea de forma manual o automática.

