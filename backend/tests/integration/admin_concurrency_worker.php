<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';
use DndPortal\Config;
use DndPortal\Database\Connection;

Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
$target = (int) $argv[1];
$barrier = $argv[2];
$mode = $argv[3] ?? 'disable';
$db = Connection::make();
$db->beginTransaction();
$db->query(
    <<<'SQL'
    SELECT
    GET_LOCK('dnd_portal_admin_invariant', 10)
    SQL
    ,
)->fetchColumn();
$q = $db->query(
    <<<'SQL'
    SELECT
    id
    FROM
    users
    WHERE
    account_status = 'active'
    ORDER BY
    id FOR UPDATE
    SQL
    ,
);
$admins = $q->fetchAll();
file_put_contents($barrier . '.' . $target, 'locked');
usleep(250000);
$q = $db->query(
    <<<'SQL'
    SELECT
    u.id
    FROM
    users u
    JOIN user_roles ur ON
    ur.user_id = u.id
    JOIN roles r ON
    r.id = ur.role_id
    WHERE
    r.role_key = 'portal_admin' AND u.account_status = 'active' FOR UPDATE
    SQL
    ,
);
if (count($q->fetchAll()) <= 1) {
    $db->rollBack();
    $db->query(
        <<<'SQL'
        SELECT
        RELEASE_LOCK('dnd_portal_admin_invariant')
        SQL
        ,
    );
    file_put_contents($barrier . '.result.' . $target, 'rejected');
    echo "rejected\n";
    exit(0);
}
$q =
    $mode === 'demote'
        ? $db->prepare(
            <<<'SQL'
            DELETE
            ur
            FROM
            user_roles ur
            JOIN roles r ON
            r.id = ur.role_id
            WHERE
            ur.user_id = ? AND r.role_key = "portal_admin"
            SQL
            ,
        )
        : $db->prepare(
            'UPDATE users SET account_status="disabled",version=version+1 WHERE id=? AND account_status="active"',
        );
$q->execute([$target]);
$db->commit();
$db->query(
    <<<'SQL'
    SELECT
    RELEASE_LOCK('dnd_portal_admin_invariant')
    SQL
    ,
);
file_put_contents($barrier . '.result.' . $target, 'success');
echo "success\n";
