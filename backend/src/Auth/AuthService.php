<?php

declare(strict_types=1);

namespace DndPortal\Auth;

use DndPortal\Config;
use DndPortal\Http\Response;
use PDO;

final class AuthService {
    public function __construct(private PDO $db, private SessionStore $sessions) {}

    public function csrf(): never {
        $cookie = $_COOKIE[Config::env('AUTH_COOKIE', 'dnd_session')] ?? '';
        if ($cookie === '') {
            $cookie = Security::code() . Security::code();
            Security::cookie($cookie, (int) Config::env('AUTH_ABSOLUTE_TTL', '2592000'));
        }
        Response::json(['data' => ['token' => hash('sha256', $cookie)]]);
    }

    public function login(array $in): never {
        $identifier = strtolower(trim((string) ($in['identifier'] ?? '')));
        $password = (string) ($in['password'] ?? '');
        if ($identifier === '' || $password === '') {
            Response::error('invalid_credentials', 'Invalid identifier or password.', 401);
        }
        $stmt = $this->db->prepare(
            <<<'SQL'
            SELECT
                u.id,
                u.display_name,
                u.email,
                u.username,
                u.account_status,
                c.password_hash,
                c.password_algo
            FROM users u
                LEFT JOIN user_credentials c ON c.user_id = u.id
            WHERE
                (
                    u.email = ?
                    OR u.username = ?
                )
            LIMIT
                2
            SQL
            ,
        );
        $stmt->execute([$identifier, $identifier]);
        $rows = $stmt->fetchAll();
        if (
            count($rows) !== 1 ||
            $rows[0]['account_status'] !== 'active' ||
            !$rows[0]['password_hash'] ||
            !password_verify($password, $rows[0]['password_hash'])
        ) {
            Response::error('invalid_credentials', 'Invalid identifier or password.', 401);
        }
        $row = $rows[0];
        if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
            $h = password_hash($password, PASSWORD_DEFAULT);
            $q = $this->db->prepare(
                <<<'SQL'
                UPDATE user_credentials
                SET
                    password_hash = ?,
                    password_algo = ?,
                    password_changed_at = ?
                WHERE
                    user_id = ?
                SQL
                ,
            );
            $q->execute([$h, PASSWORD_DEFAULT, gmdate('Y-m-d H:i:s.u'), $row['id']]);
        }
        $this->sessions->revokeUser((int) $row['id']);
        $raw = $this->sessions->create((int) $row['id']);
        Security::cookie($raw, (int) Config::env('AUTH_ABSOLUTE_TTL', '2592000'));
        $this->audit((int) $row['id'], 'auth.login');
        Response::json(['data' => $this->profile((int) $row['id'])]);
    }

    public function me(): never {
        $id = $this->currentId();
        Response::json(['data' => $this->profile($id)]);
    }

    public function logout(): never {
        $raw = $_COOKIE[Config::env('AUTH_COOKIE', 'dnd_session')] ?? '';
        if ($raw !== '') {
            $this->sessions->revoke($raw);
        }
        Security::clearCookie();
        Response::json(['data' => null]);
    }

    public function currentId(): int {
        $raw = $_COOKIE[Config::env('AUTH_COOKIE', 'dnd_session')] ?? '';
        $id = $this->sessions->userId($raw);
        if ($id === null) {
            Response::error('unauthorized', 'Authentication required.', 401);
        }
        return $id;
    }

    public function profile(int $id): array {
        $q = $this->db->prepare(
            <<<'SQL'
            SELECT
                id,
                display_name,
                email,
                username,
                account_status,
                version
            FROM users
            WHERE
                id = ?
            SQL
            ,
        );
        $q->execute([$id]);
        $p = $q->fetch();
        if (!$p) {
            Response::error('unauthorized', 'Authentication required.', 401);
        }
        $r = $this->db->prepare(
            <<<'SQL'
            SELECT
                r.role_key
            FROM roles r
            JOIN user_roles ur ON ur.role_id = r.id
            WHERE
                ur.user_id = ?
            SQL
            ,
        );
        $r->execute([$id]);
        $p['roles'] = array_column($r->fetchAll(), 'role_key');
        return $p;
    }

    public function requireRole(string ...$roles): int {
        $id = $this->currentId();
        $p = $this->profile($id);
        if (!array_intersect($roles, $p['roles'])) {
            Response::error('forbidden', 'You do not have permission for this action.', 403);
        }
        return $id;
    }

    public function invite(int $admin, array $in): never {
        $target = isset($in['user_id']) ? (int) $in['user_id'] : null;
        $email = $this->norm($in['email'] ?? null);
        $code = Security::code() . Security::code();
        $h = hash('sha256', $code, true);
        $this->db->beginTransaction();
        try {
            if ($target !== null) {
                $q = $this->db->prepare('SELECT id, account_status FROM users WHERE id=? FOR UPDATE');
                $q->execute([$target]);
                $user = $q->fetch();
                if (!$user || $user['account_status'] !== 'invited') {
                    Response::error('validation_failed', 'Only a pending user can be invited.', 422);
                }
                $q = $this->db->prepare('SELECT id, expires_at FROM registration_invites WHERE invited_user_id=? AND consumed_at IS NULL AND revoked_at IS NULL AND expires_at>? LIMIT 1');
                $q->execute([$target, gmdate('Y-m-d H:i:s.u')]);
                if ($existing = $q->fetch()) {
                    Response::error('existing_invite', 'A valid invitation already exists for this user.', 409, ['id' => (int) $existing['id'], 'expires_at' => $existing['expires_at']]);
                }
            }
            if ($target === null && $email !== null) {
                $q = $this->db->prepare(
                    <<<'SQL'
                    SELECT id, account_status
                    FROM users
                    WHERE email = ?
                    FOR UPDATE
                    SQL
                    ,
                );
                $q->execute([$email]);
                $existing = $q->fetch();
                if ($existing) {
                    if ($existing['account_status'] !== 'invited') {
                        Response::error('validation_failed', 'Email is already in use.', 422);
                    }
                    $target = (int) $existing['id'];
                } else {
                    $q = $this->db->prepare(
                        <<<'SQL'
                        INSERT INTO users (display_name, email, account_status)
                        VALUES
                            (?, ?, "invited")
                        SQL
                        ,
                    );
                    $q->execute([$email, $email]);
                    $target = (int) $this->db->lastInsertId();
                }
            }
            $q = $this->db->prepare(
                <<<'SQL'
                UPDATE registration_invites
                SET
                    consumed_at = ?
                WHERE
                    invited_user_id = ?
                    AND consumed_at IS NULL
                    AND revoked_at IS NULL
                    AND expires_at > ?
                SQL
                ,
            );
            $q->execute([gmdate('Y-m-d H:i:s.u'), $target, gmdate('Y-m-d H:i:s.u')]);
            $q = $this->db->prepare(
                <<<'SQL'
                INSERT INTO registration_invites (
                    invited_user_id,
                    invited_email,
                    code_hash,
                    created_by,
                    expires_at
                )
                VALUES
                    (?, ?, ?, ?, ?)
                SQL
                ,
            );
            $q->execute([$target, $email, $h, $admin, gmdate('Y-m-d H:i:s.u', time() + 86400)]);
            $inviteId = (int) $this->db->lastInsertId();
            if ($this->db->inTransaction()) {
                $this->db->commit();
            }
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Response::error('server_error', 'Unable to issue invite.', 500);
        }
        Response::json(
            [
                'data' => [
                    'id' => $inviteId,
                    'user_id' => $target,
                    'code' => $code,
                    'expires_at' => gmdate('c', time() + 86400),
                ],
            ],
            201,
        );
    }

    public function createPending(int $admin, array $in): never {
        $name = trim((string) ($in['display_name'] ?? ''));
        $username = $this->norm($in['username'] ?? null);
        $role = (string) ($in['role'] ?? '');
        if ($name === '' || $username === null || !in_array($role, ['portal_admin', 'campaign_dm', 'campaign_member'], true)) Response::error('validation_failed', 'Display name, username and a valid role are required.', 422);
        $this->db->beginTransaction();
        try {
            $q=$this->db->prepare('SELECT id FROM users WHERE username=?');$q->execute([$username]);if($q->fetchColumn()!==false)Response::error('validation_failed','Username is already in use.',422);
            $q=$this->db->prepare('INSERT INTO users(display_name,username,account_status) VALUES(?,?,"invited")');
            $q->execute([$name,$username]);$id=(int)$this->db->lastInsertId();
            $q=$this->db->prepare('INSERT INTO user_roles(user_id,role_id) SELECT ?,id FROM roles WHERE role_key=?');$q->execute([$id,$role]);
            if($q->rowCount()!==1)throw new \RuntimeException('Role not found.');$this->audit($admin,'user.pending_created');$this->db->commit();
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();if($e instanceof \DndPortal\Http\Response)throw $e;Response::error('validation_failed','Pending user could not be created.',422);}
        Response::json(['data'=>$this->profile($id)],201);
    }

    public function listInvites(int $admin): never {
        $q=$this->db->query('SELECT i.id,i.invited_user_id,i.invited_email,i.created_at,i.expires_at,i.consumed_at,i.revoked_at,u.display_name,u.username FROM registration_invites i LEFT JOIN users u ON u.id=i.invited_user_id ORDER BY i.id DESC LIMIT 100');
        Response::json(['data'=>$q->fetchAll()]);
    }

    public function resetIssue(int $admin, array $in): never {
        $uid = (int) ($in['user_id'] ?? 0);
        $code = Security::code() . Security::code();
        $this->db->beginTransaction();
        try {
            $q = $this->db->prepare(
                <<<'SQL'
                UPDATE password_reset_tokens
                SET
                    used_at = ?
                WHERE
                    user_id = ?
                    AND used_at IS NULL
                    AND expires_at > ?
                SQL
                ,
            );
            $q->execute([gmdate('Y-m-d H:i:s.u'), $uid, gmdate('Y-m-d H:i:s.u')]);
            $q = $this->db->prepare(
                <<<'SQL'
                INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
                VALUES
                    (?, ?, ?)
                SQL
                ,
            );
            $q->execute([
                $uid,
                hash('sha256', $code, true),
                gmdate('Y-m-d H:i:s.u', time() + 1800),
            ]);
            $tokenId = (int) $this->db->lastInsertId();
            if ($this->db->inTransaction()) {
                $this->db->commit();
            }
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Response::error('server_error', 'Unable to issue reset code.', 500);
        }
        Response::json(
            [
                'data' => [
                    'id' => $tokenId,
                    'code' => $code,
                    'expires_at' => gmdate('c', time() + 1800),
                ],
            ],
            201,
        );
    }

    public function register(array $in): never {
        $code = (string) ($in['invite_code'] ?? '');
        $email = $this->norm($in['email'] ?? null);
        $username = $this->norm($in['username'] ?? null);
        $displayName = trim((string) ($in['display_name'] ?? ''));
        $password = (string) ($in['password'] ?? '');
        $confirm = (string) ($in['password_confirmation'] ?? '');
        if ($email === null || $username === null || strlen($password) < 8 || $password !== $confirm) {
            Response::error('validation_failed', 'Password confirmation failed.', 422);
        }
        $this->db->beginTransaction();
        try {
            $q = $this->db->prepare(
                <<<'SQL'
                SELECT *
                FROM registration_invites
                WHERE code_hash=?
                    AND consumed_at IS NULL
                    AND revoked_at IS NULL
                  AND expires_at>? FOR UPDATE
                SQL
                ,
            );
            $q->execute([hash('sha256', $code, true), gmdate('Y-m-d H:i:s.u')]);
            $invite = $q->fetch();
            if (!$invite) {
                Response::error('invalid_invite', 'Invite is invalid or expired.', 422);
            }
            $uid = $invite['invited_user_id'] === null ? null : (int) $invite['invited_user_id'];
            if ($uid === null) {
                $q = $this->db->prepare(
                    'INSERT INTO users (display_name, email, username, account_status) VALUES (?, ?, ?, "active")'
                );
                $q->execute([$displayName, $email, $username]);
                $uid = (int) $this->db->lastInsertId();
            } else {
                $q = $this->db->prepare('SELECT display_name, username FROM users WHERE id=? AND account_status="invited" FOR UPDATE');
                $q->execute([$uid]);
                $pending = $q->fetch();
                if (!$pending) Response::error('invalid_invite', 'Invite is no longer available.', 422);
                $q = $this->db->prepare(
                    'UPDATE users SET email = ?, account_status = "active", updated_at = ? WHERE id = ? AND account_status = "invited"'
                );
                $q->execute([$email, gmdate('Y-m-d H:i:s.u'), $uid]);
                if ($q->rowCount() !== 1) {
                    Response::error('invalid_invite', 'Invite is no longer available.', 422);
                }
            }
            $q = $this->db->prepare(
                <<<'SQL'
                INSERT INTO user_credentials (user_id, password_hash, password_algo)
                VALUES
                    (?, ?, ?)
                SQL
                ,
            );
            $q->execute([$uid, password_hash($password, PASSWORD_DEFAULT), PASSWORD_DEFAULT]);
            $q = $this->db->prepare(
                <<<'SQL'
                UPDATE registration_invites
                    SET
                    consumed_at = ?,
                    consumed_by_user_id = ?
                WHERE
                    id = ?
                SQL
                ,
            );
            $q->execute([gmdate('Y-m-d H:i:s.u'), $uid, $invite['id']]);
            $this->audit($uid, 'auth.register');
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Response::error('validation_failed', 'Registration could not be completed.', 422);
        }
        $this->sessions->revokeUser($uid);
        Security::cookie($this->sessions->create($uid), (int) Config::env('AUTH_ABSOLUTE_TTL', '2592000'));
        Response::json(['data' => $this->profile($uid), 'redirect' => '/dashboard/polls/current'], 201);
    }

    public function validateInvite(array $in): never {
        $code = (string) ($in['invite_code'] ?? '');
        $q = $this->db->prepare(
            <<<'SQL'
            SELECT
                id,
                invited_user_id,
                u.display_name,
                u.username
            FROM registration_invites
            LEFT JOIN users u ON u.id=registration_invites.invited_user_id
            WHERE
                code_hash = ?
                AND consumed_at IS NULL
                AND revoked_at IS NULL
                AND expires_at > ?
            SQL
            ,
        );
        $q->execute([hash('sha256', $code, true), gmdate('Y-m-d H:i:s.u')]);
        $invite = $q->fetch();
        if (!$invite) {
            Response::error('invalid_invite', 'Invite is invalid or expired.', 422);
        }
        Response::json(['data' => ['valid' => true, 'has_pending_user' => $invite['invited_user_id'] !== null, 'display_name' => $invite['display_name'], 'username' => $invite['username']]]);
    }

    public function validateReset(array $in): never {
        $code = (string) ($in['code'] ?? '');
        $identifier = $this->norm($in['identifier'] ?? null);
        $q = $this->db->prepare(
            <<<'SQL'
            SELECT
                u.id
            FROM password_reset_tokens t
            JOIN users u ON u.id = t.user_id
            WHERE
                t.token_hash = ?
                AND t.used_at IS NULL
                AND t.expires_at > ?
                AND (
                    u.email = ?
                    OR u.username = ?
                )
            SQL
            ,
        );
        $q->execute([
            hash('sha256', $code, true),
            gmdate('Y-m-d H:i:s.u'),
            $identifier,
            $identifier,
        ]);
        if (!$q->fetchColumn()) {
            Response::error('invalid_reset_code', 'Reset code is invalid or expired.', 422);
        }
        Response::json(['data' => ['valid' => true]]);
    }

    public function reset(array $in): never {
        $code = (string) ($in['code'] ?? '');
        $identifier = $this->norm($in['identifier'] ?? null);
        $password = (string) ($in['password'] ?? '');
        if (strlen($password) < 8 || $password !== ($in['password_confirmation'] ?? '')) {
            Response::error('validation_failed', 'Password confirmation failed.', 422);
        }
        $this->db->beginTransaction();
        try {
            $q = $this->db->prepare(
                <<<'SQL'
                SELECT
                t.*,
                u.email,
                u.username
                FROM
                password_reset_tokens t
                JOIN users u ON
                u.id = t.user_id
                WHERE
                t.token_hash = ? AND t.used_at IS NULL AND t.expires_at > ? AND(u.email = ? OR u.username = ?) FOR UPDATE
                SQL
                ,
            );
            $q->execute([
                hash('sha256', $code, true),
                gmdate('Y-m-d H:i:s.u'),
                $identifier,
                $identifier,
            ]);
            $t = $q->fetch();
            if (!$t) {
                Response::error('invalid_reset_code', 'Reset code is invalid or expired.', 422);
            }
            $q = $this->db->prepare(
                <<<'SQL'
                UPDATE user_credentials
                SET
                    password_hash = ?,
                    password_algo = ?,
                    password_changed_at = ?
                WHERE
                    user_id = ?
                SQL
                ,
            );
            $q->execute([
                password_hash($password, PASSWORD_DEFAULT),
                PASSWORD_DEFAULT,
                gmdate('Y-m-d H:i:s.u'),
                $t['user_id'],
            ]);
            $q = $this->db->prepare(
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
            $q->execute([gmdate('Y-m-d H:i:s.u'), $t['id']]);
            if ($q->rowCount() !== 1) {
                throw new \RuntimeException('already used');
            }
            $this->sessions->revokeUser((int) $t['user_id']);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Response::error('invalid_reset_code', 'Reset code is invalid or expired.', 422);
        }
        Response::json(['data' => null]);
    }

    public function getUser(int $id): never {
        $this->requireRole('portal_admin');
        Response::json(['data' => $this->profile($id)]);
    }

    public function listUsers(): never {
        $this->requireRole('portal_admin');
        $q = $this->db->query(
            <<<'SQL'
            SELECT
                id,
                display_name,
                email,
                username,
                account_status,
                version,
                created_at,
                updated_at
            FROM users
            ORDER BY
                id ASC
            LIMIT
                100
            SQL
            ,
        );
        $rows = $q->fetchAll();
        foreach ($rows as &$row) {
            $r = $this->db->prepare(
                <<<'SQL'
                SELECT
                    r.role_key
                FROM roles r
                JOIN user_roles ur ON ur.role_id = r.id
                WHERE
                    ur.user_id = ?
                SQL
                ,
            );
            $r->execute([$row['id']]);
            $row['roles'] = array_column($r->fetchAll(), 'role_key');
        }
        Response::json(['data' => $rows]);
    }

    public function updateUser(int $admin, int $id, array $in): never {
        $allowed = ['display_name', 'email', 'username', 'roles', 'version'];
        if (array_diff(array_keys($in), $allowed)) {
            Response::error('validation_failed', 'Unsupported fields.', 422);
        }
        $name = trim((string) ($in['display_name'] ?? ''));
        $email = $this->norm($in['email'] ?? null);
        $username = $this->norm($in['username'] ?? null);
        if ($name === '' || strlen($name) > 120) {
            Response::error('validation_failed', 'Invalid display name.', 422);
        }
        $this->db->beginTransaction();
        try {
            $q = $this->db->prepare(
                <<<'SQL'
                SELECT
                account_status,
                version
                FROM
                users
                WHERE
                id = ? FOR UPDATE
                SQL
                ,
            );
            $q->execute([$id]);
            $current = $q->fetch();
            if (!$current) {
                Response::error('not_found', 'User not found.', 404);
            }
            if (isset($in['version']) && (int) $in['version'] !== (int) $current['version']) {
                Response::error('conflict', 'User was changed by another administrator.', 409);
            }
            $roles = array_values(array_unique(array_map('strval', $in['roles'] ?? [])));
            $q = $this->db->prepare(
                <<<'SQL'
                SELECT
                    id,
                    role_key
                FROM roles
                WHERE
                    role_key IN ("portal_admin", "campaign_dm", "campaign_member")
                SQL
                ,
            );
            $q->execute();
            $roleRows = $q->fetchAll();
            $known = array_column($roleRows, 'role_key');
            if (array_diff($roles, $known)) {
                Response::error('validation_failed', 'Unsupported role.', 422);
            }
            if (!in_array('portal_admin', $roles, true)) {
                $q = $this->db->query(
                    <<<'SQL'
                    SELECT
                    id
                    FROM
                    users
                    WHERE
                    account_status = 'active'
                    ORDER BY
                    id FOR UPDATE
                    SQL
                    ,
                );
                $q->fetchAll();
                $q = $this->db->query(
                    <<<'SQL'
                    SELECT
                        COUNT(*)
                    FROM user_roles ur
                    JOIN roles r ON r.id = ur.role_id
                    JOIN users u ON u.id = ur.user_id
                    WHERE
                        r.role_key = 'portal_admin'
                        AND u.account_status = 'active'
                    SQL
                    ,
                );
                if ((int) $q->fetchColumn() <= 1) {
                    Response::error(
                        'last_admin',
                        'The last active administrator cannot lose this role.',
                        409,
                    );
                }
            }
            $q = $this->db->prepare(
                'UPDATE users SET display_name=?,email=?,username=?,version=version+1,updated_at=? WHERE id=? AND version=?',
            );
            $q->execute([
                $name,
                $email,
                $username,
                gmdate('Y-m-d H:i:s.u'),
                $id,
                $current['version'],
            ]);
            $q = $this->db->prepare(
                <<<'SQL'
                DELETE FROM user_roles
                WHERE
                    user_id = ?
                SQL
                ,
            );
            $q->execute([$id]);
            $ins = $this->db->prepare(
                <<<'SQL'
                INSERT INTO user_roles (user_id, role_id)
                SELECT
                    ?,
                    id
                FROM roles
                WHERE
                    role_key = ?
                SQL
                ,
            );
            foreach ($roles as $role) {
                $ins->execute([$id, $role]);
            }
            $this->audit($admin, 'user.updated');
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Response::error(
                $e->getMessage() === 'last_admin' ? 'last_admin' : 'conflict',
                'User could not be updated.',
                409,
            );
        }
        Response::json(['data' => $this->profile($id)]);
    }

    public function disable(int $admin, int $id): never {
        $this->requireRole('portal_admin');
        if ($id === $admin) {
            Response::error('forbidden', 'You cannot disable your own account.', 403);
        }
        $this->db->beginTransaction();
        try {
            $q = $this->db->prepare(
                <<<'SQL'
                SELECT
                u.account_status,
                EXISTS(
                SELECT
                1
                FROM
                user_roles ur
                JOIN roles r ON
                r.id = ur.role_id
                WHERE
                ur.user_id = u.id AND r.role_key = "portal_admin"
                ) AS is_admin
                FROM
                users u
                WHERE
                u.id = ? FOR UPDATE
                SQL
                ,
            );
            $q->execute([$id]);
            $target = $q->fetch();
            if (!$target) {
                Response::error('not_found', 'User not found.', 404);
            }
            if ($target['account_status'] === 'active' && (int) $target['is_admin'] === 1) {
                $q = $this->db->query(
                    <<<'SQL'
                    SELECT
                    u.id
                    FROM
                    users u
                    JOIN user_roles ur ON
                    ur.user_id = u.id
                    JOIN roles r ON
                    r.id = ur.role_id
                    WHERE
                    r.role_key = 'portal_admin' AND u.account_status = 'active'
                    ORDER BY
                    u.id FOR UPDATE
                    SQL
                    ,
                );
                if (count($q->fetchAll()) <= 1) {
                    Response::error(
                        'last_admin',
                        'The last active administrator cannot be disabled.',
                        409,
                    );
                }
            }
            $q = $this->db->prepare(
                'UPDATE users SET account_status="disabled",version=version+1,updated_at=? WHERE id=? AND account_status<>"disabled"',
            );
            $q->execute([gmdate('Y-m-d H:i:s.u'), $id]);
            $this->db->commit();
            $this->sessions->revokeUser($id);
            $this->audit($admin, 'user.disabled');
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Response::error(
                $e->getMessage() === 'last_admin' ? 'last_admin' : 'conflict',
                'User could not be disabled.',
                409,
            );
        }
        Response::json(['data' => $this->profile($id)]);
    }

    private function norm(mixed $v): ?string {
        $v = trim((string) $v);
        return $v === '' ? null : strtolower($v);
    }

    private function hasCampaigns(int $uid): bool {
        $q = $this->db->prepare(
            'SELECT 1 FROM campaign_memberships WHERE user_id = ? AND left_at IS NULL LIMIT 1',
        );
        $q->execute([$uid]);
        return $q->fetchColumn() !== false;
    }

    private function audit(int $uid, string $event): void {
        $q = $this->db->prepare(
            <<<'SQL'
            INSERT INTO audit_events (actor_user_id, event_type)
            VALUES
                (?, ?)
            SQL
            ,
        );
        $q->execute([$uid, $event]);
    }
}
