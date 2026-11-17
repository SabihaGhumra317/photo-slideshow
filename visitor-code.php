<?php
declare(strict_types=1);
require_once __DIR__.'/config/bootstrap.php';
$code=$_SESSION['visitor_code_once'] ?? null; unset($_SESSION['visitor_code_once']);
page_start('Visitor Code');
?><section class="glass panel narrow"><span class="eyebrow">IDENTITY BACKUP</span><h1><?=h(t('code'))?></h1><?php if ($code): ?><p><?=h(t('save_code'))?></p><div class="visitor-code" aria-label="Visitor code"><?=h($code)?></div><p class="muted">Shown only once. Keep this private: anyone with the code can access your photos.</p><?php else: ?><p>The code is only displayed when your identity is first created.</p><?php endif; ?><a class="btn" href="index.php">Continue →</a></section><?php page_end(); ?>
