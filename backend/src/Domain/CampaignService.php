<?php

declare(strict_types=1);

namespace DndPortal\Domain;

use DndPortal\Http\Response;
use PDO;

final class CampaignService {
    public function __construct(private PDO $db) {}
    public function list(int $uid): never {
        $q = $this->db->prepare("SELECT c.id,c.name,c.slug,c.type,c.location,c.timezone,c.archived_at,c.version,cm.membership_role FROM campaigns c LEFT JOIN campaign_memberships cm ON cm.campaign_id=c.id AND cm.user_id=? AND cm.left_at IS NULL WHERE (c.archived_at IS NULL) AND (cm.user_id IS NOT NULL OR EXISTS(SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=? AND r.role_key='portal_admin')) ORDER BY c.name");
        $q->execute([$uid,$uid]);
        Response::json(['data' => $q->fetchAll()]);
    }
    public function get(int $uid, int $id): never {
        $q = $this->db->prepare('SELECT c.id,c.name,c.slug,c.type,c.location,c.timezone,c.archived_at,c.version,cm.membership_role FROM campaigns c LEFT JOIN campaign_memberships cm ON cm.campaign_id=c.id AND cm.user_id=? AND cm.left_at IS NULL WHERE c.id=?');
        $q->execute([$uid,$id]);
        $row = $q->fetch();
        if (!$row) {
            Response::error('not_found', 'Campaign not found.', 404);
        }$isAdmin = $this->isAdmin($uid);
        if ($row['membership_role'] === null && !$isAdmin) {
            Response::error('forbidden', 'Campaign access required.', 403);
        }
        Response::json(['data' => $row]);
    }
    public function create(int $uid, array $in): never {
        $this->validateCampaign($in);
        $q = $this->db->prepare('INSERT INTO campaigns(name,slug,type,location,timezone,created_by) VALUES(?,?,?,?,?,?)');
        $q->execute([trim($in['name']),$in['slug'],($in['type'] ?? 'campaign'),$in['location'] ?? null,$in['timezone'] ?? 'UTC',$uid]);
        $id = (int)$this->db->lastInsertId();
        $q = $this->db->prepare("INSERT INTO campaign_memberships(campaign_id,user_id,membership_role) VALUES(?,?, 'dm')");
        $q->execute([$id,$uid]);
        Response::json(['data' => $this->campaign($id)], 201);
    }
    public function update(int $uid, int $id, array $in): never {
        $this->assertManage($uid, $id);
        $allowed = ['name','slug','location','timezone','archived_at','version'];
        if (array_diff(array_keys($in), $allowed)) {
            Response::error('validation_failed', 'Unsupported fields.', 422);
        }$archived = $in['archived_at'] ?? null;
        if ($archived !== null) {
            try {
                $archived = (new \DateTimeImmutable((string)$archived))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');
            } catch (\Throwable) {
                Response::error('validation_failed', 'Invalid archive timestamp.', 422);
            }
        }
        $q = $this->db->prepare('UPDATE campaigns SET name=COALESCE(?,name),slug=COALESCE(?,slug),location=?,timezone=COALESCE(?,timezone),archived_at=?,version=version+1 WHERE id=? AND version=?');
        $q->execute([$in['name'] ?? null,$in['slug'] ?? null,$in['location'] ?? null,$in['timezone'] ?? null,$archived,$id,(int)($in['version'] ?? 0)]);
        if ($q->rowCount() !== 1) {
            Response::error('conflict', 'Campaign was changed or no longer exists.', 409);
        }
        Response::json(['data' => $this->campaign($id)]);
    }
    public function addMember(int $uid, int $campaign, int $member, array $in): never {
        $this->assertManage($uid, $campaign);
        $role = $in['membership_role'] ?? 'member';
        if (!in_array($role, ['member','dm'], true)) {
            Response::error('validation_failed', 'Invalid membership role.', 422);
        }
        $q = $this->db->prepare('SELECT 1 FROM campaigns WHERE id=?');
        $q->execute([$campaign]);
        if (!$q->fetchColumn()) {
            Response::error('not_found', 'Campaign not found.', 404);
        }
        $q = $this->db->prepare('SELECT 1 FROM users WHERE id=?');
        $q->execute([$member]);
        if (!$q->fetchColumn()) {
            Response::error('not_found', 'User not found.', 404);
        }
        $q = $this->db->prepare('INSERT INTO campaign_memberships(campaign_id,user_id,membership_role,left_at) VALUES(?,?,?,NULL) ON DUPLICATE KEY UPDATE membership_role=VALUES(membership_role),left_at=NULL');
        $q->execute([$campaign,$member,$role]);
        $q = $this->db->prepare('SELECT cm.campaign_id,cm.user_id,cm.membership_role,cm.joined_at,cm.left_at,u.display_name,u.email,u.username FROM campaign_memberships cm JOIN users u ON u.id=cm.user_id WHERE cm.campaign_id=? AND cm.user_id=?');
        $q->execute([$campaign,$member]);
        $row = $q->fetch();
        if (!$row) {
            Response::error('conflict', 'Membership was not stored.', 409);
        }
        Response::json(['data' => $row], 201);
    }
    public function members(int $uid, int $campaign): never {
        $this->assertView($uid, $campaign);
        $manager = $this->canManage($uid, $campaign);
        $sql = 'SELECT cm.campaign_id,cm.user_id,cm.membership_role,cm.joined_at,cm.left_at,u.display_name,u.email,u.username FROM campaign_memberships cm JOIN users u ON u.id=cm.user_id WHERE cm.campaign_id=?';
        $params = [$campaign];
        if (!$manager) {
            $sql .= ' AND cm.user_id=?';
            $params[] = $uid;
        }$sql .= ' ORDER BY cm.left_at IS NULL DESC, u.display_name';
        $q = $this->db->prepare($sql);
        $q->execute($params);
        $rows = $q->fetchAll();
        Response::json(['data' => $rows]);
    }
    public function leave(int $uid, int $campaign, int $member): never {
        $this->assertManage($uid, $campaign);
        $q = $this->db->prepare('UPDATE campaign_memberships SET left_at=? WHERE campaign_id=? AND user_id=? AND left_at IS NULL');
        $q->execute([gmdate('Y-m-d H:i:s.u'),$campaign,$member]);
        Response::json(['data' => null]);
    }
    public function sessions(int $uid, int $campaign): never {
        $this->assertView($uid, $campaign);
        $q = $this->db->prepare('SELECT id,campaign_id,title,session_date,starts_at,ends_at,rsvp_deadline_at,location,status,version FROM game_sessions WHERE campaign_id=? ORDER BY session_date');
        $q->execute([$campaign]);
        Response::json(['data' => $q->fetchAll()]);
    }
    public function getSession(int $uid, int $id): never {
        $campaign = $this->sessionCampaign($id);
        $this->assertView($uid, $campaign);
        Response::json(['data' => $this->session($id)]);
    }
    public function participants(int $uid, int $session): never {
        $campaign = $this->sessionCampaign($session);
        $this->assertView($uid, $campaign);
        $manager = $this->canManage($uid, $campaign);
        $sql = 'SELECT sp.session_id,sp.user_id,sp.rsvp_status,sp.attendance_status,sp.responded_at,sp.attendance_recorded_at,u.display_name FROM session_participants sp JOIN users u ON u.id=sp.user_id WHERE sp.session_id=?';
        $params = [$session];
        if (!$manager) {
            $sql .= ' AND sp.user_id=?';
            $params[] = $uid;
        }$sql .= ' ORDER BY u.display_name';
        $q = $this->db->prepare($sql);
        $q->execute($params);
        Response::json(['data' => $q->fetchAll()]);
    }
    public function createSession(int $uid, int $campaign, array $in): never {
        $this->assertManage($uid, $campaign);
        $in = $this->normaliseSessionInput($in);
        $this->validateSession($in);
        $q = $this->db->prepare('INSERT INTO game_sessions(campaign_id,title,session_date,starts_at,ends_at,rsvp_deadline_at,location,created_by) VALUES(?,?,?,?,?,?,?,?)');
        $q->execute([$campaign,$in['title'],$in['session_date'],$in['starts_at'] ?? null,$in['ends_at'] ?? null,$in['rsvp_deadline_at'] ?? null,$in['location'] ?? null,$uid]);
        Response::json(['data' => $this->session((int)$this->db->lastInsertId())], 201);
    }
    public function updateSession(int $uid, int $id, array $in): never {
        $campaign = $this->sessionCampaign($id);
        $this->assertManage($uid, $campaign);
        $in = $this->normaliseSessionInput($in);
        $allowed = ['title','session_date','starts_at','ends_at','rsvp_deadline_at','location','status','version'];
        if (array_diff(array_keys($in), $allowed)) {
            Response::error('validation_failed', 'Unsupported fields.', 422);
        }
        $q = $this->db->prepare('UPDATE game_sessions SET title=COALESCE(?,title),session_date=COALESCE(?,session_date),starts_at=?,ends_at=?,rsvp_deadline_at=?,location=?,status=COALESCE(?,status),version=version+1 WHERE id=? AND version=?');
        $q->execute([$in['title'] ?? null,$in['session_date'] ?? null,$in['starts_at'] ?? null,$in['ends_at'] ?? null,$in['rsvp_deadline_at'] ?? null,$in['location'] ?? null,$in['status'] ?? null,$id,(int)($in['version'] ?? 0)]);
        if (!$q->rowCount()) {
            Response::error('conflict', 'Session was changed or no longer exists.', 409);
        }
        Response::json(['data' => $this->session($id)]);
    }
    public function participant(int $uid, int $session, int $member, array $in): never {
        $campaign = $this->sessionCampaign($session);
        $this->assertView($uid, $campaign);
        $isManager = $this->canManage($uid, $campaign);
        if (!$isManager && $member !== $uid) {
            Response::error('forbidden', 'You may only change your own RSVP.', 403);
        }$allowed = $isManager ? ['rsvp_status','attendance_status'] : ['rsvp_status'];
        if (array_diff(array_keys($in), $allowed)) {
            Response::error('validation_failed', 'Unsupported participant fields.', 422);
        }
        if (!$isManager && isset($in['rsvp_status']) && $this->deadlinePassed($session)) {
            Response::error('conflict', 'The RSVP deadline has passed.', 409);
        }$existing = $this->db->prepare('SELECT rsvp_status,attendance_status FROM session_participants WHERE session_id=? AND user_id=?');
        $existing->execute([$session,$member]);
        $current = $existing->fetch() ?: ['rsvp_status' => 'pending','attendance_status' => 'unknown'];
        $r = $in['rsvp_status'] ?? $current['rsvp_status'];
        $a = $in['attendance_status'] ?? $current['attendance_status'];
        $q = $this->db->prepare('INSERT INTO session_participants(session_id,user_id,rsvp_status,attendance_status,responded_at,attendance_recorded_at) VALUES(?,?,?, ?,IF(? IS NULL,NULL,UTC_TIMESTAMP(6)),IF(? IS NULL,NULL,UTC_TIMESTAMP(6))) ON DUPLICATE KEY UPDATE rsvp_status=VALUES(rsvp_status),attendance_status=VALUES(attendance_status),responded_at=IF(? IS NULL,responded_at,VALUES(responded_at)),attendance_recorded_at=IF(? IS NULL,attendance_recorded_at,VALUES(attendance_recorded_at))');
        $q->execute([$session,$member,$r,$a,$in['rsvp_status'] ?? null,$in['attendance_status'] ?? null,$in['rsvp_status'] ?? null,$in['attendance_status'] ?? null]);
        if ($isManager && ($in['rsvp_status'] ?? null) !== null) {
            $this->audit($uid, 'session_rsvp_corrected', ['session_id' => $session,'user_id' => $member,'rsvp_status' => $r]);
        }
        if ($isManager && ($in['attendance_status'] ?? null) !== null) {
            $this->audit($uid, 'session_attendance_corrected', ['session_id' => $session,'user_id' => $member,'attendance_status' => $a]);
        }
        Response::json(['data' => null]);
    }
    public function availability(int $uid, ?int $forUser, array $in): never {
        $target = $forUser ?? $uid;
        if ($target !== $uid) {
            $this->requireAdmin($uid);
        }$isAdmin = $this->isAdmin($uid);
        if ($forUser === null && $isAdmin) {
            $q = $this->db->query('SELECT a.user_id,a.availability_date,a.status,a.note,a.updated_at,u.display_name FROM user_availability a JOIN users u ON u.id=a.user_id WHERE a.availability_date BETWEEN UTC_DATE()-INTERVAL 366 DAY AND UTC_DATE()+INTERVAL 366 DAY ORDER BY a.availability_date,u.display_name');
            Response::json(['data' => $q->fetchAll()]);
        }
        $q = $this->db->prepare('SELECT availability_date,status,note,updated_at FROM user_availability WHERE user_id=? ORDER BY availability_date');
        $q->execute([$target]);
        Response::json(['data' => $q->fetchAll()]);
    }
    public function setAvailability(int $uid, string $date, array $in): never {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date.' 00:00:00 UTC') === false || abs(strtotime($date.' 00:00:00 UTC') - time()) > 366 * 86400) {
            Response::error('validation_failed', 'Date is outside the allowed range.', 422);
        }$status = $in['status'] ?? null;
        if (!in_array($status, ['available','unavailable'], true)) {
            Response::error('validation_failed', 'Invalid availability status.', 422);
        }
        $q = $this->db->prepare('INSERT INTO user_availability(user_id,availability_date,status,note) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status),note=VALUES(note),updated_at=UTC_TIMESTAMP(6)');
        $q->execute([$uid,$date,$status,$in['note'] ?? null]);
        Response::json(['data' => null]);
    }
    public function clearAvailability(int $uid, string $date): never {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            Response::error('validation_failed', 'Invalid calendar date.', 422);
        }
        $q = $this->db->prepare('DELETE FROM user_availability WHERE user_id=? AND availability_date=?');
        $q->execute([$uid,$date]);
        Response::json(['data' => null]);
    }
    private function campaign(int $id): array {
        $q = $this->db->prepare('SELECT id,name,slug,type,location,timezone,archived_at,version FROM campaigns WHERE id=?');
        $q->execute([$id]);
        return $q->fetch() ?: [];
    }
    private function session(int $id): array {
        $q = $this->db->prepare('SELECT id,campaign_id,title,session_date,starts_at,ends_at,rsvp_deadline_at,location,status,version FROM game_sessions WHERE id=?');
        $q->execute([$id]);
        return $q->fetch() ?: [];
    }
    private function sessionCampaign(int $id): int {
        $q = $this->db->prepare('SELECT campaign_id FROM game_sessions WHERE id=?');
        $q->execute([$id]);
        $v = $q->fetchColumn();
        if (!$v) {
            Response::error('not_found', 'Session not found.', 404);
        }
        return (int) $v;
    }
    private function canManage(int $uid, int $campaign): bool {
        $q = $this->db->prepare("SELECT EXISTS(SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=? AND r.role_key='portal_admin') OR EXISTS(SELECT 1 FROM campaign_memberships WHERE user_id=? AND campaign_id=? AND membership_role='dm' AND left_at IS NULL)");
        $q->execute([$uid,$uid,$campaign]);
        return(bool)$q->fetchColumn();
    }
    private function isAdmin(int $uid): bool {
        $q = $this->db->prepare("SELECT EXISTS(SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=? AND r.role_key='portal_admin')");
        $q->execute([$uid]);
        return(bool)$q->fetchColumn();
    }
    private function requireAdmin(int $uid): void {
        $q = $this->db->prepare("SELECT EXISTS(SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=? AND r.role_key='portal_admin')");
        $q->execute([$uid]);
        if (!$q->fetchColumn()) {
            Response::error('forbidden', 'Administrator access required.', 403);
        }
    }
    private function assertView(int $uid, int $campaign): void {
        $q = $this->db->prepare('SELECT 1 FROM campaign_memberships WHERE user_id=? AND campaign_id=? AND left_at IS NULL');
        $q->execute([$uid,$campaign]);
        if (!$q->fetchColumn()) {
            $this->requireAdmin($uid);
        }
    }
    private function assertManage(int $uid, int $campaign): void {
        if (!$this->canManage($uid, $campaign)) {
            Response::error('forbidden', 'You do not manage this campaign.', 403);
        }
    }
    private function deadlinePassed(int $session): bool {
        $q = $this->db->prepare('SELECT rsvp_deadline_at FROM game_sessions WHERE id=?');
        $q->execute([$session]);
        $v = $q->fetchColumn();
        return $v !== null && strtotime($v) <= time();
    }
    private function validateCampaign(array $in): void {
        if (trim((string)($in['name'] ?? '')) === '' || !preg_match('/^[a-z0-9-]+$/', (string)($in['slug'] ?? ''))) {
            Response::error('validation_failed', 'Invalid campaign fields.', 422);
        }
    }
    private function validateSession(array $in): void {
        if (trim((string)($in['title'] ?? '')) === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($in['session_date'] ?? '')) || !checkdate((int)substr((string)($in['session_date'] ?? ''), 5, 2), (int)substr((string)($in['session_date'] ?? ''), 8, 2), (int)substr((string)($in['session_date'] ?? ''), 0, 4))) {
            Response::error('validation_failed', 'Invalid session fields.', 422);
        }
    }
    private function normaliseSessionInput(array $in): array {
        foreach (['starts_at','ends_at','rsvp_deadline_at'] as $field) {
            if (array_key_exists($field, $in) && $in[$field] !== null && $in[$field] !== '') {
                $in[$field] = $this->localAmsterdamToUtc((string)$in[$field], isset($in['timezone_offset']) ? (string)$in['timezone_offset'] : null);
            }
        }unset($in['timezone_offset']);
        return$in;
    }
    private function audit(int $uid, string $event, array $payload): void {
        $q = $this->db->prepare('INSERT INTO audit_events(actor_user_id,event_type,payload_json) VALUES(?,?,?)');
        $q->execute([$uid,$event,json_encode($payload, JSON_THROW_ON_ERROR)]);
    }
    private function localAmsterdamToUtc(string $value, ?string $selectedOffset): string {
        $value = str_replace('T', ' ', trim($value));
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            $value .= ':00';
        }$naive = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new \DateTimeZone('UTC'));
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$naive || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))) {
            Response::error('validation_failed', 'Invalid Europe/Amsterdam local time.', 422);
        }$zone = new \DateTimeZone('Europe/Amsterdam');
        $offsets = [];
        foreach ($zone->getTransitions($naive->getTimestamp() - 172800, $naive->getTimestamp() + 172800) as $transition) {
            $offsets[(int)$transition['offset']] = true;
        }$candidates = [];
        foreach (array_keys($offsets) as $offset) {
            $utc = $naive->modify(sprintf('%+d seconds', -(int)$offset));
            if ($utc->setTimezone($zone)->format('Y-m-d H:i:s') === $value) {
                $candidates[(int)$offset] = $utc;
            }
        }$chosen = null;
        if ($selectedOffset !== null) {
            if (!preg_match('/^[+-]\d{2}:\d{2}$/', $selectedOffset)) {
                Response::error('validation_failed', 'Invalid timezone offset choice.', 422);
            }$seconds = ((int)substr($selectedOffset, 1, 2) * 3600 + (int)substr($selectedOffset, 4, 2) * 60) * (str_starts_with($selectedOffset, '-') ? -1 : 1);
            $chosen = $candidates[$seconds] ?? null;
            if (!$chosen) {
                Response::error('validation_failed', 'Timezone offset choice does not match this local time.', 422);
            }
        } elseif (count($candidates) !== 1) {
            Response::error('validation_failed', count($candidates) === 0 ? 'This local time does not exist in Europe/Amsterdam.' : 'This local time is ambiguous; choose a timezone offset.', 422);
        } else {
            $chosen = array_values($candidates)[0];
        }
        return $chosen->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');
    }
}
