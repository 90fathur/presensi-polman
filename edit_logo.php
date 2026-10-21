<?php
$src = imagecreatefrompng('AndroidSource/app/src/main/res/drawable/logonew.png');
$w = imagesx($src);
$h = imagesy($src);

// Let's create a new image and fill it with white
$dst = imagecreatetruecolor($w, $h);
$white = imagecolorallocate($dst, 255, 255, 255);
imagefill($dst, 0, 0, $white);
imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);

// We need a font
$font = 'C:\Windows\Fonts\arialbd.ttf';
if (!file_exists($font)) {
    $font = 'C:\Windows\Fonts\arial.ttf';
}

// Find where the text is. The text is probably in the lower third.
// Since I can't see it, I'll just draw a white rectangle over the bottom 25% of the image.
imagefilledrectangle($dst, 0, intval($h * 0.75), $w, $h, $white);

// Write "SMART PRESENSI 2.0"
$text = "SMART PRESENSI 2.0";
$textColor = imagecolorallocate($dst, 30, 30, 30); // Dark gray
$fontSize = intval($w * 0.05); // 5% of width
$bbox = imagettfbbox($fontSize, 0, $font, $text);
$textWidth = $bbox[2] - $bbox[0];
$x = ($w - $textWidth) / 2;
$y = intval($h * 0.85); // 85% down

imagettftext($dst, $fontSize, 0, $x, $y, $textColor, $font, $text);

imagepng($dst, 'AndroidSource/app/src/main/res/drawable/logonew_smart.png');
echo "Image generated.\n";
