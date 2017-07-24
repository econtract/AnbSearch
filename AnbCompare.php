<?php
/**
 * Created by PhpStorm.
 * User: imran
 * Date: 5/12/17
 * Time: 6:02 PM
 */

namespace AnbSearch;

use AnbApiClient\Aanbieders;
use AnbTopDeals\AnbProduct;

class AnbCompare
{

    public $crmApiEndpoint = "http://api.econtract.be/";//Better to take it from Admin settings
    public $anbApi;
    public $apiConf = [
        'staging' => ANB_API_STAGING,
        'key' => ANB_API_KEY,
        'secret' => ANB_API_SECRET
    ];
    const RESULTS_PAGE_URI = "/telecom/results/";

    public function __construct()
    {
        $this->anbApi = wpal_create_instance(Aanbieders::class, [$this->apiConf]);

        //enqueue JS scripts
        add_action('init', array($this, 'enqueueScripts'));
        $_GET = $this->cleanInputGet();
    }


    function enqueueScripts()
    {

        wp_enqueue_script('load-more-script', plugins_url('/js/load-more-results.js', __FILE__), array('jquery'));

        // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
        wp_localize_script('load-more-script', 'load_more_object',
            array('ajax_url' => admin_url('admin-ajax.php')));

    }

    function moreResults()
    {

        $queryParams['detaillevel'] = 'supplier,logo,services,price,reviews,texts,promotions,core_features';
        $products = $this->getCompareResults($queryParams);

        $anbTopDeals = wpal_create_instance(AnbProduct::class);

        $productResponse = '';

        $products = json_decode($products);

        $countProducts = 0;

        foreach ($products->results as $listProduct) {

            $countProducts++;

            if ($countProducts <= 4) {
                continue;
            }

            $currentProduct = $listProduct->product;

            list($productData, $priceHtml, $servicesHtml) = $this->extractProductData($anbTopDeals, $currentProduct);

            //Promotions, Installation/Activation HTML
            //display installation and activation price
            $promotionHtml = $anbTopDeals->getPromoInternalSection($productData, true);//True here will drop promotions

            list($advPrice, $monthDurationPromo, $firstYearPrice) = $anbTopDeals->getPriceInfo($productData);

            $productResponse .= '<div class="offer">
                            <div class="row listRow">
                                <div class="col-md-4">
                                    
                                    ' . $anbTopDeals->getProductDetailSection($productData, $servicesHtml) . '
                                </div>
                                <div class="col-md-3">
                                
                                ' . $anbTopDeals->getPromoSection($promotionHtml, $advPrice, 'dealFeatures', '') . '
                                   
                                </div>
                                <div class="col-md-2">
                                   ' . $anbTopDeals->priceSection($priceHtml, $monthDurationPromo, $firstYearPrice, 'dealPrice', '') . '
                                </div>
                                <div class="col-md-3">
                                    <div class="actionButtons">
                                        <div class="comparePackage">
                                            <label>
                                                <input type="checkbox" value="pack1"> ' . pll__('Compare') . '
                                            </label>
                                        </div>
                                        <div class="buttonWrapper">
                                            <a href="#" class="btn btn-primary ">' . pll__('Info and options') . '</a>
                                            <a href="#" class="link block-link">' . pll__('Order Now') . '</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
        }


        print $productResponse;

        wp_die(); // this is required to terminate immediately and return a proper response
    }

