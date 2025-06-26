# Propuesta de Modelo de Gestión Financiera Integral para Inmobiliarias

## Objetivo

Diseñar una arquitectura funcional clara y escalable que permita administrar todo el flujo financiero de una inmobiliaria, incluyendo:

- Cuentas corrientes de clientes (inquilinos y propietarios)
- Cargos, cobros, liquidaciones y pagos
- Comprobantes internos y fiscales
- Integración con caja y eventual facturación electrónica

---

## 1. Comprobantes Involucrados

### 💼 Inquilinos:

| Comprobante | Tipo | Rol | Afecta saldo | Informa AFIP |
| ----------- | ---- | --- | ------------ | ------------ |
| **Cargo** | Interno | Genera deuda (alquiler, seguro, etc.) | ✅ Suma | ❌ No |
| **Factura A/B** | Fiscal | Declara fiscalmente el cargo (opcional) | ❌ No | ✅ Sí |
| **Recibo de Cobranza X** | Interno | Registra pago efectivo | ✅ Resta | ❌ No |
| **Recibo de Cobranza B** | Fiscal | Registra pago efectivo y cumple rol legal | ✅ Resta | ✅ Sí |
| **Nota de Crédito** | Fiscal | Disminuye saldo a favor del cliente | ✅ Resta | ✅ Sí |
| **Nota de Débito** | Fiscal | Agrega deuda posterior | ✅ Suma | ✅ Sí |

### 👤 Propietarios:

| Comprobante | Tipo | Rol | Afecta saldo | Informa AFIP |
| ----------- | ---- | --- | ------------ | ------------ |
| **Liquidación** | Interno | Determina lo que debe cobrarse al propietario | ✅ Suma | ❌ No |
| **Recibo de Pago** | Interno | Indica el pago real al propietario | ✅ Resta | ❌ No |
| **Factura A (comisión)** | Fiscal | Solo si se factura el servicio de administración | ❌ No | ✅ Sí |
| **Retenciones** | Fiscal | Solo si se realizan retenciones (IVA, ganancias) | ❌ No | ✅ Sí |

---

## 2. Cuenta Corriente por Cliente

Cada cliente (inquilino o propietario) tiene una **cuenta corriente**:

- Registra movimientos cronológicos
- Aumenta con cargos o liquidaciones
- Disminuye con recibos, pagos o notas de crédito

### Tabla: `client_ledger_entries`

| Campo | Valor |
| ----- | ----- |
| `client_id` | Cliente involucrado |
| `type` | charge / payment / credit / debit |
| `related_type` | collection, receipt, adjustment, settlement |
| `related_id` | ID del comprobante |
| `amount` | Monto |
| `direction` | inbound / outbound |
| `created_at` | Fecha de operación |

---

## 3. Caja y movimientos reales

| Movimiento | Comprobante que lo genera |
| ---------- | ------------------------- |
| ✅ Ingreso de dinero | Recibo de cobranza (interno o fiscal) |
| ❌ Egreso de dinero | Recibo de pago al propietario |
| ❌ Pago de factura o proveedor | Opcional, si se integra con gastos |

Se integran en `cashbox_movements`, permitiendo conciliación diaria.

---

## 4. Consideraciones sobre AFIP y Facturación

- El sistema **no requiere** emitir facturas, salvo que la inmobiliaria lo necesite.
- Las facturas se asocian a cargos ya existentes o se emiten por separado.
- Los recibos fiscales (con CAE) pueden **reemplazar** la factura en operaciones finales.
- Las notas de crédito y débito permiten ajustes formales.
- Las comisiones sólo se facturan al propietario si la inmobiliaria brinda servicio y tiene alta en AFIP.

---

## 5. Ciclos Completos

### 🏡 Inquilino:

```
CARGO generado --> (opcional) FACTURA --> RECIBO DE COBRANZA --> CAJA
```

### 👥 Propietario:

```
COBRANZA realizada --> LIQUIDACIÓN generada --> RECIBO DE PAGO --> CAJA (-)
```

---

## 6. Beneficios del modelo

- Claridad en movimientos internos y legales
- Control total del flujo: deuda, cobro, liquidación, pago
- Preparado para integrarse con facturación electrónica (AFIP)
- Soporte completo para auditoría, contabilidad y conciliación
- Totalmente trazable, extensible y modular

---

## 7. Reglas de Moneda en Cargos y Facturas

### Cargos

- Cada comprobante de cargo debe tener **una única moneda**.
- Si se necesita generar conceptos en distintas monedas, se deben generar **un cargo por cada moneda**.
- Esto simplifica la integración con la cuenta corriente, los pagos y la conciliación de caja.

### Facturas

- La AFIP requiere que cada factura tenga **una única moneda**.
- En caso de emitir facturas en moneda extranjera (USD, EUR), se debe informar:
  - Código de moneda (ej. DOL, EUR)
  - Cotización al momento de la emisión
- Si un cargo se factura en más de una moneda, se deben generar **una factura por cada moneda**.

Esta regla asegura coherencia operativa, contable y fiscal, y evita ambigüedades en los documentos generados.
