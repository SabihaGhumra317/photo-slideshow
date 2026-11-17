<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/admin.php'; require_admin();
$status=(string)($_GET['status'] ?? 'pending');
if (!in_array($status,['pending','approved','rejected','all'],true)) $status='pending';
$sql='SELECT p.*,v.display_name FROM photos p JOIN visitors v ON v.id=p.visitor_id';
if ($status!=='all') $sql.=' WHERE p.status=?';
$sql.=' ORDER BY p.created_at DESC,p.id DESC LIMIT 250';
$s=db()->prepare($sql); $s->execute($status==='all'?[]:[$status]); $photos=$s->fetchAll();
$counts=db()->query('SELECT status,COUNT(*) AS n FROM photos GROUP BY status')->fetchAll();
$stats=['pending'=>0,'approved'=>0,'rejected'=>0]; foreach($counts as $c) $stats[$c['status']]=(int)$c['n'];
$duration=(int)(db()->query("SELECT setting_value FROM settings WHERE setting_key='slide_duration_seconds'")->fetchColumn() ?: 8);
$days=(int)(db()->query("SELECT setting_value FROM settings WHERE setting_key='retention_days'")->fetchColumn() ?: 90);
require_once __DIR__.'/layout.php';
admin_page_start('Admin dashboard', $status, $stats['pending']);
?><section class="admin-title"><div><span class="eyebrow">ADMIN WORKSPACE / PHOTO REVIEW</span><h1>Welcome back, Admin <span aria-hidden="true">✦</span></h1><p class="muted">Review incoming visitor photos and decide what appears on the public screen.</p></div><div class="admin-verified"><span aria-hidden="true">●</span> Logged in as Administrator</div></section>
<div class="stats admin-stats"><div class="glass stat"><strong><?=$stats['pending']?></strong><span>Pending requests</span></div><div class="glass stat"><strong><?=$stats['approved']?></strong><span>Approved photos</span></div><div class="glass stat"><strong><?=$stats['rejected']?></strong><span>Rejected photos</span></div></div>
<section class="glass panel admin-panel" id="photo-requests"><div class="section-heading"><div><span class="eyebrow">REVIEW QUEUE</span><h2>Photo requests</h2></div><div class="tabs"><?php foreach(['pending','approved','rejected','all'] as $tab): ?><a class="tab <?=$tab===$status?'active':''?>" href="?status=<?=h($tab)?>"><?=ucfirst($tab)?></a><?php endforeach; ?></div></div>
<div class="photo-grid"><?php foreach($photos as $p): ?><article class="photo-card"><img src="<?=h(photo_url($p))?>" loading="lazy" alt="Pending photo by <?=h($p['display_name'])?>"><div class="photo-details"><strong><?=h($p['display_name'])?></strong><small><?=h($p['created_at'])?> · #<?= (int)$p['id']?></small><span class="pill <?=h($p['status'])?>"><?=h(ucfirst($p['status']))?><?=((int)$p['is_hidden']===1?' · Hidden':'')?></span></div><form method="post" action="action.php" class="action-row" onsubmit="return this.querySelector('button:focus')?.value !== 'delete' || confirm('Permanently delete photo?')"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="photo_id" value="<?= (int)$p['id']?>"><input type="hidden" name="return_status" value="<?=h($status)?>"><?php if ($p['status']!=='approved'): ?><button class="btn small" name="action" value="approve">Approve</button><?php endif; ?><?php if ($p['status']!=='rejected'): ?><button class="btn small secondary" name="action" value="reject">Reject</button><?php endif; ?><?php if ($p['status']==='approved'): ?><button class="btn small secondary" name="action" value="<?=((int)$p['is_hidden']===1?'unhide':'hide')?>"><?=((int)$p['is_hidden']===1?'Unhide':'Hide')?></button><?php endif; ?><button class="btn small danger" name="action" value="delete">Delete</button></form></article><?php endforeach; ?></div>
<?php if (!$photos): ?><p class="muted">No photos in this category.</p><?php endif; ?></section>
<section class="glass panel admin-panel" id="admin-settings"><span class="eyebrow">CONFIGURATION</span><h2>Slideshow settings</h2><form method="post" action="settings.php" class="settings-form"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><label>Seconds per photo <input type="number" name="duration" min="3" max="60" value="<?=$duration?>" required></label><label>Retention days <input type="number" name="retention" min="1" max="3650" value="<?=$days?>" required></label><button class="btn" type="submit">Save settings</button></form><p class="muted">Run scheduled cleanup to enforce retention; changing the setting alone does not delete files.</p></section>
<?php admin_page_end(); ?>
