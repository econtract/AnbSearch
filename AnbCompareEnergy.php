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

class AnbCompareEnergy extends AnbCompare
{
    /**
     * constant form page URI
     */
    const RESULTS_PAGE_URI = "/energy/uitslagen/";

    public function __construct()
    {
        parent::__construct();

	    add_action( 'wp_enqueue_scripts', array($this, 'enqueueScripts') );
    }


    /**
     * enqueue ajax scripts
     */
    function enqueueScripts()
    {
	    wp_enqueue_script('search-results-energy', plugins_url('/js/search-results-energy.js', __FILE__), array('jquery'), '1.0.1', true);
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

	    ), $atts, 'anb_energy_search_form');

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

	    $needHelpHtml = "";

	    if ($values['enable_need_help'] == true) {
		    $needHelpHtml .= "<div class='needHelp'>
                                <a href='javascript:void(0)' data-toggle='modal' data-target='#widgetPopup' data-backdrop='static' data-keyboard='false'>
                                    <i class='floating-icon fa fa-chevron-right'></i>
                                    <h6>" . pll__('get a personalized simulation') . "</h6>
                                    <p>" . pll__('We calculate your potential savings') . "</p>
                                </a>
                              </div>";
	    }

	    $formNew = $this->getSearchBoxContentHtml($values, $needHelpHtml, $supplierHtml, pll__("Compare Energy Prices"), false, "", '/'.pll__('energy').'/'.pll__('results'));

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
    public function getSearchBoxContentHtml(
        $values, $needHelpHtml = "", $supplierHtml = "", $submitBtnTxt = "Compare Energy Prices",
        $hideTitle = false, $infoMsg = "", $resultsPageUri = self::RESULTS_PAGE_URI
    )
    {
	    $titleHtml = "<h3>" . pll__('Compare energy rates') . "</h3>";
	    if ($hideTitle) {
		    $titleHtml = "";
	    }

	    $hiddenMultipleProvidersHtml = $this->getSuppliersHiddenInputFields($values, $supplierHtml);

	    // html for quick search content
	    $formNew = "<div class='quick-search-content'>
                    <div class='searchBox'>
                        " . $needHelpHtml . "
                        " . $titleHtml . "
                        <div class='formWraper'>
                            <form action='" . $resultsPageUri . "' id='quickSearchForm'>
                                <div class='form-group'>
                                	<label>" . pll__('I like to compare') . "</label>
                                </div>
                                <div class='form-group'>
                                    <ul class='service-tabs'>
                                        <li>
                                            <input type='radio' name='cat' id='service_dual_fuel' value='dualfuel_pack' checked='checked'>
                                            <label for='service_dual_fuel' class='service-dual-fuel'>
                                                <i></i>
                                                <span class='service-label'>".pll__('Dual Fuel')."</span>
                                                <span class='check-box'></span>
                                            </label>
                                        </li>
                                        <li>
                                            <input type='radio' name='cat' id='service_electricity' value='electricity'>
                                            <label for='service_electricity' class='service-electricity'>
                                                <i></i>
                                                <span class='service-label'>".pll__('Electricity')."</span>
                                                <span class='check-box'></span>
                                            </label>
                                        </li>
                                        <li>
                                            <input type='radio' name='cat' id='service_gas' value='gas'>
                                            <label for='service_gas' class='service-gas'>
                                                <i></i>
                                                <span class='service-label'>".pll__('Gas')."</span>
                                                <span class='check-box'></span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                                {$infoMsg}
                                <div class='form-group'>
                                    <label for='installation_area'>" . pll__('Installation area') . "</label>
                                    <input type='text' class='form-control typeahead' id='installation_area' name='zip' 
                                      value='" . ((!empty($values['zip'])) ? $values['zip'] : '') . "' placeholder='" . pll__('Enter Zipcode') . "'
                                      data-error='" . pll__('Please enter valid zip code') . "' autocomplete='off' query_method='cities' query_key='postcode' required>
                                </div>
                                <div class='form-group'>
                                    <div class='check fancyCheck'>
                                        <input type='hidden' name='sg' value='consumer'>
                                        <input type='checkbox' name='sg' id='showBusinessDeal' class='radio-salutation' value='sme'>
                                        <label for='showBusinessDeal'>
                                            <i class='fa fa-circle-o unchecked'></i>
                                            <i class='fa fa-check-circle checked'></i>
                                            <span>".pll__('Show business deals')."</span>
                                        </label>
                                    </div>
                                </div>
                                <div class='form-group family-members-container'>
                                    <label>".pll__('How many family members?')."</label>
                                    <div class='family-members'>
                                        <fieldset class='person-sel-sm'>

                                            <input type='radio' id='person6' name='f' value='6' usage-val='".KWH_5_PLUS_PERSON."' usage-val-night='".KWH_5_PLUS_PERSON_NIGHT."'>
                                            <label class='full' for='person6' title='6 ".pll__('persons')."'>
                                                <span class='person-value'>5+</span>
                                            </label>

                                            <input type='radio' id='person5' name='f' value='5' usage-val='".KWH_5_PERSON."' usage-val-night='".KWH_5_PERSON_NIGHT."'>
                                            <label class='full' for='person5' title='5 ".pll__('persons')."'></label>

                                            <input type='radio' id='person4' name='f' value='4' usage-val='".KWH_4_PERSON."' usage-val-night='".KWH_4_PERSON_NIGHT."'>
                                            <label class='full' for='person4' title='4 ".pll__('persons')."'></label>


                                            <input type='radio' id='person3' name='f' value='3' usage-val='".KWH_3_PERSON."' usage-val-night='".KWH_3_PERSON_NIGHT."'>
                                            <label class='full' for='person3' title='3 ".pll__('persons')."'></label>


                                            <input type='radio' id='person2' name='f' value='2' usage-val='".KWH_2_PERSON."' usage-val-night='".KWH_2_PERSON_NIGHT."'>
                                            <label class='full' for='person2' title='2 ".pll__('persons')."'></label>


                                            <input type='radio' id='person1' name='f' value='1' usage-val='".KWH_1_PERSON."' usage-val-night='".KWH_1_PERSON_NIGHT."'>
                                            <label class='full' for='person1' title='1 ".pll__('persons')."'></label>
                                            <div class='clearfix'></div>
                                        </fieldset>
                                        <div class='double-meter-fields'>
                                            <div class='field general-energy kwh-energy'>
                                                <i></i>
                                                <input type='text' name='du'/>
                                                <label>kwh</label>
												<span class='question-circle custom-tooltip' data-toggle='tooltip' title='".pll__('Informational text for electricty')."'></span>
                                            </div>
                                            <div class='field day-night-energy kwh-energy hide'>
                                                <div class='day-energy'>
                                                    <i></i>
                                                    <input type='text' disabled='disabled' name='du'/>
                                                    <label>kwh</label>
                                                    <span class='question-circle custom-tooltip' data-toggle='tooltip' title='".pll__('Informational text for electricty')."'></span>
                                                </div>
                                                <div class='night-energy'>
                                                    <i></i>
                                                    <input type='text' disabled='disabled' name='nou'/>
                                                    <label>kwh</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class='form-group'>
                                        <div class='check fancyCheck'>
                                            <input type='checkbox' name='doubleMeter' id='doubleMeter' class='radio-salutation' value='1'>
                                            <label for='doubleMeter'>
                                                <i class='fa fa-circle-o unchecked'></i>
                                                <i class='fa fa-check-circle checked'></i>
                                                <span>".pll__('Double meter')."</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class='form-group house-type-container'>
                                    <label>".pll__('What type of house?')."</label>
                                    <div class='house-selector'>
                                        <div class='house-type'>
                                            <label class='single-house' data-toggle='tooltip' title='House 1'>
                                                <input type='radio' name='houseType' id='single_house' value='single' usage-val='".M3_SINGLE_HOUSE."'/>
                                                <i class='houses'></i>
                                            </label>

                                            <label class='double-house' data-toggle='tooltip' title='House 2'>
                                                <input type='radio' name='houseType' id='double_house' value='double' usage-val='".M3_DOUBLE_HOUSE."'/>
                                                <i class='houses'></i>
                                            </label>

                                            <label class='triple-house' data-toggle='tooltip' title='House 3'>
                                                <input type='radio' name='houseType' id='triple_house' value='tripple' usage-val='".M3_TRIPLE_HOUSE."'/>
                                                <i class='houses'></i>
                                            </label>

                                            <label class='tetra-house' data-toggle='tooltip' title='House 4'>
                                                <input type='radio' name='houseType' id='tetra_house' value='tetra' usage-val='".M3_TETRA_HOUSE."'/>
                                                <i class='houses'></i>
                                            </label>

                                            <label class='tower-house' data-toggle='tooltip' title='".pll__('appartment')."'>
                                                <input type='radio' name='houseType' id='tower_house' value='flat' usage-val='".M3_FLAT."'/>
                                                <i class='houses'></i>
                                            </label>
                                        </div>
                                        <div class='field'>
                                            <i></i>
                                            <input type='text' id='m3_u' name='u' value=''/>
                                            <input type='hidden' name='ut' value='m3'/>
                                            <label>m3</label>
                                            <span class='question-circle custom-tooltip' data-toggle='tooltip' title='".pll__('Informational text for gas')."'></span>
                                        </div>
                                    </div>
                                </div>
                                <div class='form-group solar-panel-container'>
	                                <label>Do you have solar panels?</label>
	                                <div class='check fancyCheck'>
	                                    <input type='checkbox' name='has_solar' id='solarPanel' class='radio-salutation' value='1'>
	                                    <label for='solarPanel'>
	                                        <i class='fa fa-circle-o unchecked'></i>
	                                        <i class='fa fa-check-circle checked'></i>
	                                        <span>".pll__('Yes, I have solar panels')."</span>
	                                    </label>
	                                </div>
	                            </div>
                                <div class='btnWrapper text-center'>
                                    <button name='searchSubmit' type='submit' class='btn btn-default btn-block' >$submitBtnTxt</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>";

	    return $formNew;
    }

    function moreResults(){
        $productResp = '';
        $forceCheckAvailability = false;
        $parentSegment = getSectorOnCats( $_SESSION['product']['cat'] );
        $products = $this->getCompareResults([
            'detaillevel' => 'supplier,logo,services,price,reviews,texts,promotions,core_features,specifications'
        ]);

        $results = json_decode($products);
        /** @var \AnbTopDeals\AnbProduct $anbTopDeals */
        $anbTopDeals = wpal_create_instance( \AnbTopDeals\AnbProductEnergy::class );
        $countProducts = 0;
        $chkbox = 100;
        foreach ($results->results as $listProduct) :
            $countProducts++;
            $chkbox++;
            if ($countProducts <= $this->defaultNumberOfResults) {
                continue;
            }
            $endScriptTime = getEndTime();
            displayCallTime($startScriptTime, $endScriptTime, "Total page load time for Results page invidual gridView start.");

            $countProducts ++;

            $product     = $listProduct->product;
            $pricing     = $listProduct->pricing;
            $productData = $anbTopDeals->prepareProductData( $product );
            $productId   = $product->product_id;

            $endScriptTime = getEndTime();
            displayCallTime($startScriptTime, $endScriptTime, "Total page load time for Results page invidual gridView till prepareProductData.");

            list(, , , , $toCartLinkHtml) = $anbTopDeals->getToCartAnchorHtml($parentSegment, $productData['product_id'], $productData['supplier_id'], $productData['sg'], $productData['producttype'], $forceCheckAvailability);

            $blockLinkClass = 'block-link';
            if($forceCheckAvailability) {
                $blockLinkClass = 'block-link missing-zip';
            }
            $toCartLinkHtml = '<a '.$toCartLinkHtml.' class="link '.$blockLinkClass.'">' . pll__( 'Order Now' ) . '</a>';

            if($productData['commission'] === false) {
                $toCartLinkHtml = '<a href="#not-available" class="link block-link not-available">' . pll__('Not Available') . '</a>';
            }

            $servicesHtml = $anbTopDeals->getServicesHtml( $product, $pricing );

            /*
            //Services HTML
            $servicesHtml = $anbTopDeals->getServicesHtml( $productData );

            $endScriptTime = getEndTime();
            displayCallTime($startScriptTime, $endScriptTime, "Total page load time for Results page invidual gridView till getServicesHtml.");

            //Price HTML
            $priceHtml = $anbTopDeals->getPriceHtml( $productData, true );

            $endScriptTime = getEndTime();
            displayCallTime($startScriptTime, $endScriptTime, "Total page load time for Results page invidual gridView till getPriceHtml.");*/
            include(locate_template('template-parts/section/energy-overview-popup.php'));
            $productResp .= '<div class="result-box-container" id="listgridview_'.$chkbox.'">';
            $productResp .= '<div class="result-box">';
            $productResp .= '<div class="top-label">'. $anbTopDeals->getBadgeSection( $productData ) .'</div>';
            $productResp .= '<div class="flex-grid">';
            $productResp .= '<div class="cols">';
            $productResp .= $anbTopDeals->getProductDetailSection( $productData, '', false, '', true  );
            $productResp .= $anbTopDeals->getGreenPeaceRating( $product );
            $productResp .= '</div>';
            $productResp .= '<div class="cols">';
            $productResp .= '<ul class="green-services">' .$servicesHtml. '</ul>';
            $productResp .= '</div>';
            $productResp .= '<div class="cols grid-hide">' .$anbTopDeals->getPromoSection( $product ). '</div>';
            $productResp .= '<div class="cols">';
            $productResp .= '<div class="actual-price-board">' .$anbTopDeals->getPriceHtml( $productData, $pricing, true ). '</div>';
            $productResp .= '</div>';
            $productResp .= '<div class="cols grid-show">' .$anbTopDeals->getPromoSection( $product ). '</div>';
            $productResp .= '<div class="cols">';
            $productResp .= '<div class="price-label ">';
            $productResp .= '<label>Potential saving</label>';
            $productResp .= '<div class="price">€ 136<small>,00</small></div>';
            $productResp .= '</div>';

            $yearAdv = $pricing->yearly->price - $pricing->yearly->promo_price;
            if($yearAdv !== 0):
                $yearAdvArr = formatPriceInParts($yearAdv, 2);
                $monthlyAdv = $pricing->monthly->price - $pricing->monthly->promo_price;
                $monthAdvArr = formatPriceInParts($monthlyAdv, 2);

                $productResp .= '<div class="price-label">';
                $productResp .= '<label>' .pll__('Your advantage'). '</label>';
                $productResp .= '<div class="price yearly">';
                $productResp .= $yearAdvArr['currency'] . ' ' . $yearAdvArr['price'];
                $productResp .= '<small>,' .$yearAdvArr['cents']. '</small>';
                $productResp .= '</div>';
                $productResp .= '<div class="price monthly hide">';
                $productResp .= $monthAdvArr['currency'] . ' ' . $monthAdvArr['price'];
                $productResp .= '<small>,' .$monthAdvArr['cents']. '</small>';
                $productResp .= '</div>';
                $productResp .= '</div>';
            endif;
            $productResp .= '<div class="inner-col grid-show">';
            /*$productResp .= '<div class="promo">added services</div>';
            $productResp .= '<ul class="col_9">';
            $productResp .= '<li>Isolation</li>';
            $productResp .= '<li>SOlar panels</li>';
            $productResp .= '<li>Comfort Service bij storing/defect</li>';
            $productResp .= '<li>Bijstand elektrische wagen</li>';
            $productResp .= '<li>Verlengde ganantie</li>';
            $productResp .= '</ul>';*/
            $productResp .= '</div>';
            $productResp .= '<div class="grid-show border-top col_10">' .decorateLatestOrderByProduct($product->product_id) . '</div>';
            $productResp .= '<a href="#" class="btn btn-primary all-caps">connect now</a>';

			$detailHtml = '<a href="'.getEnergyProductPageUri($productData).'" 
			                                                     class="link block-link all-caps">'.pll__('Detail').'</a>';
			if($productData['commission'] === false) {
				$detailHtml = '<a href="#not-available" class="link block-link not-available">' . pll__('Not Available') . '</a>';
			}

            $productResp .= $detailHtml;
            $productResp .= '</div>';
            $productResp .= '</div>';
            $productResp .= '<div class="result-footer">';
            $productResp .= '<div class="pull-left grid-hide">'.decorateLatestOrderByProduct($product->product_id) . '</div>';
            $productResp .= '<div class="pull-right">';
            $productResp .= '<span class="grid-hide">'.$anbTopDeals->getLastUpdateDate( $productData ).'</span>';
            $productResp .= '<div class="comparePackage">';
            $productResp .= '<div class="checkbox">';
            $productResp .= '<label>';
            $productResp .= '<input type="hidden" name="compareProductType152" value="internet">';
            $productResp .= '<input type="checkbox" value="listgridview_'.$chkbox.'"> Compare';
            $productResp .= '</label>';
            $productResp .= '</div>';
            $productResp .= '</div>';
            $productResp .= '</div>';
            $productResp .= '</div>';
            $productResp .= '</div>';
            $productResp .= '</div>';
            $endScriptTime = getEndTime();
            displayCallTime($startScriptTime, $endScriptTime, "Total page load time for Results page for individual product listView end.");
            //unset($productData);//these variables are used in portion right below on calling getProductDetailSection
            unset($product);
            //unset($servicesHtml);
        endforeach;
        echo $productResp;
        wp_die(); // this is required to terminate immediately and return a proper response
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

		//$hiddenMultipleProvidersHtml = $this->getSuppliersHiddenInputFields($values, $supplierHtml);
        $hiddenMultipleProvidersHtml = '';

		$formNew = "<div class='formWrapper'>
                        <form action='" . $resultsPageUri . "' id='yourProfileWizardForm' data-toggle='validator' role='form'>
                        	<div class='container-fluid'>
	                            <div class='panel-group formWrapper-energy' id='accordion' role='tablist' aria-multiselectable='true'>
	                            
	                                <!--Compare-->	                            	
	                            	<div class='panel panel-default' id='comparePanel'>
                                        <div class='panel-heading active' role='tab' id='CompareHeading'>
                                            <h4 class='panel-title'>
                                                <a role='button' data-toggle='collapse' data-parent='#accordion' href='#compareCompPanel' aria-expanded='true' aria-controls='collapseOne'>
                                                    <span class='headingTitle hasSelectedValue'>
                                                        <span class='icon-holder'><i class='energy-icons electricity-gas'></i></span>
                                                        <span class='caption'>
                                                            <span class='caption_close'>" . pll__('compare') . "</span>
                                                            <span class='caption_open'>" . pll__('compare') . "</span>
                                                        </span>
                                                        <span class='selectedInfo'></span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='compareCompPanel' class='panel-collapse collapse in' role='tabpanel' aria-labelledby='headingOne'  data-wizard-panel='compare'>
                                            <div class='panel-body text-center'>
                                                <div class='form-group'>
                                                    <div class='selectServicesComp'>
                                                    <label class='block bold-600 text-left'>".pll__('I like to compare')."</label>
                                                        <ul class='service-tabs'>
                                                            <li>
                                                                <input name='cat' id='dualfuel_pack_service_wiz' checked='checked' type='radio' value='dualfuel_pack' " . (($values['cat'] == 'dualfuel_pack') ? 'checked="checked"' : '') . ">
                                                                <label for='dualfuel_pack_service_wiz' class='service-dual-fuel'>
                                                                    <i></i>
                                                                    <span class='service-label'>".pll__('Dualfuel Pack')."</span>
                                                                    <span class='check-box'></span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <input name='cat' id='electricity_service_wiz' type='radio' value='electricity' " . (($values['cat'] == 'electricity') ? 'checked="checked"' : '') . ">
                                                                <label for='electricity_service_wiz' class='service-electricity'>
                                                                <i></i>
                                                                <span class='service-label'>".pll__('Electricity')."</span>
                                                                <span class='check-box'></span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <input name='cat' id='gas_service_wiz' type='radio' value='gas' " . (($values['cat'] == 'gas') ? 'checked="checked"' : '') . ">
                                                                <label for='gas_service_wiz' class='service-gas'>
                                                                    <i></i>
                                                                    <span class='service-label'>".pll__('Gas')."</span>
                                                                    <span class='check-box'></span>
                                                                </label>
                                                            </li>
                                                        </ul>
                                                        <div class='block-desc'>" . pll__('Combining service often helps you save every year.') . "</div>
                                                        
                                                        <div class='buttonWrapper text-left'>
                                                            <button type='button' class='btn btn-primary'>" . pll__('Ok') . "</button>
                                                        </div>
                                                    </div>
                                                </div>
                                               
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!--Location-->	                            	
	                            	<div class='panel panel-default' id='locationPanel'>
                                        <div class='panel-heading' role='tab' id='installationHeading'>
                                            <h4 class='panel-title'>
                                                <a role='button' data-toggle='collapse' data-parent='#accordion' href='#installationPanel' aria-expanded='true' aria-controls='collapseOne'>
                                                    <span class='headingTitle'>
                                                        <span class='icon-holder'><i class='icon wizard location'></i></span>
                                                        <span class='caption'>
                                                            <span class='caption_close'>" . pll__('Set location') . "</span>
                                                            <span class='caption_open'>" . pll__('Installation area') . "</span>
                                                        </span>
                                                        <span class='selectedInfo'></span>
                                                        <span class='changeInfo'>". pll__('Change') ."</span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='installationPanel' class='panel-collapse collapse' role='tabpanel' aria-labelledby='headingOne'  data-wizard-panel='location'>
                                            <div class='panel-body text-center'>
                                                <div class='singleFormWrapper'>
                                                    <div class='row'>
                                                        <div class='col-md-3 p-r-0 col-sm-3 col-xs-12'><label for='installation_area' class='control-label p-l-0'>" . pll__('Installation area') . "</label></div>
                                                        <div class='col-md-7 col-sm7 col-xs-12'>
                                                            <div class='form-group has-feedback p-l-0'>
                                                                <div class=''>
                                                                    <input class='form-control typeahead' id='installation_area' name='zip'
                                                                           placeholder='" . pll__('Enter Zipcode') . "' maxlength='4'
                                                                           value='" . ((!empty($values['zip'])) ? $values['zip'] : '') . "' required type='text'>
                                                                    <span class='staricicon form-control-feedback '
                                                                          aria-hidden='true'></span>
                                                                    <div class='help-block with-errors'></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='block-desc'>" . pll__('Not all packs are available on every location. Thats why we need your zipcode.') . "</div>
                                                        
                                                    <div class='buttonWrapper text-left'>
                                                        <button type='button' class='btn btn-primary'>" . pll__('Ok') . "</button>
                                                    </div>
                                                    
                                                    
                                                    <!--<div class='staticTooltipWrapper'>
                                                            <div class='staticTooltip'>
                                                                <p>". pll__('some tooltip here if required'). " </p>
                                                            </div>
                                                        </div>-->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                   <!--Use-->
                                   <!--
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
                                    -->
                                    
                                    <!--Family Members-->
                                    <div class='panel panel-default' id='familyPanel'>
                                        <div class='panel-heading' role='tab' id='headingOne'>
                                            <h4 class='panel-title'>
                                                <a role='button' data-toggle='collapse' data-parent='#accordion' href='#collapseOne' aria-expanded='true' aria-controls='collapseOne'>
                                                    <span class='headingTitle'>
                                                        <span class='icon-holder'><i class='icon wizard user'></i></span>
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
                                        <div id='collapseOne' class='panel-collapse collapse' role='tabpanel' aria-labelledby='headingOne'  data-wizard-panel='familyMembers'>
                                            <div class='panel-body text-center'>
                                    
                                                <div class='totalPersonWizard'>
                                                    <div class='compPanel withStaticToolTip'>
                                                        <div class='selectionPanel clearfix energy-family'>
                                                            <fieldset class='person-sel gray fancyComp'>
                                                                <input type='radio' id='person6' name='f' value='6' 
                                                                " . (("6" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person6' title='6 ". pll__('persons') ."'>
                                                                    <span class='person-value'>5+</span>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>6 ". pll__('person is betterInfo about extensive use of internet') ." </p>
                                                                </div>
                                    
                                                                <input type='radio' id='person5' name='f' value='5' 
                                                                " . (("5" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person5' title='5 ". pll__('persons') ."'>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>". pll__('5th Person. Info about extensive use of internet.') .". </p>
                                                                </div>
                                    
                                                                <input type='radio' id='person4' name='f' value='4' 
                                                                " . (("4" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person4' title='4 ". pll__('persons') ."'>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>". pll__('4thInfo about extensive use of internet') ." </p>
                                                                </div>
                                    
                                                                <input type='radio' id='person3' name='f' value='3' 
                                                                " . (("3" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person3' title='3 ". pll__('persons') ."'>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>". pll__('3rd Info about extensive use of internet') ."</p>
                                                                </div>
                                    
                                                                <input type='radio' id='person2' name='f' value='2' 
                                                                " . (("2" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person2' title='2 ". pll__('persons') ."'>

                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>". pll__('Two person info about extensive use of internet') ."</p>
                                                                </div>
                                    
                                                                <input type='radio' id='person1' name='f' value='1' 
                                                                " . (("1" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person1' title='1 ". pll__('person') ."'>

                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>". pll__('only one person. Info about extensive use of internet') ."</p>
                                                                </div>
                                    
                                                            </fieldset>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class='block-desc'>".pll__('Select an option to view information about it')."</div>
                                                    
                                                    <div class='buttonWrapper text-left'>
                                                        <button type='button' class='btn btn-primary'>".pll__('Ok')."</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Double meter -->
                                    <div class='panel panel-default' id='doubleMeterPanel'>
                                        <div class='panel-heading' role='tab' id='headingTwo'>
                                            <h4 class='panel-title'>
                                                <a class='collapsed' role='button' data-toggle='collapse' data-parent='#accordion' href='#collapseTwo' aria-expanded='false' aria-controls='collapseTwo'>
                                                    <span class='headingTitle'>
                                                        <span class='icon-holder'><i class='energy-icons double-meter'></i></span>
                                                        <span class='caption'>
                                                            <span class='caption_close'>" . pll__('Set Double meter') . "</span>
                                                            <span class='caption_open'>"  . pll__('Double meter ') . "</span>
                                                        </span>
                                                        <span class='selectedInfo'></span>
                                                        <span class='changeInfo'>". pll__('Change') ."</span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='collapseTwo' class='panel-collapse collapse' role='tabpanel' aria-labelledby='headingTwo'  data-wizard-panel='doubleMeter'>
                                            <div class='panel-body text-center'>
                                                <div class='counterPanel'>
                                                    <div class='form-group'><label class='block bold-600 text-left'>" . pll__('What kind of meter do you have?') . "</label></div>
                                                    <div class='form-group'>
                                                        <div class='fancy-radio inline'>
                                                            <label>
                                                                <input type='radio' value='Single' name='doubleMeter' checked />
                                                                <span></span>
                                                                <img src='".get_template_directory_uri()."/images/common/single-meter.svg' alt='' height='52' />
                                                            </label>
                                                            
                                                            <label>
                                                                <input type='radio' value='Double' name='doubleMeter' />
                                                                <span></span>
                                                                <img src='".get_template_directory_uri()."/images/common/double-meter.svg' alt='' height='52' />
                                                            </label>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class='form-group text-left'>
                                                        <div class='check fancyCheck'>
                                                            <input type='checkbox' name='doubleMeter' id='doubleMeter' class='radio-salutation' value='1'>
                                                            <label for='doubleMeter'>
                                                                <i class='fa fa-circle-o unchecked'></i>
                                                                <i class='fa fa-check-circle checked'></i>
                                                                <span>" . pll__('I also have an Exclusive night meter') . "</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class='block-desc'>".pll__('Some usefull information about these options?')."</div>
                                                    
                                                    <div class='buttonWrapper text-left'>
                                                        <button type='button' class='btn btn-primary'>".pll__('Ok')."</button>
                                                    </div>
                                                    <!--div class='info'>
                                                        <p>Some information will come here as well just like any other
                                                            information so current putting lorem ipsum to see how it
                                                            looks</p>
                                                    </div
                                                    <div class='buttonWrapper'>
                                                        <button type='button' class='btn btn-primary'><i
                                                                    class='fa fa-check'></i> " . pll__('Ok') . "
                                                        </button>
                                                    </div>-->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Electricity -->
                                    <div class='panel panel-default' id='electricityPanel'>
                                        <div class='panel-heading' role='tab' id='headingThree'>
                                            <h4 class='panel-title'>
                                                <a class='collapsed' role='button' data-toggle='collapse' data-parent='#accordion' href='#collapseThree' aria-expanded='false' aria-controls='collapseThree'>
                                                    <span class='headingTitle'>
                                                        <span class='icon-holder'><i class='energy-icons plug'></i></span>
                                                        <span class='caption'>
                                                            <span class='caption_close'>" . pll__('Day') . "</span>
                                                            <span class='caption_open'>1234 kWh/2156 kWh</span>
                                                        </span>
                                                        <span class='selectedInfo'></span>
                                                        <span class='changeInfo'>". pll__('Change') ."</span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='collapseThree' class='panel-collapse collapse' role='tabpanel' aria-labelledby='headingThree'  data-wizard-panel='electricityEnergy'>
                                            <div class='panel-body text-center'>
                                                <div class='counterPanel'>
                                                    <div class='form-group text-left'>
                                                        <div class='check fancyCheck'>
                                                            <input type='checkbox' name='doubleMeter' id='electricityConsumption' class='radio-salutation' value='1'>
                                                            <label for='electricityConsumption'>
                                                                <i class='fa fa-circle-o unchecked'></i>
                                                                <i class='fa fa-check-circle checked'></i>
                                                                <span>" . pll__('I also have an Exclusive night meter') . "</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class='row'>
                                                        <div class='col-md-5 col-sm-5 col-xs-12 form-group'>
                                                            <label class='block bold-600 text-left'>" . pll__('Day consumption') . "</label>
                                                            <div class='day-consumption'>
                                                                <input type='text'  name='dayConsumption' />
                                                            </div>
                                                        </div>
                                                        <div class='col-md-5 col-sm-5 col-xs-12 form-group'>
                                                            <label class='block bold-600 text-left'>" . pll__('Night consumption') . "</label>
                                                            <div class='night-consumption'>
                                                                <input type='text' name='nightConsumption' />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    
                                                    
                                                    
                                                    <div class='block-desc'>".pll__('Where can I find this information?')."</div>
                                                    
                                                    <div class='buttonWrapper text-left'>
                                                        <button type='button' class='btn btn-primary'>".pll__('Ok')."</button>
                                                    </div>
                                                    <!--div class='info'>
                                                        <p>Some information will come here as well just like any other
                                                            information so current putting lorem ipsum to see how it
                                                            looks</p>
                                                    </div
                                                    <div class='buttonWrapper'>
                                                        <button type='button' class='btn btn-primary'><i
                                                                    class='fa fa-check'></i> " . pll__('Ok') . "
                                                        </button>
                                                    </div>-->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- House -->
                                    <div class='panel panel-default' id='housePanel'>
                                        <div class='panel-heading' role='tab' id='headingFour'>
                                            <h4 class='panel-title'>
                                                <a class='collapsed' role='button' data-toggle='collapse' data-parent='#accordion' href='#collapseFour' aria-expanded='false' aria-controls='collapseFour'>
                                                    <span class='headingTitle'>
                                                        <span class='icon-holder'><i class='energy-icons home'></i></span>
                                                        <span class='caption'>
                                                            <span class='caption_close'>". pll__('House') ."</span>
                                                            <span class='caption_open'>". pll__('House') ."</span>
                                                        </span>
                                                        <span class='selectedInfo'></span>
                                                        <span class='changeInfo'>". pll__('Change') ."</span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='collapseFour' class='panel-collapse collapse' role='tabpanel' aria-labelledby='headingFour'  data-wizard-panel='house'>
                                            <div class='panel-body text-center'>
                                                <div class='counterPanel'>
                                                    <div class='form-group text-left'>
                                                        <label class='block bold-600 text-left'>" . pll__('What type of house?') . "</label>
                                                    </div>
                                                    <div class='house-selector'>
                                                        <div class='house-type'>
                                                            <label class='single-house' data-toggle='tooltip' title='' data-original-title='House 1'>
                                                                <input type='radio' name='houseType' id='single_house' value='single' usage-val='200'>
                                                                <i class='houses'></i>
                                                            </label>
                                                    
                                                            <label class='double-house' data-toggle='tooltip' title='' data-original-title='House 2'>
                                                                <input type='radio' name='houseType' id='double_house' value='double' usage-val='300'>
                                                                <i class='houses'></i>
                                                            </label>
                                                    
                                                            <label class='triple-house' data-toggle='tooltip' title='' data-original-title='House 3'>
                                                                <input type='radio' name='houseType' id='triple_house' value='tripple' usage-val='400'>
                                                                <i class='houses'></i>
                                                            </label>
                                                    
                                                            <label class='tetra-house' data-toggle='tooltip' title='' data-original-title='House 4'>
                                                                <input type='radio' name='houseType' id='tetra_house' value='tetra' usage-val='500'>
                                                                <i class='houses'></i>
                                                            </label>
                                                    
                                                            <label class='tower-house' data-toggle='tooltip' title='' data-original-title='appartment'>
                                                                <input type='radio' name='houseType' id='tower_house' value='flat' usage-val='100'>
                                                                <i class='houses'></i>
                                                            </label>
                                                        </div>
                                                        <div class='field m-l-10'>
                                                            <i></i>
                                                            <input type='text' id='m3_u' name='u' value=''>
                                                            <input type='hidden' name='ut' value='m3'>
                                                            <label>m3</label>
                                                            <span class='question-circle custom-tooltip' data-toggle='tooltip' title='' data-original-title='Informational text for gas'></span>
                                                        </div>
                                                    </div>
                                                    
                                                    <p class='color-red underline text-left'>".pll__('Tell us more for a accurate estimation')."</p>
                                                    
                                                    
                                                    <div class='block-desc'>".pll__('This is the average consumption of family of 4 with this house characteristics is 4500 kWh and 1700 m3 gas a year.')."</div>
                                                    
                                                    <div class='buttonWrapper text-left'>
                                                        <button type='button' class='btn btn-primary'>".pll__('Ok')."</button>
                                                    </div>
                                                    <!--div class='info'>
                                                        <p>Some information will come here as well just like any other
                                                            information so current putting lorem ipsum to see how it
                                                            looks</p>
                                                    </div
                                                    <div class='buttonWrapper'>
                                                        <button type='button' class='btn btn-primary'><i
                                                                    class='fa fa-check'></i> " . pll__('Ok') . "
                                                        </button>
                                                    </div>-->
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Gas -->
                                    <div class='panel panel-default' id='electricityPanel'>
                                        <div class='panel-heading' role='tab' id='headingFive'>
                                            <h4 class='panel-title'>
                                                <a class='collapsed' role='button' data-toggle='collapse' data-parent='#accordion' href='#collapseFive' aria-expanded='false' aria-controls='collapseFive'>
                                                    <span class='headingTitle'>
                                                        <span class='icon-holder'><i class='energy-icons fire'></i></span>
                                                        <span class='caption'>
                                                            <span class='caption_close'>".pll__('Year')."</span>
                                                            <span class='caption_open'>".pll__('Year')."</span>
                                                        </span>
                                                        <span class='selectedInfo'></span>
                                                        <span class='changeInfo'>". pll__('Change') ."</span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='collapseFive' class='panel-collapse collapse' role='tabpanel' aria-labelledby='headingFive'  data-wizard-panel='gasEnergy'>
                                            <div class='panel-body text-center'>
                                                <div class='counterPanel'>
                                                    <div class='form-group text-left'>
                                                        <div class='check fancyCheck'>
                                                            <input type='checkbox' name='doubleMeter' id='electricityConsumption' class='radio-salutation' value='1'>
                                                            <label for='electricityConsumption'>
                                                                <i class='fa fa-circle-o unchecked'></i>
                                                                <i class='fa fa-check-circle checked'></i>
                                                                <span>" . pll__('I know my consumption') . "</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <label class='block bold-600 text-left'>" . pll__('Average Gas Consumption') . "</label>
                                                    <div class='row'>
                                                        <div class='col-md-3 col-sm-3 col-xs-6 form-group'>
                                                            <div class='gas-consumption'>
                                                                <input type='text' name='gasConsumption'  />
                                                            </div>
                                                        </div>
                                                        <div class='col-md-5 col-sm-5 col-xs-6 form-group p-l-0'>
                                                            <div class='box-radio'>
                                                                <label>
                                                                    <input type='radio' name='gasConsumption' value='kWh' checked />
                                                                    <span>kWh</span>
                                                                </label>
                                                                <label>
                                                                    <input type='radio' name='gasConsumption' value='m3' />
                                                                    <span>m3</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    
                                                    
                                                    
                                                    <div class='block-desc'>".pll__('This is the average consumption of family of 4, 4500 kWh and 1700 m3 gas year')."</div>
                                                    <p class='color-red text-left'>" . pll__('More accurate estimation? Tell us more about your house') . "</p>
                                                    <div class='buttonWrapper text-left'>
                                                        <button type='button' class='btn btn-primary'>".pll__('Ok')."</button>
                                                    </div>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Solar Energy -->
                                    <div class='panel panel-default' id='electricityPanel'>
                                        <div class='panel-heading' role='tab' id='headingSix'>
                                            <h4 class='panel-title'>
                                                <a class='collapsed' role='button' data-toggle='collapse' data-parent='#accordion' href='#collapseSix' aria-expanded='false' aria-controls='collapseSix'>
                                                    <span class='headingTitle'>
                                                        <span class='icon-holder'><i class='energy-icons solar'></i></span>
                                                        <span class='caption'>
                                                            <span class='caption_close'>" . pll__('No solar energy') . "</span>
                                                            <span class='caption_open'>" . pll__('Do you have solar energy?') . "</span>
                                                        </span>
                                                        <span class='selectedInfo'></span>
                                                        <span class='changeInfo'>". pll__('Change') ."</span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='collapseSix' class='panel-collapse collapse' role='tabpanel' aria-labelledby='headingSix'  data-wizard-panel='solarEnergy'>
                                            <div class='panel-body text-center'>
                                                <div class='counterPanel'>
                                                    <div class='form-group text-left'>
                                                        <div class='check fancyCheck'>
                                                            <input type='checkbox' name='doubleMeter' id='solarPanel' class='radio-salutation' value='1'>
                                                            <label for='solarPanel'>
                                                                <i class='fa fa-circle-o unchecked'></i>
                                                                <i class='fa fa-check-circle checked'></i>
                                                                <span>" . pll__('Yes, I have solar panels') . "</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class='form-group text-left'>
                                                        <div class='check fancyCheck'>
                                                            <input type='checkbox' name='doubleMeter' id='solarCapacity' class='radio-salutation' value='1'>
                                                            <label for='solarCapacity'>
                                                                <i class='fa fa-circle-o unchecked'></i>
                                                                <i class='fa fa-check-circle checked'></i>
                                                                <span>" . pll__('I know the capacity of the transformer') . "</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <label class='block bold-600 text-left'>" . pll__('Average capacity of the transformer') . "</label>
                                                    <div class='row'>
                                                        <div class='col-md-5 col-sm-5 col-xs-12 form-group'>
                                                            <div class='solar-capacity'>
                                                                <input type='text' name='solarEnergy'  />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='block-desc'>".pll__('This is the average consumption of family 4,4500 kWh and 1700 m3 gas a year . You can change the amount if you know your exact usage.')."</div>
                                                    <div class='buttonWrapper text-left'>
                                                        <button type='button' class='btn btn-primary'>".pll__('Ok')."</button>
                                                    </div>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Current supplier -->
                                    <div class='panel panel-default' id='internetPanel'>
                                        <div class='panel-heading' role='tab' id='headingSeven'>
                                            <h4 class='panel-title'>
                                                <a class='collapsed' role='button' data-toggle='collapse' data-parent='#accordion' href='#collapseSeven' aria-expanded='false' aria-controls='collapseSeven'>
                                                    <span class='headingTitle'>
                                                        <span class='icon-holder'><i class='energy-icons edit-file'></i></span>
                                                        <span class='caption'>
                                                            <span class='caption_close'>" . pll__('Current supplier') . "</span>
                                                            <span class='caption_open'>".pll__('Whos your current supplier?')."</span>
                                                        </span>
                                                        <span class='selectedInfo'></span>
                                                        <span class='changeInfo'>". pll__('Change') ."</span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='collapseSeven' class='panel-collapse collapse' role='tabpanel' aria-labelledby='headingSeven' data-wizard-panel='currentSupplier'>
                                            <div class='panel-body'>
                                                <div class='row'>
                                                    <div class='col-md-10'>
                                                        <div class='form-group'>
                                                            <label class='text-left bold-600 '>".pll__('Select your current supplier for electricity and gas')."</label>
                                                            <div class='custom-select'>
                                                                <select class='currentSupplier'>
                                                                    <option>Select your provider</option>
                                                                    <option value='Current Supplier 1'>Current Supplier 1</option>
                                                                    <option value='Current Supplier 2'>Current Supplier 2</option>
                                                                    <option value='Current Supplier 3'>Current Supplier 3</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class='form-group'>
                                                            <label class='text-left bold-600 '>".pll__('Select your contract')."</label>
                                                            <div class='custom-select'>
                                                                <select class='currentSupplierContract'>
                                                                    <option>I dont know the contract</option>
                                                                    <option value='Contract 1'>Contract 1</option>
                                                                    <option value='Contract 2'>Contract 2</option>
                                                                    <option value='Contract 3'>Contract 3</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class='check fancyCheck'>
                                                    <input type='checkbox' name='doubleMeter' id='customerSupplier' class='radio-salutation' value='1'>
                                                    <label for='customerSupplier'>
                                                        <i class='fa fa-circle-o unchecked'></i>
                                                        <i class='fa fa-check-circle checked'></i>
                                                        <span class='bold-600'>".pll__('I have been a customer for more than 10 months')." <i class='question-icon custom-tooltip' data-toggle='tooltip' title='Helping text will show here'>?</i></span>
                                                    </label>
                                                </div>
                                                    
                                                <div class='block-desc'>".pll__('This is the average consumption of family 4,4500 kWh and 1700 m3 gas a year . You can change the amount if you know your exact usage.')."</div>    
                                        
                                                <div class='buttonWrapper text-left'>
                                                    <button type='button' class='btn btn-primary'>".pll__('Ok')."</button>
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
}

