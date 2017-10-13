<?php

//error_reporting(E_ALL);
//print_r($_POST);

// config
$DEST_DIR = "wp-content/uploads/patterns/";

// get post variables
$is_public = $_POST["public"];
$projectname = $_POST["projectname"];
$filename = $_POST["filename"];

// upload pattern file
$files = $_FILES['dstfile'];
$fn = uniqid();
$dest_file = $DEST_DIR . $fn . ".dst";
$png_file = $DEST_DIR . $fn . ".png";

// set private or public category
if ($is_public) {
	$term_id = 23;
} else {
	$term_id = 22;
}

// create an image
$ret = move_uploaded_file($files['tmp_name'], $dest_file);
if (!$ret) die("error uploading");
exec("stitchconv.py -i $dest_file -o $png_file");


//now get into wordpress
define('WP_USE_THEMES', true);
require_once( dirname(__FILE__) . '/wp-load.php' );
// Set up the WordPress query.
wp();

	
// add the product	
$post_id = wp_insert_post( array(
    'post_title' => $projectname,
    'post_content' => 'Embroidery pattern\n'
					   . 'source: ' . $_POST["source"] .'\n'
					   . 'uploaded from: ' . $_POST["url"] . '\n',
    'post_status' => 'publish',
    'post_type' => "product",
) );
wp_set_object_terms( $post_id, 'simple', 'product_type' );

// update meta data
update_post_meta( $post_id, '_visibility', 'visible' );
update_post_meta( $post_id, '_stock_status', 'instock');
update_post_meta( $post_id, 'total_sales', '0' );
update_post_meta( $post_id, '_downloadable', 'yes' );
update_post_meta( $post_id, '_virtual', 'no' );
update_post_meta( $post_id, '_price', '100' );
update_post_meta( $post_id, '_regular_price', '100' );
update_post_meta( $post_id, '_sale_price', '100' );
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
$siteurl = get_option('siteurl');
$thumbnail = 'importedproductimages/' . $fn;
$pattern_file = 'patterns/'.$fn.'.dst';
$preview_file = 'patterns/'.$fn.'.png';

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
	'url'=> get_post($post_id)->guid
);

echo json_encode($response); 

?>
