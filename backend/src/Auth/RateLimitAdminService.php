<?php

declare(strict_types=1);

namespace DndPortal\Auth;

use DndPortal\Http\Response;
use PDO;

final class RateLimitAdminService {
    public function __construct(private PDO $db) {}

    public function listActive(): void {
        $q = $this->db->query(
            <<<'SQL'
            SELECT
                bucket_type,
                request_count,
                blocked_until,
                updated_at
            FROM rate_limit_buckets
            WHERE
                blocked_until IS NOT NULL
                AND blocked_until > UTC_TIMESTAMP(6)
            ORDER BY blocked_until ASC
            SQL
            ,
        );
        Response::json(['data' => $q->fetchAll()]);
    }

    public function reset(string $type, string $key, int $adminId): void {
        if (!in_array($type, ['login'], true) || trim($key) === '') {
            Response::error('validation_failed', 'A supported rate-limit type and key are required.', 422);
        }
        $q = $this->db->prepare(
            'DELETE FROM rate_limit_buckets WHERE bucket_type = ? AND bucket_key_hash = ?',
        );
        $q->execute([$type, RateLimiter::hashKey($type, trim($key))]);
        $deleted = $q->rowCount();
        if ($deleted === 0) {
            Response::json(['data' => ['reset' => false, 'deleted' => 0]]);
        }
        $audit = $this->db->prepare(
            <<<'SQL'
            INSERT INTO audit_events (
                actor_user_id,
                event_type,
                entity_type,
                payload_json
            )
            VALUES (?, 'rate_limit.reset', 'rate_limit_bucket', ?)
            SQL
            ,
        );
        $audit->execute([$adminId, json_encode(['bucket_type' => $type, 'deleted' => $deleted], JSON_THROW_ON_ERROR)]);
        Response::json(['data' => ['reset' => true, 'deleted' => $deleted]]);
    }

    public function resetAll(int $adminId): void {
        $q = $this->db->query(
            'DELETE FROM rate_limit_buckets WHERE blocked_until IS NOT NULL AND blocked_until > UTC_TIMESTAMP(6)',
        );
        $deleted = $q->rowCount();
        if ($deleted > 0) {
            $audit = $this->db->prepare(
                <<<'SQL'
                INSERT INTO audit_events (
                    actor_user_id,
                    event_type,
                    entity_type,
                    payload_json
                )
                VALUES (?, 'rate_limit.reset_all', 'rate_limit_bucket', ?)
                SQL
                ,
            );
            $audit->execute([$adminId, json_encode(['deleted' => $deleted], JSON_THROW_ON_ERROR)]);
        }
        Response::json(['data' => ['reset' => $deleted > 0, 'deleted' => $deleted]]);
    }
}
