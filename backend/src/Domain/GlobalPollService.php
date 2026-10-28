<?php
declare(strict_types=1);
namespace DndPortal\Domain;
use DndPortal\Http\Response;
use PDO;

final class GlobalPollService {
    public function __construct(private PDO $db) {}
    public function current(int $uid): never {
        $poll = $this->latest();
        if (!$poll) Response::json(['data' => null]);
        $poll['options'] = $this->options((int)$poll['id']);
        $q=$this->db->prepare('SELECT option_id FROM global_poll_responses WHERE poll_id=? AND user_id=? ORDER BY option_id');
        $q->execute([$poll['id'],$uid]);
        $poll['selected_option_ids']=array_map('intval',array_column($q->fetchAll(),'option_id'));
        Response::json(['data'=>$poll]);
    }
    public function answer(int $uid,int $id,array $in): never {
        $q=$this->db->prepare('SELECT choice_type,status FROM global_polls WHERE id=?'); $q->execute([$id]); $poll=$q->fetch();
        if (!$poll) Response::error('not_found','Poll not found.',404);
        if ($poll['status'] !== 'open') Response::error('poll_closed','This poll is closed.',409);
        $ids=array_values(array_unique(array_map('intval',is_array($in['option_ids']??null)?$in['option_ids']:[])));
        if (!$ids) Response::error('validation_failed','Choose at least one date.',422);
        $p=$this->db->prepare('SELECT id FROM global_poll_options WHERE poll_id=? AND id IN ('.implode(',',array_fill(0,count($ids),'?')).')');
        $p->execute(array_merge([$id],$ids)); if(count($p->fetchAll())!==count($ids)) Response::error('validation_failed','Choose a valid answer.',422);
        $this->db->beginTransaction();
        try { $d=$this->db->prepare('DELETE FROM global_poll_responses WHERE poll_id=? AND user_id=?');$d->execute([$id,$uid]);$i=$this->db->prepare('INSERT INTO global_poll_responses (poll_id,user_id,option_id) VALUES (?,?,?)');foreach($ids as $option)$i->execute([$id,$uid,$option]);$this->db->commit(); }
        catch(\Throwable){if($this->db->inTransaction())$this->db->rollBack();Response::error('server_error','Answer could not be saved.',500);}
        $this->current($uid);
    }
    public function listAdmin(int $uid): never { $q=$this->db->query('SELECT * FROM global_polls ORDER BY updated_at DESC');$rows=$q->fetchAll();foreach($rows as &$r)$r['options']=$this->options((int)$r['id']);Response::json(['data'=>$rows]); }
    public function create(int $uid,array $in): never { $title=trim((string)($in['title']??''));$options=array_values(array_filter(array_map(fn($x)=>trim((string)$x),$in['options']??[])));if($title===''||count($options)<2)Response::error('validation_failed','Title and two options are required.',422);$q=$this->db->prepare('INSERT INTO global_polls(title,explanation,choice_type,status,created_by) VALUES(?,?,?,?,?)');$q->execute([$title,trim((string)($in['explanation']??'')),'multiple','open',$uid]);$id=(int)$this->db->lastInsertId();$o=$this->db->prepare('INSERT INTO global_poll_options(poll_id,label,position) VALUES(?,?,?)');foreach($options as $i=>$label)$o->execute([$id,$label,$i+1]);$this->current($uid); }
    public function update(int $uid,int $id,array $in): never { $allowed=['title','explanation','status'];$set=[];$v=[];foreach($allowed as $f)if(array_key_exists($f,$in)){$set[]="$f=?";$v[]=$in[$f];}if(!$set)Response::error('validation_failed','No changes supplied.',422);$v[]=$id;$q=$this->db->prepare('UPDATE global_polls SET '.implode(',',$set).' WHERE id=?');$q->execute($v);$this->listAdmin($uid); }
    public function results(int $uid,int $id): never { $q=$this->db->prepare('SELECT o.id,o.label,COUNT(DISTINCT r.user_id) responses FROM global_poll_options o LEFT JOIN global_poll_responses r ON r.option_id=o.id AND r.poll_id=o.poll_id WHERE o.poll_id=? GROUP BY o.id,o.label,o.position ORDER BY o.position');$q->execute([$id]);$options=$q->fetchAll();$t=$this->db->prepare('SELECT COUNT(DISTINCT user_id) FROM global_poll_responses WHERE poll_id=?');$t->execute([$id]);Response::json(['data'=>['options'=>$options,'total_respondents'=>(int)$t->fetchColumn()]]); }
    private function latest(): ?array { $q=$this->db->query("SELECT * FROM global_polls ORDER BY (status='open') DESC, updated_at DESC LIMIT 1");$r=$q->fetch();return $r?:null; }
    private function options(int $id): array { $q=$this->db->prepare('SELECT id,label,position FROM global_poll_options WHERE poll_id=? ORDER BY position');$q->execute([$id]);return $q->fetchAll(); }
}
