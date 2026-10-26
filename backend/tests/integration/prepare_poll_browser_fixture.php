<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';
use DndPortal\Config;
use DndPortal\Database\Connection;
Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
if (Config::env('APP_ENV') !== 'testing' || !preg_match('/^dnd_portal_test_run_[0-9]+$/', (string) Config::env('DB_NAME'))) { fwrite(STDERR, "REFUSED: dedicated test-run database required.\n"); exit(2); }
$db = Connection::make();
// This is fixture setup in the isolated test database only; production rate limiting is untouched.
$db->exec('DELETE FROM rate_limit_buckets');
$adminUsername = 'polladmin';
$adminPassword = 'PollAdmin2026!';
$q = $db->prepare('SELECT id FROM users WHERE username=?');
$q->execute([$adminUsername]);
$admin = $q->fetchColumn();
if ($admin === false) {
    $db->prepare('INSERT INTO users(display_name,email,username,account_status) VALUES("Poll Admin","poll-admin@example.test",?,"active")')->execute([$adminUsername]);
    $admin = (int) $db->lastInsertId();
    $db->prepare('INSERT INTO user_credentials(user_id,password_hash,password_algo) VALUES(?,?,?)')->execute([$admin, password_hash($adminPassword, PASSWORD_DEFAULT), PASSWORD_DEFAULT]);
    $db->prepare('INSERT INTO user_roles(user_id,role_id) SELECT ?,id FROM roles WHERE role_key="portal_admin"')->execute([$admin]);
} else {
    $admin = (int) $admin;
    $db->prepare('UPDATE users SET account_status="active" WHERE id=?')->execute([$admin]);
    $db->prepare('UPDATE user_credentials SET password_hash=?,password_algo=? WHERE user_id=?')->execute([password_hash($adminPassword, PASSWORD_DEFAULT), PASSWORD_DEFAULT, $admin]);
}
$db->prepare('INSERT IGNORE INTO user_roles(user_id,role_id) SELECT ?,id FROM roles WHERE role_key=?')->execute([$admin, 'portal_admin']);
$campaign = (int) $db->query('SELECT id FROM campaigns ORDER BY id DESC LIMIT 1')->fetchColumn();
if (!$campaign) throw new RuntimeException('Poll browser fixture requires a campaign.');
$db->prepare('INSERT INTO campaign_memberships(campaign_id,user_id,membership_role) VALUES(?, ?, "dm") ON DUPLICATE KEY UPDATE membership_role="dm",left_at=NULL')->execute([$campaign, $admin]);
$q = $db->prepare('SELECT id FROM users WHERE username="pollplayer"');
$q->execute();
$player = $q->fetchColumn();
if ($player === false) {
    $db->prepare('INSERT INTO users(display_name,email,username,account_status) VALUES("Poll Player","poll-player@example.test","pollplayer","active")')->execute();
    $player = (int) $db->lastInsertId();
    $db->prepare('INSERT INTO user_credentials(user_id,password_hash,password_algo) VALUES(?,?,?)')->execute([$player, password_hash('PollPlayer2026!', PASSWORD_DEFAULT), PASSWORD_DEFAULT]);
    $db->prepare('INSERT INTO user_roles(user_id,role_id) SELECT ?,id FROM roles WHERE role_key="campaign_member"')->execute([$player]);
} else {
    $player = (int) $player;
    $db->prepare('UPDATE users SET account_status="active" WHERE id=?')->execute([$player]);
    $db->prepare('UPDATE user_credentials SET password_hash=?,password_algo=? WHERE user_id=?')->execute([password_hash('PollPlayer2026!', PASSWORD_DEFAULT), PASSWORD_DEFAULT, $player]);
}
$db->prepare('INSERT INTO campaign_memberships(campaign_id,user_id,membership_role) VALUES(?, ?,"member") ON DUPLICATE KEY UPDATE membership_role="member",left_at=NULL')->execute([$campaign, $player]);
echo "POLL_BROWSER_CAMPAIGN=$campaign\n";
