<?php
/**
 * Created by PhpStorm.
 * User: imran
 * Date: 5/12/17
 * Time: 6:02 PM
 */

namespace AnbSearch;

use AnbApiClient\Aanbieders;

class AnbCompare {

    public $crmApiEndpoint = "http://api.econtract.be/";//Better to take it from Admin settings
    public $anbApi;
    public $apiConf = [
        'staging' => ANB_API_STAGING,
        'key' => ANB_API_KEY,
        'secret' => ANB_API_SECRET
    ];

    public function __construct()
    {
        $this->anbApi = wpal_create_instance(Aanbieders::class, [$this->apiConf]);
    }

    function getCompareResults( $atts ) {
        //print_r($atts);
        if(!empty($atts['detaillevel'])) {
            $atts['detaillevel'] = explode(',', $atts['detaillevel']);
        }
        //print_r($atts);
        if(!empty($atts['product_ids'])) {
            $atts['product_ids'] = explode(",", $atts['product_ids']);
        }

        $atts = shortcode_atts( array(
            'cat' => 'internet',
            'zip' => '',
            'pref_cs' => '',
            'detaillevel' => ['null'],
            'sg' => 'consumer',
            'lang' => $this->getCurrentLang(),

        ), $atts, 'anb_search' );
        //print_r($atts);

        if(isset($_REQUEST['searchSubmit'])) {
            //$this->cleanArrayData($_REQUEST);
            sort($_REQUEST['cat']);
            if(count($_REQUEST['cat'])  >= 2) {
                //if it's pack pass the pack type as well which are below
                //Pack type: 'int_tel', 'tv_tel', 'int_tel_tv', 'gsm_int_tel_tv', 'int_tv', 'gsm_int_tv'
                $packType = "";
                foreach($_REQUEST['cat'] as $cat) {
                    if(!empty($packType)) {
                        $packType .= "_";
                    }
                    $packType .= substr(strtolower($cat), 0, 3);
                }

                if($packType == "tel_tv") {
                    $packType = "tv_tel";
                }

                $_REQUEST['cp'] = $packType;

                $_REQUEST['cat'] = 'packs';
            } else {
                if(is_array($_REQUEST['cat'])) {
                    $_REQUEST['cat'] = $_REQUEST['cat'][0];
                }
            }

            $params = $_REQUEST + $atts;//append any missing but default values
            //remove empty params
            $params = array_filter($params);

            if(strtolower($params['cat']) == "internet") {
                $params['s'] = 0;//TODO: This is just temporary solution as for internet products API currently expecting this value to be passed
            }

            $this->cleanArrayData($params);
            // get the products
            print_r($params);
            $result = $this->anbApi->compare($params);
            return $result;
        }
    }

