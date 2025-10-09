# LQI – Documento Funcional (Liquidación al Inquilino) · v1.1

> **Objetivo:** Definir, de punta a punta, cómo se generan y gestionan las **Liquidaciones al Inquilino (LQI)**. El documento resume **qué hace el sistema**, **por qué** tomamos cada decisión y **cómo** se refleja en la operación (builder/sync, emisión, notas de crédito/débito, reglas bloqueantes, multi–moneda y UX).

---

## 1) Alcance y principios

**Alcance:** construcción, sincronización, validación, emisión y reapertura/cancelación de LQI. Interacción con NC/ND y flujo de cobranza (recibos).

**Fuera de alcance:** AFIP para LQI (son “X”), impresión legal avanzada, LQP (propietario), motor de cobranza.

**Principios rectores**

1. **Fuente de verdad = `contract_charges`.** El builder toma cargos y arma vouchers. No se “tunearán” ítems manualmente.
2. **Unicidad funcional:** por `(contrato, período YYYY-MM, moneda)` a lo sumo **1 LQI**.
3. **LQI sólo devenga deuda:** contiene **ítems positivos (add)** y siempre **Total > 0**. Los negativos (bonificaciones/recuperos) **NO** van dentro de la LQI.
4. **Créditos en NC:** todo ítem negativo se liquida con **Nota de Crédito (NC)** separada (agregada), **asociada** a la LQI (**no aplicada** automáticamente).
5. **Nuevos movimientos tras la emisión:** se expresan con **NC** (−) y/o **ND** (+) adicionales, asociadas a la LQI; no se reabre salvo que no existan cobranzas y sea para corregir armado.
6. **Multi–moneda real:** cargos mantienen su moneda; LQI/NC/ND son **mono–moneda**. Un contrato puede tener varias LQI (una por moneda) en el mismo período.
7. **Idempotencia:** sincronizar múltiples veces no duplica ni ensucia; las líneas referencian `contract_charge_id`.
8. **Transparencia al cliente final:** siempre se muestra el **detalle completo del período** (LQI + NC asociada) y el **neto** esperado.

---

## 2) Datos base y dependencias

- `contract_charges`: cargos del contrato (RENT y otros), con:
  - `contract_id`, `charge_type_id`, `amount` (>0), `currency`
  - `effective_date` (define período), `due_date?`
  - `is_canceled` y trazas de cancelación
  - Asentamientos por liquidaciones: `tenant_liquidation_voucher_id?`, `tenant_liquidation_settled_at?` (**se usan tanto para LQI como para NC/ND**).
- `charge_types` define el **impacto tenant**: `add` | `subtract` (signo efectivo).
- `vouchers` y `voucher_items`:
  - LQI/NC/ND/RCB usan el mismo modelo.
  - **LQI**: `affects_account = true`, `credit = false` (débito).
  - **NC**: `affects_account = true`, `credit = true` (crédito).
  - **ND**: `affects_account = true`, `credit = false` (débito).
  - Cada `voucher_item` guarda `contract_charge_id` y `impact` (`add|subtract`).

---

## 3) Elegibilidad de cargos (lado inquilino)

Un cargo es **elegible** para el período/moneda si y sólo si:

1. `impact(tenant)` ∈ {`add`, `subtract`} (según `charge_types`).
2. `effective_date` cae dentro del período (`YYYY-MM`).
3. `currency` = moneda de la liquidación.
4. **No cancelado** (`is_canceled = false`).
5. **No asentado** en una liquidación **emitida** del período (LQI/NC/ND): `tenant_liquidation_settled_at IS NULL`.

> **Nota:** `due_date` es informativa (mora/comunicación). No altera la elegibilidad.

---

## 4) Reglas bloqueantes previas a generar

Antes de crear/sincronizar un borrador se chequea:

- **Ajuste pendiente del período** → **bloquea** generación. Motivo: `pending_adjustment`.
- **Falta de cuota RENT del período** → **bloquea** generación. Motivo: `missing_rent`.

**Resultado:** el sistema **omite** ese contrato/moneda y registra motivo. No se crean LQI “vacías”.

