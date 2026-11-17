<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    // Server returns current approved state every poll; client compares IDs even if older images were approved late.
    $s=db()->query("SELECT p.id,p.stored_name,v.display_name,p.visitor_id,p.approved_at FROM photos p JOIN visitors v ON v.id=p.visitor_id JOIN consents c ON c.id=p.consent_id WHERE p.status='approved' AND p.is_hidden=0 AND c.withdrawn_at IS NULL ORDER BY p.approved_at ASC,p.id ASC LIMIT 500");
    $slides=[];
    foreach($s->fetchAll() as $p) $slides[]=['id'=>(int)$p['id'],'url'=>photo_url($p),'name'=>$p['display_name'],'visitor'=>(int)$p['visitor_id'],'approved_at'=>$p['approved_at']];
    $duration=(int)(db()->query("SELECT setting_value FROM settings WHERE setting_key='slide_duration_seconds'")->fetchColumn() ?: 8);
    echo json_encode(['slides'=>$slides,'duration'=>max(3,min(60,$duration)),'since'=>(string)($_GET['since'] ?? '')],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch(Throwable $e) { http_response_code(500); echo json_encode(['error'=>'Slides temporarily unavailable.']); }
