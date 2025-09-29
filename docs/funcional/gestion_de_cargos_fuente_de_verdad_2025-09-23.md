# Gestión de Cargos — **Fuente de Verdad**

> Este documento reemplaza versiones previas. Concentra **todo** lo necesario para operar y desarrollar la Gestión de Cargos (contract_charges). Está dividido en dos partes: **Funcional (usuario final)** y **Técnica (equipo de desarrollo)**.

---

## PARTE I — Funcional (para usuarios de negocio)

### 1) Propósito
Administrar **cargos de contrato** que impactan las **liquidaciones** del Inquilino y del Propietario. Los cargos **no mueven caja** por sí mismos; la caja se mueve con **documentos** (vouchers) de **Liquidación** y **Cobranza/Pagos**.

### 2) Conceptos clave
- **Cargo (Contract Charge):** un item con monto positivo que se imputará en liquidaciones según su tipo.
- **Tipo de cargo (Charge Type):** define cómo se muestra y cómo impacta en cada liquidación.
- **Liquidación Inquilino (LQI):** documento que informa y devenga la **deuda** del inquilino (cuenta por cobrar).
- **Liquidación Propietario (LQP):** documento que informa y devenga la **obligación** hacia el propietario (cuenta por pagar), aplicando retenciones si corresponde.
- **Cobranza / Pagos:** documentos que cancelan total o parcialmente lo devengado y **mueven caja**.
- **Cancelación de cargo:** acción de negocio que **anula** un cargo antes/después de su inclusión en borradores de liquidación.
  Deja rastro (`canceled_at`, `canceled_by`, `canceled_reason`) y lo **excluye** de LQI/LQP.


### 3) Cómo se muestra e impacta cada cargo
Cada tipo de cargo define su **impacto** en cada lado:
- `add`: se **muestra** y **suma** al total de la liquidación.
- `subtract`: se **muestra** y **resta** del total.
- `info`: se **muestra** solo como **informativo** (no suma ni resta).
- `hidden`: **no se muestra** en esa liquidación.

> El **monto** del cargo en el sistema siempre se guarda **positivo**. El **signo efectivo** lo aporta el tipo al calcular la liquidación.

### 4) Catálogo de tipos (MVP)

| Código | Descripción | LQI (Inquilino) | LQP (Propietario) |
|---|---|---|---|
| RENT | Alquiler mensual | add | add |
| ADJ_DIFF_DEBIT | Diferencia/Ajuste a cobrar | add | add |
| ADJ_DIFF_CREDIT | Diferencia/Ajuste a devolver | subtract | subtract |
| RECUP_TENANT_AGENCY | Recupero de la inmobiliaria al inquilino | add | hidden |
| RECUP_OWNER_AGENCY | Recupero de la inmobiliaria al propietario | hidden | subtract |
| RECUP_TENANT_OWNER | Recupero inquilino→propietario | add | add |
| RECUP_OWNER_TENANT | Recupero propietario→inquilino | subtract | subtract |
| BONIFICATION | Bonificación / Descuento | subtract | subtract |
| SELF_PAID_INFO | Pagado directo por el inquilino (informativo) | info | info |

### 5) ¿Cuándo entra un cargo en una liquidación?
**LQI:** entra si: (a) su impacto en inquilino es `add`/`subtract`, (b) el **período** coincide (effective_date dentro del mes),
(c) **no está cancelado** (`canceled_at` = NULL), (d) no fue **asentado** previamente (la LQI previa no está posteada),
(e) si el tipo requiere **contraparte** inquilino, ésta está informada.

**LQP:** igual lógica mirando el impacto del lado propietario y su contraparte, y **no cancelado**.


> Si existe una LQI/LQP **DRAFT** del período, el sistema **sincroniza** el cargo con ese borrador (no duplica líneas). Al **postear** la liquidación, el cargo queda **asentado** del lado correspondiente.
> Si un cargo se **cancela**, deja de sincronizarse y/o se elimina de borradores DRAFT conforme a reglas de cada builder.


### 6) Contraparte
Algunos cargos requieren indicar la **persona específica** dentro del contrato a la que se aplica (un inquilino o un propietario). Sirve para imputar correctamente y para reportes. Si el tipo no requiere contraparte del lado propietario, la liquidación reparte por **% de ownership**.

### 7) Service type y períodos de servicio
- **`service_type`** clasifica conceptos (luz, agua, expensas, ABL, etc.).
- Algunos tipos **exigen** `service_type` para permitir conciliaciones de recuperos vs. egresos.
- Los **períodos de servicio** (`service_period_start/end`) se usan en ajustes/prorrateos o cuando el cargo representa consumo en un rango.

