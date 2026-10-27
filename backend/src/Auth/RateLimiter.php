<?php

declare(strict_types=1);

namespace DndPortal\Auth;

use PDO;
use DndPortal\Http\Response;

final class RateLimiter {
    public function __construct(private PDO $db) {}

    public function check(string $type, string $key, int $limit, int $window = 900): void {
        $hash = hash('sha256', $type . '|' . $key, true);
        $now = time();
        $q = $this->db->prepare(
            <<<'SQL'
            SELECT
            window_started_at,
            request_count,
            blocked_until
            FROM
            rate_limit_buckets
            WHERE
            bucket_key_hash = ? AND bucket_type = ?
            SQL
            ,
        );
        $q->execute([$hash, $type]);
        $row = $q->fetch();
        if ($row && $row['blocked_until'] && strtotime($row['blocked_until']) > $now) {
            Response::error('rate_limited', 'Too many requests. Try again later.', 429);
        }
        $start = $row ? strtotime($row['window_started_at']) : 0;
        $count = $row && $now - $start < $window ? (int) $row['request_count'] + 1 : 1;
        $windowStart =
            $row && $now - $start < $window ? $row['window_started_at'] : gmdate('Y-m-d H:i:s');
        $blocked = $count > $limit ? gmdate('Y-m-d H:i:s', $now + $window) : null;
        $q = $this->db->prepare(
            <<<'SQL'
            INSERT INTO rate_limit_buckets(
            bucket_key_hash,
            bucket_type,
            window_started_at,
            request_count,
            blocked_until
            )
            VALUES(?, ?, ?, ?, ?)
            ON DUPLICATE KEY
            UPDATE
            window_started_at =
            VALUES(window_started_at), request_count =
            VALUES(request_count), blocked_until =
            VALUES(blocked_until)
            SQL
            ,
        );
        $q->execute([$hash, $type, $windowStart, $count, $blocked]);
        if ($blocked) {
            Response::error('rate_limited', 'Too many requests. Try again later.', 429);
        }
    }
}
