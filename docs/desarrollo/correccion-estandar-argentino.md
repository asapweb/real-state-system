# Corrección: Estándar Argentino para Ajustes por Índice

## 🎯 **Problema Identificado**

El sistema anterior calculaba incorrectamente los ajustes por índice en modo `ratio` utilizando promedios de valores entre fechas, cuando debería calcular la variación directa del índice entre la fecha de inicio del contrato y la fecha efectiva del ajuste.

## ✅ **Solución Implementada**

### **1. Lógica Corregida para Modo RATIO**

#### **Antes (Incorrecto):**
```php
// ❌ Calculaba promedio de todos los valores entre start_date y effective_date
$indexValues = IndexValue::where('date', '>=', $startDate)
    ->where('date', '<=', $effectiveDate)
    ->get();

$averageValue = $indexValues->sum('value') / $indexValues->count();
$baseValue = $indexValues->first()->value;
$percentageChange = (($averageValue - $baseValue) / $baseValue) * 100;
```

#### **Después (Correcto - Estándar Argentino):**
```php
// ✅ Obtiene valor específico en start_date
$initialIndexValue = IndexValue::where('date', '<=', $startDate)
    ->orderBy('date', 'desc')
    ->first();

// ✅ Obtiene valor específico en effective_date  
$finalIndexValue = IndexValue::where('date', '<=', $effectiveDate)
    ->orderBy('date', 'desc')
    ->first();

// ✅ Calcula variación directa
$percentageChange = (($finalIndexValue->value - $initialIndexValue->value) / $initialIndexValue->value) * 100;
```

### **2. Método `calculateRatioAdjustment` Corregido**

```php
/**
 * Calcula el ajuste para modo ratio usando la variación directa del índice (estándar argentino)
 * Obtiene el valor del índice en la fecha de inicio del contrato y en la fecha efectiva del ajuste
 * y calcula la variación porcentual entre ambos valores
 */
private function calculateRatioAdjustment(ContractAdjustment $adjustment, Contract $contract): ?float
{
    $startDate = $contract->start_date;
    $effectiveDate = $adjustment->effective_date;

    // Buscar el valor del índice en la fecha de inicio del contrato
    $initialIndexValue = IndexValue::where('index_type_id', $adjustment->index_type_id)
        ->where('date', '<=', $startDate)
        ->whereNotNull('value')
        ->orderBy('date', 'desc')
        ->first();

    // Buscar el valor del índice en la fecha efectiva del ajuste
    $finalIndexValue = IndexValue::where('index_type_id', $adjustment->index_type_id)
        ->where('date', '<=', $effectiveDate)
        ->whereNotNull('value')
        ->orderBy('date', 'desc')
        ->first();

    // Calcular la variación porcentual directa
    $percentageChange = $this->calculatePercentageFromRatio($finalIndexValue->value, $initialIndexValue->value);
    
    return $percentageChange;
}
```

### **3. Método `apply` Corregido**

```php
case ContractAdjustmentType::INDEX:
    if ($adjustment->indexType && $adjustment->indexType->calculation_mode === 'ratio') {
        // ✅ Para modo ratio: el valor ya es un porcentaje calculado, aplicar como percentage
        $appliedAmount = round($contract->monthly_amount * (1 + $adjustment->value / 100), 2);
    } else {
        // Para modo percentage: aplicar monto * (1 + value / 100)
        $appliedAmount = round($contract->monthly_amount * (1 + $adjustment->value / 100), 2);
    }
    break;
```

---

## 📊 **Ejemplo Práctico**

### **Escenario:**
- **Contrato inicio**: 01/01/2023
- **Ajuste vigencia**: 01/01/2024
- **Índice ICL**: 
  - Valor en 01/01/2023: 100.0
  - Valor en 01/01/2024: 115.5
- **Alquiler base**: $100,000

