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

    // Mirror the existing local frontend fixture records in the real local database.
    // These records are synthetic and deliberately have no credentials or elevated roles.
    $fixtureUsers = [
        1 => 'Vince', 2 => 'Roel', 3 => 'Stan', 4 => 'Tijs', 5 => 'Liam', 6 => 'Thomas',
        7 => 'Stijn', 8 => 'Casper', 9 => 'Eline', 10 => 'Lais', 11 => 'Ben', 12 => 'Lucas',
        14 => 'Andy', 15 => 'Bryce', 16 => 'Chanel'
    ];
    $fixtureIds = [];
    foreach ($fixtureUsers as $legacyId => $displayName) {
        $username = 'fixture-' . strtolower($displayName) . '-' . $legacyId;
        $email = $username . '@example.test';
        $db->prepare(
            'INSERT IGNORE INTO users (display_name, email, username, account_status, timezone) VALUES (?, ?, ?, "invited", "Europe/Amsterdam")'
        )->execute([$displayName, $email, $username]);
        $q = $db->prepare('SELECT id FROM users WHERE username = ?');
        $q->execute([$username]);
        $fixtureIds[$legacyId] = (int) $q->fetchColumn();
    }

    $fixtureCampaigns = [
        ['Party 1', 'campaign-1', 'In person'],
        ['LuckyClover', 'campaign-2', 'In person'],
        ['FcGoonUnited', 'campaign-3', 'In person'],
        ['Party 4', 'campaign-4', 'In person'],
        ['BootySlayer', 'campaign-5', 'Online'],
        ['Teeras', 'campaign-6', 'Online']
    ];
    $fixtureCampaignIds = [];
    foreach ($fixtureCampaigns as [$name, $slug, $location]) {
        $db->prepare(
            'INSERT IGNORE INTO campaigns (name, slug, type, location, timezone, created_by) VALUES (?, ?, "campaign", ?, "Europe/Amsterdam", ?)'
        )->execute([$name, $slug, $location, $admin]);
        $q = $db->prepare('SELECT id FROM campaigns WHERE slug = ?');
        $q->execute([$slug]);
        $fixtureCampaignIds[$slug] = (int) $q->fetchColumn();
    }

    $membershipMap = [
        'campaign-1' => [14 => 'dm', 2 => 'member', 3 => 'member', 15 => 'member', 4 => 'member'],
        'campaign-2' => [7 => 'dm', 1 => 'member', 11 => 'member', 4 => 'member', 5 => 'member'],
        'campaign-3' => [15 => 'dm', 3 => 'member', 2 => 'member'],
        'campaign-4' => [2 => 'dm', 3 => 'member']
    ];
    foreach ($membershipMap as $slug => $members) {
        foreach ($members as $legacyId => $membershipRole) {
            $db->prepare(
                'INSERT IGNORE INTO campaign_memberships (campaign_id, user_id, membership_role) VALUES (?, ?, ?)'
            )->execute([$fixtureCampaignIds[$slug], $fixtureIds[$legacyId], $membershipRole]);
        }
    }

    $sessionFixtures = [
        ['Party 1 - Previous', 'campaign-1', -14, 'completed'],
        ['Party 1 - Next', 'campaign-1', 7, 'planned'],
        ['Party 1 - Later', 'campaign-1', 21, 'planned'],
        ['Party 2 - Previous', 'campaign-2', -7, 'completed']
    ];
    foreach ($sessionFixtures as [$title, $slug, $days, $status]) {
        $q = $db->prepare('SELECT id FROM game_sessions WHERE campaign_id = ? AND title = ?');
        $q->execute([$fixtureCampaignIds[$slug], $title]);
        if ($q->fetchColumn() === false) {
            $sessionDate = (new DateTimeImmutable("$days days"))->format('Y-m-d');
            $startsAt = (new DateTimeImmutable("$days days 20:00:00"))->format('Y-m-d H:i:s');
            $endsAt = (new DateTimeImmutable("$days days 23:00:00"))->format('Y-m-d H:i:s');
            $db->prepare(
                'INSERT INTO game_sessions (campaign_id, title, session_date, starts_at, ends_at, location, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $fixtureCampaignIds[$slug], $title, $sessionDate, $startsAt, $endsAt, 'Local fixture location', $status, $admin
            ]);
        }
    }

    $q = $db->prepare('SELECT id FROM game_sessions WHERE campaign_id = ? AND title = ?');
    $q->execute([$fixtureCampaignIds['campaign-1'], 'Party 1 - Next']);
    $nextFixtureSession = (int) $q->fetchColumn();
    foreach ([14 => 'accepted', 2 => 'accepted', 3 => 'declined', 15 => 'pending'] as $legacyId => $rsvp) {
        $db->prepare(
            'INSERT INTO session_participants (session_id, user_id, rsvp_status, attendance_status, responded_at) VALUES (?, ?, ?, ?, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE rsvp_status = VALUES(rsvp_status), attendance_status = VALUES(attendance_status)'
        )->execute([$nextFixtureSession, $fixtureIds[$legacyId], $rsvp, $rsvp === 'accepted' ? 'unknown' : 'absent']);
    }

    $pollDefinitions = [
        ['campaign-1', 'Welke datum kan je?', 'open', 'single'],
        ['campaign-2', 'Afgesloten fixture poll', 'closed', 'multiple']
    ];
    foreach ($pollDefinitions as [$slug, $title, $status, $choiceType]) {
        $q = $db->prepare('SELECT id FROM polls WHERE campaign_id = ? AND title = ?');
        $q->execute([$fixtureCampaignIds[$slug], $title]);
        if ($q->fetchColumn() === false) {
            $closesAt = (new DateTimeImmutable(($status === 'open' ? '+3 days' : '-3 days')))->format('Y-m-d H:i:s');
            $db->prepare(
                'INSERT INTO polls (campaign_id, title, status, choice_type, closes_at, created_by) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$fixtureCampaignIds[$slug], $title, $status, $choiceType, $closesAt, $dm]);
        }
    }
    $q = $db->prepare('SELECT id FROM polls WHERE title = ?');
    $q->execute(['Welke datum kan je?']);
    $openPoll = (int) $q->fetchColumn();
    foreach (['16 september 2026', '22 september 2026', '30 september 2026', '7 oktober 2026', '14 oktober 2026'] as $position => $label) {
        $db->prepare('INSERT IGNORE INTO poll_options (poll_id, label, position) VALUES (?, ?, ?)')->execute([$openPoll, $label, $position + 1]);
    }
    foreach ([1, 2, 3, 4] as $legacyId) {
        $db->prepare('INSERT IGNORE INTO poll_participants (poll_id, user_id, response_state) VALUES (?, ?, "pending")')->execute([$openPoll, $fixtureIds[$legacyId]]);
    }

    $db->prepare(
        'INSERT IGNORE INTO user_availability (user_id, availability_date, status, note) VALUES (?, DATE_ADD(CURDATE(), INTERVAL 7 DAY), "unavailable", "Existing fixture availability")'
    )->execute([$fixtureIds[4]]);
    $inviteHash = hash('sha256', 'fixture-invite-1', true);
    $db->prepare(
        'INSERT IGNORE INTO registration_invites (invited_user_id, invited_email, code_hash, created_by, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY))'
    )->execute([$fixtureIds[1], 'fixture-vince-1@example.test', $inviteHash, $admin]);
    $db->prepare(
        'INSERT INTO audit_events (actor_user_id, event_type, entity_type, entity_id, payload_json) SELECT ?, "local_fixture_seeded", "campaign", ?, JSON_OBJECT("source", "frontend-fixture") WHERE NOT EXISTS (SELECT 1 FROM audit_events WHERE event_type = "local_fixture_seeded" AND entity_type = "campaign" AND entity_id = ?)'
    )->execute([$admin, $fixtureCampaignIds['campaign-1'], $fixtureCampaignIds['campaign-1']]);

    $db->commit();
    echo "SEEDED local demo database: dnd_portal_development\n";
    echo "Accounts (local only): localdemo-admin / LocalDemoAdmin2026!, localdemo-dm / LocalDemoDm2026!, localdemo-player / LocalDemoPlayer2026!, localdemo-empty / LocalDemoEmpty2026!\n";
} catch (Throwable $error) {
    $db->rollBack();
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
