## Resumen de Entidades del Sistema de Gestión Inmobiliaria

Este documento presenta una visión general de las entidades principales del sistema de gestión inmobiliaria, con el objetivo de facilitar la comprensión funcional del modelo y su cobertura sobre las operaciones administrativas de una inmobiliaria.

---

## CLIENTES Y OPERACIONES

### Clientes
- Personas físicas o jurídicas.
- Pueden ser propietarios, inquilinos, garantes, proveedores o combinaciones.
- Asociados a contratos, cobranzas, pagos y movimientos en cuenta corriente.

### Propiedades
- Inmuebles administrados por la inmobiliaria.
- Incluyen datos generales, ubicación, propietarios, servicios, estado y documentación.
- Se vinculan con contratos, mantenimientos y ofertas de alquiler.

### Ofertas de Alquiler
- Propuestas activas de alquiler basadas en propiedades disponibles.
- Incluyen condiciones comerciales: precio, duración, garantías, requisitos.

### Solicitudes y Reservas
- Postulaciones de clientes interesados en alquilar una propiedad.
- Incluyen datos del solicitante, garantías y documentación.
- Pueden derivar en una reserva o en la firma de un contrato.

### Contratos
- Representan acuerdos de alquiler entre la inmobiliaria y los inquilinos.
- Incluyen fechas, condiciones, clientes involucrados, ajustes, comisiones y penalidades.
- Fuente principal para generar cobranzas y liquidaciones.

### Mantenimiento
- Solicitudes de arreglos, mejoras o intervenciones sobre propiedades.
- Pueden estar vinculadas a un contrato o a la propiedad directamente.
- Pueden generar gastos asociados a liquidaciones o caja.

---

## MÓDULO FINANCIERO

### Cobranzas (tipo `charge`)
- Representan cargos mensuales a inquilinos (alquiler, comisión, seguro, penalidades).
- Generadas automáticamente desde contratos.
- Afectan la cuenta corriente del cliente, pero no implican ingreso real hasta que se paga.

### Recibos de Cobranzas (tipo `receipt`)
- Registran pagos realizados por clientes.
- Cancelan parcial o totalmente las cobranzas previas.
- Afectan cuenta corriente y generan ingreso en caja o banco.

### Liquidaciones a Propietarios
- Calculadas a partir de lo cobrado al inquilino menos gastos, comisiones y retenciones.
- Representan el dinero que la inmobiliaria debe transferir al propietario.
- Afectan la cuenta corriente del propietario.

### Recibos de Pago (tipo `payment`)
- Representan egresos a propietarios o proveedores.
- Cancelan liquidaciones, gastos u otras deudas.
- Afectan cuenta corriente y generan salida de caja.

### Gastos
- Registran egresos operativos (reparaciones, servicios, honorarios, etc.).
- Pueden imputarse a una propiedad y afectar liquidaciones.
- Se cancelan mediante un pago (`payment`).

---

## CAJA

### Caja
- Representa el punto físico de recaudación o el ciclo diario de operaciones.
- Se abre y cierra por día o usuario.
- Controla el saldo inicial, los movimientos y el cierre.

### Movimientos de Caja
- Generados automáticamente al registrar pagos (recibos o egresos).
- Incluyen tipo de movimiento, medio de pago y referencia al comprobante.
- Soportan operaciones en efectivo, transferencia, cheque u otros medios.

---

## Consideraciones Finales

- Todas las entidades se integran bajo un modelo contable coherente basado en comprobantes (`vouchers`).
- La cuenta corriente de clientes, propietarios y proveedores se actualiza automáticamente con cada operación financiera.
- La estructura está pensada para escalar e integrar en el futuro funcionalidades como factura electrónica o reportes impositivos.

