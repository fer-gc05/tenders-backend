# Suplos

API de licitaciones construida en PHP puro, con los componentes de Laravel (`illuminate/database` y `illuminate/events`) y Eloquent como ORM. Sigue una arquitectura MVC sin frameworks ni micro-frameworks.

## Requisitos

- PHP 8.3 o superior
- Composer 2.x
- MySQL o MariaDB con la extensión `pdo_mysql`

## Instalación

```bash
composer install
cp .env.example .env
```

Edita `.env` con las credenciales de tu base de datos y crea la base de datos `suplos`.

## Ejecución

```bash
composer serve
```

La aplicación queda disponible en `http://localhost:8000`.

## Estructura

```
app/
├── Config/
│   └── Database.php          Inicialización de la conexión y Eloquent
├── Controllers/
├── Http/
│   ├── Controller.php        Controlador base
│   ├── Request.php           Solicitud HTTP
│   ├── Response.php          Respuesta HTTP
│   └── Router.php            Enrutador
├── Models/
├── Support/
│   └── ErrorHandler.php      Manejo global de errores y excepciones
└── Views/                    Representación JSON de los recursos
config/
└── Database.php              Configuración de la base de datos
public/
└── index.php                 Punto de entrada
```

El flujo MVC es `Request → Router → Controller → Model → View → Response`.

Las carpetas `Controllers`, `Models` y `Views` quedan preparadas para su implementación. Las clases de `app/Http` y `app/Support` son la base estructural (sin lógica todavía).

## Variables de entorno

| Variable | Descripción | Valor por defecto |
| --- | --- | --- |
| `APP_NAME` | Nombre de la aplicación | `Suplos` |
| `APP_ENV` | Entorno de ejecución | `local` |
| `DB_CONNECTION` | Driver de base de datos | `mysql` |
| `DB_HOST` | Host de la base de datos | `127.0.0.1` |
| `DB_PORT` | Puerto de la base de datos | `3306` |
| `DB_DATABASE` | Nombre de la base de datos | `suplos` |
| `DB_USERNAME` | Usuario de la base de datos | `root` |
| `DB_PASSWORD` | Contraseña de la base de datos | vacío |
