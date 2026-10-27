<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';
require dirname(__DIR__, 2) . '/src/Auth/SessionStore.php';
use DndPortal\Config;
use DndPortal\Database\Connection;
use DndPortal\Auth\SessionStore;

Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
if (
    Config::env('APP_ENV') !== 'testing' ||
    !preg_match(
        '/^(dnd_portal_test|dnd_portal_test_run_[0-9]+|test_[a-z0-9_]+)$/',
        Config::env('DB_NAME'),
    )
) {
    fwrite(STDERR, "REFUSED: testing database required.\n");
    exit(2);
}
$base = getenv('TEST_BACKEND_URL') ?: 'http://127.0.0.1:8099';
$email = getenv('TEST_ADMIN_EMAIL') ?: 'admin@example.test';
$password = getenv('TEST_ADMIN_PASSWORD') ?: '';
if ($password === '') {
    fwrite(
        STDERR,
        "REFUSED: TEST_ADMIN_PASSWORD must be supplied through the process environment.\n",
    );
    exit(2);
}
$cookie = '';
$request = static function (
    string $method,
    string $path,
    ?array $body = null,
    string $origin = 'http://127.0.0.1:5173',
) use (&$cookie, $base): array {
    $headers = ['Accept: application/json', 'Origin: ' . $origin];
    if ($cookie !== '') {
        $headers[] = 'Cookie: dnd_session=' . $cookie;
    }
    if ($method !== 'GET' && !empty($GLOBALS['__csrf_token'])) {
        $headers[] = 'X-CSRF-Token: ' . $GLOBALS['__csrf_token'];
    }
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
    }
    $opts = [
        'http' => [
            'method' => $method,
            'ignore_errors' => true,
            'header' => implode("\r\n", $headers),
            'content' => $body === null ? '' : json_encode($body, JSON_THROW_ON_ERROR),
        ],
    ];
    $raw = file_get_contents($base . $path, false, stream_context_create($opts));
    $status = (int) preg_replace(
        '/^HTTP\/[^ ]+ ([0-9]+).*$/',
        '$1',
        $http_response_header[0] ?? '0',
    );
    foreach ($http_response_header ?? [] as $header) {
        if (stripos($header, 'Set-Cookie: dnd_session=') === 0) {
            $cookie =
                explode(';', trim(substr($header, 11)))[0] === ''
                    ? $cookie
                    : explode('=', trim(substr($header, 11)), 2)[1];
        }
    }
    return [$status, json_decode($raw ?: '{}', true) ?: []];
};
$assert = static function (bool $ok, string $name): void {
    if (!$ok) {
        throw new RuntimeException('FAIL: ' . $name);
    }
    echo 'PASS: ' . $name . "\n";
};
[$status, $privateUsers] = $request('GET', '/api/v1/users');
$assert($status === 401 && isset($privateUsers['error']), 'unauthenticated user list rejected');
[$status, $privateUser] = $request('GET', '/api/v1/users/1');
$assert($status === 401 && isset($privateUser['error']), 'unauthenticated user detail rejected');
[$status, $privateDashboard] = $request('GET', '/api/v1/dashboard');
$assert($status === 401 && isset($privateDashboard['error']), 'unauthenticated dashboard rejected');
[$status, $csrf] = $request('GET', '/api/v1/auth/csrf');
$assert($status === 200 && isset($csrf['data']['token']), 'csrf token issued');
$token = $csrf['data']['token'];
$requestWithCsrf = static function (
    string $method,
    string $path,
    array $body = [],
    string $origin = 'http://127.0.0.1:5173',
) use (&$request, &$token): array {
    $GLOBALS['__csrf_header'] = $token;
    return $request($method, $path, $body, $origin);
};
[$status, $bad] = $request(
    'POST',
    '/api/v1/auth/login',
    ['identifier' => $email, 'password' => $password],
    'https://wrong.example',
);
$assert(
    $status === 419 && ($bad['error']['code'] ?? '') === 'csrf_failed',
    'wrong Origin rejected',
);
[$status, $login] = $request('POST', '/api/v1/auth/login', [
    'identifier' => $email,
    'password' => $password,
]);
$assert($status === 419, 'missing CSRF rejected');
[$status, $csrf] = $request('GET', '/api/v1/auth/csrf');
$token = $csrf['data']['token'];
$headersCsrf = static function () use (&$request, &$token, &$cookie): void {}; // documents the required token boundary
$opts = [
    'http' => [
        'method' => 'POST',
        'ignore_errors' => true,
        'header' => "Accept: application/json\r\nOrigin: http://127.0.0.1:5173\r\nCookie: dnd_session=$cookie\r\nContent-Type: application/json\r\nX-CSRF-Token: $token",
        'content' => json_encode(
            ['identifier' => $email, 'password' => $password],
            JSON_THROW_ON_ERROR,
        ),
    ],
];
$loginRaw = file_get_contents($base . '/api/v1/auth/login', false, stream_context_create($opts));
$status = (int) preg_replace('/^HTTP\/[^ ]+ ([0-9]+).*$/', '$1', $http_response_header[0] ?? '0');
foreach ($http_response_header ?? [] as $header) {
    if (stripos($header, 'Set-Cookie: dnd_session=') === 0) {
        $cookie = explode('=', explode(';', trim(substr($header, 11)), 2)[0], 2)[1];
    }
}
$login = json_decode($loginRaw ?: '{}', true) ?: [];
$assert(
    $status === 200 && isset($login['data']['roles']),
    'valid login status=' . $status . ' code=' . ($login['error']['code'] ?? 'none'),
);
[$status, $me] = $request('GET', '/api/v1/auth/me');
$assert(
    $status === 200 && !isset($me['data']['password_hash']),
    'current user allowlist has no hash',
);
[$csrfStatus, $csrfAfterLogin] = $request('GET', '/api/v1/auth/csrf');
$token = $csrfAfterLogin['data']['token'] ?? '';
$GLOBALS['__csrf_token'] = $token;
[$status, $selfDemote] = $request('PATCH', '/api/v1/users/' . (int) $me['data']['id'], [
    'display_name' => $me['data']['display_name'],
    'email' => $me['data']['email'],
    'username' => $me['data']['username'],
    'roles' => ['campaign_member'],
    'version' => (int) $me['data']['version'],
]);
$assert(
    $status === 409 && ($selfDemote['error']['code'] ?? '') === 'last_admin',
    'self-demotion preserves the last admin status=' .
        $status .
        ' code=' .
        ($selfDemote['error']['code'] ?? 'none'),
);
[$status, $selfDisable] = $request(
    'POST',
    '/api/v1/users/' . (int) $me['data']['id'] . '/disable',
    [],
);
$assert(
    $status === 403,
    'self-disable is rejected status=' .
        $status .
        ' code=' .
        ($selfDisable['error']['code'] ?? 'none'),
);
[$status, $users] = $request('GET', '/api/v1/users');
$assert($status === 200 && !isset($users['data'][0]['password_hash']), 'user list has no secrets');
[$status, $forbidden] = $request('GET', '/api/v1/users/999999');
$assert(in_array($status, [401, 403, 404], true), 'resource ID is server-checked');
$prefix = 'access_' . bin2hex(random_bytes(4));
$db = Connection::make();
$q = $db->prepare(
    <<<'SQL'
    INSERT INTO users (display_name, email, username, account_status)
    VALUES
        (?, ?, ?, "active")
    SQL
    ,
);
$q->execute([$prefix . ' player', $prefix . '@example.test', $prefix]);
$playerId = (int) $db->lastInsertId();
$q = $db->prepare(
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
);
$q->execute([$playerId]);
$adminCookie = $cookie;
$cookie = new SessionStore($db)->create($playerId);
[$status, $playerUsers] = $request('GET', '/api/v1/users');
$assert(
    $status === 403 && isset($playerUsers['error']),
    'ordinary player cannot use admin user list',
);
[$status, $playerDetail] = $request('GET', '/api/v1/users/' . (int) $me['data']['id']);
$assert(
    $status === 403 && isset($playerDetail['error']),
    'ordinary player cannot use admin user detail',
);
$cookie = $adminCookie;
echo "PASS: HTTP auth smoke scenarios\n";
