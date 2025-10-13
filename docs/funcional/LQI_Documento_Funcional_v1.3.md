# LQI – Documento Funcional (Liquidación al Inquilino) · v1.3

> **Objetivo:** Consolidar la funcionalidad de **Liquidación al Inquilino (LQI)** incorporando los cambios acordados/implementados tras v1.2: **KPI Cobertura corregido** (universo histórico del período), **KPIs de NC**, nuevas **acciones de Emisión Post‑Liquidación** (por fila y masiva) con **gating** de visibilidad según estado, y **badges/CTAs** consistentes.

---

## 0) Cambios vs v1.2 (Changelog)

1. **Cobertura (KPI) – corrección:** ahora usa **universo histórico del período** (pares `(contrato, moneda)` con `add_total > 0` y **sin bloqueos**, independientemente de estar asentados). Si universo = 0 y no hay emitidas ⇒ **N/A**.
2. **KPIs de NC:** exposición de **NC emitidas** por moneda/overall, desglosadas en **asociadas** a LQI y **solas** (solo negativos).
3. **Emisión Post‑Liquidación:** nuevas acciones para documentar **movimientos posteriores** a la LQI del mismo período:
   - **Por fila** y **Bulk**: `Emitir ajustes post‑liquidación` ⇒ **ND** por `add` pendientes, **NC** por `subtract` pendientes; asociadas a LQI si existe.
4. **Gating/UX de acciones:** el botón `Emitir ajustes post‑liquidación` solo aparece con **LQI emitida** y **pendientes > 0**. En `status=none/draft` se muestran CTAs alternativos (Emitir LQI / NC sola) y badges adecuados.

---

## 1) Alcance y principios

**Alcance:** construcción, sincronización, validación, **emisión LQI**, emisión de **NC/ND** (incluye **post‑liquidación**), reapertura/cancelación controlada; integración con cobranza; métricas (Cobertura, NC) y UI.

**Fuera de alcance:** AFIP para LQI (emite “X”), plantillas/impresión legal avanzada, LQP (propietario), motor de cobranza.

**Principios**

1. **Fuente de verdad = **``**.** Ítems de vouchers no se editan manualmente.
2. **Unicidad funcional:** por `(contrato, período YYYY‑MM, moneda)` a lo sumo **1 LQI**.
3. **LQI devenga deuda:** solo ítems **positivos** (`add`). Total LQI **> 0**.
4. **Créditos en NC:** ítems negativos se liquidan en **Nota de Crédito** (asociada o **NC sola** si solo negativos).
5. **Cambios post‑emisión:** se documentan con **NC/ND** adicionales (no reabrir salvo sin recibos para corregir armado).
6. **Multi‑moneda real:** cargos en su currency; LQI/NC/ND son **mono‑moneda**. Un contrato puede tener varias LQI en el mismo período (una por moneda).
7. **Idempotencia:** líneas referencian `contract_charge_id`; las acciones pueden reintentarse sin duplicar.
8. **Transparencia:** siempre se muestra al inquilino **detalle del período** (LQI + NC/ND relacionadas) y el **neto**.

---

## 2) Datos base

