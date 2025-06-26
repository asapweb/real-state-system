## Módulo de Gestión de Gastos - Sistema de Gestión Inmobiliaria

### Objetivo

Modelar y administrar de forma estructurada todos los **egresos** asociados a la operación de una inmobiliaria, ya sean gastos propios, pagos a proveedores o costos imputables a propiedades, contratos o clientes.

---

## 1. Definición de Gasto (`expenses`)

Un gasto representa un compromiso o salida de dinero por parte de la inmobiliaria. Puede tener origen contable (factura), financiero (orden de pago) o funcional (orden de trabajo, gestión).

### Campos principales

| Campo           | Descripción                                     |
|----------------|-------------------------------------------------|
| `id`            | Identificador                                   |
| `client_id`     | Opcional (si se registra como proveedor)        |
| `supplier_id`   | Opcional (referencia específica a proveedor)   |
| `property_id`   | Opcional (si se relaciona con un inmueble)      |
| `contract_id`   | Opcional (si se origina en un contrato)         |
| `description`   | Concepto libre del gasto                        |
| `amount`        | Importe                                         |
| `currency`      | Moneda                                          |
| `issue_date`    | Fecha del gasto                                 |
| `due_date`      | Vencimiento (opcional)                          |
| `document_type` | Ej: Factura A, Ticket, Recibo                   |
| `invoice_number`| Número de documento externo (si aplica)        |
| `status`        | pendiente / pagado / parcial                    |
| `paid_at`       | Fecha del pago (si aplica)                      |

---

## 2. Clasificación de Gastos

Los gastos pueden clasificarse según:

- Su **origen**:
  - Interno (operativos, administrativos)
  - Externo (servicios, proveedores, mantenimiento)

- Su **asociación**:
  - A una propiedad (ej. arreglo de plomería)
  - A un contrato (ej. costo cubierto por el inquilino o propietario)
  - A un cliente o proveedor

- Su **naturaleza fiscal**:
  - Con comprobante fiscal (Factura A, B, C)
  - Gasto informal con comprobantes **internos tipo X**:
    - Factura X (informal)
    - Nota de Crédito X
    - Nota de Débito X

---

## 3. Integración con Recibos de Pago

- Un gasto puede estar vinculado a uno o varios **recibos de pago** (`payment_receipts`)
- Esto permite trazar qué se pagó, cuándo y desde qué caja
- Un mismo recibo puede cancelar varios gastos
- **Todos los pagos**, incluso de gastos informales, deben registrarse mediante recibos de pago

---

## 4. Reportes y Control

- Listado de gastos por propiedad, proveedor o rubro
- Gastos no pagados (cuentas a pagar)
- Análisis de costos vs. ingresos
- Exportación contable y preparación para declaraciones fiscales

---

## 5. Casos de Uso Comunes

- Carga de factura por trabajo de proveedor (ej. gasista)
- Registro de pago por honorarios profesionales (escribano, gestor)
- Compra de materiales para mantenimiento
- Gastos administrativos: papelería, alquiler de oficina, servicios
- Registro de egresos informales mediante comprobantes tipo X

---

## 6. Beneficios

- Visibilidad completa de egresos
- Trazabilidad con caja, propiedades y clientes
- Base sólida para conciliaciones contables y reportes
- Separación clara entre gasto (razón del egreso) y movimiento de pago
- Soporte para comprobantes internos y pagos formales

---

## 7. Flujo de Gasto asociado a Mantenimiento

Cuando se genera una solicitud de mantenimiento (ej. por parte del inquilino), el flujo financiero puede tomar distintos caminos según quién efectúa el pago:

### Caso A: El inquilino paga directamente al proveedor
- Se registra la solicitud de mantenimiento
- Se marca como resuelta y abonada por el inquilino
- No se genera gasto ni recibo
- No impacta cuenta corriente del inquilino ni del proveedor

### Caso B: La inmobiliaria paga al proveedor
1. Se registra un **gasto** (`expense`) asociado al proveedor y opcionalmente a una solicitud de mantenimiento y/o contrato
2. Se emite un **recibo de pago** (`payment_receipt`) que genera el egreso de dinero y compensa la cuenta corriente del proveedor
3. Si se desea trasladar el gasto al inquilino:
   - Se puede generar un **cargo manual** (`charge`) asociado al contrato, lo que afecta la cuenta corriente del inquilino
   - O bien incluir el gasto como concepto adicional en la **próxima cobranza mensual** (collection)
   - Cuando el inquilino paga, se registra un **recibo de cobranza** (`payment_receipt`) que ingresa dinero y cancela el cargo

Este enfoque asegura trazabilidad completa desde la solicitud, pasando por el gasto y el pago, hasta el eventual recupero del monto por parte del inquilino.

---

Este módulo complementa el modelo financiero permitiendo una gestión ordenada y auditable de todos los gastos asociados a la actividad inmobiliaria.

