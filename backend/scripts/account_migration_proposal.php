<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/Migration/AccountMigrationPlanner.php';

use DndPortal\Migration\AccountMigrationPlanner;

$zipPath = $argv[1] ?? '';
$outputPath = $argv[2] ?? '';
if ($zipPath === '' || $outputPath === '') {
    fwrite(STDERR, "Usage: php account_migration_proposal.php EXPORT.zip OUTPUT.json\n");
    exit(2);
}
$archive = new ZipArchive();
if ($archive->open($zipPath) !== true || ($sql = $archive->getFromName('users.sql')) === false) throw new RuntimeException('users.sql missing');
$archive->close();
if (!preg_match('/INSERT INTO `users`.*?VALUES\s*(.*?);/s', $sql, $match)) throw new RuntimeException('users INSERT block missing');
preg_match_all("/\((\\d+),\\s*'((?:''|[^'])*)',\\s*'((?:''|[^'])*)',\\s*'((?:''|[^'])*)',\\s*'((?:''|[^'])*)'/", $match[1], $matches, PREG_SET_ORDER);
$source = [];
foreach ($matches as $row) {
    $source[] = [
        'legacy_id' => (int)$row[1],
        'name' => str_replace("''", "'", $row[2]),
        'email' => strtolower(str_replace("''", "'", $row[3])),
        'hash' => str_replace("''", "'", $row[4]),
        'role' => str_replace("''", "'", $row[5]),
    ];
}
$plan = AccountMigrationPlanner::propose(array_map(static fn(array $row): array => [
    'legacy_id' => $row['legacy_id'], 'name' => $row['name'], 'email' => $row['email'],
], $source), 1);
$byId = [];
foreach ($source as $row) $byId[$row['legacy_id']] = $row;
foreach ($plan as &$item) {
    $sourceRow = $byId[$item['legacy_id']];
    $item['name'] = $sourceRow['name'];
    $item['password_hash_compatible'] = (password_get_info($sourceRow['hash'])['algoName'] ?? 'unknown') === 'bcrypt';
    $item['password_reset_required'] = !$item['password_hash_compatible'];
    $item['admin_mapping'] = null;
    $item['campaign_membership_mapping'] = null;
}
unset($item);
$proposal = [
    'source' => basename($zipPath),
    'target_database' => 'dnd_portal_test_run_20261027120001',
    'dry_run_only' => true,
    'records' => $plan,
    'notes' => [
        'proposed_new_id values are isolated dry-run IDs and are not production IDs',
        'no sessions, reset codes, invite tokens, admin roles or campaign memberships are proposed',
        'one incompatible password hash is marked for the existing reset procedure',
    ],
];
if (file_put_contents($outputPath, json_encode($proposal, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) === false) throw new RuntimeException('proposal could not be written');
echo 'PROPOSAL_WRITTEN records=' . count($plan) . ' reset_required=' . count(array_filter($plan, static fn(array $row): bool => $row['password_reset_required'])) . PHP_EOL;
