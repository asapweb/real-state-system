# Módulo de Ajustes de Contrato

Este módulo permite gestionar los ajustes de alquiler asociados a un contrato, ya sea de forma manual (negociada o fija) o automática a través de índices oficiales como ICL, ICP, UVA y otros índices personalizados.

---

## 1. Objetivo

Garantizar la correcta actualización del valor de alquiler a lo largo del tiempo, con trazabilidad, reglas de validación estrictas y control sobre la aplicación de ajustes. El sistema soporta tanto ajustes manuales como automáticos basados en índices oficiales del BCRA e INDEC.

---

## 2. Tipos de Ajustes

| Tipo        | Descripción                                                  | Modo de Cálculo |
|-------------|--------------------------------------------------------------|------------------|
| `index`     | Ajuste automático por índice oficial (ICL, ICP, UVA, etc.)  | Ratio/Percentage |
| `percentage`| Ajuste por porcentaje manual                                 | Percentage       |
| `fixed`     | Valor fijo pactado como nuevo monto de alquiler             | Fijo             |
| `negotiated`| Valor negociado sin fórmula matemática                      | Fijo             |

---

## 3. Tipos de Índices

### 3.1. IPC (Índice de Precios al Consumidor)
- **Fuente**: INDEC (Instituto Nacional de Estadística y Censos)
- **URL**: https://www.indec.gob.ar/indec/web/Nivel4-Tema-3-9-31
- **Modo de cálculo**: `percentage`
- **Frecuencia**: `monthly`
- **Descripción**: Índice que mide la variación de precios al consumidor
- **Comando de importación**: `php artisan indices:import-ipc-excel`

### 3.2. ICL (Índice de Contratos de Locación)
- **Fuente**: INDEC
- **URL**: https://www.indec.gob.ar/indec/web/Nivel4-Tema-3-9-32
- **Modo de cálculo**: `ratio`
- **Frecuencia**: `daily`
- **Descripción**: Índice que mide el Contratos de Locación
- **Comando de importación**: `php artisan indices:import-icl-excel`

### 3.3. ICP (Índice de Contratos de Locación)
- **Fuente**: INDEC
- **URL**: https://www.indec.gob.ar/indec/web/Nivel4-Tema-3-9-33
- **Modo de cálculo**: `ratio`
- **Frecuencia**: `daily`
- **Descripción**: Índice que mide el Contratos de Locación
- **Comando de importación**: `php artisan indices:import-icp-excel`

### 3.4. UVA (Unidad de Valor Adquisitivo)
- **Fuente**: BCRA (Banco Central de la República Argentina)
- **URL**: https://www.bcra.gob.ar/Pdfs/PublicacionesEstadisticas/diar_uva.xls
- **Modo de cálculo**: `ratio`
- **Frecuencia**: `daily`
- **Descripción**: Unidad de valor utilizada para créditos hipotecarios y ajustes
- **Comando de importación**: `php artisan indices:import-uva-excel`

### 4.3. Casa Propia Mode
- **Aplicación**: `nuevo_alquiler = alquiler_base * coeficiente_casa_propia`
- **Ejemplo**: Si el coeficiente Casa Propia es 1.15, el alquiler se multiplica por 1.15
- **Uso**: Para ajustes específicos que requieren el coeficiente Casa Propia
- **Formato**: Fecha y valor del coeficiente (similar a ratio pero con propósito específico)

### 4.4. Multiplicative Chain Mode (Casa Propia)
- **Aplicación**: `nuevo_alquiler = alquiler_base * (coef1 * coef2 * coef3 * ...)`
- **Ejemplo**: Si los coeficientes mensuales son 1.04, 1.05, 1.06, el resultado es 1.04 * 1.05 * 1.06 = 1.15752
- **Uso**: Para índices como "Casa Propia" que requieren multiplicar coeficientes mensuales entre sí
- **Formato**: Períodos mensuales (YYYY-MM) con coeficientes que se multiplican en cadena
- **Metodología Oficial** (solo para multiplicative_chain): 
  - ✅ **Excluye el mes de inicio del contrato**
  - ✅ **Incluye el mes del ajuste**
  - ✅ **Incluye todos los meses intermedios**
- **Ejemplo Funcional**:
  - Contrato inicia: 2024-10-01
  - Fecha de ajuste: 2025-04-01
  - Meses considerados: nov-2024, dic-2024, ene-2025, feb-2025, mar-2025, abr-2025 (6 coeficientes)
  - Mes excluido: oct-2024 (mes de inicio)
