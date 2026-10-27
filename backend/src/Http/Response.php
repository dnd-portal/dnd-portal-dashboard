<?php

declare(strict_types=1);

namespace DndPortal\Http;

final class Response {
    public static function json(array $data, int $status = 200, array $headers = []): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        foreach ($headers as $key => $value) {
            header($key . ': ' . $value);
        }
        echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        exit();
    }

    public static function error(
        string $code,
        string $message,
        int $status,
        array $details = [],
    ): never {
        self::json(
            ['error' => ['code' => $code, 'message' => $message, 'details' => $details]],
            $status,
        );
    }
}
