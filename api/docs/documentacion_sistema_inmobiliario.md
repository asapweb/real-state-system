## Secciones del sistema / entidades

### Clientes
Los clientes representan personas físicas o jurídicas que interactúan con la inmobiliaria. Se pensó esta entidad para centralizar la información básica del cliente:
- Nombre y apellido (o razón social)
- Documento y tipo
- Condición fiscal
- Estado civil, nacionalidad

**Relaciones con otras entidades:**
- Propietarios de propiedades (vía `property_owners`)
- Postulantes o garantes en solicitudes de alquiler (`rental_application_clients`)
- Titulares de contratos de alquiler (`contracts`)

**Flujos:**
- Son seleccionables desde múltiples formularios mediante autocompletado.
- Su creación puede darse desde las secciones de propiedades, solicitudes o contratos.

**Archivos:**
- Se pueden adjuntar archivos específicos del cliente (DNI, comprobantes, etc.), clasificados por categoría.

**Resumen de estados:**
- Activo: Cliente habilitado para operaciones
- Inactivo: Cliente deshabilitado, por baja o duplicación

---

### Propiedades
La entidad `Property` representa inmuebles ofrecidos por la inmobiliaria. Fue diseñada para reflejar tanto su ubicación como su composición administrativa y técnica.

**Información cargada:**
- Dirección normalizada (calle, número, ciudad, barrio)
- Tipo de propiedad
- Estado y observaciones

**Servicios:**
- Se cargan los servicios asociados al inmueble (luz, gas, internet, etc.) mediante `property_services`
- Cada servicio tiene tipo, número de cuenta, proveedor y titular
- Estos servicios pueden ser replicados luego en una oferta de alquiler

**Propietarios:**
- Una propiedad puede tener uno o más propietarios vía `property_owners`, con porcentaje de titularidad

**Relaciones:**
- Puede tener ofertas (`rental_offers`) y contratos (`contracts`)

**Archivos:**
- Se pueden adjuntar planos, informes técnicos, etc.

**Resumen de estados:**
- En preparación: Cargándose o con documentación incompleta
- Activa: Publicable o utilizable
- Reservada: Asociada a una solicitud o contrato pendiente
- Alquilada: Con contrato activo vigente
- Inactiva: No disponible por baja o fuera del mercado

---

### Ofertas de Alquiler
Las `rental_offers` representan inmuebles en condiciones de ser alquilados.

**Información cargada:**
- Precio, moneda, fechas de publicación y vencimiento
- Observaciones
- Relación con la propiedad

**Diferencias con la propiedad:**
- La oferta permite múltiples ofertas por una misma propiedad
- No toda propiedad debe tener una oferta vigente

**Visualización de solicitudes:**
- En el `show` de una oferta se listan las solicitudes (`rental_applications`) vinculadas a ella
- Se puede crear una nueva solicitud desde esta vista

**Gestión de servicios:**
- Se copian desde la propiedad al momento de crear la oferta
- Se puede editar el estado de los servicios (activo, con deuda, monto, quién paga)
- Se gestiona desde `rental_offer_service_statuses`

**Archivos:**
- Contratos borradores, requisitos del propietario, etc.

**Resumen de estados:**
- Borrador: No publicada, editable
- Publicada: Disponible públicamente
- Vencida: Caducó la vigencia
- Alquilada: Asociada a contrato activo

---

### Solicitudes de Alquiler
Las `rental_applications` son el corazón del proceso de evaluación para firmar un contrato.

**¿Qué son?**
- Representan el proceso de postulación de una persona a una oferta o propiedad
- Permiten capturar garantías, reservas, notas internas y documentos

**Información cargada:**
- Oferta y propiedad relacionada
- Postulante (cliente)
- Monto reservado, moneda, estado
- Seguro requerido

**Personas asociadas:**
- Garantes, co-solicitantes, cónyuge, etc., mediante `rental_application_clients`
- Se cargan datos personales y laborales

**Archivos:**
- Recibos de sueldo, garantías, formularios

