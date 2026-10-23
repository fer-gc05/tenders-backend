# Suplos

Aplicación de licitaciones construida en PHP con los componentes de Laravel (`illuminate/database` y `illuminate/events`) y Eloquent como ORM.

## Requisitos

- PHP 8.5
- Composer 2.x
- MySQL con la extensión `pdo_mysql`

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
  Config/        Configuración e inicialización de servicios
  Controllers/   Controladores
  Models/        Modelos Eloquent
config/          Archivos de configuración
public/          Punto de entrada (index.php)
```

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
