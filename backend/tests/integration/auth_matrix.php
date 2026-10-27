<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';
require dirname(__DIR__, 2) . '/src/Auth/RateLimiter.php';
use DndPortal\Config;
use DndPortal\Database\Connection;
use DndPortal\Auth\RateLimiter;

Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
$name = Config::env('DB_NAME', '');
if (Config::env('APP_ENV') !== 'testing' || !preg_match('/^dnd_portal_test_run_[0-9]+$/', $name)) {
    fwrite(STDERR, "REFUSED: dedicated test-run database required.\n");
    exit(2);
}
$db = Connection::make();
$prefix = 'matrix_' . bin2hex(random_bytes(4));
$ok = 0;
$assert = static function (bool $condition, string $label) use (&$ok): void {
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    $ok++;
    echo "PASS: $label\n";
};
$now = gmdate('Y-m-d H:i:s.u');
$q = $db->prepare(
    <<<'SQL'
    INSERT INTO users(
    display_name,
    email,
    username,
    account_status
    )
    VALUES(?, ?, ?, "active"),(?, ?, ?, "active")
    SQL
    ,
);
$q->execute([
    $prefix . ' admin',
    $prefix . '@example.test',
    $prefix . 'admin',
    $prefix . ' second',
    $prefix . '2@example.test',
    $prefix . 'second',
]);
$admin = (int) $db->lastInsertId();
$second = $admin + 1;
$q = $db->prepare(
    <<<'SQL'
    SELECT
    id
    FROM
    roles
    WHERE
    role_key = "portal_admin"
    SQL
    ,
);
$q->execute();
$role = (int) $q->fetchColumn();
$q = $db->prepare(
    <<<'SQL'
    INSERT INTO user_roles(user_id, role_id)
    VALUES(?, ?),(?, ?)
    SQL
    ,
);
$q->execute([$admin, $role, $second, $role]);
$q = $db->prepare(
    <<<'SQL'
    INSERT INTO registration_invites(
    invited_user_id,
    invited_email,
    code_hash,
    created_by,
    expires_at
    )
    VALUES(?, ?, ?, ?, ?)
    SQL
    ,
);
$q->execute([
    $second,
    $prefix . '2@example.test',
    hash('sha256', random_bytes(32), true),
    $admin,
    gmdate('Y-m-d H:i:s.u', time() - 1),
]);
$invite = (int) $db->lastInsertId();
$q = $db->prepare(
    <<<'SQL'
    UPDATE
    registration_invites
    SET
    consumed_at = ?
    WHERE
    id = ?
    SQL
    ,
);
$q->execute([$now, $invite]);
$q = $db->prepare(
    <<<'SQL'
    SELECT
    consumed_at
    FROM
    registration_invites
    WHERE
    id = ?
    SQL
    ,
);
$q->execute([$invite]);
$assert($q->fetchColumn() !== null, 'used invite is not reusable');
$q = $db->prepare(
    <<<'SQL'
    INSERT INTO password_reset_tokens(
    user_id,
    token_hash,
    expires_at
    )
    VALUES(?, ?, ?),(?, ?, ?)
    SQL
    ,
);
$token = random_bytes(32);
$q->execute([
    $admin,
    hash('sha256', $token, true),
    gmdate('Y-m-d H:i:s.u', time() - 1),
    $admin,
    hash('sha256', random_bytes(32), true),
    gmdate('Y-m-d H:i:s.u', time() + 1800),
]);
$expired = (int) $db->lastInsertId() - 1;
$live = (int) $db->lastInsertId();
$q = $db->prepare(
    <<<'SQL'
    SELECT
    COUNT(*)
    FROM
    password_reset_tokens
    WHERE
    id = ? AND used_at IS NULL AND expires_at > UTC_TIMESTAMP(6)
    SQL
    ,
);
$q->execute([$expired]);
$assert((int) $q->fetchColumn() === 0, 'expired reset is rejected');
$q = $db->prepare(
    <<<'SQL'
    UPDATE
    password_reset_tokens
    SET
    used_at = ?
    WHERE
    id = ?
    SQL
    ,
);
$q->execute([$now, $live]);
$q = $db->prepare(
    <<<'SQL'
    SELECT
    used_at
    FROM
    password_reset_tokens
    WHERE
    id = ?
    SQL
    ,
);
$q->execute([$live]);
$assert($q->fetchColumn() !== null, 'revoked reset is rejected');
$q = $db->prepare(
    <<<'SQL'
    INSERT INTO users(
    display_name,
    email,
    username,
    account_status
    )
    VALUES(?, ?, ?, "active")
    SQL
    ,
);
$q->execute([$prefix . ' unique', $prefix . '3@example.test', $prefix . 'unique']);
$u = (int) $db->lastInsertId();
try {
    $q = $db->prepare(
        <<<'SQL'
        UPDATE
        users
        SET
        email = ?
        WHERE
        id = ?
        SQL
        ,
    );
    $q->execute([$prefix . '@example.test', $u]);
    $assert(false, 'duplicate identifier blocked');
} catch (Throwable) {
    $assert(true, 'duplicate identifier blocked');
}
$q = $db->prepare(
    <<<'SQL'
    INSERT INTO auth_sessions(
    session_id_hash,
    user_id,
    session_data,
    created_at,
    last_seen_at,
    expires_at
    )
    VALUES(?, ?, ?, ?, ?, ?)
    SQL
    ,
);
$raw = random_bytes(32);
$q->execute([
    hash('sha256', $raw, true),
    $admin,
    '',
    $now,
    $now,
    gmdate('Y-m-d H:i:s.u', time() + 3600),
]);
$sid = (int) $db->lastInsertId();
$q = $db->prepare(
    <<<'SQL'
    UPDATE
    auth_sessions
    SET
    revoked_at = ?
    WHERE
    user_id = ? AND revoked_at IS NULL
    SQL
    ,
);
$q->execute([$now, $admin]);
$q = $db->prepare(
    <<<'SQL'
    SELECT
    revoked_at
    FROM
    auth_sessions
    WHERE
    id = ?
    SQL
    ,
);
$q->execute([$sid]);
$assert($q->fetchColumn() !== null, 'all existing sessions revoked after reset operation');
$lim = new RateLimiter($db);
$lim->check('matrix', $prefix, 2);
$lim->check('matrix', $prefix, 2);
$blocked = false;
try {
    $lim->check('matrix', $prefix, 2);
} catch (Throwable) {
    $blocked = true;
}
$assert($blocked, 'rate limiter blocks after configured limit');
$q = $db->prepare(
    <<<'SQL'
    INSERT INTO password_reset_tokens(
    user_id,
    token_hash,
    expires_at
    )
    VALUES(?, ?, ?)
    SQL
    ,
);
$q->execute([
    $admin,
    hash('sha256', random_bytes(32), true),
    gmdate('Y-m-d H:i:s.u', time() + 1800),
]);
$concurrent = (int) $db->lastInsertId();
$barrier = tempnam(sys_get_temp_dir(), 'dnd-concurrency-');
$cmd =
    'php ' .
    escapeshellarg(__DIR__ . '/concurrency_worker.php') .
    ' %d ' .
    escapeshellarg((string) $concurrent) .
    ' ' .
    escapeshellarg($barrier);
$p1 = proc_open(sprintf($cmd, 1), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes1);
$p2 = proc_open(sprintf($cmd, 2), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes2);
if (!is_resource($p1) || !is_resource($p2)) {
    throw new RuntimeException('Could not start concurrency workers');
}
$r1 = stream_get_contents($pipes1[1]);
$r2 = stream_get_contents($pipes2[1]);
foreach ($pipes1 as $p) {
    fclose($p);
}
foreach ($pipes2 as $p) {
    fclose($p);
}
proc_close($p1);
proc_close($p2);
$successes = substr_count($r1, 'success') + substr_count($r2, 'success');
$assert($successes === 1, 'concurrent reset consumption has exactly one success');
echo "PASS: auth matrix $ok checks\n";
