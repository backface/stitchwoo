<?php

$a = parse_url($_GET["a"]);
$b = parse_url($_GET["b"]);
$c = parse_url($_GET["c"]);

if (!isset($_SERVER["HTTP_REFERER"]))
	die("ERR");

$address = $_SERVER["SERVER_NAME"];	
if (strpos($address, $_SERVER["HTTP_REFERER"]) < 0) {
	die("NOT ALLOWD");
}

if (!strpos($a["path"],"wp-content/uploads/"))
	die ("ERR");
if (!strpos($b["path"],"wp-content/uploads/"))
	die ("ERR");
if (!strpos($c["path"],"wp-content/uploads/"))
	die ("ERR");
	
$dest = imagecreatefrompng(".".$a["path"]);
$src = imagecreatefrompng(".".$b["path"]);
$final = ".".$c["path"];

$bag =  strpos($a["path"], "bag");

$white = imagecolorexact($src, 255, 255, 255);
$trans = imagecolortransparent($src,$white);

imagealphablending($dest, false);
imagesavealpha($dest, true);

list($width, $height, $type, $attr) = getimagesize(".".$b["path"]);
//$scale = min(150.0/$width, 125.0/$height);
$scale = 0.3;
$new_w = $width* $scale;
$new_h = $height* $scale;

$rgb = array(233,233,233);
$rgb = array(255-$rgb[0],255-$rgb[1],255-$rgb[2]);
imagefilter($src, IMG_FILTER_NEGATE); 
imagefilter($src, IMG_FILTER_COLORIZE, $rgb[0], $rgb[1], $rgb[2]); 
imagefilter($src, IMG_FILTER_NEGATE); 
imagealphablending( $src, false );

if ($bag)
	imagecopyresampled($dest, $src, 287-$new_w/2, 275-$new_h/2, 0, 0, $new_w, $new_h, $width, $height);
else
	imagecopyresampled($dest, $src, 287-$new_w/2, 200-$new_h/2, 0, 0, $new_w, $new_h, $width, $height);

imagepng($dest,$final);
//$im = file_get_contents($final);
//echo base64_encode($im);
echo "OK";

$new = imagecreate(150,150);
imagecopyresampled($new, $dest, 0, 0, 150, 150, 0, 0, 150, 150);
imagepng($dest, substr($final,0, strlen($final)-4) . "-150x150.png");
$new = imagecreate(180,152);
imagecopyresampled($new, $dest, 0, 0, 180, 152, 0, 0, 180, 152);
imagepng($dest, substr($final,0,strlen($final)-4) . "-180x152.png");
$new = imagecreate(300,253);
imagecopyresampled($new, $dest, 0, 0, 300, 253, 0, 0, 300, 253);
imagepng($dest, substr($final,0,strlen($final)-4) . "-300x253.png");

//header("Content-Type: image/png");
//imagepng($dest);

imagedestroy($dest);
imagedestroy($src);
?>
