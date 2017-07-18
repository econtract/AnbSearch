<?php
/**
 * Created by PhpStorm.
 * User: imran
 * Date: 6/12/17
 * Time: 4:38 PM
 */
if ( !function_exists( 'pll_register_string' ) ) {
    require_once WP_PLUGIN_DIR . '/polylang/include/api.php';
}

function anbSearchTrans() {
    pll_register_string('Services', 'Services', 'AnbSearch');
    pll_register_string('Internet', 'Internet', 'AnbSearch');
    pll_register_string('TV', 'TV', 'AnbSearch');
    pll_register_string('Fixed line', 'Fixed line', 'AnbSearch');
    pll_register_string('Mobile', 'Mobile', 'AnbSearch');
    pll_register_string('Installation area', 'Installation area', 'AnbSearch');
    pll_register_string('Enter Zipcode', 'Enter Zipcode', 'AnbSearch');
    pll_register_string('Type of Use', 'Type of Use', 'AnbSearch');
    pll_register_string('Private', 'Private', 'AnbSearch');
    pll_register_string('Business', 'Business', 'AnbSearch');
    pll_register_string('Search Deals', 'Search Deals', 'AnbSearch');
    pll_register_string('Provider preferences', 'Provider preferences', 'AnbSearch');
    pll_register_string('Select Provider', 'Select Provider', 'AnbSearch');
    pll_register_string('Need help?', 'Need help?', 'AnbSearch');
    pll_register_string('We\'ll guide you', 'We\'ll guide you', 'AnbSearch');
    pll_register_string('Search', 'Search', 'AnbSearch');
    pll_register_string('Select the service you like to compare', 'Select the service you like to compare', 'AnbSearch');
    pll_register_string('Need help?', 'Need help?', 'AnbSearch');
}

add_action('init', 'anbSearchTrans');