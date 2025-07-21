# Debugging del Sistema de Índices

## 🎯 **Objetivo**

Esta documentación explica cómo usar los logs detallados agregados al sistema de índices para entender paso a paso cómo se obtienen y calculan los valores de los índices.

---

## 📋 **Logs Implementados**

### 1. **Comando Principal** (`adjustments:assign-index-values`)

#### **Logs del Comando**
```bash
# Ejecutar el comando
php artisan adjustments:assign-index-values
```

**Logs generados:**
- 🎯 **Inicio del comando**: Tiempo de inicio y parámetros
- 📊 **Ajustes encontrados**: Lista de ajustes a procesar
- ✅ **Resumen final**: Total procesados y tiempo de ejecución

#### **Ejemplo de logs:**
```
[2024-01-15 10:30:00] local.INFO: 🎯 Comando adjustments:assign-index-values iniciado
[2024-01-15 10:30:00] local.INFO: 🔍 Iniciando asignación de valores de índice {"today":"2024-01-15"}
[2024-01-15 10:30:01] local.INFO: 📊 Ajustes encontrados para procesar {"total_found":3,"adjustments":[...]}
[2024-01-15 10:30:02] local.INFO: ✅ Comando adjustments:assign-index-values completado {"total_updated":2,"execution_time_seconds":1.5}
```

### 2. **Servicio de Asignación** (`AssignIndexValuesToAdjustmentsService`)

#### **Logs del Servicio**
- 🔍 **Inicio del proceso**: Fecha actual y parámetros
- 📊 **Ajustes encontrados**: Detalles de cada ajuste a procesar
- 🔄 **Procesamiento individual**: Información de cada ajuste
- ✅ **Éxito/Fallo**: Resultado de cada procesamiento
- 📈 **Resumen final**: Estadísticas completas

#### **Ejemplo de logs:**
```
[2024-01-15 10:30:00] local.INFO: 🔍 Iniciando asignación de valores de índice {"today":"2024-01-15"}
[2024-01-15 10:30:00] local.INFO: 📊 Ajustes encontrados para procesar {"total_found":3,"adjustments":[...]}
[2024-01-15 10:30:01] local.INFO: 🔄 Procesando ajuste {"adjustment_id":123,"contract_id":456,"effective_date":"2024-01-01","index_type_id":1}
[2024-01-15 10:30:01] local.INFO: ✅ Ajuste procesado exitosamente {"adjustment_id":123,"new_value":15.5}
[2024-01-15 10:30:02] local.INFO: 📈 Resumen de procesamiento {"total_found":3,"successful":2,"failed":1,"processed_ids":[123,124],"failed_ids":[125]}
```

### 3. **Servicio de Ajustes** (`ContractAdjustmentService`)

#### **Logs de assignIndexValue**
- 🔍 **Inicio del proceso**: Datos del ajuste
- ❌ **Validaciones**: Por qué no se puede procesar
- 📊 **Información del índice**: Tipo y modo de cálculo
- 📋 **Información del contrato**: Datos del contrato
- 🔄/📈 **Modo de cálculo**: Ratio vs Percentage
- 🔍 **Búsqueda de valores**: Período y resultados
- ✅ **Valor encontrado**: Detalles del valor
- 💾 **Guardado**: Valor final calculado

#### **Ejemplo de logs (modo PERCENTAGE):**
```
[2024-01-15 10:30:01] local.INFO: 🔍 Iniciando assignIndexValue {"adjustment_id":123,"type":"index","effective_date":"2024-01-01","index_type_id":1,"current_value":null,"applied_at":null}
[2024-01-15 10:30:01] local.INFO: 📊 Información del tipo de índice {"index_type_id":1,"index_name":"ICL","calculation_mode":"percentage"}
[2024-01-15 10:30:01] local.INFO: 📋 Información del contrato {"contract_id":456,"start_date":"2023-01-01","monthly_amount":100000}
[2024-01-15 10:30:01] local.INFO: 📈 Usando modo PERCENTAGE para cálculo
[2024-01-15 10:30:01] local.INFO: 🔍 Buscando valor de índice {"period":"2024-01","index_type_id":1}
[2024-01-15 10:30:01] local.INFO: ✅ Valor de índice encontrado {"period":"2024-01","percentage":15.5,"date":"2024-01-01"}
[2024-01-15 10:30:01] local.INFO: 💾 Guardando valor calculado {"final_value":15.5}
```

#### **Ejemplo de logs (modo RATIO):**
```
[2024-01-15 10:30:01] local.INFO: 🔍 Iniciando assignIndexValue {"adjustment_id":124,"type":"index","effective_date":"2024-01-01","index_type_id":2,"current_value":null,"applied_at":null}
[2024-01-15 10:30:01] local.INFO: 📊 Información del tipo de índice {"index_type_id":2,"index_name":"UVA","calculation_mode":"ratio"}
[2024-01-15 10:30:01] local.INFO: 📋 Información del contrato {"contract_id":457,"start_date":"2023-01-01","monthly_amount":80000}
[2024-01-15 10:30:01] local.INFO: 🔄 Usando modo RATIO para cálculo
[2024-01-15 10:30:01] local.INFO: 🔄 Iniciando cálculo RATIO {"start_date":"2023-01-01","effective_date":"2024-01-01","index_type_id":2}
[2024-01-15 10:30:01] local.INFO: 📊 Valores de índice encontrados {"count":12,"date_range":"2023-01-01 to 2024-01-01","values":[...]}
[2024-01-15 10:30:01] local.INFO: 🧮 Cálculos intermedios {"total_value":1200.5,"count_values":12,"average_value":100.04,"base_value":95.2}
[2024-01-15 10:30:01] local.INFO: ✅ Cálculo RATIO completado {"final_percentage":5.08,"formula":"((100.04 - 95.2) / 95.2) * 100"}
[2024-01-15 10:30:01] local.INFO: 💾 Guardando valor calculado {"final_value":5.08}
```