    function searchForm( $atts ){
        $atts = shortcode_atts( array(
            'cat' => '',
            'zip' => '',
            'pref_cs' => '',
            'sg' => 'consumer',
            'lang' => $this->getCurrentLang(),

        ), $atts, 'anb_search_form' );

        $values = $atts;

        if(!empty($_REQUEST)) {
            $values = $_REQUEST + $atts;//append any missing but default values
        }

        //convert coma separated values to array
        if((isset($values['cat']) && (boolean)strpos($values['cat'], ",") !== false)) {
            $values['cat'] = explode(",", $values['cat']);
        }
        //store cat in array if it's plain value
        if(!empty($values['cat']) && !is_array($values['cat'])) {
            $values['cat'] = [$values['cat']];
        }

        if($_GET['debug']) {
            echo "<pre>";
            print_r($values);
            echo "</pre>";
        }

        $this->loadFormStyles();
        //$this->loadJqSumoSelect();
        $this->loadBootstrapSelect();
        //for self page esc_url( $_SERVER['REQUEST_URI'] )

        //Generate option HTML for suppliers
        $suppliers = $this->getSuppliers();
        $supplierHtml = "";
        foreach($suppliers as $supplier) {
            $supplierHtml .= "<option value='{$supplier->supplier_id}' selected>{$supplier->name}</option>";
        }

        $formNew = "<div class='searchBoxContent'>
                    <div class='searchBox'>
                        <h3>".pll__('Search')."</h3>
                        <p class='caption'>".pll__('Select the service you like to compare')."</p>
                        <div class='formWrapper'>
                            <form action='/search/'>
                                <div class='form-group'>
                                    <label>".pll__('Services')."</label>
                                    <div class='selectServices'>
                                        <ul class='list-unstyled'>
                                            <li>
                                                <div>
                                                    <input name='cat[]' id='internet_service' checked='checked' onclick='event.preventDefault();' type='checkbox' value='internet'>
                                                    <label for='internet_service'>
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
                                                    <input name='cat[]' id='tv_service' type='checkbox' value='tv' 
                                                    ". ((in_array("tv", $values['cat']) === true) ? 'checked="checked"' : '') .">
                                                    <label for='tv_service'>
                                                        <span class='icon'>
                                                            <i class='sprite sprite-television'></i>
                                                        </span>
                                                        <span class='description'>".pll_('TV')."</span>
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
                                                    ". ((in_array("telephone", $values['cat']) === true) ? 'checked="checked"' : '') .">
                                                    <label for='telephone_service'>
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
                                                    <input name='cat[]' id='mobile_service' type='checkbox' value='gsm'
                                                    ". ((in_array("gsm", $values['cat']) === true) ? 'checked="checked"' : '') .">
                                                    <label for='mobile_service'>
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
                                <div class='form-group'>
                                    <label for='installation_area'>".pll__('Installation area')."</label>
                                    <input class='form-control' id='installation_area' name='zip' placeholder='".pll__('Enter Zipcode')."' type='text' required
                                    value='" . ((!empty($values['zip'])) ? $values['zip'] : '') . "'>
                                </div>
                                <div class='form-group' style='display: none;'>
                                    <label for='provider_preferences'>".pll__('Provider preferences')."</label>
                                    <input class='form-control' id='pref_cs' name='pref_cs' placeholder='".pll__('Select Provider')."' type='text'
                                    value='" . ((!empty($values['pref_cs'])) ? $values['pref_cs'] : '') . "'>
                                </div>
                                <div class='form-group'>
                                    <label for='provider_preferences'>".pll__('Provider preferences')."</label>
                                    <!--<input type='text' class='form-control' id='provider_preferences' placeholder='Select Provider'>-->
                                    <select name='provider_preferences[]' id='provider_preferences' class='form-control 
                                    custom-select' data-live-search='true' title='".pll__('Select Provider')."' data-selected-text-format='count > 3' 
                                    data-size='10'  data-actions-box='true' multiple>
                                        {$supplierHtml}
                                    </select>
                                </div>
                                <div class='form-group'>
                                    <label>".pll__('Type of Use')."</label>
                                    <div class='radio fancyRadio'>
                                        <input name='sg' value='consumer' id='private_type' checked='checked' type='radio'
                                        ". (("private" == $values['sg']) ? 'checked="checked"' : '') .">
                                        <label for='private_type'>
                                            <i class='fa fa-circle-o unchecked'></i>
                                            <i class='fa fa-check-circle checked'></i>
                                            <span>".pll__('Private')."</span>
                                        </label>
                                        <input name='sg' value='business' id='business_type' type='radio'
                                        ". (("business" == $values['sg']) ? 'checked="checked"' : '') .">
                                        <label for='business_type'>
                                            <i class='fa fa-circle-o unchecked'></i>
                                            <i class='fa fa-check-circle checked'></i>
                                            <span>".pll__('Business')."</span>
                                        </label>
                                    </div>
                                </div>
                                <div class='btnWrapper'>
                                    <button name='searchSubmit' type='submit' class='btn btn-default btn-block'>".pll__('Search Deals')."</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>";

        return $formNew;
    }

    function cleanArrayData(&$data) {
        foreach($data as $key => $val) {
            if(!is_array($val) || is_object($val)) {
                $data[$key] = sanitize_text_field($val);
            }
        }
    }

    function loadFormStyles() {
        wp_enqueue_style( 'anbsearch_bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css' );
        wp_enqueue_style( 'anbsearch_font_awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
        wp_enqueue_style( 'anbsearch_normalize',  plugin_dir_url(__FILE__ ) . 'css/normalize.css' );
        wp_enqueue_style( 'anbsearch_default',  plugin_dir_url(__FILE__ ) . 'css/default.css' );
    }

    function loadJqSumoSelect() {
        wp_enqueue_style( 'jq_sumoselect_css', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.sumoselect/3.0.2/sumoselect.min.css' );
        wp_enqueue_script( 'jq_sumoselect_js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.sumoselect/3.0.2/jquery.sumoselect.min.js' );
    }

    function loadBootstrapSelect() {
        wp_enqueue_style( 'jq_bootstrapselect_css', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.2/css/bootstrap-select.min.css' );
        wp_enqueue_script( 'jq_bootstrapselect_js', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.2/js/bootstrap-select.min.js' );
    }

    function getSuppliers($params = []) {
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

    function getCurrentLang() {
        $lang = 'nl';
        if(method_exists('pll_current_language')) {
            $lang = (pll_current_language()) ? pll_current_language() : 'nl';
        }
        return $lang;
    }
}
