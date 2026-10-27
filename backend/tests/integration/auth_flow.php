<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';

use DndPortal\Config;
use DndPortal\Database\Connection;

Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
if (
    Config::env('APP_ENV') !== 'testing' ||
    !preg_match(
        '/^(dnd_portal_test|dnd_portal_test_run_[0-9]+|test_[a-z0-9_]+)$/',
        Config::env('DB_NAME'),
    )
) {
    fwrite(STDERR, "REFUSED: set APP_ENV=testing and DB_NAME=dnd_portal_test (or test_*).\n");
    exit(2);
}
try {
    $db = Connection::make();
    $db->query(
        <<<'SQL'
        SELECT
            1
        SQL
        ,
    )->fetchColumn();
} catch (Throwable $e) {
    fwrite(STDERR, 'BLOCKED: test database unavailable: ' . $e->getMessage() . "\n");
    exit(3);
}

$prefix = 'it_' . bin2hex(random_bytes(4));
$checks = 0;
$ok = static function (bool $condition, string $name) use (&$checks): void {
    $checks++;
    if (!$condition) {
        throw new RuntimeException("FAIL: $name");
    }
    echo "PASS: $name\n";
};
$db->beginTransaction();
try {
    $q = $db->prepare(
        <<<'SQL'
        INSERT INTO users (display_name, email, username, account_status)
        VALUES
            (?, ?, ?, "invited")
        SQL
        ,
    );
    $q->execute([$prefix, "$prefix@example.test", $prefix]);
    $uid = (int) $db->lastInsertId();
    $q = $db->prepare(
        <<<'SQL'
        INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
        VALUES
            (?, ?, ?)
        SQL
        ,
    );
    $token = random_bytes(32);
    $q->execute([$uid, hash('sha256', $token, true), gmdate('Y-m-d H:i:s.u', time() + 1800)]);
    $tokenId = (int) $db->lastInsertId();
    $ok(
        password_verify(
            'password-never-stored',
            password_hash('password-never-stored', PASSWORD_DEFAULT),
        ),
        'password hash verify',
    );
    $q = $db->prepare(
        <<<'SQL'
        SELECT
            id
        FROM password_reset_tokens
        WHERE
            id = ?
            AND used_at IS NULL
            AND expires_at > ?
        SQL
        ,
    );
    $q->execute([$tokenId, gmdate('Y-m-d H:i:s.u')]);
    $ok((int) $q->fetchColumn() === $tokenId, 'unused reset token is valid');
    $q = $db->prepare(
        <<<'SQL'
        UPDATE password_reset_tokens
        SET
            used_at = ?
        WHERE
            id = ?
            AND used_at IS NULL
        SQL
        ,
    );
    $q->execute([gmdate('Y-m-d H:i:s.u'), $tokenId]);
    $ok($q->rowCount() === 1, 'reset token consumed once');
    $q->execute([gmdate('Y-m-d H:i:s.u'), $tokenId]);
    $ok($q->rowCount() === 0, 'reset token cannot be consumed twice');
    $db->rollBack();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    throw $e;
}
echo "PASS: $checks checks\n";
