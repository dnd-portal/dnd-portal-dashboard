<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';

use DndPortal\Config;
use DndPortal\Database\Connection;

Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
if (Config::env('APP_ENV') !== 'testing' || !preg_match('/^dnd_portal_test_run_[0-9]+$/', Config::env('DB_NAME'))) {
    throw new RuntimeException('Refusing backup test outside an isolated test database.');
}

$source = Connection::make();
$targetName = Config::env('DB_NAME') . '_restore';
if (!preg_match('/^[a-zA-Z0-9_]+$/', $targetName)) {
    throw new RuntimeException('Invalid restore database name.');
}
$root = new PDO(
    sprintf('mysql:host=%s;port=%s;charset=utf8mb4', Config::env('DB_HOST'), Config::env('DB_PORT', '3306')),
    'root',
    Config::env('TEST_DB_ROOT_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
);
$root->exec('DROP DATABASE IF EXISTS `' . $targetName . '`');
$root->exec('CREATE DATABASE `' . $targetName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$root->exec("GRANT ALL PRIVILEGES ON `$targetName`.* TO 'dnd_test'@'%'");
$root->exec('FLUSH PRIVILEGES');
$restore = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', Config::env('DB_HOST'), Config::env('DB_PORT', '3306'), $targetName),
    Config::env('DB_USER'),
    Config::env('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
);

$restore->exec('SET FOREIGN_KEY_CHECKS=0');
$tables = $source->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$sourceName = Config::env('DB_NAME');
foreach ($tables as $table) {
    $quoted = '`' . str_replace('`', '``', (string) $table) . '`';
    $qualifiedSource = '`' . $sourceName . '`.' . $quoted;
    $restore->exec("CREATE TABLE $quoted LIKE $qualifiedSource");
    $restore->exec("INSERT INTO $quoted SELECT * FROM $qualifiedSource");
}
$restore->exec('SET FOREIGN_KEY_CHECKS=1');

foreach ($tables as $table) {
    $sourceCount = (int) $source->query('SELECT
    COUNT(*)
FROM
    `' . $table . '`')->fetchColumn();
    $restoreCount = (int) $restore->query('SELECT
    COUNT(*)
FROM
    `' . $table . '`')->fetchColumn();
    if ($sourceCount !== $restoreCount) {
        throw new RuntimeException("Row count mismatch for $table.");
    }
}
$checks = [
    'schema_migrations' => 'SELECT
    COUNT(*)
FROM
    schema_migrations',
    'relations' => 'SELECT
    COUNT(*)
FROM
    campaign_memberships m
JOIN campaigns c ON
    c.id = m.campaign_id
JOIN users u ON
    u.id = m.user_id',
    'financial_sums' => "SELECT COALESCE(SUM(CASE WHEN entry_kind='payment' THEN amount_cents WHEN entry_kind='reversal' THEN -amount_cents ELSE 0 END),0) FROM payment_entries WHERE status='confirmed'",
];
foreach ($checks as $label => $sql) {
    $left = (string) $source->query($sql)->fetchColumn();
    $right = (string) $restore->query($sql)->fetchColumn();
    if ($left !== $right) {
        throw new RuntimeException("Restore check failed: $label ($left != $right).");
    }
    echo "PASS: $label = $left\n";
}
echo "PASS: backup restored to $targetName with matching rows, relations and financial sum\n";
