# 📄 CASSA - Generación de Cobranzas Mensuales

Este documento consolida los datos y reglas necesarias para generar cobranzas mensuales en el sistema de gestión inmobiliaria, tomando como base los procesos internos de CASSA.

---

## 1. Datos necesarios desde el Contrato

- **Monto de alquiler actual**  
  Siempre debe reflejar el valor vigente según contrato, considerando actualizaciones por índice.

- **Índice de actualización**  
  El contrato puede estipular ICL, CREEBBA o CASA PROPIA. El sistema debe calcular el nuevo valor y redondearlo automáticamente al momento de aplicar el ajuste.

- **Fecha de vencimiento del alquiler**  
  Generalmente es del 1 al 7 de cada mes. A partir de allí se calcula mora si corresponde.

- **Bonificaciones**  
  - Bonificaciones generales durante todo el contrato.
  - Bonificaciones temporales definidas por el propietario.
  - Bonificaciones por pagos realizados por el inquilino que corresponden al propietario (como expensas extraordinarias).

- **Otros conceptos acordados**  
  Por ejemplo, pagarés o ítems como "Pago por acuerdo con propietario".

- **Pagos directos al propietario**  
  El inquilino puede transferir total o parcialmente al propietario. El recibo debe reflejar esta operación y registrar los montos en cuenta corriente.

---

## 2. Reglas de Generación

### 2.1. Mora

- Si el pago se realiza fuera de término, se aplica interés.
  - Modalidad 1: desde el día 1 del mes.
  - Modalidad 2: desde el día siguiente al vencimiento contractual.

### 2.2. Servicios pagados por CASSA o propietario

- El sistema debe permitir cargar gastos y generar automáticamente:
  - Cargos al inquilino.
  - Bonificaciones al propietario.
  - División proporcional por ocupación o servicios compartidos.

### 2.3. Recuperos

- Todo gasto pagado por CASSA y a recuperar se considera un "recupero".
- El sistema debe permitir:
  - Carga desde mantenimiento o servicios.
  - Control de si ya se cobró o liquidó.
  - Inclusión en la cobranza siguiente o manual según corresponda.
  - Reclamos automáticos si hay deuda.

---

## 3. Comunicación Automática

- **Inquilinos**:
  - Notificación por WhatsApp y mail cuando:
    - Se actualiza el alquiler por índice.
    - Se genera el recibo.

- **Documentación de seguros**:
  - Aviso automático para renovación de póliza, con carga del nuevo documento.

- **Recuperos pendientes**:
  - Mensajes automáticos con alias de pago para servicios no recuperados aún.

---

## 4. Recomendaciones

- Permitir redondeo del valor de alquiler al aplicar ajustes.
- Reflejar con claridad cada concepto en el recibo: alquiler, punitorio, bonificación, servicios, otros.
- Permitir ajustes manuales si hay acuerdos especiales.
- Filtrar contratos por necesidades pendientes (entrega de contrato físico, presentación de seguro, etc.).

---

**Origen del documento**:  
Consolidación de los siguientes documentos internos de CASSA:
- "Proceso de generación de cobranzas"
- "Actualización de alquileres"
- "Procedimiento de actualización de alquileres"
