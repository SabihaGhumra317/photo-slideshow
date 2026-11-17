<?php
declare(strict_types=1);
// Run: php tests/test-file-validation.php
if (!extension_loaded('gd') || !extension_loaded('fileinfo')) { fwrite(STDERR,"SKIP: GD and Fileinfo required\n"); exit(0); }
require_once dirname(__DIR__).'/config/image.php';
$tmp=tempnam(sys_get_temp_dir(),'pf_');
file_put_contents($tmp,'not an image');
try { process_image($tmp,strlen('not an image'),sys_get_temp_dir()); throw new RuntimeException('FAIL: invalid file accepted'); }
catch(RuntimeException $e) { if (str_starts_with($e->getMessage(),'FAIL')) throw $e; echo "PASS: invalid MIME rejected\n"; }
unlink($tmp);
$img=imagecreatetruecolor(25,25); $tmp=tempnam(sys_get_temp_dir(),'pf_'); imagepng($img,$tmp); imagedestroy($img);
$encoded=process_image($tmp,filesize($tmp),sys_get_temp_dir());
if (!is_file($encoded['path']) || $encoded['width']!==25 || $encoded['mime_type']!=='image/png') throw new RuntimeException('FAIL: valid PNG');
unlink($encoded['path']); unlink($tmp); echo "PASS: PNG decoded, resized and re-encoded\n";
