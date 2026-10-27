<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';
use DndPortal\Config;
use DndPortal\Database\Connection;

Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
$database = Config::env('DB_NAME', '');
if (
    Config::env('APP_ENV') !== 'testing' ||
    !preg_match('/^dnd_portal_test_run_[0-9]+$/', $database)
) {
    fwrite(STDERR, "REFUSED: dedicated test-run database required.\n");
    exit(2);
}
$db = Connection::make();
$role = (int) $db
    ->query(
        <<<'SQL'
        SELECT
            id
        FROM roles
        WHERE
            role_key = 'portal_admin'
        SQL
        ,
    )
    ->fetchColumn();
if ($role < 1) {
    throw new RuntimeException('portal_admin role is missing');
}
$worker = __DIR__ . '/admin_concurrency_worker.php';
$run = static function (string $label, string $left, string $right) use (
    $db,
    $role,
    $worker,
): void {
    $prefix = 'matrix_' . bin2hex(random_bytes(4));
    $q = $db->prepare(
        <<<'SQL'
        INSERT INTO users (display_name, email, username, account_status)
        VALUES
            (?, ?, ?, "active"),
            (?, ?, ?, "active")
        SQL
        ,
    );
    $q->execute([
        $prefix . ' a',
        $prefix . 'a@example.test',
        $prefix . 'a',
        $prefix . ' b',
        $prefix . 'b@example.test',
        $prefix . 'b',
    ]);
    $first = (int) $db->lastInsertId();
    $second = $first + 1;
    $db->prepare(
        <<<'SQL'
        INSERT INTO user_roles (user_id, role_id)
        VALUES
            (?, ?),
            (?, ?)
        SQL
        ,
    )->execute([$first, $role, $second, $role]);
    $barrier = tempnam(sys_get_temp_dir(), 'dnd-admin-matrix-');
    if ($barrier === false) {
        throw new RuntimeException('Could not create barrier');
    }
    $command = 'php ' . escapeshellarg($worker) . ' %d ' . escapeshellarg($barrier) . ' %s';
    $one = proc_open(
        sprintf($command, $first, $left),
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipesOne,
    );
    $two = proc_open(
        sprintf($command, $second, $right),
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipesTwo,
    );
    if (!is_resource($one) || !is_resource($two)) {
        throw new RuntimeException('Could not start workers');
    }
    $outOne = stream_get_contents($pipesOne[1]);
    $outTwo = stream_get_contents($pipesTwo[1]);
    foreach ($pipesOne as $pipe) {
        fclose($pipe);
    }
    foreach ($pipesTwo as $pipe) {
        fclose($pipe);
    }
    proc_close($one);
    proc_close($two);
    $remaining = (int) $db
        ->query(
            <<<'SQL'
            SELECT
                COUNT(*)
            FROM users u
            JOIN user_roles ur ON ur.user_id = u.id
            JOIN roles r ON r.id = ur.role_id
            WHERE
                r.role_key = 'portal_admin'
                AND u.account_status = 'active'
            SQL
            ,
        )
        ->fetchColumn();
    if ($remaining < 1) {
        throw new RuntimeException("FAIL $label: invariant broken");
    }
    echo "PASS: $label active_admins=$remaining responses=$outOne|$outTwo\n";
    $db->prepare(
        <<<'SQL'
        DELETE FROM user_roles
        WHERE
            user_id IN (?, ?)
        SQL
        ,
    )->execute([$first, $second]);
    $db->prepare(
        <<<'SQL'
        DELETE FROM users
        WHERE
            id IN (?, ?)
        SQL
        ,
    )->execute([$first, $second]);
    @unlink($barrier);
};
$run('two simultaneous demotions', 'demote', 'demote');
$run('two simultaneous disables', 'disable', 'disable');
$run('simultaneous demotion and disable', 'demote', 'disable');
echo "PASS: isolated admin concurrency matrix left runner fixtures untouched\n";
