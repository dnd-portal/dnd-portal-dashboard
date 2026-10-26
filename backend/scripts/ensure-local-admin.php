<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/Config.php';
\DndPortal\Config::loadDotEnv(dirname(__DIR__) . '/.env');
require dirname(__DIR__) . '/src/Database/Connection.php';
use DndPortal\Database\Connection;

if (PHP_SAPI !== 'cli' || \DndPortal\Config::env('APP_ENV') !== 'local') { fwrite(STDERR, "Refused: local CLI environment required.\n"); exit(2); }
$email = strtolower(trim((string)($argv[1] ?? '')));
$username = strtolower(trim((string)($argv[2] ?? '')));
$name = trim((string)($argv[3] ?? ''));
$password = getenv('INITIAL_ADMIN_PASSWORD') ?: '';
if ($email === '' || $username === '' || $name === '') { fwrite(STDERR, "Usage: php ensure-local-admin.php email username display-name\n"); exit(2); }
$db = Connection::make();
$q = $db->prepare('SELECT u.id,u.account_status,EXISTS(SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=u.id AND r.role_key="portal_admin") AS is_admin FROM users u WHERE u.email=? OR u.username=? LIMIT 2');
$q->execute([$email, $username]); $rows = $q->fetchAll();
if (count($rows) > 1) { fwrite(STDERR, "Refused: login identifiers are ambiguous.\n"); exit(1); }
if ($rows) {
    $row = $rows[0];
    if ($row['account_status'] !== 'active' || (int)$row['is_admin'] !== 1) { fwrite(STDERR, "Refused: existing identifier is not an active portal admin.\n"); exit(1); }
    echo "EXISTS $email\n"; exit(0);
}
if ($password === '') { fwrite(STDERR, "ADMIN_PASSWORD_REQUIRED\n"); exit(3); }
if (strlen($password) < 8) { fwrite(STDERR, "Password must be at least 8 characters.\n"); exit(2); }
$db->beginTransaction();
try {
    $q = $db->prepare('INSERT INTO users(display_name,email,username,account_status) VALUES(?,?,?,"active")'); $q->execute([$name,$email,$username]); $id=(int)$db->lastInsertId();
    $q=$db->prepare('INSERT INTO user_credentials(user_id,password_hash,password_algo) VALUES(?,?,?)'); $q->execute([$id,password_hash($password,PASSWORD_DEFAULT),PASSWORD_DEFAULT]);
    $q=$db->prepare('INSERT INTO user_roles(user_id,role_id) SELECT ?,id FROM roles WHERE role_key="portal_admin"'); $q->execute([$id]);
    if ($q->rowCount() !== 1) throw new RuntimeException('portal_admin role is missing');
    $db->commit(); echo "CREATED $email\n";
} catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); fwrite(STDERR,"Creation failed; transaction rolled back.\n"); exit(1); }
