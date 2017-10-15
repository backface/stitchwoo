<?php
//error_reporting(E_ALL);
//print_r($_POST);

// config
$DEST_DIR = "wp-content/uploads/patterns/";
$BASE_PRICE = 5;
$PRICE_PER_1K_STITCHES = 1.5;


// get post variables
$is_public = $_POST["public"] == "true";
$projectname = $_POST["projectname"];
$filename = $_POST["filename"];

// upload pattern file
$files = $_FILES['dstfile'];
$fn = uniqid();
$dest_file = $DEST_DIR . $fn . ".dst";
$png_file = $DEST_DIR . $fn . ".png";


// set private or public category
if ($is_public) {
	$term_id = 24;
} else {
	$term_id = 23;
}

// read meta data
$fp = fopen($files['tmp_name'],"r");
$headers = fread($fp,512);
fclose($fp);

$info["stitchcount"] =  intval(substr($headers,20,9));
$info["width"] =  (floatval(substr($headers,41,6)) - floatval(substr($headers,50,6))) / 10.0;
$info["height"] =  (floatval(substr($headers,59,6)) - floatval(substr($headers,68,6))) / 10.0;


// create an image
$ret = move_uploaded_file($files['tmp_name'], $dest_file);
if (!$ret) die("error uploading");
exec("stitchconv.py -i $dest_file -o $png_file --show-info", $ret_array);

$nr_stitches = intval(trim(split(":",$ret_array[0])[1]));
$size = split("x",trim(split(":",$ret_array[1])[1]));

//now get into wordpress
define('WP_USE_THEMES', true);
require_once( dirname(__FILE__) . '/wp-load.php' );
// Set up the WordPress query.
wp();

$content_str = 'Custom Embroidery pattern<br />'
   . 'stitches: ' . $info["stitchcount"] . '<br />'
   . 'size: ' . $info["width"] . ' x ' . $info["height"] .' mm<br />'
   . 'uploaded from:<br /> <a href="' . $_POST["url"] . '">' . $_POST["url"] . '</a><br />'
   . '<br />';
   
   
$content_str .= "BACKEND says: <br />";   
$content_str .= "stitches: " . $nr_stitches . "<br />";   
$content_str .= "width: " . (floatval($size[0]) * 2.0) . "mm <br />";
$content_str .= "height: " . (floatval($size[1]) * 2.0) . "mm <br />";
   
if ($_POST["username"])
	$content_str .= 'username: ' . $_POST["username"] . '<br />';

if ($_POST["projectname"])
	$content_str .= 'projectname: ' . $_POST["projectname"] . '<br />';
	

$price = $BASE_PRICE + 	max($info["stitchcount"] / 1000, 1) * $PRICE_PER_1K_STITCHES;
// add the product	
$post_id = wp_insert_post( array(
    'post_title' => $projectname,
    'post_content' => nl2br($content_str),
    'post_status' => 'publish',
    'post_type' => "product",
) );
wp_set_object_terms( $post_id, 'simple', 'product_type' );

// update meta data
if ($is_public)
	update_post_meta( $post_id, '_visibility', 'visible' );
else
	update_post_meta( $post_id, '_visibility', 'hidden' );
update_post_meta( $post_id, '_stock_status', 'instock');
update_post_meta( $post_id, 'total_sales', '0' );
update_post_meta( $post_id, '_downloadable', 'yes' );
update_post_meta( $post_id, '_virtual', 'no' );
update_post_meta( $post_id, '_price', $price );
update_post_meta( $post_id, '_regular_price', $price );
update_post_meta( $post_id, '_sale_price',  $price);
update_post_meta( $post_id, '_purchase_note', '' );
update_post_meta( $post_id, '_featured', 'no' );
update_post_meta( $post_id, '_weight', '' );
update_post_meta( $post_id, '_length', '' );
update_post_meta( $post_id, '_width', '' );
update_post_meta( $post_id, '_height', '' );
update_post_meta( $post_id, '_sku', '' );
update_post_meta( $post_id, '_product_attributes', array() );
update_post_meta( $post_id, '_sale_price_dates_from', '' );
update_post_meta( $post_id, '_sale_price_dates_to', '' );
update_post_meta( $post_id, '_sold_individually', '' );
update_post_meta( $post_id, '_manage_stock', 'no' );
update_post_meta( $post_id, '_backorders', 'no' );
update_post_meta( $post_id, '_stock', '' );


