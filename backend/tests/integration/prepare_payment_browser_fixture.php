<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';

use DndPortal\Config;
use DndPortal\Database\Connection;

Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
if (Config::env('APP_ENV') !== 'testing' || !preg_match('/^dnd_portal_test_run_[0-9]+$/', (string) Config::env('DB_NAME'))) exit("REFUSED\n");
$d = Connection::make();
// The production limiter keys login attempts by type and remote address.
// Clear only the isolated test database before this browser group.
$d->exec('DELETE FROM rate_limit_buckets');
$q = $d->prepare('SELECT id FROM users WHERE username="paybrowser"');
$q->execute();
$id = $q->fetchColumn();
if ($id === false) {
    $d->prepare('INSERT INTO users(display_name,email,username,account_status) VALUES("Payment Browser","pay-browser@example.test","paybrowser","active")')->execute();
    $id = (int) $d->lastInsertId();
    $d->prepare('INSERT INTO user_credentials(user_id,password_hash,password_algo) VALUES(?,?,?)')->execute([$id, password_hash('PayBrowser2026!', PASSWORD_DEFAULT), PASSWORD_DEFAULT]);
    $d->prepare('INSERT INTO user_roles(user_id,role_id) SELECT ?,id FROM roles WHERE role_key="campaign_member"')->execute([$id]);
} else {
    $id = (int) $id;
    $d->prepare('UPDATE users SET account_status="active" WHERE id=?')->execute([$id]);
    $d->prepare('UPDATE user_credentials SET password_hash=? WHERE user_id=?')->execute([password_hash('PayBrowser2026!', PASSWORD_DEFAULT), $id]);
}
$campaign = (int) $d->query('SELECT id FROM campaigns ORDER BY id DESC LIMIT 1')->fetchColumn();
$d->prepare('INSERT INTO campaign_memberships(campaign_id,user_id,membership_role) VALUES(?,? ,"member") ON DUPLICATE KEY UPDATE left_at=NULL')->execute([$campaign, $id]);
echo "PAYMENT_BROWSER_USER=paybrowser\n";
