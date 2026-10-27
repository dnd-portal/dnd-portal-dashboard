<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';

use DndPortal\Config;
use DndPortal\Database\Connection;

Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
if (
    Config::env('APP_ENV') !== 'testing' ||
    !preg_match('/^dnd_portal_test_run_[0-9]+$/', (string) Config::env('DB_NAME'))
) {
    exit("REFUSED\n");
}
$d = Connection::make();
// The production limiter keys login attempts by type and remote address.
// Clear only the isolated test database before this browser group.
$d->exec(
    <<<'SQL'
    DELETE FROM rate_limit_buckets
    SQL
    ,
);
$q = $d->prepare(
    <<<'SQL'
    SELECT
        id
    FROM users
    WHERE
        username = "paybrowser"
    SQL
    ,
);
$q->execute();
$id = $q->fetchColumn();
if ($id === false) {
    $d->prepare(
        <<<'SQL'
        INSERT INTO users (display_name, email, username, account_status)
        VALUES
            (
                "Payment Browser",
                "pay-browser@example.test",
                "paybrowser",
                "active"
            )
        SQL
        ,
    )->execute();
    $id = (int) $d->lastInsertId();
    $d->prepare(
        <<<'SQL'
        INSERT INTO user_credentials (user_id, password_hash, password_algo)
        VALUES
            (?, ?, ?)
        SQL
        ,
    )->execute([$id, password_hash('PayBrowser2026!', PASSWORD_DEFAULT), PASSWORD_DEFAULT]);
    $d->prepare(
        <<<'SQL'
        INSERT INTO user_roles (user_id, role_id)
        SELECT
            ?,
            id
        FROM roles
        WHERE
            role_key = "campaign_member"
        SQL
        ,
    )->execute([$id]);
} else {
    $id = (int) $id;
    $d->prepare(
        <<<'SQL'
        UPDATE users
        SET
            account_status = "active"
        WHERE
            id = ?
        SQL
        ,
    )->execute([$id]);
    $d->prepare(
        <<<'SQL'
        UPDATE user_credentials
        SET
            password_hash = ?
        WHERE
            user_id = ?
        SQL
        ,
    )->execute([password_hash('PayBrowser2026!', PASSWORD_DEFAULT), $id]);
}
$campaign = (int) $d
    ->query(
        <<<'SQL'
        SELECT
            id
        FROM campaigns
        ORDER BY
            id DESC
        LIMIT
            1
        SQL
        ,
    )
    ->fetchColumn();
$d->prepare(
    <<<'SQL'
    INSERT INTO campaign_memberships (campaign_id, user_id, membership_role)
    VALUES
        (?, ?, "member")
    ON DUPLICATE KEY UPDATE
        left_at = NULL
    SQL
    ,
)->execute([$campaign, $id]);
echo "PAYMENT_BROWSER_USER=paybrowser\n";
