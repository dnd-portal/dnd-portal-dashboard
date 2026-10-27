<?php

declare(strict_types=1);

namespace DndPortal;

use DndPortal\Http\Response;

final class HealthService {
    public static function report(): never {
        $checks = [
            'php' => version_compare(PHP_VERSION, '8.2.0', '>=') ? 'geslaagd' : 'gefaald',
            'json' => extension_loaded('json') ? 'geslaagd' : 'gefaald',
            'pdo' => extension_loaded('pdo') ? 'geslaagd' : 'gefaald',
            'pdo_mysql' => extension_loaded('pdo_mysql') ? 'geslaagd' : 'gefaald',
        ];
        $healthy = !in_array('gefaald', $checks, true);
        Response::json(
            ['data' => ['status' => $healthy ? 'ok' : 'degraded', 'checks' => $checks]],
            $healthy ? 200 : 503,
        );
    }
}
