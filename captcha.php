<?php
  // Start the session
  require_once('templates/startsession.php');

  // CAPTCHA constants
  define('CAPTCHA_WIDTH', 180);
  define('CAPTCHA_HEIGHT', 40);
  define('CAPTCHA_NUMCHARS', 6);
  define('CAPTCHA_LINES', 15);
  define('CAPTCHA_DOTS', 1000);

  // Generate the random passphrase
  $passphrase = "";
  for($i=0; $i<CAPTCHA_NUMCHARS; $i++) {
    $passphrase .= chr(rand(97, 122));
  }

  // Store the encrypted pass-phrase in a session variable
  $_SESSION['passphrase'] = SHA1($passphrase);

  // Create the image
  $img = imagecreatetruecolor(CAPTCHA_WIDTH, CAPTCHA_HEIGHT);

  // Set a white background with black text and graphics color
  $bg_color = imagecolorallocate($img, 255, 255, 255);
  $text_color = imagecolorallocate($img, 0, 0, 0);
  $graphic_color = imagecolorallocate($img, 34, 34, 34);

  // Fill the background
  imagefilledrectangle($img, 0, 0, CAPTCHA_WIDTH, CAPTCHA_HEIGHT, $bg_color);

  // Draw some random lines
  for ($i=0; $i<CAPTCHA_LINES; $i++) {
    imageline($img, 0, rand() % CAPTCHA_HEIGHT, CAPTCHA_WIDTH, rand() % CAPTCHA_HEIGHT, $graphic_color);
  }

  // Sprinkle in some random dots
  for ($i=0; $i<CAPTCHA_DOTS; $i++) {
    imagesetpixel($img, rand() % CAPTCHA_WIDTH, rand() % CAPTCHA_HEIGHT, $graphic_color);
  }

  // Draw the passphrase string
  imagettftext($img, 34, 0, 5, CAPTCHA_HEIGHT - 10, $text_color, 'Courier New Bold.ttf', $passphrase);

  // Output the image as a PNG using a header, then destroy it
  header("Content-type: image/png");
  imagepng($img);
  imagedestroy($img);
?>
