<?php

declare(strict_types=1);

namespace DndPortal\Database;

use DndPortal\Config;
use PDO;

final class Connection {
    public static function make(): PDO {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            Config::env('DB_HOST'),
            Config::env('DB_PORT', '3306'),
            Config::env('DB_NAME'),
        );
        return new PDO($dsn, Config::env('DB_USER'), Config::env('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
