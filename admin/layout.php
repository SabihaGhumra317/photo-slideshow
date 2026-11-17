<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/admin.php';
/** A dedicated admin-only layout, separate from the public visitor navigation. */
function admin_page_start(string $title, string $active, int $pendingCount = 0): void {
    $flash = get_flash();
    $links = [
        ['all','All photos','index.php?status=all','▦'],
        ['pending','Pending requests','index.php?status=pending','◷'],
        ['approved','Approved photos','index.php?status=approved','✓'],
        ['rejected','Rejected photos','index.php?status=rejected','×'],
        ['settings','Settings','index.php?status='.$active.'#admin-settings','⚙'],
    ];
    echo '<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="dark light"><title>'.h($title).' • PhotoFlow Admin</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="'.APP_ROOT.'/assets/css/style.css"><link rel="stylesheet" href="'.APP_ROOT.'/assets/css/admin.css"></head><body class="admin-body"><div class="mesh"></div><div class="admin-shell">';
    echo '<aside class="admin-sidebar" id="adminNav" aria-label="Admin navigation"><a class="admin-logo" href="'.APP_ROOT.'/admin/index.php"><span class="admin-logo-mark" aria-hidden="true">◈</span><span>PhotoFlow<small>MANAGEMENT PORTAL</small></span></a>';
    echo '<div class="admin-workspace-label">WORKSPACE</div><nav class="admin-side-links" aria-label="Manage photos">';
    foreach ($links as [$key,$label,$path,$symbol]) {
        $isActive = $key===$active;
        $count = $key==='pending' ? '<span class="admin-side-count">'.$pendingCount.'</span>' : '';
        echo '<a href="'.APP_ROOT.'/admin/'.h($path).'" class="admin-side-link'.($isActive?' is-active':'').'"'.($isActive?' aria-current="page"':'').'><span class="admin-side-icon" aria-hidden="true">'.h($symbol).'</span><span>'.h($label).'</span>'.$count.'</a>';
    }
    echo '</nav><div class="admin-sidebar-footer"><a href="'.APP_ROOT.'/index.php" target="_blank" rel="noopener noreferrer">↗ View visitor website</a><a href="'.APP_ROOT.'/display.php" target="_blank" rel="noopener noreferrer">↗ Open TV display</a><span class="admin-sidebar-caption">Protected administrator workspace</span></div></aside>';
    echo '<div class="admin-area"><header class="admin-topbar"><div class="admin-topbar-left"><button class="admin-menu-button" id="adminMenuButton" type="button" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle admin navigation">☰</button><span class="admin-top-label">PHOTO MODERATION</span><span class="admin-top-divider" aria-hidden="true"></span><strong>Admin Dashboard</strong></div><div class="admin-topbar-right"><span class="admin-role-pill"><span class="admin-role-dot"></span> Administrator</span><div class="admin-profile" aria-label="Signed in as admin"><span class="admin-avatar" aria-hidden="true">A</span><span><strong>admin</strong><small>System administrator</small></span></div><form method="post" action="'.APP_ROOT.'/admin/logout.php"><input type="hidden" name="csrf" value="'.h(csrf_token()).'"><button class="admin-signout" type="submit" aria-label="Log out of administrator account">Log out ↗</button></form></div></header><main class="admin-content" id="mainContent">';
    if ($flash) echo '<div role="status" class="alert '.($flash['type']==='error'?'error':'').'">'.h($flash['message']).'</div>';
}
/** Close the admin workspace and load the shared theme script and sidebar control. */
function admin_page_end(): void {
    echo '</main><footer class="admin-footer">PhotoFlow • Administrator access only</footer></div></div><script src="'.APP_ROOT.'/assets/js/app.js" defer></script><script src="'.APP_ROOT.'/assets/js/admin.js" defer></script></body></html>';
}
