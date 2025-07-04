# 🏗️ Arquitectura del Backend (Laravel)

Este documento define las convenciones, estructuras y buenas prácticas que deben respetarse en el desarrollo del backend del sistema inmobiliario, implementado en **Laravel 11**. Actúa como guía oficial para Cursor, ChatGPT y el equipo de desarrollo.

---

## 🧱 Stack Tecnológico

- **Framework**: Laravel 11
- **PHP**: 8.3
- **Base de Datos**: MySQL 8
- **Autenticación**: Laravel Sanctum con token Bearer
- **Testing**: PHPUnit (sintaxis moderna `#[Test]`)
- **Documentación**: Markdown + arquitectura desacoplada en `ARCHITECTURE.md`

---

## 📦 Estructura y Estándares

### Rutas (`routes/api.php`)

- Todas las rutas van bajo el prefijo `/api`.
- Las rutas están protegidas con `auth:sanctum`.
- Se definen manualmente, **no se usa `Route::apiResource()` por defecto**.
- Cada entidad tiene su grupo de rutas propias (CRUD + extras como `generate`, `preview`, etc.).

### Controladores

- Ubicados en `App\Http\Controllers\`.
- Siguen el patrón: `index()`, `store()`, `show()`, `update()`, `destroy()`.
- Devuelven respuestas consistentes:
  - `index()` → `ModelResource::collection(...)`
  - `show()` → `new ModelResource(...)`
  - `store()` → `201 Created` con el recurso
  - `update()` → `200 OK` con el recurso
- Métodos deben ser **delgados**: la lógica se delega a Services.

### Validación

- Se usa **Form Request obligatorio** (`php artisan make:request`).
- No se valida directamente en el controlador.
- Validaciones condicionales (`requiredIf`, `nullable`, etc.) se usan para flags como `no_document`.

### Resources (`App\Http\Resources\`)

- Se usa siempre `ApiResource` para controlar la salida.
- Se incluyen relaciones condicionalmente (`whenLoaded`).
- Se agregan campos virtuales (`full_name`, `is_overdue`, etc.) si aportan valor.

### Enums

- Clases de Enum: `PascalCase` → `ContractAdjustmentType`
- Valores: `SCREAMING_SNAKE_CASE` → `FIXED`, `PERCENTAGE`, etc.
- Se castean en el modelo (`$casts`) como:
  ```php
  protected $casts = [
      'type' => ContractAdjustmentType::class,
  ];
  ```

### Mutators

- Se utilizan `set` mutators para limpieza automática de campos dependientes (ej. `no_document`).
- Permiten mantener los controladores más simples.

---

## 🧪 Testing

- Se usa PHPUnit con atributos:
  ```php
  #[Test]
  public function it_validates_something() { ... }
  ```
- Ubicación:
  - Unitarios: `tests/Unit/`
  - Funcionales: `tests/Feature/`
- Factories completas para todas las entidades, incluyendo relaciones.
- Tests deben cubrir:
  - Validación de requests
  - Generación de cobranzas
  - Aplicación de ajustes, penalidades, bonificaciones
  - Serialización de recursos

---

## 💡 Convenciones Adicionales

- Métodos `scopeActiveDuring`, `scopeSearch` definidos en modelos para búsquedas.
- Campos booleanos y de control (`is_one_time`, `included_in_collection`) se documentan y se castean explícitamente.
- Métodos `markAsApplied()`, `calculateRentForPeriod()`, etc., centralizan la lógica de negocio.

---

## 📎 Pendientes a revisar con Cursor

- Revisión de todos los `show()` para que devuelvan `ApiResource`.
- Verificación de `index()` para que usen `ModelResource::collection(...)`.
- Estandarización de enums en todo el código a `SCREAMING_SNAKE_CASE`.

---

Este archivo debe mantenerse sincronizado con el contrato general (`ARCHITECTURE.md`) y refleja cómo se implementan internamente las reglas descritas ahí.
