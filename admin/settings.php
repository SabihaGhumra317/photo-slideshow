<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/admin.php'; require_admin();
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
try {
    check_csrf(); $duration=filter_var($_POST['duration'] ?? null,FILTER_VALIDATE_INT);
    $days=filter_var($_POST['retention'] ?? null,FILTER_VALIDATE_INT);
    if ($duration===false || $duration<3 || $duration>60 || $days===false || $days<1 || $days>3650) throw new RuntimeException('Invalid settings values.');
    $s=db()->prepare('UPDATE settings SET setting_value=? WHERE setting_key=?');
    $s->execute([(string)$duration,'slide_duration_seconds']); $s->execute([(string)$days,'retention_days']);
    flash('Settings updated.');
} catch(Throwable $e) { flash($e->getMessage(),'error'); }
redirect('admin/index.php');
