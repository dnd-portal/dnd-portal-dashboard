<?php

declare(strict_types=1);

namespace DndPortal\Domain;

use DndPortal\Http\Response;
use PDO;

final class DashboardService {
    public function __construct(private PDO $db) {}

    public function get(int $uid): never {
        $q = $this->db->prepare(
            <<<'SQL'
            SELECT
            s.id,
            s.title,
            s.session_date,
            s.starts_at,
            c.name campaign_name,
            (
            SELECT
            COUNT(*)
            FROM
            session_participants sp
            WHERE
            sp.session_id = s.id
            ) participants,
            (
            SELECT
            COUNT(*)
            FROM
            session_participants sp
            WHERE
            sp.session_id = s.id AND sp.rsvp_status = "accepted"
            ) rsvp_accepted
            FROM
            game_sessions s
            JOIN campaigns c ON
            c.id = s.campaign_id
            JOIN campaign_memberships m ON
            m.campaign_id = c.id AND m.user_id = ? AND m.left_at IS NULL
            WHERE
            s.status = "planned" AND c.archived_at IS NULL AND s.session_date >= UTC_DATE()
            ORDER BY
            s.session_date, s.starts_at
            LIMIT 1
            SQL
            ,
        );
        $q->execute([$uid]);
        $next = $q->fetch() ?: null;
        $q = $this->db->prepare(
            <<<'SQL'
            SELECT
            COUNT(*)
            FROM
            polls p
            JOIN poll_participants pp ON
            pp.poll_id = p.id AND pp.user_id = ?
            WHERE
            p.status = "open" AND(
            p.closes_at IS NULL OR p.closes_at > UTC_TIMESTAMP()) AND NOT EXISTS(
            SELECT
            1
            FROM
            poll_votes v
            WHERE
            v.poll_id = p.id AND v.user_id = ?
            )
            SQL
            ,
        );
        $q->execute([$uid, $uid]);
        $openPolls = (int) $q->fetchColumn();
        $q = $this->db->prepare(
            <<<'SQL'
            SELECT
            o.currency,
            SUM(
            o.amount_cents - COALESCE(
            (
            SELECT
            SUM(
            CASE WHEN e.entry_kind = "payment" THEN e.amount_cents WHEN e.entry_kind = "reversal" THEN - e.amount_cents ELSE 0
            END
            )
            FROM
            payment_entries e
            WHERE
            e.obligation_id = o.id AND e.status = "confirmed"
            ),
            0
            )
            ) outstanding_cents
            FROM
            payment_obligations o
            WHERE
            o.user_id = ? AND o.status = "open"
            GROUP BY
            o.currency
            SQL
            ,
        );
        $q->execute([$uid]);
        $balances = [];
        foreach ($q->fetchAll() as $row) {
            $balances[$row['currency']] = (int) $row['outstanding_cents'];
        }
        $data = [
            'next_session' => $next,
            'open_polls' => $openPolls,
            'open_contributions_cents' => $balances['EUR'] ?? 0,
            'open_contributions_by_currency' => $balances,
        ];
        if ($this->isAdmin($uid)) {
            $data['admin'] = $this->adminTotals();
        }
        Response::json(['data' => $data]);
    }

    private function isAdmin(int $uid): bool {
        $q = $this->db->prepare(
            <<<'SQL'
            SELECT
            1
            FROM
            user_roles ur
            JOIN roles r ON
            r.id = ur.role_id
            WHERE
            ur.user_id = ? AND r.role_key = 'portal_admin'
            SQL
            ,
        );
        $q->execute([$uid]);
        return (bool) $q->fetchColumn();
    }

    private function adminTotals(): array {
        $q = $this->db->query(
            <<<'SQL'
            SELECT
            COUNT(*)
            FROM
            users
            SQL
            ,
        );
        $users = (int) $q->fetchColumn();
        $q = $this->db->query(
            <<<'SQL'
            SELECT
            COUNT(*)
            FROM
            campaigns
            WHERE
            archived_at IS NULL
            SQL
            ,
        );
        $campaigns = (int) $q->fetchColumn();
        $q = $this->db->query(
            'SELECT COUNT(*) FROM game_sessions WHERE status="planned" AND session_date>=UTC_DATE()',
        );
        $sessions = (int) $q->fetchColumn();
        $q = $this->db->query(
            'SELECT COUNT(*) FROM polls WHERE status="open" AND (closes_at IS NULL OR closes_at>UTC_TIMESTAMP())',
        );
        $polls = (int) $q->fetchColumn();
        $q = $this->db->query(
            <<<'SQL'
            SELECT
            COUNT(*)
            FROM
            payment_obligations o
            WHERE
            o.status = "open" AND o.amount_cents > COALESCE(
            (
            SELECT
            SUM(
            CASE WHEN e.entry_kind = "payment" THEN e.amount_cents WHEN e.entry_kind = "reversal" THEN - e.amount_cents ELSE 0
            END
            )
            FROM
            payment_entries e
            WHERE
            e.obligation_id = o.id AND e.status = "confirmed"
            ),
            0
            )
            SQL
            ,
        );
        $payments = (int) $q->fetchColumn();
        $q = $this->db->query(
            <<<'SQL'
            SELECT
            a.id,
            a.event_type,
            a.occurred_at,
            u.display_name actor_name
            FROM
            audit_events a
            LEFT JOIN users u ON
            u.id = a.actor_user_id
            ORDER BY
            a.occurred_at
            DESC
            LIMIT 5
            SQL
            ,
        );
        $activity = array_map(
            static fn(array $row): array => [
                'id' => (int) $row['id'],
                'event_type' => $row['event_type'],
                'occurred_at' => $row['occurred_at'],
                'actor_name' => $row['actor_name'],
            ],
            $q->fetchAll(),
        );
        return compact('users', 'campaigns', 'sessions', 'polls', 'payments', 'activity');
    }
}
