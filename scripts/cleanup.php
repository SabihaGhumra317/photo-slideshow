<?php
declare(strict_types=1);
// Windows Task Scheduler: C:\wamp64\bin\php\php8.x.x\php.exe scripts\cleanup.php
if (PHP_SAPI!=='cli') { http_response_code(403); exit('CLI only'); }
require_once dirname(__DIR__).'/config/db.php';
$days=(int)(db()->query("SELECT setting_value FROM settings WHERE setting_key='retention_days'")->fetchColumn() ?: 90);
$days=max(1,min($days,3650));
$stmt=db()->prepare('SELECT id,stored_name FROM photos WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)');
$stmt->execute([$days]); $photos=$stmt->fetchAll();
$del=db()->prepare('DELETE FROM photos WHERE id=?');
foreach($photos as $p) {
    $path=dirname(__DIR__).'/uploads/'.$p['stored_name'];
    if (is_file($path) && !unlink($path)) { error_log('Could not delete: '.$path); continue; }
    $del->execute([$p['id']]);
}
echo count($photos)." expired photo(s) checked.\n";
