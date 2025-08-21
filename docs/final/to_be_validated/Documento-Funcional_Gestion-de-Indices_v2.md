
# Módulo: Gestión de Índices — Documento Funcional
**Versión:** 1.0  

**Fecha:** 2025-08-17  

**Ámbito:** Sistema de gestión inmobiliaria — ajustes de alquiler por índices (IPC CREEBBA, IPC/INDEC, UVA, ICL)

---

## 1) Objetivo
Definir **cómo se configuran, cargan y aplican** los **índices económicos** que se usan para **actualizar alquileres**. El módulo debe ser **simple, auditable** y **parametrizable**, evitando ambigüedades sobre la base de cálculo.

---

## 2) Alcance y fuentes
Índices soportados y **fuentes oficiales**:
- **IPC CREEBBA** (Bahía Blanca): *Planilla “Serie IPC”* (nivel general, mensual, serie empalmada base dic-2020=100).
- **IPC (INDEC, nivel general)**: índice mensual nacional/provincial según corresponda.
- **UVA (BCRA)**: valor **diario** (derivado del CER, con rezago).
- **ICL (BCRA)**: índice **diario** (combinación IPC + RIPTE publicado por BCRA).

> **Dato a guardar** siempre: **Nivel / valor del índice** por período.  

> Los **porcentajes** (variación mensual, interanual, etc.) **se derivan** a partir de los niveles.

---

## 3) Conceptos clave y reglas de cálculo
### 3.1. Definiciones
- `I(t)`: **nivel del índice** en el período `t` (primer día de mes para índices mensuales; fecha exacta para diarios).
- **F** (*fin del tramo*): **mes/día anterior** a la fecha en que **rige** el ajuste.
- **S** (*inicio del tramo*): depende del método elegido.
- **Factor** de ajuste (regla **ratio**):  

  **`Factor = I(F) / I(S)`**  

  **`Nuevo alquiler = Base × Factor`**

### 3.2. Métodos de base (parametrizables en el **tipo de índice**)
1. **Por tramo (no acumulativo)** → **S** = mes/día **anterior al último ajuste** (para el primer ajuste, el **mes/día de inicio del contrato**).  
2. **Desde inicio de contrato (acumulativo)** → **S** = **mes/día de inicio del contrato** (constante).  
3. **Año corriente (YTD, base diciembre previo)** → informativo/compatible con calculadoras; para ajustes periódicos usar **forma incremental**, que se reduce a **por tramo**.  
4. **Interanual (m vs m−12)** → informativo; para ajustes periódicos, su forma incremental vuelve a **por tramo**.

> **Nota:** El módulo trabaja con **ratio**. Si un índice cambiara de base, el cociente `I(F)/I(S)` **sigue siendo válido** (invariante de base).

### 3.3. Correspondencia con calculadoras externas (ej. CREEBBA)
Para obtener `I(F)/I(S)` con una calculadora que **compone variaciones mensuales**:
- Rango a seleccionar: **Desde = 1° de (S + 1)**, **Hasta = último día de F**.  
- Ej.: contrato inicia **2024-01**, primer ajuste **01/05/2024** → **Desde 01/02/2024** hasta **30/04/2024** → `I(abr)/I(ene)`.

---

## 4) Modelo de datos (vista funcional)
### 4.1. `index_types` (configuración por índice)
- `code` (único), `name`, `is_active`
- `calculation_mode` = **`ratio`**
- `frequency` = `monthly` | `daily`
- `is_cumulative` (bool): si el **método por defecto** es “desde inicio” (true) o “por tramo” (false).  
- `base_rule` (opcional): `tranche_start` | `contract_start` | `ytd_december_previous_year` | `interannual_same_month`
- `lag_months` / `lag_days` (opcional): rezago intencional.
- `rounding_rule` (p.ej.: a pesos enteros).
- `notes`

> **Importante:** La modalidad **acumulativa/no acumulativa** es **propiedad del índice** (no del ajuste).

### 4.2. `index_values` (valores del índice)
- `index_type_id` (fk)
- `effective_date` (fecha **día 1** para mensuales; **fecha exacta** para diarios)
- `value` (decimal) — **nivel** del índice
- Único: (`index_type_id`, `effective_date`)
- (Opcional) `source_url`, `source_period_label`, `imported_at`

### 4.3. `contract_adjustments` (resultado de aplicar el índice)
- `contract_id` (fk), `effective_date` (desde cuándo rige)
- `type` (enum: `index`, `fixed`, `percentage`, `negotiated`)
- `index_type_id` (fk, nullable)
- `applied_amount` (alquiler post-ajuste), `applied_at`, `notes`

---

## 5) Flujo funcional
1. **Configurar índice** (`index_types`) con `calculation_mode=ratio`, `frequency`, `is_cumulative`, `base_rule`, `lag`, `rounding_rule`.
2. **Cargar valores** (`index_values`) desde la fuente oficial.  
   - Mensual: `effective_date` = **día 1** del mes.  
   - Diario: `effective_date` = **fecha exacta**.
