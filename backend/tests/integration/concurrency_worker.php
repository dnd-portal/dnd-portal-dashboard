<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';
use DndPortal\Config;
use DndPortal\Database\Connection;

Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
$id = (int) ($argv[1] ?? 0);
$token = (int) ($argv[2] ?? 0);
$barrier = $argv[3] ?? '';
$db = Connection::make();
$db->beginTransaction();
$q = $db->prepare(
    <<<'SQL'
    SELECT
    id
    FROM
    password_reset_tokens
    WHERE
    id = ? AND used_at IS NULL FOR UPDATE
    SQL
    ,
);
$q->execute([$token]);
file_put_contents($barrier . '.' . $id, 'locked');
usleep(250000);
$q = $db->prepare(
    <<<'SQL'
    UPDATE password_reset_tokens
    SET
        used_at = UTC_TIMESTAMP(6)
    WHERE
        id = ?
        AND used_at IS NULL
    SQL
    ,
);
$q->execute([$token]);
$result = $q->rowCount() === 1 ? 'success' : 'rejected';
$db->commit();
file_put_contents($barrier . '.result.' . $id, $result);
echo $result . PHP_EOL;
