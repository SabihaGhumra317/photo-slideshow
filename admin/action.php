<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/admin.php'; require_admin();
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
$return=(string)($_POST['return_status'] ?? 'pending'); if (!in_array($return,['pending','approved','rejected','all'],true)) $return='pending';
try {
    check_csrf(); $id=filter_var($_POST['photo_id'] ?? null,FILTER_VALIDATE_INT);
    $action=(string)($_POST['action'] ?? '');
    if (!$id || !in_array($action,['approve','reject','hide','unhide','delete'],true)) throw new RuntimeException('Invalid request.');
    $pdo=db(); $pdo->beginTransaction();
    $s=$pdo->prepare('SELECT * FROM photos WHERE id=? FOR UPDATE'); $s->execute([$id]); $p=$s->fetch();
    if (!$p) throw new RuntimeException('Photo no longer exists.');
    if ($action==='delete') {
        $file=dirname(__DIR__).'/uploads/'.$p['stored_name'];
        if (is_file($file) && !unlink($file)) throw new RuntimeException('Could not delete the photo file.');
        $s=$pdo->prepare('DELETE FROM photos WHERE id=?'); $s->execute([$id]);
    } elseif ($action==='approve') {
        $s=$pdo->prepare("UPDATE photos SET status='approved',is_hidden=0,approved_at=NOW(6) WHERE id=?"); $s->execute([$id]);
    } elseif ($action==='reject') {
        $s=$pdo->prepare("UPDATE photos SET status='rejected',approved_at=NULL WHERE id=?"); $s->execute([$id]);
    } else {
        if ($p['status']!=='approved') throw new RuntimeException('Only approved photos can be hidden/unhidden.');
        $s=$pdo->prepare('UPDATE photos SET is_hidden=? WHERE id=?'); $s->execute([$action==='hide'?1:0,$id]);
    }
    $pdo->commit(); flash('Photo '.($action==='delete'?'deleted':$action.'d').'.');
} catch(Throwable $e) { if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack(); flash($e->getMessage(),'error'); }
header('Location: '.APP_ROOT.'/admin/index.php?status='.rawurlencode($return)); exit;
