<?php

declare(strict_types=1);

namespace DndPortal\Domain;

use DndPortal\Http\Response;
use PDO;

final class PaymentService
{
    public function __construct(private PDO $db) {}
    public function list(int $uid, int $campaign): never
    {
        $this->manage($uid, $campaign);
        $q = $this->db->prepare('SELECT id,title,description,amount_cents,currency,status,due_date,external_url,created_at,updated_at,version FROM payment_requests WHERE campaign_id=? ORDER BY created_at DESC');
        $q->execute([$campaign]);
        Response::json(['data' => $q->fetchAll()]);
    }
    public function get(int $uid, int $id): never
    {
        $q = $this->db->prepare('SELECT r.*,c.id campaign_id FROM payment_requests r JOIN campaigns c ON c.id=r.campaign_id WHERE r.id=?');
        $q->execute([$id]);
        $r = $q->fetch();
        if (!$r) {
            Response::error('not_found', 'Payment request not found.', 404);
        }$this->manage($uid, (int)$r['campaign_id']);
        $q = $this->db->prepare('SELECT o.id,o.user_id,o.amount_cents,o.currency,o.status,COALESCE(SUM(CASE WHEN e.status="confirmed" AND e.entry_kind="payment" THEN e.amount_cents WHEN e.status="confirmed" AND e.entry_kind="reversal" THEN -e.amount_cents ELSE 0 END),0) paid_cents,MAX(CASE WHEN e.entry_kind="payment" AND e.status="confirmed" THEN e.id ELSE NULL END) last_payment_entry_id FROM payment_obligations o LEFT JOIN payment_entries e ON e.obligation_id=o.id WHERE o.payment_request_id=? GROUP BY o.id');
        $q->execute([$id]);
        Response::json(['data' => ['id' => (int)$r['id'],'title' => $r['title'],'description' => $r['description'],'amount_cents' => $r['amount_cents'],'currency' => $r['currency'],'status' => $r['status'],'due_date' => $r['due_date'],'version' => $r['version'],'obligations' => $q->fetchAll()]]);
    }
    public function create(int $uid, int $campaign, array $in): never
    {
        $this->manage($uid, $campaign);
        $amount = $in['amount_cents'] ?? null;
        $url = $in['external_url'] ?? null;
        if (($amount !== null && (!is_int($amount) || $amount < 0)) || ($url !== null && !$this->safeUrl((string)$url))) {
            Response::error('validation_failed', 'Invalid amount or payment URL.', 422);
        }$currency = strtoupper((string)($in['currency'] ?? 'EUR'));
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            Response::error('validation_failed', 'Invalid currency.', 422);
        }$q = $this->db->prepare('INSERT INTO payment_requests(campaign_id,created_by,title,description,amount_cents,currency,external_url,due_date) VALUES(?,?,?,?,?,?,?,?)');
        $q->execute([$campaign,$uid,trim((string)($in['title'] ?? '')),$in['description'] ?? null,$amount,$currency,$url,$in['due_date'] ?? null]);
        Response::json(['data' => ['id' => (int)$this->db->lastInsertId()]], 201);
    }
    public function obligation(int $uid, int $request, array $in): never
    {
        $q = $this->db->prepare('SELECT campaign_id,currency FROM payment_requests WHERE id=?');
        $q->execute([$request]);
        $r = $q->fetch();
        if (!$r) {
            Response::error('not_found', 'Payment request not found.', 404);
        }$this->manage($uid, (int)$r['campaign_id']);
        $amount = $in['amount_cents'] ?? null;
        $target = (int)($in['user_id'] ?? 0);
        if (!is_int($amount) || $amount <= 0 || $target < 1 || strtoupper((string)($in['currency'] ?? $r['currency'])) !== $r['currency']) {
            Response::error('validation_failed', 'Invalid obligation.', 422);
        }$q = $this->db->prepare('INSERT INTO payment_obligations(payment_request_id,user_id,amount_cents,currency) VALUES(?,?,?,?)');
        try {
            $q->execute([$request,$target,$amount,$r['currency']]);
        } catch (\PDOException) {
            Response::error('conflict', 'Obligation already exists or is invalid.', 409);
        }Response::json(['data' => ['id' => (int)$this->db->lastInsertId()]], 201);
    }
    public function entry(int $uid, int $obligation, array $in): never
    {
        $amount = $in['amount_cents'] ?? null;
        $currency = strtoupper((string)($in['currency'] ?? ''));
        if (!is_int($amount) || $amount <= 0) {
            Response::error('validation_failed', 'Amount and currency are invalid.', 422);
        }
        $this->db->beginTransaction();
        try {
            $q = $this->db->prepare('SELECT o.amount_cents,o.currency,r.campaign_id FROM payment_obligations o JOIN payment_requests r ON r.id=o.payment_request_id WHERE o.id=? FOR UPDATE');
            $q->execute([$obligation]);
            $o = $q->fetch();
            if (!$o) {
                Response::error('not_found', 'Obligation not found.', 404);
            }$this->manage($uid, (int)$o['campaign_id']);
            if ($currency !== $o['currency']) {
                Response::error('validation_failed', 'Amount and currency are invalid.', 422);
            }
            $q = $this->db->prepare('SELECT COALESCE(SUM(CASE WHEN entry_kind="payment" THEN amount_cents WHEN entry_kind="reversal" THEN -amount_cents ELSE 0 END),0) FROM payment_entries WHERE obligation_id=? AND status="confirmed"');
            $q->execute([$obligation]);
            $net = (int)$q->fetchColumn();
            $correction = $in['correction_of_entry_id'] ?? null;
            $kind = $correction === null ? 'payment' : 'reversal';
            if ($kind === 'payment' && $amount > (int)$o['amount_cents'] - $net) {
                Response::error('conflict', 'Payment exceeds the outstanding obligation.', 409);
            }
            if ($kind === 'reversal') {
                $q = $this->db->prepare('SELECT id,amount_cents,currency,entry_kind,status FROM payment_entries WHERE id=? AND obligation_id=? FOR UPDATE');
                $q->execute([(int)$correction,$obligation]);
                $original = $q->fetch();
                if (!$original || $original['entry_kind'] !== 'payment' || $original['status'] !== 'confirmed' || $original['currency'] !== $currency || $amount > (int)$original['amount_cents'] || $amount > $net) {
                    Response::error('validation_failed', 'Reversal exceeds the confirmed payment balance.', 422);
                }
            }
            $reference = $in['external_reference'] ?? null;
            if ($reference !== null) {
                $q = $this->db->prepare('SELECT 1 FROM payment_entries WHERE obligation_id=? AND external_reference=?');
                $q->execute([$obligation,$reference]);
                if ($q->fetchColumn()) {
                    Response::error('conflict', 'Payment reference already recorded.', 409);
                }
            }
            try {
                $q = $this->db->prepare('INSERT INTO payment_entries(obligation_id,amount_cents,currency,status,entry_kind,external_reference,entered_by,correction_of_entry_id) VALUES(?,?,?,?,?,?,?,?)');
                $q->execute([$obligation,$amount,$currency,'confirmed',$kind,$reference,$uid,$correction]);
            } catch (\PDOException $e) {
                if ((string)($e->errorInfo[1] ?? '') === '1062') {
                    Response::error('conflict', 'Payment reference already recorded.', 409);
                }throw $e;
            }$id = (int)$this->db->lastInsertId();
            $newNet = $kind === 'payment' ? $net + $amount : $net - $amount;
            $status = $newNet >= (int)$o['amount_cents'] ? 'settled' : 'open';
            $this->db->prepare('UPDATE payment_obligations SET status=? WHERE id=?')->execute([$status,$obligation]);
            $this->db->commit();
            Response::json(['data' => ['id' => $id,'entry_kind' => $kind,'paid_cents' => $newNet,'outstanding_cents' => max(0, (int)$o['amount_cents'] - $newNet)]], 201);
        } catch (\Throwable) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            } Response::error('server_error', 'Payment could not be recorded.', 500);
        }
    }
    public function own(int $uid): never
    {
        $q = $this->db->prepare('SELECT o.id,o.payment_request_id,o.amount_cents,o.currency,o.status,COALESCE(SUM(CASE WHEN e.status="confirmed" AND e.entry_kind="payment" THEN e.amount_cents WHEN e.status="confirmed" AND e.entry_kind="reversal" THEN -e.amount_cents ELSE 0 END),0) paid_cents,o.amount_cents-COALESCE(SUM(CASE WHEN e.status="confirmed" AND e.entry_kind="payment" THEN e.amount_cents WHEN e.status="confirmed" AND e.entry_kind="reversal" THEN -e.amount_cents ELSE 0 END),0) outstanding_cents FROM payment_obligations o LEFT JOIN payment_entries e ON e.obligation_id=o.id WHERE o.user_id=? AND o.status="open" GROUP BY o.id');
        $q->execute([$uid]);
        Response::json(['data' => $q->fetchAll()]);
    }
    private function safeUrl(string $url): bool
    {
        $p = parse_url($url);
        return is_array($p) && ($p['scheme'] ?? '') === 'https' && !empty($p['host']) && empty($p['user']) && !isset($p['password']);
    }
    private function manage(int $uid, int $campaign): void
    {
        $q = $this->db->prepare("SELECT EXISTS(SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=? AND r.role_key='portal_admin') OR EXISTS(SELECT 1 FROM campaign_memberships WHERE user_id=? AND campaign_id=? AND membership_role='dm' AND left_at IS NULL)");
        $q->execute([$uid,$uid,$campaign]);
        if (!$q->fetchColumn()) {
            Response::error('forbidden', 'Payment management required.', 403);
        }
    }
}
