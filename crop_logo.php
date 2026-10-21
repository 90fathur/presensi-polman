<?php
$src = imagecreatefrompng('AndroidSource/app/src/main/res/drawable/logonew.png');
$w = imagesx($src);
$h = imagesy($src);

// We'll crop the top 78% of the image (removes the bottom text)
$new_h = intval($h * 0.78);

$dst = imagecreatetruecolor($w, $new_h);
// Make it transparent
imagesavealpha($dst, true);
$transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
imagefill($dst, 0, 0, $transparent);

// Copy the top part
imagecopy($dst, $src, 0, 0, 0, 0, $w, $new_h);

imagepng($dst, 'AndroidSource/app/src/main/res/drawable/logonew_smart.png');
echo "Cropped image saved to logonew_smart.png\n";
