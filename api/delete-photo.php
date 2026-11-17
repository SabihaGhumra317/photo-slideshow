<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
try {
    check_csrf(); $v=current_visitor(); if (!$v) throw new RuntimeException('Visitor identity required.');
    $id=filter_var($_POST['id'] ?? null,FILTER_VALIDATE_INT); if (!$id) throw new RuntimeException('Invalid photo.');
    $pdo=db(); $pdo->beginTransaction();
    $s=$pdo->prepare('SELECT stored_name FROM photos WHERE id=? AND visitor_id=? FOR UPDATE'); $s->execute([$id,$v['id']]);
    $p=$s->fetch(); if (!$p) throw new RuntimeException('Photo not found.');
    // Unlink before transaction commit so failed removal cannot leave a displayed photo.
    $file=dirname(__DIR__).'/uploads/'.$p['stored_name'];
    if (is_file($file) && !unlink($file)) throw new RuntimeException('Could not remove photo file.');
    $s=$pdo->prepare('DELETE FROM photos WHERE id=? AND visitor_id=?'); $s->execute([$id,$v['id']]);
    $pdo->commit(); flash('Photo deleted.');
} catch(Throwable $e) { if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack(); flash($e->getMessage(),'error'); }
redirect('my-photos.php');
