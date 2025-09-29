# Generación de Rentas — **Fuente de Verdad**

> Este documento reemplaza versiones previas sobre generación de rentas. Define el funcionamiento **funcional** y **técnico** del módulo de **Generación de Rentas** para contratos, alineado al modelo de **contract_charges** y al flujo de **Liquidaciones (LQI/LQP)**.

---

## PARTE I — Funcional (usuarios de negocio)

### 1) Propósito
Asegurar que cada contrato activo tenga su **renta mensual** generada correctamente para el período, con reglas de **idempotencia**, **prorrateos** y **ajustes** aplicadas, lista para ser **incluida** en la **Liquidación del Inquilino (LQI)** y, si corresponde, en la **Liquidación del Propietario (LQP)**.

### 2) Qué genera
- Un **Contract Charge** de tipo **RENT** por contrato, por **mes** y **moneda** del contrato: `amount > 0`, `effective_date = 1° del mes`.
- La **descripción** por defecto: “Renta mensual”.

### 3) Cuándo se genera
- Para contratos **activos durante el período** (tienen vigencia que intersecta el mes).
- No se generan rentas para contratos **pausados/cancelados** durante todo el período.

### 4) Idempotencia
- **Nunca** se crean duplicados de **RENT** para el mismo `(contrato, período, moneda)`.
- Si existe un RENT **sin liquidación posteada**, se **actualiza** el monto/fecha de vencimiento según la política vigente.
- Si existe un RENT ya **asentado** en alguna liquidación (LQI/LQP posteada), **no se toca**; diferencias se resuelven por **ADJ_DIFF_DEBIT/CREDIT**.

### 5) Prorrateos (alta/baja en medio de mes)
- Si el contrato **inicia** o **finaliza** en el mes, la renta se **prorratea** proporcional a días **activos** sobre días del mes.
- Política sugerida: `monto_prorrateado = renta_base * (días_activos / días_del_mes)`, redondeo a 2 decimales.

### 6) Ajustes y diferencias
- Antes de generar RENT, el sistema puede **aplicar/asegurar ajustes** contractuales vigentes para el mes (índices de actualización, cláusulas).
- Si se detectan cambios **después** de liquidado el mes, las diferencias se modelan con **ADJ_DIFF_DEBIT/CREDIT**.

### 7) Vencimientos
- Vencimiento sugerido: **día 10** del mes (`due_date = 10`), configurable por política del negocio.

### 8) Multimoneda
- La renta se genera en la **moneda del contrato**. Si hay cláusulas de conversión, se aplican **antes** de emitir liquidaciones.

### 9) Inclusión en liquidaciones
- La renta **entra automáticamente** en la LQI/LQP si el **impacto del tipo** lo permite (`add`); ver documento de **Gestión de Cargos** para reglas completas.

### 10) Reportes clave
- **Rentas generadas** por período.
- **Rentas prorrateadas** y sus razones (alta/baja).
- **Diferencias aplicadas** (ADJ_DIFF_*).

---

## PARTE II — Técnica (equipo de desarrollo)

### A) Entradas y dependencias
- **Contract** (vigencia, moneda, precio base por mes, parámetros de prorrateo/índice).
- **ChargeType RENT** (código `RENT` existente en catálogo).
- **Servicios auxiliares** (opcionales): `ContractRentCalculator`, `AdjustmentService`, `Liquidation builders`.

### B) Estructura de datos generada
- **contract_charges** (nuevo o actualizado):
  - `contract_id`, `charge_type_id = RENT`, `amount`, `currency`
  - `effective_date = 1º del mes`
  - `due_date = política del negocio (p.ej. día 10)`
  - `description = 'Renta mensual'`
  - **No** setea `tenant_settled_at/owner_settled_at` (eso ocurre al postear LQI/LQP).

### C) Algoritmo (alto nivel)
1. Determinar **contratos activos** en el período `P` (mes).
2. Para cada contrato:
   a. Calcular **renta base** (tarifa del contrato, cláusulas vigentes).
   b. Aplicar **prorrateo** si el contrato no abarca todo el mes.
   c. **Asegurar** un único cargo RENT:
      - Si **no existe** → **crear**.
      - Si **existe** y **no** hay LQI/LQP posteada que lo involucre → **actualizar** monto y `due_date`.
      - Si **existe** y **ya** hay liquidación posteada → **no tocar** (resolver por ADJ_DIFF_*).

