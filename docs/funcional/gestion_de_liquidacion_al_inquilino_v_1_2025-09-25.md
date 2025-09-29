# LQI – Documento Funcional (Liquidación al Inquilino) · v1

> **Objetivo**: definir la funcionalidad completa de LQI (Liquidación al Inquilino) como documento que devenga deuda del inquilino agrupando cargos elegibles por período y moneda, con ciclo de vida claro (borrador → emisión → cancelación/reapertura) y reglas de sincronización idempotentes.

---

## 1) Alcance y contexto
- **Alcance**: construcción, sincronización, validación y emisión de LQI. Gestión UI/UX y reglas de negocio. No incluye cobranza (recibos) ni LQP (al propietario), aunque se contemplan sus interacciones.
- **Contexto**: LQI se apoya en `contract_charges` y en RENT generado en el Paso 2 del flujo general. Cada LQI:
  - Se genera **por moneda** y **por período** (YYYY-MM).
  - Agrupa cargos con impacto **tenant** (`add`/`subtract`), **no cancelados** y **no asentados** previamente.
  - No mueve caja; la caja se mueve con **recibos** (Paso 5).

## 2) Definiciones
- **Período**: `YYYY-MM`. Deriva de `effective_date` de cada cargo.
- **Moneda**: los cargos mantienen su `currency`; LQI es mono-moneda. Puede haber múltiples LQI por contrato en un mismo período si hay más de una moneda.
- **Sincronización (builder/sync)**: acción que crea o actualiza un **LQI DRAFT** para reflejar todos los cargos elegibles a la fecha, sin duplicar líneas.
- **Asentado**: LQI **issued**. Sus ítems quedan fijados; los cargos quedan “asentados” para la liquidación (no se reusan en otra LQI del mismo período/moneda).

## 3) Supuestos y dependencias
- Paso 1 (Ajustes): `FACTORED` y `VALUED` con política base (acumulativa / no acumulativa). **VALUED ≠ consumido** hasta que RENT de ese mes lo consuma.
- Paso 2 (RENT): para cada `(contract, period)` existe a lo sumo **1 cargo RENT** (upsert). Si deriva de ajuste **VALUED**, ese ajuste se marca `applied` en la misma transacción.
- Paso 3 (Cargos): cargos no-RENT se cargan manualmente, consistentes e inmutables en `contract_id` y `charge_type`.

## 4) Estados de LQI
- `draft`: editable y sincronizable. Admite altas/bajas de ítems por sync.
- `issued`: asentado. No editable financiero. Permite **reapertura** con reglas.
- `canceled`: documento anulado (excepcional). Dejar rastro/auditoría.
- `reopened` (concepto): resultado de revertir `issued` → volver a `draft` preservando auditoría.

> **Regla**: No se puede **emitir** si no hay ítems o si hay inconsistencias (validaciones fallidas).

## 5) Reglas de inclusión de cargos en LQI
Un cargo entra en la LQI del **período/moneda** si y solo si:
1. `impact(tenant)` ∈ {`add`, `subtract`}.
2. `effective_date` ∈ mes objetivo (o prorrateo explícito si aplica; v1 sin prorrateo automático en LQI).
3. No está cancelado (`is_canceled = false`).
4. No está asentado (`lqi_settled_at` NULL) y no pertenece a un LQI **issued** previo.
5. Cumple requisitos del tipo (p.ej. contraparte/supplier si corresponde).
6. `currency` = moneda de la LQI.

> **Nota**: `due_date` es informativa a nivel ítem (mora/comunicación); **no** afecta la elegibilidad ni el período.

## 6) Idempotencia y unicidad
- **Unicidad LQI**: por `(contract_id, period, currency)` a lo sumo **1 LQI activo** (`draft` o `issued`).
- **Idempotencia de sync**: ejecutar sync múltiples veces **no duplica** LQI ni ítems:
  - Si existe **DRAFT**, se **sincroniza** (agrega/quita ítems para reflejar cargos elegibles).
  - Si **no existe**, se crea el DRAFT con los ítems correspondientes.
- Ítems se vinculan por el **cargo fuente** para evitar duplicaciones (clave lógica: `contract_charge_id`).

