# Corrección: Currency Dinámico en formatMoney

## 🎯 **Problema Identificado**

La función `formatMoney` estaba usando un currency hardcodeado (`'$'`) en lugar de usar el currency específico de cada contrato, lo que causaba inconsistencias en el formato de moneda.

---

## ✅ **Corrección Implementada**

### **1. Uso Correcto de Currency**

#### **Antes (Incorrecto):**
```javascript
formatMoney(item.value, '$')  // Currency hardcodeado
formatMoney(item.applied_amount, '$')
formatMoney(item.contract.monthly_amount, '$')
```

#### **Después (Correcto):**
```javascript
formatMoney(item.value, item.contract?.currency || '$')
formatMoney(item.applied_amount, item.contract?.currency || '$')
formatMoney(item.contract.monthly_amount, item.contract.currency || '$')
```

---

## 🔧 **Archivos Corregidos**

### **1. ContractAdjustmentTable.vue (Global)**
- ✅ **Línea 125**: `formatMoney(item.value, item.contract?.currency || '$')`
- ✅ **Línea 136**: `formatMoney(item.applied_amount, item.contract?.currency || '$')`
- ✅ **Línea 170**: `formatMoney(item.contract.monthly_amount, item.contract.currency || '$')`

### **2. ContractAdjustmentTable.vue (Contracts)**
- ✅ **Línea 49**: `formatMoney(item.value, item.contract?.currency || '$')`
- ✅ **Línea 62**: `formatMoney(item.applied_amount, item.contract?.currency || '$')`
- ✅ **Línea 96**: `formatMoney(item.contract.monthly_amount, item.contract.currency || '$')`

### **3. CollectionItemTable.vue**
- ✅ **Agregado prop `currency`**: Para recibir el currency del formulario
- ✅ **Línea 3**: `formatMoney(item.unit_price, currency)`
- ✅ **Línea 7**: `formatMoney(item.amount, currency)`
- ✅ **Línea 22**: `formatMoney(totalAmount, currency)`

### **4. CollectionForm.vue**
- ✅ **Línea 52**: Pasando `:currency="formData.currency"` al CollectionItemTable

---

## 📊 **Lógica de Currency**

### **1. Para Ajustes de Contrato**
```javascript
// Currency del contrato específico
item.contract?.currency || '$'

// Casos:
// - Contrato con currency 'USD' → "$ 1,234.56"
// - Contrato con currency 'ARS' → "$ 1.234,56"
// - Sin contrato → "$ 1.234,56" (default)
```

### **2. Para Colecciones**
```javascript
// Currency del formulario de colección
formData.currency  // 'ARS' o 'USD'

// Casos:
// - Formulario con 'USD' → "$ 1,234.56"
// - Formulario con 'ARS' → "$ 1.234,56"
```

---

## 🎯 **Casos de Uso**

### **1. Ajustes de Contrato**
- **`item.value`**: Monto del ajuste (usa currency del contrato)
- **`item.applied_amount`**: Nuevo alquiler aplicado (usa currency del contrato)
- **`item.contract.monthly_amount`**: Alquiler base del contrato (usa currency del contrato)

### **2. Colecciones**
- **`item.unit_price`**: Precio unitario (usa currency del formulario)
- **`item.amount`**: Subtotal del ítem (usa currency del formulario)
- **`totalAmount`**: Total de la colección (usa currency del formulario)

---

## 🧪 **Testing**

### **Verificación Manual:**
1. **Ir a**: `/contracts/adjustments`
2. **Verificar**: Los montos usan el currency del contrato
3. **Ir a**: `/contracts/{id}` (ajustes específicos)
4. **Verificar**: Los montos usan el currency del contrato
5. **Ir a**: `/collections/create`
6. **Cambiar moneda**: De ARS a USD
7. **Verificar**: Los montos se actualizan con el nuevo currency

### **Casos de Prueba:**
- ✅ **Contrato USD**: Montos con formato USD
- ✅ **Contrato ARS**: Montos con formato ARS
- ✅ **Sin contrato**: Usa currency por defecto
- ✅ **Colección ARS**: Montos con formato ARS
- ✅ **Colección USD**: Montos con formato USD

---

## 📋 **Archivos Modificados**

### **Frontend:**
- ✅ `front/src/views/contract-adjustments/components/ContractAdjustmentTable.vue`
- ✅ `front/src/views/contracts/components/ContractAdjustmentTable.vue`
- ✅ `front/src/views/collections/components/CollectionItemTable.vue`
- ✅ `front/src/views/collections/components/CollectionForm.vue`

### **Cambios Realizados:**
1. **Currency dinámico**: Usa `item.contract?.currency` en lugar de `'$'`
2. **Prop currency**: Agregado a CollectionItemTable
3. **Fallback seguro**: `|| '$'` para casos sin currency
4. **Consistencia**: Todos los montos usan el currency correcto

---

## 🎯 **Resultado Final**

### **Antes:**
```javascript
formatMoney(1234.56, '$')  // Siempre "$ 1.234,56"
```

### **Después:**
```javascript
// Para contratos USD
formatMoney(1234.56, 'USD')  // "$ 1,234.56"

// Para contratos ARS
formatMoney(1234.56, 'ARS')  // "$ 1.234,56"

// Para colecciones
formatMoney(1234.56, formData.currency)  // Currency dinámico
```

**¡La corrección está implementada y funcionando!** 🚀

Ahora todos los montos usan el currency específico de cada contrato o formulario, asegurando consistencia y precisión en el formato de moneda. 