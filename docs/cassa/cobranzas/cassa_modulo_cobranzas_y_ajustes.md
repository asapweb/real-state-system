# Documento Consolidado – Proceso de Generación de Cobranzas y Actualización de Alquileres

## 1. Objetivo General

Definir el funcionamiento esperado del sistema de generación de cobranzas, gestión de actualizaciones de alquiler, administración de recuperos, bonificaciones y notificaciones para inquilinos y propietarios, reflejando las prácticas reales y casos especiales que enfrenta el área administrativa.

---

## 2. Generación de Cobranzas

### 2.1 Alquiler Base y Punitorios por Mora

- Siempre se debe reflejar el alquiler vigente según contrato.
- Si se paga fuera de término, calcular punitorios según:
  - a) Desde el día 1 del mes completo.
  - b) Desde el día posterior al vencimiento pactado (por ejemplo, del 8 al 10 si vencía el 7).

### 2.2 Pagos Combinados

- Cuando parte del pago es informal (ej. pagaré), se debe mostrar:
  - Alquiler base.
  - Ítem extra: “Pago por acuerdo con propietario” o similar.

### 2.3 Bonificaciones

- Tipos:
  - Por duración total del contrato.
  - Temporales o excepcionales.
  - Según acuerdo con propietario.

### 2.4 Servicios Pagados por CASSA

- Si CASSA abona servicios como ABSA, SEGURO, CAMUZZI, etc.:
  - Se genera un cargo para recuperar el monto.
  - Puede incluirse en el recibo de alquiler o gestionarse luego.
  - Si hay medidores compartidos, se debe prorratear proporcionalmente entre inquilinos.

### 2.5 Bonificaciones por Expensas Extraordinarias

- Cuando el inquilino paga conceptos que corresponden al propietario:
  - Se bonifica ese importe al inquilino.
  - Se genera el cargo correspondiente al propietario.
  - Idealmente, el sistema detecta el comprobante cargado por cliente y genera automáticamente ambos movimientos.

### 2.6 Servicios Pagados por el Propietario

- Cuando el propietario paga un servicio que corresponde al inquilino:
  - Se genera un cargo al inquilino.
  - Se bonifica al propietario.
  - Puede aplicarse un reparto (50/50, por ejemplo), incluso si no está detallado en contrato.

### 2.7 Cálculo Proporcional por Períodos

- Para facturas que abarcan múltiples ocupantes o vacancias:
  - Indicar fechas de ocupación.
  - Calcular cargos y bonificaciones proporcionales.
  - Posibilidad de ajustes manuales.

---

## 3. Actualización de Alquileres

### 3.1 Índices de Ajuste

- El contrato debe indicar el índice de actualización (ICL, CREEBBA, CASAPROPIA).
- El sistema debe:
  - Consultar el índice correspondiente al vencimiento.
  - Calcular y redondear el nuevo monto.
  - Informar automáticamente al inquilino por WhatsApp y mail.

---

## 4. Comunicación y Documentación

### 4.1 Entrega de Contratos

- El sistema debe identificar quién debe retirar copia del contrato (inquilino o propietario).
- Mostrar alertas cuando se abra la cuenta corriente o se reciba un pago.

### 4.2 Control de Seguros

- Registrar fecha de vigencia de póliza.
- Enviar alertas automáticas antes del vencimiento para cargar la renovación.

### 4.3 Facturación

- Cuando se genera una factura (honorarios administrativos o profesionales), debe:
  - Subirse al sistema.
  - Enviarse automáticamente al cliente correspondiente.

---

## 5. Recibos y Liquidaciones

### 5.1 Pagos Directos al Propietario

- a) Si el inquilino transfiere todo el alquiler al propietario:
  - Se genera recibo detallando este método de pago.
  - Se envía automáticamente por WhatsApp y mail.
- b) Si el inquilino paga alquiler al propietario y honorarios a CASSA:
  - Se detallan ambos pagos en el recibo.
  - El sistema carga automáticamente los montos en las cuentas corrientes respectivas.
  - Al liquidar al propietario, el sistema debería enviarle la liquidación por mail.

---

## 6. Recuperos

- Los "recuperos" son pagos realizados por CASSA (servicios o arreglos) que luego deben cobrarse a quien corresponda.
- El sistema debe:
  - Permitir filtrar recuperos pendientes.
  - Gestionar el reclamo por WhatsApp (con alias, datos de pago).
  - Controlar si se debe cargar al alquiler o descontar de la liquidación.
  - Considerar que algunos pagos ocurren luego de haber liquidado o cobrado.

