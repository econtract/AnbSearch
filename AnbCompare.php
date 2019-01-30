<?php
/**
 * Created by PhpStorm.
 * User: imran
 * Date: 5/12/17
 * Time: 6:02 PM
 */

namespace AnbSearch;


use abApiCrm\includes\controller\OrderController;
use abSuppliers\AbSuppliers;

if(!function_exists('getUriSegment')) {
    function getUriSegment($n)
    {
        $segment = explode("/", parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

        return count($segment) > 0 && count($segment) >= ($n - 1) ? $segment[$n] : '';
    }
}

class AnbCompare extends Base
{
    /**
     * constant form page URI
     */
    const RESULTS_PAGE_URI = "/telecom/results/";

    /**
     * @var string
     */
    public $currencyUnit = 'EUR';

    /**
     * @var
     */
    private $abSuppliers;

    public $orignalCats = [];

    public $sector;

    public $pagename;

    /**
     * AnbCompare constructor.
     */
    public function __construct()
    {
        $this->sector = getUriSegment(1);
        $this->pagename = getUriSegment(2);
        //enqueue JS scripts
        add_action( 'wp_enqueue_scripts', array($this, 'enqueueScripts') );

        $this->abSuppliers = wpal_create_instance( AbSuppliers::class );

        $_GET = $this->cleanInputGet();
        $this->orignalCats = $_GET['cat'];

        parent::__construct();
    }


    /**
     * enqueue ajax scripts
     */
    function enqueueScripts()
    {
        if($this->pagename == pll__('results')) {
            //This is required for searchFilterNav functionality on energy too
            wp_enqueue_script('search-compare-script', plugins_url('/js/search-results.js', __FILE__), array('jquery'), '1.2.9', true);

            // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
            wp_localize_script( 'search-compare-script', 'search_compare_obj',
                array(
                    'ajax_url'     => admin_url( 'admin-ajax.php' ),
                    'site_url'     => pll_home_url(),
                    'zipcode_api'  => ZIPCODE_API,
                    'template_uri' => get_template_directory_uri(),
                    'lang' => getLanguage(),
                    'trans_monthly_cost' => pll__( 'Monthly costs' ),
                    'trans_monthly_total' => pll__( 'Monthly total' ),
                    'trans_first_month' => pll__( 'First month' ),
                    'trans_monthly_total_tooltip_txt' => pll__( 'PBS: Monthly total tooltip text' ),
                    'trans_ontime_costs' => pll__( 'One-time costs' ),
                    'trans_ontime_total' => pll__( 'One-time total' ),
                    'trans_mth'          => pll__('mth'),
                    'trans_loading_dots'       => pll__('Loading...')
                ) );

            wp_enqueue_script('compare-between-results-script', plugins_url('/js/compare-results.js', __FILE__), array('jquery'), '1.3.0', true);
            //This is required for current pack functionality on energy too
            // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
            wp_localize_script('compare-between-results-script', 'compare_between_results_object',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'site_url' => pll_home_url(),
                    'current_pack' => pll__('your current pack'),
                    'select_your_pack' => pll__('Select your pack'),
                    'template_uri' => get_template_directory_uri(),
                    'lang' => $this->getCurrentLang(),
                    'features_label' => pll__('Features'),
                    'telecom_trans' => pll__('telecom'),
                    'energy_trans' => pll__('energy'),
                    'brands_trans' => pll__('brands'),
                    'selected_pack' => pll__('selected pack'),
                    'change_pack' => pll__('change pack'),
                    'customer_score' => pll__('Customer Score'),
                    'trans_ontime_total' => pll__('One-time costs'),
                    'trans_installation' => pll__('Installation'),
                    'trans_free_activation' => pll__('Free activation'),
                    'trans_Free_modem' => pll__('Free modem'),
                    'trans_your_advantage' => pll__('Your advantage'),
                    'trans_order_now' => pll__('Order Now'),
                    'trans_info_options' => pll__('Info and options'),
                    'trans_mth' => pll__('mth'),
                    'trans_free' => pll__('Free'),
                    'trans_free_installation' => pll__('Free Installation'),
                    'trans_activation' => pll__('Activation'),
                    'trans_free_modem' => pll__('Free modem'),
                    'trans_modem' => pll__('Modem'),
                    'trans_loading_dots'   => pll__('Loading...'),
                    'trans_idontknow' => pll__('I dont know the contract')
                )
            );
        }

        if($this->pagename == pll__('wizard') && $this->sector == pll__('telecom')) {
            wp_enqueue_script('wizard-script', plugins_url('/js/wizard.js', __FILE__), array('jquery'), '1.0.5', true);

            // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
            wp_localize_script('wizard-script', 'wizard_object',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'zip_empty' => pll__('Zip cannot be empty'),
                    'zip_invalid' => pll__('Please enter valid Zip Code'),
                    'offers_msg' => pll__('offers') . " " . pll__('starting from'),
                    'no_offers_msg' => pll__('No offers in your area'),
                    'currency' => $this->getCurrencySymbol($this->currencyUnit)
                ));
        }
    }

    function getCompareResults($atts, $enableCache = true, $cacheDurationSeconds = 86400)
    {
        if(defined('COMPARE_API_CACHE_DURATION')) {
            $cacheDurationSeconds = COMPARE_API_CACHE_DURATION;
        }
        if (!empty($atts['detaillevel'])) {
            $atts['detaillevel'] = explode(',', $atts['detaillevel']);
        }
        //print_r($atts);
        if (!empty($atts['product_ids'])) {
            $atts['product_ids'] = explode(",", $atts['product_ids']);
        }

        $atts = shortcode_atts(array(
            'cat' => 'internet',
            'zip' => '',
            'pref_cs' => '',
            'detaillevel' => ['supplier,logo,services,price,reviews,texts,promotions,core_features'],
            'sg' => 'consumer',
            'lang' => $this->getCurrentLang(),
            'limit' => '',

            's' => 1,//Min download speed in Bps 1000 Bps = 1Mbps
            'dl' => '',
            'ds' => '',
            'sort' => '',
            'cp' => '',
            'nm' => '',
            'ns' => '',
            'int' => '',
            'fleet' => '',

            'pr' => '',
            'cm' => '',
            'f' => '',
            'du' => '',
            'nu' => '',
            'nou' => '',
            'dndu' => '',
            'dnnu' => '',
            'u' => '',
            'ut' => '',
            'houseType' => '',
            'has_solar' => '',
            'solar_capacity' => '',
            'gp' => '',
            'l' => '',
            'd' => '',
            'situation' => '',

            'num_pc' => '',
            'num_tv' => '',
            'num_smartphones' => '',
            'num_tablets' => '',
            'ms_internet' => '',
            'ms_idtv' => '',
            'ms_fixed' => '',
            'ms_fixed' => '',
            'ms_mobile' => '',
            'free_install' => '',
            'free_activation' => '',
            'qos_cs' => '',
            'ms_internet' => '',
            'ms_idtv' => '',
            'ms_fixed' => '',
            'ms_mobile' => '',
            'pref_pids' => [],
            'searchSubmit' => '', // conditional param ( this param doesn't belong to API Params)
            'cmp_pid' => '',
            'cmp_sid' => '',
            'greenpeace' => ''
        ), $atts, 'anb_search');
        // print_r($atts);die;

        $getParams = $_GET;

        //$this->cleanArrayData($_GET);
        if (count($getParams['cat']) >= 2) {
            $getParams['cp'] = getPacktypeOnCats($getParams['cat']);
            $this->orignalCats = $getParams['cat'];
            $getParams['cat'] = 'packs';
        } else {
            if (is_array($getParams['cat'])) {
                $getParams['cat'] = (is_array($getParams['cat'])) ? $getParams['cat'][0] : $getParams['cat'];
            }
        }

        //$WizardAllowedParams  = ['ms_internet', 'ms_idtv', 'ms_fixed', 'ms_mobile'];

        $params = array_filter($getParams) + $atts;//append any missing but default values

        //print_r($params);
        //remove empty params
        $params = array_filter($params);

        //this will not remove if pref_cs is passed as array but is empty so adding another check to ensure that
        if(isset($params['pref_cs'][0]) && empty($params['pref_cs'][0])) {
            unset($params['pref_cs']);
        }

        if($params['cat'] == 'dualfuel_pack' || $params['cat'] == 'electricity' || $params['cat'] == 'gas'){
            $params['d'] = 1;
            $params['situation'] = 3;
        }

        if($params['greenpeace']) {
            $params['greenpeace'] = $params['greenpeace']*5;
        }

        /**
         * custom check to allow values from wizard
         * wizard doesn't contain pack_type so these
         * values will be used to fetch match records
         */
        /*foreach ($WizardAllowedParams as $allowed ) {
            if (array_key_exists($allowed, $_GET)) {
                $params[$allowed] = $_GET[$allowed];
            }
        }*/

        // set category to packs when it comes from wizard
        if (isset($getParams['search_via_wizard'])){
            $params['cat'] = 'packs';
        }

        if (strtolower($params['cat']) == "internet") {
            $params['s'] = 0;//TODO: This is just temporary solution as for internet products API currently expecting this value to be passed
        }

        if (isset($params['hidden_sp']) && !empty($params['hidden_sp'])) {
            $params['pref_cs'] = $params['hidden_sp'];
            unset($params['hidden_sp']);
        }

        // Covert DS to s because param S in url is reserve word for Wordpress search
        if (!empty($params['ds'])) {
            $params['s'] = $params['ds'] * 1000; //Min. download speed in Bps: 1000 Bps = 1Mbps
            unset($params['ds']);
        }

        // in case of Max download limit set parameter to -1
        if (isset($params['dl']) && !empty($params['dl']) && $params['dl'] == INTERNET_DOWNLOAD_LIMIT) {
            $params['dl'] = "-1";
        }

        $this->cleanArrayData($params);
        // get the products
        $params = $this->allowedParams($params, array_keys($atts));//Don't allow all variables to be passed to API

        // no need to send this parameter to API call
        unset($params['searchSubmit']);

        //get integer value from zip code
        if(!is_numeric($params['zip'])) {
            $params['zip'] = intval($params['zip']);
        }

        //generate key from params to store in cache
        displayParams($params);
        $start = getStartTime();
        $displayText = "Time API (Compare) inside getCompareResults";
        if ($enableCache && !isset($_GET['no_cache'])) {
            $cacheKey = md5(serialize($params)) . ":compare";
            $result = mycache_get($cacheKey);

            if($result === false || empty($result)) {
                $result = $this->anbApi->compare($params);
                mycache_set($cacheKey, $result, $cacheDurationSeconds);
            } else {
                $displayText = "Time API Cached (Compare) inside getCompareResults";
            }
        } else {
            $result = $this->anbApi->compare($params);
        }
        $finish = getEndTime();
        displayCallTime($start, $finish, $displayText);

        return $result;
    }

    function getPreviousCompareResults($compareId, $recompare = 1, $enableCache = true, $cacheDurationSeconds = 86400)
    {
        if(defined('COMPARE_API_CACHE_DURATION')) {
            $cacheDurationSeconds = COMPARE_API_CACHE_DURATION;
        }
        $result = null;
        $start = getStartTime();
        $displayText = "Time API (Previous Compare) inside getPreviousCompareResults";
        if ($enableCache && !isset($_GET['no_cache'])) {
            $cacheKey = md5($compareId . $recompare) . ":compare";
            $result = mycache_get($cacheKey);

            if($result === false || empty($result)) {
                $result = $this->anbApi->previousCompare($compareId, ['recompare' => $recompare]);
                mycache_set($cacheKey, $result, $cacheDurationSeconds);
            } else {
                $displayText = "Time API Cached (Compare) inside getCompareResults";
            }
        } else {
            $result = $this->anbApi->previousCompare($compareId, ['recompare' => $recompare]);
        }
        $finish = getEndTime();
        displayCallTime($start, $finish, $displayText);

        return $result;
    }

    /**
     * get result for compare wizard to show number of found records against search criteria in wizard
     * also get minimum prices of results
     */
    function getCompareResultsForWizard()
    {
        $result = $this->getCompareResults([]);

        if (is_null($result)) {
            return;
        }

        $result = json_decode($result);

        $partners = $this->abSuppliers->getSupplierIds(true);
        $prices = $this->fetchMinimumPriceOfResultsGroupBySupplier($result);

        $pricesKeys = !is_null($prices) ? array_keys($prices) : [];

        echo json_encode([
            'count'        => $result->num_results,
            'prices'       => $prices,
            'no_offer_ids' => array_diff($partners, $pricesKeys)
        ]);

        wp_die();

    }

    /**
     * @param $results
     * @return mixed|void
     */
    function fetchMinimumPriceOfResultsGroupBySupplier ($results) {
        $prices = $units = [];

        if (is_null($results)) {
            return;
        }

        foreach ($results->results as $listResults) {
            $currentResult = $listResults->product;

            $prices[$currentResult->supplier_id][$currentResult->product_id] = (float)$currentResult->monthly_fee->value;

            // Each supplier will have same currency for all products, so no need to make multi dimensional
            if (!isset($minPrice[$currentResult->supplier_id]['unit'])) {
                $units[$currentResult->supplier_id]['unit'] = $currentResult->monthly_fee->unit;
            }

        }

        $prices = array_map('min', $prices);
        $minPrices = array_map( function ($k, $v) use ($prices) {

            $response[$k] = [
                'price' => str_replace( '.', ',', $prices[$k]),
                'unit' => $v['unit'],
            ];
            return $response;
        }, array_keys($units), $units);

        return call_user_func_array('array_replace', $minPrices);
    }

    /**
     * get more results and return in html form
     */
    function moreResults()
    {
        $productResponse = '';

        $products = $this->getCompareResults([
            'detaillevel' => 'supplier,logo,services,price,reviews,texts,promotions,core_features'
        ]);

        $products = json_decode($products);

        /** @var \AnbTopDeals\AnbProduct $anbTopDeals */
        $anbTopDeals = wpal_create_instance( \AnbTopDeals\AnbProduct::class );

        $countProducts = 0;
        foreach ($products->results as $listProduct) {

            $countProducts++;

            if ($countProducts <= $this->defaultNumberOfResults) {
                continue;
            }
            $currentProduct = $listProduct->product;

            // include badge or text - partner logo
            $includeText = ($currentProduct->supplier->is_partner == 1) ? false : true;

            list($productData, $priceHtml, $servicesHtml) = $this->extractProductData($this->anbTopDeals, $currentProduct, true);

            //Promotions, Installation/Activation HTML
            //display installation and activation price
            $promotionHtml = $this->anbTopDeals->getPromoInternalSection($productData, true);//True here will drop promotions

            list($advPrice, $monthDurationPromo, $firstYearPrice) = $this->anbTopDeals->getPriceInfo($productData);

            $parentSegment = getSectorOnCats($_SESSION['product']['cat']);
            $checkoutPageLink = '/' . $parentSegment . '/' . pll__('checkout');

            $forceCheckAvailability = false;

            if ( empty( $_GET['zip'] ) ) {
                //don't continue if zip is empty
                $forceCheckAvailability = true;
            }

            list(, , , , $toCartLinkHtml) = $anbTopDeals->getToCartAnchorHtml($parentSegment, $productData['product_id'], $productData['supplier_id'], $productData['sg'], $productData['producttype'], $forceCheckAvailability);

            /*$toCartLinkHtml = "href='" . $checkoutPageLink . "?product_to_cart&product_id=" . $productData['product_id'] .
                              "&provider_id=" . $productData['supplier_id'] . "&sg={$productData['sg']}&producttype={$productData['producttype']}'";*/
            $blockLinkClass = 'block-link';
            if($forceCheckAvailability) {
                $blockLinkClass = 'block-link missing-zip';
            }

            if($productData['commission'] === true) {
                $toCartLinkHtml = '<a ' . $toCartLinkHtml . ' class="link '.$blockLinkClass.'">' . pll__('Order Now') . '</a>';
            } else {
                $toCartLinkHtml = '<a href="#not-available" class="link block-link not-available">' . pll__('Not Available') . '</a>';
            }
            $appendHtml = '<p class="message">' . decorateLatestOrderByProduct($currentProduct->product_id) . '</p>';
            $orderInfoHtml = '<div class="buttonWrapper">
                            <a href="' . getTelecomProductPageUri($productData) . '" class="btn btn-primary ">' . pll__( 'Info and options' ) . '</a>
                            '.$toCartLinkHtml.'
                          </div>';
            //echo "yoooo...1";die();
            $productResp .= '<div class="offer">
                            <div class="row listRow">
                                <div class="col-md-3">
                                    '.$this->anbTopDeals->getProductDetailSection( $productData, $servicesHtml, $includeText,false, '', true ).'
                                </div>
                                <div class="col-md-3">
                                    '.$this->anbTopDeals->getPromoSection( $promotionHtml, 0, 'dealFeatures', '' ).'
                                </div>
                                <div class="col-md-3">
                                    '.$this->anbTopDeals->priceSection( $priceHtml, $monthDurationPromo, $firstYearPrice, 'dealPrice', '', '', $productData, true ).'
                                </div>
                                <div class="col-md-3">
                                    <div class="totalCalcWrapper">
                                        '.$this->anbTopDeals->getTotalAdvHtml($productData['advantage']).'
                                    </div>
                                    <div class="actionButtons print-hide">
                                        '.$orderInfoHtml.'
                                    </div>
                                </div>
                              </div>
                            <div class="row listRow withSeperator">'.$this->getServiceDetail( $currentProduct, true ).'</div>
                            <div class="row listRow withSeperator">
                                <div class="col-md-7">
                                    <div class="recentOrder">
                                        '.decorateLatestOrderByProduct($currentProduct->product_id).'
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="rightWrapper">
                                        <!--<span class="waitingTooltip" data-toggle="custom-tooltip-bottom" title="<p>'.pll__('Popover bottom Sed posuere consectetur est at lob ortis. Aenean eu leo quam. Pellentesque ornare sem lacinia quam.').' </p>"><i class="customIcon fa fa-exclamation-triangle"></i> Waiting for input</span> -->
                                        <span class="lastUpdated" data-toggle="custom-tooltip-bottom" title="<p>'.pll__('Sed posuere consectetur est at lob ortis. Aenean eu leo quam. Pellentesque ornare sem lacinia quam.').' </p>"> '.pll__('Last updated').': <span class="timestamp">'.formatDate($currentProduct->last_update).'</span></span>
                                        <span class="comparePackage print-hide">
		                                    <label>
		                                        <input type="hidden"
		                                               name="compareProductType'.$currentProduct->product_id.'"
		                                               value="'.$currentProduct->producttype.'">
		                                                <input type="checkbox"
		                                                       value="'.$currentProduct->product_id.'"> '.pll__( 'Compare' ).'
		                                    </label>
		                                </span>
                                    </div>
                                </div>
                            </div>
                        </div>';

            //old product response with old design
            /*$productResponse .= '<div class="offer">
                            <div class="row listRow">
                                <div class="col-md-4">
                                    ' . $this->anbTopDeals->getProductDetailSection($productData, $servicesHtml) . '
                                </div>
                                <div class="col-md-3">
                                ' . $this->anbTopDeals->getPromoSection($promotionHtml, $advPrice, 'dealFeatures', $appendHtml) . '
                                </div>
                                <div class="col-md-2">
                                   ' . $this->anbTopDeals->priceSection($priceHtml, $monthDurationPromo, $firstYearPrice, 'dealPrice', '') . '
                                </div>
                                <div class="col-md-3">
                                    <div class="actionButtons">
                                        <div class="comparePackage">
                                            <label>
                                                <input type="hidden" name="compareProductType' . $currentProduct->product_id . '>" value="' . $currentProduct->producttype . '">
                                                <input type="checkbox" value="' . $currentProduct->product_id . '"> ' . pll__('Compare') . '
                                            </label>
                                        </div>
                                        <div class="buttonWrapper">
                                            <a href="/' . pll__('brands') . '/' . $currentProduct->supplier_slug . '/' . $currentProduct->product_slug . '" class="btn btn-primary btn-block">' . pll__('Info and options') . '</a>
                                            '.$toCartLinkHtml.'
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';*/
        }

        echo $productResp;

        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * compare between already fetched results
     */
    function compareBetweenResults()
    {
        $productResponse = '';
        $crntpackSelected = $crntpackSelectedEnd = $crntpackSelectedClass = '';

        $category = (is_array($_REQUEST['productTypes']) ? $_REQUEST['productTypes'][0] : $_REQUEST['productTypes']);

        $getProducts = $this->anbProduct->getProducts(
            [
                'productid' => $_REQUEST['products'],
                'sg' => trim($_REQUEST['sg']),
                'lang' => $this->getCurrentLang(),
                'status' => $this->productStatus,
                'cat' => $category,
                'detaillevel' => ['supplier', 'logo', 'services', 'price', 'reviews', 'texts', 'promotions', 'core_features']
            ]
        );

        $products = json_decode($getProducts);

        $countProducts = 0;

        foreach ($products as $listProduct) {

            $countProducts++;

            $currentProduct = $listProduct;

            // include badge or text - partner logo
            $includeText = ($currentProduct->supplier->is_partner == 1) ? false : true;

            list($productData, $priceHtml, $servicesHtml) = $this->extractProductData($this->anbTopDeals, $currentProduct);

            //Promotions, Installation/Activation HTML
            //display installation and activation price
            $promotionHtml = $this->anbTopDeals->getPromoInternalSection($productData, true);//True here will drop promotions

            list($advPrice, $monthDurationPromo, $firstYearPrice) = $this->anbTopDeals->getPriceInfo($productData);

            $parentSegment = getSectorOnCats($_SESSION['product']['cat']);
            $checkoutPageLink = '/' . $parentSegment . '/' . pll__('checkout');
            $toCartLinkHtml = "href='" . $checkoutPageLink . "?product_to_cart&product_id=" . $productData['product_id'] .
                "&provider_id=" . $productData['supplier_id'] . "&sg={$productData['sg']}&producttype={$productData['producttype']}'";
            $toCartLinkHtml = '<a ' . $toCartLinkHtml . ' class="link block-link">' . pll__('Order Now') . '</a>';

            $selectedVal = !empty($_REQUEST['crntPack']) ? $_REQUEST['crntPack'] : pll__('Selected Pack') . ' ' . $countProducts;

            if (!empty($_REQUEST['crntPack'])) {
                $crntpackSelected = '<div class="selectedOfferWrapper">';
                $crntpackSelectedEnd = '</div>';
                $crntpackSelectedClass = 'selected';
                $crntPackHtml = '<a href="#" class="edit" data-toggle="modal" data-target="#selectCurrentPack">
                                 <i class="fa fa-chevron-right"></i>' . pll__('change pack') . '</a>
                                 <a href="#" class="close closeCrntPack"><span>×</span></a>';

                $toCartLinkHtml = "<p class='link block-link'>&nbsp;</p>";
            } else {
                if($productData['commission'] === false) {
                    $toCartLinkHtml = '<a href="#not-available" class="link block-link not-available">' . pll__('Not Available') . '</a>';
                }
            }

            $productResponse .= '<div class="col-md-4 offer-col ' . $crntpackSelectedClass . '">' .
                $crntpackSelected .
                '<div class="selection">
                                            <h4>' . $selectedVal . '</h4>' .
                $crntPackHtml .
                '</div>' .

                '<div class="offer">' .
                $this->anbTopDeals->getProductDetailSection($productData, $servicesHtml, $includeText) .
                $this->anbTopDeals->priceSection($priceHtml, $monthDurationPromo, $firstYearPrice, 'dealPrice', '', '', $productData, true) .
                $this->anbTopDeals->getPromoSection($promotionHtml, $productData['advantage'], 'dealFeatures',
                    '<a href="' . getTelecomProductPageUri($productData) . '" class="btn btn-primary ">' . pll__('Info and options') . '</a>
                                                     '.$toCartLinkHtml.'
                                                     <p class="message">' . decorateLatestOrderByProduct($productData['product_id']) . '</p>') .
                '<div class="packageInfo">' .
                $this->getServiceDetail($currentProduct) .
                '</div>' .

                $this->anbTopDeals->priceSection($priceHtml, $monthDurationPromo, $firstYearPrice, 'dealPrice last', '<div class="buttonWrapper">
                                                        <a href="' . getTelecomProductPageUri($productData) . '" class="btn btn-primary ">' . pll__('Info and options') . '</a>
                                                        '.$toCartLinkHtml.'
                                                </div>') . '
                                               
                                          </div>' .
                $crntpackSelectedEnd .
                '</div>';
        }

        print $productResponse;

        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     *  call back will fetch data from API
     *  detail level added just to minimize response time
     */
    public function productsCallback()
    {
        $extSuppTbl = new \wpdb(DB_PRODUCT_USER, DB_PRODUCT_PASS, DB_PRODUCT, DB_PRODUCT_HOST);
        $startTime = getStartTime();
        $statemet = $extSuppTbl->prepare(
            "SELECT producttype,product_id,product_name FROM supplier_products 
				WHERE supplier_id=%d AND lang=%s AND segment=%s AND (active=%d OR active=%d) AND (producttype=%s OR producttype=%s) 
				ORDER BY product_name",
            [
                $_REQUEST['supplier'],
                $this->getCurrentLang(),
                $_REQUEST['sg'],
                $this->productStatus[0],
                $this->productStatus[1],
                $this->productTypes[0],
                $this->productTypes[1],
            ]
        );

        $products = $extSuppTbl->get_results($statemet, ARRAY_A);
        $endTime = getEndTime();

        if($_GET['debug']) {
            displayCallTime($startTime, $endTime, 'Display Time for Comp Query+++');
        }

        if (empty($products)) {
            return $html = '';
        }

        $startTime = getStartTime();
        $html = '<option value="">' . pll__('Select your pack') . '</option>';

        foreach ($products as $product) {
            $html .= '<option value="' . $product['producttype'] . '|' . $product['product_id'] . '">' . $product['product_name'] . '</option>';
        }

        print $html;
        $endTime = getStartTime();
        if($_GET['debug']) {
            displayCallTime($startTime, $endTime, '+++HTML GENERATED+++');
        }
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    function getProductDetails($productId, $supplierId, $producttype, $lang = "") {
        if(empty($lang)) {
            $lang = $this->getCurrentLang();
        }
        $extSuppTbl = new \wpdb(DB_PRODUCT_USER, DB_PRODUCT_PASS, DB_PRODUCT, DB_PRODUCT_HOST);
        $startTime = getStartTime();
        $statemet = $extSuppTbl->prepare(
            "SELECT producttype,product_id,product_name,commission_fee_fixed as commission, segment FROM supplier_products 
			WHERE product_id=%d AND supplier_id=%d AND producttype=%s AND lang=%s AND (active=%d OR active=%d) 
			ORDER BY product_name",
            [
                $productId,
                $supplierId,
                $producttype,
                $lang,
                $this->productStatus[0],
                $this->productStatus[1],
            ]
        );

        return $extSuppTbl->get_row($statemet, ARRAY_A);
    }

    function searchForm($atts)
    {
        $atts = shortcode_atts(array(
            'cat' => '',
            'zip' => '',
            'pref_cs' => '',
            'sg' => 'consumer',
            'lang' => $this->getCurrentLang(),
            'hidden_sp' => '',
            'enable_need_help' => false

        ), $atts, 'anb_search_form');

        $values = $atts;

        if (!empty($_GET)) {
            $values = $_GET + $atts;//append any missing but default values
        }

        if ($values['cat'] == 'packs') {
            $values['cat'] = $this->orignalCats;//restore the selection
        }

        $this->convertMultiValToArray($values['cat']);

        if ($_GET['debug']) {
            echo "<pre>";
            print_r($values);
            echo "</pre>";
        }

        //$this->loadFormStyles();
        //$this->loadJqSumoSelect();
        //$this->loadBootstrapSelect();
        //for self page esc_url( $_SERVER['REQUEST_URI'] )
        if (!empty($values['hidden_sp'])) {
            $supplierHtml = $this->generateHiddenSupplierHtml($values['hidden_sp']);
        } else {
            //$supplierHtml = $this->generateSupplierHtml($values['pref_cs']);
            //No need to fetch suppliers now
            $supplierHtml = '';
        }

        $needHelpHtml = "";

        if ($values['enable_need_help'] == true) {
            $needHelpHtml .= "<div class='needHelp'>
                                <a href='javascript:void(0)' data-toggle='modal' data-target='#widgetPopup' data-backdrop='static' data-keyboard='false'>
                                    <i class='floating-icon fa fa-chevron-right'></i>
                                    <h6>" . pll__('Need help?') . "</h6>
                                    <p>" . pll__('We\'ll guide you') . "</p>
                                </a>
                              </div>";
        }

        //In below call change '/' . getUriSegment(1) . '/' .pll__('results') to pll__('results') in case you want to submit it on the same URL struture like on provider details page.

        $formNew = $this->getSearchBoxContentHtml($values, $needHelpHtml, $supplierHtml, pll__("Search Deals"), false, "", '/' . getUriSegment(1) . '/' . pll__('results'));
        return $formNew;
    }

    public function searchFormMobile($atts)
    {
        $atts = shortcode_atts(array(
            'cat' => '',
            'zip' => '',
            'pref_cs' => '',
            'sg' => 'consumer',
            'lang' => $this->getCurrentLang(),
            'hidden_sp' => '',
            'enable_need_help' => false

        ), $atts, 'anb_mobile_search_form');

        $values = $atts;

        if (!empty($_GET)) {
            $values = $_GET + $atts;//append any missing but default values
        }

        if ($values['cat'] == 'packs') {
            $values['cat'] = $this->orignalCats;//restore the selection
        }

        $this->convertMultiValToArray($values['cat']);

        if ($_GET['debug']) {
            echo "<pre>";
            print_r($values);
            echo "</pre>";
        }

        //$this->loadFormStyles();
        //$this->loadJqSumoSelect();
        //$this->loadBootstrapSelect();
        //for self page esc_url( $_SERVER['REQUEST_URI'] )
        if (!empty($values['hidden_sp'])) {
            $supplierHtml = $this->generateHiddenSupplierHtml($values['hidden_sp']);
        } else {
            //$supplierHtml = $this->generateSupplierHtml($values['pref_cs']);
            //No need to fetch suppliers now
            $supplierHtml = '';
        }

        $needHelpHtml = "";

        if ($values['enable_need_help'] == true) {
            $needHelpHtml .= "<div class='needHelp'>
                                <a href='javascript:void(0)' data-toggle='modal' data-target='#widgetPopup' data-backdrop='static' data-keyboard='false'>
                                    <i class='floating-icon fa fa-chevron-right'></i>
                                    <h6>" . pll__('Need help?') . "</h6>
                                    <p>" . pll__('We\'ll guide you') . "</p>
                                </a>
                              </div>";
        }

        //In below call change '/' . getUriSegment(1) . '/' .pll__('results') to pll__('results') in case you want to submit it on the same URL struture like on provider details page.

        $formNew = $this->getMobileSearchBoxContentHtml($values, $needHelpHtml, $supplierHtml, pll__("Search Deals"), false, "", '/telecom/' . pll__('results'));
        return $formNew;
    }

    function cleanArrayData(&$data)
    {
        foreach ($data as $key => $val) {
            if (!is_array($val) || is_object($val)) {
                $data[$key] = sanitize_text_field($val);
            }
        }
    }

    function loadFormStyles()
    {
        wp_enqueue_style('anbsearch_bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css');
        wp_enqueue_style('anbsearch_font_awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
        wp_enqueue_style('anbsearch_normalize', plugin_dir_url(__FILE__) . 'css/normalize.css');
        wp_enqueue_style('anbsearch_default', plugin_dir_url(__FILE__) . 'css/default.css');
    }

    function loadJqSumoSelect()
    {
        wp_enqueue_style('jq_sumoselect_css', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.sumoselect/3.0.2/sumoselect.min.css');
        wp_enqueue_script('jq_sumoselect_js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.sumoselect/3.0.2/jquery.sumoselect.min.js');
    }

    function loadBootstrapSelect()
    {
        wp_enqueue_style('jq_bootstrapselect_css', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.2/css/bootstrap-select.min.css');
        wp_enqueue_script('jq_bootstrapselect_js', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.2/js/bootstrap-select.min.js');
    }

    function getSuppliers($params = [])
    {
        //'cat' => ['internet', 'idtv', 'telephony', 'mobile', 'packs'],
        $atts = array(
            'cat' => ['internet', 'packs'],//products relevant to internet and pack products
            'pref_cs' => '',
            'lang' => 'nl',
            'detaillevel' => ['null']

        );

        $params = $params + $atts;

        $params = array_filter($params);//remove empty entries

        $suppliers = $this->anbApi->getSuppliers($params);

        return json_decode($suppliers);
    }


    /**
     * @param $value
     */
    public function comaSepToArray(&$value)
    {
        //convert coma separated values to array
        if ((isset($value) && (boolean)strpos($value, ",") !== false)) {
            $value = explode(",", $value);
        }
    }

    /**
     * @param $selectedSuppliers
     *
     * @return string
     */
    protected function generateSupplierHtml($selectedSuppliers = [])
    {
        //Generate option HTML for suppliers
        $suppliers = $this->getSuppliers();
        $supplierHtml = "<div class='form-group'>
                            <label for='provider_preferences'>" . pll__('Provider preferences') . "</label>
                            <!--<input type='text' class='form-control' id='provider_preferences' placeholder='Select Provider'>-->
                            <select name='pref_cs[]' id='provider_preferences' class='form-control 
                            custom-select' data-live-search='true' title='" . pll__('Select Provider') . "' data-selected-text-format='count > 3' 
                            data-size='10'  data-actions-box='true' multiple>";
        foreach ($suppliers as $supplier) {
            if (!empty($selectedSuppliers)) {
                $selected = '';
                if (in_array($supplier, $selectedSuppliers)) {
                    $selected = 'selected';
                }
                $supplierHtml .= "<option value='{$supplier->supplier_id}' {$selected}>{$supplier->name}</option>";
            } else {
                $supplierHtml .= "<option value='{$supplier->supplier_id}' selected>{$supplier->name}</option>";
            }
        }
        $supplierHtml .= "    </select>
                          </div>";

        return $supplierHtml;
    }

    protected function generateHiddenSupplierHtml($supplierId)
    {
        return "<input type='hidden' name='pref_cs[]' value='{$supplierId}' />";
    }

    /**
     * @param $value
     */
    private function plainInputToArray(&$value)
    {
        //store cat in array if it's plain value
        if (!empty($value) && !is_array($value)) {
            $value = [$value];
        }
    }

    /**
     * @param $value
     */
    protected function convertMultiValToArray($value)
    {
        $this->comaSepToArray($value);
        $this->plainInputToArray($value);
    }

    /**
     * @param $product
     *
     * @return string
     */
    function getServiceDetail($product, $listView = false)
    {
        $servicesHtml = '';
        if(isset($product->packtypes)) {
            foreach ($product->packtypes as $key => $packType) {

                $features = $packType->core_features->{$key};
                if($listView) {
                    $servicesHtml .= '<div class="col-md-3">'.
                        $this->generateServiceDetailHtml($key, $packType->product_name, $features, $listView).
                        '</div>';
                } else {
                    $servicesHtml .= $this->generateServiceDetailHtml($key, $packType->product_name, $features, $listView);
                }
            }
        } else {
            $features = $product->core_features->internet;
            $service = ($product->producttype == 'mobile_internet') ? 'mobile_internet' : 'internet';
            $servicesHtml = $this->generateServiceDetailHtml($service, $product->product_name, $features, $listView);
            if($listView) {
                $servicesHtml = '<div class="col-md-3">'.
                    $this->generateServiceDetailHtml($service, $product->product_name, $features, $listView).
                    '</div>';
            }
        }

        return $servicesHtml;
    }

    function generateServiceDetailHtml ($service, $productName, $features = '', $listView = false) {
        $featuresHtml = '';
        foreach ($features as $feature) {
            $featuresHtml .= '<li>' . $feature->label . '</li>';
        }
        $serviceLabel = '<h6>' . $productName . '</h6>';
        if($listView === true) {
            $serviceLabel = '';
        }
        if($service == 'mobile_internet'){
            $iconHTML = '<img src="'.get_bloginfo('template_url').'/images/print-images/mobile-data-sim.svg">';
        } else {
            $iconHTML = '<i class="print-hide service-icons ' . $service . '"></i>
                        <img src="'.get_bloginfo('template_url').'/images/print-images/'. $service .'.svg" class="print-show">';
        }

        return '<div class="packageDetail ' . $service . '">
                    <div class="iconWrapper">'.$iconHTML.'</div>
                    '.$serviceLabel.'
                    <ul class="list-unstyled pkgSummary">
                       ' . $featuresHtml . '
                    </ul>
                </div>';
    }

    /**
     * @param $anbTopDeals
     * @param $currentProduct
     *
     * @return array
     */
    public function extractProductData($anbTopDeals, $currentProduct, $withCalcHtml = false)
    {
        // prepare data
        $productData = $anbTopDeals->prepareProductData($currentProduct);

        //Price HTML
        $priceHtml = $anbTopDeals->getPriceHtml($productData, $withCalcHtml);

        //Services HTML
        $servicesHtml = $anbTopDeals->getServicesHtml($productData);

        return array($productData, $priceHtml, $servicesHtml);
    }

    /**
     * @param $values
     * @param string $needHelpHtml
     * @param string $supplierHtml
     * @param string $submitBtnTxt
     * @param bool $hideTitle
     * @param string $infoMsg
     * @param string $resultsPageUri
     *
     * @return string
     */
    public function getSearchBoxContentHtml(
        $values, $needHelpHtml = "", $supplierHtml = "", $submitBtnTxt = "Search Deals",
        $hideTitle = false, $infoMsg = "", $resultsPageUri = self::RESULTS_PAGE_URI
    )
    {
        $titleHtml = "<h3>" . pll__('Search') . "</h3>";
        if ($hideTitle) {
            $titleHtml = "";
        }

        $hiddenMultipleProvidersHtml = $this->getSuppliersHiddenInputFields($values, $supplierHtml);

        $formNew = "<div class='searchBoxContent'>
                    <div class='searchBox'>
                        " . $needHelpHtml . "
                        " . $titleHtml . "
                        <p class='caption'>" . pll__('Select the service you like to compare') . "</p>
                        <div class='formWrapper'>
                            <form action='" . $resultsPageUri . "' id='anbSearchForm'>
                                <div class='form-group'>
                                    <label>" . pll__('Services') . "</label>
                                    <div class='selectServices'>
                                        <ul class='list-unstyled'>
                                            <li>
                                                <div>
                                                    <input name='cat[]' id='internet_service' checked='checked' onclick='event.preventDefault();' type='checkbox' value='internet'>
                                                    <label for='internet_service'>
                                                        <span class='icon'>
                                                            <i class='sprite sprite-wifi'></i>
                                                        </span>
                                                        <span class='description'>" . pll__('Internet') . "</span>
                                                        <span class='tick-icon'>
                                                            <i class='fa fa-check'></i>
                                                            <i class='fa fa-square-o'></i>
                                                        </span>
                                                    </label>
                                                </div>
                                            </li>
                                            <li>
                                                <div>
                                                    <input name='cat[]' id='tv_service' type='checkbox' value='tv' 
                                                    " . ((in_array("tv", $values['cat']) === true) ? 'checked="checked"' : '') . ">
                                                    <label for='tv_service'>
                                                        <span class='icon'>
                                                            <i class='sprite sprite-tv'></i>
                                                        </span>
                                                        <span class='description'>" . pll__('TV') . "</span>
                                                        <span class='tick-icon'>
                                                            <i class='fa fa-check'></i>
                                                            <i class='fa fa-square-o'></i>
                                                        </span>
                                                    </label>
                                                </div>
                                            </li>
                                            <li>
                                                <div>
                                                    <input name='cat[]' id='telephone_service' type='checkbox' value='telephone'
                                                    " . ((in_array("telephone", $values['cat']) === true) ? 'checked="checked"' : '') . ">
                                                    <label for='telephone_service'>
                                                        <span class='icon'>
                                                            <i class='sprite sprite-phone'></i>
                                                        </span>
                                                        <span class='description'>" . pll__('Fixed line') . "</span>
                                                        <span class='tick-icon'>
                                                            <i class='fa fa-check'></i>
                                                            <i class='fa fa-square-o'></i>
                                                        </span>
                                                    </label>
                                                </div>
                                            </li>
                                            <li>
                                                <div>
                                                    <input name='cat[]' id='mobile_service' type='checkbox' value='gsm'
                                                    " . ((in_array("gsm", $values['cat']) === true) ? 'checked="checked"' : '') . ">
                                                    <label for='mobile_service'>
                                                        <span class='icon'>
                                                            <i class='sprite sprite-mobile'></i>
                                                        </span>
                                                        <span class='description'>" . pll__('Mobile') . "</span>
                                                        <span class='tick-icon'>
                                                            <i class='fa fa-check'></i>
                                                            <i class='fa fa-square-o'></i>
                                                        </span>
                                                    </label>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>    
                                </div>
                                {$infoMsg}
                                <div class='form-group'>
                                    <label for='installation_area'>" . pll__('Installation area') . "</label>
                                    <input type='text' class='form-control typeahead' id='installation_area' name='zip' 
                                      value='" . ((!empty($values['zip'])) ? $values['zip'] : '') . "' placeholder='" . pll__('Enter Zipcode') . "'
                                      data-error='" . pll__('Please enter valid zip code') . "' autocomplete='off' query_method='cities' query_key='postcode' required>
                                </div>
                                {$supplierHtml}";
        $formNew.= "<div class='form-group11'>
                                    <div class='check fancyCheck'>
                                        <input name='sg' value='consumer' type='hidden' id='valconsumer'>
                                        <input name='sg' id='showBusinessDeal' class='radio-salutation' value='sme' type='checkbox' " . (("sme" == $values['sg']) ? 'checked="checked"' : '') . ">
                                        <label for='showBusinessDeal'>
                                            <i class='fa fa-circle-o unchecked' id='disableconsumer'></i>
                                            <i class='fa fa-check-circle checked' id='enableconsumer'></i>
                                            <span>". pll__('Show business deals') ."</span>
                                        </label>
                                    </div>
                                </div>                                
                                <div class='btnWrapper'>
                                    {$hiddenMultipleProvidersHtml}
                                    <button name='searchSubmit' type='submit' class='btn btn-default btn-block' >$submitBtnTxt</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>";

        return $formNew;
    }

    /**
     * @param $values
     * @param string $needHelpHtml
     * @param string $supplierHtml
     * @param string $submitBtnTxt
     * @param bool $hideTitle
     * @param string $infoMsg
     * @param string $resultsPageUri
     *
     * @return string
     */
    public function getMobileSearchBoxContentHtml(
        $values, $needHelpHtml = "", $supplierHtml = "", $submitBtnTxt = "Search Deals",
        $hideTitle = false, $infoMsg = "", $resultsPageUri = self::RESULTS_PAGE_URI
    )
    {
        $titleHtml = "<h3>" . pll__('Search') . "</h3>";
        if ($hideTitle) {
            $titleHtml = "";
        }

        $hiddenMultipleProvidersHtml = $this->getSuppliersHiddenInputFields($values, $supplierHtml);

        $formNew = "<div class='searchBoxContent'>
                    <div class='searchBox'>
                        " . $needHelpHtml . "
                        " . $titleHtml . "
                        <p class='caption'>" . pll__('Select the service you like to compare') . "</p>
                        <div class='formWrapper'>
                            <form action='" . $resultsPageUri . "' id='anbSearchForm'>
                                <div class='form-group'>
                                    <label>" . pll__('Services') . "</label>
                                    <div class='selectServices mobileSelectServices'>
                                        <ul class='list-unstyled service-tabs'>
                                            <li>
                                                <div>
                                                    <input name='cat[]' id='mobile_service' type='radio' value='mobile'
                                                    " . ((in_array("mobile", $values['cat']) === true) ? 'checked="checked"' : '') . ">
                                                    <label for='mobile_service'>
                                                        <span class='icon'>
                                                            <i class='sprite sprite-mobile'></i>
                                                        </span>
                                                        <span class='description'>" . pll__('Mobile') . "</span>
                                                        <span class='check-box'></span>
                                                    </label>
                                                </div>
                                            </li>
                                            <li>
                                                <div>
                                                    <input name='cat[]' id='mobile_internet' type='radio' value='mobile_internet'
                                                    " . ((in_array("mobile_internet", $values['cat']) === true) ? 'checked="checked"' : '') . ">
                                                    <label for='mobile_internet'>
                                                        <span class='icon mobile_internet'>
                                                            
                                                            <svg class='svg-mobile-data-sim'> <use xlink:href='".get_bloginfo('template_url') ."/images/svg-sprite.svg#svg-mobile-data-sim'></use> </svg>
                                                        </span>
                                                        <span class='description'>" . pll__('Mobile Internet') . "</span>
                                                        <span class='check-box'></span>
                                                    </label>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>    
                                </div>
                                {$infoMsg}
                                <div class='form-group'>
                                    <label for='installation_area'>" . pll__('Installation area') . "</label>
                                    <input type='text' class='form-control typeahead' id='installation_area' name='zip' 
                                      value='" . ((!empty($values['zip'])) ? $values['zip'] : '') . "' placeholder='" . pll__('Enter Zipcode') . "'
                                      data-error='" . pll__('Please enter valid zip code') . "' autocomplete='off' query_method='cities' query_key='postcode' required>
                                </div>
                                {$supplierHtml}";
        $formNew.= "<div class='form-group11'>
                                    <div class='check fancyCheck'>
                                        <input name='sg' value='consumer' type='hidden' id='valconsumer'>
                                        <input name='sg' id='showBusinessDeal' class='radio-salutation' value='sme' type='checkbox' " . (("sme" == $values['sg']) ? 'checked="checked"' : '') . ">
                                        <label for='showBusinessDeal'>
                                            <i class='fa fa-circle-o unchecked' id='disableconsumer'></i>
                                            <i class='fa fa-check-circle checked' id='enableconsumer'></i>
                                            <span>". pll__('Show business deals') ."</span>
                                        </label>
                                    </div>
                                </div>                                
                                <div class='btnWrapper'>
                                    {$hiddenMultipleProvidersHtml}
                                    <button name='searchSubmit' type='submit' class='btn btn-default btn-block' >$submitBtnTxt</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>";

        return $formNew;
    }

    function getSuppliersHiddenInputFields ($values, $supplierHtml="") {
        $hiddenMultipleProvidersHtml = "";

        if (empty($supplierHtml)) {//If no supplier html generated but pref_cs are present keep them included as hidden values
            $hiddenMultipleProvidersHtml .= '<div id="wizard_popup_pref_cs" class="hidden">';
            foreach ($values['pref_cs'] as $provider) {
                $hiddenMultipleProvidersHtml .= "<input type='hidden' name='pref_cs[]' value='" . $provider . "' />";
            }
            $hiddenMultipleProvidersHtml .= '</div>';
        }

        return $hiddenMultipleProvidersHtml;
    }

    /**
     * @param $values
     * @param string $submitBtnTxt
     * @param bool $hideTitle
     * @param string $resultsPageUri
     * @param string $supplierHtml
     *
     * @return string
     */
    public function getWizardSearchBoxContentHtml($values, $submitBtnTxt = "Search Deals", $hideTitle = false, $resultsPageUri = self::RESULTS_PAGE_URI, $supplierHtml = "")
    {
        $titleHtml = "<h3>" . pll__('Change Profile') . "</h3>";
        if ($hideTitle) {
            $titleHtml = "";
        }

        $hiddenMultipleProvidersHtml = $this->getSuppliersHiddenInputFields($values, $supplierHtml);
        $hiddenMultipleProvidersHtml = '';

        $formNew = "<div class='formWrapper'>
                        <form action='" . $resultsPageUri . "' class='form-horizontal' id='yourProfileWizardForm' data-toggle='validator' role='form'>
                        	<div class='container-fluid'>
	                            <div class='panel-group' id='accordion' role='tablist' aria-multiselectable='true'>
	                            
	                                <!--Compare-->	                            	
	                            	<div class='panel panel-default' id='comparePanel'>
                                        <div class='panel-heading active' role='tab' id='CompareHeading'>
                                            <h4 class='panel-title'>
                                                <a role='button' data-toggle='collapse' data-parent='#accordion'
                                                   href='#compareCompPanel' aria-expanded='true'
                                                   aria-controls='collapseOne'>
                                                        <span class='headingTitle hasSelectedValue'>
                                                            <i class='icon wizard multidevice'></i>
                                                            <span class='caption'>
                                                                <span class='caption_close'>" . pll__('compare') . "</span>
                                                                <span class='caption_open'>" . pll__('compare') . "</span>
                                                            </span>
                                                            <span class='selectedInfo'></span>
                                                        </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='compareCompPanel' class='panel-collapse collapse in' role='tabpanel'
                                             aria-labelledby='headingOne'  data-wizard-panel='compare' aria-expanded='true'>
                                            <div class='panel-body text-center'>
                                                <div class='form-group'>
                                                    <div class='selectServicesComp'>
                                                        <ul class='list-unstyled'>
                                                            <li>
                                                                <div>
                                                                    <input name='cat[]' id='internet_service_wiz' checked='checked' onclick='event.preventDefault();' type='checkbox' value='internet'>
                                                                    <label for='internet_service_wiz'>
                                                                        <span class='icon'>
                                                                        <i class='sprite sprite-wifi'></i>
                                                                        </span>
                                                                        <span class='description'>".pll__('Internet')."</span>
                                                                        <span class='tick-icon'>
                                                                        <i class='fa fa-check'></i>
                                                                        <i class='fa fa-square-o'></i>
                                                                        </span>
                                                                    </label>
                                                                </div>
                                                            </li>
                                                            <li>
                                                                <div>
                                                                    <input name='cat[]' id='tv_service_wiz' type='checkbox' value='tv' " . ((in_array("tv", $values['cat']) === true) ? 'checked="checked"' : '') . ">
                                                                    <label for='tv_service_wiz'>
                                                                        <span class='icon'>
                                                                        <i class='sprite sprite-tv'></i>
                                                                        </span>
                                                                        <span class='description'>".pll__('TV')."</span>
                                                                        <span class='tick-icon'>
                                                                        <i class='fa fa-check'></i>
                                                                        <i class='fa fa-square-o'></i>
                                                                        </span>
                                                                    </label>
                                                                </div>
                                                            </li>
                                                            <li>
                                                                <div>
                                                                    <input name='cat[]' id='telephone_service_wiz' type='checkbox' value='telephone' " . ((in_array("telephone", $values['cat']) === true) ? 'checked="checked"' : '') . ">
                                                                    <label for='telephone_service_wiz'>
                                                                        <span class='icon'>
                                                                        <i class='sprite sprite-phone'></i>
                                                                        </span>
                                                                        <span class='description'>".pll__('Fixed line')."</span>
                                                                        <span class='tick-icon'>
                                                                        <i class='fa fa-check'></i>
                                                                        <i class='fa fa-square-o'></i>
                                                                        </span>
                                                                    </label>
                                                                </div>
                                                            </li>
                                                            <li>
                                                                <div>
                                                                    <input name='cat[]' id='mobile_service_wiz' type='checkbox' value='gsm' " . ((in_array("gsm", $values['cat']) === true) ? 'checked="checked"' : '') . ">
                                                                    <label for='mobile_service_wiz'>
                                                                        <span class='icon'>
                                                                        <i class='sprite sprite-mobile'></i>
                                                                        </span>
                                                                        <span class='description'>".pll__('Mobile')."</span>
                                                                        <span class='tick-icon'>
                                                                        <i class='fa fa-check'></i>
                                                                        <i class='fa fa-square-o'></i>
                                                                        </span>
                                                                    </label>
                                                                </div>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <div class='staticTooltipWrapper'>
                                                    <div class='staticTooltip'>
                                                        <p>".pll__('Did you know combining services is often much more interesting for your wallet?')." </p>
                                                    </div>
                                                </div>
                                                <div class='buttonWrapper'>
                                                    <button type='button' class='btn btn-primary'>
                                                        <i class='fa fa-check'></i> " . pll__('Ok') . "
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!--Location-->	                            	
	                            	<div class='panel panel-default' id='locationPanel'>
                                        <div class='panel-heading' role='tab' id='installationHeading'>
                                            <h4 class='panel-title'>
                                                <a role='button' data-toggle='collapse' data-parent='#accordion'
                                                   href='#installationPanel' aria-expanded='true'
                                                   aria-controls='collapseOne'>
                                                        <span class='headingTitle'>
                                                            <i class='icon wizard location'></i>
                                                            <span class='caption'>
                                                                <span class='caption_close'>" . pll__('set location') . "</span>
                                                                <span class='caption_open'>" . pll__('Installation area') . "</span>
                                                            </span>
                                                            <span class='selectedInfo'></span>
                                                            <span class='changeInfo'>". pll__('Change') ."</span>
                                                        </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='installationPanel' class='panel-collapse collapse' role='tabpanel'
                                             aria-labelledby='headingOne'  data-wizard-panel='location'>
                                            <div class='panel-body text-center'>
                                                <div class='singleFormWrapper'>
                                                    <div class=''>
                                                        <label for='installation_area' class='control-label'>" . pll__('Installation area') . "</label>
                                                    </div>
                                                    <div class='form-group has-feedback'>
                                                        <div class=''>
                                                            <input class='form-control typeahead' id='installation_area' name='zip'
                                                                   placeholder='" . pll__('Enter Zipcode') . "' maxlength='4'
                                                                   value='" . ((!empty($values['zip'])) ? $values['zip'] : '') . "' required type='text'>
                                                            <span class='staricicon form-control-feedback'
                                                                  aria-hidden='true'></span>
                                                            <div class='help-block with-errors'></div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!--<div class='staticTooltipWrapper'>
                                                            <div class='staticTooltip'>
                                                                <p>".pll__('some tooltip here if required')." </p>
                                                            </div>
                                                        </div>-->
                                                    
                                                    <div class='buttonWrapper'>
                                                        <button type='button' class='btn btn-primary'><i
                                                                    class='fa fa-check'></i> " . pll__('Ok') . "
                                                        </button>
                                                    </div>    
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!--Use-->
                                    <div class='panel panel-default' id='usagePanel'>
                                        <div class='panel-heading' role='tab' id='consumerHeading'>
                                            <h4 class='panel-title'>
                                                <a role='button' data-toggle='collapse' data-parent='#accordion'
                                                   href='#consumerPanel' aria-expanded='true'
                                                   aria-controls='collapseOne'>
                                                            <span class='headingTitle'>
                                                                <i class='icon wizard location'></i>
                                                                <span class='caption'>
                                                                    <span class='caption_close'>" . pll__('Define use') . "</span>
                                                                    <span class='caption_open'>" . pll__('Type of use') . "</span>
                                                                </span>
                                                                <span class='selectedInfo'></span>
                                                                <span class='changeInfo'>". pll__('Change') ."</span>
                                                            </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='consumerPanel' class='panel-collapse collapse' role='tabpanel'
                                             aria-labelledby='headingOne'  data-wizard-panel='use'>
                                            <div class='panel-body text-center'>
                                                <div class='form-group'>
                                                    <label>" . pll__('Type of Use') . "</label>
                                                    <div class='radio fancyRadio'>
                                                        <input name='sg' value='consumer' id='wiz_private_type' type='radio'
                                                               " . (("consumer" == $values['sg'] || empty($values['sg'])) ? 'checked="checked"' : '') . ">
                                                        <label for='wiz_private_type'>
                                                            <i class='fa fa-circle-o unchecked'></i>
                                                            <i class='fa fa-check-circle checked'></i>
                                                            <span>" . pll__('Private') . "</span>
                                                        </label>
                                                        <input name='sg' value='sme' id='wiz_business_type' type='radio'
                                                        " . (("sme" == $values['sg']) ? 'checked="checked"' : '') . ">
                                                        <label for='wiz_business_type'>
                                                            <i class='fa fa-circle-o unchecked'></i>
                                                            <i class='fa fa-check-circle checked'></i>
                                                            <span>" . pll__('Business') . "</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class='buttonWrapper'>
                                                    <button type='button' class='btn btn-primary'><i
                                                                class='fa fa-check'></i> " . pll__('Ok') . "
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!--Family Members-->
                                    <div class='panel panel-default' id='familyPanel'>
                                        <div class='panel-heading' role='tab' id='headingOne'>
                                            <h4 class='panel-title'>
                                                <a role='button' data-toggle='collapse' data-parent='#accordion'
                                                   href='#collapseOne' aria-expanded='true' aria-controls='collapseOne'>
                                                    <span class='headingTitle'>
                                                        <i class='icon wizard user'></i>
                                                        <span class='caption'>
                                                            <span class='caption_close'>" . pll__('Set family members') . "</span>
                                                            <span class='caption_open'>" . pll__('How many members your family have?') . "</span>
                                                        </span>
                                                        <span class='selectedInfo'></i></span>
                                                        <span class='changeInfo'>". pll__('Change') ."</span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='collapseOne' class='panel-collapse collapse' role='tabpanel'
                                             aria-labelledby='headingOne'  data-wizard-panel='familyMembers'>
                                            <div class='panel-body text-center'>
                                    
                                                <div class='totalPersonWizard'>
                                                    <div class='compPanel withStaticToolTip'>
                                                        <div class='selectionPanel clearfix'>
                                                            <fieldset class='person-sel gray fancyComp'>
                                                                <input type='radio' id='person6' name='f' value='6' 
                                                                " . (("6" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person6' title='6 ". pll__('persons') ."'>
                                                                    <span class='person-value'>5+</span>
                                                                    <span class='tick-icon'>
                                                                                            <i class='fa fa-check'></i>
                                                                                        </span>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>6 ". pll__('person is betterInfo about extensive use of internet') ." </p>
                                                                </div>
                                    
                                                                <input type='radio' id='person5' name='f' value='5' 
                                                                " . (("5" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person5' title='5 ". pll__('persons') ."'>
                                                                    <span class='person-value'>5</span>
                                                                    <span class='tick-icon'>
                                                                                        <i class='fa fa-check'></i>
                                                                                    </span>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>". pll__('5th Person. Info about extensive use of internet.') .". </p>
                                                                </div>
                                    
                                                                <input type='radio' id='person4' name='f' value='4' 
                                                                " . (("4" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person4' title='4 ". pll__('persons') ."'>
                                                                    <span class='person-value'>4</span>
                                                                    <span class='tick-icon'>
                                                                                        <i class='fa fa-check'></i>
                                                                                    </span>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>". pll__('4thInfo about extensive use of internet') ." </p>
                                                                </div>
                                    
                                                                <input type='radio' id='person3' name='f' value='3' 
                                                                " . (("3" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person3' title='3 ". pll__('persons') ."'>
                                                                    <span class='person-value'>3</span>
                                                                    <span class='tick-icon'>
                                                                                        <i class='fa fa-check'></i>
                                                                                    </span>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>". pll__('3rd Info about extensive use of internet') ."</p>
                                                                </div>
                                    
                                                                <input type='radio' id='person2' name='f' value='2' 
                                                                " . (("2" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person2' title='2 ". pll__('persons') ."'>
                                                                    <span class='person-value'>2</span>
                                                                    <span class='tick-icon'>
                                                                                        <i class='fa fa-check'></i>
                                                                                    </span>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>". pll__('Two person info about extensive use of internet') ."</p>
                                                                </div>
                                    
                                                                <input type='radio' id='person1' name='f' value='1' 
                                                                " . (("1" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person1' title='1 ". pll__('person') ."'>
                                                                    <span class='person-value'>1</span>
                                                                    <span class='tick-icon'>
                                                                                        <i class='fa fa-check'></i>
                                                                                    </span>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>". pll__('only one person. Info about extensive use of internet') ."</p>
                                                                </div>
                                    
                                                            </fieldset>
                                                        </div>
                                                    </div>
                                    
                                                    <div class='staticTooltipWrapper'>
                                                        <div class='staticTooltip'>
                                                            <p>".pll__('Select an option to view information about it')." </p>
                                                        </div>
                                                    </div>
                                    
                                                    <div class='buttonWrapper'>
                                                        <button type='button' class='btn btn-primary'><i
                                                                class='fa fa-check'></i> ". pll__('Ok')."
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!--Devices-->
                                    <div class='panel panel-default' id='devicesPanel'>
                                        <div class='panel-heading' role='tab' id='headingTwo'>
                                            <h4 class='panel-title'>
                                                <a class='collapsed' role='button' data-toggle='collapse'
                                                   data-parent='#accordion' href='#collapseTwo' aria-expanded='false'
                                                   aria-controls='collapseTwo'>
                                                            <span class='headingTitle'>
                                                                <i class='icon wizard multidevice'></i>
                                                                <span class='caption'>
                                                                    <span class='caption_close'>" . pll__('Total devices') . "</span>
                                                                    <span class='caption_open'>"  . pll__('How many devices do you have?') . "</span>
                                                                </span>
                                                                <span class='selectedInfo'></span>
                                                                <span class='changeInfo'>". pll__('Change') ."</span>
                                                            </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='collapseTwo' class='panel-collapse collapse' role='tabpanel'
                                             aria-labelledby='headingTwo'  data-wizard-panel='devices'>
                                            <div class='panel-body text-center'>
                                                <div class='counterPanel'>
                                                    <ul class='list-unstyled'>
                                                        <li>
                                                            <div class='itemWrapper'>
                                                                <div class='counterBox'>
                                                                    <input class='form-control' id='num_pc'
                                                                           name='num_pc' placeholder='" . pll__('Number of PCs') . "'
                                                                           maxlength='4' pattern='^\d{1,3}$' value='" . ((!empty($values['num_pc'])) ? $values['num_pc'] : 0) . "'
                                                                           type='text'>
                                                                    <span class='counterBtn dec'>
                                                                                <a href='#' data-value='-'><i
                                                                                            class='fa fa-minus-circle'></i></a>
                                                                            </span>
                                                                    <div class='counterWrapper'>
                                                                        <span class='currentValue'>" . ((!empty($values['num_pc'])) ? $values['num_pc'] : 0) . "</span>
                                                                        <label class='label'>
                                                                                <span class='icon'>
                                                                                    <i class='device-icon md computer'></i>
                                                                                </span>
                                                                            <span class='caption'>" . pll__('Computers') . "</span>
                                                                        </label>
                                                                    </div>
                                                                    <span class='counterBtn inc'>
                                                                                <a href='#' data-value='+'><i
                                                                                            class='fa fa-plus-circle'></i></a>
                                                                            </span>
                                                                </div>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class='itemWrapper'>
                                                                <div class='counterBox'>
                                                                    <input type='number' name='num_tv' id='tv_counter' value='" . ((!empty($values['num_tv'])) ? $values['num_tv'] : 0) . "'>
                                                                    <span class='counterBtn dec'>
                                                                        <a href='#' data-value='-'><i class='fa fa-minus-circle'></i></a>
                                                                    </span>
                                                                    <div class='counterWrapper'>
                                                                        <span class='currentValue'>" . ((!empty($values['num_tv'])) ? $values['num_tv'] : 0) . "</span>
                                                                        <label class='label'>
                                                                            <span class='icon'>
                                                                                <i class='device-icon md tv'></i>
                                                                            </span>
                                                                            <span class='caption'>" . pll__('Television') . "</span>
                                                                        </label>
                                                                    </div>
                                                                    <span class='counterBtn inc'>
                                                                        <a href='#' data-value='+'><i class='fa fa-plus-circle'></i></a>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class='itemWrapper'>
                                                                <div class='counterBox'>
                                                                    <input type='number' name='num_smartphones' id='phone_counter' value='" . ((!empty($values['num_smartphones'])) ? $values['num_smartphones'] : 0) . "'>
                                                                    <span class='counterBtn dec'>
                                                                        <a href='#' data-value='-'><i class='fa fa-minus-circle'></i></a>
                                                                    </span>
                                                                    <div class='counterWrapper'>
                                                                        <span class='currentValue'>" . ((!empty($values['num_smartphones'])) ? $values['num_smartphones'] : 0) . "</span>
                                                                        <label class='label'>
                                                                            <span class='icon'>
                                                                                <i class='device-icon md smartphone'></i>
                                                                            </span>
                                                                            <span class='caption'>" . pll__('Smartphones') . "</span>
                                                                        </label>
                                                                    </div>
                                                                    <span class='counterBtn inc'>
                                                                        <a href='#' data-value='+'><i class='fa fa-plus-circle'></i></a>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class='itemWrapper'>
                                                                <div class='counterBox'>
                                                                    <input type='number' name='num_tablets' id='tablet_counter' value='" . ((!empty($values['num_tablets'])) ? $values['num_tablets'] : 0) . "'>
                                                                    <span class='counterBtn dec'>
                                                                        <a href='#' data-value='-'><i class='fa fa-minus-circle'></i></a>
                                                                    </span>
                                                                    <div class='counterWrapper'>
                                                                        <span class='currentValue'>" . ((!empty($values['num_tablets'])) ? $values['num_tablets'] : 0) . "</span>
                                                                        <label class='label'>
                                                                            <span class='icon'>
                                                                                <i class='device-icon md tablet'></i>
                                                                            </span>
                                                                            <span class='caption'>" . pll__('Tablets') . "</span>
                                                                        </label>
                                                                    </div>
                                                                    <span class='counterBtn inc'>
                                                                        <a href='#' data-value='+'><i class='fa fa-plus-circle'></i></a>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                    <!--div class='info'>
                                                        <p>Some information will come here as well just like any other
                                                            information so current putting lorem ipsum to see how it
                                                            looks</p>
                                                    </div-->
                                                    <div class='buttonWrapper'>
                                                        <button type='button' class='btn btn-primary'><i
                                                                    class='fa fa-check'></i> " . pll__('Ok') . "
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!--Internet Needs -->
                                    <div class='panel panel-default' id='internetPanel'>
                                        <div class='panel-heading' role='tab' id='headingInternetNeeds'>
                                            <h4 class='panel-title'>
                                                <a class='collapsed' role='button' data-toggle='collapse'
                                                   data-parent='#accordion' href='#collapseInternetNeeds' aria-expanded='false'
                                                   aria-controls='collapseInternetNeeds'>
                                                    <span class='headingTitle'>
                                                        <i class='icon wizard internet'></i>
                                                        <span class='caption'>
                                                            <span class='caption_close'>" . pll__('Set internet usage') . "</span>
                                                            <span class='caption_open'>".pll__('What are your internet needs?')."</span>
                                                        </span>
                                                        <span class='selectedInfo'></span>
                                                        <span class='changeInfo'>". pll__('Change') ."</span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='collapseInternetNeeds' class='panel-collapse collapse' role='tabpanel'
                                             aria-labelledby='headingInternetNeeds' data-wizard-panel='internetNeeds'>
                                            <div class='panel-body'>
                                                <div class='compWrapper withStaticToolTip'>
                                                    <ul class='list-unstyled radioComp tickOption'>
                                                        <li>
                                                            <input type='radio' name='ms_internet' id='internet_need_high' value='2'
                                                            " . (("2" == $values['ms_internet']) ? 'checked="checked"' : '') . ">
                                                            <label for='internet_need_high'>
                                                                <i class='icon-wifi lg'></i>
                                                                ".pll__('Extended')."
                                                                <i class='checkOption fa fa-check'></i>
                                                                <div class='tooltip'>
                                                                    <p>".pll__('Info about extensive use of internet')." </p>
                                                                </div>
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <input type='radio' name='ms_internet' id='internet_need_low' value='0'
                                                            " . (("0" == $values['ms_internet']) ? 'checked="checked"' : '') . ">
                                                            <label for='internet_need_low'>
                                                                <i class='icon-wifi'></i>
                                                                ". pll__('Low')."
                                                                <i class='checkOption fa fa-check'></i>
                                                                <div class='tooltip'>
                                                                    <p>". pll__('Info about little use of internet'). " </p>
                                                                </div>
                                                            </label>
                                                        </li>
                                                    </ul>
                                    
                                                    <!--only activates if the tooltip in the component is described in a way to hide -->
                                                    <!---->
                                                    <div class='staticTooltipWrapper'>
                                                        <div class='staticTooltip'>
                                                            <p>".pll__('Select an option to view information about it')." </p>
                                                        </div>
                                                    </div>
                                                </div>
                                    
                                                <div class='buttonWrapper'>
                                                    <button type='button' class='btn btn-primary'><i
                                                            class='fa fa-check'></i> " . pll__('Ok') . "
                                                    </button>
                                                </div>
                                    
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!--Tv Needs -->
                                    <div class='panel panel-default' id='televisionPanel'>
                                        <div class='panel-heading' role='tab' id='headingTVNeeds'>
                                            <h4 class='panel-title'>
                                                <a class='collapsed' role='button' data-toggle='collapse'
                                                   data-parent='#accordion' href='#collapseTVNeeds' aria-expanded='false'
                                                   aria-controls='collapseTVNeeds'>
                                                    <span class='headingTitle'>
                                                        <i class='icon wizard tv'></i>
                                                        <span class='caption'>
                                                            <span class='caption_close'>" . pll__('Set Television needs') . "</span>
                                                            <span class='caption_open'>".pll__('What are your tv needs?')."</span>
                                                        </span>
                                                        <span class='selectedInfo'></span>
                                                        <span class='changeInfo'>". pll__('Change') ."</span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='collapseTVNeeds' class='panel-collapse collapse' role='tabpanel'
                                             aria-labelledby='headingTVNeeds' data-wizard-panel='tvNeeds'>
                                            <div class='panel-body'>
                                                <div class='compWrapper withStaticToolTip'>
                                                    <ul class='list-unstyled radioComp tickOption'>
                                                        <li>
                                                            <input type='radio' name='ms_idtv' id='tv_need_high' value='2'
                                                            " . (("2" == $values['ms_idtv']) ? 'checked="checked"' : '') . ">
                                                            <label for='tv_need_high'>
                                                                <i class='icon-tv lg'></i>
                                                                ".pll__('Extended')."
                                                                <i class='checkOption fa fa-check'></i>
                                                                <div class='tooltip'>
                                                                    <p>".pll__('Info about extensive use of television')." </p>
                                                                </div>
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <input type='radio' name='ms_idtv' id='tv_need_low' value='0'
                                                            " . (("0" == $values['ms_idtv']) ? 'checked="checked"' : '') . ">
                                                            <label for='tv_need_low'>
                                                                <i class='icon-tv'></i>
                                                                ". pll__('Low')."
                                                                <i class='checkOption fa fa-check'></i>
                                                                <div class='tooltip'>
                                                                    <p>". pll__('Info about little use of television'). " </p>
                                                                </div>
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <input type='radio' name='ms_idtv' id='tv_need_no' value='-1'
                                                            " . (("-1" == $values['ms_idtv']) ? 'checked="checked"' : '') . ">
                                                            <label for='tv_need_no' class='noNeed'>
                                                                <i class='icon-block'></i>
                                                                " . pll__('No need') . "
                                                                <i class='checkOption fa fa-check'></i>
                                                                <div class='tooltip'>
                                                                    <p>". pll__("Info about if you hate to watch tv") . " </p>
                                                                </div>
                                                            </label>
                                                        </li>
                                                    </ul>
                                    
                                                    <!--only activates if the tooltip in the component is described in a way to hide -->
                                                    <!---->
                                                    <div class='staticTooltipWrapper'>
                                                        <div class='staticTooltip'>
                                                            <p>".pll__('Select an option to view information about it')." </p>
                                                        </div>
                                                    </div>
                                                </div>
                                    
                                                <div class='buttonWrapper'>
                                                    <button type='button' class='btn btn-primary'><i
                                                            class='fa fa-check'></i> " . pll__('Ok') . "
                                                    </button>
                                                </div>
                                    
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!--Fixed Lines -->
                                    <div class='panel panel-default' id='fixedLinePanel'>
                                        <div class='panel-heading' role='tab' id='headingFixedLine'>
                                            <h4 class='panel-title'>
                                                <a class='collapsed' role='button' data-toggle='collapse'
                                                   data-parent='#accordion' href='#collapseFixedLine' aria-expanded='false'
                                                   aria-controls='collapseInternetNeeds'>
                                                    <span class='headingTitle'>
                                                        <i class='icon wizard phone'></i>
                                                        <span class='caption'>
                                                            <span class='caption_close'>" . pll__('Set fixed line needs') . "</span>
                                                            <span class='caption_open'>".pll__('What are your needs for Fixed line?')."</span>
                                                        </span>
                                                        <span class='selectedInfo'></span>
                                                        <span class='changeInfo'>". pll__('Change') ."</span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='collapseFixedLine' class='panel-collapse collapse' role='tabpanel'
                                             aria-labelledby='headingInternetNeeds' data-wizard-panel='fixedLineUse'>
                                            <div class='panel-body'>
                                                <div class='compWrapper withStaticToolTip'>
                                                    <ul class='list-unstyled radioComp tickOption'>
                                                        <li>
                                                            <input type='radio' name='ms_fixed' id='phone_need_high' value='2'
                                                            " . (("2" == $values['ms_fixed']) ? 'checked="checked"' : '') . ">
                                                            <label for='phone_need_high'>
                                                                <i class='icon-phone lg'></i>
                                                                ".pll__('Extensive')."
                                                                <i class='checkOption fa fa-check'></i>
                                                                <div class='tooltip'>
                                                                    <p>".pll__('Info about extensive use of phone')." </p>
                                                                </div>
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <input type='radio' name='ms_fixed' id='phone_need_low' value='0'
                                                            " . (("0" == $values['ms_fixed']) ? 'checked="checked"' : '') . ">
                                                            <label for='phone_need_low'>
                                                                <i class='icon-phone'></i>
                                                                ". pll__('Basic')."
                                                                <i class='checkOption fa fa-check'></i>
                                                                <div class='tooltip'>
                                                                    <p>". pll__('Info about little use of phone'). " </p>
                                                                </div>
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <input type='radio' name='ms_fixed' id='phone_need_no' value='-1'
                                                            " . (("-1" == $values['ms_fixed']) ? 'checked="checked"' : '') . ">
                                                            <label for='phone_need_no' class='noNeed'>
                                                                <i class='icon-block'></i>
                                                                " . pll__('No need') . "
                                                                <i class='checkOption fa fa-check'></i>
                                                                <div class='tooltip'>
                                                                    <p>". pll__("Info about if you really don't want to use internet") . " </p>
                                                                </div>
                                                            </label>
                                                        </li>
                                                    </ul>
                                    
                                                    <!--only activates if the tooltip in the component is described in a way to hide -->
                                                    <!---->
                                                    <div class='staticTooltipWrapper'>
                                                        <div class='staticTooltip'>
                                                            <p>".pll__('Select an option to view information about it')." </p>
                                                        </div>
                                                    </div>
                                                </div>
                                    
                                                <div class='buttonWrapper'>
                                                    <button type='button' class='btn btn-primary'><i
                                                            class='fa fa-check'></i> " . pll__('Ok') . "
                                                    </button>
                                                </div>
                                    
                                            </div>
                                        </div>
                                    </div>

                                    <!--Mobile Subscription-->
                                    <div class='panel panel-default' id='mobilePanel'>
                                        <div class='panel-heading' role='tab' id='headingThree'>
                                            <h4 class='panel-title'>
                                                <a class='collapsed' role='button' data-toggle='collapse'
                                                   data-parent='#accordion' href='#collapseThree' aria-expanded='false'
                                                   aria-controls='collapseThree'>
                                                    <span class='headingTitle'>
                                                        <i class='icon wizard mobile'></i>
                                                        <span class='caption'>
                                                            <span class='caption_close'>" . pll__('Set Mobile needs') . "</span>
                                                            <span class='caption_open'>".pll__('How many mobile subscriptions you have?')."</span>
                                                        </span>
                                                        <span class='selectedInfo'></span>
                                                        <span class='changeInfo'>". pll__('Change') ."</span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='collapseThree' class='panel-collapse collapse' role='tabpanel'
                                             aria-labelledby='headingThree' data-wizard-panel='mobileSubscription'>
                                            <div class='panel-body text-center'>
                                                <div class='totalPersonWizard'>
                                                    <div class='compPanel withStaticToolTip'>
                                                        <div class='selectionPanel clearfix'>
                                                            <fieldset class='mobile-sel gray fancyComp'>
                                                                <input type='radio' id='subscription6' name='ms_mobile' value='6'
                                                                 " . (("6" == $values['ms_mobile']) ? 'checked="checked"' : '') . " />
                                                                <label class = 'full' for='subscription6' title='6 ". pll__('subscriptions') ."'>
                                                                    <span class='sub-value'>5+</span>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p> " . pll__('I have have more than five subscription') . "</p>
                                                                </div>
                                    
                                    
                                                                <input type='radio' id='subscription5' name='ms_mobile' value='5' 
                                                                " . (("5" == $values['ms_mobile']) ? 'checked="checked"' : '') . " />
                                                                <label class = 'full' for='subscription5' title='5 ". pll__('subscriptions') ."'>
                                                                    <span class='sub-value'>5</span>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p> " . pll__('I have have five subscription') . "</p>
                                                                </div>
                                    
                                                                <input type='radio' id='subscription4' name='ms_mobile' value='4' 
                                                                " . (("4" == $values['ms_mobile']) ? 'checked="checked"' : '') . " />
                                                                <label class = 'full' for='subscription4' title='4 ". pll__('subscriptions') ."'>
                                                                    <span class='sub-value'>4</span>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p> " . pll__('I have have four subscription') . "</p>
                                                                </div>
                                    
                                                                <input type='radio' id='subscription3' name='ms_mobile' value='3' 
                                                                " . (("3" == $values['ms_mobile']) ? 'checked="checked"' : '') . " />
                                                                <label class = 'full' for='subscription3' title='3 ". pll__('subscriptions') ."'>
                                                                    <span class='sub-value'>3</span>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p> " . pll__('I have have three subscription') . "</p>
                                                                </div>
                                    
                                                                <input type='radio' id='subscription2' name='ms_mobile' value='2' 
                                                                " . (("2" == $values['ms_mobile']) ? 'checked="checked"' : '') . " />
                                                                <label class = 'full' for='subscription2' title='2 ". pll__('subscriptions') ."'>
                                                                    <span class='sub-value'>2</span>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p> " . pll__('I have have two subscription') . "</p>
                                                                </div>
                                    
                                                                <input type='radio' id='subscription1' name='ms_mobile' value='1' 
                                                                " . (("1" == $values['ms_mobile']) ? 'checked="checked"' : '') . " />
                                                                <label class = 'full' for='subscription1' title='1 ". pll__('subscription') ."'>
                                                                    <span class='sub-value'>1</span>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p> " . pll__('I have only have one subscription') . "</p>
                                                                </div>
                                    
                                                                <input type='radio' id='no_subscription1' name='ms_mobile' value='-1' class='noSubscription' 
                                                                " . (("-1" == $values['ms_mobile']) ? 'checked="checked"' : '') . " />
                                                                <label class = 'full noSubscription' for='no_subscription1' title='". pll__('no subscription') ."'>
                                                                    <span class='sub-value'>". pll__('none') ."</span>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p> " . pll__('I don\'t have any subscription yet') . "</p>
                                                                </div>
                                    
                                                            </fieldset>
                                                        </div>
                                                    </div>
                                    
                                                    <div class='staticTooltipWrapper'>
                                                        <div class='staticTooltip'>
                                                            <p> " . pll__('Select an option to view information about it') . " </p>
                                                        </div>
                                                    </div>
                                    
                                                    <div class='buttonWrapper'>
                                                        <button type='button' class='btn btn-primary'><i
                                                                class='fa fa-check'></i> " . pll__('Ok') . "
                                                        </button>
                                                    </div>
                                    
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                                             
                                    <!--Value Most -->
                                    <div class='panel panel-default' id='valueMostPanel'>
                                        <div class='panel-heading' role='tab' id='headingValueMost'>
                                            <h4 class='panel-title'>
                                                <a class='collapsed' role='button' data-toggle='collapse'
                                                   data-parent='#accordion' href='#collapseValueMost' aria-expanded='false'
                                                   aria-controls='collapseValueMost'>
                                                    <span class='headingTitle'>
                                                        <i class='icon wizard multidevice'></i>
                                                        <span class='caption'>
                                                            <span class='caption_close'>" . pll__('value most?') . "</span>
                                                            <span class='caption_open'>".pll__('Select one or more criteria that is important to you')."</span>
                                                        </span>
                                                        <span class='selectedInfo'></span>
                                                        <span class='changeInfo'>". pll__('Change') ."</span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='collapseValueMost' class='panel-collapse collapse' role='tabpanel'
                                             aria-labelledby='headingValueMost' data-wizard-panel='valueMost'>
                                            <div class='panel-body'>
                                                <div class='compWrapper withStaticToolTip'>
                                                <ul class='list-unstyled checkBoxComp'>

                                                    <li>
                                                        <input type='checkbox' name='free_install' id='value_most_installation' value='1' 
                                                        " . (("1" == $values['free_install']) ? 'checked="checked"' : '') . ">
                                                        <label for='value_most_installation'>
                                                            " . pll__('Free Installation') . "
                                                            <i class='fa fa-check'></i>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <input type='checkbox' name='free_activation' id='free_activation' value='1'
                                                        " . (("1" == $values['free_activation']) ? 'checked="checked"' : '') . ">                                                        
                                                        <label for='free_activation'>
                                                            " . pll__('Free activation') . "
                                                            <i class='fa fa-check'></i>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <input type='checkbox' name='qos_cs' id='qos_cs' value='70'
                                                        " . (("70" == $values['qos_cs']) ? 'checked="checked"' : '') . ">                                                                                                                
                                                        <label for='qos_cs'>
                                                            " . pll__('Fast Customer Service') . "
                                                            <i class='fa fa-check'></i>
                                                        </label>
                                                    </li>
                                                </ul>
                                                </div>
                                                <div class='buttonWrapper'>
                                                    <button type='button' class='btn btn-primary'><i
                                                            class='fa fa-check'></i> " . pll__('Ok') . "
                                                    </button>
                                                </div>
                                    
                                            </div>
                                        </div>
                                    </div>
                                    
		                            <div class='buttonWrapper'>
		                                <button name='searchSubmit' type='submit' class='btn btn-default'>$submitBtnTxt</button>
		                            </div>
	                            </div>
                            </div>
                            {$hiddenMultipleProvidersHtml}
                            <input type='hidden' name='profile_wizard' value='1' />
                            <input type='hidden' name='filters_applied' value='true' />
                        </form>
                    </div>";

        return $formNew;
    }

    public function cleanInputGet()
    {
        $get = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);//Clean Params
        $get = (empty($get)) ? [] : $get;

        $get = array_merge($_GET, $get);//preserve everything in core $_GET
        //Remove any duplicates in cat as well, while we do
        if(isset($_GET['cat'])) {
            $_GET['cat'] = array_unique($_GET['cat']);
        }

        return $get;
    }

    public function allowedParams($array, $allowed)
    {
        return array_intersect_key($array, array_flip($allowed));
        /*return array_filter(
            $array,
            function ($key) use ($allowed) {
                return in_array($key, $allowed);
            },
            ARRAY_FILTER_USE_KEY
        );*/
    }

    /**
     * verify zip code is valid
     * against valid zip code city will be found
     */
    public function verifyWizardZipCode()
    {

        /** @var \AnbSearch\AnbToolbox $anbToolbox */
        $anbToolbox = wpal_create_instance(AnbToolbox::class);

        $zip = $_POST['zip'];
        $isFound = false;

        $city = $anbToolbox->getCityOnZip($zip);

        if ($city) {
            $isFound = true;
        }

        print $isFound;

        wp_die();
    }

    /**
     * @param $productID
     * @return mixed
     */
    public function getLatestOrderByProduct($productID)
    {

        $orderController = new OrderController(['product_id' => $productID]);

        return $orderController->getLatestOrderByProductResponse();

        wp_die();
    }

    public function getSalesAgentsInternalMode(){
        return json_decode($this->anbApi->getSalesAgent());
    }
}