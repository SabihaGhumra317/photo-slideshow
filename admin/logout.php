<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/admin.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
try { check_csrf(); unset($_SESSION['admin_id']); session_regenerate_id(true); flash('Logged out.'); }
catch(Throwable $e) { flash($e->getMessage(),'error'); }
redirect('admin/login.php');
