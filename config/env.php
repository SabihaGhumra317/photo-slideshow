<?php
declare(strict_types=1);
/** Read simple KEY=VALUE .env lines; no PHP/shell evaluation. */
function load_env(string $path): void {
    if (!is_readable($path)) {
        // One-time localhost-only, zero-edit WampServer configuration.
        // Never embed credentials or a fixed application secret in the repository.
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (PHP_SAPI !== 'cli' && !in_array($ip, ['127.0.0.1', '::1'], true)) {
            throw new RuntimeException('Initial setup is only allowed from localhost.');
        }
        $generated = "# Automatically generated for a local WampServer installation.\n"
            . "DB_HOST=127.0.0.1\nDB_NAME=photo_slideshow\nDB_USER=root\nDB_PASS=\n"
            . "APP_SECRET=" . bin2hex(random_bytes(32)) . "\n";
        $handle = @fopen($path, 'x'); // Exclusive creation avoids overwriting existing secrets.
        if ($handle !== false) {
            @chmod($path, 0600);
            fwrite($handle, $generated);
            fclose($handle);
        }
        if (!is_readable($path)) {
            throw new RuntimeException('Cannot create .env automatically. Give Apache write access to the project directory.');
        }
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line=trim($line);
        if ($line==='' || str_starts_with($line,'#') || !str_contains($line,'=')) continue;
        [$key,$val]=array_map('trim',explode('=',$line,2));
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/',$key)) continue;
        if (strlen($val)>=2 && (($val[0]==='"' && str_ends_with($val,'"')) || ($val[0]==="'" && str_ends_with($val,"'")))) $val=substr($val,1,-1);
        if (!isset($_ENV[$key])) $_ENV[$key]=$val;
    }
}
/** Return a secret/config value without printing it. */
function env_value(string $name, ?string $default=null): ?string {
    return $_ENV[$name] ?? (getenv($name) !== false ? (string)getenv($name) : $default);
}
load_env(dirname(__DIR__).'/.env');
