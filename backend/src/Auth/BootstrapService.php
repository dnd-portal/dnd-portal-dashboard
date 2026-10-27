<?php

declare(strict_types=1);

namespace DndPortal\Auth;

use DndPortal\Http\Response;
use PDO;

final class BootstrapService {
    public function __construct(private PDO $db) {}

    public function create(array $input, string $token): never {
        $configured = \DndPortal\Config::env('BOOTSTRAP_TOKEN');
        if ($configured === '' || $token === '' || !hash_equals($configured, $token)) {
            Response::error('not_found', 'Not found.', 404);
        }
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $username = strtolower(trim((string) ($input['username'] ?? '')));
        $displayName = trim((string) ($input['display_name'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-z0-9][a-z0-9_.-]{2,63}$/', $username) || $displayName === '' || strlen($password) < 12) {
            Response::error('validation_failed', 'Valid admin details are required.', 422);
        }
        $this->db->beginTransaction();
        try {
            $state = $this->db->query('SELECT used_at FROM portal_bootstrap_state WHERE id=1 FOR UPDATE')->fetch();
            $admins = (int) $this->db->query("SELECT COUNT(*) FROM user_roles ur JOIN roles r ON r.id=ur.role_id JOIN users u ON u.id=ur.user_id WHERE r.role_key='portal_admin' AND u.account_status='active'")->fetchColumn();
            if (!$state || $state['used_at'] !== null || $admins > 0) {
                $this->db->rollBack();
                Response::error('not_found', 'Not found.', 404);
            }
            $q = $this->db->prepare('INSERT INTO users (display_name,email,username,account_status) VALUES (?,?,?,"active")');
            $q->execute([$displayName, $email, $username]);
            $id = (int) $this->db->lastInsertId();
            $q = $this->db->prepare('INSERT INTO user_credentials (user_id,password_hash,password_algo) VALUES (?,?,?)');
            $q->execute([$id, password_hash($password, PASSWORD_DEFAULT), PASSWORD_DEFAULT]);
            $q = $this->db->prepare('INSERT INTO user_roles (user_id,role_id) SELECT ?,id FROM roles WHERE role_key="portal_admin"');
            $q->execute([$id]);
            if ($q->rowCount() !== 1) throw new \RuntimeException('role missing');
            $this->db->exec('UPDATE portal_bootstrap_state SET used_at=UTC_TIMESTAMP(6) WHERE id=1');
            $this->db->commit();
            Response::json(['data' => ['created' => true]], 201);
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            Response::error('validation_failed', 'Bootstrap could not be completed.', 422);
        }
    }
}
