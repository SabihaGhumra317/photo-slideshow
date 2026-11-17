<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
/** Admin session flag, established only after password_verify. */
function admin_logged_in(): bool { return isset($_SESSION['admin_id']) && is_int($_SESSION['admin_id']); }
/** Redirect unauthorized requests to the admin login form. */
function require_admin(): void { if (!admin_logged_in()) redirect('admin/login.php'); }
/** Throttle password guesses by hashed client IP. */
function login_throttled(): bool {
    $s=db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_hash=? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $s->execute([ip_hash()]); return (int)$s->fetchColumn()>=8;
}
/** Save each failed login attempt for the rate limit. */
function record_login_failure(): void {
    $s=db()->prepare('INSERT INTO login_attempts(ip_hash) VALUES(?)'); $s->execute([ip_hash()]);
}
