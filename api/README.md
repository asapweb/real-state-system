# Real State API

## Documentación

- [Secciones del sistema / entidades](docs/documentacion_sistema_inmobiliario.md)
- [Lógica Funcional: Contratos de Alquiler](docs/logica_funcional_contratos.md)
- [Lógica Funcional: Generación de Cobranzas Mensuales](docs/logica_cobranzas_mensuales.md)
- [Propuesta de Modelo de Gestión Financiera Integral para Inmobiliarias](docs/Propuesta_Modelo_Gestion_Financiera_Inmobiliarias.md)
- [Módulo de Mantenimiento - Sistema de Gestión Inmobiliaria”](docs/modulo_mantenimiento_inmobiliaria.md)
## Entorno de desarrollo

### DDEV
* PHP 8.3 con Nginx + PHP-FPM.
* Xdebug deshabilitado para mejor rendimiento.
* Sin contenedor de base de datos (db), lo que indica que usarás una base de datos externa.
* Carpetas de subida definidas (public/uploads y storage/app).

* Reverb
  * Puertos expuestos para Reverb:
    * Contenedor: 8080
    * HTTP: 8081
    * HTTPS: 8080
  * Daemon para Reverb, ejecutando php artisan reverb:start al iniciar el contenedor.

Archivo .ddev/caonfig.yaml   
```yaml
name: real-state-api
type: laravel
docroot: public
php_version: "8.3"
webserver_type: nginx-fpm
xdebug_enabled: false
additional_hostnames: []
additional_fqdns: []
omit_containers: ["db"]
use_dns_when_possible: true
composer_version: "2"
web_environment: []
corepack_enable: false
upload_dirs:
    - public/uploads
    - storage/app
web_extra_exposed_ports:
  - name: reverb
    container_port: 8080
    http_port: 8081
    https_port: 8080
web_extra_daemons:
  - name: reverb
    command: bash -c 'php artisan reverb:start'
    directory: /var/www/html
```

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
