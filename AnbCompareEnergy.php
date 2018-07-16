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
                                             aria-labelledby='headingOne'  data-wizard-panel='compare'>
                                            <div class='panel-body text-center'>
                                                <div class='form-group'>
                                                    <div class='selectServicesComp'>
                                                        <ul class='list-unstyled'>
                                                            <li>
                                                                <div>
                                                                    <input name='cat' id='dualfuel_pack_service_wiz' checked='checked' type='radio' value='dualfuel_pack' " . (($values['cat'] == 'dualfuel_pack') ? 'checked="checked"' : '') . ">
                                                                    <label for='dualfuel_pack_service_wiz'>
                                                                        <span class='icon'>
                                                                        <i class='energy-icons dualfuel_pack'></i>
                                                                        </span>
                                                                        <span class='description'>".pll__('Dualfuel Pack')."</span>
                                                                        <span class='tick-icon'>
                                                                        <i class='fa fa-check'></i>
                                                                        <i class='fa fa-square-o'></i>
                                                                        </span>
                                                                    </label>
                                                                </div>
                                                            </li>
                                                            <li>
                                                                <div>
                                                                    <input name='cat' id='electricity_service_wiz' type='radio' value='electricity' " . (($values['cat'] == 'electricity') ? 'checked="checked"' : '') . ">
                                                                    <label for='electricity_service_wiz'>
                                                                        <span class='icon'>
                                                                        <i class='energy-icons electricity'></i>
                                                                        </span>
                                                                        <span class='description'>".pll__('Electricity')."</span>
                                                                        <span class='tick-icon'>
                                                                        <i class='fa fa-check'></i>
                                                                        <i class='fa fa-square-o'></i>
                                                                        </span>
                                                                    </label>
                                                                </div>
                                                            </li>
                                                            <li>
                                                                <div>
                                                                    <input name='cat' id='gas_service_wiz' type='radio' value='gas' " . (($values['cat'] == 'gas') ? 'checked="checked"' : '') . ">
                                                                    <label for='gas_service_wiz'>
                                                                        <span class='icon'>
                                                                        <i class='energy-icons gas'></i>
                                                                        </span>
                                                                        <span class='description'>".pll__('Gas')."</span>
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
                                                <!--<div class='buttonWrapper'>
                                                    <button type='button' class='btn btn-primary'><i
                                                                class='fa fa-check'></i> " . pll__('Ok') . "
                                                    </button>
                                                </div> -->
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
                                    <!--<div class='panel panel-default' id='usagePanel'>
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
                                    </div>-->
                                    
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
}

