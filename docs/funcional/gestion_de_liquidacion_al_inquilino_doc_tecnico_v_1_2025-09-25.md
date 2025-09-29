# LQI – Documento Técnico (especialización de Vouchers) · v1

> **Propósito**: describir cómo implementar **Liquidación al Inquilino (LQI)** reutilizando el **módulo de Vouchers** existente. Define modelo de datos, servicios, algoritmo de sync, validaciones, endpoints API, UI y tests, alineado con el **Documento Funcional LQI v1**.

---

## 0) Principios y convenciones del proyecto

- **Stack**: Laravel 11 (API), Vue 3 + Vuetify (front).
- **Períodos**: YYYY-MM. Comparaciones por rango: >= primer día del mes y < primer día del mes siguiente.
- **Rutas**: manuales por entidad; `apiResource` solo para CRUDs simples.
- **Respuestas**: `index()` y `show()` con `ApiResource`; `store()` → **201** con recurso; `update()` → **200** con recurso. Nunca 204.
- **Tablas con montos**: siempre con `currency` explícita.
- **Vouchers**: `draft|issued|canceled` + reapertura controlada.

---

## 1) Modelo de datos (reutilización + extensiones mínimas)

### 1.1 Vouchers (reutilizado)

Se reutiliza `vouchers` e `voucher_items`.

- **voucher\_types** debe incluir **LQI** (Liquidación al Inquilino).
  - `code`: `LQI`
  - `name`: `Liquidación Inquilino`
  - `affects_account`: **true** (devenga deuda)
  - `credit`: **false** (no es NC)

### 1.2 Campos relevantes existentes en `vouchers`

- `contract_id` (FK)
- `voucher_type_id` (FK → LQI)
- `period` (**date**, convención: primer día del mes del período, ej. `2025-09-01`)
- `currency` (STRING / ENUM)
- `status` (`draft|issued|canceled`)
- `issue_date?`, `due_date?`
- Totales: `items_count`, `subtotal`, `total`
- Auditoría: `created_by`, `updated_by`, `issued_by?`, `reopened_by?`, `canceled_by?`, motivos/fechas

### 1.3 Índices y unicidad sugeridos

- **Unicidad LQI activa por contrato/período/moneda**:
  - `UNIQUE KEY ux_lqi_active (contract_id, period, currency) WHERE voucher_type_id = LQI AND status IN ('draft','issued')`
  - Implementable con **índice parcial** (DB que soporte) o **constraint lógica** en servicio.
- Índices: `(contract_id, period)`, `(voucher_type_id, period)`, `(status)`, `(currency)`.

### 1.4 `voucher_items` (reutilizado)

- **Columnas relevantes** (según tu schema + extensiones mínimas):
  - `voucher_id` (FK)
  - `contract_charge_id` (FK → `contract_charges`, **nuevo**)
  - `type`, `description`, `quantity`, `unit_price`, `subtotal`, `vat_amount`, `tax_rate_id?`, `subtotal_with_vat`
  - `impact` **ENUM('add','subtract') NOT NULL DEFAULT 'add'** (**nuevo**)
  - `meta` (JSON) — **no se usa** para fechas/impact en LQI (queda disponible para otros casos del sistema)
- **Unicidad/idempotencia**: `UNIQUE (voucher_id, contract_charge_id)`

### 1.5 `contract_charges` (reutilizado)

- Campos que usa LQI (según tu migración):
  - `contract_id`, `charge_type_id`, `service_type_id?`, `counterparty_contract_client_id?`
  - `amount`, `currency`
  - `effective_date`, `due_date?`, `service_period_start?`, `service_period_end?`, `invoice_date?`
  - **Vinculación a liquidaciones**: `tenant_liquidation_voucher_id?`, `owner_liquidation_voucher_id?`
  - **Asentado (tenant)**: `tenant_liquidation_settled_at?`
  - Cancelación: `canceled_at?`, `canceled_by?`, `canceled_reason?`, `is_canceled`
  - Auditoría de timestamps
- Índices (como en tu migración): `idx_cc_contract_effective`, `idx_cc_contract_currency`, `idx_cc_service_period`, `idx_cc_type`, `idx_cc_tenant_liq`, `idx_cc_owner_liq`, `idx_cc_contract_type_period`.
- Campos que usa LQI: `contract_id`, `charge_type_id`, `amount`, `currency`, `effective_date`, `due_date?`, `impact(tenant)`, `is_canceled`, `lqi_voucher_id?`, `lqi_settled_at?`, `source`, `service_period_start/end?`, `invoice_date?`, `period` (CHAR(7)).
- Índices recomendados: `(contract_id, period, currency, is_canceled)`, `(lqi_voucher_id)`, `(lqi_settled_at)`.

> Nota: En v1 **no** se agregan columnas nuevas; todo está contemplado por el modelo de `contract_charges` acordado.

---

## 2) Elegibilidad de cargos (query canonical)

