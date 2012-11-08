<?php

if(isset($_GET['i']) && isset($_GET['m']) && isset($_GET['f'])){

$text = $_GET['i'];
$market = $_GET['m'];
$filename = $_GET['f'];

$img = 'markets/' . $market . '/' . $filename;

header('Content-type: image/png');
header("Cache-Control: private, max-age=10800, pre-check=10800");
header("Pragma: private");
header("Expires: " . date(DATE_RFC822,strtotime(" 2 day")));

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) 
       && 
  (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == filemtime($img))) {
  // send the last mod time of the file back
  header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($img)).' GMT', 
  true, 304);
  exit;
}


$im = imagecreatefromjpeg($img);

$size = getimagesize($img);

list($width,$height) = $size;

$x = $height;
$y = 5;

$black = imagecolorallocatealpha($im, 0, 0, 0,120);


$font = 'css/arial.ttf';

$strLength = strlen($text)*12;
$radians = atan($height/$width);
$degrees = $radians*(180/M_PI);
$hypotenuse = $height/sin($radians);

$newHypotenuse = ($hypotenuse-$strLength)/2;

$x = cos($radians)*$newHypotenuse;
$y = $height - (sin($radians)*$newHypotenuse);
imagettftext($im, 15, $degrees, $x, $y, $black, $font, $text);



header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($img)) . ' GMT');
imagepng($im);
imagedestroy($im);

}

?>