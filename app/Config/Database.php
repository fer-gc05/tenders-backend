<?php

declare(strict_types=1);

namespace App\Config;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Inicializa la conexión a la base de datos y Eloquent.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.1
 * @since     2026-10-22
 * @updated   2026-10-24
 * @package   App\Config
 *
 * @change    1.0.1 - 2026-10-24 - Fernando Luis Gil Correa - Tipos
 *            estrictos.
 */
class Database
{
    public static function init(): void
    {
        $config = require __DIR__ . '/../../config/Database.php';

        $capsule = new Capsule();
        $capsule->addConnection($config);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}
