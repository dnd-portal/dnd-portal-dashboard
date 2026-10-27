<?php

declare(strict_types=1);

namespace DndPortal\Domain;

use DndPortal\Http\Response;
use PDO;

final class PollService
{
    public function __construct(private PDO $db)
    {
    }
    public function list(int $uid, int $campaign): never
    {
        $this->manage($uid, $campaign);
        $q = $this->db->prepare('SELECT id,campaign_id,title,choice_type,status,closes_at,created_at,updated_at,version FROM polls WHERE campaign_id=? ORDER BY created_at DESC');
        $q->execute([$campaign]);
        Response::json(['data' => $q->fetchAll()]);
    }
    public function get(int $uid, int $id): never
    {
        $p = $this->find($id);
        if (!$p) {
            Response::error('not_found', 'Poll not found.', 404);
        } $this->member($uid, (int)$p['campaign_id']);
        $poll = $this->poll($id);
        $q = $this->db->prepare('SELECT option_id FROM poll_votes WHERE poll_id=? AND user_id=? ORDER BY option_id');
        $q->execute([$id,$uid]);
        $poll['selected_option_ids'] = array_map('intval', array_column($q->fetchAll(), 'option_id'));
        $q = $this->db->prepare('SELECT response_state FROM poll_participants WHERE poll_id=? AND user_id=?');
        $q->execute([$id,$uid]);
        $poll['response_state'] = $q->fetchColumn() ?: 'pending';
        Response::json(['data' => $poll]);
    }
    public function create(int $uid, int $campaign, array $in): never
    {
        $this->manage($uid, $campaign);
        $title = trim((string)($in['title'] ?? ''));
        $type = $in['choice_type'] ?? 'single';
        $options = $in['options'] ?? [];
        if ($title === '' || !in_array($type, ['single','multiple'], true) || !is_array($options) || count($options) < 2) {
            Response::error('validation_failed', 'A poll needs a title and at least two options.', 422);
        } $labels = array_values(array_unique(array_filter(array_map(static fn ($v) => trim((string)$v), $options), static fn ($v) => $v !== '')));
        if (count($labels) !== count($options)) {
            Response::error('validation_failed', 'Poll options must be non-empty and unique.', 422);
        } $this->db->beginTransaction();
        try {
            $q = $this->db->prepare('INSERT INTO polls(campaign_id,title,choice_type,status,closes_at,created_by) VALUES(?,?,?,?,?,?)');
            $q->execute([$campaign,$title,$type,'open',$in['closes_at'] ?? null,$uid]);
            $id = (int)$this->db->lastInsertId();
            $o = $this->db->prepare('INSERT INTO poll_options(poll_id,label,position) VALUES(?,?,?)');
            foreach ($labels as $i => $label) {
                $o->execute([$id,$label,$i + 1]);
            }$this->db->commit();
        } catch (\Throwable) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }Response::error('validation_failed', 'Poll could not be created.', 422);
        } Response::json(['data' => $this->poll($id)], 201);
    }
    public function update(int $uid, int $id, array $in): never
    {
        $p = $this->find($id);
        if (!$p) {
            Response::error('not_found', 'Poll not found.', 404);
        }$this->manage($uid, (int)$p['campaign_id']);
        if (array_diff(array_keys($in), ['title','closes_at','status','version','options'])) {
            Response::error('validation_failed', 'Unsupported fields.', 422);
        }$version = filter_var($in['version'] ?? null, FILTER_VALIDATE_INT);
        if ($version === false) {
            Response::error('validation_failed', 'Version is required.', 422);
        }$fields = [];
        $values = [];
        foreach (['title','closes_at','status'] as $field) {
            if (array_key_exists($field, $in)) {
                $fields[] = "$field=?";
                $values[] = $field === 'title' ? trim((string)$in[$field]) : $in[$field];
            }
        }if (isset($in['status']) && !in_array($in['status'], ['open','closed'], true)) {
            Response::error('validation_failed', 'Invalid poll status.', 422);
        }$optionUpdates = $in['options'] ?? null;
        if ($optionUpdates !== null) {
            if (!is_array($optionUpdates) || count($optionUpdates) < 2) {
                Response::error('validation_failed', 'Poll options are invalid.', 422);
            }$q = $this->db->prepare('SELECT id FROM poll_options WHERE poll_id=? ORDER BY id');
            $q->execute([$id]);
            $existing = array_map('intval', array_column($q->fetchAll(), 'id'));
            $received = [];
            $labels = [];
            foreach ($optionUpdates as $option) {
                $optionId = filter_var($option['id'] ?? null, FILTER_VALIDATE_INT);
                $label = trim((string)($option['label'] ?? ''));
                if ($optionId === false || $label === ' ' || $label === '' || in_array($optionId, $received, true)) {
                    Response::error('validation_failed', 'Poll options are invalid.', 422);
                }$received[] = (int)$optionId;
                $labels[] = $label;
            }sort($existing);
            $sorted = $received;
            sort($sorted);
            if ($sorted !== $existing) {
                Response::error('validation_failed', 'Options cannot be added or removed after poll creation.', 422);
            }if (count($labels) !== count(array_unique($labels))) {
                Response::error('validation_failed', 'Poll options must be unique.', 422);
            }$q = $this->db->prepare('SELECT COUNT(*) FROM poll_votes WHERE poll_id=?');
            $q->execute([$id]);
            if ((int)$q->fetchColumn() > 0 && $sorted !== $existing) {
                Response::error('conflict', 'Voted poll options cannot be changed structurally.', 409);
            }
        }$this->db->beginTransaction();
        try {
            if (!$fields) {
                $fields[] = 'title=title';
            }$values[] = $id;
            $values[] = $version;
            $q = $this->db->prepare('UPDATE polls SET '.implode(',', $fields).',updated_at=CURRENT_TIMESTAMP,version=version+1 WHERE id=? AND version=?');
            $q->execute($values);
            if ($q->rowCount() !== 1) {
                $this->db->rollBack();
                Response::error('conflict', 'Poll was changed by another request.', 409);
            }if ($optionUpdates !== null) {
                $q = $this->db->prepare('UPDATE poll_options SET label=? WHERE id=? AND poll_id=?');
                foreach ($optionUpdates as $option) {
                    $q->execute([trim((string)$option['label']),(int)$option['id'],$id]);
                }
            }$this->db->commit();
        } catch (\Throwable) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }Response::error('server_error', 'Poll could not be updated.', 500);
        }Response::json(['data' => $this->poll($id)]);
    }
    public function vote(int $uid, int $poll, array $in): never
    {
        $ids = $in['option_ids'] ?? [];
        if (!is_array($ids) || count($ids) < 1 || count($ids) !== count(array_unique($ids))) {
            Response::error('validation_failed', 'Invalid choices.', 422);
        }$this->db->beginTransaction();
        try {
            $q = $this->db->prepare('SELECT campaign_id,choice_type,status,closes_at FROM polls WHERE id=? FOR UPDATE');
            $q->execute([$poll]);
            $p = $q->fetch();
            if (!$p) {
                $this->db->rollBack();
                Response::error('not_found', 'Poll not found.', 404);
            }$this->member($uid, (int)$p['campaign_id']);
            $q = $this->db->prepare('SELECT 1 FROM poll_participants WHERE poll_id=? AND user_id=?');
            $q->execute([$poll,$uid]);
            if (!$q->fetchColumn()) {
                $this->db->rollBack();
                Response::error('forbidden', 'Poll participation required.', 403);
            }if ($p['status'] !== 'open' || ($p['closes_at'] && strtotime($p['closes_at']) <= time())) {
                $this->db->rollBack();
                Response::error('conflict', 'Poll is closed.', 409);
            }if ($p['choice_type'] === 'single' && count($ids) !== 1) {
                $this->db->rollBack();
                Response::error('validation_failed', 'Single-choice polls accept one option.', 422);
            }$place = implode(',', array_fill(0, count($ids), '?'));
            $q = $this->db->prepare("SELECT id FROM poll_options WHERE poll_id=? AND id IN ($place)");
            $q->execute(array_merge([$poll], array_map('intval', $ids)));
            if (count($q->fetchAll()) !== count($ids)) {
                $this->db->rollBack();
                Response::error('validation_failed', 'Options do not belong to this poll.', 422);
            }$q = $this->db->prepare('DELETE FROM poll_votes WHERE poll_id=? AND user_id=?');
            $q->execute([$poll,$uid]);
            $q = $this->db->prepare('INSERT INTO poll_votes(poll_id,user_id,option_id) VALUES(?,?,?)');
            foreach ($ids as $id) {
                $q->execute([$poll,$uid,(int)$id]);
            }$this->db->prepare('UPDATE poll_participants SET response_state="voted" WHERE poll_id=? AND user_id=?')->execute([$poll,$uid]);
            $this->db->commit();
        } catch (\Throwable) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }Response::error('server_error', 'Vote could not be saved.', 500);
        }Response::json(['data' => null]);
    }
    public function respondWithoutOption(int $uid, int $poll): never
    {
        $p = $this->find($poll);
        if (!$p) {
            Response::error('not_found', 'Poll not found.', 404);
        }$this->member($uid, (int)$p['campaign_id']);
        if ($p['status'] !== 'open' || ($p['closes_at'] && strtotime($p['closes_at']) <= time())) {
            Response::error('conflict', 'Poll is closed.', 409);
        }$q = $this->db->prepare('SELECT 1 FROM poll_participants WHERE poll_id=? AND user_id=?');
        $q->execute([$poll,$uid]);
        if (!$q->fetchColumn()) {
            Response::error('forbidden', 'Poll participation required.', 403);
        }$this->db->prepare('DELETE FROM poll_votes WHERE poll_id=? AND user_id=?')->execute([$poll,$uid]);
        $this->db->prepare('UPDATE poll_participants SET response_state="responded_without_option" WHERE poll_id=? AND user_id=?')->execute([$poll,$uid]);
        Response::json(['data' => ['poll_id' => $poll,'response_state' => 'responded_without_option']]);
    }
    public function results(int $uid, int $id): never
    {
        $p = $this->find($id);
        if (!$p) {
            Response::error('not_found', 'Poll not found.', 404);
        }$this->member($uid, (int)$p['campaign_id']);
        $q = $this->db->prepare('SELECT o.id,o.label,COUNT(v.user_id) AS vote_count FROM poll_options o LEFT JOIN poll_votes v ON v.poll_id=o.poll_id AND v.option_id=o.id WHERE o.poll_id=? GROUP BY o.id,o.label,o.position ORDER BY o.position');
        $q->execute([$id]);
        Response::json(['data' => ['poll_id' => $id,'options' => $q->fetchAll()]]);
    }
    public function setParticipant(int $uid, int $id, int $target, array $in): never
    {
        $p = $this->find($id);
        if (!$p) {
            Response::error('not_found', 'Poll not found.', 404);
        }$this->manage($uid, (int)$p['campaign_id']);
        $q = $this->db->prepare('SELECT id FROM users WHERE id=? AND account_status="active"');
        $q->execute([$target]);
        if (!$q->fetchColumn()) {
            Response::error('not_found', 'User not found.', 404);
        }$add = ($in['active'] ?? true) === true;
        if ($add) {
            $q = $this->db->prepare('INSERT IGNORE INTO poll_participants(poll_id,user_id) VALUES(?,?)');
            $q->execute([$id,$target]);
        } else {
            $q = $this->db->prepare('DELETE FROM poll_participants WHERE poll_id=? AND user_id=?');
            $q->execute([$id,$target]);
        }Response::json(['data' => ['poll_id' => $id,'user_id' => $target,'active' => $add]]);
    }
    private function find(int $id): ?array
    {
        $q = $this->db->prepare('SELECT id,campaign_id,title,choice_type,status,closes_at,created_at,updated_at,version FROM polls WHERE id=?');
        $q->execute([$id]);
        return $q->fetch() ?: null;
    }
    private function poll(int $id): array
    {
        $p = $this->find($id);
        $q = $this->db->prepare('SELECT id,label,position FROM poll_options WHERE poll_id=? ORDER BY position');
        $q->execute([$id]);
        $p['options'] = $q->fetchAll();
        return $p;
    }
    private function member(int $uid, int $campaign): void
    {
        $q = $this->db->prepare('SELECT 1 FROM campaign_memberships WHERE user_id=? AND campaign_id=? AND left_at IS NULL');
        $q->execute([$uid,$campaign]);
        if (!$q->fetchColumn()) {
            Response::error('forbidden', 'Campaign membership required.', 403);
        }
    }
    private function manage(int $uid, int $campaign): void
    {
        $q = $this->db->prepare("SELECT EXISTS(SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=? AND r.role_key='portal_admin') OR EXISTS(SELECT 1 FROM campaign_memberships WHERE user_id=? AND campaign_id=? AND membership_role='dm' AND left_at IS NULL)");
        $q->execute([$uid,$uid,$campaign]);
        if (!$q->fetchColumn()) {
            Response::error('forbidden', 'Campaign management required.', 403);
        }
    }
}
