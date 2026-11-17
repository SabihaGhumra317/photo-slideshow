<?php
declare(strict_types=1);
require_once __DIR__.'/config/bootstrap.php';
$v=current_visitor();
page_start(t('my_photos'));
?><section class="glass panel"><h1><?=h(t('my_photos'))?></h1><?php if (!$v): ?><p>Please identify yourself or restore your visitor code on the upload page.</p><a class="btn" href="index.php">Go to upload</a><?php else:
$s=db()->prepare('SELECT * FROM photos WHERE visitor_id=? ORDER BY id DESC'); $s->execute([$v['id']]); $photos=$s->fetchAll(); ?>
<p><?=h(t('hello'))?>, <?=h($v['display_name'])?> · <?=count($photos)?>/20 photos</p><p class="muted">Pending photos are visible only to you and the admin. Deletion is permanent.</p>
<div class="photo-grid"><?php foreach($photos as $p): ?><article class="photo-card"><img src="<?=h(photo_url($p))?>" loading="lazy" alt="Your uploaded photo"><div class="card-foot"><span class="pill <?=h($p['status'])?>"><?=h(ucfirst($p['status']))?><?=((int)$p['is_hidden']===1?' · Hidden':'')?></span><form method="post" action="api/delete-photo.php" onsubmit="return confirm('Delete this photo permanently?')"><input type="hidden" name="id" value="<?= (int)$p['id']?>"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><button class="btn small danger" type="submit">Delete</button></form></div></article><?php endforeach; ?></div>
<?php if (!$photos): ?><p>No photos yet. <a href="index.php">Submit your first photo</a>.</p><?php endif; ?>
<hr><h2>Consent & privacy</h2><p>Withdrawing consent removes all of your photos permanently, including pending photos.</p><form method="post" action="api/withdraw.php" onsubmit="return confirm('Withdraw consent and permanently delete all your photos?')"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><button class="btn danger">Withdraw consent & delete photos</button></form>
<?php endif; ?></section><?php page_end(); ?>
