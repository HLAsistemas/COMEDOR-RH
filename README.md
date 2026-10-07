## COMEDOR-RH

Aplicación independiente para el sistema de comedor y recursos humanos de HLA.
Este proyecto usa Laravel 12 y requiere PHP 8.2 o posterior,
separado del despliegue de HLA.

La primera versión registra una comida por empleado y día y reporta el total
por empleado dentro de un rango de fechas. No incluye menús, precios ni
subsidios.

### Desarrollo local

Después de ingresar, se muestra un menú con accesos al registro de comidas y al
reporte por rango de fechas. Cada opción abre una pantalla independiente; el
encabezado permite regresar al menú o cambiar de sección.

Requiere PHP 8.2+, Composer y Node.js. Ejecuta `composer install` y `npm install`.
Si aún no existe `.env`, copia `.env.example` a `.env`; configura la conexión
a la base existente y genera la clave con `php artisan key:generate`. Arranca
Vite en una terminal con `npm run dev` y Laravel en otra con
`php artisan serve --host=192.168.11.123 --port=8001`.

### Contenedor y base compartida

1. Copia `.env.example` a `.env` y configura la conexión MySQL a la base de HLA
   con los valores entregados de forma segura por su administrador.
2. Configura `HLA_DOCKER_NETWORK` con el nombre de la red Docker existente
   compartida por HLA y MySQL. Usa en `DB_HOST` el nombre de servicio o alias DNS
   del contenedor MySQL dentro de esa red.
3. Establece `APP_ENV=production`, `APP_DEBUG=false` y el `APP_URL` real.
4. Construye la imagen con `docker compose build` y genera una clave con
   `docker compose run --rm --no-deps comedor-rh php artisan key:generate --show`.
   Copia el valor mostrado a `APP_KEY` en `.env`.
5. Ejecuta `docker compose up -d`. El puerto por defecto es `8001` y se puede
   cambiar con `COMEDOR_RH_PORT`.

El Compose no ejecuta migraciones. Antes de liberar, coordina con HLA la creación
de las tablas `comedor_rh_users` y `comedor_rh_consumos`; la aplicación usa una
tabla de historial propia llamada `comedor_rh_migrations`. La aplicación no
modifica el padrón HLA. Un DBA debe ejecutar las migraciones aprobadas con una
cuenta de despliegue separada; no otorgues permisos DDL a la cuenta de runtime.
Esta última solo necesita lectura sobre las tablas de trabajadores y lectura/
registro sobre las tablas de COMEDOR-RH. Sesiones y caché usan almacenamiento
local persistido en un volumen y los trabajos se ejecutan de forma síncrona.
Configura firewall y permisos de MySQL de forma restrictiva, y nunca agregues
`.env` ni credenciales al control de versiones.

Después de crear las tablas, crea cada acceso autorizado con
`docker compose exec comedor-rh php artisan comedor:operator`; el comando
solicita la contraseña sin mostrarla.

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

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
