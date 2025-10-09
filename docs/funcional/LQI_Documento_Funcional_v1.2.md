# LQI – Documento Funcional (Liquidación al Inquilino) · v1.2

> **Objetivo:** Definir, de punta a punta, el proceso de **Liquidación al Inquilino (LQI)** con foco en: reglas de inclusión, emisión, NC/ND asociadas, bloqueo por ajustes/cuotas, multi–moneda, KPIs y UX. Esta versión incorpora las decisiones finales: **LQI solo positivos** y **negativos en NC asociada o NC sola**; **bulk sin checkboxes**; **moneda “Todas”** con autodetección; y **KPI de Cobertura**.

---

## 1) Alcance y principios

**Alcance:** construcción, sincronización, validación, emisión, reapertura/cancelación de LQI; interacción con **NC**/**ND**; integración con cobranza (RCB); métricas y UI.

**Fuera de alcance:** AFIP para LQI (se emite como “X”), plantillas/impresión legal avanzada, LQP (propietario), motor de cobranza.

**Principios rectores**

1. **Fuente de verdad = `contract_charges`.** La liquidación surge de cargos; los ítems de vouchers no se editan manualmente.
2. **Unicidad funcional:** por `(contrato, período YYYY-MM, moneda)` a lo sumo **1 LQI**.
3. **LQI devenga deuda:** **solo ítems positivos (`add`)** y **Total > 0**. Nada de `subtract` dentro de LQI.
4. **Créditos en NC:** los ítems negativos se liquidan en **Nota de Crédito**: asociada a LQI (si existe) o **NC sola** (si solo hay negativos).
5. **Cambios post–emisión:** se expresan con **NC** (−) y/o **ND** (+) adicionales; no se reabre salvo que **no haya recibos** y sea para corregir armado.
6. **Multi–moneda real:** cargos en su propia currency; LQI/NC/ND **mono–moneda**. Un contrato puede tener varias LQI en el mismo período (una por moneda).
7. **Idempotencia:** sync/bulk pueden ejecutarse varias veces sin duplicar; las líneas referencian `contract_charge_id`.
8. **Transparencia al inquilino:** siempre se muestra **detalle completo del período** (LQI + NC/ND relacionadas) y el **neto** a pagar.

---

## 2) Datos base

- **`contract_charges`**: `contract_id`, `charge_type_id`, `amount (>0)`, `currency`, `effective_date`, `due_date?`, `is_canceled`, trazas de cancelación y de asentamiento:\
  `tenant_liquidation_voucher_id?`, `tenant_liquidation_settled_at?`.
- **`charge_types`**: define **impacto tenant** `add|subtract`.
- **`vouchers`** y **`voucher_items`** (comparten modelo):
  - **LQI**: `affects_account = true`, `credit = false` (débito). Ítems con `impact='add'` y `contract_charge_id`.
  - **NC**: `affects_account = true`, `credit = true` (crédito). Ítems `impact='subtract'` y `contract_charge_id`.
  - **ND**: `affects_account = true`, `credit = false` (débito). Ítems `impact='add'` y `contract_charge_id`.

---

## 3) Elegibilidad (inquilino)

Un cargo es **elegible** para el período/moneda si y solo si:
1. `impact(tenant)` ∈ {`add`, `subtract`} (según `charge_types`).
2. `effective_date` cae dentro del período (`YYYY-MM`).
3. `currency` = moneda objetivo.
4. **No cancelado** (`is_canceled = false`).
5. **No asentado** (`tenant_liquidation_settled_at IS NULL`).

> `due_date` es informativa (mora/comunicación) y **no** altera la elegibilidad.

---

## 4) Reglas bloqueantes previas

Antes de sync/bulk/emitir, chequear por contrato/moneda del período:
- **Ajuste pendiente** (`pending_adjustment`) → **bloquea**.
- **Falta cuota RENT** del período (`missing_rent`) → **bloquea**.

Los bloqueados se **omite** y se **registran** con motivo. No se crean borradores “vacíos”.

---

## 5) Sincronización (builder/sync)

**Entrada:** `(contrato, período, moneda)`.

**Proceso:**
1. Carga cargos **elegibles** (§3).
2. Separa por impacto:
   - **`add`** → se transforman en **ítems LQI** (idempotente por `contract_charge_id`).
   - **`subtract`** → **no** se agregan a LQI; se marcan para **NC**.
3. Recalcula totales del borrador.
4. Si **`add_total == 0`** → **no** crear/actualizar LQI; marcar “**Solo créditos**”.

**Edición en DRAFT:** no se pueden editar **ítems** (cantidad, precio, impacto, moneda). Sí metadatos del voucher (fecha, notes, vencimiento de documento).

---

## 6) Emisión (LQI + NC asociada / NC sola)

**Regla de emisión:**
- **LQI** se **emite solo si `add_total > 0`** (Total > 0).
- Si existen cargos negativos del período:
  - **Con LQI**: emitir **1 NC asociada** con todos los `subtract` (no aplicada automáticamente).
  - **Sin LQI (solo subtract)**: emitir **NC sola** del período/moneda.

**Efectos al emitir:**
- **LQI `issued`**: los cargos `add` fuente quedan **asentados** (`tenant_liquidation_*`).\
- **NC `issued`**: registra crédito y **se asocia** a la LQI (si existía). No se aplica automáticamente.

**Rationale:** LQI expresa **deuda**; NC/ND expresan **créditos/débitos** posteriores o complementarios. Se preserva unicidad y trazabilidad.

---

## 7) Cobranza (RCB)

- El recibo **no** es automático.
- En UI, al cobrar, el sistema **sugiere** aplicar en orden: **LQI** (débito) y **NC** asociada (crédito). El operador confirma.
- **Neto esperado** por moneda: `LQI − NC`. Si > 0, se cobra; si = 0, no hay cobro; si < 0, queda crédito a favor.

---

## 8) Cambios posteriores al emitir

- **Nuevos `add`** del período → **ND** agregada, **asociada** a LQI (no aplicada automática). Se puede emitir desde la acción post‑liquidación.
- **Nuevos `subtract`** del período → **nueva NC** agregada, **asociada** a LQI (no aplicada automática). También disponible desde la acción post‑liquidación.
- **Reapertura** de LQI solo si **no hay recibos**; limpia asentamientos y vuelve a `draft` para reconstruir.

---

## 9) Multi–moneda

- LQI/NC/ND son **mono–moneda**.
- En acciones **bulk**, la **Moneda** del filtro puede ser específica o **“Todas”**:\
  el sistema **autodetecta** por contrato qué monedas tienen elegibles y procesa cada `(contrato, moneda)`.

---

## 10) Política ante totales 0 o solo negativos

- **Borrador**: se permite el intento de sync para **visibilidad**, pero si `add_total == 0` no se crea/actualiza LQI y se informa “Solo créditos”.
- **Emisión**:\
  - **LQI**: solo si **Total > 0**.\
  - **Solo negativos**: **NC sola** (sin LQI).\
  - **Total LQI = 0**: no emitir (evita documentos sin sentido contable).

---

## 11) UI/UX (lista, fila y bulk)

**Filtros:** Período (obligatorio), Moneda (*Todas* o específica), Contrato (opcional), Estado (opcional).

**KPIs:**\
- **Elegibles positivos** (pares contrato/moneda con `add_total > 0` y sin bloqueos)\
- **LQI emitidas** (conteo y total)\
- **NC emitidas** (asociadas o solas)\
- **Omitidos por ajuste / falta RENT / sin elegibles**\
- **Cobertura** (ver §12)

**Listado (por contrato/moneda):**
- Contrato, Inquilino, Moneda
- `add_count / add_total`, `subtract_count / subtract_total`
- Estado LQI (Sin / Draft / Issued) y totals
- Badges: **ND sugerida** (si hay add pendientes), **NC sugerida** (si hay subtract), **Solo créditos** (add=0 & subtract>0), **Bloqueado** (ajuste/rent)
- Acciones: **Sincronizar** (respeta bloqueos y separa add/subtract), **Emitir** (LQI + NC asociada; o NC sola), **Emitir ajustes post‑liquidación** (genera ND/NC según pendientes), **Reabrir** (si aplica), **Ver detalle** / **Ver NC**

**Bulk (sin checkboxes):**
- **Generar liquidaciones del período** (opera sobre todos los resultados filtrados):\
  omite bloqueados; crea/actualiza LQI DRAFT **solo con add**; marca “NC sugerida” si hay subtract; si solo subtract → “NC sola”; si nada → “sin elegibles”.
- **Emitir borradores**: emite todas las LQI **con Total > 0**; si una fila tiene subtract, dispara **NC asociada**.
- **Emitir ajustes post‑liquidación (bulk)**: recorre el universo filtrado, omite bloqueados, emite ND para `add` pendientes y NC para `subtract` pendientes; si faltan LQI para los positivos, registra el motivo “missing_lqi_for_adds”.
- **Toast resumen**: `LQI creadas: X · Con NC sugerida: Y · NCs solas: Z · Omitidos — ajustes: A, falta RENT: B, sin elegibles: C`.
- **Toast post‑liquidación**: `ND emitidas: X · NC emitidas: Y · Omitidos — ajustes: A, falta RENT: B, nada para emitir: C[, LQI pendiente: D]`.

---

## 12) KPI Cobertura

**Definición:**  
**Cobertura % = (LQI emitidas elegibles / Universos elegibles positivos) × 100**

- **Universo (denominador):** cantidad de pares `(contrato, moneda)` con `add_total > 0` **y sin bloqueos** en el período filtrado.
  - **Excluir** “solo subtract” y “sin elegibles”.
  - **Excluir** bloqueados (`pending_adjustment`, `missing_rent`). Se reportan aparte.
- **Numerador:** esos pares con **LQI en `issued`**.
- **Reglas:** un `draft` no cubre; **NC sola** no entra en cobertura; si universo = 0, mostrar **N/A**.
- **Objetivo:** cuando se emitieron todas las liquidaciones elegibles, **Cobertura = 100%**.

---

## 13) Auditoría, permisos y trazabilidad

- Auditoría en LQI/NC/ND e ítems: usuario, timestamps, razones.
- Trazabilidad: **LQI/NC/ND ↔ voucher_items ↔ contract_charge**.
- Permisos: `lqi.view`, `lqi.sync`, `lqi.issue`, `lqi.reopen`, `lqi.cancel`.

---

## 14) Casos límite y decisiones

1. **Solo negativos** → **NC sola**; LQI no existe. Se comunica “Liquidación sin deuda – crédito a favor”.
2. **Add y subtract** → LQI (Total > 0) + **NC asociada** (no aplicada). Neto visible en cobranza.
3. **Nuevos movimientos tras emitir** → **ND/NC** adicionales asociadas (no aplicar automáticamente).
4. **Edición manual** de ítems de vouchers del proceso → **no permitido**; cambios se hacen sobre cargos + Sync.
5. **Reapertura**: solo sin recibos; limpia asentamientos y vuelve a `draft`.
6. **Moneda = Todas** en bulk → autodetección de monedas con elegibles por contrato.

---

## 15) Roadmap v2 (mencionado, sin implementar en v1)

- Aplicación **automática** (opcional) de NC/ND al emitir para netear inmediatamente.
- LQI **complementaria** para períodos ya emitidos con cobranzas.
- Estado de cuenta **“0”** opcional (documento informativo sin impacto).
- Prorrateos automáticos en el builder.
- Impuestos desglosados por ítem (IVA y otros).
- Impresiones/plantillas y envío al inquilino.
- Compensaciones automáticas para documentos en 0.

---

## 16) Post‑liquidación (ND/NC posteriores)

- **Acción por fila**: botón “Emitir ajustes post‑liquidación” disponible cuando existen `add`/`subtract` pendientes y la feature está activa. Abre modal con resumen de ND/NC a emitir y, al confirmar, genera:
  - **ND** con todos los `add` pendientes, asociada a la LQI emitida.
  - **NC** con todos los `subtract` pendientes, asociada a la LQI o independiente si la LQI no existe (solo negativos).
  - Registra asentamiento en `tenant_liquidation_*` y evita duplicados (idempotencia por `contract_charge_id`).
- **Acción masiva**: botón en toolbar “Emitir ajustes post‑liquidación (bulk)” muestra previa (filas, totales ND/NC) y ejecuta el mismo algoritmo por `(contrato, moneda)` según filtros. Para `Moneda = Todas` itera cada moneda detectada en overview.
- **Bloqueos y omisiones**: respeta `pending_adjustment` y `missing_rent`; notifica “missing_lqi_for_adds” cuando hay cargos positivos sin LQI emitida; “nothing_to_issue” cuando ya no quedan pendientes.
- **Mensajería**: toasts informan ND/NC emitidas y razones de omisiones; en la UI se refrescan KPIs y filas al finalizar.
- **Feature flag**: controlada por `features.lqi.post_issue` (backend) y permiso `lqi.post_issue_adjustments`. Sin flag o permiso las acciones se ocultan/retornan 403.
- **API**: `POST /api/lqi/{contract}/{period}/{currency}/post-issue` (fila) y `POST /api/lqi/{period}/post-issue/bulk` (masivo) comparten validaciones y respuestas.

---

## 17) Resumen ejecutivo

- Cada mes (y moneda), el sistema genera **LQI** con **todos los cargos positivos** del período (**Total > 0**).
- Las **bonificaciones/recuperos** se emiten en **NC**: asociada a LQI cuando hay deuda o **NC sola** si solo hubo créditos.
- En cobranza, el operador aplica **LQI** y luego **NC** (sugerido por sistema). El **neto** es `LQI − NC`.
- Si aparecen movimientos luego de emitir, se emiten **ND/NC** adicionales; no se toca lo ya emitido.
- La UI soporta bulk sin checkboxes, moneda “Todas”, bloqueos por ajuste/cuota, y KPI **Cobertura** (objetivo 100% cuando todo elegible fue emitido).
