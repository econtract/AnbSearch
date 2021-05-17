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
use http\Exception\InvalidArgumentException;

if(!function_exists('getUriSegment')) {
    function getUriSegment($n)
    {
        $segment = explode("/", parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

        return count($segment) > 0 && count($segment) >= ($n - 1) ? $segment[$n] : '';
    }
}

class AnbCompare extends Base
{
    const SECTOR_ENERGY  = 'energy';
    const SECTOR_MOBILE  = 'mobile';
    const SECTOR_TELECOM = 'telecom';
    const SECTOR_OTHER   = 'other';

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

    public $defaultNumberOfResults = 4;

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
        $this->orignalCats = isset($_GET['cat']) ? $_GET['cat'] : [];

        parent::__construct();
    }

    /**
     * @param array|string $catsOrType
     * @return string
     */
    public static function getSector($catsOrType = [])
    {
        $catsOrType = (array)$catsOrType;

        if (array_intersect($catsOrType, ['gas', 'electricity', 'dualfuel_pack'])) {
            return self::SECTOR_ENERGY;
        } elseif (array_intersect($catsOrType, ['packs', 'internet', 'telephone', 'tv', 'gsm', 'idtv', 'telephony'])) {
            return self::SECTOR_TELECOM;
        } elseif (array_intersect($catsOrType, ['mobile', 'mobile_internet', 'prepaid'])) {
            return self::SECTOR_MOBILE;
        }
        return self::SECTOR_OTHER;
    }


    /**
     * enqueue ajax scripts
     */
    function enqueueScripts()
    {
        if($this->pagename == pll__('results')) {
            //This is required for ssearchFilterNav functionality on energy too
            wp_enqueue_script('search-compare-script', plugins_url('/js/search-results.js', __FILE__), array('jquery'), '1.2.9', true);
        }
    }

    function compareResults($params, $enableCache, $cacheDurationSeconds = 86400)
    {
        if (defined('COMPARE_API_CACHE_DURATION')) {
            $cacheDurationSeconds = COMPARE_API_CACHE_DURATION;
        }

        if (!empty($params['detaillevel']) && !is_array($params['detaillevel'])) {
            $params['detaillevel'] = explode(',', $params['detaillevel']);
        }

        if (!empty($params['product_ids']) && !is_array($params['product_ids'])) {
            $params['product_ids'] = explode(",", $params['product_ids']);
        }

        $defaults = [
            'cat'                    => 'internet',
            'zip'                    => '',
            'pref_cs'                => '',
            'detaillevel'            => ['supplier', 'logo', 'services', 'price', 'reviews', 'texts', 'promotions', 'core_features'],
            'sg'                     => 'consumer',
            'lang'                   => $this->getCurrentLang(),
            'limit'                  => '',
            's'                      => 1,//Min download speed in Bps 1000 Bps = 1Mbps
            'dl'                     => '',
            'ds'                     => '',
            'sort'                   => '',
            'cp'                     => '',
            'nm'                     => '',
            'ns'                     => '',
            'int'                    => '',
            'fleet'                  => '',
            'pr'                     => '',
            'cm'                     => '',
            'f'                      => '',
            'du'                     => '',
            'nu'                     => '',
            'nou'                    => '',
            'dndu'                   => '',
            'dnnu'                   => '',
            'u'                      => '',
            'ut'                     => '',
            'houseType'              => '',
            'has_solar'              => '',
            'solar_meter_type'       => '',
            'solar_calculation_type' => '',
            'solar_injection_normal' => '',
            'solar_injection_day'    => '',
            'solar_injection_night'  => '',
            'solar_installation'     => '',
            'solar_capacity'         => '',
            'gp'                     => '',
            'gp_full'                => '', // 1 -> products should have 100% green power, 0 -> products should have less than 100% green power
            'l'                      => '',
            'd'                      => '',
            't'                      => '',
            'situation'              => '',
            'ignore_promos'          => '',
            'num_pc'                 => '',
            'num_tv'                 => '',
            'num_smartphones'        => '',
            'num_tablets'            => '',
            'ms_internet'            => '',
            'ms_idtv'                => '',
            'ms_fixed'               => '',
            'ms_mobile'              => '',
            'free_install'           => '',
            'free_activation'        => '',
            'qos_cs'                 => '',
            'pref_pids'              => [],
            'searchSubmit'           => '', // conditional param ( this param doesn't belong to API Params)
            'cmp_pid'                => '',
            'cmp_sid'                => '',
            'excl_sids'              => '',
            'excl_pids'              => '',
            'greenpeace'             => '',
            'meter'                  => '',
            'technology'             => '',
        ];

        $params = shortcode_atts($defaults, $params, 'anb_search');

        $params = array_filter($params, function ($value) {
            return !empty($value) || is_bool($value) || (is_numeric($value) && (int)$value === 0);
        });

        //this will not remove if pref_cs is passed as array but is empty so adding another check to ensure that
        if (isset($params['pref_cs'][0]) && empty($params['pref_cs'][0])) {
            unset($params['pref_cs']);
        }

        if (isset($params['cat']) && in_array($params['cat'], ['dualfuel_pack','electricity', 'gas'])) {
            if (!isset($params['situation'])) {
                $params['situation'] = 3;
            }
        }

        if (isset($params['greenpeace']) && is_numeric($params['greenpeace'])) {
            $params['greenpeace'] = $params['greenpeace'] * 5;
        }

        if (isset($params['cat']) && strtolower($params['cat']) == "internet") {
            $params['s'] = 0;//TODO: This is just temporary solution as for internet products API currently expecting this value to be passed
        }

        if (!empty($params['hidden_sp'])) {
            $params['pref_cs'] = $params['hidden_sp'];
            unset($params['hidden_sp']);
        }

        // Covert DS to s because param S in url is reserve word for Wordpress search
        if (!empty($params['ds'])) {
            $params['s'] = $params['ds'] * 1000; //Min. download speed in Bps: 1000 Bps = 1Mbps
            unset($params['ds']);
        }

        if (isset($params['t']) && is_array($params['t'])) {
            if (in_array('f', $params['t']) && in_array('i', $params['t'])) {
                $tariffType = 'no';
            } else {
                $tariffType = current($params['t']);
            }
            $params['t'] = $tariffType;
        }

        $this->cleanArrayData($params);
        // get the products
        $params = $this->allowedParams($params, array_keys($defaults));//Don't allow all variables to be passed to API
        // Remove supplier ID parameter if none selected
        if (isset($params['cmp_sid']) && $params['cmp_sid'] === 'none') {
            unset($params['cmp_sid']);
        }

        if (isset($params['zip']) && !is_numeric($params['zip'])) {
            $params['zip'] = intval($params['zip']);
        }

        if ($enableCache) {
            $cacheKey = md5(serialize($params)) . ":compare";
            $result   = mycache_get($cacheKey);
            if ($result === false || empty($result)) {
                $result = $this->anbApi->compare($params);
                mycache_set($cacheKey, $result, $cacheDurationSeconds);
            }
        } else {
            $result = $this->anbApi->compare($params);
        }

        return $result;
    }

    function getCompareResults($atts, $enableCache = true, $cacheDurationSeconds = 86400)
    {
        if (isset($atts['cat'])) {
            if (is_array($atts['cat']) && count($atts['cat']) >= 2 && self::getSector($atts['cat']) === self::SECTOR_TELECOM) {
                $atts['cp']        = getPacktypeOnCats($atts['cat']);
                $this->orignalCats = $atts['cat'];
                $atts['cat']       = 'packs';
            } elseif (is_array($atts['cat'])) {
                $atts['cat'] = $atts['cat'][0];
            }
        }

        if (isset($atts['search_via_wizard'])) {
            $atts['cat'] = 'packs';
        }

        return $this->compareResults($atts, $enableCache, $cacheDurationSeconds);
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
        $compareParams = [
            'detaillevel' => 'supplier,logo,services,price,reviews,texts,promotions,core_features,specifications,attachments,availability,contact_info,contract_periods,reviews_texts',
            'lang'        => getLanguage(),
        ];

        $compareParams += $_GET;

        $pageSize    = isset($compareParams['pageSize']) ? $compareParams['pageSize'] : $this->defaultNumberOfResults;
        $page        = isset($compareParams['page']) ? $compareParams['page'] : 2;
        $resultIndex = isset($compareParams['offset']) ? $compareParams['offset'] : ($page - 1) * $pageSize;

        $products = $this->getCompareResults($compareParams);

        $result         = json_decode($products);
        /** @var \AnbTopDeals\AnbProductEnergy $anbTopDeals */
        $anbTopDeals = wpal_create_instance(\AnbTopDeals\AnbProductEnergy::class);
        $anbComp     = $this;

        if ($result->num_results > $resultIndex) {
            if ($result->num_results > ($resultIndex + $pageSize)) {
                $result->results = array_slice($result->results, $resultIndex, $pageSize);
            } else {
                $result->results = array_slice($result->results, $resultIndex);
            }
        } else {
            $result->results = [];
        }

        ob_start();
        include(locate_template('template-parts/section/results/overview.php'));

        echo ob_get_clean();
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

    /**
     * Render search bar
     *
     * @param array $atts
     * @return string The rendered search bar
     */
    function searchBar($atts)
    {
        $dataDefaults = [
            'cat'  => null,
            'zip'  => null,
            'sg'   => 'consumer',
            'lang' => $this->getCurrentLang(),
        ];

        $optionsDefaults = [
            'type'  => 'telecom',
            'title' => null,
        ];

        $data = shortcode_atts($dataDefaults, (!empty($_GET) ? $_GET + $atts : $atts), 'anb_search_bar');

        // Options cannot be overridden by GET params
        $options = shortcode_atts($optionsDefaults, $atts, 'anb_search_bar');

        if (!in_array($options['type'], ['energy', 'telecom', 'mobile'])) {
            throw new InvalidArgumentException(sprintf('Unknown search bar type %s', $data['type']));
        }

        $this->convertMultiValToArray($data['cat']);

        ob_start();
        extract($options);

        include(locate_template('template-parts/widgets/' . $options['type'] . '/search-bar.php'));

        return ob_get_clean();
    }

    function cleanArrayData(&$data)
    {
        foreach ($data as $key => $val) {
            if (!is_array($val) || is_object($val)) {
                $data[$key] = sanitize_text_field($val);
            }
        }
    }

    function getSuppliers($params = array())
    {
        //'cat' => ['internet', 'idtv', 'telephony', 'mobile', 'packs'],
        $atts = array(
            'cat' => ['internet', 'packs'],//products relevant to internet and pack products
            'pref_cs' => '',
            'lang' => $this->getCurrentLang(),
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

    function getSuppliersHiddenInputFields ($values, $supplierHtml="") {
        $hiddenMultipleProvidersHtml = "";

        if (empty($supplierHtml) && is_array($values['pref_cs'])) {//If no supplier html generated but pref_cs are present keep them included as hidden values
            $hiddenMultipleProvidersHtml .= '<div id="wizard_popup_pref_cs" class="hidden">';
            foreach ($values['pref_cs'] as $provider) {
                $hiddenMultipleProvidersHtml .= "<input type='hidden' name='pref_cs[]' value='" . $provider . "' />";
            }
            $hiddenMultipleProvidersHtml .= '</div>';
        }

        return $hiddenMultipleProvidersHtml;
    }

    public function cleanInputGet()
    {
        $get = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);//Clean Params
        $get = (empty($get)) ? [] : $get;

        $get = array_merge($_GET, $get);//preserve everything in core $_GET
        //Remove any duplicates in cat as well, while we do
        if(isset($_GET['cat'])) {
            $cat = $_GET['cat'];
            if (!is_array($cat)) {
                $cat = array($_GET['cat']);
            }
            $_GET['cat'] = array_unique($cat);
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

    public function getProduct($productId, $productType, $language){
        $params = [];

        $params['detaillevel'] = [ 'core_features' ];
        $params['lang'] = $language;
        $params['cat'] = $productType;
        $result = $this->anbApi->getProducts( $params, $productId );
        return json_decode($result);
    }
}
