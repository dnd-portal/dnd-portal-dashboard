<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Config.php';
\DndPortal\Config::loadDotEnv(dirname(__DIR__) . '/.env');
require dirname(__DIR__) . '/src/Database/Connection.php';
require dirname(__DIR__) . '/src/Auth/SessionStore.php';

use DndPortal\Config;
use DndPortal\Database\Connection;
use DndPortal\Auth\SessionStore;

if (PHP_SAPI !== 'cli' || Config::env('APP_ENV') !== 'local' || Config::env('DB_NAME') !== 'dnd_portal_v4_development') {
    fwrite(STDERR, "Refused: V4 local development database required.\n");
    exit(2);
}

$password = rtrim((string) fgets(STDIN), "\r\n");
if ($password === '' || strlen($password) < 8) {
    fwrite(STDERR, "Refused: password must be at least 8 characters.\n");
    exit(2);
}

$db = Connection::make();
$q = $db->prepare(
    <<<'SQL'
    SELECT u.id
    FROM users u
    JOIN user_roles ur ON ur.user_id = u.id
    JOIN roles r ON r.id = ur.role_id
    WHERE u.account_status = "active"
      AND r.role_key = "portal_admin"
    ORDER BY u.id
    LIMIT 1
    SQL
);
$q->execute();
$id = $q->fetchColumn();
if ($id === false) {
    fwrite(STDERR, "Refused: active local portal admin not found.\n");
    exit(1);
}

$credential = $db->prepare('SELECT 1 FROM user_credentials WHERE user_id = ?');
$credential->execute([(int) $id]);
if ($credential->fetchColumn() === false) {
    fwrite(STDERR, "Refused: existing v4admin credential not found.\n");
    exit(1);
}

$db->beginTransaction();
try {
    $update = $db->prepare(
        'UPDATE user_credentials SET password_hash = ?, password_algo = ?, password_changed_at = UTC_TIMESTAMP(6) WHERE user_id = ?',
    );
    $update->execute([password_hash($password, PASSWORD_DEFAULT), PASSWORD_DEFAULT, (int) $id]);
    if ($update->rowCount() !== 1) {
        throw new RuntimeException('Credential update affected an unexpected number of rows.');
    }
    (new SessionStore($db))->revokeUser((int) $id);
    $db->exec("DELETE FROM rate_limit_buckets WHERE bucket_type = 'login'");
    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "Update failed; transaction rolled back.\n");
    exit(1);
}
