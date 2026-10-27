<?php

declare(strict_types=1);

namespace DndPortal\Auth;

use DndPortal\Config;
use PDO;
use DateTimeImmutable;
use DateTimeZone;

final class SessionStore {
    public function __construct(private PDO $db) {}

    public function create(int $userId): string {
        $raw = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $expires = $now->modify(
            '+' . (int) Config::env('AUTH_ABSOLUTE_TTL', '2592000') . ' seconds',
        );
        $stmt = $this->db->prepare(
            <<<'SQL'
            INSERT INTO auth_sessions(
            session_id_hash,
            user_id,
            session_data,
            created_at,
            last_seen_at,
            expires_at
            )
            VALUES(?, ?, ?, ?, ?, ?)
            SQL
            ,
        );
        $stmt->execute([
            hash('sha256', $raw, true),
            $userId,
            '',
            $now->format('Y-m-d H:i:s.u'),
            $now->format('Y-m-d H:i:s.u'),
            $expires->format('Y-m-d H:i:s.u'),
        ]);
        return $raw;
    }

    public function userId(string $raw): ?int {
        if ($raw === '') {
            return null;
        }
        $stmt = $this->db->prepare(
            <<<'SQL'
            SELECT
            s.user_id
            FROM
            auth_sessions s
            JOIN users u ON
            u.id = s.user_id
            WHERE
            s.session_id_hash = ? AND s.revoked_at IS NULL AND s.expires_at > ? AND s.last_seen_at > ? AND u.account_status = "active"
            SQL
            ,
        );
        $stmt->execute([
            hash('sha256', $raw, true),
            gmdate('Y-m-d H:i:s.u'),
            gmdate('Y-m-d H:i:s.u', time() - (int) Config::env('AUTH_IDLE_TTL', '7200')),
        ]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function revoke(string $raw): void {
        $stmt = $this->db->prepare(
            <<<'SQL'
            UPDATE
            auth_sessions
            SET
            revoked_at = ?
            WHERE
            session_id_hash = ? AND revoked_at IS NULL
            SQL
            ,
        );
        $stmt->execute([gmdate('Y-m-d H:i:s.u'), hash('sha256', $raw, true)]);
    }

    public function revokeUser(int $userId): void {
        $stmt = $this->db->prepare(
            <<<'SQL'
            UPDATE
            auth_sessions
            SET
            revoked_at = ?
            WHERE
            user_id = ? AND revoked_at IS NULL
            SQL
            ,
        );
        $stmt->execute([gmdate('Y-m-d H:i:s.u'), $userId]);
    }
}