3. Retornar métricas del proceso (procesados/creados/actualizados/omitidos/errores).

### D) Reglas de bloqueo por liquidación
- Si el RENT está vinculado a **LQI/LQP** con estado **NO-DRAFT**, se considera **asentado** y no se modifica.
- Si está vinculado a una liquidación **DRAFT**, se **sincroniza** (no se duplica).

### E) Prorrateo (detalle)
- **Días del mes**: `total_days = last_day_of_month - first_day_of_month + 1`.
- **Días activos**: intersección de `[start_date, end_date]` del contrato con el mes `P`.
- **Redondeos**: a 2 decimales (half up). Parametrizable si el negocio exige otro método.
- **Descripción** (opcional): incluir anotación de prorrateo para auditoría.

### F) Idempotencia/Concurrencia
- Uso de **bloqueo** (`SELECT … FOR UPDATE`) al leer/asegurar el cargo del período.
- Chequear existencia por `(contract_id, charge_type_id RENT, currency, effective_date)`.
- Manejar `Duplicate key` con **bucle de recuperación** (re-consulta tras excepción).

### G) Interacción con Ajustes
- `AdjustmentService.applyForPeriod(P)`: asegura que ajustes que afectan la base estén vigentes **antes** de calcular.
- Si luego de liquidar cambia la base (p.ej. nuevo índice retroactivo), usar **ADJ_DIFF_*** y **no** reescribir RENT asentado.

### H) Eventos/Logs sugeridos
- `rent.generated` (contrato, período, amount)
- `rent.updated` (antes/después, razón)
- `rent.skipped` (porque estaba asentado o fuera de vigencia)
- `rent.error` (detalle de excepción)

### I) Parámetros de configuración
- `due_day` (por defecto `10`)
- Política de **prorrateo** (`daily/30/360`), por defecto **días reales del mes**.
- Política de **redondeo** (`HALF_UP`).
- Habilitar/deshabilitar **aplicación de ajustes** previa.

### J) API (sugerida)
- `POST /rents/generate?period=YYYY-MM` → procesa todos los contratos activos.
- `POST /contracts/{id}/rents/generate?period=YYYY-MM` → procesa un contrato.
- `GET /contracts/{id}/charges?type=RENT&period=YYYY-MM` → consulta de control.

**Respuestas (ejemplo resumido)**
```json
{
  "period": "2025-08",
  "processed": 120,
  "created": 118,
  "updated": 2,
  "skipped": 0,
  "errors": 0
}
```

### K) Casuística y bordes
- Contrato **sin precio base** → error de negocio.
- Contrato **sin moneda** → error de negocio.
- Fechas de contrato inconsistentes (fin < inicio) → error.
- Contrato **suspendido**: excluir si la suspensión abarca todo el mes; si es parcial, **prorratear** según política.
- Moneda del cargo distinta a la del contrato cuando `currency_policy = CONTRACT_CURRENCY` → invalidar.

### L) QA / Tests sugeridos
- Generación **idempotente** (dos corridas dan mismo resultado).
- Prorrateo correcto en **alta/baja** a mitad de mes.
- Bloqueo por **LQI/LQP posteada** (no reescribe, crea ADJ_DIFF_* en su lugar).
- Sincronización con **LQI DRAFT** (no duplica líneas).
- Multimoneda según política.
- Resumen del proceso con conteos correctos.

---

## Anexos (referencia rápida)
- **RENT**: cargo base del mes; 1 por contrato/mes/moneda.
- **Prorrateo**: proporcional a días activos vs. días del mes.
- **Ajustes**: aplicar antes; si ya liquidado, usar **ADJ_DIFF_***.
- **Bloqueo**: respetar estado de liquidaciones (DRAFT vs NO-DRAFT).

---

**Estado:** aprobado para uso como **FUENTE DE VERDAD**. Cualquier cambio debe actualizarse aquí antes de tocar código.
