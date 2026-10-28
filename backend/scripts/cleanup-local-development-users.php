<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/Config.php';
\DndPortal\Config::loadDotEnv(dirname(__DIR__).'/.env');
require dirname(__DIR__).'/src/Database/Connection.php';
if(\DndPortal\Config::env('APP_ENV')!=='local'||\DndPortal\Config::env('DB_NAME')!=='dnd_portal_v4_development'){fwrite(STDERR,"Refused: local V4 development database required.\n");exit(2);}
$db=\DndPortal\Database\Connection::make();$db->beginTransaction();
try{
 $q=$db->prepare('SELECT id FROM users WHERE display_name=? AND email=? FOR UPDATE');$q->execute(['Toon van Berkel','admin@example.test']);$toon=$q->fetchColumn();if($toon===false)throw new RuntimeException('Target Toon van Berkel row not found.');$toon=(int)$toon;
 $q=$db->prepare('SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE u.username=? AND u.account_status="active" AND r.role_key="portal_admin" FOR UPDATE');$q->execute(['v4admin']);$admin=$q->fetchColumn();if($admin===false)throw new RuntimeException('Existing v4admin portal_admin row not found.');$admin=(int)$admin;if($admin===$toon)throw new RuntimeException('Target and v4admin rows unexpectedly match.');
 $q=$db->prepare('SELECT password_hash,password_algo FROM user_credentials WHERE user_id=? FOR UPDATE');$q->execute([$admin]);$credential=$q->fetch(PDO::FETCH_ASSOC);if(!$credential)throw new RuntimeException('Existing v4admin credential not found.');
 $db->prepare('DELETE FROM user_credentials WHERE user_id=?')->execute([$toon]);$db->prepare('INSERT INTO user_credentials(user_id,password_hash,password_algo,password_changed_at,failed_attempts,locked_until) VALUES(?,?,?,?,0,NULL)')->execute([$toon,$credential['password_hash'],$credential['password_algo'],date('Y-m-d H:i:s.u')]);
 $db->prepare('UPDATE users SET display_name=?,email=?,account_status="active" WHERE id=?')->execute(['Toon van Berkel','admin@example.test',$toon]);
 $role=$db->query("SELECT id FROM roles WHERE role_key='portal_admin'")->fetchColumn();$db->prepare('DELETE FROM user_roles WHERE user_id=?')->execute([$toon]);$db->prepare('INSERT INTO user_roles(user_id,role_id) VALUES(?,?)')->execute([$toon,(int)$role]);
 $ids=$db->query('SELECT id FROM users WHERE id<>'. $toon)->fetchAll(PDO::FETCH_COLUMN);$ph=implode(',',array_fill(0,count($ids),'?'));
 $del=function(string $sql)use($db,$ids){if($ids)$db->prepare($sql)->execute($ids);};
 $del('DELETE FROM auth_sessions WHERE user_id IN ('.$ph.')');$del('DELETE FROM password_reset_tokens WHERE user_id IN ('.$ph.')');$del('DELETE FROM user_availability WHERE user_id IN ('.$ph.')');$del('DELETE FROM session_participants WHERE user_id IN ('.$ph.')');$del('DELETE FROM campaign_memberships WHERE user_id IN ('.$ph.')');$del('DELETE FROM poll_votes WHERE user_id IN ('.$ph.')');$del('DELETE FROM poll_participants WHERE user_id IN ('.$ph.')');$del('DELETE FROM global_poll_responses WHERE user_id IN ('.$ph.')');$del('DELETE FROM payment_obligations WHERE user_id IN ('.$ph.')');$del('DELETE FROM audit_events WHERE actor_user_id IN ('.$ph.')');
 $db->exec('DELETE po FROM global_poll_options po JOIN global_polls p ON p.id=po.poll_id WHERE p.created_by<>'.$toon);$db->exec('DELETE FROM global_poll_responses WHERE poll_id IN (SELECT id FROM global_polls WHERE created_by<>'.$toon.')');$db->exec('DELETE FROM global_polls WHERE created_by<>'.$toon);
 $db->exec('DELETE FROM registration_invites WHERE created_by<>'.$toon.' OR invited_user_id<>'.$toon.' OR consumed_by_user_id IS NOT NULL AND consumed_by_user_id<>'.$toon);
 $db->exec('DELETE pe FROM payment_entries pe JOIN payment_obligations po ON po.id=pe.obligation_id JOIN payment_requests pr ON pr.id=po.payment_request_id WHERE pr.created_by<>'.$toon);$db->exec('DELETE po FROM payment_obligations po JOIN payment_requests pr ON pr.id=po.payment_request_id WHERE pr.created_by<>'.$toon);$db->exec('DELETE FROM payment_requests WHERE created_by<>'.$toon);
 $db->exec('DELETE sp FROM session_participants sp JOIN game_sessions gs ON gs.id=sp.session_id WHERE gs.created_by<>'.$toon);$db->exec('DELETE FROM game_sessions WHERE created_by<>'.$toon);
 $db->exec('DELETE FROM user_roles WHERE user_id<>'.$toon);$db->exec('DELETE FROM user_credentials WHERE user_id<>'.$toon);$db->exec('DELETE FROM auth_sessions');$db->exec("DELETE FROM rate_limit_buckets WHERE bucket_type='login'");$db->exec('DELETE FROM users WHERE id<>'.$toon);
 $remaining=(int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();$admins=(int)$db->query("SELECT COUNT(*) FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE u.account_status='active' AND r.role_key='portal_admin'")->fetchColumn();if($remaining!==1||$admins!==1)throw new RuntimeException('Post-cleanup account assertions failed.');
 $db->commit();echo "Local development users consolidated: 61 -> 1\n";
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();fwrite(STDERR,"Cleanup rolled back: ".$e->getMessage()."\n");exit(1);}
