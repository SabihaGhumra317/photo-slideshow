<?php
declare(strict_types=1);
require_once __DIR__.'/db.php';
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode','1');
    ini_set('session.cookie_httponly','1');
    ini_set('session.cookie_samesite','Lax');
    if (session_status()!==PHP_SESSION_ACTIVE) session_start([
        'cookie_httponly'=>true,'cookie_samesite'=>'Lax',
        'cookie_secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off',
    ]);
}
const APP_ROOT='/photo-slideshow';
// Redirect unconfigured local installations to one-time automatic setup.
// Normal visitors, admins, and API calls cannot use the app before tables exist.
if (PHP_SAPI !== 'cli' && basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'setup.php') {
    try {
        $ready = db()->query("SHOW TABLES LIKE 'admins'")->fetchColumn();
        $count = $ready ? (int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() : 0;
        if ($count === 0) {
            header('Location: /photo-slideshow/setup.php');
            exit;
        }
    } catch (PDOException $exception) {
        header('Location: /photo-slideshow/setup.php');
        exit;
    }
}

const CONSENT_VERSION='2026-11-v1';
/** Safe HTML escaping for all visitor-controlled strings. */
function h(?string $value): string { return htmlspecialchars($value ?? '',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
/** Secret must be explicitly configured, not a fallback literal. */
function secret(): string {
    $s=env_value('APP_SECRET');
    if (!$s || strlen($s)<32 || str_starts_with($s,'replace_')) throw new RuntimeException('Set APP_SECRET (32+ characters) in .env.');
    return $s;
}
/** Keyed IP digest avoids storing the original IP address. */
function ip_hash(): string { return hash_hmac('sha256',$_SERVER['REMOTE_ADDR'] ?? 'unknown',secret()); }
/** CSRF token is tied to this PHP session. */
function csrf_token(): string {
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
/** Fail a form request if its CSRF token is missing or incorrect. */
function check_csrf(): void {
    if (!isset($_POST['csrf']) || !hash_equals(csrf_token(),(string)$_POST['csrf'])) {
        http_response_code(403); throw new RuntimeException('Security check failed. Reload and try again.');
    }
}
/** Set messages for the next page request (post/redirect/get). */
function flash(string $message, string $type='ok'): void { $_SESSION['flash']=['message'=>$message,'type'=>$type]; }
/** Retrieve and clear a one-time message. */
function get_flash(): ?array { $f=$_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
/** Redirect only to an internal project-relative path. */
function redirect(string $path): never { header('Location: '.APP_ROOT.'/'.$path); exit; }
/** Create a cookie without allowing JavaScript access to the visitor credential. */
function visitor_cookie(string $raw): void {
    setcookie('visitor_token',$raw,[
        'expires'=>time()+365*24*3600,'path'=>APP_ROOT.'/',
        'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off',
        'httponly'=>true,'samesite'=>'Lax'
    ]);
}
/** Log out only the current visitor on this browser without deleting photos or consent. */
function clear_visitor_cookie(): void {
    // Match the cookie path and security attributes used by visitor_cookie().
    setcookie('visitor_token', '', [
        'expires' => time() - 3600,
        'path' => APP_ROOT . '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    // Also clear the value for this request so the visitor isn't still recognized.
    unset($_COOKIE['visitor_token']);
}
/** Read only a known, active visitor from the random cookie credential. */
function current_visitor(): ?array {
    $token=$_COOKIE['visitor_token'] ?? '';
    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/',$token)) return null;
    $s=db()->prepare('SELECT * FROM visitors WHERE token_hash=?');
    $s->execute([hash('sha256',hex2bin($token))]);
    return $s->fetch() ?: null;
}
/**
 * The first successful upload request of a newly registered visitor can contain
 * up to three photos. A restored identity, or one with any earlier upload,
 * can submit only one photo per request.
 *
 * In the upload handler call this while holding a FOR UPDATE lock on visitors,
 * so concurrent requests cannot both claim the first-time allowance.
 */
function visitor_upload_limit(array $visitor): int {
    if (!empty($visitor['last_seen_at'])) return 1;
    $stmt = db()->prepare('SELECT EXISTS(SELECT 1 FROM photos WHERE visitor_id=?) OR EXISTS(SELECT 1 FROM upload_attempts WHERE visitor_id=? AND successful=1)');
    $stmt->execute([(int)$visitor['id'], (int)$visitor['id']]);
    return (int)$stmt->fetchColumn() === 1 ? 1 : 3;
}
/** A code can restore identity only when presented to a rate-limited handler. */
function code_hash(string $code): string { return hash_hmac('sha256',strtoupper($code),secret()); }
/** Return the visitor's current active consent record, if any. */
function active_consent(int $id): ?array {
    $s=db()->prepare('SELECT * FROM consents WHERE visitor_id=? AND withdrawn_at IS NULL ORDER BY id DESC LIMIT 1');
    $s->execute([$id]); return $s->fetch() ?: null;
}
/** Request counter for throttling uploads or visitor-code guesses. */
function recent_attempts(string $hash, int $minutes=15): int {
    $s=db()->prepare('SELECT COUNT(*) FROM upload_attempts WHERE ip_hash=? AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)');
    $s->execute([$hash,$minutes]); return (int)$s->fetchColumn();
}
/** Record an attempt, successful or unsuccessful, for throttling. */
function record_attempt(?int $visitorId=null, bool $ok=false): void {
    $s=db()->prepare('INSERT INTO upload_attempts(visitor_id,ip_hash,successful) VALUES(?,?,?)');
    $s->execute([$visitorId,ip_hash(),(int)$ok]);
}
/** Current language from a checked allowlist; never include arbitrary paths. */
function language(): string {
    // A missing, malformed or unsupported language cookie always falls back to English.
    $selected = $_COOKIE['lang'] ?? 'en';
    return is_string($selected) && in_array($selected, ['en', 'hi', 'gu'], true)
        ? $selected
        : 'en';
}
/** Translate an interface key using the selected PHP dictionary. */
function t(string $key): string {
    static $catalogues=[];
    $lang=language();
    if (!isset($catalogues[$lang])) $catalogues[$lang]=require dirname(__DIR__).'/lang/'.$lang.'.php';
    return $catalogues[$lang][$key] ?? $key;
}
/** HTML header with shared language and theme controls. */
function page_start(string $title): void {
    $lang=language(); $visitor=current_visitor(); $flash=get_flash();
    echo '<!doctype html><html lang="'.h($lang).'"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="dark light"><title>'.h($title).' • PhotoFlow</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="'.APP_ROOT.'/assets/css/style.css"></head><body><div class="mesh"></div><header class="site-header"><a class="brand" href="'.APP_ROOT.'/index.php">◈ PhotoFlow</a><nav aria-label="Main navigation"><a href="'.APP_ROOT.'/index.php">'.h(t('upload')).'</a><a href="'.APP_ROOT.'/my-photos.php">'.h(t('my_photos')).'</a><a href="'.APP_ROOT.'/privacy.php">'.h(t('privacy')).'</a><a href="'.APP_ROOT.'/display.php">'.h(t('display')).'</a><a href="'.APP_ROOT.'/admin/index.php">Admin</a></nav>'.($visitor ? '<form class="visitor-logout-form" action="'.APP_ROOT.'/visitor-logout.php" method="post"><input type="hidden" name="csrf" value="'.h(csrf_token()).'"><button class="visitor-logout-btn" type="submit">'.h(t('visitor_logout')).'</button></form>' : '').'<div class="controls"><button type="button" class="icon-btn" id="themeToggle" aria-label="Switch colour theme">☀/☾</button><label for="lang" class="sr-only">Language</label><select id="lang" aria-label="Language"><option value="en"'.($lang==='en'?' selected':'').'>English</option><option value="hi"'.($lang==='hi'?' selected':'').'>हिन्दी</option><option value="gu"'.($lang==='gu'?' selected':'').'>ગુજરાતી</option></select></div></header><main class="main">';
    if ($flash) echo '<div role="status" class="alert '.($flash['type']==='error'?'error':'').'">'.h($flash['message']).'</div>';
}
/** Shared footer, including theme and language JS. */
function page_end(): void { echo '</main><footer class="footer">PhotoFlow · Consent-first community slideshow</footer><script src="'.APP_ROOT.'/assets/js/app.js" defer></script></body></html>'; }
/** Escape a photo identifier into a safe local URL. */
function photo_url(array $row): string { return APP_ROOT.'/uploads/'.rawurlencode($row['stored_name']); }
