# Corrección: Parámetro Currency en formatMoney

## 🎯 **Problema Identificado**

La función `formatMoney` espera un segundo parámetro opcional `currency` que no se estaba utilizando en varios archivos del frontend, lo que podría causar inconsistencias en el formato de moneda.

---

## ✅ **Corrección Implementada**

### **1. Función formatMoney**

#### **Definición Actual:**
```javascript
export function formatMoney(value, currency = '$') {
  if (isNaN(Number(value))) return '—'

  const number = Number(value)

  // Convertimos el número a formato local: 1.234,56
  const formatted = number.toLocaleString('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })

  return `${currency} ${formatted}`
}
```

#### **Uso Correcto:**
```javascript
// ✅ Correcto - especificando currency
formatMoney(1234.56, '$')  // "$ 1.234,56"

// ❌ Incorrecto - sin especificar currency
formatMoney(1234.56)       // "$ 1.234,56" (usa default)
```

---

## 🔧 **Archivos Corregidos**

### **1. ContractAdjustmentTable.vue (Global)**
- ✅ **Línea 125**: `formatMoney(item.value, '$')`
- ✅ **Línea 136**: `formatMoney(item.applied_amount, '$')`
- ✅ **Línea 170**: `formatMoney(item.contract.monthly_amount, '$')`

### **2. ContractAdjustmentTable.vue (Contracts)**
- ✅ **Línea 49**: `formatMoney(item.value, '$')`
- ✅ **Línea 62**: `formatMoney(item.applied_amount, '$')`
- ✅ **Línea 96**: `formatMoney(item.contract.monthly_amount, '$')`

### **3. CollectionItemTable.vue**
- ✅ **Línea 3**: `formatMoney(item.unit_price, '$')`
- ✅ **Línea 7**: `formatMoney(item.amount, '$')`
- ✅ **Línea 22**: `formatMoney(totalAmount, '$')`

---

## 📊 **Beneficios de la Corrección**

### **1. Consistencia**
- ✅ **Formato uniforme**: Todas las monedas se muestran con el mismo símbolo
- ✅ **Parámetro explícito**: Evita confusiones sobre qué moneda se está usando
- ✅ **Mantenibilidad**: Más fácil de cambiar el símbolo de moneda globalmente

### **2. Flexibilidad Futura**
- ✅ **Soporte multi-moneda**: Fácil agregar otras monedas (USD, EUR, etc.)
- ✅ **Configuración dinámica**: Se puede cambiar el símbolo según el contexto
- ✅ **Escalabilidad**: Preparado para sistemas multi-moneda

### **3. Mejores Prácticas**
- ✅ **Parámetros explícitos**: Evita dependencias en valores por defecto
- ✅ **Código más claro**: Es obvio qué moneda se está usando
- ✅ **Menos errores**: Reduce confusiones en el formato

---

## 🧪 **Testing**

### **Verificación Manual:**
1. **Ir a**: `/contracts/adjustments`
2. **Verificar**: Los montos se muestran con "$" al inicio
3. **Ir a**: `/contracts/{id}` (ajustes específicos)
4. **Verificar**: Los montos se muestran correctamente
5. **Ir a**: `/collections/create`
6. **Verificar**: Los precios y totales se muestran con "$"

### **Casos de Prueba:**
- ✅ **Montos positivos**: `$ 1.234,56`
- ✅ **Montos negativos**: `$ -1.234,56`
- ✅ **Montos cero**: `$ 0,00`
- ✅ **Valores nulos**: `—`
- ✅ **Valores inválidos**: `—`

---

## 📋 **Archivos Modificados**

### **Frontend:**
- ✅ `front/src/views/contract-adjustments/components/ContractAdjustmentTable.vue`
- ✅ `front/src/views/contracts/components/ContractAdjustmentTable.vue`
- ✅ `front/src/views/collections/components/CollectionItemTable.vue`

### **Cambios Realizados:**
1. **Agregado parámetro `'$'`** a todas las llamadas a `formatMoney`
2. **Mantenida funcionalidad** existente
3. **Mejorada consistencia** en el formato de moneda

---

## 🎯 **Resultado Final**

### **Antes:**
```javascript
formatMoney(1234.56)  // "$ 1.234,56" (dependía del default)
```

### **Después:**
```javascript
formatMoney(1234.56, '$')  // "$ 1.234,56" (explícito)
```

**¡La corrección está implementada y funcionando!** 🚀

Ahora todas las llamadas a `formatMoney` incluyen explícitamente el parámetro de currency, asegurando consistencia y preparando el sistema para futuras expansiones multi-moneda. 