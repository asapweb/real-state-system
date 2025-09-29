# Gestión de Ajustes de Contrato — **Fuente de Verdad**

> Este documento define el funcionamiento **funcional** y **técnico** del módulo de **Ajustes de Contrato**, alineado a `contract_charges` y a los flujos de **Liquidaciones (LQI/LQP)**. Reemplaza versiones previas y centraliza criterios de cálculo, aplicación e interacción con rentas y liquidaciones.

---

## PARTE I — Funcional (usuarios de negocio)

### 1) Propósito
Permitir **definir y aplicar** ajustes contractuales (indexaciones, montos fijos, porcentajes, retroactivos) sobre la **renta base** y/o sobre componentes pactados, garantizando **trazabilidad**, **idempotencia** y **transparencia** en liquidaciones.

### 2) Conceptos clave
- **Ajuste de contrato**: regla que **modifica la base de renta** o genera **diferencias** en períodos específicos.
- **Tipos de ajuste (MVP)**:
  - `INDEXED` (Indexación por índice / fórmula)
  - `FIXED_DELTA` (Suma o resta de monto fijo)
  - `PERCENT_DELTA` (Aumento/bonificación en %)
  - `RETROACTIVE` (aplica diferencias sobre períodos pasados)
- **Aplicación**:
  - Si el período **no** está liquidado ⇒ el ajuste **modifica** la **renta base** previa a generar `RENT`.
  - Si el período **ya** tiene LQI/LQP **posteada** ⇒ el ajuste **NO** reescribe el `RENT`; genera **cargos de diferencia**: `ADJ_DIFF_DEBIT` o `ADJ_DIFF_CREDIT`.

### 3) Alcance
- Los ajustes operan por **contrato** (no son globales).
- Pueden tener **vigencia**: desde una fecha `effective_from` (y opcional `effective_to`).
- Pueden ser **bloqueantes** del proceso de generación si requieren confirmación (ver §7).

### 4) Reglas de negocio
1. **Idempotencia**: un ajuste aplicado múltiples veces al **mismo período** no debe duplicar efectos.
2. **Orden de aplicación** dentro del período:
   1) Ajustes **base** (INDEXED / FIXED / PERCENT) → definen la **renta base resultante**.
   2) Ajustes **retroactivos** → si el período ya está liquidado, se expresan con `ADJ_DIFF_*`.
3. **Liquidaciones posteadas** (NO-DRAFT): no se alteran montos liquidados; todo va por **diferencias** (`ADJ_DIFF_*`).
4. **Períodos parciales** (alta/baja): el **prorrateo** se calcula **después** de aplicar ajustes base.
5. **Moneda**: los ajustes siguen la **moneda del contrato** salvo regla explícita; si hay conversión, se hace **antes** de liquidar.

### 5) Ejemplos operativos
- **Indexación mensual por ICL**: cada mes se recalcula la renta base según el índice publicado.
- **Ajuste fijo por mejora**: se suma $10.000 desde septiembre a diciembre.
- **Descuento temporario**: -5% durante dos meses por obra en el edificio.
- **Retroactivo**: el índice de julio se publicó tarde; ya liquidado agosto, se genera `ADJ_DIFF_DEBIT` con período de servicio **jul–ago**.

### 6) UI (orientación)
- **Listado** de ajustes por contrato: filtros por tipo, vigencia, estado de aplicación.
- **Alta/edición**: campos dinámicos según tipo (fórmula, valor fijo, %).
- **Simulación**: vista previa del impacto en el período actual y próximo.
- **Historial**: auditoría de quién creó/actualizó y cuándo aplicó.

### 7) Bloqueo operativo (opcional)
- Un ajuste puede marcarse **bloqueante del período** (flag): mientras esté pendiente de confirmación, el sistema **omite** generar rentas del período, evitando divergencias. Una vez confirmado/aplicado, se libera el período.

---

## PARTE II — Técnica (equipo de desarrollo)

### A) Modelo de datos (sugerido)
**Tabla `contract_adjustments`**
- `id`
- `contract_id` FK
- `type` ∈ `INDEXED|FIXED_DELTA|PERCENT_DELTA|RETROACTIVE`
- **Parámetros** según tipo:
  - `index_code` (nullable) — p.ej. ICL/IPC/UVA
  - `fixed_amount` (nullable, >0)
  - `percent` (nullable, puede ser negativo para descuento)
  - `formula_json` (nullable) — estructura para expresiones complejas
- **Vigencia**:
  - `effective_from` (date, requerido)
  - `effective_to` (date, nullable)
- **Ámbito** (opcional): `applies_to` ∈ `BASE_RENT|COMPONENT_RENT|ALL` (MVP: `BASE_RENT`)
- **Control**:
  - `is_active` (bool, default true)
  - `is_blocking` (bool, default false) — ver §7
  - `applied_up_to` (date, nullable) — último período aplicado para idempotencia