Dado `contract_id`, `period (YYYY-MM)`, `currency`:

```php
$from = Carbon::parse($period.'-01')->startOfMonth();
$to   = (clone $from)->addMonth();

$eligible = ContractCharge::query()
    ->where('contract_id', $contractId)
    ->whereDate('effective_date', '>=', $from)
    ->whereDate('effective_date', '<',  $to)
    ->where('currency', $currency)
    ->where('is_canceled', false)
    ->whereNull('tenant_liquidation_settled_at')
    ->whereHas('chargeType', fn($q) => $q->whereIn('impact_tenant', ['add','subtract']))
    ;
```

---

## 3) Servicio: `LqiBuilderService`

### 3.1 Responsabilidades

- **Sync** (crear/actualizar DRAFT): reflejar íntegramente los cargos elegibles en `voucher_items` de un LQI `draft`.
- **Idempotencia**: sin duplicar `voucher` ni `items`.
- **Cálculo**: recomputar `items_count`, `subtotal`, `total`.
- **Asociaciones**: no marca asentado (eso ocurre al **emitir**).

### 3.2 Firma

- `sync(Contract $contract, string $period, string $currency): Voucher`

### 3.3 Algoritmo de sync

1. **Encontrar o crear** `voucher draft` para `(contract, period, currency, type=LQI)`.
2. Obtener `eligible charges` (query §2).
3. Cargar `existingItems` indexados por **`contract_charge_id`**.
4. **Agregar** items faltantes (creando `voucher_items` con `contract_charge_id` y `impact`='add|subtract'); **remover** items cuyos cargos ya no sean elegibles.
5. Recalcular totales.
6. Guardar y devolver el `voucher`.

### 3.4 Reglas de negocio

- Si no hay cargos elegibles, el draft puede quedar vacío; la **emisión** lo bloqueará.
- Si existe **issued** y **no** tiene recibos: permitir `reopen()` y luego `sync()`.
- Si existe **issued** con recibos: **no** permitir `sync()` (período cerrado). Mensaje claro.

---

## 4) Emisión y Reapertura (reutilizando servicios core)

### 4.1 Emisión (`issueLqi`)

- Usa `VoucherValidatorService` + `VoucherCalculationService`.
- Validaciones extra LQI:
  - `voucher_type = LQI`
  - `status = draft`
  - `items_count > 0` y `total` consistente.
  - Moneda uniforme y coincide con `voucher.currency`.
  - Ítems referencian cargos elegibles.
- Al emitir (en **transacción**):
  1. `issue_date = now()` (o provista) y `status = issued`.
  2. Para cada `voucher_item` → **cargo fuente**: `tenant_liquidation_voucher_id = $voucher->id`, `tenant_liquidation_settled_at = now()`.

### 4.2 Reapertura (`reopenLqi`)

- Requisitos: `status = issued` y **sin recibos** (ni parciales) aplicados.
- Efectos (en **transacción**):
  - `status = draft`, `reopened_at/by/reason` en voucher.
  - Para cada cargo asentado por este voucher: `tenant_liquidation_voucher_id = null`, `tenant_liquidation_settled_at = null`.
  - Posible `sync()` posterior para reflejar cambios.

---

## 5) API (endpoints)

> Base sugerida **/lqi** (global) + **/contracts/{contract}/lqi** (scoped). Mantiene simetría con otros módulos.

### 5.1 LQI global

- `GET /lqi` — listado con filtros: `period`, `contract_id`, `currency`, `status`. **Paginado**.
- `GET /lqi/{voucher}` — show (debe ser LQI).

### 5.2 LQI por contrato

- `POST /contracts/{contract}/lqi/sync` — body: `{ period, currency }` → devuelve **draft** actualizado/creado.
- `POST /contracts/{contract}/lqi/issue` — body: `{ period, currency, issue_date? }` → emite el LQI (debe existir draft consistente).
- `POST /contracts/{contract}/lqi/reopen` — body: `{ period, currency, reason }` → reabre un LQI `issued` **sin recibos**.
- `DELETE /contracts/{contract}/lqi` — body: `{ period, currency, reason }` → cancela (si política lo permite y sin recibos).

> Respuestas en `201/200` con `VoucherResource` (y `VoucherItemResource`). Errores con mensajes claros (ver §9).

---

## 6) Validaciones de dominio (resumen)

- **Unicidad activa**: solo 1 `draft|issued` por `(contract, period, currency)`.
- **Elegibilidad**: todos los items deben provenir de cargos `impact tenant add|subtract`, no cancelados ni asentados, moneda = voucher.currency, período correcto.
- **Draft vacío**: no emitir.
- **Reapertura**: prohibida con recibos.
- **Cancelación**: prohibida con recibos; requiere motivo.

---

## 7) Permisos y seguridad

- `lqi.view`, `lqi.sync`, `lqi.issue`, `lqi.reopen`, `lqi.cancel`.
- Logs de auditoría en operaciones sensibles (motivos y usuarios).