3. **Detectar ajustes** (cron/acción manual): identificar contratos con ajuste que **rige** el mes/día siguiente.
4. **Calcular**: determinar **F** y **S** según el método del índice y obtener **`Factor = I(F)/I(S)`**.
5. **Aplicar**: recalcular alquiler, generar `contract_adjustments`, y **notificar** al inquilino (WhatsApp/mail).
6. **Auditar**: dejar registro de quién, cuándo y con qué valores se aplicó.

---

## 6) Ejemplos prácticos (IPC CREEBBA — mensual)
**Contrato:** ARS **1.000.000**, **inicio 2024-01**, **ajuste cada 4 meses** (01/05, 01/09, 01/01, 01/05).  

Niveles (ejemplo real de la serie CREEBBA): `I(2024-01)=1005,15`, `I(2024-04)=1422,97`, `I(2024-08)=1713,70`.

### 6.1. Método 1 — Por tramo (no acumulativo)
- **01/05/2024**: `F=2024-04`, `S=2024-01` → **Factor = 1422,97 / 1005,15 = 1,41568** → **$1.415.679**
- **01/09/2024**: `F=2024-08`, `S=2024-04` → **Factor = 1713,70 / 1422,97 = 1,20431** → **$1.704.920**

### 6.2. Método 2 — Desde inicio (acumulativo)
- **01/05/2024**: `F=2024-04`, `S=2024-01` → **$1.415.679** (igual al tramo 1)  
- **01/09/2024**: `F=2024-08`, `S=2024-01` → **1.000.000 × (1713,70/1005,15) = $1.704.920**  
> Aplicando cada ajuste en tiempo y forma, **coincide** con el método 1 (salvo redondeo).

### 6.3. Mapeo a la calculadora CREEBBA
- Ajuste 01/05/2024 → seleccionar **Desde 01/02/2024** hasta **30/04/2024** (feb–mar–abr).  
- Ajuste 01/09/2024 → seleccionar **Desde 01/05/2024** hasta **31/08/2024** (may–jun–jul–ago).

---

## 7) Ejemplo UVA / ICL (diarios, regla idéntica)
**Contrato:** ARS **1.000.000**, **inicio 2025-03-15**, ajuste **mensual**.  

- **Por tramo** de marzo→abril (ajuste rige 01/05):  

  `F = 2025-04-30`, `S = 2025-03-15` → **Factor = UVA(2025-04-30)/UVA(2025-03-15)**.  
- **Desde inicio** para el ajuste del 01/06:  

  `F = 2025-05-31`, `S = 2025-03-15` → **Factor = ICL(2025-05-31)/ICL(2025-03-15)**.

> Para diarios, **F** es el **día previo** a la entrada en vigencia; **S** es el día del **inicio del contrato** o del **último ajuste**.

---

## 8) Validaciones y reglas de negocio
- **Unicidad**: (`index_type_id`, `effective_date`) no se repite.  
- **Fechas**: mensual → **día 1**; diario → **fecha exacta**.  
- **Datos faltantes**: si no hay `I(F)` aún publicado, usar **último disponible previo** o **posponer** (parametrizable).  
- **Redondeo**: aplicar `rounding_rule` en cada ajuste.  
- **Bloqueos**: impedir editar/eliminar `index_values` ya aplicados, o registrar **auditoría** y recalcular bajo regla explícita.  
- **Lag**: si `lag_months/lag_days` > 0, recalcular **F** en consecuencia.  
- **Moneda**: el índice es adimensional; aplica sobre el importe en la **moneda del contrato**.

---

## 9) Interfaz (visión usuario)
- **Tipos de índice**: alta/edición (código, nombre, frecuencia, base por defecto, lag, redondeo, activo).  
- **Valores del índice**: carga mensual/diaria, importación masiva (CSV/Excel), ver historial y fuente.  
- **Simulador**: elegir contrato/fechas y ver: `I(F)`, `I(S)`, factor, nuevo alquiler y rango equivalente para calculadora.  
- **Agenda de ajustes**: lista de contratos con ajustes próximos, estado (“listo”, “falta dato”), acción de aplicar.  
- **Notificaciones**: envío automático al inquilino con detalle del cálculo (índice y período).  
- **Auditoría**: quién cargó valores, quién aplicó ajustes, cuándo y con qué parámetros.

---

## 10) Criterios de aceptación (QA/UAT)
- [ ] Se puede crear un **tipo de índice** con `ratio`, frecuencia y base por defecto.  
- [ ] Se pueden cargar **valores** con `effective_date` correcto (día 1 o fecha exacta).  
- [ ] El **simulador** muestra `I(F)/I(S)` y el **rango** de calculadora equivalente.  
- [ ] Para un contrato mensual/cuatri, el **primer ajuste** usa `F` mes previo al ajuste y `S` según el método.  
- [ ] **Ajustes sucesivos** encadenan correctamente (método 1) o se anclan al inicio (método 2).  
- [ ] El **redondeo** aplica la regla configurada.  
- [ ] Si falta un valor, el sistema **avisa** y aplica la política (usar último previo / posponer).  
- [ ] Queda **auditoría** completa de cargas y aplicaciones.

