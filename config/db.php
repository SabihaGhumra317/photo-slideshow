<?php
declare(strict_types=1);
require_once __DIR__.'/env.php';
/** Shared PDO connection with native prepared statements and exceptions. */
function db(): PDO {
    static $db=null;
    if ($db instanceof PDO) return $db;
    $host=env_value('DB_HOST'); $name=env_value('DB_NAME'); $user=env_value('DB_USER');
    if (!$host || !$name || !$user) throw new RuntimeException('Database settings missing in .env.');
    $db=new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4",$user,env_value('DB_PASS',''),[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false
    ]);
    return $db;
}
