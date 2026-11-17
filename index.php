<?php
declare(strict_types=1);
require_once __DIR__.'/config/bootstrap.php';
$v=current_visitor();
page_start(t('welcome'));
?>
<section class="hero"><span class="eyebrow">✦ YOUR MOMENTS, ON THE BIG SCREEN</span><h1><?=h(t('hero'))?> <span class="gradient-text">PhotoFlow.</span></h1><p><?=h(t('intro'))?></p></section>
<div class="grid-two"><section class="glass panel"><h2><?=h(t('upload'))?></h2><p class="muted"><?=h(t('approval_note'))?></p>
<?php if (!$v): ?>
<form method="post" action="api/identify.php" class="stack">
<label for="display_name"><?=h(t('name'))?></label><input id="display_name" name="display_name" maxlength="80" required placeholder="Your name">
<label class="check"><input type="checkbox" name="consent" value="1" required><span><?=h(t('consent_text'))?></span></label>
<input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><button class="btn" type="submit"><?=h(t('start'))?></button>
</form>
<?php else: ?>
<p class="visitor-greet"><?=h(t('hello'))?>, <strong><?=h($v['display_name'])?></strong> 👋</p>
<?php if (!active_consent((int)$v['id'])): ?>
<form method="post" action="api/consent.php"><label class="check"><input type="checkbox" required name="consent" value="1"><span><?=h(t('consent_text'))?></span></label><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><button class="btn"><?=h(t('agree'))?></button></form>
<?php else: ?>
<?php $maxBatch = visitor_upload_limit($v); ?>
<form id="uploadForm" data-max-files="<?= $maxBatch ?>" action="api/upload.php" method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
<label for="files" class="drop-zone" id="dropZone" tabindex="0"><span class="drop-icon">⇧</span><strong><?=h(t('drop'))?></strong><small>JPG / PNG / WEBP · 5 MB each · <?= $maxBatch === 3 ? 'First upload: up to 3 photos' : 'Returning visitor: 1 photo per request' ?></small></label>
<input class="sr-only" id="files" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" <?= $maxBatch === 3 ? 'multiple' : '' ?> required>
<div id="previews" class="previews" aria-live="polite"></div>
<div id="progressBox" hidden><div class="progress-track"><div id="progressFill"></div></div><output id="progressText">0%</output></div>
<button class="btn" type="submit"><?=h(t('send_request'))?></button><div id="uploadResult" role="status" aria-live="polite"></div>
</form>
<?php endif; ?>
<?php endif; ?>
</section><aside class="glass panel side"><h2>✦ <?=h(t('how_it_works'))?></h2><div class="step"><b>01</b><span><?=h(t('step_one'))?></span></div><div class="step"><b>02</b><span><?=h(t('step_two'))?></span></div><div class="step"><b>03</b><span><?=h(t('step_three'))?></span></div>
<?php if (!$v): ?><hr><h3><?=h(t('returning'))?></h3><form action="api/restore.php" method="post" class="stack"><label for="visitor_code"><?=h(t('code'))?></label><input id="visitor_code" name="visitor_code" maxlength="6" minlength="6" pattern="[A-Za-z0-9]{6}" required autocomplete="off" placeholder="ABC123"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><button class="btn secondary"><?=h(t('restore'))?></button></form><?php endif; ?>
</aside></div>
<script src="assets/js/upload.js" defer></script>
<?php page_end(); ?>
