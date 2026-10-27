<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Config.php';
\DndPortal\Config::loadDotEnv(dirname(__DIR__) . '/.env');

if (PHP_SAPI !== 'cli' || \DndPortal\Config::env('APP_ENV') !== 'local') {
    fwrite(STDERR, "Refused: local CLI environment required.\n");
    exit(2);
}
$name = \DndPortal\Config::env('DB_NAME');
$rootPassword = \DndPortal\Config::env('LOCAL_DB_ROOT_PASSWORD');
if ($name !== 'dnd_portal_development' || $rootPassword === '') {
    fwrite(STDERR, "Refused: explicit development database and local root password required.\n");
    exit(2);
}
$host = \DndPortal\Config::env('DB_HOST', '127.0.0.1');
$port = \DndPortal\Config::env('DB_PORT', '3307');
$pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", 'root', $rootPassword, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$q = $pdo->prepare('SELECT SCHEMA_NAME
FROM
    INFORMATION_SCHEMA.SCHEMATA
WHERE SCHEMA_NAME
    = ?');
$q->execute([$name]);
if ($q->fetchColumn() === false) {
    $pdo->exec(
        'CREATE DATABASE `dnd_portal_development` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
    );
    $pdo->exec("GRANT ALL PRIVILEGES ON `dnd_portal_development`.* TO 'dnd_test'@'%'");
    $pdo->exec('FLUSH PRIVILEGES');
    echo "CREATED dnd_portal_development\n";
} else {
    echo "EXISTS dnd_portal_development\n";
}