**Rationale:** evita liquidador “hueco” cuando la base del período está incompleta o mal secuenciada.

---

## 5) Sincronización (builder/sync)

**Entrada:** `(contrato, período, moneda)`

**Proceso:**

1. Carga cargos **elegibles** (según §3).
2. **Sólo positivos** (`add`) se transforman en ítems LQI; los `subtract` se **acumulan** para formar la **NC** (si existe).
3. Ítems se crean/actualizan por `contract_charge_id` (idempotente).
4. Recalcula totales del borrador.

**Salida:**
- `LQI DRAFT` con **Total > 0** o vacío (si sólo había subtract → no se crea LQI).
- (Opcional) Vista previa de **negativos** del período para la futura **NC**.

**Edición en DRAFT:** **no** se permite editar líneas (cantidad, precio, impacto, moneda). Sí metadatos del voucher (fecha, notas, vencimiento de documento).

---

## 6) Emisión (LQI + NC asociada, sin aplicar)

**Regla de emisión:**

- LQI: **debe** tener **Total > 0**.
- Si existen cargos negativos del período:
  - Se emite **1 NC** agregada con todos los `subtract` (Total NC > 0).
  - La **NC se asocia** a la LQI del mismo `(contrato, período, moneda)`.
  - **No** se aplica automáticamente (la compensación ocurre en la cobranza).

**Efectos al emitir:**

- **LQI** cambia a `issued`; los cargos **positivos** fuente se marcan **asentados** (`tenant_liquidation_settled_at` + `tenant_liquidation_voucher_id`).
- **NC** cambia a `issued`; los cargos **negativos** fuente se marcan **asentados** del mismo modo.
- (Cuando exista) **ND** cambia a `issued`; sus cargos **positivos adicionales** se marcan **asentados**.
- La NC queda asociada documentalmente a la LQI y registra crédito en cta cte.

**Rationale:** la LQI expresa la **deuda**; la NC expresa **créditos**. Separate concerns, trazabilidad perfecta y aging prolijo.

---

## 7) Cobranza (RCB)

- El recibo **no** se genera automáticamente.
- En UI, al cobrar el período, se **sugiere** aplicar: **LQI** (débito) y **NC** asociada (crédito). El operador confirma y puede ajustar.
- **Neto esperado**: `LQI − NC` (por moneda). Si > 0, el recibo cobra la diferencia; si = 0, no hace falta cobrar; si < 0, queda **crédito a favor**.

---

## 8) Cambios posteriores al emitir

Si **aparecen nuevos cargos** del período luego de emitir:

- **Nuevos + (add)** → **Nota de Débito (ND)** agregada, **asociada** a la LQI (**al emitir, asienta sus cargos**).
- **Nuevos − (subtract)** → **Nueva NC** agregada, **asociada** a la LQI (**al emitir, asienta sus cargos**).

> **No** reabrir LQI si hay recibos. Reapertura sólo para corregir armado y **sin** cobranzas asociadas.

---

## 9) Reapertura y cancelación

- **Reapertura LQI**: `issued → draft` sólo si:
  - No hay **recibos** aplicados.
  - Se limpian asentamientos de cargos (`tenant_liquidation_*`).
  - Se audita `reopened_at`, `reopened_by`, `reason`.
- **Cancelación**: excepcional, sin recibos; limpia asentamientos; queda auditoría.

---

## 10) Multi–moneda

- Cargos se guardan en su **propia** moneda.
- LQI/NC/ND son **mono–moneda**. Un período puede generar **múltiples LQI** por contrato (una por moneda). No hay conversión ni currency policy en v1.
- Acciones masivas permiten **Moneda = Todas** (autodetección por contrato).

---

## 11) Política ante totales 0 o negativos

- **Borrador**: permitir sincronización para **visibilidad** (señalar no–emitible).
- **Emisión**:
  - **Total LQI = 0** → **no emitir** (config v2 para “estado de cuenta 0”, off por defecto).
  - **Total LQI < 0** → **nunca** (semánticamente inválido).
- **Negativos** del período van en **NC**. El neto resultará de LQI − NC en cobranza.