---

## 8) UI (Vue + Vuetify)

### 8.1 Vistas

- **LqiIndex.vue** (global): filtros (period, contract, currency, status), tabla `v-data-table-server`, KPIs.
- **LqiShow\.vue**: header (estado, totales, moneda, período) + ítems + trazas + acciones según estado.
- **ContractLqiTab.vue**: tab en contrato con el mismo patrón, scoping por contract.

### 8.2 Acciones

- **Draft**: `Sincronizar`, `Emitir`, `Eliminar/Cancelar`.
- **Issued**: `Reabrir` (si no hay recibos).

### 8.3 Mensajería UX

- Si período cerrado (issued con recibos): banner “Período cerrado. Para incluir cargos nuevos, espere v2 (LQI complementaria/NC/ND) o reprogramar al próximo período según política interna.”
- Confirmaciones con resumen (cantidad de ítems, total, moneda).

---

## 9) Errores y códigos

- `409 LQI_ALREADY_ISSUED_WITH_PAYMENTS` — intento de sync/issue en período cerrado.
- `422 LQI_EMPTY_DRAFT` — emisión sin ítems.
- `422 LQI_INCONSISTENT_CURRENCY` — moneda de item/cargo ≠ voucher.
- `422 LQI_INELIGIBLE_CHARGES` — cargo cancelado/asentado o impacto ≠ tenant.
- `409 LQI_UNIQUE_ACTIVE_CONFLICT` — violación de unicidad activa.
- `403 LQI_FORBIDDEN` — sin permisos.

---

## 10) Transaccionalidad y concurrencia

- **Transacciones** en `sync`, `issue`, `reopen`, `cancel`.
- **Bloqueos**: al emitir, `SELECT ... FOR UPDATE` sobre los cargos elegibles para marcar `lqi_settled_at` atómicamente.
- **Idempotencia**: si se repite una operación con los mismos parámetros, el resultado debe ser estable.

---

## 11) Observabilidad

- Events/Listeners: `LqiIssued`, `LqiReopened`, `LqiCanceled`, `LqiSynced`.
- Métricas: cantidad/importe por estado y período, duración de sync/issue.
- Logs con contexto (contract\_id, period, currency, user\_id).

---

## 12) Plan de tests (Laravel)

### 12.1 Unit

- `LqiBuilderServiceTest` — sync agrega/quita ítems correctamente, respeta idempotencia.
- `VoucherValidatorServiceTest` (LQI flavor) — bloquea emisión vacía o inconsistente.

### 12.2 Feature

- `POST /contracts/{id}/lqi/sync` — crea/actualiza draft; no duplica ítems.
- `POST /contracts/{id}/lqi/issue` — emite y marca cargos asentados.
- `POST /contracts/{id}/lqi/reopen` — solo sin recibos; limpia asentados.
- Concurrencia: dos sync simultáneos → resultado consistente.

### 12.3 Data builders/factories

- Contracts activos durante período.
- Charges elegibles (varias monedas, impactos add|subtract, rent + extras).
- Caso LQI issued con recibos → `reopen` bloqueado.

---

## 13) Migraciones/seed (mínimos)

- Insert de `voucher_types` con `LQI`.
- (Opcional) Seed de escenarios base para QA: 1 contrato, 2 monedas, rent + bonificación, un cargo cancelado, etc.

---

## 14) Backlog v2 (alineado con Funcional)

- **LQI complementaria** para período ya **issued con recibos**.
- **NC/ND** contra LQI emitidas (ajuste legal/contable).
- **Prorrateo** de ítems a nivel LQI.
- **Impuestos** desglosados por ítem.
- **Consolidación multicontacto** (agrupación por inquilino).
- **Políticas de currency\_policy** y conversión.

---

## 15) Checklist técnico

1. `voucher_items`: agregar **contract_charge_id** (FK) y **impact** (string + CHECK si aplica) y `UNIQUE (voucher_id, contract_charge_id)`.
2. Unicidad activa `(contract, period, currency)` implementada en servicio (o índice parcial si la BD lo soporta).
3. `LqiBuilderService@sync`: usa `contract_charge_id` para idempotencia; crea/elimina ítems según elegibilidad; recalcula totales.
4. Emisión: marca `tenant_liquidation_voucher_id` y `tenant_liquidation_settled_at` en cargos (transacción); reapertura limpia ambas.
5. Validador por tipo: `subtract` permitido solo en LQI/LQP; prohibido en FAC/ND/NC/RCB/RPG.
6. `VoucherCalculationService`: aplica signo por `impact` en subtotales, IVA y total.
7. Endpoints `/contracts/{contract}/lqi/{sync|issue|reopen}` y `/lqi`: responses con `VoucherResource`; errores del §9.
8. Concurrencia: sync idempotente bajo solicitudes simultáneas.
9. Tests: elegibilidad por `effective_date`; draft vacío no emite; reopen bloqueado con recibos; cálculo `add/subtract`.

