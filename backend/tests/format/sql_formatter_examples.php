<?php

declare(strict_types=1);

// Static fixtures consumed by tools/format_pipeline.php; this file is not application code.
$select = <<<'SQL'
SELECT
    s.user_id
FROM auth_sessions s
JOIN users u ON u.id = s.user_id
WHERE
    s.session_id_hash = ?
    AND s.revoked_at IS NULL
    AND s.expires_at > ?
    AND s.last_seen_at > ?
    AND u.account_status = "active"
SQL;
$upsert = <<<'SQL'
INSERT INTO rate_limit_buckets (
    bucket_key_hash,
    bucket_type,
    window_started_at,
    request_count,
    blocked_until
)
VALUES
    (?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
    window_started_at = VALUES(window_started_at),
    request_count = VALUES(request_count),
    blocked_until = VALUES(blocked_until)
SQL;
