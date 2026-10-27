<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';
use DndPortal\Config;
use DndPortal\Database\Connection;

Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
$name = Config::env('DB_NAME', '');
if (Config::env('APP_ENV') !== 'testing' || !preg_match('/^dnd_portal_test_run_[0-9]+$/', $name)) {
    fwrite(STDERR, "REFUSED: dedicated test-run database required.\n");
    exit(2);
}
$db = Connection::make();
$p = 'adminrace_' . bin2hex(random_bytes(3));
$q = $db->query(
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
$role = (int) $q->fetchColumn();
$db->prepare(
    <<<'SQL'
    DELETE
    FROM
    user_roles
    WHERE
    role_id = ?
    SQL
    ,
)->execute([$role]);
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
$q->execute([$p . 'a', $p . 'a@example.test', $p . 'a', $p . 'b', $p . 'b@example.test', $p . 'b']);
$a = (int) $db->lastInsertId();
$b = $a + 1;
$q = $db->prepare(
    <<<'SQL'
    INSERT INTO user_roles(user_id, role_id)
    VALUES(?, ?),(?, ?)
    SQL
    ,
);
$q->execute([$a, $role, $b, $role]);
$barrier = tempnam(sys_get_temp_dir(), 'dnd-admin-race-');
$cmd =
    'php ' .
    escapeshellarg(__DIR__ . '/admin_concurrency_worker.php') .
    ' %d ' .
    escapeshellarg($barrier);
$p1 = proc_open(sprintf($cmd, $a), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $x);
$p2 = proc_open(sprintf($cmd, $b), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $y);
$o1 = stream_get_contents($x[1]);
$e1 = stream_get_contents($x[2]);
$o2 = stream_get_contents($y[1]);
$e2 = stream_get_contents($y[2]);
foreach ($x as $v) {
    fclose($v);
}
foreach ($y as $v) {
    fclose($v);
}
proc_close($p1);
proc_close($p2);
$success = substr_count($o1, 'success') + substr_count($o2, 'success');
if ($success !== 1) {
    throw new RuntimeException(
        'Expected exactly one concurrent admin mutation to succeed. outputs=[' .
            $o1 .
            '|' .
            $o2 .
            '] errors=[' .
            $e1 .
            '|' .
            $e2 .
            ']',
    );
}
$q = $db->query(
    <<<'SQL'
    SELECT
    COUNT(*)
    FROM
    users u
    JOIN user_roles ur ON
    ur.user_id = u.id
    JOIN roles r ON
    r.id = ur.role_id
    WHERE
    r.role_key = 'portal_admin' AND u.account_status = 'active'
    SQL
    ,
);
$remaining = (int) $q->fetchColumn();
if ($remaining < 1) {
    throw new RuntimeException('Invariant failed: no active admin remains.');
}
$runnerEmail = getenv('TEST_ADMIN_EMAIL');
if ($runnerEmail) {
    $q = $db->prepare(
        <<<'SQL'
        SELECT
        id
        FROM
        users
        WHERE
        email = ?
        SQL
        ,
    );
    $q->execute([$runnerEmail]);
    $runner = (int) $q->fetchColumn();
    if ($runner) {
        $q = $db->prepare(
            <<<'SQL'
            INSERT IGNORE
            INTO user_roles(user_id, role_id)
            VALUES(?, ?)
            SQL
            ,
        );
        $q->execute([$runner, $role]);
    }
}
echo "PASS: concurrent admin mutation has one success and $remaining active admin\n";
