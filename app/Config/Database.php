<?php

namespace App\Config;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Inicializa la conexión a la base de datos y Eloquent.
 *
 * @author Fernando Luis Gil Correa
 * @version 1.0.0
 * @since 2026-10-22
 * @package App\Config
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
