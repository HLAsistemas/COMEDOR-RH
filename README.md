## COMEDOR-RH

Aplicación independiente para el sistema de comedor y recursos humanos de HLA.
Este proyecto usa Laravel 12 y requiere PHP 8.2 o posterior,
separado del despliegue de HLA.

El módulo de consumos registra una comida por empleado y día y reporta el total
por empleado dentro de un rango de fechas. En **Menú del día** se capturan los
platillos y extras diarios; los platillos extra fijos activos aparecen
automáticamente cada día y se pueden desactivar cuando ya no se ofrezcan.

### Desarrollo local

Después de ingresar, se muestra un menú con accesos al registro de comidas y al
reporte por rango de fechas. Cada opción abre una pantalla independiente; el
encabezado permite regresar al menú o cambiar de sección.

Requiere PHP 8.2+, Composer y Node.js. Ejecuta `composer install` y `npm install`.
Si aún no existe `.env`, copia `.env.example` a `.env`; configura la conexión
a la base existente y genera la clave con `php artisan key:generate`. Para
desarrollo local por HTTP, configura `APP_ENV=local`, `APP_URL=http://localhost`
y `SESSION_SECURE_COOKIE=false`. Arranca Vite en una terminal con
`npm run dev` y Laravel en otra con `php artisan serve`.

### Contenedor y base compartida

COMEDOR-RH se ejecuta como contenedor independiente del stack Laradock y se une
a la red existente donde Laradock conecta sus servicios con MySQL. Su imagen
instala Composer y compila Vite durante `docker compose build`; no hace falta
ejecutar `npm run build` en el contenedor `workspace` para este proyecto.

1. Conéctate al servidor por SSH usando un método seguro y entra a la carpeta
   Laradock. Inicia los servicios que HLA necesita si aún no están levantados:
   `docker compose up -d nginx mysql workspace phpmyadmin certbot`.
2. Obtén el nombre de la red a la que está conectado el contenedor MySQL:
   `docker inspect "$(docker compose ps -q mysql)" --format '{{range $name, $config := .NetworkSettings.Networks}}{{$name}}{{"\n"}}{{end}}'`.
   Si muestra más de una red, usa la que comparte con la aplicación HLA.
3. En la carpeta del proyecto COMEDOR-RH, copia `.env.example` a `.env`.
   Configura `HLA_DOCKER_NETWORK` con el nombre encontrado, además de
   `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`. `DB_HOST=mysql` apunta al
   servicio Laradock por su alias de red. Usa una cuenta dedicada con permisos
   mínimos, no `root`, y ajusta `APP_URL` al hostname HTTPS del servidor, sin
   el prefijo `/comedor`, que ya forma parte
   de las rutas. `ASSET_URL` debe conservar el prefijo `/comedor`.
   Mantén `SESSION_SECURE_COOKIE=true` cuando se accede por HTTPS. Si el
   despliegue se prueba directamente por HTTP (sin TLS), configúralo en `false`
   tanto en `.env` como en la variable del servicio Compose; el navegador no
   envía cookies `Secure` por HTTP, y se perdería la sesión al iniciar sesión.
4. Construye la imagen desde la carpeta COMEDOR-RH:
   `docker compose build`.
5. Genera una clave para esta instalación:
   `docker compose run --rm --no-deps comedor-rh php artisan key:generate --show`.
   Copia el valor completo a `APP_KEY` en `.env`; no uses la clave de HLA.
6. Antes de iniciar, pide al DBA que compruebe/aplique las migraciones
   `comedor_rh_*` con una cuenta de despliegue separada. No ejecutes
   migraciones sobre HLA sin su autorización.
7. Inicia y verifica el contenedor desde la carpeta COMEDOR-RH:
   `docker compose up -d`, `docker compose ps` y
   `docker compose logs --tail=100 comedor-rh`. El servicio no publica un
   puerto HTTP en el host; solo es accesible desde la red Docker.
8. Configura el Nginx de Laradock para enviar las solicitudes de
   `/comedor/` al servicio `comedor-rh:80` conservando ese prefijo en la URI,
   con un certificado TLS válido y redirección de HTTP a HTTPS. Comprueba que
   Nginx y COMEDOR-RH compartan la red Docker configurada. En el firewall
   permite el puerto HTTPS (443) y no publiques el puerto HTTP del contenedor.
   Aplica las migraciones aprobadas con el DBA antes de liberar, incluyendo
   `2026_10_27_120000_add_username_and_admin_to_comedor_rh_users_table` y
   `2026_10_27_130000_create_comedor_rh_menu_dia_extras_fijos_table`. Las cuentas existentes reciben como usuario la
   parte anterior a `@` de su correo, normalizada si hace falta; desde entonces
   inician sesión con ese usuario. Abre `https://<hostname>/comedor/login` para
   acceder. La primera cuenta creada con
   `docker compose exec comedor-rh php artisan comedor:operator` será
   administradora. En una instalación existente, el DBA/operador del servidor
   debe promover la cuenta elegida una sola vez con
   `docker compose exec comedor-rh php artisan comedor:admin <usuario>`.
   Después, esa cuenta puede crear usuarios desde **Administrar usuarios** en el
   menú principal. El registro no está disponible para usuarios sin iniciar sesión.

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

El comando `comedor:operator` solicita la contraseña sin mostrarla. Para
actualizaciones, trae el código nuevo, ejecuta `docker compose build` y después
`docker compose up -d`. Si se despliega sobre un contenedor existente, limpia
las cachés de Laravel con
`docker compose exec comedor-rh php artisan optimize:clear`; verifica que
`php artisan route:list --path=comedor` muestre las rutas con el prefijo. Las
comidas y sesiones locales se conservan en el volumen `comedor-storage`.

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
