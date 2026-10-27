<?php
declare(strict_types=1);

/**
 * Small deterministic token pass after PHP-CS-Fixer.
 * It never pattern-matches PHP source: PHP is read with token_get_all.
 * SQL is intentionally authored as readable nowdocs/strings; this pass does
 * not rewrite SQL literals because changing their contents is not safe.
 */
$check = in_array('--check', $argv, true);
$root = dirname(__DIR__);
$files = [];
foreach (['src', 'public', 'scripts', 'tests'] as $dir) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
}
if (is_file($root . '/router.php')) {
    $files[] = $root . '/router.php';
}
sort($files);

foreach ($files as $file) {
    $source = file_get_contents($file);
    if ($source === false) {
        throw new RuntimeException('Cannot read ' . $file);
    }
    $formatted = splitStatements($source);
    if ($formatted !== $source) {
        if ($check) {
            fwrite(STDERR, 'Pipeline formatting required: ' . $file . PHP_EOL);
            exit(8);
        }
        file_put_contents($file, $formatted, LOCK_EX);
    }
}

function splitStatements(string $source): string
{
    $tokens = token_get_all($source);
    $output = '';
    $depth = 0;
    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];
        $text = is_array($token) ? $token[1] : $token;
        if ($text === '}') {
            $depth = max(0, $depth - 1);
            $output .= $text;
            $next = $i + 1;
            if ($next < $count && is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE
                && !str_contains($tokens[$next][1], "\n")) {
                $next++;
            }
            $nextText = $next < $count ? (is_array($tokens[$next]) ? $tokens[$next][1] : $tokens[$next]) : '';
            $nextId = $next < $count && is_array($tokens[$next]) ? $tokens[$next][0] : null;
            if ($nextText !== '' && !in_array($nextId, [T_ELSE, T_ELSEIF, T_CATCH, T_FINALLY, T_WHILE], true)
                && !in_array($nextText, [';', ',', ')', ']', '}'], true)
                && ($i + 1 >= $count || !is_array($tokens[$i + 1]) || !str_contains($tokens[$i + 1][1], "\n"))) {
                $output .= "\n" . str_repeat(' ', $depth * 4);
            }
            continue;
        }
        if ($text === '{') {
            $depth++;
        }
        $output .= $text;
    }
    return $output;
}
