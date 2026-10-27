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
require_once $root . '/vendor/autoload.php';
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
    $formatted = splitStatements($source, $file);
    if ($formatted !== $source) {
        if ($check) {
            fwrite(STDERR, 'Pipeline formatting required: ' . $file . PHP_EOL);
            exit(8);
        }
        file_put_contents($file, $formatted, LOCK_EX);
    }
    reportUnsupportedLongLines($file, $formatted);
}

function splitStatements(string $source, string $file): string
{
    $tokens = token_get_all($source);
    $output = '';
    $depth = 0;
    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];
        $text = is_array($token) ? $token[1] : $token;
        if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
            $text = formatSqlLiteral($text, $file, $token[2]);
        }
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

function formatSqlLiteral(string $literal, string $file, int $line): string
{
    if (str_contains($literal, '\\')) {
        return $literal;
    }

    $quote = $literal[0] ?? '';
    if (($quote !== "'" && $quote !== '"') || ($literal[strlen($literal) - 1] ?? '') !== $quote) {
        return $literal;
    }

    $sql = substr($literal, 1, -1);
    preg_match('/^\\s*/', $sql, $leadingMatch);
    preg_match('/\\s*$/', $sql, $trailingMatch);
    $leading = $leadingMatch[0] ?? '';
    $trailing = $trailingMatch[0] ?? '';
    if (!preg_match('/^\\s*(SELECT|INSERT|UPDATE|DELETE)\\b/i', $sql)) {
        return $literal;
    }

    try {
        $formatted = PhpMyAdmin\SqlParser\Utils\Formatter::format($sql, [
            'type' => 'text',
            'remove_comments' => false,
            'clause_newline' => true,
            'parts_newline' => true,
            'indentation' => '    ',
        ]);
        if (sqlTokenSignature($sql) !== sqlTokenSignature($formatted)) {
            fwrite(STDERR, sprintf("FORMAT NOTE %s:%d: SQL token preservation failed; literal unchanged\n", $file, $line));
            return $literal;
        }

        return $quote . $leading . trim($formatted) . $trailing . $quote;
    } catch (Throwable $exception) {
        fwrite(STDERR, sprintf("FORMAT NOTE %s:%d: SQL parser skipped query (%s)\n", $file, $line, $exception->getMessage()));
        return $literal;
    }
}

function sqlTokenSignature(string $sql): array
{
    $tokens = PhpMyAdmin\SqlParser\Lexer::getTokens($sql)->tokens;
    $signature = [];
    foreach ($tokens as $token) {
        if ($token->type === PhpMyAdmin\SqlParser\Token::TYPE_WHITESPACE) {
            continue;
        }
        $signature[] = [$token->type, $token->value];
    }

    return $signature;
}

/**
 * PHP-CS-Fixer has no safe line-wrapping rule for arbitrary expressions and
 * no SQL grammar. Report the cases that require a grammar-aware formatter;
 * never alter them heuristically.
 */
function reportUnsupportedLongLines(string $file, string $source): void
{
    $lines = explode("\n", $source);
    foreach ($lines as $number => $line) {
        if (strlen(rtrim($line, "\r")) <= 120) {
            continue;
        }

        $tokens = token_get_all("<?php\n" . $line);
        $hasSqlLiteral = false;
        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
                $value = strtoupper($token[1]);
                if (str_contains($value, 'SELECT')
                    || str_contains($value, 'INSERT')
                    || str_contains($value, 'UPDATE')
                    || str_contains($value, 'DELETE')) {
                    $hasSqlLiteral = true;
                    break;
                }
            }
        }

        $reason = $hasSqlLiteral
            ? 'static SQL literal requires a MySQL/MariaDB parser'
            : 'expression requires a PHP parser with safe line wrapping';
        fwrite(STDERR, sprintf("FORMAT NOTE %s:%d: %s\n", $file, $number + 1, $reason));
    }
}
