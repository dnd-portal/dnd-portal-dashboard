<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';

use DndPortal\Config;
use DndPortal\Database\Connection;

$zipPath = $argv[1] ?? '';
if ($zipPath === '' || !is_readable($zipPath)) {
    fwrite(STDERR, "REFUSED: readable export ZIP required.\n");
    exit(2);
}
Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
if (
    Config::env('APP_ENV') !== 'testing' ||
    !preg_match('/^dnd_portal_test_run_[0-9]+$/', Config::env('DB_NAME'))
) {
    fwrite(STDERR, "REFUSED: dedicated migration test database required.\n");
    exit(2);
}

$archive = new ZipArchive();
if ($archive->open($zipPath) !== true || ($sql = $archive->getFromName('users.sql')) === false) {
    fwrite(STDERR, "REFUSED: users.sql missing from export ZIP.\n");
    exit(2);
}
$archive->close();

if (!preg_match('/INSERT INTO `users`.*?VALUES\s*(.*?);/s', $sql, $match)) {
    fwrite(STDERR, "REFUSED: users INSERT block missing.\n");
    exit(2);
}
$rows = [];
preg_match_all(
    "/\((\\d+),\\s*'((?:''|[^'])*)',\\s*'((?:''|[^'])*)',\\s*'((?:''|[^'])*)',\\s*'((?:''|[^'])*)'/",
    $match[1],
    $matches,
    PREG_SET_ORDER,
);
foreach ($matches as $row) {
    $rows[] = [
        'email' => str_replace("''", "'", $row[3]),
        'hash' => str_replace("''", "'", $row[4]),
        'role' => str_replace("''", "'", $row[5]),
    ];
}
if (count($rows) === 0) {
    fwrite(STDERR, "REFUSED: no user records parsed.\n");
    exit(2);
}

$db = Connection::make();
$requiredTables = [
    'users',
    'user_credentials',
    'roles',
    'user_roles',
    'auth_sessions',
    'password_reset_tokens',
    'registration_invites',
];
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($requiredTables as $table) {
    if (!in_array($table, $tables, true)) {
        throw new RuntimeException('Migration schema incomplete: ' . $table);
    }
}

$emailConflicts = 0;
$hashCompatible = 0;
$hashIncompatible = 0;
$usernameMissing = 0;
$adminWithheld = 0;
$seenEmails = [];
foreach ($rows as $row) {
    $email = strtolower(trim($row['email']));
    if (isset($seenEmails[$email])) {
        $emailConflicts++;
    }
    $seenEmails[$email] = true;
    $q = $db->prepare(
        <<<'SQL'
        SELECT
        1
        FROM
        users
        WHERE
        email = ?
        LIMIT 1
        SQL
        ,
    );
    $q->execute([$email]);
    if ($q->fetchColumn()) {
        $emailConflicts++;
    }
    if ((password_get_info($row['hash'])['algoName'] ?? 'unknown') === 'bcrypt') {
        $hashCompatible++;
    } else {
        $hashIncompatible++;
    }
    $usernameMissing++;
    if ($row['role'] === 'admin') {
        $adminWithheld++;
    }
}

$emptyState = [];
foreach (
    [
        'users',
        'user_credentials',
        'auth_sessions',
        'password_reset_tokens',
        'registration_invites',
        'user_roles',
    ]
    as $table
) {
    $emptyState[$table] = (int) $db
        ->query(
            <<<'SQL'
            SELECT
            COUNT(*)
            FROM
            `
            SQL
            .
                $table .
                '`',
        )
        ->fetchColumn();
}
echo 'DRY_RUN records=' .
    count($rows) .
    ' email_conflicts=' .
    $emailConflicts .
    ' username_sources_missing=' .
    $usernameMissing .
    ' bcrypt_compatible=' .
    $hashCompatible .
    ' password_reset_required=' .
    $hashIncompatible .
    ' admin_roles_withheld=' .
    $adminWithheld .
    ' imported=0' .
    PHP_EOL;
echo 'DRY_RUN protected_tables_empty=' . (array_sum($emptyState) === 0 ? 'yes' : 'no') . PHP_EOL;
