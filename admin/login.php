<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/admin.php';
if (admin_logged_in()) redirect('admin/index.php');
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        check_csrf();
        if (login_throttled()) throw new RuntimeException('Too many attempts. Try again in 15 minutes.');
        $username=trim((string)($_POST['username'] ?? '')); $pass=(string)($_POST['password'] ?? '');
        $s=db()->prepare('SELECT id,password_hash FROM admins WHERE username=?'); $s->execute([$username]); $a=$s->fetch();
        if (!$a || !password_verify($pass,$a['password_hash'])) { record_login_failure(); throw new RuntimeException('Invalid username or password.'); }
        session_regenerate_id(true); $_SESSION['admin_id']=(int)$a['id']; redirect('admin/index.php');
    } catch(Throwable $e) { flash($e->getMessage(),'error'); redirect('admin/login.php'); }
}
page_start('Admin login');
?><section class="glass panel narrow"><span class="eyebrow">ADMIN ACCESS</span><h1>Welcome back.</h1><p class="muted">Sign in to review photo requests.</p><form method="post" class="stack"><label for="user">Username</label><input id="user" name="username" autocomplete="username" required><label for="pass">Password</label><input id="pass" type="password" name="password" autocomplete="current-password" required><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><button class="btn">Sign in →</button></form></section><?php page_end(); ?>
