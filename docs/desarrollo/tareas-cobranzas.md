# 🛠️ Tareas Técnicas: Generación de Cobranzas Mensuales

Este documento resume las tareas técnicas necesarias para completar la funcionalidad de generación de cobranzas mensuales en el sistema, basado en el análisis comparativo con la documentación funcional consolidada.

---

## ✅ Prioridad Alta (críticos para la operación)

### 1. Cálculo de punitorios por mora
- Agregar fecha de vencimiento al modelo `contracts` o `collections`.
- Incluir lógica en `CollectionGenerationService` para calcular interés según fecha de pago real.
- Permitir dos modos: desde el 1 o desde el día siguiente al vencimiento.

### 2. Integración automática de servicios (recuperos)
- Asociar automáticamente `ContractExpense` a la cobranza mensual si:
  - `due_date` del gasto cae en el mes de la cobranza
  - `paid_by = agency` y `to_be_reimbursed = true`
- Permitir prorrateo si `shared_with_tenants = true` (nuevo campo si es necesario).

### 3. Soporte para bonificaciones
- Permitir cargar bonificaciones en el contrato o manualmente para un mes específico.
- Incluirlas como ítems negativos (`CollectionItem`) en la cobranza.
- Tipos: por expensas extraordinarias, acuerdos con propietario, etc.

### 4. Pagos combinados y observaciones
- Permitir registrar pagos en blanco y en negro, y mostrar observación personalizada en el recibo.
- Registrar en `Collection.metadata` u otro campo.

---

## 🟡 Prioridad Media (mejoras operativas)

### 5. Redondeo automático de alquiler ajustado
- Al aplicar un `ContractAdjustment`, redondear el nuevo valor automáticamente (`nearest 100`, etc.).
- Configurable por contrato o globalmente.

### 6. Alerta por renovación de seguros
- Permitir cargar fecha de vigencia de póliza en `contracts` o entidad relacionada.
- Agregar notificación automática X días antes del vencimiento.

### 7. Comunicación automática (WhatsApp / Email)
- Al generar una cobranza, enviar notificación automática al inquilino con:
  - Monto del alquiler
  - Detalles de la cobranza (PDF o resumen)
- Uso de webhook o integración externa (pendiente de definición técnica).

---

## 🟠 Prioridad Baja (mejoras a futuro)

### 8. Proporcionalidad por días
- Permitir indicar fechas de entrada/salida y generar cálculo proporcional.
- Casos:
  - Cambio de inquilino dentro del mismo mes
  - Vacancia parcial
- Impacta en alquiler y servicios.

### 9. Checklist de documentación pendiente
- Agregar alertas visuales en la cuenta corriente o ficha del cliente si:
  - No presentó seguro
  - No retiró contrato
  - Falta cambio de titularidad, etc.
- Usar `ClientChecklist` o entidad auxiliar.