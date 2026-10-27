<?php

declare(strict_types=1);
require dirname(__DIR__, 2).'/src/Config.php';
require dirname(__DIR__, 2).'/src/Database/Connection.php';
use DndPortal\Config;
use DndPortal\Database\Connection;

Config::loadDotEnv(dirname(__DIR__, 2).'/.env');
if (Config::env('APP_ENV') !== 'testing' || !preg_match('/^dnd_portal_test_run_[0-9]+$/', Config::env('DB_NAME', ''))) {
    exit(2);
}$base = getenv('TEST_BACKEND_URL') ?: 'http://127.0.0.1:8099';
$db = Connection::make();
$cookie = '';
$csrf = '';
$req = function (string $m, string $p, ?array $b = null) use (&$cookie, &$csrf, $base): array {
    $h = ['Accept: application/json','Origin: http://127.0.0.1:5173'];
    if ($cookie) {
        $h[] = 'Cookie: dnd_session='.$cookie;
    }if ($b !== null) {
        $h[] = 'Content-Type: application/json';
        $h[] = 'X-CSRF-Token: '.$csrf;
    }$raw = file_get_contents($base.$p, false, stream_context_create(['http' => ['method' => $m,'ignore_errors' => true,'header' => implode("\r\n", $h),'content' => $b === null ? '' : json_encode($b)]]));
    $s = (int)preg_replace('/^HTTP\/[^ ]+ ([0-9]+).*$/', '$1', $http_response_header[0] ?? '0');
    foreach ($http_response_header ?? [] as $v) {
        if (stripos($v, 'Set-Cookie: dnd_session=') === 0) {
            $cookie = explode('=', explode(';', trim(substr($v, 11)), 2)[0], 2)[1];
        }
    }return[$s,json_decode($raw ?: '{}', true) ?: []];
};
$ok = function (bool $v, string $n): void {
    if (!$v) {
        throw new RuntimeException('FAIL: '.$n);
    }echo"PASS: $n\n";
};
[$s,$b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s,$b] = $req('POST', '/api/v1/auth/login', ['identifier' => getenv('TEST_ADMIN_EMAIL'),'password' => getenv('TEST_ADMIN_PASSWORD')]);
$ok($s === 200, 'session matrix admin login');
[$s,$b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s,$b] = $req('POST', '/api/v1/campaigns', ['name' => 'Session matrix','slug' => 'session-matrix-'.bin2hex(random_bytes(3)),'type' => 'campaign','timezone' => 'Europe/Amsterdam']);
$campaign = (int)$b['data']['id'];
$admin = (int)($b['data']['created_by'] ?? 0);
[$s,$b] = $req('GET', '/api/v1/auth/me');
$admin = (int)$b['data']['id'];
$playerName = 'session-player-'.bin2hex(random_bytes(3));
$db->prepare('INSERT INTO users(display_name,email,username,account_status) VALUES("Session Player",?,?,"active")')->execute([$playerName.'@example.test',$playerName]);
$player = (int)$db->lastInsertId();
$db->prepare('INSERT INTO user_credentials(user_id,password_hash,password_algo) VALUES(?,?,?)')->execute([$player,password_hash('SessionPlayer2026!', PASSWORD_DEFAULT),PASSWORD_DEFAULT]);
$outsiderName = 'session-outsider-'.bin2hex(random_bytes(3));
$db->prepare('INSERT INTO users(display_name,email,username,account_status) VALUES("Session Outsider",?,?,"active")')->execute([$outsiderName.'@example.test',$outsiderName]);
$outsider = (int)$db->lastInsertId();
$db->prepare('INSERT INTO user_credentials(user_id,password_hash,password_algo) VALUES(?,?,?)')->execute([$outsider,password_hash('SessionOutsider2026!', PASSWORD_DEFAULT),PASSWORD_DEFAULT]);
$db->prepare('INSERT INTO campaign_memberships(campaign_id,user_id,membership_role) VALUES(?,?,"member")')->execute([$campaign,$player]);
[$s,$b] = $req('POST', "/api/v1/campaigns/$campaign/sessions", ['title' => 'DST boundary session','session_date' => '2026-10-25']);
$ok($s === 201, 'session create with DST boundary date');
$session = (int)$b['data']['id'];
$version = (int)$b['data']['version'];
[$s,$b] = $req('PATCH', "/api/v1/sessions/$session/participants/$player", ['rsvp_status' => 'accepted']);
$ok($s === 200, 'manager adds participant RSVP');
[$s,$b] = $req('PATCH', "/api/v1/sessions/$session/participants/$player", ['attendance_status' => 'present']);
$ok($s === 200, 'manager records attendance separately');
[$s,$b] = $req('GET', "/api/v1/sessions/$session/participants");
$row = $b['data'][0] ?? [];
$ok($s === 200 && $row['rsvp_status'] === 'accepted' && $row['attendance_status'] === 'present', 'RSVP and attendance remain separate');
[$s,$b] = $req('POST', "/api/v1/campaigns/$campaign/members/$player/leave", []);
$ok($s === 200, 'member leaves campaign');
[$s,$b] = $req('GET', "/api/v1/sessions/$session/participants");
$ok($s === 200 && count($b['data']) === 1, 'historical participant remains after membership leave');
[$s,$b] = $req('POST', '/api/v1/auth/logout');
$cookie = '';
[$s,$b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s,$b] = $req('POST', '/api/v1/auth/login', ['identifier' => $playerName,'password' => 'SessionPlayer2026!']);
$ok($s === 200, 'session player login');
[$s,$b] = $req('GET', "/api/v1/sessions/$session/participants");
$ok($s === 403, 'player cannot view session after leaving campaign');
[$s,$b] = $req('POST', '/api/v1/auth/logout');
$cookie = '';
[$s,$b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s,$b] = $req('POST', '/api/v1/auth/login', ['identifier' => $outsiderName,'password' => 'SessionOutsider2026!']);
$ok($s === 200, 'session outsider login');
[$s,$b] = $req('GET', "/api/v1/sessions/$session");
$ok($s === 403, 'outsider cannot view session');
[$s,$b] = $req('POST', '/api/v1/auth/logout');
$cookie = '';
[$s,$b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s,$b] = $req('POST', '/api/v1/auth/login', ['identifier' => getenv('TEST_ADMIN_EMAIL'),'password' => getenv('TEST_ADMIN_PASSWORD')]);
$ok($s === 200, 'availability admin login');
[$s,$b] = $req('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
foreach (['2026-12-31','2027-01-01','2028-02-29'] as $date) {
    [$s,$b] = $req('PUT', "/api/v1/availability/$date", ['status' => 'available','note' => 'date boundary']);
    $ok($s === 200 || $date === '2028-02-29', 'availability boundary '.$date);
}[$s,$b] = $req('GET', '/api/v1/availability');
$dates = array_column($b['data'] ?? [], 'availability_date');
$ok(in_array('2026-12-31', $dates, true) && in_array('2027-01-01', $dates, true), 'availability DATE year boundary preserved');
echo"PASS: session availability matrix\n";
