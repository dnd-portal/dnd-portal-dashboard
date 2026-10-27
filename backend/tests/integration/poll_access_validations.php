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
$db = Connection::make();
$db->exec(
    <<<'SQL'
    DELETE FROM rate_limit_buckets
    SQL
    ,
);
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
$ok = function (bool $v, string $name): void {
    if (!$v) {
        throw new RuntimeException('FAIL: ' . $name);
    }
    echo "PASS: $name\n";
};
[$s, $b] = $request('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s, $b] = $request('POST', '/api/v1/auth/login', [
    'identifier' => getenv('TEST_ADMIN_EMAIL'),
    'password' => getenv('TEST_ADMIN_PASSWORD'),
]);
$ok($s === 200, 'poll access admin login');
[$s, $b] = $request('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s, $b] = $request('POST', '/api/v1/campaigns', [
    'name' => 'Poll access',
    'slug' => 'poll-access-' . bin2hex(random_bytes(3)),
    'type' => 'campaign',
]);
$campaign = (int) ($b['data']['id'] ?? 0);
[$s, $me] = $request('GET', '/api/v1/auth/me');
$admin = (int) $me['data']['id'];
$username = 'poll-access-' . bin2hex(random_bytes(3));
$email = $username . '@example.test';
$db->prepare(
    <<<'SQL'
    INSERT INTO users (display_name, email, username, account_status)
    VALUES
        (?, ?, ?, "active")
    SQL
    ,
)->execute(['Poll Access Player', $email, $username]);
$player = (int) $db->lastInsertId();
$db->prepare(
    <<<'SQL'
    INSERT INTO user_credentials (user_id, password_hash, password_algo)
    VALUES
        (?, ?, ?)
    SQL
    ,
)->execute([$player, password_hash('PollAccess2026!', PASSWORD_DEFAULT), PASSWORD_DEFAULT]);
$db->prepare(
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
)->execute([$player]);
$db->prepare(
    <<<'SQL'
    INSERT INTO campaign_memberships (campaign_id, user_id, membership_role)
    VALUES
        (?, ?, "member")
    SQL
    ,
)->execute([$campaign, $player]);
[$s, $b] = $request('POST', "/api/v1/campaigns/$campaign/polls", [
    'title' => 'Access',
    'choice_type' => 'single',
    'options' => ['A', 'B'],
]);
$poll = (int) $b['data']['id'];
$option = (int) $b['data']['options'][0]['id'];
[$s, $b] = $request('POST', "/api/v1/polls/$poll/participants/$admin", ['active' => true]);
[$s, $b] = $request('POST', '/api/v1/auth/logout');
$cookie = '';
[$s, $b] = $request('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s, $b] = $request('POST', '/api/v1/auth/login', [
    'identifier' => $username,
    'password' => 'PollAccess2026!',
]);
$ok($s === 200, 'poll access player login');
[$s, $b] = $request('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s, $b] = $request('PUT', "/api/v1/polls/$poll/vote", ['option_ids' => [$option]]);
$ok($s === 403, 'nonparticipant rejected status=' . $s . ' body=' . json_encode($b));
[$s, $b] = $request('POST', "/api/v1/campaigns/$campaign/polls", [
    'title' => 'Denied',
    'choice_type' => 'single',
    'options' => ['A', 'B'],
]);
$ok($s === 403, 'unprivileged manager rejected');
echo "PASS: poll access scenarios\n";
