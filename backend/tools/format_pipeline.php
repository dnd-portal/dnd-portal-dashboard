<?php
declare(strict_types=1);

/**
 * Safe SQL-only pass after Prettier.
 * PHP layout is owned by Prettier; token_get_all is used only to identify
 * static PHP string literals without changing PHP syntax or control flow.
 */
$check = in_array('--check', $argv, true);
$requestedFile = null;
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--file=')) {
        $requestedFile = realpath(substr($argument, 7));
    }
}
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
if ($requestedFile !== null) {
    $files = array_values(
        array_filter($files, static fn(string $file): bool => realpath($file) === $requestedFile),
    );
}

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
function formatSqlLiterals(string $source, string $file): string {
    $tokens = token_get_all($source);
    $lines = explode("\n", $source);
    $output = '';
    for ($index = 0, $count = count($tokens); $index < $count; $index++) {
        $token = $tokens[$index];
        if (is_array($token) && $token[0] === T_START_HEREDOC) {
            $bodyToken = $tokens[$index + 1] ?? null;
            $endToken = $tokens[$index + 2] ?? null;
            if (
                is_array($bodyToken) &&
                $bodyToken[0] === T_ENCAPSED_AND_WHITESPACE &&
                is_array($endToken) &&
                $endToken[0] === T_END_HEREDOC
            ) {
                $output .= $token[1];
                $output .= formatSqlNowdocBody($bodyToken[1], $file, $token[2]);
                $output .= $endToken[1];
                $index += 2;
                continue;
            }
        }
        $text = is_array($token) ? $token[1] : $token;
        if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
            $line = $token[2];
            $indent = preg_match('/^\\s*/', $lines[$line - 1] ?? '', $match) ? $match[0] : '';
            $text = formatSqlLiteral($text, $file, $line, $indent);
        }
        $output .= $text;
    }
    return $output;
}

function formatSqlNowdocBody(string $body, string $file, int $line): string {
    $sql = trim($body);
    if (!preg_match('/^(SELECT|INSERT|UPDATE|DELETE)\b/i', $sql)) {
        return $body;
    }
    preg_match('/(?:^|\n)([ \t]*)\S/', $body, $indentMatch);
    $indent = $indentMatch[1] ?? '';
    try {
        $formatted = formatWithSqlFormatter($sql);
        if (sqlTokenSignature($sql) !== sqlTokenSignature($formatted)) {
            fwrite(
                STDERR,
                sprintf(
                    "FORMAT NOTE %s:%d: SQL token preservation failed; nowdoc unchanged\n",
                    $file,
                    $line,
                ),
            );
            return $body;
        }
        return implode(
            "\n",
            array_map(
                static fn(string $formattedLine): string => $indent . $formattedLine,
                explode("\n", trim($formatted)),
            ),
        ) . "\n";
    } catch (Throwable $exception) {
        fwrite(
            STDERR,
            sprintf(
                "FORMAT NOTE %s:%d: SQL parser skipped nowdoc (%s)\n",
                $file,
                $line,
                $exception->getMessage(),
            ),
        );
        return $body;
    }
}

function formatSqlLiteral(string $literal, string $file, int $line, string $indent): string {
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
        $formatted = formatWithSqlFormatter($sql);
        if (sqlTokenSignature($sql) !== sqlTokenSignature($formatted)) {
            fwrite(
                STDERR,
                sprintf(
                    "FORMAT NOTE %s:%d: SQL token preservation failed; literal unchanged\n",
                    $file,
                    $line,
                ),
            );
            return $literal;
        }

        $formatted = $leading . trim($formatted) . $trailing;
        if (!str_contains($formatted, "\n")) {
            return $quote . $formatted . $quote;
        }
        if (preg_match('/(^|\n)SQL(?:\r?\n|$)/', $formatted)) {
            fwrite(
                STDERR,
                sprintf(
                    "FORMAT NOTE %s:%d: SQL delimiter occurs in literal; left unchanged\n",
                    $file,
                    $line,
                ),
            );
            return $literal;
        }

        $body = implode(
            "\n",
            array_map(
                static fn(string $line): string => $indent . $line,
                explode("\n", trim($formatted)),
            ),
        );
        return "<<<'SQL'\n" . $body . "\n" . $indent . 'SQL';
    } catch (Throwable $exception) {
        fwrite(
            STDERR,
            sprintf(
                "FORMAT NOTE %s:%d: SQL parser skipped query (%s)\n",
                $file,
                $line,
                $exception->getMessage(),
            ),
        );
        return $literal;
    }
}

function formatWithSqlFormatter(string $sql): string {
    $command =
        PHP_OS_FAMILY === 'Windows' ? 'node tools/format_sql.mjs' : 'node tools/format_sql.mjs';
    $process = proc_open(
        $command,
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        dirname(__DIR__),
    );
    if (!is_resource($process)) {
        throw new RuntimeException('SQL formatter process could not be started');
    }
    fwrite($pipes[0], json_encode($sql, JSON_THROW_ON_ERROR));
    fclose($pipes[0]);
    $result = json_decode(stream_get_contents($pipes[1]), true, 512, JSON_THROW_ON_ERROR);
    fclose($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    if ($exitCode !== 0 || isset($result['error'])) {
        throw new RuntimeException($result['error'] ?? trim($error) ?: 'SQL formatter failed');
    }
    return (string) $result['sql'];
}

function sqlTokenSignature(string $sql): array {
    $tokens = PhpMyAdmin\SqlParser\Lexer::getTokens($sql)->tokens;
    $signature = [];
    foreach ($tokens as $token) {
        if ($token->type === PhpMyAdmin\SqlParser\Token::TYPE_WHITESPACE) {
            continue;
        }
        $value = in_array(
            $token->type,
            [PhpMyAdmin\SqlParser\Token::TYPE_KEYWORD, PhpMyAdmin\SqlParser\Token::TYPE_BOOL],
            true,
        )
            ? strtolower($token->value)
            : $token->value;
        $signature[] = $value;
    }

    return $signature;
}
