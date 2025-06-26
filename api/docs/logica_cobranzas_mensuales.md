## Lógica Funcional: Generación de Cobranzas Mensuales

### Objetivo
Definir el proceso funcional para la generación de cargos mensuales automáticos asociados a contratos de alquiler vigentes, estableciendo condiciones, estructura, validaciones y su impacto en la cuenta corriente del inquilino.

---

## 1. Disparador de la generación
- Se ejecuta por proceso automático (tarea programada) o manual desde el backend.
- Recorre todos los contratos **activos**.
- Para cada contrato, verifica si ya existe un cargo generado para el mes correspondiente.
- Si no existe, se genera un nuevo **comprobante de tipo cargo**.

---

## 2. Datos requeridos del contrato
- Fecha de inicio y fin del contrato (`start_date`, `end_date`)
- Monto mensual (`monthly_amount`)
- Moneda (`currency`)
- Día de pago (`payment_day`)
- Si requiere seguro: monto y compañía
- Comisión inmobiliaria: tipo, monto y pagador
- Servicios asociados activos
- Penalidades (si aplica)
- Flags de prorrateo (`prorate_first_month`, `prorate_last_month`)

---

## 3. Estructura del comprobante generado
- **Tipo**: cargo
- **Estado inicial**: pendiente
- **Fecha de emisión**: día de generación
- **Período facturado**: mes y año correspondiente
- **Cliente asociado**: inquilino principal del contrato
- **Moneda**: heredada del contrato
- **Conceptos (detalles)**:
  - Alquiler mensual (ajustado si corresponde por prorrateo o ajustes)
  - Seguro (si corresponde)
  - Comisión (si la paga el inquilino y es mensual o única según flag `is_one_time`)
  - Servicios: **solo si están activos y la inmobiliaria es quien los paga (paid_by = 'agency')** y debe recuperar el costo del inquilino
  - Penalidades (si aplica y es automática)

---

## 4. Prorrateo (inicio y fin del contrato)
- Si el contrato **inicia o termina en un mes parcial**, y está activado el flag `prorate_first_month` o `prorate_last_month`, el importe del alquiler se calcula proporcionalmente a los días efectivamente alquilados.
- El resto de los conceptos (seguro, comisiones, servicios) pueden o no prorratearse según reglas a definir.

---

## 5. Validaciones
- No generar más de un cargo para el mismo contrato y mes.
- No generar cargos para contratos en estado distinto a "activo".
- Solo generar conceptos que correspondan según flags y condiciones contractuales.
- No incluir conceptos en otras monedas (cada comprobante debe ser monomoneda).

---

## 6. Impacto del cargo generado
- Se crea un comprobante de tipo **cargo** con los conceptos desglosados.
- Se genera un **asiento en la cuenta corriente** del inquilino por el total del cargo.
- Queda disponible para:
  - Ser facturado (opcional)
  - Ser cobrado total o parcialmente
  - Ser consultado desde el portal de inquilinos

---

## 7. Consideraciones adicionales
- Se podrá emitir una factura opcional asociada al cargo (solo si el inquilino la requiere).
- Si el cargo incluye conceptos en distintas monedas, se deberán generar comprobantes separados.
- El sistema deberá poder registrar el prorrateo y ajustes aplicados en los detalles.
- Se recomienda registrar el origen de cada concepto (alquiler, seguro, comisión, etc.) para trazabilidad.

---

## 8. Ejemplo de cargo mensual generado

**Contrato ID:** 123  
**Período:** Junio 2025  
**Moneda:** ARS

| Concepto               | Monto     | Observación              |
|------------------------|-----------|---------------------------|
| Alquiler mensual       | $100.000  |                           |
| Seguro de hogar        | $2.500    |                           |
| Comisión inmobiliaria | $5.000    | Pago único según contrato |
| **Total**              | **$107.500** |                           |

---

## 9. Cálculo de los conceptos del cargo

El sistema genera un cargo mensual incluyendo distintos conceptos, cada uno con su lógica específica. A continuación se detallan las reglas generales:

### 9.1 Alquiler mensual
- Se toma del campo `monthly_amount` del contrato.
- Si existen ajustes (`contract_adjustments`) vigentes al mes, se aplica la modificación correspondiente (fijo, porcentaje, índice, negociado).
- Si corresponde prorrateo (inicio o fin del contrato), se calcula proporcionalmente según la cantidad de días del mes efectivamente alquilados.

### 9.2 Comisión inmobiliaria
- Se incluye solo si `commission_type ≠ 'none'` **y** `commission_payer = 'tenant'`.
- Si `is_one_time = true`, se incluye únicamente en el primer cargo del contrato.
- Si la comisión es mensual, se incluye en todos los cargos.

### 9.3 Seguro
- Se incluye si `insurance_required = true`.
- Se toma el campo `insurance_amount` como valor mensual.

### 9.4 Servicios
- Se incluyen solo los servicios activos (`is_active = true`) del contrato donde `paid_by = 'agency'`.
- El monto se obtiene del campo `debt_amount` o del monto estimado según configuración.

### 9.5 Penalidades por atraso
- El sistema no recalcula el cargo original una vez vencido.
- Si un cargo anterior no fue abonado a tiempo, el sistema **genera un nuevo ítem** en el cargo del mes siguiente:
  - Se evalúa que `has_penalty = true` y que vencó la fecha (`payment_day + penalty_grace_days`)
  - Se calcula según `penalty_type` (`fixed` o `percentage`) y `penalty_value`
  - Se registra como nuevo concepto en el cargo siguiente:  
    _Ejemplo: “Punitorio por atraso – mayo 2025”_
- Este enfoque permite trazabilidad y evita alterar cargos ya emitidos.

