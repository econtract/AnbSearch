<?php
/*
Plugin Name: Aanbieders Search
Depends: Wp Autoload with Namespaces, Aanbieders Api Client, Polylang
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Search plugin for Aanbieders.
Version: 1.0.2
Author: Imran Zahoor
Author URI: http://imranzahoor.wordpress.com/
License: A "Slug" license name e.g. GPL2
*/

namespace AnbSearch;
include_once(WP_PLUGIN_DIR . "/wpal-autoload/wpal-autoload.php");
// If this file is accessed directory, then abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

include(__DIR__ . '/pll-register-trans.php');

$result = wpal_create_instance(AnbCompare::class);

$energy = wpal_create_instance(AnbCompareEnergy::class);

add_shortcode( 'anb_search_result', [$result, 'getCompareResults'] );

add_shortcode( 'anb_search_bar', [$result, 'searchBar'] );

add_shortcode( 'anb_energy_search_form', [$energy, 'searchForm'] );

add_shortcode( 'anb_energy_search_bar', [$energy, 'searchBar'] );

// add ajax calls
add_action('wp_ajax_moreResults', array($result, 'moreResults'));
add_action( 'wp_ajax_nopriv_moreResults', array($result, 'moreResults'));

add_action('wp_ajax_compareBetweenResults', array($result, 'compareBetweenResults'));
add_action( 'wp_ajax_nopriv_compareBetweenResults', array($result, 'compareBetweenResults'));

add_action('wp_ajax_productsCallback', array($result, 'productsCallback'));
add_action( 'wp_ajax_nopriv_productsCallback', array($result, 'productsCallback'));

add_action('wp_ajax_getCompareResultsForWizard', array($result, 'getCompareResultsForWizard'));
add_action( 'wp_ajax_nopriv_getCompareResultsForWizard', array($result, 'getCompareResultsForWizard'));


// energy ajax call
add_action('wp_ajax_moreResultsEnergy', array($energy, 'moreResults'));
add_action( 'wp_ajax_nopriv_moreResultsEnergy', array($energy, 'moreResults'));

add_action('wp_ajax_usageResultsEnergy', array($energy, 'usageResultsEnergy'));
add_action( 'wp_ajax_nopriv_usageResultsEnergy', array($energy, 'usageResultsEnergy'));

/** @var AnbToolbox $anbToolbox */
$anbToolbox = wpal_create_instance( AnbToolbox::class );
add_action('wp_ajax_ajaxQueryToolboxApi', array($anbToolbox, 'ajaxQueryToolboxApi'));
add_action( 'wp_ajax_nopriv_ajaxQueryToolboxApi', array($anbToolbox, 'ajaxQueryToolboxApi'));

add_action('wp_ajax_verifyWizardZipCode', array($result, 'verifyWizardZipCode'));
add_action( 'wp_ajax_nopriv_verifyWizardZipCode', array($result, 'verifyWizardZipCode'));
