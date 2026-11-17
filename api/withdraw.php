<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
try {
    check_csrf(); $v=current_visitor(); if (!$v) throw new RuntimeException('Visitor identity required.');
    $pdo=db(); $pdo->beginTransaction();
    $s=$pdo->prepare('SELECT id FROM visitors WHERE id=? FOR UPDATE'); $s->execute([$v['id']]);
    $s=$pdo->prepare('SELECT id,stored_name FROM photos WHERE visitor_id=? FOR UPDATE'); $s->execute([$v['id']]);
    foreach ($s->fetchAll() as $p) {
        $file=dirname(__DIR__).'/uploads/'.$p['stored_name'];
        if (is_file($file) && !unlink($file)) throw new RuntimeException('Could not delete one of your photos.');
    }
    $s=$pdo->prepare('DELETE FROM photos WHERE visitor_id=?'); $s->execute([$v['id']]);
    $s=$pdo->prepare('UPDATE consents SET withdrawn_at=NOW() WHERE visitor_id=? AND withdrawn_at IS NULL'); $s->execute([$v['id']]);
    $pdo->commit(); flash('Consent withdrawn and all your photos removed.');
} catch(Throwable $e) { if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack(); flash($e->getMessage(),'error'); }
redirect('my-photos.php');