## 7) Orden de cálculo (totales y signos)
- Los ítems heredan `amount` positivo del cargo y su **signo efectivo** lo define el tipo (`add` suma, `subtract` resta).
- Totales del LQI:
  - **Subtotal**: suma algebraica de ítems (add/subtract).
  - **Ajustes/bonificaciones** (si existieran como cargos `subtract`).
  - **Total**: `subtotal` (sin impuestos en v1 salvo que el cargo lo incorpore).
- No hay conversión de moneda en v1: cada LQI está en una moneda.

## 8) Validaciones funcionales
- **Estado del documento**: solo `draft` admite sync/edición. `issued` bloquea edición financiera.
- **Elegibilidad de cargos**: si un cargo deja de ser elegible (p.ej. cancelado), el próximo sync lo elimina del DRAFT.
- **Moneda**: todos los ítems deben tener `currency` = moneda LQI.
- **Cargos asentados**: si un cargo ya está asentado en un LQI `issued`, **no** se puede reutilizar.
- **Inmutables**: en ítems, `contract_charge_id` y `currency` no se alteran manualmente.
- **Vacío**: no se puede emitir un LQI sin ítems ni con total inválido.

## 9) Acciones y flujo de usuario (UI/UX)
### Pantallas
1. **LQI Global** (lista + filtros): período, contrato, moneda, estado.
2. **LQI Show**: encabezado, ítems, totales, trazas, acciones.
3. **Tab en Contrato**: vista filtrada del contrato actual.

### Acciones por estado
- **draft**:
  - `Sincronizar`: reconstruye ítems según cargos elegibles.
  - `Emitir`: valida y pasa a `issued` (asienta cargos).
  - `Eliminar` (opcional) o `Cancelar` (si corresponde política de negocio).
- **issued**:
  - `Reabrir`: vuelve a `draft` (desasienta ítems) **si no hay recibos aplicados**.
  - `Cancelar`: solo con motivo y sin recibos asociados; deja auditoría.

### Interacciones destacadas
- Si existe un **DRAFT** para `(contract, period, currency)`, el botón principal es **“Sincronizar”**.
- Si no existe, el botón principal es **“Crear borrador”**.
- Al **Emitir**, mostrar resumen (cantidad de ítems, total, moneda) y confirmación.

## 10) Emisión y asentamiento
- Al **emitir**:
  - Se valida consistencia (elegibilidad, moneda, duplicados, totales, al menos un ítem).
  - Se fija `issue_date` del LQI.
  - Se marca cada cargo como **asentado** para esa LQI (`lqi_settled_at` = timestamp y FK al voucher LQI) para impedir doble uso en el mismo período/moneda.

## 11) Reapertura
- `issued` → `draft` con **reapertura** solo si:
  - No existen **recibos** (cobranzas) aplicados a ese LQI.
  - Se registra `reopened_at`, `reopened_by`, `reopen_reason`.
- Al reabrir, los cargos vuelven a **no asentados** (limpiar marcas de asentamiento) y el próximo **sync** recalcula ítems.

## 12) Cancelación
- Acción excepcional con **motivo obligatorio**. Deja el LQI fuera del pipeline.
- Requisitos: sin recibos. Limpia asentamientos de cargos.
- Auditoría completa: `canceled_at`, `canceled_by`, `canceled_reason`.

## 13) Datos mínimos
### Encabezado LQI
- `contract_id`, `period (YYYY-MM)`, `currency`
- `status` (`draft|issued|canceled`)
- `issue_date?`, `due_date?` (vencimiento del documento)
- Totales: `items_count`, `subtotal`, `total`
- Auditoría: `created_by`, `updated_by`, `issued_by?`, `reopened_by?`, `canceled_by?` + timestamps y motivos.

### Ítems LQI
- `contract_charge_id` (FK fuente), `description`
- `amount` (positivo), `impact` (`add|subtract`)
- `currency`, `effective_date`, `due_date?`
- Referencias de trazabilidad: `source` (MANUAL/RENT_ENGINE), `service_period_*?`, `invoice_date?`

## 14) Reglas de sincronización (builder)
1. Cargar cargos elegibles (según §5) del `(contract, period, currency)`.
2. Comparar contra ítems actuales del DRAFT:
   - **Agregar** ítems faltantes.
   - **Quitar** ítems cuyos cargos dejaron de ser elegibles.
