<?php
declare(strict_types=1);
/** Validate actual bytes; decode, scale and re-encode to remove EXIF and scripts. */
function process_image(string $tmp, int $size, string $destinationDir): array {
    if ($size<=0 || $size>5*1024*1024) throw new RuntimeException('Each image must be 5 MB or smaller.');
    if (!is_uploaded_file($tmp) && PHP_SAPI!=='cli') throw new RuntimeException('Invalid uploaded file.');
    $realMime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $map=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($map[$realMime])) throw new RuntimeException('Only JPG, PNG and WEBP photos are allowed.');
    $details=@getimagesize($tmp);
    if (!$details || ($details['mime'] ?? '')!==$realMime) throw new RuntimeException('This file is not a valid image.');
    [$w,$h]=$details;
    if ($w<1 || $h<1 || $w>12000 || $h>12000 || $w*$h>45000000) throw new RuntimeException('Image dimensions are too large.');
    $src=match($realMime) {
        'image/jpeg'=>@imagecreatefromjpeg($tmp),
        'image/png'=>@imagecreatefrompng($tmp),
        'image/webp'=>@imagecreatefromwebp($tmp),
    };
    if (!$src) throw new RuntimeException('Unable to decode this photo.');
    $scale=min(1,1920/max($w,$h)); $nw=max(1,(int)round($w*$scale)); $nh=max(1,(int)round($h*$scale));
    $target=imagecreatetruecolor($nw,$nh);
    if (!$target) { imagedestroy($src); throw new RuntimeException('Could not process image.'); }
    if ($realMime!=='image/jpeg') {
        imagealphablending($target,false); imagesavealpha($target,true);
        $clear=imagecolorallocatealpha($target,0,0,0,127);
        imagefilledrectangle($target,0,0,$nw,$nh,$clear);
    }
    if (!imagecopyresampled($target,$src,0,0,0,0,$nw,$nh,$w,$h)) {
        imagedestroy($src); imagedestroy($target); throw new RuntimeException('Could not resize photo.');
    }
    imagedestroy($src);
    $filename=bin2hex(random_bytes(20)).'.'.$map[$realMime];
    $path=rtrim($destinationDir,'/\\').DIRECTORY_SEPARATOR.$filename;
    $ok=match($realMime) {
        'image/jpeg'=>imagejpeg($target,$path,85),
        'image/png'=>imagepng($target,$path,7),
        'image/webp'=>imagewebp($target,$path,83),
    };
    imagedestroy($target);
    if (!$ok || !is_file($path)) throw new RuntimeException('Could not save processed photo.');
    return ['stored_name'=>$filename,'mime_type'=>$realMime,'bytes'=>filesize($path),'width'=>$nw,'height'=>$nh,'path'=>$path];
}
