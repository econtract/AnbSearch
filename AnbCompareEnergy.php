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
    	if($_GET['debug']) {
    		echo "page name>>>";
    		var_dump($this->pagename);

    		echo "<br>sector>>>";
		    var_dump($this->sector);
	    }
	    if($this->sector == pll__('energy')) {
		    wp_enqueue_script('search-results-energy', plugins_url('/js/search-results-energy.js', __FILE__), array('jquery'), '1.0.5', true);
		    wp_localize_script('search-results-energy', 'search_compare_obj_energy',
			    array(
				    'ajax_url'              => admin_url('admin-ajax.php'),
				    'site_url'              => pll_home_url(),
				    'template_uri'          => get_template_directory_uri(),
				    'lang'                  => $this->getCurrentLang(),
                    'trans_loading_dots'    => pll__('Loading...')
			    )
		    );

		    if($this->pagename == pll__('results') || $this->pagename == 'energenie' ) {
			    wp_enqueue_script('compare-results-energy', plugins_url('/js/compare-results-energy.js', __FILE__), array('jquery'), '1.2.4', true);
			    wp_localize_script('compare-results-energy', 'compare_between_results_object_energy',
				    array(
					    'ajax_url' => admin_url('admin-ajax.php'),
					    'site_url' => pll_home_url(),
					    'current_pack' => pll__('Your Current Energy Pack'),
					    'select_your_pack' => pll__('I dont know the contract'),
					    'template_uri' => get_template_directory_uri(),
					    'lang' => $this->getCurrentLang(),
					    'features_label' => pll__('Features'),
					    'telecom_trans' => pll__('telecom'),
					    'energy_trans' => pll__('energy'),
					    'brands_trans' => pll__('brands'),
                        'checkout_button_trans' => pll__('connect now'),
                        'details_page_trans' => pll__('Detail'),
                        'select_your_energy_pack' => pll__('Select your contract'),
                        'change_pack' => pll__('change pack'),
                        'trans_loading_dots'    => pll__('Loading...')
				    )
			    );
		    }

            wp_enqueue_script('wizard-energy-script', plugins_url('/js/wizard-energy.js', __FILE__), array('jquery'), '1.0.3', true);

            // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
            wp_localize_script('wizard-energy-script', 'wizard_energy_object',
                array(
                    'ajax_url'      => admin_url('admin-ajax.php'),
                    'zip_empty'     => pll__('Zip cannot be empty'),
                    'zip_invalid'   => pll__('Please enter valid Zip Code'),
                    'offers_msg'    => pll__( 'offers' )." " . pll__('starting from'),
                    'no_offers_msg' => pll__('No offers in your area'),
                    'currency'      => $this->getCurrencySymbol($this->currencyUnit)
                ));
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
		    'enable_need_help' => false,
            'hidden_prodsel' => '',
            'supplier_service'  => '',
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

	    $supplierHtml = '';
	    if (!empty($values['hidden_sp'])) {
		    $supplierHtml = $this->generateHiddenSupplierHtml($values['hidden_sp']);
	    } else {
		    //$supplierHtml = $this->generateSupplierHtml($values['pref_cs']);
		    //This is not needed, uncomment this if you want to display the suppliers list
	    }

	    $needHelpHtml = "";

	    if ($values['enable_need_help'] == true) {
		    $needHelpHtml .= "<div class='needHelp'>
                                <a href='javascript:void(0)' data-toggle='modal' data-target='#widgetPopupEnergy' data-backdrop='static' data-keyboard='false'>
                                    <i class='floating-icon fa fa-chevron-right'></i>
                                    <h6>" . pll__('get a personalized simulation') . "</h6>
                                    <p>" . pll__('We calculate your potential savings') . "</p>
                                </a>
                              </div>";
	    }

	    //In below call change '/' . getUriSegment(1) . '/' .pll__('results') to pll__('results') in case you want to submit it on the same URL struture like on provider details page.
	    $formNew = $this->getSearchBoxContentHtml($values, $needHelpHtml, $supplierHtml, pll__("Compare Energy Prices"), false, "", '/' . getUriSegment(1) . '/' .pll__('results'));

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
        $_GET['producttype'] = (!isset($_GET['producttype'])) ? "dualfuel_pack" : $_GET['producttype'];
        $_GET['sg'] = (!isset($_GET['sg'])) ? 'consumer' : $_GET['sg'];
        $_GET['f'] = (!isset($_GET['f'])) ? '2' : $_GET['f'];
        $resultsUsages = json_decode($this->usageResultsEnergy());

        $du = (empty($values['du'])) ? $resultsUsages->data->du : $values['du'];
        $nu = (empty($values['nu'])) ? $resultsUsages->data->nu : $values['nu'];
        $nou = (empty($values['nou'])) ? $resultsUsages->data->nou : $values['nou'];
        $u = (empty($values['u'])) ? $resultsUsages->data->u : $values['u'];

        $electricityHide = $gasHide = '';
        if($values['supplier_service'] === 'electricity'){ $gasHide = 'hide'; }
        if($values['supplier_service'] === 'gas'){ $electricityHide = 'hide'; }

        $titleHtml = "<h3>" . pll__('Compare energy rates') . "</h3>";
	    if ($hideTitle) {
		    $titleHtml = "";
	    }

        $resultsPageUri = ($values['hidden_prodsel'] == 'yes') ? '' : $resultsPageUri;

	    $hiddenMultipleProvidersHtml = $this->getSuppliersHiddenInputFields($values, $supplierHtml);
        $hiddenProdSelHTML = '';
	    if($values['hidden_prodsel'] == 'yes') {
            $hiddenProdSelHTML = '<input type="hidden" name="hidden_prodsel_cmp" value="yes" />';
        }
	    // html for quick search content
	    $formNew = "<div class='quick-search-content'>
                    <div class='searchBox'>
                        " . $needHelpHtml . "
                        " . $titleHtml . "
                        <div class='formWraper'>
                            <form action='" . $resultsPageUri . "' id='quickSearchForm'>";
	                if($values['hidden_prodsel'] == '') {
                        $formNew.= "<div class='form-group hide'>
                                	<label>" . pll__('I like to compare') . "</label>
                                </div>
                                <div class='form-group'>
                                    <ul class='service-tabs'>
                                        <li>
                                            <input class='call-usages-data' type='radio' name='cat' id='service_dual_fuel' value='dualfuel_pack' ". ( ($values['cat'] === 'dualfuel_pack' || empty($values['cat']) || $values['supplier_service'] === 'dualfuel_pack' ) ? 'checked="checked"' : '') .">
                                            <label for='service_dual_fuel' class='service-dual-fuel'>
                                                <i></i>
                                                <span class='service-label'>".pll__('Dual Fuel')."</span>
                                                <span class='check-box'></span>
                                            </label>
                                        </li>
                                        <li>
                                            <input class='call-usages-data' type='radio' name='cat' id='service_electricity' value='electricity' ". (($values['cat'] === 'electricity' ||  $values['supplier_service'] === 'electricity') ? 'checked="checked"' : '') .">
                                            <label for='service_electricity' class='service-electricity'>
                                                <i></i>
                                                <span class='service-label'>".pll__('Electricity')."</span>
                                                <span class='check-box'></span>
                                            </label>
                                        </li>
                                        <li>
                                            <input class='call-usages-data' type='radio' name='cat' id='service_gas' value='gas' ". (($values['cat'] === 'gas'  || $values['supplier_service'] === 'gas') ? 'checked="checked"' : '') .">
                                            <label for='service_gas' class='service-gas'>
                                                <i></i>
                                                <span class='service-label'>".pll__('Gas')."</span>
                                                <span class='check-box'></span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>";
                    }
                    $formNew.= "{$infoMsg}
                                <div class='form-group'>
                                    <div class='check fancyCheck'>
                                        <input type='hidden' name='sg' value='consumer' class='call-usages-data'>
                                        <input type='checkbox' name='sg' id='showBusinessDeal' class='call-usages-data radio-salutation' value='sme' ". (($values['sg'] === 'sme') ? 'checked="checked"' : '') .">
                                        <label for='showBusinessDeal'>
                                            <i class='fa fa-circle-o unchecked'></i>
                                            <i class='fa fa-check-circle checked'></i>
                                            <span>".pll__('Show business deals')."</span>
                                        </label>
                                    </div>
                                </div>
                                <div class='form-group row flex-row-fix'>
                                        <div class='col-md-4'>
                                            <label for='installation_area'>" . pll__('Installation area') . "</label>
                                        </div>
                                        <div class='col-md-8 p-l-0'>
                                            <input type='text' class='form-control typeahead' id='installation_area' name='zip' 
                                            value='" . ((!empty($values['zip'])) ? $values['zip'] : '') . "' placeholder='" . pll__('Enter Zipcode') . "'
                                            data-error='" . pll__('Please enter valid zip code') . "' autocomplete='off' query_method='cities' query_key='postcode' required>
                                      </div>
                                </div>
                                
                                <div class='form-group family-members-container $electricityHide'>
                                    <label>".pll__('How many family members?')."</label>
                                    <div class='family-members'>
                                        <fieldset class='person-sel-sm'>

                                            <input class='call-usages-data' type='radio' id='person6' name='f' value='6' usage-val='".KWH_5_PLUS_PERSON."' usage-val-night='".KWH_5_PLUS_PERSON_NIGHT."' usage-night-ex='".KWH_5_PLUS_PERSON_NIGHT_EX."' ". (($values['f'] === '6') ? 'checked="checked"' : '') .">
                                            <label class='full custom-tooltip' for='person6' data-toggle='tooltip' title='5+ ".pll__('persons')."'>
                                                <span class='person-value'>5+</span>
                                            </label>

                                            <!--<input class='call-usages-data' type='radio' id='person5' name='f' value='5' usage-val='".KWH_5_PERSON."' usage-val-night='".KWH_5_PERSON_NIGHT."' usage-night-ex='".KWH_5_PERSON_NIGHT_EX."' ". (($values['f'] === '5') ? 'checked="checked"' : '') .">
                                            <label class='full custom-tooltip' for='person5' data-toggle='tooltip' title='5 ".pll__('persons')."'></label>-->

                                            <input class='call-usages-data' type='radio' id='person4' name='f' value='4' usage-val='".KWH_4_PERSON."' usage-val-night='".KWH_4_PERSON_NIGHT."' usage-night-ex='".KWH_4_PERSON_NIGHT_EX."' ". (($values['f'] === '4') ? 'checked="checked"' : '') .">
                                            <label class='full custom-tooltip' for='person4' data-toggle='tooltip' title='4 ".pll__('persons')."'></label>


                                            <input class='call-usages-data' type='radio' id='person3' name='f' value='3' usage-val='".KWH_3_PERSON."' usage-val-night='".KWH_3_PERSON_NIGHT."' usage-night-ex='".KWH_3_PERSON_NIGHT_EX."' ". (($values['f'] === '3') ? 'checked="checked"' : '') .">
                                            <label class='full custom-tooltip' for='person3' data-toggle='tooltip' title='3 ".pll__('persons')."'></label>


                                            <input class='call-usages-data' type='radio' id='person2' name='f' value='2' usage-val='".KWH_2_PERSON."' usage-val-night='".KWH_2_PERSON_NIGHT."' usage-night-ex='".KWH_2_PERSON_NIGHT_EX."' ". (($values['f'] === '2' || empty($values['f'])) ? 'checked="checked"' : '') .">
                                            <label class='full custom-tooltip' for='person2' data-toggle='tooltip' title='2 ".pll__('persons')."'></label>


                                            <input class='call-usages-data' type='radio' id='person1' name='f' value='1' usage-val='".KWH_1_PERSON."' usage-val-night='".KWH_1_PERSON_NIGHT."' usage-night-ex='".KWH_1_PERSON_NIGHT_EX."' ". (($values['f'] === '1') ? 'checked="checked"' : '') .">
                                            <label class='full custom-tooltip' for='person1' data-toggle='tooltip' title='1 ".pll__('persons')."'></label>
                                            <div class='clearfix'></div>
                                        </fieldset>
                                        <div class='double-meter-fields'>
                                            <div class='field general-energy kwh-energy'>
                                                <i></i>
                                                <input id='single-meter-du' type='text' name='du' api-value='". $du ."' value='". $du ."'/>
                                                <label>kwh</label>
                                            </div>
                                            <div class='field day-night-energy kwh-energy hide'>
                                                <div class='day-energy'>
                                                    <i></i>
                                                    <input id='double-meter-du' type='text' name='du' api-value='". $du ."' value='". $du ."'/>
                                                    <label>kwh</label>
                                                </div>
                                                <div class='night-energy'>
                                                    <i></i>
                                                    <input id='double-meter-nu' type='text' name='nu' api-value='". $nu ."' value='". $nu ."'/>
                                                    <label>kwh</label>
                                                </div>
                                            </div>
                                            <div class='field exclusive-meter-field hide'>
                                                <div class='night-energy'>
                                                    <i></i>
                                                    <input id='exclusive-night-meter-nou' type='text' api-value='". $nou ."' name='nou' value='". $nou ."'/>
                                                    <label>kwh</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class='form-group'>
                                        <div class='check fancyCheck'>
                                            <input type='checkbox' name='meter' id='doubleMeter' class='call-usages-data radio-salutation' value='double' ". (($values['meter'] === 'double') ? 'checked="checked"' : '') .">
                                            <label for='doubleMeter'>
                                                <i class='fa fa-circle-o unchecked'></i>
                                                <i class='fa fa-check-circle checked'></i>
                                                <span>".pll__('Double meter')."</span>
                                            </label>
                                        </div>
                                        
                                        <div class='check fancyCheck'>
                                            <input type='checkbox' name='exc_night_meter' id='exclusiveMeter' class='call-usages-data radio-salutation' value='1' ". (($values['exc_night_meter'] === '1') ? 'checked="checked"' : '') .">
                                            <label for='exclusiveMeter'>
                                                <i class='fa fa-circle-o unchecked'></i>
                                                <i class='fa fa-check-circle checked'></i>
                                                <span>".pll__('Exclusive night meter')."</span>
                                            </label>
                                        </div>
                                        
                                        <div class='solar-panel-container'>
                                            <div class='check fancyCheck'>
                                                <input type='checkbox' name='has_solar' id='solarPanel' class='call-usages-data radio-salutation' value='1' ". (($values['has_solar'] === '1') ? 'checked="checked"' : '') .">
                                                <label for='solarPanel'>
                                                    <i class='fa fa-circle-o unchecked'></i>
                                                    <i class='fa fa-check-circle checked'></i>
                                                    <span>".pll__('I have solar panels')."</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class='form-group house-type-container $gasHide'>
                                    <label>".pll__('What type of house?')."</label>
                                    <div class='house-selector'>
                                        ".$this->getHouseTypeHtml($values)."
                                        <div class='field'>
                                            <i></i>
                                            <input type='text' id='m3_u' name='u' api-value='". $u ."' value='". $u ."'/>
                                            <input type='hidden' name='ut' value='kwh'/>
                                            <label>kWh</label>
                                        </div>
                                    </div>
                                </div>
                                <div class='btnWrapper text-center p-b-0'>
                                	{$hiddenMultipleProvidersHtml}
                                	{$supplierHtml}
                                	{$hiddenProdSelHTML}
                                    <button name='searchSubmit' type='submit' disabled class='btn btn-default btn-block' >$submitBtnTxt</button>
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
        /** @var \AnbTopDeals\AnbProductEnergy $anbTopDeals */
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
	        $supplierId  = $product->supplier_id;
	        $productType = $product->producttype;

            $endScriptTime = getEndTime();
            displayCallTime($startScriptTime, $endScriptTime, "Total page load time for Results page invidual gridView till prepareProductData.");

            list(, , , , $toCartLinkHtml, $checkoutPageLink) = $anbTopDeals->getToCartAnchorHtml($parentSegment, $productData['product_id'], $productData['supplier_id'], $productData['sg'], $productData['producttype'], $forceCheckAvailability);

            $blockLinkClass = 'block-link';
            if($forceCheckAvailability) {
                $blockLinkClass = 'block-link missing-zip';
            }
            $toCartLinkHtml = '<a '.$toCartLinkHtml.' class="link '.$blockLinkClass.'">' . pll__( 'Order Now' ) . '</a>';

            if($productData['commission'] === false) {
                $toCartLinkHtml = '<a href="#not-available" class="link block-link not-available">' . pll__('Not Available') . '</a>';
            }

            $servicesHtml = $anbTopDeals->getServicesHtml( $product, $pricing );

            $promoPopUpLogo = $anbTopDeals->getLogoSection($productData);

            $promoPopUpDetailsArr = $anbTopDeals->getPromoSection($product, true);

            if(!empty($results->current)) {
                list($productsData, $labels) = $this->getCompareOverviewData($results->current, $listProduct);
                include(locate_template('template-parts/section/compare-popups-top-section-energy-results.php'));
                $isSetCompare = true;
            } else {
                include(locate_template('template-parts/section/energy-overview-popup.php'));
                $isSetCompare = false;
            }
            include(locate_template('template-parts/section/energy-promotions-popup.php'));
            $productResp .= '<div class="result-box-container" id="listgridview_'.$chkbox.'">';
            $productResp .= '<div class="result-box">';
            $productResp .= '<div class="top-label">'. $anbTopDeals->getBadgeSection('') .'</div>';
            $productResp .= '<div class="flex-grid">';
            $productResp .= '<div class="cols">';
            $productResp .= $anbTopDeals->getProductDetailSection( $productData, '', false, '', true  );
            $productResp .= $anbTopDeals->getGreenPeaceRatingWithImages( $product );
            $productResp .= '</div>';
            $productResp .= '<div class="cols">';
            $productResp .= '<ul class="green-services">' .$servicesHtml. '</ul>';
            $productResp .= '</div>';
            $productResp .= '<div class="cols grid-hide">' .$anbTopDeals->getPromoSection( $product ). '</div>';
            $productResp .= '<div class="cols">';
            $productResp .= '<div class="actual-price-board">' .$anbTopDeals->getPriceHtml( $productData, $pricing, true, $isSetCompare ). '</div>';
            $productResp .= '</div>';
            $productResp .= '<div class="cols grid-show">' .$anbTopDeals->getPromoSection( $product ). '</div>';
            $productResp .= '<div class="cols">';
            if(is_object($listProduct->savings)) {
                $productResp .= $anbTopDeals->getPotentialSavings($listProduct->savings);
            }

            //$yearAdv = $pricing->yearly->price - $pricing->yearly->promo_price;
            $yearAdv = $pricing->yearly->advantage;
            if($yearAdv !== 0):
                $yearAdvArr = formatPriceInParts($yearAdv, 2);
                //$monthlyAdv = $pricing->monthly->price - $pricing->monthly->promo_price;
                $monthlyAdv = $pricing->monthly->advantage;
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
            /*$productResp .= '<a href="#" class="btn btn-primary all-caps">connect now</a>';*/
	        $productResp .= "<a class='btn btn-primary all-caps' href='". $checkoutPageLink . '?' . http_build_query($_GET). "&hidden_prodsel_cmp=yes&product_to_cart=yes&product_id=".$productId."&provider_id=".$supplierId."&producttype=".$productType."'>".pll__('connect now')."</a>";

			$detailHtml = '<a href="'.getEnergyProductPageUri($productData) . '?' . http_build_query($_GET). '&hidden_prodsel_cmp=yes&product_to_cart=yes&product_id='.$productId.'&provider_id='.$supplierId.'&producttype='.$productType.'" class="link block-link all-caps">'.pll__('Detail').'</a>';
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
            $productResp .= '<label><input type="checkbox" value="listgridview_'.$chkbox.'" id="cmpcheckbox'.$productId.'" data-val="'.$productId.'">'.pll__('Compare').'</label>';
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
                                                    <span class='headingTitle'>
                                                        <span class='icon-holder'><i class='energy-icons electricity-gas'></i></span>
                                                        <span class='caption'>
                                                            <span class='caption_close'>" . pll__('compare') . "</span>
                                                            <span class='caption_open'>" . pll__('compare') . "</span>
                                                        </span>
                                                        <span class='selectedInfo'></span>
                                                        <span class='changeInfo'>". pll__('Change') ."</span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='compareCompPanel' class='panel-collapse collapse in' role='tabpanel' aria-labelledby='headingOne'  data-wizard-panel='compare'>
                                            <div class='panel-body text-center'>
                                                <div class='form-group'>
                                                    <div class='selectServicesComp'>
                                                        <label class='block bold-600 text-left'>".pll__('I like to compare')."</label>
                                                        <ul class='service-tabs service-tabs-svg'>
                                                            <li>
                                                                <input class='call-usages-data' name='cat' id='dualfuel_pack_service_wiz' checked='checked' type='radio' data-val='".pll__('Dual fuel Pack')."' value='dualfuel_pack' " . (($values['cat'] == 'dualfuel_pack' || empty($values['cat'])) ? 'checked="checked"' : '') . ">
                                                                <label for='dualfuel_pack_service_wiz' class='service-dual-fuel'>
                                                                    <i><svg class='svg-energy'><use xlink:href='".get_template_directory_uri()."/images/svg-sprite.svg#svg-energy'></use></svg></i>
                                                                    <i><svg class='svg-energy-gas'><use xlink:href='".get_template_directory_uri()."/images/svg-sprite.svg#svg-energy-gas'></use></svg></i>
                                                                    <span class='service-label'>".pll__('Dual fuel Pack')."</span>
                                                                    <span class='check-box'></span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <input class='call-usages-data' name='cat' id='electricity_service_wiz' type='radio' data-val='".pll__('Electricity')."' value='electricity' " . (($values['cat'] == 'electricity') ? 'checked="checked"' : '') . ">
                                                                <label for='electricity_service_wiz' class='service-electricity'>
                                                                <i><svg class='svg-energy'><use xlink:href='".get_template_directory_uri()."/images/svg-sprite.svg#svg-energy'></use></svg></i>
                                                                <span class='service-label'>".pll__('Electricity')."</span>
                                                                <span class='check-box'></span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <input class='call-usages-data' name='cat' id='gas_service_wiz' type='radio' data-val='".pll__('Gas')."' value='gas' " . (($values['cat'] == 'gas') ? 'checked="checked"' : '') . ">
                                                                <label for='gas_service_wiz' class='service-gas'>
                                                                    <i><svg class='svg-energy-gas'><use xlink:href='".get_template_directory_uri()."/images/svg-sprite.svg#svg-energy-gas'></use></svg></i>
                                                                    <span class='service-label'>".pll__('Gas')."</span>
                                                                    <span class='check-box'></span>
                                                                </label>
                                                            </li>
                                                        </ul>
                                                        <div class='block-desc'>" . pll__('Combining service often helps you save every year.') . "</div>
                                                        
                                                        <!-- div class='buttonWrapper text-left'>
                                                            <button type='button' class='btn btn-primary'>" . pll__('Ok') . "</button>
                                                        </div -->
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
                                            <div class='panel-body'>
                                                <div class='singleFormWrapper'>
                                                    <div class='row'>
                                                        <div class='col-md-3 p-r-0 col-sm-3 col-xs-12 p-t-12 width-auto'><label for='installation_area' class='control-label p-l-0'>" . pll__('Installation area') . "</label></div>
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
                                    <div class='panel panel-default' id='usagePanel'>
                                        <div class='panel-heading' role='tab' id='consumerHeading'>
                                            <h4 class='panel-title'>
                                                <a role='button' data-toggle='collapse' data-parent='#accordion'
                                                   href='#consumerPanel' aria-expanded='true'
                                                   aria-controls='collapseOne'>
                                                    <span class='headingTitle'>
			                                            <span class='icon-holder'>
			                                            	<i class='icon wizard location'></i>
			                                            </span>
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
                                                        <input class='call-usages-data' name='sg' value='consumer' id='wiz_private_type' type='radio'
                                                               " . (("consumer" == $values['sg'] || empty($values['sg'])) ? 'checked="checked"' : '') . ">
                                                        <label for='wiz_private_type'>
                                                            <i class='fa fa-circle-o unchecked'></i>
                                                            <i class='fa fa-check-circle checked'></i>
                                                            <span>" . pll__('Private') . "</span>
                                                        </label>
                                                        <input class='call-usages-data' name='sg' value='sme' id='wiz_business_type' type='radio'
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
                                    <div class='panel panel-default family-members-container electricity-content' id='familyPanel'>
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
                                            <div class='panel-body'>
                                    
                                                <div class='totalPersonWizard'>
                                                    <div class='compPanel withStaticToolTip'>
                                                        <div class='selectionPanel clearfix energy-family'>
                                                            <div class='form-group'>
                                                                <label class='block bold-600'>". pll__('How many family members?') ."</label>
                                                            </div>
                                                            <fieldset class='person-sel gray fancyComp'>
                                                                <input class='call-usages-data' type='radio' id='person6' name='f' value='6' 
                                                                " . (("6" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person6' title='6 ". pll__('persons') ."'>
                                                                    <span class='person-value'>5+</span>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>6 ". pll__('person is betterInfo about extensive use of internet') ." </p>
                                                                </div>
                                    
                                                                <input class='call-usages-data' type='radio' id='person5' name='f' value='5' 
                                                                " . (("5" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person5' title='5 ". pll__('persons') ."'>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>". pll__('5th Person. Info about extensive use of internet.') .". </p>
                                                                </div>
                                    
                                                                <input class='call-usages-data' type='radio' id='person4' name='f' value='4' 
                                                                " . (("4" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person4' title='4 ". pll__('persons') ."'>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>". pll__('4thInfo about extensive use of internet') ." </p>
                                                                </div>
                                    
                                                                <input class='call-usages-data' type='radio' id='person3' name='f' value='3' 
                                                                " . (("3" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person3' title='3 ". pll__('persons') ."'>
                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>". pll__('3rd Info about extensive use of internet') ."</p>
                                                                </div>
                                    
                                                                <input class='call-usages-data' type='radio' id='person2' name='f' value='2' 
                                                                " . (("2" == $values['f']) ? 'checked="checked"' : '') . "/>
                                                                <label class = 'full' for='person2' title='2 ". pll__('persons') ."'>

                                                                </label>
                                                                <div class='customTooltip'>
                                                                    <p>". pll__('Two person info about extensive use of internet') ."</p>
                                                                </div>
                                    
                                                                <input class='call-usages-data' type='radio' id='person1' name='f' value='1' 
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
                                    
                                    <!-- House -->
                                    <div class='panel panel-default house-type-container gas-content' id='housePanel'>
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
                                            <div class='panel-body'>
                                                <div class='counterPanel'>
                                                    <div class='form-group text-left'>
                                                        <label class='block bold-600 text-left'>" . pll__('What type of house?') . "</label>
                                                    </div>
                                                    <div class='house-selector'>
                                                        ".$this->getHouseTypeHtml($values)."
				                                        <!-- div class='field m-l-10'>
				                                            <i></i>
				                                            <input type='text' id='m3_u' name='u' value='". (($values['u']) ?: '') ."'/>
				                                            <input type='hidden' name='ut' value='m3'/>
				                                            <label>m3</label>
				                                            <span class='question-circle custom-tooltip' data-toggle='tooltip' title='".pll__('Informational text for gas')."'></span>
				                                        </div -->
                                                    </div>
                                                    
                                                    <p class='red-link m-b-20' id='houseMoreDetail'>".pll__('Tell us more for a accurate estimation')."</p>
                                                    
                                                    <div class='block-desc'>".pll__('This is the average consumption of family of 4 with this house charcteristics is 4500 kWh and 1700 m3 gas a year.')."</div>
                                                
                                                    
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

                                    <!-- Solar Energy -->
                                    <div class='panel panel-default solar-panel-container electricity-content' id='solarEnergyPanel'>
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
                                                            <input type='checkbox' name='has_solar' id='solarPanel' class='call-usages-data radio-salutation' value='1' ". (($values['has_solar'] === '1') ? 'checked="checked"' : '') .">
                                                            <label for='solarPanel'>
                                                                <i class='fa fa-circle-o unchecked'></i>
                                                                <i class='fa fa-check-circle checked'></i>
                                                                <span>" . pll__('Yes, I have solar panels') . "</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class='form-group text-left'>
                                                        <div class='check fancyCheck'>
                                                            <input type='checkbox' name='transCapacityCheck' id='transCapacityCheck' class='call-usages-data radio-salutation' value='1' ". (($values['transCapacityCheck'] === '1') ? 'checked="checked"' : '') .">
                                                            <label for='transCapacityCheck'>
                                                                <i class='fa fa-circle-o unchecked'></i>
                                                                <i class='fa fa-check-circle checked'></i>
                                                                <span>" . pll__('I know the capacity of the transformer') . "</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <div id='have_transformer' class='". (($values['transCapacityCheck'] != '1') ? 'hide' : '') ."'>
                                                        <label class='block bold-600 text-left'>" . pll__('Average capacity of the transformer') . "</label>
                                                        <div class='row'>
                                                            <div class='col-md-5 col-sm-5 col-xs-12 form-group'>
                                                                <div class='solar-capacity'>
                                                                    <input id='usage_solar_capacity' type='text' name='solar_capacity' value='". (($values['solar_capacity']) ?: '') ."' />
                                                                </div>
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
                                    
                                     <!-- Double meter -->
                                     <div class='panel panel-default family-members-container electricity-content' id='doubleMeterPanel'>
                                        <div class='panel-heading' role='tab' id='headingTwo'>
                                            <h4 class='panel-title'>
                                                <a class='collapsed' role='button' data-toggle='collapse' data-parent='#accordion' href='#collapseTwo' aria-expanded='false' aria-controls='collapseTwo'>
                                                    <span class='headingTitle'>
                                                        <span class='icon-holder'><i class='energy-icons double-meter'></i></span>
                                                        <span class='caption'>
                                                            <span class='caption_close'>" . pll__('Set Double meter') . "</span>
                                                            <span class='caption_open'>"  . pll__('Set Double meter') . "</span>
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
                                                                <input class='call-usages-data' type='radio' value='single' name='meter' ".(($values['meter'] == pll__('single') || empty($values['meter'])) ? "checked='checked'" : '')."/>
                                                                <span></span>
                                                                <img src='".get_template_directory_uri()."/images/common/single-meter.svg' alt='' height='52' />
                                                            </label>
                                                            
                                                            <label>
                                                                <input class='call-usages-data' type='radio' value='double' name='meter' ".(($values['meter'] == pll__('double')) ? "checked='checked'" : '')." id='doubleMeterConsumption' class='check-consumption'/>
                                                                <span></span>
                                                                <img src='".get_template_directory_uri()."/images/common/double-meter.svg' alt='' height='52' />
                                                            </label>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class='form-group text-left'>
                                                        <div class='check fancyCheck'>
                                                            <input type='checkbox' name='exc_night_meter' id='exc_night_meter' class='call-usages-data hasContent' value='1' ".(($values['exc_night_meter'] == '1') ? "checked='checked'" : '').">
                                                            <label for='exc_night_meter'>
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
                                    <div class='panel panel-default family-members-container electricity-content' id='electricityBlockPanel'>
                                        <div class='panel-heading' role='tab' id='headingThree'>
                                            <h4 class='panel-title'>
                                                <a class='collapsed' role='button' data-toggle='collapse' data-parent='#accordion' href='#collapseThree' aria-expanded='false' aria-controls='collapseThree'>
                                                    <span class='headingTitle'>
                                                        <span class='icon-holder'><i class='energy-icons plug'></i></span>
                                                        <span class='caption'>
                                                            <span class='caption_close'>" . pll__('Electricity Usage') . "</span>
                                                            <span class='caption_open'>" . pll__('Electricity Usage') . "</span>
                                                        </span>
                                                        <span class='selectedInfo'></span>
                                                        <span class='changeInfo'>". pll__('Change') ."</span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='collapseThree' class='panel-collapse collapse' role='tabpanel' aria-labelledby='headingThree'  data-wizard-panel='electricityBlock'>
                                            <div class='panel-body text-center'>
                                                <div class='counterPanel'>
                                                    <div class='form-group text-left'>
                                                        <div class='check fancyCheck'>
                                                            <input type='checkbox' name='consumption_electricity' id='consumption_electricity' class='radio-salutation check' value='1' ".(($values['consumption_electricity'] == '1' || !empty($values['du'])) ? "checked='checked'" : '').">
                                                            <label for='consumption_electricity'>
                                                                <i class='fa fa-circle-o unchecked'></i>
                                                                <i class='fa fa-check-circle checked'></i>
                                                                <span>" . pll__('I know my consumption') . "</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class='row ".((empty($values['du']) || (!empty($values['consumption_electricity'] && $values['consumption_electricity'] != '1'))) ? "hide" : '')."' id='consumption_electricity_content'>
                                                        <div class='col-md-5 col-sm-5 col-xs-12 form-group'>
                                                            <label class='block bold-600 text-left'>" . pll__('Day consumption') . "</label>
                                                            <div class='day-consumption day-consumption-grey' id='doubleMeterConsumption_grey'>
                                                                <input id='single-meter-du' type='text' disabled='disabled' name='du' value='".(($values['du']) ?: '')."' />
                                                            </div>
                                                        </div>
                                                        <div class='col-md-5 col-sm-5 col-xs-12 form-group ".(($values['meter'] != 'double') ? "hide" : '')."' id='doubleMeterConsumption_content'>
                                                            <label class='block bold-600 text-left'>" . pll__('Night consumption') . "</label>
                                                            <div class='night-consumption'>
                                                                <input id='double-meter-nu' type='text' disabled='disabled' name='nu' value='".(($values['nu']) ?: '')."'/>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='row'>
                                                        <div class='col-md-5 col-sm-5 col-xs-12 form-group hide exc_night_meter_content'>
                                                            <label class='block bold-600 text-left'>" . pll__('Exclusive night meter') . "</label>
                                                            <div class='night-consumption'>
                                                                <input id='exclusive-night-meter-nou' type='text' disabled='disabled' name='nou' value='".(($values['nou']) ?: '')."'/>
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
                                    
                                    <!-- Gas -->
                                    <div class='panel panel-default house-type-container gas-content' id='gasPanel'>
                                        <div class='panel-heading' role='tab' id='headingFive'>
                                            <h4 class='panel-title'>
                                                <a class='collapsed' role='button' data-toggle='collapse' data-parent='#accordion' href='#collapseFive' aria-expanded='false' aria-controls='collapseFive'>
                                                    <span class='headingTitle'>
                                                        <span class='icon-holder'><i class='energy-icons fire'></i></span>
                                                        <span class='caption'>
                                                            <span class='caption_close'>".pll__('Gas Usage')."</span>
                                                            <span class='caption_open'>".pll__('Gas Usage')."</span>
                                                        </span>
                                                        <span class='selectedInfo'></span>
                                                        <span class='changeInfo'>". pll__('Change') ."</span>
                                                    </span>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id='collapseFive' class='panel-collapse collapse' role='tabpanel' aria-labelledby='headingFive'  data-wizard-panel='gas'>
                                            <div class='panel-body'>
                                                <div class='counterPanel'>
                                                    <div class='form-group text-left'>
                                                        <div class='check fancyCheck'>
                                                            <input type='checkbox' name='gas_consumption' id='gas_consumption' class='radio-salutation check' value='1' ".(($values['gas_consumption'] == '1' || !empty($values['u'])) ? "checked='checked'" : '').">
                                                            <label for='gas_consumption'>
                                                                <i class='fa fa-circle-o unchecked'></i>
                                                                <i class='fa fa-check-circle checked'></i>
                                                                <span>" . pll__('I know my consumption') . "</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div id='gas_consumption_content' class='".(empty($values['u']) || (!empty($values['gas_consumption'] && $values['gas_consumption'] != '1')) ? "hide" : '')."'>
                                                        <label class='block bold-600 text-left'>" . pll__('Average Gas Consumption') . "</label>
                                                        <div class='row'>
                                                            <div class='col-md-3 col-sm-3 col-xs-6 form-group'>
                                                                <div class='gas-consumption'>
                                                                    <input id='m3_u' type='text' name='u' value='". (($values['u']) ?: '') ."' />
                                                                </div>
                                                            </div>
                                                            <div class='col-md-5 col-sm-5 col-xs-6 form-group p-l-0'>
                                                                <div class='box-radio'>
                                                                    <label>
                                                                        <input type='radio' name='ut' value='kwh' ".(($values['ut'] == 'kwh' || (empty($values['ut']) && ($values['cat'] == 'gas' || $values['cat'] == 'dualfuel_pack'))) ? "checked='checked'" : '')." />
                                                                        <span>kWh</span>
                                                                    </label>
                                                                    <label>
                                                                        <input type='radio' name='ut' value='m3' ".(($values['ut'] == 'm3') ? "checked='checked'" : '')." />
                                                                        <span>m3</span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class='block-desc'>".pll__('This is the average consumption of family of 4, 4500 kWh and 1700 m3 gas year')."</div>
                                                    <p class='red-link m-b-20' id='houseMoreDetailBellow'>" . pll__('More accurate estimation? Tell us more about your house') . "</p>
                                                    
                                                    <div class='text-left p-t-10 p-b-20 hide' id='houseMoreDetailContent'>
                                                        <div class='row'>
                                                            <div class='col-md-10'>
                                                                <div class='form-group'>
                                                                    <label class='text-left bold-600 '>".pll__('Is your roof isolated?')."</label>
                                                                    <div class='custom-select'>
                                                                        <select disabled='disabled' class='call-usages-data' name='roof_isolation'>
                                                                            <option value='1'>".pll__('Yes')."</option>
                                                                            <option value='0'>".pll__('No')."</option>
                                                                            <option value=''>".pll__('I dont know')."</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                    
                                                                <div class='form-group'>
                                                                    <label class='text-left bold-600 '>".pll__('Are your walls isolated?')."</label>
                                                                    <div class='custom-select'>
                                                                        <select disabled='disabled' class='call-usages-data' name='wall_isolation'>
                                                                            <option value='1'>".pll__('Yes')."</option>
                                                                            <option value='0'>".pll__('No')."</option>
                                                                            <option value=''>".pll__('I dont know')."</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                    
                                                                <div class='form-group'>
                                                                    <label class='text-left bold-600 '>".pll__('What type of windows do you have?')."</label>
                                                                    <div class='custom-select'>
                                                                        <select disabled='disabled' class='call-usages-data' name='glass'>
                                                                            <option value='single'>".pll__('Single glass')."</option>
                                                                            <option value='double'>".pll__('Double glass')."</option>
                                                                            <option value=''>".pll__('I dont know')."</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                    
                                                                <div class='form-group'>
                                                                    <label class='text-left bold-600 '>".pll__('How old is your boiler?')."</label>
                                                                    <div class='custom-select'>
                                                                        <select disabled='disabled' class='call-usages-data' name='boiler'>
                                                                            <option value='0'>".pll__('I dont have one')."</option>
                                                                            <option value='<10'>".pll__('Younger then 10 years')."</option>
                                                                            <option value='>10'>".pll__('Older then 10 years')."</option>
                                                                            <option value=''>".pll__('I dont know')."</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                    
                                                                <div class='form-group'>
                                                                    <label class='text-left bold-600 '>".pll__('What about your CV ketel?')."</label>
                                                                    <div class='custom-select'>
                                                                        <select disabled='disabled' class='call-usages-data' name='cv'>
                                                                            <option value='0'>".pll__('I dont have one')."</option>
                                                                            <option value='<10'>".pll__('Younger then 10 years')."</option>
                                                                            <option value='>10'>".pll__('Older then 10 years')."</option>
                                                                            <option value=''>".pll__('I dont know')."</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <a class='show-less-link' id='houseLessDetail'>".pll__('')."Show less</a>
                                                    </div>
                                                    
                                                    <div class='buttonWrapper text-left'>
                                                        <button type='button' class='btn btn-primary'>".pll__('Ok')."</button>
                                                    </div>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Current supplier -->
                                    <div class='panel panel-default' id='currentSupplierPanel'>
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
                                                                <select class='currentSupplier' id='currentProviderEnergy' name='supplier'>
							                                        <option value=''>".pll__('Select your provider')."</option>
							                                        ".supplierForDropDown(($values['supplier']) ?: $values['cmp_sid'], ['cat' => 'dualfuel_pack, electricity, gas'])."
							                                    </select>
                                                            </div>
                                                        </div>
                                                        <div class='form-group'>
                                                            <label class='text-left bold-600 '>".pll__('Select your contract')."</label>
                                                            <div class='custom-select'>
                                                                <select class='currentSupplierContract' name='currentPack' id='currentPackEnergy'>
                                                                    <option value=''>".pll__('I dont know the contract')."</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- div class='check fancyCheck'>
                                                    <input type='checkbox' name='tenMonthCustomer' id='customerSupplier' class='radio-salutation' value='1' ". (($values['tenMonthCustomer'] == '1') ? "checked='checked'" : '') .">
                                                    <label for='customerSupplier'>
                                                        <i class='fa fa-circle-o unchecked'></i>
                                                        <i class='fa fa-check-circle checked'></i>
                                                        <span class='bold-600'>".pll__('I have been a customer for more than 10 months')." <i class='question-icon custom-tooltip' data-toggle='tooltip' title='Helping text will show here'>?</i></span>
                                                    </label>
                                                </div>
                                                    
                                                <div class='block-desc'>".pll__('This is the average consumption of family 4,4500 kWh and 1700 m3 gas a year . You can change the amount if you know your exact usage.')."</div -->    
                                        
                                                <div class='buttonWrapper text-left'>
                                                    <button type='button' class='btn btn-primary'>".pll__('Ok')."</button>
                                                </div>
                                    
                                            </div>
                                        </div>
                                    </div>                                   
                                   
		                            <div id='mini_wizard_submit_btn' class='buttonWrapper'>
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

	function getHouseTypeHtml($values) {
		return "<div class='house-type'>
                <label class='single-house' data-toggle='tooltip' title='".pll__('House 1')."'>
                    <input class='call-usages-data' type='radio' name='houseType' id='single_house' value='".pll__('single')."' usage-val='".KWH_SINGLE_HOUSE."' ". (($values['houseType'] === pll__('single')) ? 'checked="checked"' : '') ."/>
                    <i class='houses'></i>
                </label>

                <label class='double-house' data-toggle='tooltip' title='".pll__('House 2')."'>
                    <input class='call-usages-data' type='radio' name='houseType' id='double_house' value='".pll__('double')."' usage-val='".KWH_DOUBLE_HOUSE."' ". (($values['houseType'] === pll__('double')) ? 'checked="checked"' : '') ."/>
                    <i class='houses'></i>
                </label>

                <label class='triple-house' data-toggle='tooltip' title='".pll__('House 3')."'>
                    <input class='call-usages-data' type='radio' name='houseType' id='triple_house' value='".pll__('tripple')."' usage-val='".KWH_TRIPLE_HOUSE."' ". (($values['houseType'] === pll__('tripple')) ? 'checked="checked"' : '') ."/>
                    <i class='houses'></i>
                </label>

                <label class='tetra-house' data-toggle='tooltip' title='".pll__('House 4')."'>
                    <input class='call-usages-data' type='radio' name='houseType' id='tetra_house' value='".pll__('tetra')."' usage-val='".KWH_TETRA_HOUSE."' ". (($values['houseType'] === pll__('tetra')) ? 'checked="checked"' : '') ."/>
                    <i class='houses'></i>
                </label>

                <label class='tower-house' data-toggle='tooltip' title='".pll__('appartment')."'>
                    <input class='call-usages-data' type='radio' name='houseType' id='tower_house' value='".pll__('flat')."' usage-val='".KWH_FLAT."' ". (($values['houseType'] === pll__('flat')) ? 'checked="checked"' : '') ."/>
                    <i class='houses'></i>
                </label>
            </div>";
	}

	function getPBSBreakDownHTML($prd, $sec){
	    if(isset($prd->$sec)) {
	        $prdObj = $prd->$sec;
            $currPricing = $prd->$sec->pricing;
            $specs = $prd->$sec->specifications;
            $promotions = $prd->$sec->promotions;
        } else {
            $prdObj = $prd->product;
            $currPricing = $prd->pricing;
            $specs = $prd->product->specifications;
            $promotions = $prd->product->promotions;
	    }
        $yearlyPromoPriceArr = formatPriceInParts($currPricing->yearly->promo_price, 2);
        $yearlyPriceArr = formatPriceInParts($currPricing->yearly->price, 2);

        $greenOrigin = $specs->green_origin;
        if(empty($promotions)) {
            $promotions = $prd->promotions;
        }
        if ( count ($promotions) ) {
            $promoHTML = '<div class="packagePromo with-promo">
                            <ul class="list-unstyled">';
            foreach ($promotions as $promo ) {
                if(!empty($promo->texts->name)) {
                    $promoHTML.= '<li class="promo prominent redHighlight"><svg class="svg-Promo"> <use xlink:href="'.get_bloginfo('template_url').'/images/svg-sprite.svg#Promo"></use> </svg>'.$promo->texts->name.'</li>';
                }
            }
            $promoHTML.= '</ul>
                        </div>';
        }
        $html = '';
        $html.= '<li class="packOption">
                    <div class="packageDetail">
                        <!--div class="packageDesc hasOldPrice">' . intval($greenOrigin->value) . $greenOrigin->unit . ' '.$specs->tariff_type->label.'</div-->
                        <div class="packageDesc hasOldPrice">' . $prdObj->product_name . '</div>
                        <div class="packagePrice">
                            <span class="oldPrice">
                            '.$yearlyPriceArr['currency'].' '.$yearlyPriceArr['price'].'.'.$yearlyPriceArr['cents'].'
                            </span>
                            <span class="currentPrice">
                                <span class="currency">' . $yearlyPromoPriceArr['currency'] . '</span>
                                <span class="amount">' . $yearlyPromoPriceArr['price'] . '</span>
                                <span class="cents">' . $yearlyPromoPriceArr['cents'] . '</span>
                            </span>
                        </div>'.$promoHTML.'
                    </div>                                    
                </li>';
        return $html;
    }

	/**
	 * This code was written by danish in anb-search-result-energy.php and Imran moved it here,
	 * the code is going to remain same the only difference will be that it'll work based on parameters
	 * @param $firstProduct
	 * @param $secondProduct
	 *
	 * @return array|[productData, labels]
	 */
    function getCompareOverviewData($firstProduct, $secondProduct) {
	    $comparePopUpData['lowest'] = json_decode( json_encode( $firstProduct ), true);
	    $comparePopUpData['highest'] = json_decode( json_encode( $secondProduct ), true);

	    $cid = 0;
	    $logosPlaced = 0;
	    $productsData = [];
	    $labels = [];
	    foreach ($comparePopUpData as $key => $pdata){
		    if( ($pdata['product']['producttype'] == 'dualfuel_pack' || $pdata['product']['producttype'] == 'electricity') ){
			    $productsData['products'][$cid]['logo'] = $pdata['product']['supplier']['logo']['200x140']['transparent']['color'];
			    $productsData['products'][$cid]['title'] = $pdata['product']['product_name'];
			    $productsData['products'][$cid]['total_yearly'] = formatPrice($pdata['pricing']['yearly']['promo_price'], 2, '&euro; ');
			    $logosPlaced = 1;
                $pbsData = $pdata['product']['electricity']['pricing']['yearly']['price_breakdown_structure'];
                if($pdata['product']['producttype'] == 'electricity'){
                    $pbsData = $pdata['pricing']['yearly']['price_breakdown_structure'];
                }
			    $labels['electricity']['main'] = pll__('electricity');
			    $labels['electricity']['total'] = pll__('Total annual electricity costs (incl.BTW)');
                $labels['electricity']['sub_total_yearly'][$cid] = formatPrice($pdata['product']['electricity']['pricing']['yearly']['promo_price'], 2, '&euro; ');
			    if($pdata['product']['producttype'] == 'electricity'){
                    $labels['electricity']['sub_total_yearly'][$cid] = formatPrice($pdata['pricing']['yearly']['promo_price'], 2, '&euro; ');
                }
			    $sh = 0;
			    foreach($pbsData as $thisKey => $priceSection){
				    $sectionlabel = str_replace(' ','_', strip_tags($priceSection['label']));
				    if($priceSection['pbs_total']) {
					    $labels['electricity']['data'][$sectionlabel]['total'][$cid] = formatPrice($priceSection['pbs_total']['value'], 2, $priceSection['pbs_total']['unit'].' ');
				    }
				    $labels['electricity']['data'][$sectionlabel]['label'] = $priceSection['label'];
				    $hh = 0;
				    foreach ($priceSection['pbs_lines'] as $pbkey => $pbdata){
					    $label_key = str_replace(' ','_', strip_tags($pbdata['label']));
					    $labels['electricity']['data'][$sectionlabel]['data'][$label_key] = $pbdata['label'];
					    $labels['electricity']['data'][$sectionlabel]['products'][$cid][$hh]['label'] = $pbdata['label'];
					    $labels['electricity']['data'][$sectionlabel]['products'][$cid][$hh]['multiplicand'] = $pbdata['multiplicand']['value'];
					    $labels['electricity']['data'][$sectionlabel]['products'][$cid][$hh]['multiplier'] = $pbdata['multiplier']['value'].' '.$pbdata['multiplier']['unit'];
					    $labels['electricity']['data'][$sectionlabel]['products'][$cid][$hh]['product'] = $pbdata['product']['value'].' '.$pbdata['product']['unit'];
					    $hh++;
				    }
				    $sh++;
			    }
		    }

		    if( ($pdata['product']['producttype'] == 'dualfuel_pack' || $pdata['product']['producttype'] == 'gas') ){
                $pbsData = $pdata['product']['gas']['pricing']['yearly']['price_breakdown_structure'];
		        if($pdata['product']['producttype'] == 'gas'){
                    $pbsData = $pdata['pricing']['yearly']['price_breakdown_structure'];
                }
			    $labels['gas']['main'] = pll__('gas');
			    $labels['gas']['total'] = pll__('Total annual gas costs (incl.BTW)');
			    $labels['gas']['sub_total_yearly'][$cid] = formatPrice($pdata['product']['gas']['pricing']['yearly']['promo_price'], 2, '&euro; ');
                if($pdata['product']['producttype'] == 'gas'){
                    $labels['gas']['sub_total_yearly'][$cid] = formatPrice($pdata['pricing']['yearly']['promo_price'], 2, '&euro; ');
                }
			    if($logosPlaced == 0) {
				    $productsData['products'][$cid]['logo'] = $pdata['product']['supplier']['logo']['200x140']['transparent']['color'];
				    $productsData['products'][$cid]['title'] = $pdata['product']['product_name'];
				    $productsData['products'][$cid]['total_yearly'] = formatPrice($pdata['pricing']['yearly']['promo_price'], 2, '&euro; ');
			    }

			    $sh = 0;
			    $logosPlaced = 1;
			    foreach($pbsData as $thisKey => $priceSection){
				    $sectionlabel = str_replace(' ','_', strip_tags($priceSection['label']));
				    if($priceSection['pbs_total']) {
					    $labels['gas']['data'][$sectionlabel]['total'][$cid] = formatPrice($priceSection['pbs_total']['value'], 2, $priceSection['pbs_total']['unit'].' ');
				    }
				    $labels['gas']['data'][$sectionlabel]['label'] = $priceSection['label'];
				    $hh = 0;
				    foreach ($priceSection['pbs_lines'] as $pbkey => $pbdata){
					    $label_key = str_replace(' ','_', strip_tags($pbdata['label']));
					    $labels['gas']['data'][$sectionlabel]['data'][$label_key] = $pbdata['label'];
					    $labels['gas']['data'][$sectionlabel]['products'][$cid][$hh]['label'] = $pbdata['label'];
					    $labels['gas']['data'][$sectionlabel]['products'][$cid][$hh]['multiplicand'] = $pbdata['multiplicand']['value'];
					    $labels['gas']['data'][$sectionlabel]['products'][$cid][$hh]['multiplier'] = $pbdata['multiplier']['value'].' '.$pbdata['multiplier']['unit'];
					    $labels['gas']['data'][$sectionlabel]['products'][$cid][$hh]['product'] = $pbdata['product']['value'].' '.$pbdata['product']['unit'];
					    $hh++;
				    }
				    $sh++;
			    }
		    }
            $labels['totalfinal']['main'] = pll__('total electricity and gas');
		    if($_GET['cat'] == 'gas' || $_GET['cat'] == 'electricity'){
                $labels['totalfinal']['main'] = pll__('total '.$_GET['cat']);
            }
		    $labels['totalfinal']['total'] = pll__('Total annualcosts (incl.BTW)');
		    $labels['totalfinal']['data']['costpermonth']['label'] = pll__('Cost/month (incl.BTW)');
		    $labels['totalfinal']['data']['advoneyear']['label'] = pll__('Total advantage 1st year');
		    $labels['totalfinal']['data']['costpermonth']['total'][$cid] = formatPrice($pdata['pricing']['monthly']['promo_price'], 2, '&euro; ');
		    $advOneYearTotal = ( $pdata['pricing']['yearly']['advantage'] > 0 ) ? formatPrice($pdata['pricing']['yearly']['advantage'], 2, '&euro; ') : '&nbsp;';
            $estimatedSavingTotal = ( $pdata['savings']['yearly']['promo_price'] > 0 ) ? formatPrice($pdata['savings']['yearly']['promo_price'], 2, '&euro; ') : '&nbsp;';
		    $labels['totalfinal']['data']['advoneyear']['total'][$cid] = $advOneYearTotal;
		    $labels['totalfinal']['sub_total_yearly'][$cid] = formatPrice($pdata['pricing']['yearly']['promo_price'], 2, '&euro; ');

		    $labels['vetsavings']['main'] = pll__('Estimated savings');
		    $labels['vetsavings']['estotal'][$cid] = $estimatedSavingTotal;
		    $cid++;
	    }

	    return [$productsData, $labels];
    }

    function compareBetweenResults($listProduct) {
        /*echo '<pre>';
        print_r($current);
        echo '</pre>';*/
        /** @var \AnbTopDeals\AnbProductEnergy $anbTopDeals */
        $anbTopDeals = wpal_create_instance( \AnbTopDeals\AnbProductEnergy::class );
        $countProducts = 0;
        $product     = ($listProduct->product) ?: $listProduct;
        $pricing     = ($listProduct->pricing) ?: '';
        $productData = $anbTopDeals->prepareProductData( $product );
        $productId   = $product->product_id;
        $supplierId  = $product->supplier_id;
        $productType = $product->producttype;
        $endScriptTime = getEndTime();
        displayCallTime($startScriptTime, $endScriptTime, "Total page load time for Results page invidual gridView till prepareProductData.");

	    $parentSegment = getSectorOnCats($_SESSION['product']['cat']);

        list(, , , , $toCartLinkHtml, $checkoutPageLink) = $anbTopDeals->getToCartAnchorHtml($parentSegment, $productData['product_id'], $productData['supplier_id'], $productData['sg'], $productData['producttype'], $forceCheckAvailability);

        $blockLinkClass = 'block-link';
        if($forceCheckAvailability) {
            $blockLinkClass = 'block-link missing-zip';
        }
        $toCartLinkHtml = '<a '.$toCartLinkHtml.' class="link '.$blockLinkClass.'">' . pll__( 'Order Now' ) . '</a>';

        if($productData['commission'] === false) {
            $toCartLinkHtml = '<a href="#not-available" class="link block-link not-available">' . pll__('Not Available') . '</a>';
        }
        $servicesHtml = $anbTopDeals->getServicesHtml( $product, $pricing );

        //TODO provide the following code with required objects, and it will then work
        $advHtml = '';
        if($pricing) {
            $yearAdv = $pricing->yearly->price - $pricing->yearly->promo_price;
            if ( $yearAdv !== 0 ) {
                $yearAdvArr  = formatPriceInParts( $yearAdv, 2 );
                $monthlyAdv  = $pricing->monthly->price - $pricing->monthly->promo_price;
                $monthAdvArr = formatPriceInParts( $monthlyAdv, 2 );
                $advHtml     .= "<div class='price-label'>
                        <label>". pll__('Your advantage') ."</label>
                        <div class='price yearly'>
                            " . $yearAdvArr['currency'] . " " . $yearAdvArr['price'] . "
                            <small>," . $yearAdvArr['cents'] . "</small>
                        </div>
                        <div class='price monthly hide'>
                            " . $monthAdvArr['currency'] . ' ' . $monthAdvArr['price'] . "
                            <small>," . $monthAdvArr['cents'] . "</small>
                        </div>
                     </div>";
            }
        }

        $html = "<div class='current-package' >
                <div class='selection'>
                    <h4>" . pll__( 'Your Current Energy Pack' ) . "</h4>
                    <a href='#' class='edit' data-toggle='modal' data-target='#selectCurrentPack'><i class='fa fa-chevron-right'></i>" . pll__( 'change pack' ) . "</a>
                    <a href='#' class='close'><span>×</span></a>
                </div>
                <div class='result-box-container' id='listgridview_3'>
                    <div class='result-box'>
                        <div class='top-label'>
                            " . $anbTopDeals->getBadgeSection( '' ) . "
                        </div>
                        <div class='flex-grid'>
                            <div class='cols'>
                                " . $anbTopDeals->getProductDetailSection( $productData, '', false, '', true ) . "
                                " . $anbTopDeals->getGreenPeaceRatingWithImages( $productData ) . "
                            </div>
                            <div class='cols'>
                                <ul class='green-services'>
                                    " . $servicesHtml . "
                                </ul>
                            </div>
                            <div class='cols grid-hide'>
                                " . $anbTopDeals->getPromoSection( $product ) . "
                            </div>
                            <div class='cols'>
                                <div class='actual-price-board'>
                                    " . $anbTopDeals->getPriceHtml( $productData, $pricing, true ) . "
                                </div>
                            </div>
                            <div class='cols grid-show'></div>
                            <div class='cols'>";
                            if($productData['commission'] === false) {
                                $detailHtml = '<a href="#not-available" class="link block-link not-available">' . pll__('Not Available') . '</a>';
                            }

                            $html.= $detailHtml;
                $html.= "</div>
                        </div>
                        <div class='result-footer'>
                            <div class='pull-left grid-hide'>
                                " . decorateLatestOrderByProduct( $product->product_id ) . "
                            </div>
                            <div class='pull-right'>
                                <span class='grid-hide'>" . $anbTopDeals->getLastUpdateDate( $productData ) . "</span>
                                <div class='comparePackage'>
                                    <div class='checkbox'>
                                        <label>
                                            <input type='hidden' name='compareProductType152' value='internet'>
                                            <input type='checkbox' value='listgridview_" . $countProducts . "'> " . pll__( 'Compare' ) . "
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>";
        return $html;
    }

    function usageResultsEnergy($enableCache = true, $cacheDurationSeconds = 86400, $isAjaxCall = false){

        if(!$enableCache){ $enableCache = true; }

        if(defined('COMPARE_API_CACHE_DURATION')) {
            $cacheDurationSeconds = COMPARE_API_CACHE_DURATION;
        } else {
            $cacheDurationSeconds = 86400;
        }

        if(isset($_GET['ajax']) && $_GET['ajax'] == true){
            $isAjaxCall = true;
        }
        if(isset($_GET['cat']) && !empty($_GET['cat'])){
            $params['producttype'] =  $_GET['cat'];
        } else {
            $params['producttype'] =  'dualfuel_pack';
        }
        if(isset($_GET['sg']) && !empty($_GET['sg'])) {
            $params['segment'] = ($_GET['sg'] == 'consumer') ? '1' : '2';
        }
        if(isset($_GET['f']) && !empty($_GET['f'])) {
            $params['family_size'] = $_GET['f'];
        }
        if(isset($_GET['houseType']) && !empty($_GET['houseType'])) {
            switch ($_GET['houseType']){
                case pll__('single'): $params['residence_type'] = '4'; break;
                case pll__('double'): $params['residence_type'] = '3'; break;
                case pll__('tripple'): $params['residence_type'] = '2'; break;
                case pll__('tetra'): $params['residence_type'] = '5'; break;
                case pll__('flat'): $params['residence_type'] = '1'; break;
            }
        }
        if(isset($_GET['home_size']) && !empty($_GET['home_size'])) {
            $params['home_size'] = $_GET['home_size'];
        }
        if(!isset($_GET['meter']) || empty($_GET['meter'])) { $_GET['meter'] = 'single'; }
        if(isset($_GET['meter']) && !empty($_GET['meter'])) {
            if($_GET['meter'] == 'single'){
                $params['meter_type'] = '1';
                if(isset($_GET['exc_night_meter'])){
                    $params['meter_type'] = '3';
                }
            } else if($_GET['meter'] == 'double'){
                $params['meter_type'] = '2';
                if(isset($_GET['exc_night_meter'])){
                    $params['meter_type'] = '4';
                }
            }
        }
        if(isset($_GET['has_solar']) && !empty($_GET['has_solar'])) {
            $params['has_solar'] = '1';
        }
        if(isset($_GET['roof_isolation']) && !empty($_GET['roof_isolation'])) {
            $params['roof_isolation'] = $_GET['roof_isolation'];
        }
        if(isset($_GET['wall_isolation']) && !empty($_GET['wall_isolation'])) {
            $params['wall_isolation'] = $_GET['wall_isolation'];
        }
        if(isset($_GET['glass']) && !empty($_GET['glass'])) {
            $params['glass'] = $_GET['glass'];
        }
        if(isset($_GET['boiler']) && !empty($_GET['boiler']) && $_GET['boiler'] != '0') {
            $params['boiler'] = $_GET['boiler'];
        }
        if(isset($params['cv']) && !empty($_GET['cv']) && $_GET['cv'] != '0') {
            $params['cv'] = $_GET['cv'];
        }

        $atts = shortcode_atts(array(
            'producttype' => '',
            'segment' => '',
            'family_size' => '',
            'residence_type' => '',
            'home_size' => '',
            'meter_type' => '',
            'has_solar' => '',
            'roof_isolation' => '',
            'wall_isolation' => '',
            'glass' => '',
            'boiler' => '',
            'cv' => ''
        ), $atts, 'anb_search_usage');
        $params = array_filter($params);

        $this->cleanArrayData($params);
        $params = $this->allowedParams($params, array_keys($atts));//Don't allow all variables to be passed to API
        displayParams($params);
        $start = getStartTime();
        $displayText = "Time API (Compare) inside getCompareResults";

        if ($enableCache && !isset($_GET['no_cache'])) {
            $cacheKey = md5(serialize($params)) . ":usage_vals";
            $result = mycache_get($cacheKey);

            if($result === false || empty($result)) {
                $result = $this->anbApi->getUsageResults($params);
                mycache_set($cacheKey, $result, $cacheDurationSeconds);
            } else {
                $displayText = "Time API Cached (Compare) inside usage in wizard";
            }
        } else {
            $result = $this->anbApi->getUsageResults($params);
        }
        $finish = getEndTime();
        displayCallTime($start, $finish, $displayText);
        if($isAjaxCall){
            echo $result;
            wp_die();
        } else {
            return $result;
        }

    }
}