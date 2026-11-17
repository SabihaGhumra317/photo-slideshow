<?php
declare(strict_types=1);
/**
 * LOCAL CLI ONLY. Updates the `admin` account with a password provided via STDIN.
 * No passwords are saved in .env, BAT files, the PHP source, or command arguments.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script must run from the local command line.');
}
require_once dirname(__DIR__) . '/config/db.php';

fwrite(STDOUT, "Admin username: admin\nType new admin password, then press Enter: ");
$password = rtrim((string) fgets(STDIN), "\r\n");
if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters. No changes made.\n");
    exit(1);
}

try {
    $conn = db();
    $stmt = $conn->prepare(
        'INSERT INTO admins (username, password_hash) VALUES (:username, :hash) ' .
        'ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)'
    );
    $stmt->execute([
        'username' => 'admin',
        'hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    // Keep session invalidation in mind for later versions: old browser sessions
    // should be logged out before using the new password.
    fwrite(STDOUT, "Admin password updated successfully.\n");
} catch (Throwable $e) {
    error_log('Admin password reset failed: ' . $e->getMessage());
    fwrite(STDERR, "Failed to update password. Ensure WampServer MySQL is running and initial setup is complete.\n");
    exit(1);
}
