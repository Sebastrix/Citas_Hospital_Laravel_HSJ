# Citas Hospital HSJ - Laravel 13 + SQL Server

Esta versión está preparada para trabajar **sin MySQL**.

## Stack

- PHP 8.4
- Laravel 13
- Blade
- HTML
- JavaScript
- Tailwind CSS 4
- Preline 4
- Microsoft SQL Server mediante `pdo_sqlsrv` / `sqlsrv`
- Composer
- Node.js + npm solo para compilar recursos frontend

## Bases de datos

### CITAS local

- Motor: Microsoft SQL Server
- Conexión Laravel: `sqlsrv`
- Base: `CITAS`
- En desarrollo local puede utilizar autenticación integrada de Windows dejando usuario y contraseña vacíos.
- Guarda sala de espera, registros y logs del chatbot.

### SIGH remoto

- Motor: Microsoft SQL Server
- Conexión Laravel: `sigh`
- Base: `SIGH`
- Se usa para información clínica, pacientes, programación, especialidades, médicos, citas y disponibilidad.

## Extensiones PHP requeridas

```powershell
php -m | findstr /I "pdo_sqlsrv sqlsrv"
```

Debe mostrar:

```text
pdo_sqlsrv
sqlsrv
```

No se requiere `pdo_mysql`.

## .env local para CITAS

```env
DB_CONNECTION=sqlsrv
DB_HOST=(local)
DB_PORT=
DB_DATABASE=CITAS
DB_USERNAME=
DB_PASSWORD=
DB_ENCRYPT=false
DB_TRUST_SERVER_CERTIFICATE=true
```

## Verificación de CITAS

```powershell
php artisan optimize:clear
php artisan tinker
```

```php
DB::connection('sqlsrv')->selectOne("SELECT DB_NAME() AS base, @@SERVERNAME AS servidor, SYSTEM_USER AS usuario");
```

## Levantar sistema

```powershell
composer install
Copy-Item .env.example .env
# Configurar en .env las conexiones CITAS y SIGH antes de continuar.
php artisan key:generate
php artisan optimize:clear
php artisan migrate
npm install
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

No usar `php artisan migrate:fresh` sobre una base CITAS que ya contiene información.