// add to category/ies
//$term_ids = [ 10, 12, 18 ];
//wp_set_object_terms( $product_id, $term_ids, 'product_cat' );
wp_set_object_terms( $post_id, $term_id, 'product_cat' );


// create attachments

require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

$uploadDir = $DEST_DIR;
$siteurl = str_replace("https","http", get_option('siteurl'));

$pattern_file = 'patterns/'.$fn.'.dst';
$preview_file = 'patterns/'.$fn.'.png';


// downloadable file attachemnt
$wp_filetype = wp_check_filetype($pattern_file, null);
$attachment = array(
			'post_author' => 1, 
			'post_date' => current_time('mysql'),
			'post_date_gmt' => current_time('mysql'),
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => $pattern_file,
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'post_content' => '',
			'post_status' => 'inherit',
			'post_modified' => current_time('mysql'),
			'post_modified_gmt' => current_time('mysql'),
			'post_parent' => $post_id,
			'post_type' => 'attachment',
			'guid' => $siteurl.'/'.$uploadDir.$fn.'.dst',
);
$attach_id = wp_insert_attachment( $attachment, $pattern_file, $post_id );
$file_name = $project_name;
$file_url = wp_get_attachment_url($attach_id);
$files[md5( $file_url )] = array(
	'name' => $file_name.'.dst',
	'file' => $file_url
);
// Updating database with the new array
update_post_meta( $post_id, '_downloadable_files', $files );


// IMAGE file attachemnt

$image = $siteurl."/wp-content/uploads/".$preview_file;

// Download file to temp location
$tmp = download_url( $image );
$file_array = array();
// Set variables for storage
// fix file filename for query strings
preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $image, $matches);
$file_array['name'] = basename($matches[0]);
$file_array['tmp_name'] = $tmp;

// If error storing temporarily, unlink
if ( is_wp_error( $tmp ) ) {
	@unlink($file_array['tmp_name']);
	$file_array['tmp_name'] = '';
	echo "error uploading image file";
}


$thumbid = media_handle_sideload( $file_array, $post_id, $project_name );
set_post_thumbnail(post_id, $thumbid);
add_post_meta($post_id, '_thumbnail_id', $thumbid);



// finished

// Create combined product
$pattern_post = get_post($post_id);
$pattern_id = $post_id;

$post_id = wp_insert_post( array(
    'post_title' => "My Costum Embroidered Product",
    'post_content' => "",
    'post_name' => uniqid(),
    'post_status' => 'publish',
    'post_type' => "product",
) );
wp_set_object_terms( $post_id, 'composite', 'product_type' );
wp_set_object_terms( $post_id, 28, 'product_cat' );

// update meta data
update_post_meta( $post_id, '_visibility', 'hidden' );
update_post_meta( $post_id, '_stock_status', 'instock');
update_post_meta( $post_id, 'total_sales', '0' );
update_post_meta( $post_id, '_featured', 'no' );
update_post_meta( $post_id, '_weight', '' );
update_post_meta( $post_id, '_length', '' );
update_post_meta( $post_id, '_width', '' );
update_post_meta( $post_id, '_height', '' );
update_post_meta( $post_id, '_sku', '' );
update_post_meta( $post_id, '_manage_stock', 'no' );
update_post_meta( $post_id, '_backorders', 'no' );
update_post_meta( $post_id, '_bto_base_price', 10 );
update_post_meta( $post_id, '_bto_base_regular_price', 10 );
update_post_meta( $post_id, '_bto_sale_regular_price', 10 );
update_post_meta( $post_id, '_bto_hide_shop_price', 10 );
update_post_meta( $post_id, '_bto_edit_in_cart', 'yes' );
update_post_meta( $post_id, '_bto_sold_individually', 10 );
update_post_meta( $post_id, '_bto_style', 'progressive' );
update_post_meta( $post_id, '_bto_scenario_data', Array() );

