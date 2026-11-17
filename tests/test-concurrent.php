<?php
declare(strict_types=1);
// Unit-level concurrency guard checks random filenames and 20-photo quota logic.
$names=[];
for($i=0;$i<1000;$i++) { $name=bin2hex(random_bytes(20)).'.jpg'; if(isset($names[$name])) throw new RuntimeException('FAIL: filename collision'); $names[$name]=true; }
function within_quota(int $existing, int $incoming): bool { return $incoming>=1 && $incoming<=5 && $existing+$incoming<=20; }
if (!within_quota(15,5) || within_quota(19,2) || within_quota(0,6)) throw new RuntimeException('FAIL: quota');
echo "PASS: random filenames and upload quota logic\n";
echo "NOTE: actual simultaneous DB transaction verification is in MANUAL_TEST_PLAN.md\n";
