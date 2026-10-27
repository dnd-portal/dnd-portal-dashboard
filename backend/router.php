<?php

declare(strict_types=1);

// PHP built-in server router: static files are served normally; API traffic reaches the front controller.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (str_starts_with($path, '/api/')) {
    require __DIR__ . '/public/index.php';
    return true;
}
return false;
