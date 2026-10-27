<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/Config.php';
require dirname(__DIR__) . '/src/Database/Connection.php';

use DndPortal\Config;
use DndPortal\Database\Connection;

Config::loadDotEnv(dirname(__DIR__) . '/.env');
$env = Config::env('APP_ENV', 'development');
$name = Config::env('DB_NAME');
if ($env === 'production') {
    fwrite(STDERR, "Refusing migrations in APP_ENV=production.\n");
    exit(1);
}
if ($env === 'testing' && !preg_match('/^(dnd_portal_test|dnd_portal_test_run_[0-9]+|dnd_portal_migration_test_[0-9]+|test_[a-z0-9_]+)$/', $name)) {
    fwrite(STDERR, "Refusing testing migrations outside an explicit test database.\n");
    exit(1);
}
if ($env === 'local' && $name !== 'dnd_portal_development') {
    fwrite(STDERR, "Refusing local migrations outside dnd_portal_development.\n");
    exit(1);
}

$db = Connection::make();
$db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(100) PRIMARY KEY, checksum CHAR(64) NOT NULL, applied_at DATETIME(6) NOT NULL) ENGINE=InnoDB');
$files = glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [];
sort($files, SORT_STRING);
foreach ($files as $file) {
    $version = basename($file, '.sql');
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException('Cannot read ' . $file);
    }
    $checksum = hash('sha256', $sql);
    $q = $db->prepare('SELECT checksum FROM schema_migrations WHERE version=?');
    $q->execute([$version]);
    $old = $q->fetchColumn();
    if ($old !== false) {
        if (!hash_equals((string)$old, $checksum)) {
            throw new RuntimeException("Checksum mismatch for applied migration $version");
        }
        echo "SKIP $version\n";
        continue;
    }
    $db->beginTransaction();
    try {
        $db->exec($sql);
        $q = $db->prepare('INSERT INTO schema_migrations(version,checksum,applied_at) VALUES(?,?,?)');
        $q->execute([$version, $checksum, gmdate('Y-m-d H:i:s.u')]);
        if ($db->inTransaction()) {
            $db->commit();
        }
        echo "APPLY $version\n";
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
}
