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

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Ejecutar GestiON con Docker

Esta configuración está pensada para desarrollo local con Windows y Docker Desktop. Usa PHP-FPM, Nginx, MySQL y Vite; no ejecuta migraciones ni seeders automáticamente. No reemplaza el `.env` existente ni importa la base de datos de Windows.

No se agrega un worker ni un servicio scheduler: la aplicación actual no define trabajos encolados ni tareas programadas. Si en el futuro se agregan jobs, se pueden procesar manualmente con `docker compose exec app php artisan queue:work`.

### Requisitos y configuración inicial

1. Instala y abre Docker Desktop con el motor Linux/WSL2 habilitado; se necesita Docker Compose v2.
2. Desde PowerShell, sitúate en la carpeta `gestion`. Si todavía no existe `.env`, créalo copiando la plantilla:

   ```powershell
   Copy-Item .env.example .env
   ```

   Si ya existe, no lo sobrescribas. Compose establece dentro del contenedor el host y los datos de conexión de su base aislada. Los valores `DOCKER_DB_*` de `.env.example` son credenciales locales de desarrollo; cámbialos antes de compartir el entorno.
3. La `APP_KEY` no se genera al arrancar. Si `.env` ya tiene una, se conserva. Si está vacía, después de iniciar los servicios genérala una sola vez:

   ```powershell
   docker compose exec app php artisan key:generate
   ```

   El archivo `.env` está montado desde Windows, así que la clave queda guardada y se mantiene al reiniciar.

### Construir y levantar

La primera vez, construye las imágenes y levanta los servicios:

```powershell
docker compose up -d --build
```

En siguientes arranques:

```powershell
docker compose up -d
docker compose ps
```

Abre GestiON en [http://localhost:8082](http://localhost:8082). Vite se sirve desde [http://localhost:5174](http://localhost:5174) para desarrollo con HMR. El puerto interno de Vite es 5173; el puerto 5174 del host se eligió porque 5173 ya estaba ocupado durante la inspección. Si fuera necesario, cambia `DOCKER_VITE_PORT` en `.env` y mantén ese puerto disponible. MySQL solo es accesible en la red Docker, en `mysql:3306`; no publica el puerto 3306 de Windows.

### Base de datos y datos existentes

El servicio utiliza MySQL 8.0.46 y el host interno `mysql`. La base de datos de Docker es independiente de la instancia MySQL 8.0.46 instalada en Windows; no copia ni modifica sus datos. La primera vez, si quieres crear el esquema vacío, ejecuta las migraciones explícitamente:

```powershell
docker compose exec app php artisan migrate
```

Los seeders también son manuales y opcionales:

```powershell
docker compose exec app php artisan db:seed
```

Antes de importar datos existentes, haz una copia de seguridad y revisa el destino; no se realiza ninguna importación automáticamente. Los volúmenes `mysql_data`, `storage_data` y `bootstrap_cache_data` conservan la base, los archivos de Laravel y su caché escribible entre reinicios y `docker compose down`. **No uses `docker compose down -v`** si quieres conservarlos. El almacenamiento runtime está separado de la carpeta de Windows para que PHP-FPM pueda escribir con permisos Linux correctos.

### Comandos de desarrollo

```powershell
# Artisan
docker compose exec app php artisan about
docker compose exec app php artisan route:list

# Dependencias PHP y pruebas
docker compose exec app composer install
docker compose exec app php artisan test

# Vite inicia automáticamente con Docker Compose
docker compose logs -f node
docker compose exec node npm run build

# Shell de cada servicio
docker compose exec app bash
docker compose exec node sh
docker compose exec mysql bash

# Logs, reinicio y parada
docker compose logs -f
docker compose restart
docker compose down
```

Si cambias dependencias de Node, actualiza el lockfile deliberadamente y ejecuta `docker compose build node` seguido de `docker compose up -d node`; no se actualizan paquetes automáticamente. `npm run build` genera los assets de producción en `public/build`.

### Comprobaciones

```powershell
docker compose ps
docker compose exec app php artisan about
docker compose exec app php artisan db:show
curl.exe -I http://localhost:8082/up
```

`db:show` comprueba la conexión de Laravel con MySQL; `/up` comprueba que la aplicación responde a través de Nginx. El enlace `public/storage` no se crea al iniciar porque el código actual no almacena archivos en el disco público. Si una funcionalidad futura lo requiere, créalo manualmente con `docker compose exec app php artisan storage:link`.

El servicio `jenkins` preexistente queda en el perfil opcional `ci` y no se inicia con los comandos anteriores. Se conserva separado de los servicios necesarios para ejecutar GestiON.