- ``: `contract_id`, `charge_type_id`, `amount (>0)`, `currency`, `effective_date`, `due_date?`, `is_canceled`, trazas de asentamiento: `tenant_liquidation_voucher_id?`, `tenant_liquidation_settled_at?`.
- ``: define impacto tenant `add|subtract`.
- ``**/**`` (modelo común):
  - **LQI**: `affects_account = true`, `credit = false`; ítems `impact='add'`.
  - **NC**: `affects_account = true`, `credit = true`; ítems `impact='subtract'`.
  - **ND**: `affects_account = true`, `credit = false`; ítems `impact='add'`.

---

## 3) Elegibilidad (inquilino)

Un cargo es **elegible** para el período/moneda si:

1. `impact(tenant)` ∈ {`add`, `subtract`}.
2. `effective_date` ∈ mes objetivo (`YYYY‑MM`).
3. `currency` = moneda objetivo.
4. **No cancelado** (`is_canceled = false`).
5. **No asentado** (`tenant_liquidation_settled_at IS NULL`).

> `due_date` es informativa; no afecta elegibilidad.

---

## 4) Reglas bloqueantes previas

Por `(contrato, moneda)` del período:

- **Ajuste pendiente** (`pending_adjustment`) ⇒ **bloquea**.
- **Falta cuota RENT** del período (`missing_rent`) ⇒ **bloquea**.

Los bloqueados se **omiten** y se **registran** con motivo; no se crean borradores vacíos.

---

## 5) Sincronización (builder/sync)

**Entrada:** `(contrato, período, moneda)`.

**Proceso:**

1. Carga elegibles (§3) y separa por impacto.
2. `` ⇒ ítems LQI (idempotente por `contract_charge_id`).
3. `` ⇒ **no** se agregan a LQI; se marcan para **NC**.
4. Si `add_total == 0` ⇒ no crear/actualizar LQI; marcar **“Solo créditos”**.

**Edición en DRAFT:** no se editan ítems; sí metadatos del voucher.

---

## 6) Emisión inicial (LQI + NC asociada / NC sola)

- **LQI** se emite **solo** si `add_total > 0`.
- Si hay `subtract` del período:
  - Con LQI ⇒ **NC asociada** (no aplicada automáticamente).
  - Sin LQI (solo `subtract`) ⇒ **NC sola** del período/moneda.

**Efectos:**

- LQI `issued` asienta los `add` fuente.
- NC `issued` registra crédito y se asocia a LQI si existe (no aplicada automáticamente).

---

## 7) Emisión **Post‑Liquidación** (novedad v1.3)

**Objetivo:** documentar **movimientos del mismo período** que aparecen **después** de emitir LQI.

### 7.1 Acciones disponibles

- **Por fila:** `Emitir ajustes post‑liquidación`.
- **Bulk:** `Emitir ajustes post‑liquidación (bulk)`.

### 7.2 Reglas de negocio

- Requiere LQI **emitida** para el `(contrato, período, moneda)`.
- ``** pendientes ⇒ ND asociada** a la LQI.
- ``** pendientes ⇒ NC asociada** a la LQI.
- Si no existe LQI:
  - Solo `subtract` ⇒ **NC sola**.
  - Con `add` ⇒ **omitir** (emitir LQI primero).
- Se emite **1 ND** (si hay `add`) y/o **1 NC** (si hay `subtract`) por ejecución. Ítems referencian `contract_charge_id` (idempotencia).
- No reabrir LQI; no aplicar ND/NC automáticamente.
- Bloqueos vigentes (§4).

### 7.3 Gating de visibilidad (UI)

- `status = issued` **y** (`add_pending_total>0` **o** `subtract_pending_total>0`) ⇒ **mostrar botón** `Emitir ajustes post‑liquidación`.
- `status = none`:
  - `add_total>0` ⇒ CTA **“Generar/Emitir LQI”**; **badge:** *LQI pendiente*.
  - `add_total=0` & `subtract_total>0` ⇒ CTA **“Emitir NC sola”**; **badge:** *NC sola sugerida*.
- `status = draft` ⇒ CTA **“Sincronizar/Emitir LQI”**.
- Sin pendientes ⇒ ocultar/deshabilitar el botón.

### 7.4 Respuestas esperadas (resumen)

- **Por fila (OK):** incluye totales y `voucher_id` de ND/NC emitidas.
- **Bulk:** métricas agregadas (`nd_issued`, `nc_issued`) y `skipped` con `reason` (`blocked`, `nothing_to_issue`, `invalid_status_for_post_issue`, `lqi_missing_for_adds`, etc.).

---

## 8) Cobranza (RCB)

- No automática.
- Sugerencia de aplicación: **LQI** (débito) y luego **NC** (crédito). Operator confirma.
- Neto esperado por moneda: `LQI − NC`. Si < 0 queda crédito a favor.

---

## 9) Cambios posteriores al emitir (resumen)

- **Nuevos **`` del período ⇒ **ND** asociada a LQI (vía acción post‑liquidación).
- **Nuevos **`` ⇒ **NC** asociada (vía acción post‑liquidación).
- **Reapertura**: solo sin recibos; limpia asentamientos y vuelve a `draft`.

---

## 10) Multi‑moneda

- LQI/NC/ND son mono‑moneda.
- En **bulk**, Moneda = **“Todas”** ⇒ autodetección por contrato de las monedas con elegibles/pedientes.

---

## 11) Política ante totales 0 o solo negativos

- **Draft:** si `add_total==0` ⇒ no se crea/actualiza LQI; marcar **Solo créditos**.
- **Emisión:**
  - LQI solo si `Total > 0`.
  - Solo negativos ⇒ **NC sola**.
  - LQI = 0 ⇒ no emitir.

---

## 12) UI/UX (lista, fila, modales y bulk)

**Filtros:** Período (obligatorio), Moneda (*Todas* o específica), Contrato (opcional), Estado (opcional).

**Badges por fila:** *ND sugerida*, *NC sugerida*, *LQI pendiente*, *NC sola sugerida*, *Bloqueado*, *Solo créditos*.

**Acciones:**

- **Sincronizar** (respeta bloqueos; separa add/subtract).
- **Emitir** (LQI + NC asociada o NC sola).
- **Emitir ajustes post‑liquidación** (nuevo; con gating).
- **Reabrir** (si aplica), **Ver detalle**, **Ver NC/ND**.

**Bulk:**

- **Generar liquidaciones del período** (sin checkboxes).
- **Emitir borradores**.
- **Emitir ajustes post‑liquidación (bulk)** (nuevo; procesa solo `status=issued` con pendientes).
- **Toast** resumen: `LQI creadas: X · Con NC sugerida: Y · NCs solas: Z · ND emitidas post‑liq: A · NC emitidas post‑liq: B · Omitidos — ajustes: A1, falta RENT: B1, estado inválido: C1, sin elegibles/pendientes: D1`.

---

## 13) KPIs

### 13.1 Cobertura (corregido)

**Definición:**

```
Cobertura % = (pares emitidos / universo positivo del período) × 100
```

- **Universo (denominador):** cantidad de pares `(contrato, moneda)` con `add_total > 0` **y sin bloqueos** en el período **independientemente** de estar asentados.
  - Excluir: “solo subtract”, “sin elegibles”, bloqueados.
- **Numerador:** esos pares con **LQI en **``.
- **Reglas:**
  - Si `universo = 0` y `emitidas = 0` ⇒ **N/A**.
  - Si `universo = 0` y `emitidas > 0` ⇒ registrar alerta `issued_without_universe`.

### 13.2 KPIs de NC (novedad)

Por **moneda** y **overall**:

- `nc.issued_count`, `nc.issued_total` (valores positivos para lectura).
- `nc.associated_count/total` (NC vinculadas a LQI del período/moneda).
- `nc.standalone_count/total` (NC sola – solo negativos).

### 13.3 Pendientes / sugerencias operativas

- `contracts_with_eligibles`, `eligible_total`: **positivos pendientes** (no afectan Cobertura).
- (Opcional) **ND sugeridas**: `nd.suggested_count/total` para destacar `add` post‑emisión.

---

## 14) Auditoría, permisos y trazabilidad

- Auditoría en LQI/NC/ND e ítems: usuario, timestamps, motivo (incl. `post_liquidation`).
- Trazabilidad: `LQI/NC/ND ↔ voucher_items ↔ contract_charge`.
- Permisos: `lqi.view`, `lqi.sync`, `lqi.issue`, `lqi.reopen`, `lqi.cancel`, `lqi.post_issue_adjustments`.

---

## 15) Casos límite y decisiones

1. **Solo negativos** ⇒ **NC sola**; LQI no existe. Se comunica “Liquidación sin deuda – crédito a favor”.
2. **Add y subtract** ⇒ LQI (Total > 0) + **NC asociada**; neto visible en cobranza.
3. **Post‑emisión** ⇒ **ND/NC** adicionales (no aplicar automáticamente).
4. **Sin LQI** pero con `add` ⇒ no post‑liquidación; emitir LQI.
5. **Gating**: post‑liquidación visible solo en `issued` con pendientes.
6. **Moneda = Todas** en bulk ⇒ autodetección por contrato.

---

## 16) Roadmap v2 (mencionado, no implementado en v1.3)

- Aplicación **automática** opcional de NC/ND al emitir.
- LQI **complementaria** para períodos ya emitidos con cobranzas.
- Estado de cuenta **“0”** informativo.
- Prorrateos automáticos en el builder.
- Impuestos desglosados (IVA/otros) por ítem.
- Plantillas/impresiones y envío al inquilino.
- Compensaciones automáticas para documentos en 0.

---

## 17) Resumen ejecutivo

- Por mes y moneda, se emite **LQI** con todos los **positivos** del período (Total > 0). Los **negativos** se documentan en **NC** (asociadas o **solas** si no hay LQI).
- **Cobertura** mide emisión sobre universo positivo del período (histórico). Con todas las LQI emitidas ⇒ **100%**.
- Para movimientos posteriores del mismo período, usar **Emisión Post‑Liquidación**: **ND** para nuevos `add`, **NC** para nuevos `subtract`; visible solo con **LQI emitida** y **pendientes**.
- UI soporta bulk sin checkboxes, moneda “Todas”, bloqueos por ajuste/cuota, **badges/CTAs** consistentes y KPIs (Cobertura, NC).

