<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/Config.php';
use DndPortal\Config;

Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
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
$check = function (bool $ok, string $name, int $status, array $body): void {
    if (!$ok) {
        throw new RuntimeException("FAIL $name status=$status body=" . json_encode($body));
    }
    echo "PASS $name\n";
};
[$s, $b] = $request('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
[$s, $b] = $request('POST', '/api/v1/auth/login', [
    'identifier' => getenv('TEST_ADMIN_EMAIL'),
    'password' => getenv('TEST_ADMIN_PASSWORD'),
]);
$check($s === 200, 'login', $s, $b);
[$s, $b] = $request('GET', '/api/v1/auth/csrf');
$csrf = $b['data']['token'] ?? '';
$slug = 'restore-' . bin2hex(random_bytes(3));
[$s, $b] = $request('POST', '/api/v1/campaigns', [
    'name' => 'Restore test',
    'slug' => $slug,
    'type' => 'campaign',
]);
$check($s === 201 && isset($b['data']['id']), 'create', $s, $b);
$id = (int) $b['data']['id'];
$version = (int) $b['data']['version'];
$adminId = (int) ($b['data']['created_by'] ?? 0);
if ($adminId === 0) {
    [$ms, $mb] = $request('GET', '/api/v1/auth/me');
    $adminId = (int) ($mb['data']['id'] ?? 0);
}
[$s, $b] = $request('POST', "/api/v1/campaigns/$id/members", [
    'user_id' => $adminId,
    'membership_role' => 'member',
]);
$check(
    $s === 201 &&
        ($b['data']['campaign_id'] ?? 0) === $id &&
        ($b['data']['user_id'] ?? 0) === $adminId,
    'membership POST reads stored row',
    $s,
    $b,
);
[$s, $b] = $request('GET', "/api/v1/campaigns/$id/members");
$check(
    $s === 200 &&
        count(
            array_filter(
                $b['data'] ?? [],
                static fn(array $row): bool => (int) $row['campaign_id'] === $id &&
                    (int) $row['user_id'] === $adminId,
            ),
        ) === 1,
    'membership GET returns stored row',
    $s,
    $b,
);
[$s, $b] = $request('POST', "/api/v1/campaigns/$id/sessions", [
    'title' => 'DST session',
    'session_date' => '2026-10-25',
]);
$check($s === 201 && isset($b['data']['id']), 'session create', $s, $b);
$sessionId = (int) $b['data']['id'];
$sessionVersion = (int) $b['data']['version'];
[$s, $b] = $request('GET', "/api/v1/campaigns/$id/sessions");
$check(
    $s === 200 &&
        count(
            array_filter(
                $b['data'] ?? [],
                static fn(array $row): bool => (int) $row['id'] === $sessionId,
            ),
        ) === 1,
    'session list route ID',
    $s,
    $b,
);
[$s, $b] = $request('GET', "/api/v1/sessions/$sessionId/participants");
$check($s === 200 && is_array($b['data'] ?? null), 'participant list route ID', $s, $b);
[$s, $b] = $request('PATCH', "/api/v1/sessions/$sessionId/participants/$adminId", [
    'rsvp_status' => 'accepted',
]);
$check($s === 200, 'player RSVP stored separately', $s, $b);
[$s, $b] = $request('PATCH', "/api/v1/sessions/$sessionId/participants/$adminId", [
    'attendance_status' => 'present',
]);
$check($s === 200, 'manager attendance stored separately', $s, $b);
[$s, $b] = $request('PATCH', "/api/v1/sessions/$sessionId", [
    'status' => 'cancelled',
    'version' => $sessionVersion,
]);
$check(
    $s === 200 && ($b['data']['status'] ?? '') === 'cancelled',
    'session cancellation preserves record',
    $s,
    $b,
);
$availabilityDate = gmdate('Y-m-d');
[$s, $b] = $request('PUT', "/api/v1/availability/$availabilityDate", [
    'status' => 'available',
    'note' => 'integration',
]);
$check($s === 200, 'availability save', $s, $b);
[$s, $b] = $request('GET', '/api/v1/availability');
$check(
    $s === 200 &&
        count(
            array_filter(
                $b['data'] ?? [],
                static fn(array $row): bool => ($row['availability_date'] ?? '') ===
                    $availabilityDate,
            ),
        ) === 1,
    'availability reload keeps DATE',
    $s,
    $b,
);
[$s, $b] = $request('DELETE', "/api/v1/availability/$availabilityDate", []);
$check($s === 200, 'availability delete to unknown', $s, $b);
[$s, $b] = $request('POST', "/api/v1/campaigns/$id/polls", [
    'title' => 'Integration poll',
    'choice_type' => 'single',
    'options' => ['Yes', 'No'],
]);
$check($s === 201 && isset($b['data']['id']), 'poll create', $s, $b);
$pollId = (int) $b['data']['id'];
$optionId = (int) $b['data']['options'][0]['id'];
$otherOption = (int) $b['data']['options'][1]['id'];
$pollVersion = (int) $b['data']['version'];
[$s, $b] = $request('GET', "/api/v1/polls/$pollId");
$check($s === 200 && count($b['data']['options'] ?? []) === 2, 'poll get', $s, $b);
[$s, $b] = $request('POST', "/api/v1/polls/$pollId/participants/$adminId", ['active' => true]);
$check($s === 200, 'poll participant assignment', $s, $b);
[$s, $b] = $request('PUT', "/api/v1/polls/$pollId/vote", ['option_ids' => [$optionId]]);
$check($s === 200, 'poll single vote', $s, $b);
[$s, $b] = $request('GET', "/api/v1/polls/$pollId/results");
$check(
    $s === 200 && (int) ($b['data']['options'][0]['vote_count'] ?? 0) === 1,
    'poll results',
    $s,
    $b,
);
[$s, $b] = $request('PUT', "/api/v1/polls/$pollId/vote", [
    'option_ids' => [$otherOption, $optionId],
]);
$check($s === 422, 'poll single-choice rejected', $s, $b);
[$s, $b] = $request('PATCH', "/api/v1/polls/$pollId", [
    'status' => 'closed',
    'version' => $pollVersion,
]);
$check($s === 200, 'poll close', $s, $b);
[$s, $b] = $request('PUT', "/api/v1/polls/$pollId/vote", ['option_ids' => [$optionId]]);
$check($s === 409, 'closed poll rejects vote', $s, $b);
[$s, $b] = $request('PATCH', "/api/v1/campaigns/$id", [
    'archived_at' => gmdate('c'),
    'version' => $version,
]);
$check($s === 200 && $b['data']['archived_at'] !== null, 'archive', $s, $b);
$version = (int) $b['data']['version'];
[$s, $b] = $request('PATCH', "/api/v1/campaigns/$id", [
    'archived_at' => null,
    'version' => $version,
]);
$check(
    $s === 200 && array_key_exists('archived_at', $b['data']) && $b['data']['archived_at'] === null,
    'restore',
    $s,
    $b,
);
$version = (int) $b['data']['version'];
[$s, $b] = $request('GET', "/api/v1/campaigns/$id");
$check(
    $s === 200 && $b['data']['archived_at'] === null && $b['data']['version'] === $version,
    'reload',
    $s,
    $b,
);
[$s, $b] = $request('PATCH', "/api/v1/campaigns/$id", [
    'archived_at' => null,
    'version' => $version - 1,
]);
$check($s === 409, 'stale version', $s, $b);
echo "PASS campaign restore flow\n";