- Metadatos: `notes` (text), `created_at/updated_at`

**Índices**
- `(contract_id, effective_from)`
- `(contract_id, is_active)`
- `(contract_id, type)`

### B) Servicios
- `ContractAdjustmentService`
  - `applyForPeriod(Carbon $period, ?Contract $contract = null)`
    - Si `$contract` es `null`, aplica para todos los contratos con ajustes activos y vigentes.
    - Para cada ajuste:
      - Determina si **corresponde** aplicarlo en `P` (vigencia y actividad del contrato).
      - Si el período **no** está liquidado → **ajusta la base** antes de `RENT`.
      - Si el período **está** liquidado → genera `contract_charges` **ADJ_DIFF_DEBIT/CREDIT** con `service_period_start/end`.
      - Actualiza `applied_up_to` para idempotencia.
  - `hasBlockingForPeriod(Contract $c, Carbon $P): bool`
    - Verdadero si existe un ajuste `is_blocking = true` cuya vigencia intersecta el mes `P` y aún no fue confirmado.

### C) Interacción con Generación de Rentas
1. **Orden recomendado**:
   - `ContractAdjustmentService::applyForPeriod(P)` **antes** de `RentGenerationService::generateForMonth(P)`.
2. **Si hay bloqueo** (`hasBlockingForPeriod=true`):
   - El generador de rentas **omite** ese contrato (registrar `reason = blocking_adjustment`).
3. **Al liquidar posteado**:
   - Cambios posteriores **no** reescriben RENT; se registran como `ADJ_DIFF_*`.

### D) Reglas de cálculo (detalles)
- **INDEXED**:
  - Fórmula general: `renta_base_nueva = renta_base_anterior × índice_relativo`.
  - Si el índice llega tarde y el período está liquidado, generar `ADJ_DIFF_*` por la diferencia total del período.
- **FIXED_DELTA**:
  - `renta_base_nueva = renta_base_anterior + fixed_amount` (permitir negativo como bonificación si se prefiere `PERCENT_DELTA` para %).
- **PERCENT_DELTA**:
  - `renta_base_nueva = renta_base_anterior × (1 + percent/100)`.
- **RETROACTIVE**:
  - Nunca modifica `RENT` asentado; siempre **diferencia** (`ADJ_DIFF_*`) con `service_period_start/end`.

### E) Generación de diferencias (`ADJ_DIFF_*`)
- Tipo elegido según signo de la diferencia:
  - **Debitar** al inquilino/propietario: `ADJ_DIFF_DEBIT` (`add` en ambos lados).
  - **Acreditar**: `ADJ_DIFF_CREDIT` (`subtract` en ambos lados).
- Campos relevantes del cargo diferencia:
  - `amount` (>0), `currency` del contrato
  - `effective_date` = 1° del mes **actual** (cuando se genera la diferencia)
  - `service_period_start/end` = rango al que corresponde la corrección
  - `description` clara (p.ej. "Diferencia por índice ICL jul–ago")

### F) Idempotencia
- Registrar `applied_up_to` para evitar re-aplicar ajustes base.
- Al generar `ADJ_DIFF_*` retroactivos, asegurar **no duplicar** por fecha/contrato/período de servicio + concepto (puede hacerse con hash lógico o consulta previa).

### G) API (sugerida)
- `POST /adjustments/apply?period=YYYY-MM` — aplica ajustes para todos.
- `POST /contracts/{id}/adjustments/apply?period=YYYY-MM` — aplica para un contrato.
- `GET /contracts/{id}/adjustments` — listar ajustes de un contrato.
- `POST /contracts/{id}/adjustments` — crear/editar ajustes.

**Response ejemplo (apply):**
```json
{
  "period": "2025-08",
  "processed": 120,
  "rent_updated": 95,
  "diff_charges_created": 12,
  "blocked": 13,
  "errors": 0
}
```

### H) Validaciones
- `effective_from` ≤ `effective_to` (si existe).
- Solo un `INDEXED` activo por contrato para un mismo período (o defina prioridad/orden si hay varios).
- `fixed_amount`/`percent` según tipo (requeridos y en rango).
- `is_blocking` debe requerir una acción de **confirmación** para liberarse.

### I) QA / Tests sugeridos
- Aplicación correcta de cada tipo.
- Idempotencia al re-ejecutar.
- Bloqueo evita generación de RENT.
- Generación de `ADJ_DIFF_*` cuando el período ya está liquidado.
- Interacción correcta con prorrateos (aplicar antes de prorratear RENT).

---

## Anexos (referencia rápida)
- **Ajustes base** cambian la **renta** antes de generar `RENT` si el período no está liquidado.
- **Retroactivos** siempre van por **`ADJ_DIFF_*`**.
- **Bloqueo** es opcional para garantizar calidad antes de emitir.

---

**Estado:** aprobado para uso como **FUENTE DE VERDAD**. Cualquier cambio debe reflejarse aquí antes de tocar código.
