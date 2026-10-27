<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Config.php';
require dirname(__DIR__) . '/src/Database/Connection.php';
require dirname(__DIR__) . '/src/Migration/AccountMigrationPlanner.php';

use DndPortal\Config;
use DndPortal\Database\Connection;
use DndPortal\Migration\AccountMigrationPlanner;

$zipPath = $argv[1] ?? '';
if ($zipPath === '' || !is_readable($zipPath)) {
    fwrite(STDERR, "REFUSED: readable export ZIP required.\n");
    exit(2);
}
Config::loadDotEnv(dirname(__DIR__) . '/.env');
$dbName = Config::env('DB_NAME');
if (!preg_match('/^dnd_portal_migration_test_[0-9]+$/', $dbName)) {
    fwrite(STDERR, "REFUSED: dedicated migration test database required.\n");
    exit(2);
}

$archive = new ZipArchive();
if ($archive->open($zipPath) !== true || ($sql = $archive->getFromName('users.sql')) === false) {
    throw new RuntimeException('users.sql missing from export ZIP');
}
$archive->close();
if (!preg_match('/INSERT INTO `users`.*?VALUES\s*(.*?);/s', $sql, $match)) {
    throw new RuntimeException('users INSERT block missing');
}
preg_match_all(
    "/\((\\d+),\s*'((?:''|[^'])*)',\s*'((?:''|[^'])*)',\s*'((?:''|[^'])*)',\s*'((?:''|[^'])*)'/",
    $match[1],
    $matches,
    PREG_SET_ORDER,
);
$source = [];
foreach ($matches as $row) {
    $source[] = [
        'legacy_id' => (int) $row[1],
        'name' => str_replace("''", "'", $row[2]),
        'email' => strtolower(str_replace("''", "'", $row[3])),
        'hash' => str_replace("''", "'", $row[4]),
    ];
}
$source = array_values(
    array_filter(
        $source,
        static fn(array $row): bool => !in_array($row['legacy_id'], [1, 2, 5], true),
    ),
);
$plan = AccountMigrationPlanner::propose(
    array_map(
        static fn(array $row): array => [
            'legacy_id' => $row['legacy_id'],
            'name' => $row['name'],
            'email' => $row['email'],
        ],
        $source,
    ),
    1,
);
$byLegacy = array_column($source, null, 'legacy_id');
$db = Connection::make();
$conflicts = AccountMigrationPlanner::conflicts(
    $plan,
    array_map(
        'strtolower',
        $db
            ->query(
                'SELECT
    email
FROM
    users
WHERE
    email IS NOT NULL',
            )
            ->fetchAll(PDO::FETCH_COLUMN),
    ),
    array_map(
        'strtolower',
        $db
            ->query(
                'SELECT
    username
FROM
    users
WHERE
    username IS NOT NULL',
            )
            ->fetchAll(PDO::FETCH_COLUMN),
    ),
);

$reused = 0;
$inserted = 0;
$db->beginTransaction();
try {
    foreach ($plan as $item) {
        $row = $byLegacy[$item['legacy_id']];
        $q = $db->prepare('SELECT
    u.id,
    u.display_name,
    u.email,
    u.username,
    c.password_hash
FROM
    users u
JOIN user_credentials c ON
    c.user_id = u.id
WHERE
    u.id = ?');
        $q->execute([$item['proposed_new_id']]);
        $existing = $q->fetch();
        if ($existing) {
            if (
                $existing['display_name'] !== $row['name'] ||
                strtolower((string) $existing['email']) !== $row['email'] ||
                $existing['username'] !== $item['username'] ||
                $existing['password_hash'] !== $row['hash']
            ) {
                throw new RuntimeException('existing identifier conflict; no overwrite');
            }
            $reused++;
            continue;
        }
        $q = $db->prepare('SELECT
    id
FROM
    users
WHERE
    email = ? OR username = ?
LIMIT 1');
        $q->execute([$row['email'], $item['username']]);
        if ($q->fetchColumn() !== false) {
            throw new RuntimeException('email or username conflict; no overwrite');
        }
        $q = $db->prepare('INSERT INTO users(
    id,
    display_name,
    email,
    username,
    account_status,
    timezone
)
VALUES(?, ?, ?, ?, "active", "UTC")');
        $q->execute([$item['proposed_new_id'], $row['name'], $row['email'], $item['username']]);
        $q = $db->prepare('INSERT INTO user_credentials(
    user_id,
    password_hash,
    password_algo
)
VALUES(?, ?, ?)');
        $q->execute([$item['proposed_new_id'], $row['hash'], 'bcrypt']);
        $inserted++;
    }
    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, 'IMPORT_REFUSED reason=' . $e->getMessage() . PHP_EOL);
    exit(1);
}
$counts = [];
foreach (
    [
        'users',
        'user_credentials',
        'user_roles',
        'auth_sessions',
        'password_reset_tokens',
        'registration_invites',
    ]
    as $table
) {
    $counts[$table] = (int) $db
        ->query(
            'SELECT
    COUNT(*)
FROM
    `' .
                $table .
                '`',
        )
        ->fetchColumn();
}
echo 'IMPORT_OK excluded_legacy_ids=1,2,5 inserted=' .
    $inserted .
    ' reused=' .
    $reused .
    ' total_users=' .
    $counts['users'] .
    ' roles=' .
    $counts['user_roles'] .
    ' sessions=' .
    $counts['auth_sessions'] .
    ' reset_tokens=' .
    $counts['password_reset_tokens'] .
    ' invites=' .
    $counts['registration_invites'] .
    PHP_EOL;
