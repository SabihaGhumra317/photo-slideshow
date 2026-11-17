<?php
declare(strict_types=1);
// Run ONCE using PHP CLI; cannot execute through the web browser.
if (PHP_SAPI!=='cli') { http_response_code(403); exit('CLI only'); }
require_once dirname(__DIR__).'/config/db.php';
$username='admin'; $password=env_value('ADMIN_DEFAULT_PASSWORD');
if (!$password || strlen($password)<10 || str_starts_with($password,'replace_')) {
    exit("Set ADMIN_DEFAULT_PASSWORD (10+ characters) in .env first.\n");
}
$sql='INSERT INTO admins(username,password_hash) VALUES(?,?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)';
$stmt=db()->prepare($sql); $stmt->execute([$username,password_hash($password,PASSWORD_DEFAULT)]);
echo "Admin username 'admin' seeded/reset with hashed password.\n";
