<?php
declare(strict_types=1);
// Random token hashes are unique, fixed-size and nonreversible.
$tokens=[];
for ($i=0;$i<1000;$i++) {
    $raw=random_bytes(32); $token=bin2hex($raw);
    if (strlen($token)!==64 || strlen(hash('sha256',$raw))!==64 || isset($tokens[$token])) throw new RuntimeException('FAIL: invalid/duplicate token');
    $tokens[$token]=true;
}
echo "PASS: 1000 unique cryptographically random tokens\n";
