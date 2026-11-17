<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
$cases = [null => 'en'];
unset($_COOKIE['lang']);
assert(language() === 'en');
foreach (['en'=>'en','hi'=>'hi','gu'=>'gu','fr'=>'en',''=>'en'] as $input=>$expected) {
    $_COOKIE['lang'] = $input;
    if (language() !== $expected) throw new RuntimeException("Unexpected language for $input");
}
$_COOKIE['lang'] = ['invalid'];
if (language() !== 'en') throw new RuntimeException('Malformed language cookie not handled');
echo "Language fallback tests passed.\n";