$cid1 = intval(time());
$cid2 = time()+1;

$data = Array
(
    '$cid1' => Array
        (
            'query_type' => product_ids,
            'assigned_ids' => Array
                (
                    '0' => $pattern_id
                ),

            'selection_mode' => thumbnails,
            'default_id' => $pattern_id,
            'title' => Pattern,
            'description' => '',
            'thumbnail_id' => $thumbid,
            'quantity_min' => 1,
            'quantity_max' => 1,
            'discount' => 0,
            'priced_individually' => yes,
            'shipped_individually' => no,
            'optional' => no,
            'hide_product_title' => no,
            'hide_product_description' => no,
            'hide_product_thumbnail' => no,
            'hide_product_price' => no,
            'hide_subtotal_product' => no,
            'hide_subtotal_cart' => no,
            'hide_subtotal_orders' => no,
            'show_orderby' => no,
            'show_filters' => no,
            'position' => 0,
            'component_id' => $cid1,
            'composite_id' => $post_id,
        ),

    '$cid2' => Array
        (
            'query_type' => category_ids,
            'assigned_category_ids' => Array
                (
                    '0' => 22,
                ),

            'selection_mode' => thumbnails,
            'default_id' => 0,
            'title' => 'Carrier product',
            'description' => '',
            'thumbnail_id' => '',
            'quantity_min' => 1,
            'quantity_max' => 1,
            'discount' => 0,
            'priced_individually' => yes,
            'shipped_individually' => no,
            'optional' => no,
            'hide_product_title' => no,
            'hide_product_description' => no,
            'hide_product_thumbnail' => no,
            'hide_product_price' => no,
            'hide_subtotal_product' => no,
            'hide_subtotal_cart' => no,
            'hide_subtotal_orders' => no,
            'show_orderby' => no,
            'show_filters' => no,
            'position' => 1,
            'component_id' => $cid2,
            'composite_id' => $post_id,
        )

);         
update_post_meta( $post_id, '_bto_data', $data);

// IMAGE file attachemnt

$dest = imagecreatefrompng('wp-content/uploads/2017/10/t-shirt-needle.png');
$src = imagecreatefrompng('wp-content/uploads/' . $preview_file);
$newimg = 'wp-content/uploads/patterns/combine'.$fn.'.png';

$white = imagecolorexact($src, 255, 255, 255);
$trans = imagecolortransparent($src,$white);

imagealphablending($dest, false);
imagesavealpha($dest, true);

list($width, $height, $type, $attr) = getimagesize('wp-content/uploads/' . $preview_file);
$scale = max(150.0/$width, 125.0/$height);
$new_w = $width* $scale;
$new_h = $height* $scale;
#print($width . "," . $new_w);
imagecopyresized($dest, $src, 287-$new_w/2, 200-$new_h/2, 0, 0, $new_w, $new_h, $width, $height);
#imagecopymerge($dest, $src, 10, 9, 0, 0, 181, 180, 100); //have to play with these numbers for it to work for you, etc.

$white = imagecolorexact($dest, 255, 255, 255);
$trans = imagecolortransparent($dest,$white);


imagepng($dest,$newimg);

imagedestroy($dest);
imagedestroy($src);


$image2 = $siteurl."/".$newimg;

// Download file to temp location
$tmp = download_url( $image2 );
$file_array = array();
// Set variables for storage
// fix file filename for query strings
preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $image2, $matches);
$file_array['name'] = basename($matches[0]);
$file_array['tmp_name'] = $tmp;

// If error storing temporarily, unlink
if ( is_wp_error( $tmp ) ) {
	@unlink($file_array['tmp_name']);
	$file_array['tmp_name'] = '';

}

$thumbid = media_handle_sideload( $file_array, $post_id, $project_name );
set_post_thumbnail(post_id, $thumbid);
add_post_meta($post_id, '_thumbnail_id', $thumbid);


$post = get_post($post_id);


// Finally return header and json data

header('Access-Control-Allow-Origin: *');
header('Content-type: application/json');

$response = array(
	'success' => true,
	'id'=> $post_id,
	'url'=> get_post($post_id)->guid . "#carrier-product",
);

echo json_encode($response); 

?>