### 8) Flujo del Inquilino (dos etapas)
1. **Liquidación (LQI)**: informa y devenga la deuda (cuenta por cobrar). **No mueve caja**.
2. **Cobranza (Recibo)**: cancela total o parcial la deuda y **mueve caja** (ingreso).

### 9) Flujo del Propietario
1. **Liquidación (LQP)**: informa y devenga obligación (cuenta por pagar) y retenciones. **No mueve caja** en ese momento.
2. **Pago**: cancela todo o parte de la obligación y **mueve caja** (egreso).

### 10) Recuperos de la agencia (casos típicos)
- **Inquilino**: la agencia paga un gasto (egreso de caja), luego lo recupera al inquilino vía LQI → cobranza.
- **Propietario**: la agencia paga un gasto y lo recupera por **retención** en LQP; el efecto de caja fue el egreso inicial.

### 11) Reportes clave
- **Cuenta corriente** por contrato/parte.
- **Aging** de saldos del inquilino.
- **Recuperos** (agencia→inquilino/propietario) por tipo de servicio y período.
- **Bonificaciones** por mes.
- **Informativos** (SELF_PAID_INFO) para transparencia.

---

## PARTE II — Técnica (para desarrollo)

### A) Modelo de datos

**Tabla `contract_charges` (resumen de campos relevantes)**
- `contract_id` FK, `charge_type_id` FK, `service_type_id` FK nullable
- `counterparty_contract_client_id` FK nullable (ContractClient con rol tenant/owner)
- `amount` **decimal positivo**, `currency` (3)
- `effective_date` (período), `due_date` (nullable)
- `service_period_start/end` (nullable), `invoice_date` (nullable)
- `tenant_liquidation_voucher_id` FK nullable (LQI), `owner_liquidation_voucher_id` FK nullable (LQP)
- `tenant_settled_at`, `owner_settled_at` (nullable, se setean al postear LQI/LQP)
- **Cancelación**: `canceled_at` (nullable), `canceled_by` (FK users, nullable), `canceled_reason` (nullable), `is_canceled` (bool; mantenido por la app)
- `description`, timestamps
- (Opcional de performance/portabilidad) `period` `CHAR(7)` (YYYY-MM) mantenido por la app


**Tabla `charge_types` (parámetros de comportamiento)**
- `code` (unique), `name`, `is_active`
- `tenant_impact` / `owner_impact` ∈ `add|subtract|info|hidden`
- `requires_service_period` (bool)
- `requires_service_type` (bool)
- `requires_counterparty` ∈ `tenant|owner|null`
- (Si la liquidación es multi-moneda por documento, **no** se usa `currency_policy` en cargos; cada LQI/LQP agrupa por `currency`)

**Índices sugeridos**
- `idx_cc_contract_effective (contract_id, effective_date)`
- `idx_cc_contract_currency (contract_id, currency)`
- `idx_cc_service_period (service_period_start, service_period_end)`
- `idx_cc_type (charge_type_id)`
- `idx_cc_tenant_liq (tenant_liquidation_voucher_id)`
- `idx_cc_owner_liq (owner_liquidation_voucher_id)`
- `idx_cc_contract_type_period (contract_id, charge_type_id, effective_date)`
- `idx_cc_is_canceled (is_canceled)` y `idx_cc_period (period)` si se usan esos campos
- **Unicidad activa** (evitar duplicados con cancelados coexistiendo):  
  `UNIQUE(contract_id, charge_type_id, currency, effective_date, is_canceled)`

> **RENT idempotente:** 1 por `(contract_id, charge_type_id, currency, effective_date)`. Proteger en **servicio** (y opcionalmente con unique parcial si el motor lo permite).
> Con cancelación, la unicidad activa se logra agregando `is_canceled` a la clave.

### B) Reglas de elegibilidad (algoritmo)
Para LQI:
1. `impact(tenant) ∈ {add, subtract}`
2. `effective_date` dentro del período
3. **No cancelado** (`canceled_at IS NULL` y/o `is_canceled = false`)
4. `tenant_settled_at IS NULL`
5. Si `requires_counterparty = tenant` ⇒ `counterparty` válido

Para LQP: lo mismo con `owner`.

**Cálculo de totales**
- `signed_amount` = `+amount` si `impact = add`, `-amount` si `subtract`, `0` en `info/hidden`.

### C) Ciclo de vida documental
- **Draft** de liquidación (LQI/LQP) se puede regenerar/sincronizar con cargos del período sin duplicar.
- Al **postear** LQI/LQP:
  - Setear `tenant_settled_at` / `owner_settled_at`.
  - Mantener `*_liquidation_voucher_id`.
- Al **reabrir/anular** LQI/LQP:
  - Limpiar `*_settled_at` y (opcionalmente) mantener FK si vuelve a DRAFT.
