<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Config.php';
\DndPortal\Config::loadDotEnv(dirname(__DIR__) . '/.env');
require dirname(__DIR__) . '/src/Database/Connection.php';
use DndPortal\Database\Connection;

if (PHP_SAPI !== 'cli' || \DndPortal\Config::env('APP_ENV') !== 'local' || \DndPortal\Config::env('DB_NAME') !== 'dnd_portal_development') {
    fwrite(STDERR, "Refused: local development database required.\n");
    exit(2);
}
$username = strtolower(trim((string)($argv[1] ?? '')));
$password = getenv('LOCAL_ADMIN_PASSWORD') ?: '';
if ($username === '' || $password === '' || strlen($password) < 8) {
    fwrite(STDERR, "Refused: username and a password of at least 8 characters are required.\n");
    exit(2);
}
$db = Connection::make();
$q = $db->prepare('SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE u.username=? AND u.account_status="active" AND r.role_key="portal_admin"');
$q->execute([$username]);
$id = $q->fetchColumn();
if ($id === false) {
    fwrite(STDERR, "Refused: active local portal admin not found.\n");
    exit(1);
}
$db->beginTransaction();
try {
    $q = $db->prepare('UPDATE user_credentials SET password_hash=?,password_algo=?,password_changed_at=UTC_TIMESTAMP(6) WHERE user_id=?');
    $q->execute([password_hash($password, PASSWORD_DEFAULT), PASSWORD_DEFAULT, $id]);
    $q = $db->prepare('UPDATE auth_sessions SET revoked_at=UTC_TIMESTAMP(6) WHERE user_id=? AND revoked_at IS NULL');
    $q->execute([$id]);
    $db->commit();
    echo "LOCAL_ADMIN_PASSWORD_UPDATED $username\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "Update failed; transaction rolled back.\n");
    exit(1);
}
