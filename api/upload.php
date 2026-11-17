<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/bootstrap.php';
require_once dirname(__DIR__).'/config/image.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); echo json_encode(['error'=>'POST required']); exit; }
$written=[]; $pdo=null;
try {
    check_csrf(); $v=current_visitor();
    if (!$v) throw new RuntimeException('Sign in or restore your visitor identity first.');
    $consent=active_consent((int)$v['id']);
    if (!$consent) throw new RuntimeException('Consent is required before uploading.');
    if (recent_attempts(ip_hash())>=12) throw new RuntimeException('Too many upload attempts. Try again in 15 minutes.');
    record_attempt((int)$v['id']);
    if (!isset($_FILES['photos']['name']) || !is_array($_FILES['photos']['name'])) throw new RuntimeException('Select photos to upload.');
    $count=count($_FILES['photos']['name']);
    if ($count<1 || $count>3) throw new RuntimeException('Select no more than 3 photos in one request.');
    foreach ($_FILES['photos']['error'] as $err) {
        if ($err!==UPLOAD_ERR_OK) throw new RuntimeException(match($err) {
            UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE=>'Photo exceeds the server upload limit.',
            UPLOAD_ERR_PARTIAL=>'A photo uploaded only partially.',
            UPLOAD_ERR_NO_FILE=>'Choose at least one photo.',
            default=>'Upload failed. Try again.'
        });
    }
    $pdo=db(); $pdo->beginTransaction();
    // Lock the visitor row to serialize concurrent requests against the 20-photo quota.
    $lock=$pdo->prepare('SELECT id, last_seen_at FROM visitors WHERE id=? FOR UPDATE'); $lock->execute([$v['id']]);
    $lockedVisitor=$lock->fetch();
    if (!$lockedVisitor) throw new RuntimeException('Visitor not found.');
    $batchLimit=visitor_upload_limit($lockedVisitor);
    if ($count>$batchLimit) throw new RuntimeException($batchLimit===3
        ? 'First upload allows a maximum of 3 photos.'
        : 'You can upload only 1 photo at a time after your first upload or when returning.');
    $quota=$pdo->prepare('SELECT COUNT(*) FROM photos WHERE visitor_id=?'); $quota->execute([$v['id']]);
    if ((int)$quota->fetchColumn()+$count>20) throw new RuntimeException('Maximum 20 photos per visitor. Delete older photos first.');
    $insert=$pdo->prepare('INSERT INTO photos(visitor_id,consent_id,stored_name,mime_type,bytes,width,height,status) VALUES(?,?,?,?,?,?,?,\'pending\')');
    for ($i=0;$i<$count;$i++) {
        $item=process_image((string)$_FILES['photos']['tmp_name'][$i],(int)$_FILES['photos']['size'][$i],dirname(__DIR__).'/uploads');
        $written[]=$item['path'];
        $insert->execute([$v['id'],$consent['id'],$item['stored_name'],$item['mime_type'],$item['bytes'],$item['width'],$item['height']]);
    }
    // Record first successful upload *inside* the same transaction.
    // This makes the first-time allowance race-safe across simultaneous requests.
    record_attempt((int)$v['id'],true);
    $pdo->commit();
    echo json_encode(['ok'=>true,'message'=>"{$count} photo(s) submitted for admin approval.",'count'=>$count,'next_max_files'=>1]);
} catch(Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    foreach ($written as $path) if (is_file($path)) unlink($path);
    http_response_code($e instanceof PDOException ? 500 : 422);
    echo json_encode(['ok'=>false,'error'=>$e instanceof PDOException ? 'Database error. Please retry.' : $e->getMessage()]);
}
