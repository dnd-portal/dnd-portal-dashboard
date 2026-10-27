<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';
use DndPortal\Config;
use DndPortal\Database\Connection;

Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
if (
    Config::env('APP_ENV') !== 'testing' ||
    !preg_match('/^dnd_portal_test_run_[0-9]+$/', Config::env('DB_NAME'))
) {
    exit("REFUSED\n");
}
$db = Connection::make();
$db->exec('DELETE
FROM
    rate_limit_buckets');
$base = getenv('TEST_BACKEND_URL') ?: 'http://127.0.0.1:8099';
$cookie = '';
$csrf = '';
$req = function (string $m, string $p, ?array $body = null) use (&$cookie, &$csrf, $base): array {
    $h = ['Accept: application/json', 'Origin: http://127.0.0.1:5173'];
    if ($cookie) {
        $h[] = 'Cookie: dnd_session=' . $cookie;
    }
    if ($body !== null) {
        $h[] = 'Content-Type: application/json';
    }
    if (!in_array($m, ['GET', 'HEAD', 'OPTIONS'], true)) {
        $h[] = 'X-CSRF-Token: ' . $csrf;
    }
    $raw = file_get_contents(
        $base . $p,
        false,
        stream_context_create([
            'http' => [
                'method' => $m,
                'ignore_errors' => true,
                'header' => implode("\r\n", $h),
                'content' => $body === null ? '' : json_encode($body),
            ],
        ]),
    );
    $s = (int) preg_replace('/^HTTP\/[^ ]+ ([0-9]+).*$/', '$1', $http_response_header[0] ?? '0');
    foreach ($http_response_header ?? [] as $v) {
        if (stripos($v, 'Set-Cookie: dnd_session=') === 0) {
            $cookie = explode('=', explode(';', trim(substr($v, 11)), 2)[0], 2)[1];
        }
    }
    foreach ($http_response_header ?? [] as $v) {
        if (preg_match('/^Set-Cookie:\\s*dnd_session=([^;]*)/i', $v, $m)) {
            $cookie = $m[1];
        }
    }
    return [$s, json_decode($raw ?: '{}', true) ?: []];
};
$ok = static function (bool $v, string $n): void {
    if (!$v) {
        throw new RuntimeException('FAIL: ' . $n);
    }
    echo "PASS: $n\n";
};
[$s, $b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s] = $req('POST', '/api/v1/auth/login', [
    'identifier' => getenv('TEST_ADMIN_EMAIL'),
    'password' => getenv('TEST_ADMIN_PASSWORD'),
]);
$ok($s === 200, 'policy admin login');
[$s, $b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s, $b] = $req('POST', '/api/v1/campaigns', [
    'name' => 'Approved policies',
    'slug' => 'approved-policies-' . bin2hex(random_bytes(3)),
    'type' => 'campaign',
]);
$campaign = (int) $b['data']['id'];
[$s, $me] = $req('GET', '/api/v1/auth/me');
$admin = (int) $me['data']['id'];
[$s] = $req('POST', "/api/v1/campaigns/$campaign/sessions", [
    'title' => 'Nonexistent local time',
    'session_date' => '2026-03-29',
    'starts_at' => '2026-03-29 02:30:00',
]);
$ok($s === 422, 'nonexistent Europe/Amsterdam time rejected');
[$s] = $req('POST', "/api/v1/campaigns/$campaign/sessions", [
    'title' => 'Ambiguous local time',
    'session_date' => '2026-10-25',
    'starts_at' => '2026-10-25 02:30:00',
]);
$ok($s === 422, 'ambiguous Europe/Amsterdam time requires choice');
[$s, $b] = $req('POST', "/api/v1/campaigns/$campaign/sessions", [
    'title' => 'Chosen ambiguous time',
    'session_date' => '2026-10-25',
    'starts_at' => '2026-10-25 02:30:00',
    'timezone_offset' => '+02:00',
]);
$ok(
    $s === 201 && str_starts_with((string) $b['data']['starts_at'], '2026-10-25 00:30:00'),
    'explicit ambiguous offset stored as UTC',
);
$username = 'policy-player-' . bin2hex(random_bytes(3));
$db->prepare(
    'INSERT INTO users(
    display_name,
    email,
    username,
    account_status
)
VALUES("Policy Player", ?, ?, "active")',
)->execute([$username . '@example.test', $username]);
$player = (int) $db->lastInsertId();
$db->prepare(
    'INSERT INTO user_credentials(
    user_id,
    password_hash,
    password_algo
)
VALUES(?, ?, ?)',
)->execute([$player, password_hash('PolicyPlayer2026!', PASSWORD_DEFAULT), PASSWORD_DEFAULT]);
$db->prepare(
    'INSERT INTO campaign_memberships(
    campaign_id,
    user_id,
    membership_role
)
VALUES(?, ?, "member")',
)->execute([$campaign, $player]);
$db->prepare(
    'INSERT INTO game_sessions(
    campaign_id,
    title,
    session_date,
    rsvp_deadline_at,
    created_by
)
VALUES(
    ?,
    ?,
    "2026-12-01",
    "2026-01-01 00:00:00",
    ?
)',
)->execute([$campaign, 'Past RSVP deadline', $admin]);
$session = (int) $db->lastInsertId();
$db->prepare(
    'INSERT INTO session_participants(session_id, user_id)
VALUES(?, ?)',
)->execute([$session, $player]);
[$s] = $req('POST', '/api/v1/auth/logout');
$cookie = '';
[$s, $b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s] = $req('POST', '/api/v1/auth/login', [
    'identifier' => $username,
    'password' => 'PolicyPlayer2026!',
]);
$ok($s === 200, 'policy player login');
$playerCookie = $cookie;
[$s, $b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s] = $req('PATCH', "/api/v1/sessions/$session/participants/$player", [
    'rsvp_status' => 'accepted',
]);
$ok($s === 409, 'player RSVP after deadline rejected');
[$s] = $req('POST', '/api/v1/auth/logout');
$cookie = '';
[$s, $b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s] = $req('POST', '/api/v1/auth/login', [
    'identifier' => getenv('TEST_ADMIN_EMAIL'),
    'password' => getenv('TEST_ADMIN_PASSWORD'),
]);
$ok($s === 200, 'policy manager relogin');
[$s, $b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s] = $req('PATCH', "/api/v1/sessions/$session/participants/$player", [
    'rsvp_status' => 'accepted',
    'attendance_status' => 'present',
]);
$ok($s === 200, 'manager correction and attendance');
$q = $db->prepare("SELECT
    COUNT(*)
FROM
    audit_events
WHERE
    actor_user_id = ? AND event_type IN(
        'session_rsvp_corrected',
        'session_attendance_corrected'
    )");
$q->execute([$admin]);
$ok((int) $q->fetchColumn() >= 2, 'manager corrections audit logged');
[$s, $b] = $req('POST', "/api/v1/campaigns/$campaign/polls", [
    'title' => 'No matching option',
    'choice_type' => 'single',
    'options' => ['A', 'B'],
]);
$poll = (int) $b['data']['id'];
[$s, $b] = $req('POST', "/api/v1/polls/$poll/participants/$player", ['active' => true]);
$ok($s === 200, 'poll participant assigned');
[$s] = $req('POST', '/api/v1/auth/logout');
$cookie = '';
[$s, $b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s] = $req('POST', '/api/v1/auth/login', [
    'identifier' => $username,
    'password' => 'PolicyPlayer2026!',
]);
$ok($s === 200, 'poll player login');
[$s, $b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s, $b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s, $b] = $req('POST', "/api/v1/polls/$poll/respond-without-option");
$ok(
    $s === 200 && ($b['data']['response_state'] ?? '') === 'responded_without_option',
    'responded without option accepted status=' . $s . ' body=' . json_encode($b),
);
$q = $db->prepare('SELECT
    response_state
FROM
    poll_participants
WHERE
    poll_id = ? AND user_id = ?');
$q->execute([$poll, $player]);
$ok(
    $q->fetchColumn() === 'responded_without_option',
    'response state persisted without option vote',
);
$q = $db->prepare('SELECT
    COUNT(*)
FROM
    poll_votes
WHERE
    poll_id = ? AND user_id = ?');
$q->execute([$poll, $player]);
$ok((int) $q->fetchColumn() === 0, 'no option vote created');
