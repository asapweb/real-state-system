
# Módulo: Gestión de Índices

## 1. Objetivo

El módulo de **Gestión de Índices** permite administrar los valores de índices económicos utilizados para realizar ajustes contractuales en alquileres. Centraliza la carga, actualización, validación y aplicación de los índices en los procesos automáticos o manuales de ajuste de contratos.

---

## 2. Tipos de Índices Soportados

El sistema soporta distintos tipos de índices, cada uno con una modalidad de cálculo específica:

### 2.1. Ratio
- **Descripción**: Calcula el porcentaje de variación entre el valor inicial del índice (fecha de inicio de contrato) y el valor en la fecha o período del ajuste.
- **Ejemplos**: ICL, UVA, CREEBA, CVS.
- **Fórmula**:
  ```
  incremento (%) = ((valor_final - valor_inicial) / valor_inicial) * 100
  ```
- **Notas**:
  - Para índices **mensuales**, el valor final debe corresponder **exactamente al mes del ajuste**.
  - Para índices **diarios**, el valor final debe ser el más reciente **dentro de una tolerancia de antigüedad** configurable (`INDEX_MAX_AGE_DAYS`, por defecto 15 días).

### 2.2. Cadena Multiplicativa (Multiplicative Chain)
- **Descripción**: Multiplica una secuencia de coeficientes mensuales o diarios para obtener un factor acumulado.
- **Ejemplos**: CVS (Casa Propia).
- **Fórmula**:
  ```
  coeficiente acumulado = coef_1 * coef_2 * ... * coef_n
  ```
- **Notas**:
  - Valida que **no falten períodos intermedios**.
  - En índices diarios, también aplica la validación de antigüedad del último valor.

### 2.3. Porcentaje
- **Descripción**: El valor del índice representa directamente el porcentaje de ajuste aplicado.
- **Ejemplos**: Ajustes pactados con porcentajes fijos.
- **Fórmula**:
  ```
  nuevo_monto = monto_actual * (1 + porcentaje / 100)
  ```

---

## 3. Validaciones Clave

- **Valores faltantes:** El cálculo de ajustes se bloquea si faltan períodos intermedios en índices de tipo `multiplicative_chain` o si no hay valor para el mes exacto en índices `ratio` mensuales.
- **Antigüedad en índices diarios:** Se valida que el último valor no esté más viejo que `INDEX_MAX_AGE_DAYS` (15 días por defecto, configurable en `.env`).
- **Duplicados:** No se permiten dos valores para el mismo índice en la misma fecha o período.
- **Formato de fecha:** 
  - Índices diarios: requieren fecha exacta (`YYYY-MM-DD`).
  - Índices mensuales: requieren período (`YYYY-MM`).

---

## 4. Frecuencia de los Índices
- **Diaria:** valores publicados con fecha exacta (ej. UVA, ICL).
- **Mensual:** valores publicados por período mensual (ej. CREEBA, CVS).
- Cada tipo de índice define su frecuencia al crearse y esto determina cómo se asignan sus valores.

---

## 5. Metodología de Aplicación

| Tipo                      | Desde                   | Hasta           | Inclusión                      | Fórmula de cálculo                  |
| ------------------------- | ----------------------- | --------------- | ------------------------------ | ----------------------------------- |
| **Ratio**                 | Fecha de inicio         | Fecha de ajuste | Ambas incluidas                | `(final - inicial) / inicial * 100` |
| **Cadena Multiplicativa** | Mes posterior al inicio | Mes del ajuste  | Excluye inicio, incluye ajuste | `coef_1 * coef_2 * ... * coef_n`    |
| **Porcentaje**            | -                       | -               | Valor directo                  | `monto * (1 + porcentaje / 100)`    |

---

## 6. Integración con Ajustes de Contrato

- Cada ajuste de tipo `index` está vinculado a un tipo de índice.
- El comando automático `assign-index-values`:
  - Calcula el valor correspondiente según tipo, frecuencia y metodología.
  - Aplica validaciones de antigüedad y de datos faltantes.
- **Importante**: Si el índice no cuenta con datos válidos para el período requerido, el ajuste queda **pendiente**.

---

## 7. Ejemplos de Índices Cargados

| Código | Nombre                          | Cálculo  | Frecuencia | Ejemplo de valor    |
| ------ | ------------------------------- | -------- | ---------- | ------------------- |
| UVA    | Unidad de Valor Adquisitivo     | Ratio    | Diaria     | 1487.4700           |
| ICL    | Índice de Contratos de Locación | Ratio    | Diaria     | 3.1543              |
| CVS    | Casa Propia (CVS)               | Ratio    | Mensual    | 1.0671 (marzo 2025) |
| CREEBA | Costo de Vida Bahía Blanca      | Ratio    | Mensual    | 451.8               |
| AJ-1   | Ajuste Pactado 10%              | Porcentaje| Manual    | 10.00               |

---

## 8. Sincronización Automática

- **UVA e ICL**: sincronizados automáticamente desde los archivos oficiales del BCRA:
  - [diar_icl.xls](https://www.bcra.gob.ar/Pdfs/PublicacionesEstadisticas/diar_icl.xls)
  - [diar_uva.xls](https://www.bcra.gob.ar/Pdfs/PublicacionesEstadisticas/diar_uva.xls)
- **CREEBA y CVS**: requieren carga manual o integración con fuentes externas.

---

## 9. Carga Manual y Validaciones

La carga manual incluye:
- Selección del tipo de índice.
- Fecha (diarios) o período (mensuales).
- Valor numérico positivo.
- Validación automática contra duplicados y coherencia de períodos.

---

## 10. Configuraciones

- **INDEX_MAX_AGE_DAYS:** Define la tolerancia en días para considerar un valor de índice diario como válido.
  - Configurable en `.env`:
    ```
    INDEX_MAX_AGE_DAYS=15
    ```
  - Usado en cálculos de `ratio` y `multiplicative_chain` para índices diarios.

---

## 11. Errores y Casos Bloqueados

| Mensaje                                                         | Causa                                   | Solución                                                                 |
| --------------------------------------------------------------- | --------------------------------------- | ------------------------------------------------------------------------ |
| "No se encontró valor de índice para la fecha/período"          | Falta carga del valor                   | Cargar el valor faltante para la fecha/período.                         |
| "Valor diario demasiado antiguo"                                | Último valor cargado está fuera de rango| Actualizar valores del índice antes de aplicar ajustes.                 |
| "Faltan valores de índice para períodos intermedios"            | Cadena multiplicativa incompleta        | Completar carga de valores faltantes.                                   |

---
