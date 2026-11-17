<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';

// Logout is intentionally POST-only to prevent a link or image from signing out users.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}

try {
    check_csrf();
    clear_visitor_cookie();
    unset($_SESSION['visitor_code_once']);
    // Keep admin authentication independent of visitor logout.
    session_regenerate_id(true);
    flash(t('visitor_logged_out'));
} catch (Throwable $e) {
    flash('Logout failed: ' . $e->getMessage(), 'error');
}
redirect('index.php');
