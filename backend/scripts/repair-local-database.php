<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Config.php';
require dirname(__DIR__) . '/src/Database/Connection.php';

\DndPortal\Config::loadDotEnv(dirname(__DIR__) . '/.env');
if (PHP_SAPI !== 'cli' || \DndPortal\Config::env('APP_ENV') !== 'local' || \DndPortal\Config::env('DB_NAME') !== 'dnd_portal_development') {
    fwrite(STDERR, "Refused: local APP_ENV and dnd_portal_development are required.\n");
    exit(2);
}

$apply = in_array('--apply', $argv, true);
$db = \DndPortal\Database\Connection::make();
$q = $db->query(
    'SELECT id, name, slug FROM campaigns WHERE name LIKE "UX test %" OR slug LIKE "ux-test-%" OR name = "Local Demo Campaign" OR slug = "local-demo-campaign" ORDER BY id',
);
$campaigns = $q->fetchAll();
echo $apply ? "APPLY preview for local database\n" : "PREVIEW only; pass --apply to modify\n";
foreach ($campaigns as $campaign) {
    $counts = [];
    foreach (['campaign_memberships', 'game_sessions', 'polls', 'payment_requests'] as $table) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM $table WHERE campaign_id = ?");
        $stmt->execute([(int) $campaign['id']]);
        $counts[$table] = (int) $stmt->fetchColumn();
    }
    echo sprintf("campaign id=%d name=%s slug=%s relations=%s\n", $campaign['id'], $campaign['name'], $campaign['slug'], json_encode($counts));
    if ($apply && $campaign['name'] === 'Local Demo Campaign' && array_sum($counts) > 0) {
        fwrite(STDERR, "Refused: Local Demo Campaign has relations; no automatic migration is safe.\n");
        exit(3);
    }
}
$pending = $db->query('SELECT COUNT(*) FROM users WHERE username LIKE "fixture-%" OR email LIKE "fixture-%@example.test"')->fetchColumn();
echo "fixture identifiers to normalize: $pending\n";
if (!$apply) {
    exit(0);
}
$db->beginTransaction();
try {
    $db->exec('UPDATE users SET email = NULL, username = NULL, account_status = "invited" WHERE username LIKE "fixture-%" OR email LIKE "fixture-%@example.test"');
    foreach ($campaigns as $campaign) {
        $counts = 0;
        foreach (['campaign_memberships', 'game_sessions', 'polls', 'payment_requests'] as $table) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM $table WHERE campaign_id = ?");
            $stmt->execute([(int) $campaign['id']]);
            $counts += (int) $stmt->fetchColumn();
        }
        if ($counts === 0) {
            $stmt = $db->prepare('DELETE FROM campaigns WHERE id = ?');
            $stmt->execute([(int) $campaign['id']]);
        }
    }
    $db->commit();
    echo "APPLIED local repair\n";
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, "Repair rolled back.\n");
    exit(1);
}
