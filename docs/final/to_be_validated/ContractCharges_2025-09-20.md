# Contract Charges — Functional & API Guide

Gestión de **cargos de contrato** (rentas, ajustes, recuperos, bonificaciones e informativos) con **monto siempre positivo** y **semántica por tipo**.

## Table of Contents
- [Scope](#scope)
- [Domain model](#domain-model)
- [Charge types (catalog)](#charge-types-catalog)
- [Business rules](#business-rules)
- [Data model](#data-model)
- [API](#api)
- [Rent generation flow](#rent-generation-flow)
- [UI reference](#ui-reference)
- [Validation matrix](#validation-matrix)
- [Examples](#examples)
- [Migrations & seeder](#migrations--seeder)
- [Testing checklist](#testing-checklist)
- [Roadmap](#roadmap)
- [Glossary](#glossary)

---

## Scope

- Maneja cargos que impactan **liquidaciones** de **Inquilino (Tenant)** y **Propietario (Owner)**.
- El **signo** (sumar/restar/mostrar/ocultar) **no** lo define el monto, sino el **tipo** de cargo.
- Los cargos **no mueven caja**: la caja se mueve con **vouchers** (cobranza/pago/liquidación).
- Gastos propios de la inmobiliaria (publicidad, cartelería, etc.) **no** son contract charges.

---

## Domain model

**ContractCharge**
- `contract_id`, `charge_type_id`, `amount (>0)`, `currency`
- `effective_date` (periodificación), `due_date`
- (opt) `service_period_start/end`, `invoice_date`
- (opt) `counterparty_contract_client_id` (si el tipo lo requiere)
- Vínculos: `voucher_id` (COB), `liquidation_voucher_id` (LIQ)
- `description`

**ChargeType**
- Impactos por lado:
  - `tenant_impact` / `owner_impact` ∈ **{ `add`, `subtract`, `info`, `hidden` }**
- Políticas:
  - `requires_service_period` (bool)
  - `requires_counterparty` ∈ { `tenant`, `owner`, `null` }
  - `currency_policy` ∈ { `CONTRACT_CURRENCY`, `CHARGE_CURRENCY`, `CONVERT_AT_TODAY` }

**Counterparty (Opción A – MVP)**
- Cuando el tipo lo requiere, se selecciona **una** persona del contrato:
  - `tenant`: por defecto el inquilino **principal**, editable.
  - `owner`: opcional; si no se elige, LIQ reparte por % ownership.

---

## Charge types (catalog)

| Code                  | Tenant | Owner   | Service Period | Requires Counterparty | Currency |
|----------------------|--------|---------|----------------|-----------------------|----------|
| RENT                 | add    | add     | no             | null                  | CONTRACT |
| ADJ_DIFF_DEBIT       | add    | add     | yes            | null                  | CONTRACT |
| ADJ_DIFF_CREDIT      | subtract | subtract | yes          | null                  | CONTRACT |
| RECUP_TENANT_AGENCY  | add    | hidden  | no             | tenant                | CONTRACT |
| RECUP_OWNER_AGENCY   | hidden | subtract| no             | owner \| null         | CONTRACT |
| RECUP_TENANT_OWNER   | add    | add     | no             | null                  | CONTRACT |
| RECUP_OWNER_TENANT   | subtract | subtract | no          | null                  | CONTRACT |
| BONIFICATION         | subtract | subtract | no          | null                  | CONTRACT |
| SELF_PAID_INFO       | info   | info    | yes            | null                  | CONTRACT |

> **Regla de inclusión:** un documento (COB/LIQ) suma/resta el cargo si el impact es `add`/`subtract`. `info` solo muestra; `hidden` no aparece.

---

## Business rules

- **Amount** siempre **positivo**; el signo efectivo lo aporta el **tipo**.
- **Unicidad RENT**: 1 por `(contract_id, charge_type_id, currency, effective_date)` (recomendado índice unique).
- **Moneda**: si `CONTRACT_CURRENCY`, forzar/validar contra moneda del contrato.
- **Períodos de servicio**: si el tipo lo requiere, `start/end` obligatorios (end ≥ start).
- **Vouchers**:
  - Cargo con **voucher NO–DRAFT** ⇒ no se edita (correcciones por NC/ajuste).
  - Cargo **sin voucher** o con **DRAFT** ⇒ puede actualizarse.

---

## Data model

### `contract_charges` (resumen)
- `contract_id`, `charge_type_id`, `service_type_id`(nullable)
- `counterparty_contract_client_id`(nullable)
- `amount` (unsigned), `currency`
- `effective_date`, `due_date`(nullable)
- `service_period_start/end`(nullable), `invoice_date`(nullable)
- `voucher_id`(nullable), `liquidation_voucher_id`(nullable)
- `settled_at`(nullable), `description`, timestamps

### `charge_types` (resumen)
- `code` (unique), `name`, `is_active`
- `tenant_impact`, `owner_impact` ∈ **add|subtract|info|hidden**
- `requires_service_period` (bool)
- `requires_counterparty` ∈ tenant|owner|null
- `currency_policy` ∈ CONTRACT_CURRENCY|CHARGE_CURRENCY|CONVERT_AT_TODAY

---

## API

### List — `GET /contract-charges`
Query params:
- `contract_id` (recomendado)
- `type_code` (opcional)
- `per_page` (default 25)

**Response (paginated)**
```json
{
  "data": [
    {
      "id": 1,
      "contract_id": 1001,
      "charge_type": {
        "id": 9,
        "code": "RENT",
        "name": "Monthly Rent",
        "tenant_impact": "add",
        "owner_impact": "add",
        "requires_service_period": false,
        "requires_counterparty": null,
        "currency_policy": "CONTRACT_CURRENCY",
        "is_active": true
      },
      "service_type_id": null,
      "counterparty": null,
      "amount": 120000.0,
      "currency": "ARS",
      "effective_date": "2025-08-01",
      "due_date": "2025-08-10",
      "service_period_start": null,
      "service_period_end": null,
      "invoice_date": null,
      "voucher_id": 5012,
      "liquidation_voucher_id": 7021,
      "settled_at": "2025-08-15 12:00:00",
      "description": "Monthly Rent",
      "tenant": { "impact": "add", "include": true, "sign": 1, "signed_amount": 120000.0 },
      "owner":  { "impact": "add", "include": true, "sign": 1, "signed_amount": 120000.0 },
      "created_at": "2025-07-30 09:10:00",
      "updated_at": "2025-07-30 09:10:00"
    }
  ],
  "links": { "...": "..." },
  "meta":  { "...": "..." }
}
```

### Create — `POST /contract-charges`
```json
{
  "contract_id": 1001,
  "charge_type_id": 9,
  "amount": 120000,
  "currency": "ARS",
  "effective_date": "2025-08-01",
  "due_date": "2025-08-10",
  "description": "Monthly Rent"
}
```

**Rules**
- `amount >= 0.01` (se normaliza abs).
- Si el tipo `requires_service_period`, enviar `service_period_start/end`.
- Si `requires_counterparty`, enviar `counterparty_contract_client_id` del mismo contrato y rol correcto.

### Show — `GET /contract-charges/{id}`  
### Update — `PUT /contract-charges/{id}`  
### Delete — `DELETE /contract-charges/{id}` *(dev; en prod puede ser soft delete)*

### Charge types — `GET /charge-types`
Lista activa para poblar selects y reglas del form.

---

## Rent generation flow

- Servicio: `RentGenerationService::ensureRentCharge(contract, period)`
- Crea/actualiza **RENT** (1 por contrato/mes/moneda):
  - Calcula `base` y prorrateo.
  - Si no existe: **crea**.
  - Si existe y **voucher** NO–DRAFT: **no toca** (usar NC/ajustes).
  - Si existe sin voucher o DRAFT: **actualiza** monto/vencimiento.
- Recomendado: unique en `(contract_id, charge_type_id, currency, effective_date)` y `lockForUpdate()`.

---

## UI reference

- **List (by contract)**: tabla con columnas clave + filtros (type, date range, impacts, text).
- **Form (drawer)**:
  - Campos dinámicos según `charge_type`:
    - `requires_service_period` ⇒ fechas obligatorias.
    - `requires_counterparty` ⇒ selector filtrado (tenant/owner).
  - Normalizar `amount` positivo.
  - Preview badges: `tenant_impact`, `owner_impact`.

---

## Validation matrix

- Amount: `>= 0.01`
- Dates: `effective_date` requerido; `due_date >= effective_date` si existe.
- Service period: si requerido ⇒ `start/end` y `end >= start`.
- Counterparty: si requerido ⇒ id válido, mismo contrato, rol correcto.
- Currency: si `CONTRACT_CURRENCY` ⇒ debe coincidir con el contrato (o convertir).

---

## Examples

### Cálculo de totales (pseudocódigo)

```php
$tenantTotal = 0;
$ownerTotal  = 0;

foreach ($charges as $c) {
  $t = $c->chargeType;

  if (in_array($t->tenant_impact->value ?? $t->tenant_impact, ['add','subtract'])) {
    $tenantTotal += $t->signedAmountForTenant((float)$c->amount);
  }

  if (in_array($t->owner_impact->value ?? $t->owner_impact, ['add','subtract'])) {
    $ownerTotal += $t->signedAmountForOwner((float)$c->amount);
  }
}
```

### SELF_PAID_INFO
- Se muestra **informativo** en ambos lados, no altera totales.
- Útil para transparencia cuando el inquilino paga directo al proveedor.

---

## Migrations & seeder

- `contract_charges` y `charge_types` ya preparados en este proyecto (migraciones incluidas).
- **Seeder**: `ChargeTypesSeeder` con los 9 tipos y sus impactos.
- Comandos:
```bash
php artisan migrate:fresh --seed
php artisan optimize:clear
```

> Recomendado: agregar unique index para RENT:
```php
Schema::table('contract_charges', function (Blueprint $table) {
  $table->unique(['contract_id','charge_type_id','currency','effective_date'], 'uq_cc_period');
});
```

---

## Testing checklist

- [ ] Crear RENT idempotente por mes.
- [ ] RECUP_TENANT_AGENCY exige **tenant counterparty** si hay múltiples.
- [ ] SELF_PAID_INFO no altera totales (solo info).
- [ ] ADJ_DIFF_* requiere `service_period_*` y aplica signos correctos.
- [ ] Update de RENT con voucher **DRAFT** actualiza; con **NO–DRAFT** no modifica.
- [ ] Unicidad: no duplica RENT por carrera (lock + unique).

---

## Roadmap

- **Allocations (opción B)**: dividir un cargo entre varias personas (equal/percent/amount).
- **Overrides puntuales** de impacto por cargo (apagado por defecto).
- Multi–moneda avanzada (políticas de conversión y cierre).

---

## Glossary

- **COB**: Cobranza al inquilino (voucher del lado tenant).
- **LIQ**: Liquidación al propietario (voucher del lado owner).
- **Impact**: `add` (suma), `subtract` (resta), `info` (solo visibilidad), `hidden` (oculto).
- **Counterparty**: persona específica del contrato asociada al cargo (tenant u owner).