- **Nota**: Esta exclusión del primer mes **solo aplica al modo multiplicative_chain**. Otros modos de cálculo incluyen todos los meses entre las fechas.

---

## 4. Modos de Cálculo

### 4.1. Ratio Mode
- **Aplicación**: `nuevo_alquiler = alquiler_base * valor_indice`
- **Ejemplo**: Si el ICL pasó de 100 a 120, el alquiler se multiplica por 1.2
- **Uso**: Índices como ICL, ICP, UVA que representan coeficientes directos

### 4.2. Percentage Mode
- **Aplicación**: `nuevo_alquiler = alquiler_base * (1 + porcentaje / 100)`
- **Ejemplo**: Si el IPC es 5%, el alquiler se incrementa un 5%
- **Uso**: Índices que representan porcentajes de variación

### 4.3. Casa Propia Mode
- **Aplicación**: `nuevo_alquiler = alquiler_base * coeficiente_casa_propia`
- **Ejemplo**: Si el coeficiente Casa Propia es 1.15, el alquiler se multiplica por 1.15
- **Uso**: Para ajustes específicos que requieren el coeficiente Casa Propia
- **Formato**: Fecha y valor del coeficiente (similar a ratio pero con propósito específico)

### 4.4. Multiplicative Chain Mode
- **Aplicación**: `nuevo_alquiler = alquiler_base * (coef1 * coef2 * coef3 * ...)`
- **Ejemplo**: Si los coeficientes mensuales son 1.03, 1.04, 1.05, el resultado es 1.03 * 1.04 * 1.05 = 1.12476
- **Uso**: Para índices como "Casa Propia" que requieren multiplicar coeficientes mensuales entre sí
- **Formato**: Períodos mensuales (YYYY-MM) con coeficientes que se multiplican en cadena

---

## 5. Frecuencias de Índices

### 5.1. Daily Frequency
- **Descripción**: Valores de índice disponibles por fecha específica
- **Uso**: Para índices que se publican diariamente
- **Ejemplos**: ICL, ICP, UVA
- **Búsqueda**: Por fecha exacta (`date`)

### 5.2. Monthly Frequency
- **Descripción**: Valores de índice disponibles por mes
- **Uso**: Para índices que se publican mensualmente
- **Ejemplos**: IPC, CREEBBA, CASA_PROPIA
- **Búsqueda**: Por período (`period` en formato YYYY-MM)

---

## 5. Aplicación de Ajustes

### 5.1. Modo PERCENTAGE
- **Fórmula**: `nuevo_alquiler = alquiler_base * (1 + porcentaje / 100)`
- **Ejemplo**: Si el porcentaje es 5%, el alquiler se incrementa un 5%
- **Aplicación en sistema**: `$appliedAmount = $monthlyAmount * (1 + $adjustmentValue / 100)`

### 5.2. Modo RATIO
- **Fórmula**: `nuevo_alquiler = alquiler_base * (1 + porcentaje / 100)`
- **Ejemplo**: Si el índice varió 5%, el alquiler se incrementa un 5%
- **Aplicación en sistema**: `$appliedAmount = $monthlyAmount * (1 + $adjustmentValue / 100)`
- **Nota**: El valor ya es un porcentaje calculado de la variación del índice

### 5.3. Modo MULTIPLICATIVE_CHAIN (Casa Propia)
- **Fórmula**: `nuevo_alquiler = alquiler_base * coeficiente_multiplicativo`
- **Ejemplo**: Si el coeficiente es 1.15752, el alquiler se multiplica por 1.15752
- **Aplicación en sistema**: `$appliedAmount = $monthlyAmount * $adjustmentValue`
- **Nota**: El valor es un coeficiente multiplicativo directo (ej: 1.15752)

### 5.4. Otros Tipos de Ajuste
- **PERCENTAGE**: `$appliedAmount = $monthlyAmount * (1 + $adjustmentValue / 100)`
- **FIXED/NEGOTIATED**: `$appliedAmount = $adjustmentValue` (reemplaza el monto mensual)

---

## 6. Logs del Sistema

El sistema registra logs detallados para cada aplicación de ajuste:

```
🚀 Iniciando aplicación de ajuste
📋 Información del contrato para aplicación
🔗 Aplicando ajuste de índice en modo MULTIPLICATIVE_CHAIN (Casa Propia)
✅ Cálculo completado
  - original_amount: 100000
  - applied_amount: 115752
  - difference: 15752
💾 Ajuste aplicado exitosamente
```

---

## 7. Flujo de Funcionamiento

