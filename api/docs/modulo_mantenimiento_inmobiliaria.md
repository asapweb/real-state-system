## Módulo de Mantenimiento - Sistema de Gestión Inmobiliaria

### Objetivo
Gestionar de forma integral el mantenimiento preventivo y correctivo de las propiedades administradas por la inmobiliaria, incluyendo proveedores, órdenes de trabajo, costos y seguimiento.

---

## 1. Órdenes de Trabajo

### Descripción
Representan tareas o intervenciones concretas a realizar sobre una propiedad.

### Campos principales
- Propiedad (obligatorio)
- Contrato asociado (opcional)
- Descripción del trabajo
- Estado: `pendiente`, `en curso`, `completado`, `cancelado`
- Fecha de solicitud
- Fecha estimada y real de realización
- Tipo: `preventivo`, `correctivo`
- Urgencia: `normal`, `alta`, `crítica`
- Observaciones

### Relación con proveedores
- Puede estar asignada a uno o más proveedores
- Cada proveedor puede tener: presupuesto, estado, observaciones

---

## 2. Proveedores (`suppliers`)

### Campos clave
- Nombre o razón social
- Rubro / especialidad (plomería, electricidad, etc.)
- Datos de contacto (teléfono, email)
- Condiciones de contratación
- Tarifa por hora o trabajo fijo
- Asociación a `client_id` (opcional, si se le paga)

---

## 3. Presupuestos y aprobaciones

- Cada orden puede tener uno o más presupuestos cargados
- Puede registrarse aprobación por parte del propietario
- Se almacena monto esperado vs. monto final ejecutado

---

## 4. Pagos y cobranzas

- Si el trabajo lo paga la inmobiliaria: se registra un **egreso** (recibo de pago)
- Si lo debe pagar el inquilino: se genera un **cargo** asociado a su cuenta
- Si lo cubre el propietario: puede deducirse en la **liquidación**

---

## 5. Archivos adjuntos

- Fotos del estado inicial / final
- Presupuestos
- Facturas del proveedor
- Certificaciones de trabajo realizado

---

## 6. Alertas y tareas periódicas

- Se permite configurar tareas recurrentes por propiedad o rubro:
  - Control de matafuegos
  - Inspección de gas
  - Limpieza semestral
- Se generan alertas automáticas próximo al vencimiento

---

## 7. Integración futura

- Posibilidad de generar solicitudes desde el portal de propietarios o inquilinos
- Consulta del historial de mantenimiento desde la ficha de propiedad
- Vinculación con gastos generales (expensas, impuestos)
- Reportes de costos por propiedad, proveedor o tipo de tarea

