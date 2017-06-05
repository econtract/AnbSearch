<?php
/*
Plugin Name: Aanbieders Search
Depends: Wp Autoload with Namespaces, Aanbieders Api Client, Polylang
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Search plugin for Aanbieders.
Version: 1.0.0
Author: Imran Zahoor
Author URI: http://imranzahoor.wordpress.com/
License: A "Slug" license name e.g. GPL2
*/

namespace AnbSearch;
include_once(WP_PLUGIN_DIR . "/wp-autoload/wpal-autoload.php" );
// If this file is accessed directory, then abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

//wpal_load(AnbProduct::class);
//$product = new AnbProduct();
$result = wpal_create_instance(AnbCompare::class);

add_shortcode( 'anb_search_result', [$result, 'getCompareResults'] );

add_shortcode( 'anb_search_form', [$result, 'searchForm'] );

//add_shortcode('anb_get_suppliers', [$result, 'anb_get_suppliers'] );