3. Recalcular totales y contadores.
4. Mantener idempotencia (no duplicar por múltiples ejecuciones).

## 15) Errores y mensajes
- **Sin cargos elegibles**: “No hay cargos elegibles para el período/moneda seleccionados”.
- **Conflicto de moneda**: “El ítem tiene moneda diferente a la LQI”.
- **Cargos ya asentados**: “Algunos cargos ya están asentados en otra LQI emitida”.
- **Reapertura bloqueada**: “No se puede reabrir: existen recibos aplicados”.
- **Cancelación bloqueada**: “No se puede cancelar: existen recibos aplicados”.

## 16) Auditoría y trazabilidad
- Guardar en LQI y en cada ítem: usuario, timestamps y motivo en operaciones sensibles.
- Traza desde LQI → ítems → `contract_charge` (y viceversa) para reconstruir historia.

## 17) Permisos
- Ver LQI / Crear DRAFT / Sincronizar / Emitir / Reabrir / Cancelar pueden mapearse a permisos independientes (`lqi.view`, `lqi.sync`, `lqi.issue`, `lqi.reopen`, `lqi.cancel`).

## 18) KPIs y listados
- **KPIs globales** (por período):
  - LQI `issued` (cantidad, total) por moneda.
  - Contratos con LQI `draft` pendientes de emisión.
  - Contratos sin LQI teniendo cargos elegibles.
- **Listado** con filtros: período, contrato, moneda, estado; orden por contrato, fecha, total.

## 19) UX detallada (wireflow)
1. Usuario elige **período** y **moneda** en “LQI Global”.
2. Click **Crear borrador** (si no existe) o **Sincronizar** (si existe `draft`).
3. Revisa ítems y totales; puede abrir el show del contrato para inspección de cargos fuente.
4. Click **Emitir** → confirmación → LQI `issued`.
5. (Opcional) Si detecta error y no hay recibos: **Reabrir**.

## 20) Casos límite

> **Nota**: Los casos que impliquen cargos nuevos en períodos ya cerrados con LQI emitida y cobranzas aplicadas **se resolverán en la v2** (con LQI complementaria o NC/ND). En v1 solo se admite reapertura si no hay recibos.
- **Cargos cruzados** (múltiples monedas): se reparten en LQI separados por moneda.
- **Cargos cancelados luego de emitir**: ya no afectan LQI emitida; se corrigen por **NC** (futuro) o reapertura si no hay recibos.
- **RENT inexistente**: si no hay RENT pero hay otros cargos elegibles, la LQI puede emitirse (total ≠ 0) — política aceptada en v1.
- **Bonificaciones**: si existen como cargos `subtract`, se incluyen naturalmente por regla de impacto.
- **Nuevos cargos tras LQI emitida**: 
  - Si la LQI está emitida y sin recibos, puede reabrirse y sincronizarse.
  - Si la LQI está emitida y tiene recibos, en v1 no se reabre ni se crea otra LQI del mismo período. Esos cargos no se pierden, pero quedan sin liquidar hasta el próximo período. Esto preserva unicidad.  
  - **Advertencia**: mover `effective_date` al período siguiente altera la referencia temporal original del cargo. Se documenta como workaround de v1, pero la solución formal es parte de v2 (NC/ND o LQI complementaria).

## 21) Roadmap v2+ (fuera de alcance v1)
- **Casos de cargos nuevos en períodos ya cerrados con LQI emitida y cobranzas aplicadas**: resolver mediante LQI complementaria o NC/ND.

- **Prorrateo** automático a nivel LQI.
- **Impuestos** desglosados por ítem (IVA u otros) con redondeo.
- **Notas de crédito/débito** contra LQI emitidas.
- **LQI complementaria** en período ya emitido con recibos.
- **Plantillas/imprimir** con formato legal.
- **Multi-contrato** (acumulación por inquilino) y consolidación.
- **Políticas de `currency_policy`** y conversión al emitir.

---

### Checklist funcional para QA
- [ ] Un solo LQI activo por `(contract, period, currency)`.
- [ ] Sync idempotente (no duplica, refleja elegibilidad actual).
- [ ] Emisión bloqueada sin ítems o con inconsistencias.
- [ ] Reapertura y cancelación respetan reglas y dejan auditoría.
- [ ] Trazabilidad completa LQI ↔ cargos.

