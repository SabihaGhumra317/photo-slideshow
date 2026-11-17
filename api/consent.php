<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
try {
    check_csrf(); $v=current_visitor();
    if (!$v) throw new RuntimeException('Please identify yourself first.');
    if (($_POST['consent'] ?? '')!=='1') throw new RuntimeException('Please agree to continue.');
    if (!active_consent((int)$v['id'])) {
        $s=db()->prepare('INSERT INTO consents(visitor_id,text_version,ip_hash) VALUES(?,?,?)');
        $s->execute([$v['id'],CONSENT_VERSION,ip_hash()]);
    }
    flash('Consent saved. You may submit photos for approval.');
} catch(Throwable $e) { flash($e->getMessage(),'error'); }
redirect('index.php');
