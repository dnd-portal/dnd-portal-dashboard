<?php

declare(strict_types=1);

namespace DndPortal\Auth;

use DndPortal\Config;
use DndPortal\Http\Request;
use DndPortal\Http\Response;

final class Security {
    private static function cookieOptions(int $expires): array {
        return [
            'expires' => $expires,
            'path' => '/',
            'secure' => Config::isProduction(),
            'httponly' => true,
            'samesite' => Config::env('AUTH_COOKIE_SAMESITE', 'Lax'),
        ];
    }

    public static function cookie(string $value, int $maxAge): void {
        setcookie(
            Config::env('AUTH_COOKIE', 'dnd_session'),
            $value,
            self::cookieOptions(time() + $maxAge),
        );
    }

    public static function clearCookie(): void {
        setcookie(Config::env('AUTH_COOKIE', 'dnd_session'), '', self::cookieOptions(1));
    }

    public static function csrf(Request $request, bool $mutating): void {
        if (!$mutating) {
            return;
        }
        $origin = $request->header('Origin');
        $allowed = array_values(array_filter(array_map('trim', explode(',', Config::env('APP_ALLOWED_ORIGIN')))));
        if ($origin === '' || !in_array($origin, $allowed, true)) {
            Response::error('csrf_failed', 'Request origin is not allowed.', 419);
        }
        $cookie = $_COOKIE[Config::env('AUTH_COOKIE', 'dnd_session')] ?? '';
        $token = $request->header('X-CSRF-Token');
        if ($cookie === '' || $token === '' || !hash_equals(hash('sha256', $cookie), $token)) {
            Response::error('csrf_failed', 'CSRF validation failed.', 419);
        }
    }

    public static function code(): string {
        return rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    }
}
