<?php

declare(strict_types=1);

/**
 * Router para el servidor embebido de PHP (solo desarrollo).
 * Sirve los archivos estáticos existentes y delega el resto al front controller.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.1
 * @since     2026-10-24
 * @updated   2026-10-24
 * @package   App
 *
 * @change    1.0.1 - 2026-10-24 - Fernando Luis Gil Correa - Tipos
 *            estrictos.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

require __DIR__ . '/index.php';
