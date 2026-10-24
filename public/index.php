<?php

declare(strict_types=1);

/**
 * Archivo principal de la aplicación.
 * Punto de entrada.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.1.0
 * @since     2026-10-22
 * @updated   2026-10-24
 * @package   App
 *
 * @change    1.1.0 - 2026-10-24 - Fernando Luis Gil Correa - Zona horaria
 *            de la aplicación.
 */

use Dotenv\Dotenv;
use App\Config\Database;
use App\Support\ErrorHandler;

# Carga de dependencias.
require __DIR__ . '/../vendor/autoload.php';

# Carga de variables de entorno.
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

# Zona horaria de la aplicación. Debe coincidir con la zona horaria del
# servidor de base de datos para que las marcas de tiempo que escribe Eloquent
# (created_at, updated_at) y los valores por defecto de MySQL/MariaDB
# (CURRENT_TIMESTAMP) queden en el mismo instante.
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Bogota');

# Registro del manejador global de errores.
ErrorHandler::register();

# Inicializa la conexión a la base de datos (Eloquent).
Database::init();

# Despacha la solicitud hacia la ruta correspondiente.
$router = require __DIR__ . '/../routes/api.php';
$router->dispatch();
