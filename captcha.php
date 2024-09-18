<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/session.php');

// Set the content-type
header('Content-type: image/png');

// Set the captcha string excluding potentially confusing characters
$captcha_string = generateCaptchaString();

// Save the captcha string in the session for later verification
$_SESSION['captcha'] = $captcha_string;

// Generate captcha image
generateCaptchaImage($captcha_string);

function generateCaptchaString($length = 6)
{
    $characters = 'ABCDEFGHJKLMNPQRTUVWXYZ123456789'; // Excluding potentially confusing characters
    // $characters = 'ABCDEFGHJKLMNPQRTUVWXYZabcdefghjkmnopqrtuvwxyz2345678'; // Excluding potentially confusing characters
    $captcha_string = '';

    for ($i = 0; $i < $length; $i++) {
        $captcha_string .= $characters[rand(0, strlen($characters) - 1)];
    }

    return $captcha_string;
}

function generateCaptchaImage($captcha_string)
{
    // Create an image with a white background
    $width = 300;
    $height = 60;
    $image = imagecreatetruecolor($width, $height);

    if (!$image) {
        die('Unable to initialize GD image');
    }

    $bg_color = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $bg_color);

    // Add random lines as background abstraction
    for ($i = 0; $i < 10; $i++) {
        $line_color = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
        imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
    }

    // Add random dots as background abstraction
    for ($i = 0; $i < 100; $i++) {
        $dot_color = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
        imagesetpixel($image, rand(0, $width), rand(0, $height), $dot_color);
    }

    // Add the captcha text in the center with rotation
    $text_color = imagecolorallocate($image, 0, 0, 0);
    $font_size = 25;
    $font_path = $_SERVER['DOCUMENT_ROOT'] . '/assets/vendor/fonts/TEMPSITC.TTF';
    // $font_path = $_SERVER['DOCUMENT_ROOT'] . '/assets/vendor/fonts/Kindapix-Ke0A.ttf';

    if (!file_exists($font_path)) {
        die('Font file not found');
    }

    $text_length = strlen($captcha_string);
    $text_width = $text_length * ($font_size + 5); // Total width occupied by text

    // Calculate the X coordinate to place the text in the center
    $x = ($width - $text_width) / 2;

    // Calculate the Y coordinate to place the text in the vertical center
    $y = ($height + $font_size) / 2;

    for ($i = 0; $i < $text_length; $i++) {
        $angle = rand(-30, 30); // Random rotation angle between -30 and 30 degrees

        imagettftext($image, $font_size, $angle, $x, $y, $text_color, $font_path, $captcha_string[$i]);

        // Adjust the X coordinate for the next character
        $x += $font_size + 5;
    }

    // Output the image
    imagepng($image);
    imagedestroy($image);
}

?>
