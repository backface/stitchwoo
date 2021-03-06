<?php
/**
 * Functions.php
 *
 * @package  Theme_Customisations
 * @author   WooThemes
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * functions.php
 * Add PHP snippets here
 */

remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

function remove_from_homepage() {
	remove_action( 'homepage', 'storefront_recent_products',       30 );
	remove_action( 'homepage', 'storefront_popular_products',      50 );
	remove_action( 'homepage', 'storefront_on_sale_products',      60 );
	remove_action( 'homepage', 'storefront_best_selling_products', 70 );
}
add_action("init","remove_from_homepage");

function custom_pre_get_posts_query( $q ) {

    $tax_query = (array) $q->get( 'tax_query' );

    $tax_query[] = array(
           'taxonomy' => 'product_cat',
           'field' => 'slug',
           'terms' => array( 'customized-items','private-patterns', 'carrier','public-patterns'), 
			// Don't display products in these categories on shop page
           'operator' => 'NOT IN'
    );
    $q->set( 'tax_query', $tax_query );

}
add_action( 'woocommerce_product_query', 'custom_pre_get_posts_query' );
add_action( 'best_selling_products', 'custom_pre_get_posts_query' );


add_filter( 'get_terms', 'this_exclude_category', 10, 3 );
add_filter( 'best_selling_products', 'this_exclude_category', 10, 3 );

function this_exclude_category( $terms, $taxonomies, $args ) {
  $new_terms = array();
  // if a product category and on a page
  if ( in_array( 'product_cat', $taxonomies ) && ! is_admin() && is_page() ) {
    foreach ( $terms as $key => $term ) {
	// Enter the name of the category you want to exclude in place of 'uncategorised'
      if ( ! in_array( $term->slug, array( 'private-patterns', 'mediums', 'carrier', 'customized-items','public-patterns' ) ) ) {
        $new_terms[] = $term;
      }
    }
    $terms = $new_terms;
  }
  return $terms;
}

add_filter( 'woocommerce_product_categories_widget_args', 'this_exclude_widget_category' );
add_filter( 'storefront_best_selling_products', 'this_exclude_widget_category' );

function this_exclude_widget_category( $args ) {
	// Enter the id of the category you want to exclude in place of '30'
-	$args['exclude'] = array('22','23','28','24');
	return $args;
}

add_filter( 'woocommerce_composite_component_summary_max_columns', 'wc_cp_summary_max_columns', 10, 2 );
function wc_cp_summary_max_columns( $cols, $composite ) {
	$cols = 3;
	return $cols;
}

add_filter( 'woocommerce_composite_component_loop_columns', 'wc_cp_component_loop_columns', 10, 3 );
function wc_cp_component_loop_columns( $cols, $component_id, $composite ) {
	$cols = 3;
	return $cols;
}

