# 🏠 Sistema de Gestión Inmobiliaria - CASSA

Este repositorio contiene el código y documentación del sistema de gestión inmobiliaria desarrollado para CASSA. El objetivo principal es digitalizar y automatizar los procesos internos de la inmobiliaria, haciendo foco inicialmente en **gestión de alquileres**, **cobranzas**, **liquidaciones**, **cuentas corrientes**, y más adelante extender a portales de inquilinos y propietarios.

---

## 📁 Estructura del Repositorio

```
real-state-system/
├── api/                # Backend Laravel 11 (API REST)
├── front/              # Frontend Vue 3 + Vuetify
├── docs/               # Documentación funcional y técnica
├── .env.example        # Configuración base
└── README.md           # Este archivo
```

---

## ✅ Estado del Proyecto

- [x] Gestión de propiedades
- [x] Gestión de contratos y partes
- [x] Generación automática de cobranzas mensuales
- [x] Cobranzas manuales
- [x] Gestión de penalidades, bonificaciones y prorrateos
- [x] Gestión de gastos asociados al contrato
- [x] Liquidaciones a propietarios (pendiente)
- [ ] Portal de propietarios e inquilinos (segunda etapa)

---

## 🧠 Reglas de Negocio Clave

- Todos los procesos se centran en el **contrato** como entidad principal.
- Las **cobranzas mensuales** son generadas automáticamente considerando ajustes, bonificaciones, servicios y prorrateos.  
- Los **punitorios por mora NO se calculan en la cobranza mensual**, sino en el momento de **generar el recibo de cobranza** si hay pagos fuera de término.
- Las **cobranzas manuales** permiten cobrar montos adicionales o fuera de cronograma.
- La **cuenta corriente** de cada cliente refleja todos los movimientos (cargos, pagos, bonificaciones).
- Los **gastos** pueden ser pagados por la agencia, el propietario o el inquilino, y se trasladan mediante cargos o bonificaciones.

---

## 🧩 Módulos Principales

1. **Propiedades (`Property`)**
   - Dirección, tipo, ubicación geográfica, dueños, servicios, etc.

2. **Contratos (`Contract`)**
   - Asociados a propiedades e inquilinos.
   - Incluyen ajustes, bonificaciones, servicios y gastos recurrentes.

3. **Cobranzas (`Collection`)**
   - Automáticas por mes y contrato.
   - Manuales con conceptos personalizados.

4. **Gastos de contrato (`ContractExpense`)**
   - Servicios, reparaciones o impuestos que deben trasladarse a alguna de las partes.

5. **Ajustes (`ContractAdjustment`)**
   - Índices (ICL, CREEBBA, CASAPROPIA), porcentuales, fijos o negociados.

6. **Pagos (`Voucher`, `VoucherPayment`)**
   - Aplican a cobranzas, generan movimientos de caja.

---

## 🛠️ Tecnologías

- **Backend**: Laravel 11
- **Frontend**: Vue 3 + Vuetify 3
- **Base de datos**: MySQL 8
- **Testing**: PHPUnit moderno (con `#[Test]`)
- **Documentación**: Markdown en `/docs` + integración con Cursor y ChatGPT

---

## 👥 Flujo de Trabajo Colaborativo

- **ChatGPT**: análisis, definiciones funcionales, refactor y ayuda general.
- **Cursor**: escritura y edición directa del código, implementación de tests, lectura de contexto de proyecto.
- **Usuario (vos)**: decisiones estratégicas, control funcional, validaciones con cliente y visión general.

Para mantener sincronización, los siguientes archivos deben mantenerse **siempre actualizados**:

| Archivo | Descripción |
|--------|-------------|
| `README.md` | Panorama general del proyecto |
| `docs/flujo-cobranzas.md` | Lógica completa de generación de cobranzas |
| `docs/entidades.md` | Entidades, relaciones y campos |
| `docs/ajustes.md` | Gestión de ajustes de contratos |
| `docs/bonificaciones.md` | Reglas para bonificaciones |
| `docs/gastos.md` | Servicios y gastos prorrateados |
| `docs/punitorios.md` | Reglas para intereses por mora |
| `docs/liquidaciones.md` | (Pendiente) Lógica de liquidaciones a propietarios |

---

## 📦 Instalación rápida (modo desarrollo)

### Requisitos

- PHP 8.3, MySQL 8, Node 20+
- Docker + DDEV recomendado

```bash
# Iniciar entorno
ddev start

# Backend
cd api
composer install
cp .env.example .env
php artisan migrate:fresh --seed

# Frontend
cd ../front
npm install
npm run dev
```

---

## 🧪 Testing

```bash
# Ejecutar tests del backend
cd api
php artisan test
```

---

## 📄 Licencia

Uso privado para CASSA. No redistribuir.