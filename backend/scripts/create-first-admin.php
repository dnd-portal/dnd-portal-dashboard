<?php

declare(strict_types=1);

// Operator-only CLI procedure. Keep this file outside the public webroot.
require dirname(__DIR__) . '/src/Config.php';
\DndPortal\Config::loadDotEnv(dirname(__DIR__) . '/.env');
require dirname(__DIR__) . '/src/Database/Connection.php';

use DndPortal\Database\Connection;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}
$email = trim((string) ($argv[1] ?? ''));
$username = strtolower(trim((string) ($argv[2] ?? '')));
$name = trim((string) ($argv[3] ?? ''));
$password = getenv('INITIAL_ADMIN_PASSWORD') ?: '';
if ($password === '' && PHP_SAPI === 'cli') {
    fwrite(STDERR, 'Initial admin password (hidden input is recommended): ');
    $password = trim((string) fgets(STDIN));
}
if ($email === '' || $username === '' || $name === '' || $password === '') {
    fwrite(
        STDERR,
        "Usage: php backend/scripts/create-first-admin.php email username display-name\n",
    );
    exit(2);
}
if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(2);
}
$db = Connection::make();
$db->beginTransaction();
try {
    $q = $db->prepare(
        <<<'SQL'
        INSERT INTO users (display_name, email, username, account_status)
        VALUES
            (?, ?, ?, "active")
        SQL
        ,
    );
    $q->execute([$name, $email, $username]);
    $id = (int) $db->lastInsertId();
    $q = $db->prepare(
        <<<'SQL'
        INSERT INTO user_credentials (user_id, password_hash, password_algo)
        VALUES
            (?, ?, ?)
        SQL
        ,
    );
    $q->execute([$id, password_hash($password, PASSWORD_DEFAULT), PASSWORD_DEFAULT]);
    $q = $db->prepare(
        <<<'SQL'
        INSERT INTO user_roles (user_id, role_id)
        SELECT
            ?,
            id
        FROM roles
        WHERE
            role_key = "portal_admin"
        SQL
        ,
    );
    $q->execute([$id]);
    if ($q->rowCount() !== 1) {
        throw new RuntimeException('portal_admin role is missing');
    }
    $db->commit();
    fwrite(
        STDOUT,
        "First admin created. Remove INITIAL_ADMIN_PASSWORD from the operator environment now.\n",
    );
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "Creation failed; transaction rolled back.\n");
    exit(1);
}