### 7.1. Creación del ajuste
- Se registra un nuevo ajuste con:
  - Contrato asociado
  - Fecha de vigencia (`effective_date`)
  - Tipo de ajuste
  - Valor del ajuste (puede ser null si aún no se conoce)
  - Índice aplicado (solo si `type = index`)

> El sistema permite cargar un ajuste con valor `value` ya definido o dejarlo en null a la espera de asignación automática.

### 7.2. Asignación de valores de índice (automática)
- Cada mes, el sistema ejecuta un proceso automático (cron) que:
  - Busca ajustes de tipo `index` con `value = null`
  - Busca el valor publicado correspondiente en `index_values` según el `index_type_id` y el mes
  - Asigna el valor calculado al campo `value` del ajuste

**Para modo ratio**: Calcula el promedio de valores entre `start_date` del contrato y `effective_date` del ajuste, luego calcula el porcentaje de variación.

**Para modo percentage**: Toma directamente el valor del índice correspondiente al mes del ajuste.

### 7.3. Aplicación del ajuste
- Un ajuste puede aplicarse manualmente desde el frontend si:
  - Tiene `value` definido
  - `applied_at` es null
  - No existe una **cobranza emitida (voucher tipo 'COB')** para el mismo contrato y mes
  - No existe otro ajuste **aplicado** para ese contrato y mes

> Al aplicar el ajuste, se calcula el nuevo valor de alquiler (`applied_amount`) y se marca la fecha de aplicación (`applied_at`). Opcionalmente se actualiza el contrato (`monthly_amount`).

---

## 8. Restricciones

- ❌ No se puede editar ni eliminar un ajuste ya aplicado.
- ❌ No se puede aplicar un ajuste si ya existe otro ajuste aplicado en ese mes.
- ❌ No se puede aplicar si ya existe un voucher emitido de tipo "COB" para el contrato y período.

---

## 9. Interfaz de Usuario

### 9.1. Desde el contrato
- Visualización de ajustes en pestaña específica
- Botón "+ nuevo ajuste"
- Botón "Aplicar ajuste" si corresponde
- Indicador visual de "Ajuste aplicado" y monto resultante

### 9.2. Vista global de ajustes
- Filtros por contrato, tipo, estado (`pending`, `expired_without_value`, `with_value`, `applied`)
- Acciones disponibles por ajuste según estado
- Tabla paginada con columnas: fecha, tipo, índice, valor, aplicado, monto final, notas

---

## 10. Entidades Relacionadas

- `ContractAdjustment`
- `IndexType` (tipos de índice con modo de cálculo)
- `IndexValue` (valores por índice, fecha y modo)
- `Voucher` (emisión de cobranzas)

---

## 11. Comandos de Sincronización

### 11.1. Importación de Índices
```bash
# Importar ICL desde Excel oficial del BCRA
php artisan indices:import-icl-excel --dry-run
php artisan indices:import-icl-excel

# Importar ICP desde Excel oficial del INDEC
php artisan indices:import-icp-excel --dry-run
php artisan indices:import-icp-excel

# Importar UVA desde Excel oficial del BCRA
php artisan indices:import-uva-excel --dry-run
php artisan indices:import-uva-excel
```

### 11.2. Asignación de Valores
```bash
# Asignar valores de índice a ajustes pendientes
php artisan adjustments:assign-index-values
```

### 11.3. Generación de Datos de Prueba
```bash
# Generar datos de prueba para desarrollo
php artisan indices:generate-test-icl-data --months=24
php artisan indices:generate-test-icp-data --months=24
php artisan indices:generate-test-uva-data --months=24
```

---

## 12. Automatizaciones

- Comando `adjustments:assign-index-values` (mensual)
- Validaciones automáticas al aplicar:
  - Duplicidad por mes
  - Conflicto con vouchers
- Cron mensual programado en `Kernel.php`

---

## 13. Casos Especiales

- ✅ Si un ajuste es creado con `value` definido y `effective_date` ya vencida, el sistema alerta pero **no aplica automáticamente**.
- ⚠️ Si se detecta error en un ajuste ya aplicado, se recomienda:
  - Crear un nuevo ajuste correctivo con la misma fecha
  - Aplicar solo el nuevo
  - Documentar en `notes` qué reemplaza

---

## 14. Futuras mejoras

- Notificación automática al inquilino cuando se aplica un ajuste
- Vista de historial de alquileres por contrato
- Simulación o previsualización de ajustes antes de aplicar
- Soporte para más índices oficiales
- Dashboard de seguimiento de índices
