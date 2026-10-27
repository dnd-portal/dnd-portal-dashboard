<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Config.php';
require dirname(__DIR__) . '/src/Database/Connection.php';

\DndPortal\Config::loadDotEnv(dirname(__DIR__) . '/.env');
if (PHP_SAPI !== 'cli' || \DndPortal\Config::env('APP_ENV') !== 'local' || \DndPortal\Config::env('DB_NAME') !== 'dnd_portal_development') {
    fwrite(STDERR, "Refused: explicit local environment and dnd_portal_development are required.\n");
    exit(2);
}

$db = \DndPortal\Database\Connection::make();
$db->beginTransaction();
try {
    $role = static function (\PDO $db, string $key): int {
        $q = $db->prepare('SELECT id FROM roles WHERE role_key = ?');
        $q->execute([$key]);
        $id = $q->fetchColumn();
        if ($id === false) {
            throw new RuntimeException("Missing role: $key. Run migrations first.");
        }
        return (int) $id;
    };
    $user = static function (\PDO $db, string $username, string $email, string $name, string $password): int {
        $q = $db->prepare('SELECT id, email FROM users WHERE username = ? OR email = ?');
        $q->execute([$username, $email]);
        $rows = $q->fetchAll();
        if (count($rows) > 1 || ($rows && ($rows[0]['email'] ?? '') !== $email)) {
            throw new RuntimeException("Identifier conflict for local demo account: $username");
        }
        if ($rows) {
            return (int) $rows[0]['id'];
        }
        $db->prepare('INSERT INTO users (display_name, email, username, account_status) VALUES (?, ?, ?, "active")')->execute([$name, $email, $username]);
        $id = (int) $db->lastInsertId();
        $db->prepare('INSERT INTO user_credentials (user_id, password_hash, password_algo) VALUES (?, ?, ?)')->execute([$id, password_hash($password, PASSWORD_DEFAULT), PASSWORD_DEFAULT]);
        return $id;
    };
    $roleAdmin = $role($db, 'portal_admin');
    $roleDm = $role($db, 'campaign_dm');
    $roleMember = $role($db, 'campaign_member');
    $admin = $user($db, 'localdemo-admin', 'localdemo-admin@example.test', 'Local Demo Admin', 'LocalDemoAdmin2026!');
    $dm = $user($db, 'localdemo-dm', 'localdemo-dm@example.test', 'Local Demo DM', 'LocalDemoDm2026!');
    $player = $user($db, 'localdemo-player', 'localdemo-player@example.test', 'Local Demo Player', 'LocalDemoPlayer2026!');
    $empty = $user($db, 'localdemo-empty', 'localdemo-empty@example.test', 'Local Empty Player', 'LocalDemoEmpty2026!');
    foreach ([[$admin, $roleAdmin], [$dm, $roleDm], [$player, $roleMember], [$empty, $roleMember]] as [$uid, $rid]) {
        $db->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$uid, $rid]);
    }
    $q = $db->prepare('SELECT id FROM campaigns WHERE slug = ?');
    $q->execute(['local-demo-campaign']);
    $campaign = $q->fetchColumn();
    if ($campaign === false) {
        $db->prepare('INSERT INTO campaigns (name, slug, type, location, timezone, created_by) VALUES (?, ?, "campaign", ?, "Europe/Amsterdam", ?)')->execute(['Local Demo Campaign', 'local-demo-campaign', 'The Old Keep', $admin]);
        $campaign = (int) $db->lastInsertId();
    } else {
        $campaign = (int) $campaign;
    }
    foreach ([[$admin, 'dm'], [$dm, 'dm'], [$player, 'member']] as [$uid, $membership]) {
        $db->prepare('INSERT IGNORE INTO campaign_memberships (campaign_id, user_id, membership_role) VALUES (?, ?, ?)')->execute([$campaign, $uid, $membership]);
    }
    $q = $db->prepare('SELECT id FROM game_sessions WHERE campaign_id = ? AND title = ?');
    $q->execute([$campaign, 'Local Demo Session']);
    $session = $q->fetchColumn();
    if ($session === false) {
        $db->prepare('INSERT INTO game_sessions (campaign_id, title, session_date, starts_at, ends_at, rsvp_deadline_at, location, created_by) VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL 7 DAY), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 7 DAY), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 7 DAY), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 6 DAY), ?, ?)')->execute([$campaign, 'Local Demo Session', 'The Old Keep', $dm]);
        $session = (int) $db->lastInsertId();
    } else {
        $session = (int) $session;
    }
    foreach ([$dm => 'accepted', $player => 'pending'] as $uid => $status) {
        $db->prepare('INSERT IGNORE INTO session_participants (session_id, user_id, rsvp_status) VALUES (?, ?, ?)')->execute([$session, $uid, $status]);
    }
    $db->prepare('INSERT IGNORE INTO user_availability (user_id, availability_date, status, note) VALUES (?, DATE_ADD(CURDATE(), INTERVAL 7 DAY), "available", "Local demo availability")')->execute([$player]);
    $q = $db->prepare('SELECT id FROM polls WHERE campaign_id = ? AND title = ?');
    $q->execute([$campaign, 'Local Demo Poll']);
    $poll = $q->fetchColumn();
    if ($poll === false) {
        $db->prepare('INSERT INTO polls (campaign_id, title, choice_type, closes_at, created_by) VALUES (?, ?, "single", DATE_ADD(UTC_TIMESTAMP(), INTERVAL 3 DAY), ?)')->execute([$campaign, 'Local Demo Poll', $dm]);
        $poll = (int) $db->lastInsertId();
        $db->prepare('INSERT INTO poll_options (poll_id, label, position) VALUES (?, "Friday", 1), (?, "Saturday", 2)')->execute([$poll, $poll]);
        $db->prepare('INSERT INTO poll_participants (poll_id, user_id) VALUES (?, ?)')->execute([$poll, $player]);
    } else {
        $poll = (int) $poll;
    }
    $q = $db->prepare('SELECT id FROM payment_requests WHERE campaign_id = ? AND title = ?');
    $q->execute([$campaign, 'Local Demo Snacks']);
    $request = $q->fetchColumn();
    if ($request === false) {
        $db->prepare('INSERT INTO payment_requests (campaign_id, created_by, title, description, amount_cents, currency, external_url, due_date) VALUES (?, ?, ?, ?, 1250, "EUR", "https://payments.example.test/local-demo", DATE_ADD(CURDATE(), INTERVAL 14 DAY))')->execute([$campaign, $dm, 'Local Demo Snacks', 'Synthetic local-only obligation']);
        $request = (int) $db->lastInsertId();
        $db->prepare('INSERT INTO payment_obligations (payment_request_id, user_id, amount_cents, currency) VALUES (?, ?, 1250, "EUR")')->execute([$request, $player]);
    }
    $db->commit();
    echo "SEEDED local demo database: dnd_portal_development\n";
    echo "Accounts (local only): localdemo-admin / LocalDemoAdmin2026!, localdemo-dm / LocalDemoDm2026!, localdemo-player / LocalDemoPlayer2026!, localdemo-empty / LocalDemoEmpty2026!\n";
} catch (Throwable $error) {
    $db->rollBack();
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