**Resumen de estados:**
- Borrador: Incompleta o aún no evaluada
- En evaluación: En análisis por la inmobiliaria
- Aprobada: Apta para pasar a contrato
- Rechazada: No califica
- Cancelada: Desestimada por el postulante o el sistema

---

### Contrato
La entidad `contracts` representa el acuerdo formal y legal entre inquilino y propietario.

**¿Cómo se pensó?**
- Se genera generalmente desde una `rental_application` aprobada, aunque podría crearse de forma directa
- Incluye toda la información necesaria para definir condiciones contractuales, plazos, montos, seguros, comisiones y penalidades

**Congelamiento de condiciones:**
- El contrato repite parte de la información de la oferta y/o la solicitud como fuente de verdad, incluso si los datos originales cambian luego.
- Esto asegura trazabilidad legal y autonomía documental.
- Se consideran parte del contrato los valores que hayan sido editados o cargados directamente allí, como nueva "fuente de verdad" contractual

**Información cargada:**
- Relación con la propiedad y la solicitud
- Fecha de inicio y fin
- Monto mensual y moneda
- Día de pago mensual
- Comisión (tipo, monto, quién la paga)
- Seguro (si es requerido, monto, empresa aseguradora)
- Porcentaje del propietario (en caso de compartir con otro actor)
- Depósito (tipo, monto, quién lo retiene)
- Penalidades por mora (tipo, monto, días de gracia)
- Notas internas y archivos

**Personas asociadas y servicios:**
- Independientemente de si se origina desde una solicitud o no, se pueden agregar, editar o eliminar personas asociadas al contrato (garantes, cónyuges, etc.).
- También se pueden editar o agregar servicios específicos de este contrato, distintos de los registrados en la propiedad o la oferta.

**Archivos:**
- Contrato firmado, anexos, seguros, depósitos

**Resumen de estados:**
- Borrador: Contrato en edición o en revisión previa a firma
- Activo: Contrato firmado y vigente
- Finalizado: Venció el contrato en su fecha establecida
- Rescindido: Finalizado de forma anticipada

---

### Archivos
La gestión de archivos se pensó como polimórfica:
- Modelo `Attachment` y `AttachmentCategory`
- Relacionado con cualquier entidad del sistema (`clients`, `properties`, `rental_offers`, `rental_applications`, etc.)

**Categorías:**
- Definidas por contexto: Ej. "DNI", "Factura de Luz", "Contrato Borrador"

**Integración:**
- Cada entidad cuenta con un bloque de gestión de archivos (con vista y carga)
- Los archivos se listan filtrados por categoría

---

## Flujos y estados

**Flujos posibles:**

**Flujo 1:** Cliente y Propiedad -> Oferta -> Solicitud -> Contrato
- Caso más completo: se publica una propiedad, alguien postula, y se firma contrato

**Flujo 2:** Cliente y Propiedad -> Solicitud -> Contrato
- Sin oferta intermedia: se postula directamente a una propiedad

**Flujo 3:** Cliente y Propiedad -> Contrato
- Contratos preexistentes, o firma directa sin evaluación

**Estados generales:**

**Cliente**
- Activo: Cliente utilizable en operaciones
- Inactivo: Cliente dado de baja o sin vigencia

**Propiedad**
- En preparación: Datos aún incompletos
- Activa: Disponible para ofertar
- Reservada: Con solicitud pendiente
- Alquilada: Con contrato vigente
- Inactiva: Fuera de uso o del mercado

**Oferta**
- Borrador: Editable, no publicada
- Publicada: Disponible en sistema
- Vencida: Fecha de vigencia superada
- Alquilada: Ya adjudicada a contrato

**Solicitud**
- Borrador: Sin completar
- En evaluación: En análisis
- Aprobada: Lista para contrato
- Rechazada: Denegada por evaluación
- Cancelada: Abortada por usuario o inmobiliaria

**Contrato**
- Borrador: Aún sin firmar, editable
- Activo: Vigente y en curso
- Finalizado: Concluido por vencimiento de fechas
- Rescindido: Cancelado de forma anticipada

