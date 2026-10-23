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

# Carga de dependencias.
require __DIR__ . '/../vendor/autoload.php';

# Carga de variables de entorno.
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

# Inicializa la conexión a la base de datos (Eloquent).
Database::init();

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'app' => $_ENV['APP_NAME'] ?? 'Suplos',
    'status' => 'ok',
]);
