<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';
use DndPortal\Config;
use DndPortal\Database\Connection;

Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
if (!preg_match('/^dnd_portal_migration_test_[0-9]+$/', Config::env('DB_NAME'))) {
    exit("FAIL: wrong database\n");
}
$ok = static function (bool $v, string $s): void {
    if (!$v) {
        throw new RuntimeException('FAIL: '.$s);
    }
    echo 'PASS: '.$s."\n";
};
$zipPath = $argv[1] ?? '';
if (!is_readable($zipPath)) {
    throw new RuntimeException('source ZIP required');
}
$archive = new ZipArchive();
if ($archive->open($zipPath) !== true || ($sql = $archive->getFromName('users.sql')) === false) {
    throw new RuntimeException('source ZIP invalid');
}
$archive->close();
if (!preg_match('/INSERT INTO `users`.*?VALUES\s*(.*?);/s', $sql, $match)) {
    throw new RuntimeException('source SQL invalid');
}
preg_match_all("/\((\\d+),\s*'((?:''|[^'])*)',\s*'((?:''|[^'])*)',\s*'((?:''|[^'])*)'/", $match[1], $matches, PREG_SET_ORDER);
$sourceHashes = [];
foreach ($matches as $row) {
    if (!in_array((int)$row[1], [1, 2, 5], true)) {
        $sourceHashes[] = str_replace("''", "'", $row[4]);
    }
}
$db = Connection::make();
$ok((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 4, 'exactly four accounts imported');
$ok((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 4, 'source exclusion leaves four target accounts');
$ok((int)$db->query('SELECT COUNT(*) FROM user_roles')->fetchColumn() === 0, 'no roles imported');
$ok((int)$db->query('SELECT COUNT(*) FROM auth_sessions')->fetchColumn() === 0, 'no sessions imported');
$ok((int)$db->query('SELECT COUNT(*) FROM password_reset_tokens')->fetchColumn() === 0, 'no reset tokens imported');
$ok((int)$db->query('SELECT COUNT(*) FROM registration_invites')->fetchColumn() === 0, 'no invite tokens imported');
$stored = $db->query('SELECT password_hash FROM user_credentials ORDER BY user_id')->fetchAll(PDO::FETCH_COLUMN);
$ok(count($stored) === 4 && count(array_filter($stored, static fn ($hash, $i): bool => hash_equals($sourceHashes[$i], $hash), ARRAY_FILTER_USE_BOTH)) === 4, 'four source hashes retained byte-for-byte');
echo "PASS: migration import invariants\n";
