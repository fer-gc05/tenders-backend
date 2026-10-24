<?php

/**
 * Archivo principal de la aplicación.
 * Punto de entrada.
 *
 * @author Fernando Luis Gil Correa
 * @version 1.0.0
 * @since 2026-10-22
 * @package App
 */

use Dotenv\Dotenv;
use App\Config\Database;
use App\Support\ErrorHandler;

# Carga de dependencias.
require __DIR__ . '/../vendor/autoload.php';

# Carga de variables de entorno.
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

# Registro del manejador global de errores.
ErrorHandler::register();

# Inicializa la conexión a la base de datos (Eloquent).
Database::init();

# Despacha la solicitud hacia la ruta correspondiente.
$router = require __DIR__ . '/../routes/api.php';
$router->dispatch();
