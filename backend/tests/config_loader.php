<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Config.php';

use DndPortal\Config;

$path = tempnam(sys_get_temp_dir(), 'dnd-config-');
if ($path === false) {
    throw new RuntimeException('Unable to create temporary config fixture.');
}

file_put_contents($path, "FILE_ONLY=from-file\nWITH_EQUALS=left=right\nQUOTED=\"quoted # value\" # comment\n");
Config::loadDotEnv($path);

if (Config::env('FILE_ONLY') !== 'from-file') {
    throw new RuntimeException('Parsed file value was not retained.');
}
if (Config::env('WITH_EQUALS') !== 'left=right') {
    throw new RuntimeException('Values containing equals signs were not retained.');
}
if (Config::env('QUOTED') !== 'quoted # value') {
    throw new RuntimeException('Quoted values were not parsed correctly.');
}
if (Config::env('MISSING', 'fallback') !== 'fallback') {
    throw new RuntimeException('Missing values did not use the default.');
}

unlink($path);
echo "Config loader checks passed.\n";