---

## 11) Futuras extensiones
- Importación automática desde APIs oficiales (cuando existan).  
- Validación cruzada / alertas por outliers.  
- Recalcular ajustes históricos con **versionado** de índices.  
- Reportes: interanual, YTD, contribuciones por tramo.  

---

### Anexo A — Chuleta de “F” y “S”
- **F** = **mes/día anterior** al ajuste.  
- **S** =  
  - **por tramo** → mes/día **anterior al último ajuste** (en el primero, el **inicio del contrato**).  
  - **desde inicio** → el **inicio del contrato** siempre.  
- **Calculadora (mensual)**: **Desde = 1° de (S+1)**, **Hasta = último día de F**.
---

# 12) Criterio de cálculo adoptado y relación con la calculadora CREEBBA

## 12.1 ¿Qué calcula la “calculadora CREEBBA”?
La calculadora está pensada para **informar inflación**, no para ajustar contratos.  
Cuando elegís un rango “desde enero hasta abril 2024”, te devuelve el **acumulado del año (YTD)**:
\[
\text{YTD (abr-2024)}=\frac{I(\mathbf{2024-04})}{I(\mathbf{2023-12})}-1
\]
Esa base (diciembre del año previo) **no coincide** con la base típica de un contrato que ajusta “por tramo”.

## 12.2 Qué calculamos nosotros en contratos (método adoptado)
Usamos **ratio de niveles “por tramo”**:
\[
\text{Factor}=\frac{I(\mathbf{F})}{I(\mathbf{S})}
\]
- **F** = *mes anterior al ajuste* (si el ajuste rige 01/mayo ⇒ F = abril).  
- **S** = base del tramo:
  - **No acumulativo (por tramo):** mes anterior al **último ajuste** (en el primero, el **mes de inicio del contrato**).  
  - **Acumulativo (desde inicio):** **mes de inicio del contrato** (constante).

**Nuevo alquiler = Alquiler base × Factor.**

## 12.3 Ejemplo claro (mismo caso, dos bases distintas)
Contrato inicia **01/01/2024**, primer ajuste **01/05/2024**:

- **Tramo contractual (lo que usamos):**  
\[
\frac{I(\text{2024-04})}{I(\text{2024-01})}-1
= \frac{1422{,}97}{1005{,}15}-1 \approx \mathbf{41{,}57\%}
\]

- **Calculadora (YTD, lo que NO usamos para ajustar):**  
\[
\frac{I(\text{2024-04})}{I(\text{2023-12})}-1
= \frac{1422{,}97}{819{,}01}-1 \approx \mathbf{73{,}7\%}
\]

> Mismo mes “abril 2024”, **dos bases distintas** ⇒ **dos resultados distintos**. Para contratos, la base correcta es la del **tramo**.

## 12.4 ¿Cómo replicar el **tramo contractual** con la calculadora?
La calculadora compone **variaciones mensuales** entre un “desde” y un “hasta”.  
Para obtener exactamente \(I(F)/I(S)\):

- **Desde:** 1° de **(S + 1 mes)**  
- **Hasta:** **último día de F**

Ejemplo del primer ajuste anterior (S = 2024-01, F = 2024-04):  
**Desde 01/02/2024** hasta **30/04/2024** → compone **feb + mar + abr** = \(I(\text{abr})/I(\text{ene})\) = **41,57%**.

## 12.5 ¿Por qué elegimos “por tramo” (ratio de niveles)?
- 🎯 **Alineado con las cláusulas**: “se ajusta cada X meses según el índice”. Compara **dos momentos del contrato**, no “lo que va del año”.  
- 🧮 **Invariante de base**: si el organismo cambia la base del índice, el cociente \(I(F)/I(S)\) **sigue siendo válido**.  
- 🔁 **Consistente entre índices**: funciona igual para **mensuales** (IPC/IPC CREEBBA) y **diarios** (UVA/ICL).  
- 🔎 **Trazabilidad y auditoría**: lee **dos niveles** de la planilla (S y F), guarda el **factor** y el **monto** resultante.  
- ⚖️ **Evita sobre/infra-ajustes**: YTD o interanual pueden incluir meses fuera del tramo contratado.

> Si un cliente **pide otra base** (p. ej. YTD o interanual), el módulo la soporta como **`base_rule`**; pero la **configuración por defecto** es **por tramo**.

## 12.6 Chuleta operativa (mensual)
- **Identificar F**: mes anterior al ajuste.  
- **Elegir S**: según método (último ajuste / inicio del contrato).  
- **Calcular**: \( \text{Factor} = I(F)/I(S) \) → aplicar redondeo → notificar.

**Equivalencia en calculadora (para chequear):**  
**Desde 1° de (S+1)** hasta **último día de F**.
