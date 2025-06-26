## Lógica Funcional: Contratos de Alquiler

### Objetivo
Representar formalmente el acuerdo de alquiler entre inquilinos y propietarios, incluyendo todas las condiciones operativas y económicas necesarias para la gestión mensual, cobros, liquidaciones, penalidades y archivos legales.

---

### Generación del contrato

Un contrato puede ser creado de dos formas:
- Desde una solicitud de alquiler aprobada (`rental_application`)
- De forma directa, sin solicitud previa

---

### Datos requeridos

| Campo                  | Descripción                                                                 |
|------------------------|------------------------------------------------------------------------------|
| `property_id`          | Inmueble alquilado                                                           |
| `rental_application_id`| (opcional) solicitud de alquiler origen                                       |
| `start_date`, `end_date` | Vigencia contractual                                                        |
| `monthly_amount`, `currency` | Monto mensual y su moneda                                            |
| `payment_day`          | Día de vencimiento mensual del pago                                          |
| `commission_type`, `commission_amount`, `commission_payer`, `is_one_time` | Condiciones de comisión |
| `insurance_required`, `insurance_amount`, `insurance_company_name` | Datos del seguro                 |
| `deposit_amount`, `deposit_currency`, `deposit_type`, `deposit_holder` | Depósito de garantía     |
| `owner_share_percentage` | Porcentaje de lo cobrado a transferir al propietario                       |
| `has_penalty`, `penalty_type`, `penalty_value`, `penalty_grace_days` | Punitorios por mora           |
| `prorate_first_month`, `prorate_last_month` | Flags para prorratear primeros o últimos días         |
| `status`, `notes`      | Estado actual del contrato y notas internas                                  |

---

### Personas asociadas (`contract_clients`)

Un contrato puede tener asociados clientes en distintos roles:
- `tenant` (inquilino)
- `guarantor` (garante)
- `owner` (propietario)

#### Atributos
- `ownership_percentage`: Solo para `owner`, indica participación en la propiedad
- `is_primary`: Indica si el cliente es el responsable principal del contrato

#### Regla funcional
- Debe haber un (y solo un) inquilino con `is_primary = true` por contrato
- Los garantes nunca tienen `is_primary`
- Los propietarios pueden tener uno marcado como principal, si aplica

---

### Servicios asociados (`contract_services`)

El contrato puede tener servicios (agua, gas, luz, etc) distintos a los de la propiedad.

| Campo | Descripción |
|-------|-------------|
| `service_type` | Tipo de servicio (enum: electricity, gas, etc) |
| `account_number`, `provider_name`, `owner_name` | Datos administrativos |
| `is_active`, `has_debt`, `debt_amount` | Estado actual del servicio |
| `paid_by` | Quién se hace cargo del pago (inquilino, propietario, etc) |
| `notes` | Observaciones internas |

---

### Ajustes contractuales (`contract_adjustments`)

Permiten actualizar el monto mensual del contrato a lo largo del tiempo. Se aplica el ajuste vigente según su `effective_date`.

Tipos:
- `fixed`: nuevo monto fijo
- `percentage`: aumento porcentual
- `index`: ajuste según índice (requiere `index_type_id`)
- `negotiated`: ajuste manual acordado

Solo un ajuste aplica por mes (el más reciente con `effective_date <= mes de cobranza`).

---

### Estados del contrato (`status`)

| Estado      | Descripción |
|-------------|-------------|
| `draft`     | Contrato en edición, no operativo |
| `active`    | Contrato vigente y firmado |
| `completed` | Contrato finalizado naturalmente |
| `terminated`| Contrato rescindido anticipadamente |

---

### Archivos asociados

Se pueden adjuntar archivos como:
- Contrato firmado
- Anexos
- Comprobantes de depósito
- Seguro de caucion u otros

Estos se manejan mediante la relación polimórfica con `attachments`, usando `attachment_categories`.

---

### Observaciones

- Las condiciones del contrato (alquiler, comisión, seguro) se "congelan" al momento de su creación, incluso si la solicitud u oferta cambian luego.
- El contrato es la fuente de verdad definitiva para la generación de cobranzas y liquidaciones.

