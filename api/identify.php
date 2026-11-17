<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
try {
    check_csrf();
    $name=trim((string)($_POST['display_name'] ?? ''));
    if (preg_match('/[\x00-\x1F\x7F]/u',$name) || mb_strlen($name)>80 || mb_strlen($name)<2) throw new RuntimeException('Enter a name of 2–80 characters.');
    if (($_POST['consent'] ?? '')!=='1') throw new RuntimeException('Consent is required before uploading.');
    $raw=random_bytes(32); $token=bin2hex($raw);
    // 6 characters from an unambiguous 32-symbol alphabet; collision checked via DB UNIQUE.
    $alphabet='ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $pdo=db(); $done=false;
    for ($attempt=0;$attempt<8;$attempt++) {
        $code=''; for ($i=0;$i<6;$i++) $code.=$alphabet[random_int(0,strlen($alphabet)-1)];
        try {
            $pdo->beginTransaction();
            $s=$pdo->prepare('INSERT INTO visitors(display_name,token_hash,code_hash) VALUES(?,?,?)');
            $s->execute([$name,hash('sha256',$raw),code_hash($code)]);
            $id=(int)$pdo->lastInsertId();
            $s=$pdo->prepare('INSERT INTO consents(visitor_id,text_version,ip_hash) VALUES(?,?,?)');
            $s->execute([$id,CONSENT_VERSION,ip_hash()]);
            $pdo->commit(); $done=true; break;
        } catch(PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if (($e->errorInfo[1] ?? null)!==1062) throw $e;
        }
    }
    if (!$done) throw new RuntimeException('Could not issue visitor code. Try again.');
    visitor_cookie($token); $_COOKIE['visitor_token']=$token;
    $_SESSION['visitor_code_once']=$code;
    redirect('visitor-code.php');
} catch(Throwable $e) { flash($e instanceof PDOException ? 'Could not create visitor. Try again.' : $e->getMessage(),'error'); redirect('index.php'); }
