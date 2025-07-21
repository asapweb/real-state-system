# Módulo: Gestión de Índices

## 1. Objetivo

El módulo de **Gestión de Índices** permite administrar los valores de índices económicos utilizados para realizar ajustes contractuales en alquileres. Centraliza la carga, actualización, visualización y uso de los índices en los procesos automáticos o manuales de ajuste de contratos.

---

## 2. Tipos de Índices Soportados

El sistema soporta distintos tipos de índices, cada uno con una modalidad de cálculo específica:

### 2.1. Ratio

- **Descripción**: Compara el valor del índice entre dos fechas y calcula el incremento porcentual.
- **Ejemplos**: ICL, UVA, CREEBA.
- **Fórmula**:
  ```
  incremento (%) = ((valor_final - valor_inicial) / valor_inicial) * 100
  ```

### 2.2. Cadena Multiplicativa (Multiplicative Chain)

- **Descripción**: Multiplica una secuencia de coeficientes mensuales para obtener un factor acumulado.
- **Ejemplos**: CVS (Casa Propia).
- **Fórmula**:
  ```
  coeficiente acumulado = coef_1 * coef_2 * ... * coef_n
  ```

### 2.3. Porcentaje

- **Descripción**: El valor del índice representa directamente el porcentaje de ajuste aplicado.
- **Ejemplos**: Ajustes manuales pactados como porcentaje fijo.
- **Fórmula**:
  ```
  nuevo_monto = monto_actual * (1 + porcentaje / 100)
  ```

---

## 3. Funcionalidades del Módulo

- Alta, edición y baja de tipos de índice.
- Carga de valores históricos o actuales (manual, importación, API).
- Consulta y edición de valores por fecha o período.
- Visualización tabular y gráfica de la evolución del índice.
- Asignación automática de valores de índice a ajustes contractuales pendientes.
- Validaciones:
  - Detección de valores faltantes
  - Coherencia de fechas/períodos
  - Duplicados

---

## 4. Frecuencia de los Índices

- **Diaria**: valores por fecha exacta (ej. UVA, ICL)
- **Mensual**: valores por período (ej. CVS - Casa Propia, CREEBA)
- **Otro**: posibilidad de definir nuevas frecuencias personalizadas

---

## 5. Metodología de Aplicación

La forma de aplicar los valores depende del tipo de índice:

| Tipo                      | Desde                   | Hasta           | Inclusión                      | Fórmula de cálculo                  |
| ------------------------- | ----------------------- | --------------- | ------------------------------ | ----------------------------------- |
| **Ratio**                 | Fecha de inicio         | Fecha de ajuste | Ambas incluidas                | `(final - inicial) / inicial * 100` |
| **Cadena Multiplicativa** | Mes posterior al inicio | Mes del ajuste  | Excluye inicio, incluye ajuste | `coef_1 * coef_2 * ... * coef_n`    |
| **Porcentaje**            | -                       | -               | Valor directo                  | `monto * (1 + porcentaje / 100)`    |

---

## 6. Integración con Ajustes de Contrato

- Cada ajuste contractual con tipo `index` referencia un tipo de índice.
- El sistema permite ejecutar el comando `assign-index-values` para calcular y asignar automáticamente el valor correspondiente, en función del tipo y metodología del índice.
- Una vez asignado el valor, se puede aplicar para calcular el nuevo monto contractual.

---

## 7. Ejemplos de Índices Cargados

| Código | Nombre                          | Tipo                  | Frecuencia | Ejemplo de valor    |
| ------ | ------------------------------- | --------------------- | ---------- | ------------------- |
| UVA    | Unidad de Valor Adquisitivo     | Ratio                 | Diaria     | 1487.4700           |
| ICL    | Índice de Contratos de Locación | Ratio                 | Diaria     | 3.1543              |
| CVS    | Casa Propia (CVS)               | Cadena Multiplicativa | Mensual    | 1.0671 (marzo 2025) |
| CREEBA | Costo de Vida Bahía Blanca      | Ratio                 | Mensual    | 451.8               |
| AJ-1   | Ajuste Pactado 10%              | Porcentaje            | Manual     | 10.00               |

---

## 8. Sincronización Automática

