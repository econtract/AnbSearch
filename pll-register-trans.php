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

pll_register_string('Services', 'Services', 'AnbSearch');
pll_register_string('Internet', 'Internet', 'AnbSearch');
pll_register_string('TV', 'TV', 'AnbSearch');
pll_register_string('Fixed line', 'Fixed line', 'AnbSearch');