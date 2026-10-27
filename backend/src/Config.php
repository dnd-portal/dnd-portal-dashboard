<?php

declare(strict_types=1);

namespace DndPortal;

final class Config
{
    /** @var array<string, bool> */
    private static array $loaded = [];

    public static function loadDotEnv(string $path): void
    {
        if (isset(self::$loaded[$path]) || !is_readable($path)) {
            return;
        }
        // A local file is only considered when the operator explicitly starts
        // PHP with APP_ENV=local. It can never silently override production.
        if (basename($path) === '.env' && self::env('APP_ENV') === 'local') {
            $local = dirname($path) . '/.env.local';
            if (is_readable($local)) {
                self::loadDotEnv($local);
            }
        }
        self::$loaded[$path] = true;
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if ($value !== '' && (($value[0] ?? '') === '"' || ($value[0] ?? '') === "'")) {
                $value = trim($value, "\"'");
            }
            if ($name !== '' && getenv($name) === false) {
                putenv($name . '=' . $value);
            }
        }
    }

    public static function env(string $name, string $default = ''): string
    {
        $value = getenv($name);
        return $value === false ? $default : trim($value);
    }

    public static function isProduction(): bool
    {
        return self::env('APP_ENV', 'development') === 'production';
    }
}