Los índices **UVA** e **ICL** se sincronizan automáticamente utilizando archivos oficiales del BCRA:

- [diar\_icl.xls](https://www.bcra.gob.ar/Pdfs/PublicacionesEstadisticas/diar_icl.xls)
- [diar\_uva.xls](https://www.bcra.gob.ar/Pdfs/PublicacionesEstadisticas/diar_uva.xls)

El sistema cuenta con una pantalla que permite:

- Ejecutar el comando de sincronización en modo **Dry Run** (muestra qué se importaría sin guardar).
- Habilitar la opción **Forzar importación**, para cargar valores aunque ya existan previamente en la base de datos.

---

## 9. Carga Manual de Índices

Además de la sincronización automática, el sistema permite la carga manual de valores de índices como **CREEBA**, **CVS** u otros definidos por el usuario. Esta funcionalidad incluye:

- Selección del tipo de índice.
- Ingreso del valor correspondiente a una fecha o período.
- Edición y eliminación de registros existentes.
- Validaciones automáticas de integridad y duplicados.

---

## 10. Consideraciones Finales

- Los tipos de índice y sus valores deben ser gestionados por un usuario con permisos de administración.
- Se recomienda validar que los datos estén completos antes de ejecutar procesos automáticos de ajuste.
- Se podrán integrar fuentes oficiales para automatizar la carga de valores (ej. BCRA, INDEC, CREEBA).

---

## Glosario

**Índice:**  Valor numérico que representa la evolución de una variable económica (ejemplo: inflación, costo de vida, etc.).

**Ratio:**  Método de ajuste que compara el valor de un índice entre dos fechas para calcular el incremento porcentual.

**Cadena Multiplicativa:**  Método que multiplica coeficientes mensuales para obtener un factor acumulado de ajuste.

**Porcentaje:**  Ajuste directo aplicando un porcentaje fijo sobre el monto.

**Frecuencia:**  Periodicidad con la que se publica el valor del índice (diaria, mensual, etc.).

---

## Paso a Paso: Carga Manual de un Valor de Índice

1. Ingresa a la sección **Gestión de Índices**.
2. Haz clic en **Nuevo Valor**.
3. Selecciona el **Tipo de Índice**.
4. Ingresa la **fecha** (para índices diarios) o el **período** (para índices mensuales).
5. Ingresa el **valor** correspondiente.
6. Haz clic en **Guardar**.
7. El sistema validará automáticamente que no existan duplicados y que el formato sea correcto.

---

## Errores Comunes y Soluciones

| Mensaje de Error                                              | Causa Probable                                 | Solución Sugerida                                 |
|--------------------------------------------------------------|------------------------------------------------|---------------------------------------------------|
| Ya existe un valor para este tipo de índice en el periodo... | Intentas cargar un valor ya existente          | Edita el valor existente o elige otro período      |
| El formato debe ser YYYY-MM (ej: 2025-07)                    | El período no tiene el formato correcto        | Usa el formato año-mes, por ejemplo: 2025-07      |
| El valor debe ser un número positivo                         | El campo valor está vacío o es negativo        | Ingresa un número mayor a cero                     |
| El campo date es requerido y debe ser una fecha válida       | No se ingresó una fecha o el formato es inválido| Usa el formato año-mes-día, por ejemplo: 2025-07-01|

---

## Preguntas Frecuentes (FAQ)

**¿Qué pasa si me olvido de cargar un valor de índice?**  
El sistema notificará la ausencia de valores al intentar aplicar ajustes. Puedes cargar el valor faltante en cualquier momento.

**¿Puedo deshacer una importación automática?**  
No directamente, pero puedes editar o eliminar los valores importados manualmente si tienes permisos de administración.

**¿Cómo sé si un valor fue importado automáticamente o manualmente?**  
En la tabla de valores de índice, puedes ver la columna de auditoría que indica el origen y la fecha de carga.

---

## Enlaces Útiles

- [BCRA - Índices Oficiales](https://www.bcra.gob.ar/)
- [INDEC - Estadísticas](https://www.indec.gob.ar/)
- [Ayuda del sistema (interna)](enlace-a-la-ayuda-interna)

