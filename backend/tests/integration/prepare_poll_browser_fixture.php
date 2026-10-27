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
    fwrite(STDERR, "REFUSED: dedicated test-run database required.\n");
    exit(2);
}
$db = Connection::make();
// This is fixture setup in the isolated test database only; production rate limiting is untouched.
$db->exec(
    <<<'SQL'
    DELETE
    FROM
    rate_limit_buckets
    SQL
    ,
);
$adminUsername = 'polladmin';
$adminPassword = 'PollAdmin2026!';
$q = $db->prepare(
    <<<'SQL'
    SELECT
    id
    FROM
    users
    WHERE
    username = ?
    SQL
    ,
);
$q->execute([$adminUsername]);
$admin = $q->fetchColumn();
if ($admin === false) {
    $db->prepare(
        <<<'SQL'
        INSERT INTO users(
        display_name,
        email,
        username,
        account_status
        )
        VALUES(
        "Poll Admin",
        "poll-admin@example.test",
        ?,
        "active"
        )
        SQL
        ,
    )->execute([$adminUsername]);
    $admin = (int) $db->lastInsertId();
    $db->prepare(
        <<<'SQL'
        INSERT INTO user_credentials(
        user_id,
        password_hash,
        password_algo
        )
        VALUES(?, ?, ?)
        SQL
        ,
    )->execute([$admin, password_hash($adminPassword, PASSWORD_DEFAULT), PASSWORD_DEFAULT]);
    $db->prepare(
        <<<'SQL'
        INSERT INTO user_roles(user_id, role_id)
        SELECT
        ?,
        id
        FROM
        roles
        WHERE
        role_key = "portal_admin"
        SQL
        ,
    )->execute([$admin]);
} else {
    $admin = (int) $admin;
    $db->prepare(
        <<<'SQL'
        UPDATE
        users
        SET
        account_status = "active"
        WHERE
        id = ?
        SQL
        ,
    )->execute([$admin]);
    $db->prepare(
        <<<'SQL'
        UPDATE
        user_credentials
        SET
        password_hash = ?,
        password_algo = ?
        WHERE
        user_id = ?
        SQL
        ,
    )->execute([password_hash($adminPassword, PASSWORD_DEFAULT), PASSWORD_DEFAULT, $admin]);
}
$db->prepare(
    <<<'SQL'
    INSERT IGNORE
    INTO user_roles(user_id, role_id)
    SELECT
    ?,
    id
    FROM
    roles
    WHERE
    role_key = ?
    SQL
    ,
)->execute([$admin, 'portal_admin']);
$campaign = (int) $db
    ->query(
        <<<'SQL'
        SELECT
        id
        FROM
        campaigns
        ORDER BY
        id
        DESC
        LIMIT 1
        SQL
        ,
    )
    ->fetchColumn();
if (!$campaign) {
    throw new RuntimeException('Poll browser fixture requires a campaign.');
}
$db->prepare(
    <<<'SQL'
    INSERT INTO campaign_memberships(
    campaign_id,
    user_id,
    membership_role
    )
    VALUES(?, ?, "dm")
    ON DUPLICATE KEY
    UPDATE
    membership_role = "dm",
    left_at = NULL
    SQL
    ,
)->execute([$campaign, $admin]);
$q = $db->prepare(
    <<<'SQL'
    SELECT
    id
    FROM
    users
    WHERE
    username = "pollplayer"
    SQL
    ,
);
$q->execute();
$player = $q->fetchColumn();
if ($player === false) {
    $db->prepare(
        <<<'SQL'
        INSERT INTO users(
        display_name,
        email,
        username,
        account_status
        )
        VALUES(
        "Poll Player",
        "poll-player@example.test",
        "pollplayer",
        "active"
        )
        SQL
        ,
    )->execute();
    $player = (int) $db->lastInsertId();
    $db->prepare(
        <<<'SQL'
        INSERT INTO user_credentials(
        user_id,
        password_hash,
        password_algo
        )
        VALUES(?, ?, ?)
        SQL
        ,
    )->execute([$player, password_hash('PollPlayer2026!', PASSWORD_DEFAULT), PASSWORD_DEFAULT]);
    $db->prepare(
        <<<'SQL'
        INSERT INTO user_roles(user_id, role_id)
        SELECT
        ?,
        id
        FROM
        roles
        WHERE
        role_key = "campaign_member"
        SQL
        ,
    )->execute([$player]);
} else {
    $player = (int) $player;
    $db->prepare(
        <<<'SQL'
        UPDATE
        users
        SET
        account_status = "active"
        WHERE
        id = ?
        SQL
        ,
    )->execute([$player]);
    $db->prepare(
        <<<'SQL'
        UPDATE
        user_credentials
        SET
        password_hash = ?,
        password_algo = ?
        WHERE
        user_id = ?
        SQL
        ,
    )->execute([password_hash('PollPlayer2026!', PASSWORD_DEFAULT), PASSWORD_DEFAULT, $player]);
}
$db->prepare(
    <<<'SQL'
    INSERT INTO campaign_memberships(
    campaign_id,
    user_id,
    membership_role
    )
    VALUES(?, ?, "member")
    ON DUPLICATE KEY
    UPDATE
    membership_role = "member",
    left_at = NULL
    SQL
    ,
)->execute([$campaign, $player]);
echo "POLL_BROWSER_CAMPAIGN=$campaign\n";
