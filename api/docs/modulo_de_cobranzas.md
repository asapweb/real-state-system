## Módulo de Cobranzas

Este documento funcional describe el funcionamiento del módulo de **Cobranzas** del sistema de gestión inmobiliaria, incluyendo:
- Generación automática de cobranzas mensuales por contrato
- Cobranzas manuales asociadas o no a contratos
- Estructura, validaciones y consideraciones de uso

---

## 1. Objetivo del Módulo

El módulo de cobranzas permite generar comprobantes de tipo **Collection** para registrar montos adeudados por parte de los clientes (principalmente inquilinos). Estas cobranzas pueden originarse de forma automática (alquiler mensual) o manual (otros conceptos). El objetivo es mantener trazabilidad, estructura clara y coherencia contable.

---

## 2. Tipos de Cobranzas

### 2.1 Cobranzas Mensuales (Automáticas)
- **Disparador**: proceso automático o manual desde el backend
- **Requisitos**:
  - Contrato activo en el mes correspondiente
  - No debe existir otra cobranza para ese contrato y mes
- **Conceptos generados**:
  - Alquiler mensual (prorrateado si aplica)
  - Seguro (si aplica)
  - Comisión (mensual o única)
  - Servicios activos pagados por la inmobiliaria
  - Penalidades automáticas por atraso
- **Relaciones**: `contract_id`, `client_id`, `collection_items`

### 2.2 Cobranzas Manuales
- **Disparador**: creación manual desde el sistema
- **Requisitos**:
  - Asociación obligatoria a un cliente (`client_id`)
  - Asociación opcional a un contrato (`contract_id`)
  - No deben incluirse ítems de tipo `rent`
- **Casos comunes**:
  - Penalidades especiales
  - Servicios no incluidos previamente
  - Venta de productos
  - Ajustes o recargos excepcionales
- **Identificación**: en `meta.origin = "manual"`

---

## 3. Estructura de los Comprobantes

### 3.1 Collection
- `client_id` (obligatorio)
- `contract_id` (opcional en cobranzas manuales)
- `currency`, `issue_date`, `due_date`, `period`
- `total_amount`: suma de los `collection_items`
- `status`: `pending`, `paid`, `partially_paid`, `canceled`
- `meta`: origen, usuario creador, observaciones

### 3.2 CollectionItem
- `type`: `rent`, `insurance`, `commission`, `service`, `penalty`, `product`, `adjustment`
- `description`, `quantity`, `unit_price`, `amount`
- `currency` (moneda heredada del comprobante)
- `meta`: prorrateo, penalidades, origen del servicio, etc.

---

## 4. Reglas de Validación

### Para todas las cobranzas:
- Debe existir al menos un `CollectionItem`
- Todos los ítems deben estar en la misma moneda
- El `total_amount` debe coincidir con la suma de los ítems

### Para cobranzas automáticas:
- Solo contratos activos
- Solo una cobranza por contrato y mes
- Se permite el tipo `rent`

### Para cobranzas manuales:
- Prohibido incluir `rent`
- `contract_id` opcional pero permitido
- Se permite cualquier otro tipo de concepto

---

## 5. Consideraciones

- Las cobranzas impactan en la cuenta corriente del cliente
- Se pueden asociar uno o más pagos (recibos) que cancelan la deuda parcial o total
- Las cobranzas asociadas a contratos pueden usarse en liquidaciones a propietarios
- Todas las cobranzas deben ser auditables y trazables

---

## 6. Ejemplos

### Cobranza mensual (automática)
| Concepto     | Monto   |
|--------------|---------|
| Alquiler     | 100.000 |
| Seguro       | 2.500   |
| Comisión     | 5.000   |
| **Total**    | 107.500 |

### Cobranza manual (servicio adicional)
| Concepto     | Monto   |
|--------------|---------|
| Reparación  | 12.000  |
| **Total**    | 12.000  |

---

Este documento deberá utilizarse como referencia para la implementación de migraciones, modelos, controladores, validadores y vistas del módulo de cobranzas.