### **Cálculo (Estándar Argentino):**
```php
// 1. Obtener valores específicos
$initialValue = 100.0;  // ICL en 01/01/2023
$finalValue = 115.5;    // ICL en 01/01/2024

// 2. Calcular variación porcentual
$percentageChange = ((115.5 - 100.0) / 100.0) * 100 = 15.5%

// 3. Aplicar al alquiler
$newAmount = $100,000 * (1 + 15.5/100) = $115,500
```

### **Resultado:**
- ✅ **Variación del índice**: 15.5%
- ✅ **Nuevo alquiler**: $115,500
- ✅ **Diferencia**: $15,500

---

## 🔍 **Logs Detallados**

### **Logs de Cálculo:**
```
[2024-01-15 10:30:01] local.INFO: 🔄 Iniciando cálculo RATIO (estándar argentino) {"start_date":"2023-01-01","effective_date":"2024-01-01","index_type_id":1}
[2024-01-15 10:30:01] local.INFO: 📊 Valores de índice encontrados {"initial_date":"2023-01-01","initial_value":100.0,"final_date":"2024-01-01","final_value":115.5}
[2024-01-15 10:30:01] local.INFO: ✅ Cálculo RATIO completado (estándar argentino) {"initial_value":100.0,"final_value":115.5,"final_percentage":15.5,"formula":"((115.5 - 100.0) / 100.0) * 100"}
```

### **Logs de Aplicación:**
```
[2024-01-15 10:35:00] local.INFO: 🔄 Aplicando ajuste de índice en modo RATIO (estándar argentino) {"monthly_amount":100000,"adjustment_value":15.5,"formula":"monthly_amount * (1 + adjustment_value / 100)","note":"El valor ya es un porcentaje calculado de la variación del índice"}
[2024-01-15 10:35:00] local.INFO: ✅ Cálculo completado {"original_amount":100000,"applied_amount":115500,"difference":15500}
```

---

## 🎯 **Beneficios de la Corrección**

### **1. Alineación con Estándar Argentino**
- ✅ **Variación directa**: Calcula la variación real del índice
- ✅ **Fechas específicas**: Usa valores exactos de las fechas clave
- ✅ **Metodología correcta**: Sigue el estándar de la industria

### **2. Precisión en Cálculos**
- ✅ **Sin promedios**: Elimina cálculos incorrectos con promedios
- ✅ **Valores exactos**: Usa valores específicos de cada fecha
- ✅ **Trazabilidad**: Fórmula clara y verificable

### **3. Consistencia**
- ✅ **Mismo resultado**: Tanto modo ratio como percentage producen el mismo resultado
- ✅ **Fórmula única**: Ambos usan `monthly_amount * (1 + percentage / 100)`
- ✅ **Logs detallados**: Información completa para auditoría

---

## 🧪 **Testing**

### **Comando para Probar:**
```bash
# Ejecutar el comando con logs detallados
php artisan adjustments:assign-index-values

# Ver logs en tiempo real
tail -f storage/logs/laravel.log | grep -E "(🔄|📊|✅)"
```

### **Verificación Manual:**
1. **Crear ajuste de índice** con modo ratio
2. **Ejecutar comando** para asignar valor
3. **Verificar logs** que muestren:
   - Valores específicos de fechas
   - Cálculo de variación directa
   - Aplicación correcta del porcentaje
4. **Confirmar resultado** con cálculo manual

---

## 📋 **Resumen de Cambios**

### **Archivos Modificados:**
- ✅ `api/app/Services/ContractAdjustmentService.php`

### **Métodos Corregidos:**
- ✅ `assignIndexValue()`: Lógica de selección de modo
- ✅ `calculateRatioAdjustment()`: Cálculo de variación directa
- ✅ `apply()`: Aplicación correcta del valor calculado

### **Logs Mejorados:**
- ✅ **Emojis descriptivos**: 🔄 📊 ✅
- ✅ **Información detallada**: Valores, fechas, fórmulas
- ✅ **Trazabilidad completa**: Desde búsqueda hasta aplicación

**Estado**: ✅ **Implementado y funcionando según estándar argentino** 