- **Cancelación de cargo** (independiente de LQI/LQP):
  - `cancel(charge, reason, by)` setea `canceled_at`, `canceled_reason`, `canceled_by`, `is_canceled = true` (idempotente).
  - **Bloquea** si el cargo está vinculado a una LQI/LQP **no-DRAFT**.
  - Excluye al cargo de futuros builds/sincronizaciones de borradores.

### D) Validaciones (Requests)
- `amount` ≥ 0.01 (se normaliza a **abs** y `currency` a **upper** en `prepareForValidation`).
- `requires_counterparty`: exigir `counterparty_contract_client_id` del mismo contrato y rol correcto.
- `requires_service_period`: exigir `service_period_start/end` y `end ≥ start`.
- `requires_service_type`: exigir `service_type_id`.
- **Cancelación**: `canceled_reason` requerido cuando se informa `canceled_at`; al cancelar vía endpoint, `reason` requerido (min 3).
- **Edición**: si `canceled_at` **no** es NULL o si existe LQI/LQP **no-DRAFT**, **no** permitir cambios financieros (amount, currency, effective_date, counterparty, service_period_*).


### E) Semántica de LQI/LQP (tipos de voucher)
- **LQI** (`short_name`: `LQI`): `credit = false`, `affects_account = true`, `affects_cash = false`.
- **LQP** (`short_name`: `LQP`): `credit = true`,  `affects_account = true`, `affects_cash = false`.

> Los **recibos** (cobranza) y **pagos** (a propietarios/proveedores) viven en sus propios tipos documentales y **mueven caja**.

### F) Recuperos de la agencia — diseño
- Sin vínculo duro obligatorio egreso↔recupero.
- Trazabilidad vía **reportes** por `service_type` y período.
- (Futuro) tabla opcional `charge_expense_links` con asignaciones parciales para auditoría.

### G) Servicios del dominio (comportamiento esperado)
- **RentGenerationService**: asegura 1 RENT por contrato/mes/moneda; crea/actualiza salvo que exista LQI/LQP posteada que lo involucre.
- **Tenant/Owner Liquidation Builders**: construyen/actualizan borradores de LQI/LQP del período a partir de cargos elegibles.
- **LiquidationLifecycleService**: `on*Posted` / `on*Reopened` para setear/limpiar `*_settled_at` y FKs.
- **ContractChargeCancellationService** (o método en el modelo):
  - Reglas: idempotente; prohíbe cancelar con LQI/LQP no-DRAFT; registra auditoría; emite evento `ContractChargeCanceled`.


### H) UI (pautas)
- **Listado de cargos**: filtros por contrato, período, tipo, impactos, `service_type`, contraparte.
- **Formulario**: muestra/oculta campos dinámicamente según el `charge_type` (service_type, período de servicio, contraparte).
- **Acción “Cancelar cargo”** en listado y edición:
  - Modal con textarea “Motivo” (requerido, min 3).
  - Tras cancelar: mostrar badge **Cancelado**, deshabilitar campos financieros, y excluir de previews de LQI/LQP.
- **Filtros de estado**: Activos / Cancelados / Todos.
- **Detalle**: mostrar vínculos a LQI/LQP y estado de asentado por lado.

### I) QA / Tests sugeridos
- RENT idempotente por período.
- Inclusión correcta en LQI/LQP según impactos.
- `requires_counterparty/service_type/period` forzados cuando corresponde.
- Posteo de LQI/LQP setea `*_settled_at`; reabrir limpia.
- SELF_PAID_INFO no impacta totales.
- Recuperos de agencia: flujo completo (egreso → cargo → LQI/LQP → cobro/pago).
- **Cancelación**:
  - Cancela ok (setea `canceled_*`, `is_canceled` true, excluido de builders).
  - Idempotencia (segunda cancelación no falla ni cambia datos).
  - Bloquea cuando hay LQI/LQP **no-DRAFT**.
  - Bloquea edición financiera si está cancelado o documentado.

### J) Roadmap
- Asignaciones opcionales egreso↔recupero (many-to-many con `allocated_amount`).
- Reglas de conversión multi-moneda extendidas.
- Overrides puntuales de impacto por cargo (apagado en MVP).

---

## Anexos (referencia rápida)

**Impactos**: `add` suma, `subtract` resta, `info` sólo muestra, `hidden` oculta.  
**Elegibilidad**: período + impacto del lado + sin asentado + requisitos del tipo.  
**Cargos**: no mueven caja; la mueven LQI/LQP **y** los Recibos/Pagos.

---

**Estado:** aprobado para uso como **FUENTE DE VERDAD**. Cualquier cambio debe reflejarse aquí antes de tocar código.
