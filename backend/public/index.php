<?php

declare(strict_types=1);

// Single public front controller. Keep configuration and src/ outside the public web directory when hosting permits.
require dirname(__DIR__) . '/src/Config.php';
\DndPortal\Config::loadDotEnv(dirname(__DIR__) . '/.env');
require dirname(__DIR__) . '/src/Http/Request.php';
require dirname(__DIR__) . '/src/Http/Response.php';
require dirname(__DIR__) . '/src/Http/Router.php';
require dirname(__DIR__) . '/src/HealthService.php';
require dirname(__DIR__) . '/src/Database/Connection.php';
require dirname(__DIR__) . '/src/Auth/SessionStore.php';
require dirname(__DIR__) . '/src/Auth/Security.php';
require dirname(__DIR__) . '/src/Auth/RateLimiter.php';
require dirname(__DIR__) . '/src/Auth/AuthService.php';
require dirname(__DIR__) . '/src/Domain/CampaignService.php';
require dirname(__DIR__) . '/src/Domain/PollService.php';
require dirname(__DIR__) . '/src/Domain/PaymentService.php';
require dirname(__DIR__) . '/src/Domain/DashboardService.php';

use DndPortal\HealthService;
use DndPortal\Http\Request;
use DndPortal\Http\Response;
use DndPortal\Http\Router;
use DndPortal\Database\Connection;
use DndPortal\Auth\SessionStore;
use DndPortal\Auth\Security;
use DndPortal\Auth\AuthService;
use DndPortal\Domain\CampaignService;
use DndPortal\Domain\PollService;
use DndPortal\Domain\PaymentService;
use DndPortal\Domain\DashboardService;
use DndPortal\Auth\RateLimiter;

