<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Config.php';
use DndPortal\Config;

Config::loadDotEnv(dirname(__DIR__) . '/.env');
$name = $argv[1] ?? '';
$password = getenv('TEST_DB_ROOT_PASSWORD') ?: '';
if (
    PHP_SAPI !== 'cli' ||
    !preg_match('/^dnd_portal_(?:test_run|migration_test)_[0-9]+$/', $name) ||
    $password === ''
) {
    fwrite(STDERR, "Refused: explicit test database name and TEST_DB_ROOT_PASSWORD required.\n");
    exit(2);
}
$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;charset=utf8mb4',
        getenv('DB_HOST') ?: '127.0.0.1',
        getenv('DB_PORT') ?: '3307',
    ),
    'root',
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);
$pdo->exec('CREATE DATABASE `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('GRANT ALL PRIVILEGES ON `' . $name . '`.* TO \'dnd_test\'@\'%\'');
$pdo->exec('FLUSH PRIVILEGES');
echo "CREATED $name\n";
