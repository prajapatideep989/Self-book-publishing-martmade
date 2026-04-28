<?php
session_start();

// Generate random string
$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Removed confusing O, 0, I, 1
$captcha_string = '';
for ($i = 0; $i < 6; $i++) {
    $captcha_string .= $characters[rand(0, strlen($characters) - 1)];
}

$_SESSION['captcha_code'] = $captcha_string;

// Create Image
$image = imagecreatetruecolor(120, 45);

// Colors
$background = imagecolorallocate($image, 243, 244, 246); // Matches your CSS --bg
$text_color = imagecolorallocate($image, 99, 102, 241);   // Matches your --primary
$noise_color = imagecolorallocate($image, 156, 163, 175);

imagefilledrectangle($image, 0, 0, 120, 45, $background);

// Add some random noise lines
for($i=0; $i<5; $i++) {
    imageline($image, 0, rand(0,45), 120, rand(0,45), $noise_color);
}

// Add some dots
for($i=0; $i<100; $i++) {
    imagesetpixel($image, rand(0,120), rand(0,45), $noise_color);
}

// Draw the text
// Note: This uses built-in fonts. For better look, you can use imagettftext() with a .ttf file
imagestring($image, 5, 25, 15, $captcha_string, $text_color);

header('Content-type: image/png');
imagepng($image);
imagedestroy($image);
?>