    function getCompareResults($atts)
    {
        //print_r($atts);
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
            'detaillevel' => ['null'],
            'sg' => 'consumer',
            'lang' => $this->getCurrentLang(),
            'limit' => ''

        ), $atts, 'anb_search');
        //print_r($atts);

        if (isset($_GET['searchSubmit'])) {
            //$this->cleanArrayData($_GET);
            sort($_GET['cat']);
            if (count($_GET['cat']) >= 2) {
                //if it's pack pass the pack type as well which are below
                //Pack type: 'int_tel', 'tv_tel', 'int_tel_tv', 'gsm_int_tel_tv', 'int_tv', 'gsm_int_tv'
                $packType = "";
                foreach ($_GET['cat'] as $cat) {
                    if (!empty($packType)) {
                        $packType .= "_";
                    }
                    $packType .= substr(strtolower($cat), 0, 3);
                }

                if ($packType == "tel_tv") {
                    $packType = "tv_tel";
                }

                $_GET['cp'] = $packType;

                $_GET['cat'] = 'packs';
            } else {
                if (is_array($_GET['cat'])) {
                    $_GET['cat'] = $_GET['cat'][0];
                }
            }

            $params = $_GET + $atts;//append any missing but default values
            //remove empty params
            $params = array_filter($params);

            if (strtolower($params['cat']) == "internet") {
                $params['s'] = 0;//TODO: This is just temporary solution as for internet products API currently expecting this value to be passed
            }

            if (isset($params['hidden_sp']) && !empty($params['hidden_sp'])) {
                $params['pref_cs'] = $params['hidden_sp'];
                unset($params['hidden_sp']);
            }

            $this->cleanArrayData($params);
            // get the products
            if (isset($_GET['debug'])) {
                echo "Passed Params>>>";
                print_r($params);
            }
            $result = $this->anbApi->compare($params);
            return $result;
        }
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
            $supplierHtml = $this->generateSupplierHtml($values['pref_cs']);
        }

        $needHelpHtml = "";

        if ($values['enable_need_help'] == true) {
            $needHelpHtml .= "<div class='needHelp'>
                                <a href='#'>
                                    <i class='floating-icon fa fa-chevron-right'></i>
                                    <h6>" . pll__('Need help?') . "</h6>
                                    <p>" . pll__('We\'ll guide you') . "</p>
                                </a>
                              </div>";
        }

        $formNew = $this->getSearchBoxContentHtml($values, $needHelpHtml, $supplierHtml);

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
        /*echo "<pre>";
        print_r($suppliers);
        echo "</pre>";*/

        return json_decode($suppliers);
    }

    function getCurrentLang()
    {
        $lang = 'nl';
        if (method_exists('pll_current_language')) {
            $lang = (pll_current_language()) ? pll_current_language() : 'nl';
        }
        return $lang;
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
     * @return string
     */
    private function generateSupplierHtml($selectedSuppliers = [])
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

    private function generateHiddenSupplierHtml($supplierId)
    {
        return "<input type='hidden' name='hidden_sp' value='{$supplierId}' />";
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
    private function convertMultiValToArray($value)
    {
        $this->comaSepToArray($value);
        $this->plainInputToArray($value);
    }

    /**
     * @param $product
     * @return string
     */
    function getServiceDetail($product)
    {
        $servicesHtml = '';
        $product = (array)$product;

        $types = [
            'internet'  => 'internet',
            'mobile'    => 'gsm abo.',
            'idtv'      => 'tv',
            'telephony' => 'tel.'
        ];

        $prdOrPckTypes = ($product['producttype'] == 'packs') ? $product['packtype'] : $product['producttype'];
        $prdOrPckTypes = explode('+',strtolower($prdOrPckTypes));

        // custom sort with specific order :- (internet > mobile > tv > fixed telphony)
        $target = array_values($types);

        usort($prdOrPckTypes, function ($key, $key2) use ($target) {
            $pos_a = array_search(trim($key), $target);
            $pos_b = array_search(trim($key2), $target);
            return $pos_a - $pos_b;
        });

        foreach ($prdOrPckTypes as $key => $packType) {
            //var_dump(trim($packType),$types); //die;
            if (in_array(trim($packType), $types)) {
                $currentType = array_search(trim($packType), $types);
                $features = $product[$currentType]->core_features;

                $featuresHtml = '';
                foreach ($features as $feature) {
                    $featuresHtml .= '<li>' . $feature->label . '</li>';
                }

                $servicesHtml .= '<div class="packageDetail ' . $currentType . '">
                                            <div class="iconWrapper">
                                                <i class="service-icons ' . $currentType . '"></i>
                                            </div>
                                            <h6>' . $product[$currentType]->product_name . '</h6>
                                            <ul class="list-unstyled pkgSummary">
                                               ' . $featuresHtml . '
                                            </ul>
                                        </div>';
            }
        }

        return $servicesHtml;
    }

    /**
     * @param $anbTopDeals
     * @param $currentProduct
     * @return array
     */
    public function extractProductData($anbTopDeals, $currentProduct)
    {
        // prepare data
        $productData = $anbTopDeals->prepareProductData($currentProduct);

        //Price HTML
        $priceHtml = $anbTopDeals->getPriceHtml($productData);

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
     * @return string
     */
    public function getSearchBoxContentHtml($values, $needHelpHtml = "", $supplierHtml = "", $submitBtnTxt = "Search Deals",
                                            $hideTitle = false, $infoMsg = "", $resultsPageUri = self::RESULTS_PAGE_URI)
    {
        $titleHtml = "<h3>" . pll__('Search') . "</h3>";
        if ($hideTitle) {
            $titleHtml = "";
        }

        $hiddenMultipleProvidersHtml = "";

        if (empty($supplierHtml)) {//If no supplier html generated but pref_cs are present keep them included as hidden values
            foreach ($values['pref_cs'] as $provider) {
                $hiddenMultipleProvidersHtml .= "<input type='hidden' name='pref_cs[]' value='" . $provider . "' />";
            }
        }
        $formNew = "<div class='searchBoxContent'>
                    <div class='searchBox'>
                        " . $needHelpHtml . "
                        " . $titleHtml . "
                        <p class='caption'>" . pll__('Select the service you like to compare') . "</p>
                        <div class='formWrapper'>
                            <form action='" . $resultsPageUri . "'>
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
                                    <input class='form-control' id='installation_area' name='zip' placeholder='" . pll__('Enter Zipcode') . "' type='text' 
                                    maxlength='4' pattern='\d{4, 4}' value='" . ((!empty($values['zip'])) ? $values['zip'] : '') . "' required>
                                </div>
                                {$supplierHtml}
                                <div class='form-group'>
                                    <label>" . pll__('Type of Use') . "</label>
                                    <div class='radio fancyRadio'>
                                        <input name='sg' value='consumer' id='private_type' checked='checked' type='radio'
                                        " . (("private" == $values['sg']) ? 'checked="checked"' : '') . ">
                                        <label for='private_type'>
                                            <i class='fa fa-circle-o unchecked'></i>
                                            <i class='fa fa-check-circle checked'></i>
                                            <span>" . pll__('Private') . "</span>
                                        </label>
                                        <input name='sg' value='sme' id='business_type' type='radio'
                                        " . (("sme" == $values['sg']) ? 'checked="checked"' : '') . ">
                                        <label for='business_type'>
                                            <i class='fa fa-circle-o unchecked'></i>
                                            <i class='fa fa-check-circle checked'></i>
                                            <span>" . pll__('Business') . "</span>
                                        </label>
                                    </div>
                                </div>
                                <div class='btnWrapper'>
                                    {$hiddenMultipleProvidersHtml}
                                    <button name='searchSubmit' type='submit' class='btn btn-default btn-block'>" . pll__($submitBtnTxt) . "</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>";
        return $formNew;
    }

    public function cleanInputGet()
    {
        return filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);//Clean Params
    }
}
