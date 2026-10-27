<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';
use DndPortal\Config;
use DndPortal\Database\Connection;

Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
if (
    Config::env('APP_ENV') !== 'testing' ||
    !preg_match('/^dnd_portal_test_run_[0-9]+$/', Config::env('DB_NAME', ''))
) {
    exit(2);
}
$base = getenv('TEST_BACKEND_URL') ?: 'http://127.0.0.1:8099';
$cookie = '';
$csrf = '';
$req = function (string $m, string $p, ?array $b = null) use (&$cookie, &$csrf, $base): array {
    $h = ['Accept: application/json', 'Origin: http://127.0.0.1:5173'];
    if ($cookie) {
        $h[] = 'Cookie: dnd_session=' . $cookie;
    }
    if ($b !== null) {
        $h[] = 'Content-Type: application/json';
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
                'content' => $b === null ? '' : json_encode($b),
            ],
        ]),
    );
    $s = (int) preg_replace('/^HTTP\/[^ ]+ ([0-9]+).*$/', '$1', $http_response_header[0] ?? '0');
    foreach ($http_response_header ?? [] as $v) {
        if (stripos($v, 'Set-Cookie: dnd_session=') === 0) {
            $cookie = explode('=', explode(';', trim(substr($v, 11)), 2)[0], 2)[1];
        }
    }
    return [$s, json_decode($raw ?: '{}', true) ?: []];
};
$ok = function (bool $v, string $n): void {
    if (!$v) {
        throw new RuntimeException('FAIL: ' . $n);
    }
    echo "PASS: $n\n";
};
[$s, $x] = $req('GET', '/api/v1/auth/csrf');
$csrf = $x['data']['token'] ?? '';
[$s, $x] = $req('POST', '/api/v1/auth/login', [
    'identifier' => getenv('TEST_ADMIN_EMAIL'),
    'password' => getenv('TEST_ADMIN_PASSWORD'),
]);
$ok($s === 200, 'campaign login');
[$s, $x] = $req('GET', '/api/v1/auth/csrf');
$csrf = $x['data']['token'] ?? '';
$slug = 'it-campaign-' . bin2hex(random_bytes(3));
[$s, $x] = $req('POST', '/api/v1/campaigns', [
    'name' => 'Integration Campaign',
    'slug' => $slug,
    'type' => 'campaign',
]);
$ok($s === 201, 'campaign create');
$id = (int) $x['data']['id'];
$v = (int) $x['data']['version'];
[$s, $x] = $req('GET', '/api/v1/campaigns');
$ok($s === 200, 'campaign list');
[$s, $x] = $req('GET', '/api/v1/campaigns/' . $id);
$ok($s === 200 && $x['data']['type'] === 'campaign', 'campaign detail');
[$s, $x] = $req('PATCH', '/api/v1/campaigns/' . $id, ['name' => 'stale', 'version' => $v - 1]);
$ok($s === 409, 'version conflict');
[$s, $x] = $req('PATCH', '/api/v1/campaigns/' . $id, [
    'name' => 'bad',
    'slug' => $slug,
    'type' => 'campaign',
    'version' => $v,
]);
$ok($s === 422, 'immutable type rejected');
[$s, $x] = $req('PATCH', '/api/v1/campaigns/' . $id, [
    'name' => 'Archived Campaign',
    'slug' => $slug,
    'location' => null,
    'timezone' => 'UTC',
    'archived_at' => gmdate('c'),
    'version' => $v,
]);
$ok($s === 200, 'archive');
$v = (int) $x['data']['version'];
[$s, $x] = $req('PATCH', '/api/v1/campaigns/' . $id, [
    'name' => 'Archived Campaign',
    'slug' => $slug,
    'location' => null,
    'timezone' => 'UTC',
    'archived_at' => null,
    'version' => $v,
]);
$ok($s === 200 && ($x['data']['archived_at'] ?? 'x') === null, 'restore');
[$s, $x] = $req('POST', '/api/v1/campaigns', [
    'name' => 'Integration One-shot',
    'slug' => 'it-one-shot-' . bin2hex(random_bytes(3)),
    'type' => 'one_shot',
]);
$ok($s === 201 && $x['data']['type'] === 'one_shot', 'one-shot type');
echo "PASS: campaign flow 10 checks\n";