**Rationale:** no contaminar numeración ni aging con “facturas 0/−”.

---

## 12) UI/UX (gestión masiva y por fila)

**Filtros:** Período (obligatorio), Moneda (*Todas* o específica), Contrato (opcional), Estado (opcional).

**KPIs:** contratos activos; con cargos elegibles; bloqueados (`pending_adjustment`, `missing_rent`); LQI borrador/emitidas (conteo y total); cobertura (emitidas / con elegibles). Si Moneda = Todas, KPIs por moneda + total.

**Listado (fila por contrato/moneda):**
- Contrato, Inquilino, Moneda
- Cargos elegibles (cant/total)
- Estado LQI (Sin LQI / Borrador / Emitida), Totales
- Badges: **Ajuste pendiente**, **Falta RENT**
- Acciones: **Sincronizar** (respeta bloqueos), **Emitir**, **Reabrir**, **Ver detalle**

**Bulk (sin checkboxes):**
- **Generar liquidaciones del período:** procesa todo el listado filtrado; **omite** bloqueados y sin elegibles. Toast: `Generadas X · Omitidas Y — (Ajuste pendiente: a, Falta RENT: b, Sin elegibles: c)`.
- **Emitir borradores:** emite todas las LQI DRAFT con **Total > 0**.
- **Reabrir emitidas:** sólo sin cobranzas.

---

## 13) Auditoría, permisos y trazabilidad

- Trazas en LQI/NC/ND e ítems: usuario, timestamps, razones sensibles.
- Trazabilidad completa: **LQI/NC/ND ↔ ítems ↔ contract_charge**.
- Permisos: `lqi.view`, `lqi.sync`, `lqi.issue`, `lqi.reopen`, `lqi.cancel`.

---

## 14) Casos límite y decisiones documentadas

1. **Contrato con sólo negativos** en el período: se emite **solo NC** (crédito a favor); LQI no existe. Se presenta al inquilino como “Liquidación sin deuda – crédito a favor”. **Por qué:** la LQI debe devengar **deuda**; el crédito se representa en NC.
2. **Contratos con LQI emitida y nuevas bonificaciones/cargos**: se generan **NC/ND** adicionales, **asociadas** y no aplicadas. **Por qué:** no se altera histórico emitido; se conserva unicidad y claridad contable.
3. **Moneda omitida en bulk**: el sistema **detecta** monedas con elegibles y crea LQI por cada una. **Por qué:** reduce fricción operativa y errores por configuración.
4. **Ediciones manuales**: ítems financieros **no** editables en vouchers generados por sistema; cambios se realizan **en cargos** + Sync. **Por qué:** idempotencia y coherencia con la fuente de verdad.
5. **LQI en 0**: no emitir. **Por qué:** no aporta valor contable, distorsiona KPIs/aging.

---

## 15) Roadmap v2 (mencionado, no implementado en v1)

- **NC/ND automáticas** para netear inmediatamente (aplicación automática opcional).
- **LQI complementaria** para períodos ya emitidos con cobranzas.
- **Estado de cuenta “0”** opcional (documento informativo sin impacto).
- **Prorrateos automáticos** en el builder.
- **Impuestos desglosados** por ítem (IVA y otros).
- **Impresiones/plantillas** legales y envío al inquilino.
- **Compensación automática** de documentos en 0.

---

## 16) Resumen ejecutivo para el cliente

- Cada mes (y moneda), el sistema construye una **LQI** que **devenga la deuda** del inquilino con todos los **cargos positivos** del período (**Total > 0**).
- Si hubo **bonificaciones/recuperos**, se emite además **una Nota de Crédito** con esos **ítems negativos**, **asociada** a la LQI (no aplicada automáticamente).
- Al **cobrar**, el operador aplica el pago a la **LQI** y luego a la **NC** asociada (sugerido por sistema). El **neto** es `LQI − NC`.
- Si después aparecen nuevos movimientos, se generan **NC/ND adicionales** sin tocar lo ya emitido.
- El inquilino **siempre** ve el **detalle completo del período** (LQI + NC) y el neto resultante, por **moneda**.