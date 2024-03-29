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
        $segments = explode("/", parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

        return isset($segments[$n]) ? $segments[$n] : '';
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
