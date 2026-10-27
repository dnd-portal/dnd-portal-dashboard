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
$base = getenv('TEST_BACKEND_URL') ?: 'http://127.0.0.1:8099';
$cookie = '';
$csrf = '';
$request = function (string $method, string $path, ?array $body = null) use (
    &$cookie,
    &$csrf,
    $base,
): array {
    $headers = ['Accept: application/json', 'Origin: http://127.0.0.1:5173'];
    if ($cookie) {
        $headers[] = 'Cookie: dnd_session=' . $cookie;
    }
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'X-CSRF-Token: ' . $csrf;
    }
    $raw = file_get_contents(
        $base . $path,
        false,
        stream_context_create([
            'http' => [
                'method' => $method,
                'ignore_errors' => true,
                'header' => implode("\r\n", $headers),
                'content' => $body === null ? '' : json_encode($body),
            ],
        ]),
    );
    $status = (int) preg_replace(
        '/^HTTP\/[^ ]+ ([0-9]+).*$/',
        '$1',
        $http_response_header[0] ?? '0',
    );
    foreach ($http_response_header ?? [] as $header) {
        if (stripos($header, 'Set-Cookie: dnd_session=') === 0) {
            $cookie = explode('=', explode(';', trim(substr($header, 11)), 2)[0], 2)[1];
        }
    }
    return [$status, json_decode($raw ?: '{}', true) ?: []];
};
$ok = static function (bool $condition, string $name): void {
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $name);
    }
    echo "PASS: $name\n";
};
[$status, $body] = $request('GET', '/api/v1/auth/csrf');
$csrf = $body['data']['token'] ?? '';
[$status] = $request('POST', '/api/v1/auth/login', [
    'identifier' => getenv('TEST_ADMIN_EMAIL'),
    'password' => getenv('TEST_ADMIN_PASSWORD'),
]);
$ok($status === 200, 'dashboard validation admin login');
[$status, $body] = $request('GET', '/api/v1/auth/csrf');
$csrf = $body['data']['token'] ?? '';
[$status, $body] = $request('POST', '/api/v1/campaigns', [
    'name' => 'Dashboard exact totals',
    'slug' => 'dashboard-exact-' . bin2hex(random_bytes(3)),
    'type' => 'campaign',
]);
$campaign = (int) $body['data']['id'];
[$status, $body] = $request('GET', '/api/v1/auth/me');
$admin = (int) $body['data']['id'];
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
    left_at = NULL,
    membership_role = "dm"
    SQL
    ,
)->execute([$campaign, $admin]);
$db->prepare(
    <<<'SQL'
    INSERT INTO game_sessions(
    campaign_id,
    title,
    session_date,
    starts_at,
    created_by
    )
    VALUES(
    ?,
    ?,
    "2026-10-27",
    "2026-10-27 00:00:00",
    ?
    )
    SQL
    ,
)->execute([$campaign, 'Dashboard exact session', $admin]);
$session = (int) $db->lastInsertId();
for ($i = 1; $i <= 3; $i++) {
    $username = 'dashboard-player-' . bin2hex(random_bytes(3));
    $db->prepare(
        <<<'SQL'
        INSERT INTO users(
        display_name,
        email,
        username,
        account_status
        )
        VALUES(?, ?, ?, "active")
        SQL
        ,
    )->execute(["Dashboard Player $i", $username . '@example.test', $username]);
    $player = (int) $db->lastInsertId();
    $db->prepare(
        <<<'SQL'
        INSERT INTO campaign_memberships(
        campaign_id,
        user_id,
        membership_role
        )
        VALUES(?, ?, "member")
        SQL
        ,
    )->execute([$campaign, $player]);
    $db->prepare(
        <<<'SQL'
        INSERT INTO session_participants(
        session_id,
        user_id,
        rsvp_status
        )
        VALUES(?, ?, ?)
        SQL
        ,
    )->execute([$session, $player, $i < 3 ? 'accepted' : 'pending']);
}
[$status, $body] = $request('GET', '/api/v1/dashboard');
$next = $body['data']['next_session'] ?? [];
$ok(
    $status === 200 && (int) $next['participants'] === 3 && (int) $next['rsvp_accepted'] === 2,
    'dashboard exact session and RSVP totals',
);
[$status] = $request('POST', '/api/v1/auth/logout');
$cookie = '';
$empty = 'dashboard-empty-' . bin2hex(random_bytes(3));
$db->prepare(
    <<<'SQL'
    INSERT INTO users(
    display_name,
    email,
    username,
    account_status
    )
    VALUES("Dashboard Empty", ?, ?, "active")
    SQL
    ,
)->execute([$empty . '@example.test', $empty]);
$emptyId = (int) $db->lastInsertId();
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
)->execute([$emptyId, password_hash('EmptyDashboard2026!', PASSWORD_DEFAULT), PASSWORD_DEFAULT]);
[$status, $body] = $request('GET', '/api/v1/auth/csrf');
$csrf = $body['data']['token'] ?? '';
[$status] = $request('POST', '/api/v1/auth/login', [
    'identifier' => $empty,
    'password' => 'EmptyDashboard2026!',
]);
$ok($status === 200, 'empty dashboard login');
[$status, $body] = $request('GET', '/api/v1/dashboard');
$ok(
    $status === 200 &&
        $body['data']['next_session'] === null &&
        (int) $body['data']['open_polls'] === 0 &&
        (int) $body['data']['open_contributions_cents'] === 0,
    'empty dashboard state',
);