$request = new Request();
$origin = $request->header('Origin');
$allowedOrigin = DndPortal\Config::env('APP_ALLOWED_ORIGIN');
if ($origin !== '' && $allowedOrigin !== '' && hash_equals($allowedOrigin, $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
    header('Vary: Origin');
}

$router = new Router();
$router->options(
    '/api/v1/health',
    static fn () => Response::json(['data' => ['status' => 'ok']]),
);
$router->get(
    '/api/v1/health',
    static fn () => HealthService::report(),
);
$router->get(
    '/api/v1/auth/csrf',
    static function () {
        $db = Connection::make();
        (new AuthService($db, new SessionStore($db)))->csrf();
    },
);
$router->post(
    '/api/v1/auth/login',
    static function ($r) {
        $db = Connection::make();
        (new RateLimiter($db))->check(
            'login',
            $r->header(
                'X-Forwarded-For',
            ) ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            10,
        );
        Security::csrf($r, true);
        (new AuthService($db, new SessionStore($db)))->login($r->json());
    },
);
$router->get(
    '/api/v1/auth/me',
    static function () {
        $db = Connection::make();
        (new AuthService($db, new SessionStore($db)))->me();
    },
);
$router->post(
    '/api/v1/auth/logout',
    static function ($r) {
        Security::csrf($r, true);
        $db = Connection::make();
        (new AuthService($db, new SessionStore($db)))->logout();
    },
);
$router->post(
    '/api/v1/auth/register',
    static function ($r) {
        Security::csrf($r, true);
        $db = Connection::make();
        (new AuthService($db, new SessionStore($db)))->register($r->json());
    },
);
$router->post('/api/v1/auth/invite/validate', static function ($r) {
    Security::csrf($r, true);
    $db = Connection::make();
    (new AuthService($db, new SessionStore($db)))->validateInvite($r->json());
});
$router->post('/api/v1/auth/password-reset/validate', static function ($r) {
    Security::csrf($r, true);
    $db = Connection::make();
    (new AuthService($db, new SessionStore($db)))->validateReset($r->json());
});
$router->post(
    '/api/v1/auth/password-reset/complete',
    static function ($r) {
        Security::csrf($r, true);
        $db = Connection::make();
        (new AuthService($db, new SessionStore($db)))->reset($r->json());
    },
);
$router->post(
    '/api/v1/admin/invites',
    static function ($r) {
        Security::csrf($r, true);
        $db = Connection::make();
        $a = new AuthService($db, new SessionStore($db));
        $a->invite($a->requireRole('portal_admin'), $r->json());
    },
);
$router->post(
    '/api/v1/admin/password-reset-codes',
    static function ($r) {
        Security::csrf($r, true);
        $db = Connection::make();
        $a = new AuthService($db, new SessionStore($db));
        $a->resetIssue($a->requireRole('portal_admin'), $r->json());
    },
);
$router->post(
    '/api/v1/admin/invites/revoke',
    static function ($r) {
        Security::csrf($r, true);
        $db = Connection::make();
        $a = new AuthService($db, new SessionStore($db));
        $a->requireRole('portal_admin');
        $q = $db->prepare('UPDATE registration_invites SET consumed_at=? WHERE id=? AND consumed_at IS NULL');
        $q->execute([gmdate('Y-m-d H:i:s.u'),(int)($r->json()['id'] ?? 0)]);
        Response::json(['data' => null]);
    },
);
$router->post(
    '/api/v1/admin/password-reset-codes/revoke',
    static function ($r) {
        Security::csrf($r, true);
        $db = Connection::make();
        $a = new AuthService($db, new SessionStore($db));
        $a->requireRole('portal_admin');
        $q = $db->prepare('UPDATE password_reset_tokens SET used_at=? WHERE id=? AND used_at IS NULL');
        $q->execute([gmdate('Y-m-d H:i:s.u'),(int)($r->json()['id'] ?? 0)]);
        Response::json(['data' => null]);
    },
);
$router->get(
    '/api/v1/users/{id}',
    static function ($r) {
        $db = Connection::make();
        $a = new AuthService($db, new SessionStore($db));
        $a->getUser((int)basename($r->path()));
    },
);
$router->get('/api/v1/users', static function () {
    $db = Connection::make();
    (new AuthService($db, new SessionStore($db)))->listUsers();
});
$router->patch(
    '/api/v1/users/{id}',
    static function ($r) {
        Security::csrf($r, true);
        $db = Connection::make();
        $a = new AuthService($db, new SessionStore($db));
        $a->updateUser($a->requireRole('portal_admin'), (int)basename($r->path()), $r->json());
    },
);
$router->post(
    '/api/v1/users/{id}/disable',
    static function ($r) {
        Security::csrf($r, true);
        $db = Connection::make();
        $a = new AuthService($db, new SessionStore($db));
        $parts = explode('/', trim($r->path(), '/'));
        $a->disable($a->requireRole('portal_admin'), (int)($parts[3] ?? 0));
    },
);
$router->get(
    '/api/v1/campaigns',
    static function () {
        $db = Connection::make();
        $a = new AuthService($db, new SessionStore($db));
        $new = new CampaignService($db);
        $new->list($a->currentId());
    },
);
$router->get('/api/v1/campaigns/{id}', static function ($r) {
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new CampaignService($db))->get($a->currentId(), (int)basename($r->path()));
});
$router->get('/api/v1/campaigns/{id}/members', static function ($r) {
    $parts = explode('/', trim($r->path(), '/'));
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new CampaignService($db))->members($a->currentId(), (int)($parts[3] ?? 0));
});
$router->post('/api/v1/campaigns/{id}/members', static function ($r) {
    Security::csrf($r, true);
    $parts = explode('/', trim($r->path(), '/'));
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new CampaignService($db))->addMember($a->currentId(), (int)($parts[3] ?? 0), (int)($r->json()['user_id'] ?? 0), $r->json());
});
$router->patch('/api/v1/campaigns/{id}/members/{userId}', static function ($r) {
    Security::csrf($r, true);
    $parts = explode('/', trim($r->path(), '/'));
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new CampaignService($db))->addMember($a->currentId(), (int)($parts[3] ?? 0), (int)($parts[5] ?? 0), $r->json());
});
$router->post('/api/v1/campaigns/{id}/members/{userId}/leave', static function ($r) {
    Security::csrf($r, true);
    $parts = explode('/', trim($r->path(), '/'));
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new CampaignService($db))->leave($a->currentId(), (int)($parts[3] ?? 0), (int)($parts[5] ?? 0));
});
$router->post(
    '/api/v1/campaigns',
    static function ($r) {
        Security::csrf($r, true);
        $db = Connection::make();
        $a = new AuthService($db, new SessionStore($db));
        $new = new CampaignService($db);
        $new->create($a->requireRole('portal_admin'), $r->json());
    },
);
$router->patch(
    '/api/v1/campaigns/{id}',
    static function ($r) {
        Security::csrf($r, true);
        $db = Connection::make();
        $a = new AuthService($db, new SessionStore($db));
        $new = new CampaignService($db);
        $new->update($a->currentId(), (int)basename($r->path()), $r->json());
    },
);
$router->get(
    '/api/v1/campaigns/{id}/sessions',
    static function ($r) {
        $parts = explode('/', trim($r->path(), '/'));
        $db = Connection::make();
        $a = new AuthService($db, new SessionStore($db));
        $new = new CampaignService($db);
        $new->sessions($a->currentId(), (int)($parts[3] ?? 0));
    },
);
$router->get('/api/v1/sessions/{id}/participants', static function ($r) {
    $parts = explode('/', trim($r->path(), '/'));
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new CampaignService($db))->participants($a->currentId(), (int)($parts[3] ?? 0));
});
$router->post(
    '/api/v1/campaigns/{id}/sessions',
    static function ($r) {
        Security::csrf($r, true);
        $parts = explode('/', trim($r->path(), '/'));
        $db = Connection::make();
        $a = new AuthService($db, new SessionStore($db));
        $new = new CampaignService($db);
        $new->createSession($a->currentId(), (int)($parts[3] ?? 0), $r->json());
    },
);
$router->get('/api/v1/sessions/{id}', static function ($r) {
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new CampaignService($db))->getSession($a->currentId(), (int)basename($r->path()));
});
$router->patch(
    '/api/v1/sessions/{id}',
    static function ($r) {
        Security::csrf($r, true);
        $db = Connection::make();
        $a = new AuthService($db, new SessionStore($db));
        $new = new CampaignService($db);
        $new->updateSession($a->currentId(), (int)basename($r->path()), $r->json());
    },
);
$router->patch(
    '/api/v1/sessions/{id}/participants/{userId}',
    static function ($r) {
        Security::csrf($r, true);
        $parts = explode('/', trim($r->path(), '/'));
        $db = Connection::make();
        $a = new AuthService($db, new SessionStore($db));
        $new = new CampaignService($db);
        $new->participant($a->currentId(), (int)$parts[3], (int)$parts[5], $r->json());
    },
);
$router->get(
    '/api/v1/availability',
    static function () {
        $db = Connection::make();
        $a = new AuthService($db, new SessionStore($db));
        $new = new CampaignService($db);
        $new->availability($a->currentId(), null, []);
    },
);
$router->put(
    '/api/v1/availability/{date}',
    static function ($r) {
        Security::csrf($r, true);
        $db = Connection::make();
        $a = new AuthService($db, new SessionStore($db));
        $new = new CampaignService($db);
        $new->setAvailability($a->currentId(), basename($r->path()), $r->json());
    },
);
$router->delete('/api/v1/availability/{date}', static function ($r) {
    Security::csrf($r, true);
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new CampaignService($db))->clearAvailability($a->currentId(), basename($r->path()));
});
$router->get('/api/v1/campaigns/{id}/polls', static function ($r) {
    $parts = explode('/', trim($r->path(), '/'));
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new PollService($db))->list($a->currentId(), (int)($parts[3] ?? 0));
});
$router->post('/api/v1/campaigns/{id}/polls', static function ($r) {
    Security::csrf($r, true);
    $parts = explode('/', trim($r->path(), '/'));
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new PollService($db))->create($a->currentId(), (int)($parts[3] ?? 0), $r->json());
});
$router->get('/api/v1/polls/{id}', static function ($r) {
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new PollService($db))->get($a->currentId(), (int)basename($r->path()));
});
$router->patch('/api/v1/polls/{id}', static function ($r) {
    Security::csrf($r, true);
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new PollService($db))->update($a->currentId(), (int)basename($r->path()), $r->json());
});
$router->put('/api/v1/polls/{id}/vote', static function ($r) {
    Security::csrf($r, true);
    $parts = explode('/', trim($r->path(), '/'));
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new PollService($db))->vote($a->currentId(), (int)($parts[3] ?? 0), $r->json());
});
$router->post('/api/v1/polls/{id}/respond-without-option', static function ($r) {
    Security::csrf($r, true);
    $parts = explode('/', trim($r->path(), '/'));
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new PollService($db))->respondWithoutOption($a->currentId(), (int)($parts[3] ?? 0));
});
$router->get('/api/v1/polls/{id}/results', static function ($r) {
    $parts = explode('/', trim($r->path(), '/'));
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new PollService($db))->results($a->currentId(), (int)($parts[3] ?? 0));
});
$router->post('/api/v1/polls/{id}/participants/{userId}', static function ($r) {
    Security::csrf($r, true);
    $parts = explode('/', trim($r->path(), '/'));
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new PollService($db))->setParticipant($a->currentId(), (int)$parts[3], (int)$parts[5], $r->json());
});
$router->get('/api/v1/campaigns/{id}/payment-requests', static function ($r) {
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    $parts = explode('/', trim($r->path(), '/'));
    (new PaymentService($db))->list($a->currentId(), (int)$parts[3]);
});
$router->post('/api/v1/campaigns/{id}/payment-requests', static function ($r) {
    Security::csrf($r, true);
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    $parts = explode('/', trim($r->path(), '/'));
    (new PaymentService($db))->create($a->currentId(), (int)$parts[3], $r->json());
});
$router->get('/api/v1/payment-requests/{id}', static function ($r) {
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new PaymentService($db))->get($a->currentId(), (int)basename($r->path()));
});
$router->post('/api/v1/payment-requests/{id}/obligations', static function ($r) {
    Security::csrf($r, true);
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    $parts = explode('/', trim($r->path(), '/'));
    (new PaymentService($db))->obligation($a->currentId(), (int)$parts[3], $r->json());
});
$router->post('/api/v1/payment-obligations/{id}/entries', static function ($r) {
    Security::csrf($r, true);
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    $parts = explode('/', trim($r->path(), '/'));
    (new PaymentService($db))->entry($a->currentId(), (int)$parts[3], $r->json());
});
$router->get('/api/v1/me/payment-obligations', static function () {
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new PaymentService($db))->own($a->currentId());
});
$router->get('/api/v1/dashboard', static function () {
    $db = Connection::make();
    $a = new AuthService($db, new SessionStore($db));
    (new DashboardService($db))->get($a->currentId());
});
$router->dispatch($request);
