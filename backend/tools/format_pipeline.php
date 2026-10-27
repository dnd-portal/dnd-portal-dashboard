<?php
declare(strict_types=1);

/**
 * Safe SQL-only pass after Prettier.
 * PHP layout is owned by Prettier; token_get_all is used only to identify
 * static PHP string literals without changing PHP syntax or control flow.
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
    $formatted = formatSqlLiterals($source, $file);
    if ($formatted !== $source) {
        if ($check) {
            fwrite(STDERR, 'Pipeline formatting required: ' . $file . PHP_EOL);
            exit(8);
        }
        file_put_contents($file, $formatted, LOCK_EX);
    }
}
function formatSqlLiterals(string $source, string $file): string
{
    $tokens = token_get_all($source);
    $output = '';
    foreach ($tokens as $token) {
        $text = is_array($token) ? $token[1] : $token;
        if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
            $text = formatSqlLiteral($text, $file, $token[2]);
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
