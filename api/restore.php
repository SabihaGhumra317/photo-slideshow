<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
try {
    check_csrf();
    $ip=ip_hash();
    if (recent_attempts($ip)>=5) throw new RuntimeException('Too many attempts. Please try again in 15 minutes.');
    record_attempt();
    $code=strtoupper(trim((string)($_POST['visitor_code'] ?? '')));
    if (!preg_match('/^[A-Z0-9]{6}$/',$code)) throw new RuntimeException('Enter a valid six-character code.');
    $s=db()->prepare('SELECT id FROM visitors WHERE code_hash=?'); $s->execute([code_hash($code)]);
    $row=$s->fetch();
    if (!$row) throw new RuntimeException('Code not recognized.');
    $raw=random_bytes(32); $token=bin2hex($raw);
    $s=db()->prepare('UPDATE visitors SET token_hash=?,last_seen_at=NOW() WHERE id=?');
    $s->execute([hash('sha256',$raw),$row['id']]);
    visitor_cookie($token); flash('Visitor identity restored. Previous browser sessions are signed out.');
} catch(Throwable $e) { flash('Restore failed: '.$e->getMessage(),'error'); }
redirect('index.php');
