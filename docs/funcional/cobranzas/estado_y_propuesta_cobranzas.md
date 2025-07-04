## Estado Actual y Propuesta de Implementación
### Proceso de Generación de Cobranzas y Gestión de Alquileres

Este documento detalla el **estado actual del sistema** y las **propuestas de implementación** para cada punto funcional derivado del documento consolidado entregado por el área de cobranzas. El objetivo es validar con el equipo responsable y definir una solución definitiva.

---

### 2.1 Alquiler Base y Punitorios por Mora
**Estado actual:**
- El alquiler mensual se genera automáticamente según el contrato y sus ajustes (ítem `rent`).
- Si el pago se realiza fuera de término (vencimiento + días de gracia), se genera un punitorio (`late_fee`).

**Pendiente:**
- Permitir configuración del tipo de cálculo de mora:
  - Desde el 1° del mes.
  - Desde el vencimiento pactado.

**Propuesta:**
- Agregar campo `penalty_start_type` (`from_month_start`, `from_due_date`) al contrato.
- Adaptar `calculateLateFee()` para contemplar ambas variantes.

---

### 2.2 Actualización de Alquileres
**Estado actual:**
- El sistema permite definir ajustes fijos, porcentuales, por índice o negociados.
- El ajuste se aplica automáticamente si existe un valor definido.

**Pendiente:**
- Permitir carga de valores pendientes (por ejemplo, índice IPC aún no publicado).

**Propuesta:**
- Ya resuelto con `adjustment.value = null` hasta que se defina.
- El sistema omite el ajuste si el valor no fue ingresado.

---

### 2.3 Bonificaciones
**Estado actual:**
- El sistema no tiene aún soporte explícito para bonificaciones.

**Propuesta:**
- Utilizar Notas de Crédito asociadas a la cobranza:
  - Tipo: `bonus`
  - Descripción: origen o motivo (ej. bonificación por firma de contrato)
- Para bonificaciones pactadas desde el inicio:
  - Registrar en el contrato para trazabilidad.
  - No incluir como ítem negativo, sino generar NC automáticamente al generar la cobranza.

---

### 2.4 Servicios Pagados por CASSA
**Estado actual:**
- Se cargan como `ContractExpense` con `paid_by = agency`.
- Se incluyen automáticamente como ítems tipo `service` en la cobranza.

**Propuesta:**
- Ya implementado y funcionando correctamente.

---

### 2.5 Bonificaciones por Expensas Extraordinarias
**Estado actual:**
- No implementado aún.

**Propuesta:**
- Registrar el gasto como `ContractExpense` con:
  - `paid_by = tenant`
  - `expense_type = extraordinary`
- Generar automáticamente:
  - Nota de crédito al inquilino.
  - Nota de débito al propietario.

---

### 2.6 Servicios Pagados por el Propietario
**Estado actual:**
- No implementado aún.

**Propuesta:**
- Registrar el gasto como `ContractExpense` con:
  - `paid_by = owner`
  - `expense_type = reassigned`
- Generar automáticamente:
  - Nota de crédito al propietario.
  - Nota de débito al inquilino.

---

### 2.7 Cálculo Proporcional por Períodos
**Estado actual:**
- No implementado.

**Propuesta:**
- Permitir prorrateo de gastos (`ContractExpense`) según fechas de ocupación en el mes.
- Posibilidad de definir porcentaje manual o factor de reparto.
- Aplicar proporcionalidad al generar los ítems correspondientes.

---

### Prorrateo primer/último mes del contrato
**Estado actual:**
- Ya implementado y funcional.
- Controlado con flags en el contrato: `prorate_first_month`, `prorate_last_month`.
- Aplica solo al ítem de alquiler.

**Observación:**
- No está relacionado con el punto 2.7.

---

### Ítems manuales
**Estado actual:**
- Las cobranzas manuales se pueden generar desde el endpoint `store()`.
- Los ítems se ingresan libremente por el operador.

**Pendiente:**
- Asegurar que se permite todo tipo de ítems (`rent`, `service`, `late_fee`, `commission`, etc.).

**Propuesta:**
- Validar tipos permitidos en `StoreCollectionRequest`.

---

### Comisión inmobiliaria
**Estado actual:**
- Se puede configurar monto y tipo (única o mensual).
- El ítem `commission` se genera con monto 0.

**Propuesta:**
- Ajustar lógica para:
  - Aplicar monto si corresponde.
  - Calcular porcentaje si `commission_type = percentage`.
  - Evaluar si debe cobrarse según el período (`shouldChargeCommission()` ya lo cubre).

---

### Seguro locativo
**Estado actual:**
- Campo `insurance_required` y `insurance_amount` ya definidos.
- No se está generando el ítem correspondiente.

**Propuesta:**
- Agregar ítem `insurance` en `generateItemsForContract()` si aplica.

---

### Notas de crédito y débito
**Estado actual:**
- No implementado aún.

**Propuesta:**
- Implementar modelo `CreditNote` y `DebitNote`, asociados a:
  - `collection_id` o `liquidation_id`
  - `client_id`
- Incluir ítems como en `CollectionItem`
- Deben imputarse en `Receipt` o `Payment` y reflejarse en la cuenta corriente.

---

### Imputación y cuenta corriente
**Estado actual:**
- En desarrollo.

**Modelo funcional:**
| Comprobante             | Afecta cuenta corriente | Afecta caja |
|-------------------------|--------------------------|-------------|
| Cobranzas               | ✅ Cliente (inquilino)   | ❌          |
| Liquidaciones           | ✅ Cliente (propietario) | ❌          |
| Recibo de Cobranza      | ✅ Inquilino              | ✅          |
| Recibo de Pago          | ✅ Propietario            | ✅          |

---

Este documento está listo para validación con el equipo de cobranzas y servirá de guía para definir prioridades técnicas y ajustes futuros.
