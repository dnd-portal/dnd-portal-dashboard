<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/Config.php'; require dirname(__DIR__).'/src/Database/Connection.php';
\DndPortal\Config::loadDotEnv(dirname(__DIR__).'/.env');
if(\DndPortal\Config::env('APP_ENV')!=='local'||\DndPortal\Config::env('DB_NAME')!=='dnd_portal_v4_development')die("Refused: V4 development database required.\n");
$db=\DndPortal\Database\Connection::make(); $db->beginTransaction();
try{
 $role=function(string $key)use($db){$q=$db->prepare('SELECT id FROM roles WHERE role_key=?');$q->execute([$key]);return (int)$q->fetchColumn();};
 $user=function(string $u,string $e,string $n,string $p)use($db){$q=$db->prepare('SELECT id FROM users WHERE username=?');$q->execute([$u]);$id=$q->fetchColumn();if($id===false){$db->prepare('INSERT INTO users(display_name,email,username,account_status) VALUES(?,?,?,"active")')->execute([$n,$e,$u]);$id=$db->lastInsertId();$db->prepare('INSERT INTO user_credentials(user_id,password_hash,password_algo) VALUES(?,?,?)')->execute([$id,password_hash($p,PASSWORD_DEFAULT),PASSWORD_DEFAULT]);}return (int)$id;};
 $admin=$user('v4-admin','v4-admin@example.test','V4 Admin','V4AdminLocal2026!');$normal=$user('v4-player','v4-player@example.test','V4 Player','V4PlayerLocal2026!');
 foreach([$admin=>'portal_admin',$normal=>'campaign_member'] as $id=>$key)$db->prepare('INSERT IGNORE INTO user_roles(user_id,role_id) VALUES(?,?)')->execute([$id,$role($key)]);
 $q=$db->prepare('SELECT id FROM users WHERE username=?');$q->execute(['v4-pending']);$pending=$q->fetchColumn();if($pending===false){$db->prepare('INSERT INTO users(display_name,account_status) VALUES("Pending V4 User","invited")')->execute([]);$pending=$db->lastInsertId();}
 $invite=function(?int $uid,string $code)use($db,$admin){$db->prepare('INSERT IGNORE INTO registration_invites(invited_user_id,code_hash,created_by,expires_at) VALUES(?,?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 DAY))')->execute([$uid,hash('sha256',$code,true),$admin]);};$invite((int)$pending,'V4-TYPE-A-INVITE');$invite(null,'V4-TYPE-B-INVITE');
 $q=$db->query("SELECT id FROM global_polls WHERE title='V4 global poll'");if($q->fetchColumn()===false){$db->prepare('INSERT INTO global_polls(title,explanation,choice_type,status,created_by) VALUES("V4 global poll","Choose a date that works for you.","single","open",?)')->execute([$admin]);$id=$db->lastInsertId();$o=$db->prepare('INSERT INTO global_poll_options(poll_id,label,position) VALUES(?,?,?)');foreach(['Thursday 29 October 2026','Friday 30 October 2026','Saturday 31 October 2026'] as $i=>$label)$o->execute([$id,$label,$i+1]);}
 $db->commit();echo "SEEDED V4. Type A invite: V4-TYPE-A-INVITE; Type B invite: V4-TYPE-B-INVITE\n";
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();fwrite(STDERR,$e->getMessage()."\n");exit(1);}