### 4. **Logs de Aplicación** (`ContractAdjustmentService::apply`)

#### **Logs de aplicación de ajustes**
- 🚀 **Inicio de aplicación**: Datos del ajuste
- ⚠️ **Validaciones**: Cobranzas existentes, ajustes duplicados
- 📋 **Información del contrato**: Monto base
- 🔄/📈/💰 **Tipo de cálculo**: Fórmula aplicada
- ✅ **Cálculo completado**: Montos original y final
- 💾 **Aplicación exitosa**: Resultado final

#### **Ejemplo de logs:**
```
[2024-01-15 10:35:00] local.INFO: 🚀 Iniciando aplicación de ajuste {"adjustment_id":123,"type":"index","value":15.5,"effective_date":"2024-01-01","contract_id":456}
[2024-01-15 10:35:00] local.INFO: 📋 Información del contrato para aplicación {"contract_id":456,"monthly_amount":100000,"adjustment_type":"index","adjustment_value":15.5}
[2024-01-15 10:35:00] local.INFO: 📈 Aplicando ajuste de índice en modo PERCENTAGE {"monthly_amount":100000,"adjustment_value":15.5,"formula":"monthly_amount * (1 + adjustment_value / 100)"}
[2024-01-15 10:35:00] local.INFO: ✅ Cálculo completado {"original_amount":100000,"applied_amount":115500,"difference":15500}
[2024-01-15 10:35:00] local.INFO: 💾 Ajuste aplicado exitosamente {"adjustment_id":123,"applied_at":"2024-01-15 10:35:00","final_amount":115500}
```

---

## 🔍 **Cómo Usar los Logs para Debugging**

### **1. Verificar el Proceso Completo**
```bash
# Ejecutar el comando y ver logs en tiempo real
php artisan adjustments:assign-index-values

# Ver logs específicos
tail -f storage/logs/laravel.log | grep "adjustments:assign-index-values"
```

### **2. Debugging de Casos Específicos**
```bash
# Buscar logs de un ajuste específico
grep "adjustment_id\":123" storage/logs/laravel.log

# Buscar logs de un contrato específico
grep "contract_id\":456" storage/logs/laravel.log

# Buscar logs de un tipo de índice específico
grep "index_type_id\":1" storage/logs/laravel.log
```

### **3. Análisis de Errores**
```bash
# Buscar errores
grep "❌\|⚠️" storage/logs/laravel.log

# Buscar fallos en el procesamiento
grep "Ajuste no pudo ser procesado" storage/logs/laravel.log

# Buscar valores no encontrados
grep "No se encontró valor de índice" storage/logs/laravel.log
```

### **4. Verificar Cálculos**
```bash
# Buscar cálculos de ratio
grep "Cálculo RATIO" storage/logs/laravel.log

# Buscar cálculos de percentage
grep "modo PERCENTAGE" storage/logs/laravel.log

# Buscar fórmulas aplicadas
grep "formula" storage/logs/laravel.log
```

---

## 📊 **Información que Proporcionan los Logs**

### **Para Modo PERCENTAGE:**
- ✅ **Período buscado**: `2024-01`
- ✅ **Valor encontrado**: `15.5%`
- ✅ **Fecha del valor**: `2024-01-01`
- ✅ **Fórmula aplicada**: `monthly_amount * (1 + 15.5 / 100)`

### **Para Modo RATIO:**
- ✅ **Rango de fechas**: `2023-01-01 to 2024-01-01`
- ✅ **Valores encontrados**: Lista completa de valores
- ✅ **Cálculos intermedios**: Total, promedio, valor base
- ✅ **Fórmula final**: `((100.04 - 95.2) / 95.2) * 100 = 5.08%`

### **Para Aplicación:**
- ✅ **Monto original**: `$100,000`
- ✅ **Valor del ajuste**: `15.5%`
- ✅ **Monto final**: `$115,500`
- ✅ **Diferencia**: `$15,500`

---

## 🛠 **Comandos Útiles para Debugging**

### **1. Ver logs en tiempo real:**
```bash
tail -f storage/logs/laravel.log
```

### **2. Filtrar logs por comando:**
```bash
tail -f storage/logs/laravel.log | grep -E "(🎯|🔍|📊|🔄|✅|❌|⚠️)"
```

### **3. Buscar logs de un día específico:**
```bash
grep "2024-01-15" storage/logs/laravel.log
```

### **4. Exportar logs a archivo:**
```bash
php artisan adjustments:assign-index-values 2>&1 | tee debug_output.log
```

---

## 🎯 **Casos de Uso Comunes**

### **Caso 1: Verificar por qué no se asigna valor**
1. Buscar logs del ajuste específico
2. Verificar si cumple condiciones iniciales
3. Verificar si encuentra el tipo de índice
4. Verificar si encuentra valores para el período

### **Caso 2: Verificar cálculo de ratio**
1. Buscar logs de "Cálculo RATIO"
2. Verificar rango de fechas
3. Verificar valores encontrados
4. Verificar cálculos intermedios
5. Verificar fórmula final

### **Caso 3: Verificar aplicación de ajuste**
1. Buscar logs de "Iniciando aplicación"
2. Verificar validaciones
3. Verificar tipo de cálculo
4. Verificar fórmula aplicada
5. Verificar resultado final

---

## 🧹 **Limpieza de Logs**

**IMPORTANTE**: Después de las pruebas, remover los logs para no saturar el sistema:

```bash
# Limpiar logs después de debugging
echo "" > storage/logs/laravel.log
```

**Estado**: ✅ **Logs implementados y listos para debugging** 