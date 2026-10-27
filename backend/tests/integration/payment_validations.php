<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';
use DndPortal\Config;
use DndPortal\Database\Connection;

Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
if (Config::env('APP_ENV') !== 'testing' || !preg_match('/^dnd_portal_test_run_[0-9]+$/', (string) Config::env('DB_NAME'))) {
    exit("REFUSED\n");
}
$db = Connection::make();
$base = getenv('TEST_BACKEND_URL') ?: 'http://127.0.0.1:8099';
$cookie = '';
$csrf = '';
$req = function (string $m, string $p, ?array $body = null) use (&$cookie, &$csrf, $base): array {
    $h = ['Accept: application/json','Origin: http://127.0.0.1:5173'];
    if ($cookie) {
        $h[] = 'Cookie: dnd_session=' . $cookie;
    }
    if ($body !== null) {
        $h[] = 'Content-Type: application/json';
        $h[] = 'X-CSRF-Token: ' . $csrf;
    }
    $raw = file_get_contents($base . $p, false, stream_context_create(['http' => ['method' => $m, 'ignore_errors' => true, 'header' => implode("\r\n", $h), 'content' => $body === null ? '' : json_encode($body)]]));
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
[$s,$b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s,$b] = $req('POST', '/api/v1/auth/login', ['identifier' => getenv('TEST_ADMIN_EMAIL'),'password' => getenv('TEST_ADMIN_PASSWORD')]);
$ok($s === 200, 'payment admin login');
[$s,$b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s,$b] = $req('POST', '/api/v1/campaigns', ['name' => 'Payment validation','slug' => 'payment-validation-' . bin2hex(random_bytes(3)),'type' => 'campaign']);
$campaign = (int) $b['data']['id'];
[$s,$me] = $req('GET', '/api/v1/auth/me');
$admin = (int) $me['data']['id'];
$db->prepare('INSERT INTO campaign_memberships(
    campaign_id,
    user_id,
    membership_role
)
VALUES(?, ?, "dm")
ON DUPLICATE KEY
UPDATE
    membership_role = "dm",
    left_at = NULL')->execute([$campaign,$admin]);
$username = 'payplayer-' . bin2hex(random_bytes(3));
$db->prepare('INSERT INTO users(
    display_name,
    email,
    username,
    account_status
)
VALUES("Payment Player", ?, ?, "active")')->execute([$username . '@example.test',$username]);
$player = (int) $db->lastInsertId();
$db->prepare('INSERT INTO user_credentials(
    user_id,
    password_hash,
    password_algo
)
VALUES(?, ?, ?)')->execute([$player,password_hash('PayPlayer2026!', PASSWORD_DEFAULT),PASSWORD_DEFAULT]);
[$s,$b] = $req('POST', "/api/v1/campaigns/$campaign/payment-requests", ['title' => 'Table fee','amount_cents' => 10000,'currency' => 'EUR','external_url' => 'https://payments.example.test/request']);
$ok($s === 201, 'request create');
$rid = (int) $b['data']['id'];
[$s,$b] = $req('POST', "/api/v1/payment-requests/$rid/obligations", ['user_id' => $player,'amount_cents' => 10000,'currency' => 'EUR']);
$ok($s === 201, 'obligation create');
$oid = (int) $b['data']['id'];
[$s,$b] = $req('POST', "/api/v1/payment-obligations/$oid/entries", ['amount_cents' => 4000,'currency' => 'EUR','external_reference' => 'pay-1']);
$ok($s === 201 && $b['data']['outstanding_cents'] === 6000, 'partial balance');
$entry = (int) $b['data']['id'];
[$s,$b] = $req('POST', "/api/v1/payment-obligations/$oid/entries", ['amount_cents' => 6000,'currency' => 'EUR','external_reference' => 'pay-2']);
$ok($s === 201 && $b['data']['outstanding_cents'] === 0, 'full balance');
[$s,$b] = $req('POST', "/api/v1/payment-obligations/$oid/entries", ['amount_cents' => 1000,'currency' => 'EUR','external_reference' => 'rev-1','correction_of_entry_id' => $entry]);
$ok($s === 201 && $b['data']['entry_kind'] === 'reversal', 'reversal');
[$s,$b] = $req('POST', "/api/v1/payment-obligations/$oid/entries", ['amount_cents' => 4001,'currency' => 'EUR','external_reference' => 'rev-too-much','correction_of_entry_id' => $entry]);
$ok($s === 422, 'reversal bound');
[$s,$b] = $req('POST', "/api/v1/payment-obligations/$oid/entries", ['amount_cents' => 100,'currency' => 'EUR','external_reference' => 'pay-1']);
$ok($s === 409, 'duplicate payment reference rejected');
$q = $db->prepare('SELECT
    COUNT(*)
FROM
    payment_entries
WHERE
    obligation_id = ? AND external_reference = ?');
$q->execute([$oid,'pay-1']);
$ok((int) $q->fetchColumn() === 1, 'duplicate reference created no second entry');
[$s,$b] = $req('POST', "/api/v1/campaigns/$campaign/payment-requests", ['title' => 'USD table fee','amount_cents' => 2500,'currency' => 'USD']);
$ok($s === 201, 'second currency request create');
$usdRequest = (int) $b['data']['id'];
[$s,$b] = $req('POST', "/api/v1/payment-requests/$usdRequest/obligations", ['user_id' => $player,'amount_cents' => 2500,'currency' => 'USD']);
$ok($s === 201, 'second currency obligation create');
[$s,$b] = $req('POST', '/api/v1/auth/logout');
$cookie = '';
[$s,$b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s,$b] = $req('POST', '/api/v1/auth/login', ['identifier' => $username,'password' => 'PayPlayer2026!']);
$ok($s === 200, 'payment player login');
[$s,$b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s,$b] = $req('GET', '/api/v1/me/payment-obligations');
$ok($s === 200 && isset($b['data'][0]) && (int) $b['data'][0]['paid_cents'] === 9000, 'private own balance');
[$s,$b] = $req('POST', "/api/v1/payment-obligations/$oid/entries", ['amount_cents' => 100,'currency' => 'EUR','external_reference' => 'unauthorized']);
$ok($s === 403, 'player payment write denied');
[$s,$b] = $req('GET', '/api/v1/dashboard');
$ok($s === 200 && (int) $b['data']['open_contributions_cents'] === 1000 && (int) $b['data']['open_contributions_by_currency']['EUR'] === 1000 && (int) $b['data']['open_contributions_by_currency']['USD'] === 2500 && !isset($b['data']['admin']), 'player dashboard own reversal-aware balance and separate currencies');
echo "PASS: payment scenarios\n";
[$s,$b] = $req('POST', '/api/v1/auth/logout');
$cookie = '';
[$s,$b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s,$b] = $req('POST', '/api/v1/auth/login', ['identifier' => getenv('TEST_ADMIN_EMAIL'),'password' => getenv('TEST_ADMIN_PASSWORD')]);
$ok($s === 200, 'dashboard admin login');
[$s,$b] = $req('GET', '/api/v1/dashboard');
$ok($s === 200 && isset($b['data']['admin']) && array_key_exists('activity', $b['data']['admin']), 'admin dashboard totals and activity');
