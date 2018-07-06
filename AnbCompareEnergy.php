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
    }


    /**
     * enqueue ajax scripts
     */
    function enqueueScripts()
    {

        parent::enqueueScripts();
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

                                            <input type='radio' id='person6' name='f' value='6'>
                                            <label class='full' for='person6' title='6 ".pll__('persons')."'>
                                                <span class='person-value'>5+</span>
                                            </label>

                                            <input type='radio' id='person5' name='f' value='5'>
                                            <label class='full' for='person5' title='5 ".pll__('persons')."'></label>

                                            <input type='radio' id='person4' name='f' value='4'>
                                            <label class='full' for='person4' title='4 ".pll__('persons')."'></label>


                                            <input type='radio' id='person3' name='f' value='3'>
                                            <label class='full' for='person3' title='3 ".pll__('persons')."'></label>


                                            <input type='radio' id='person2' name='f' value='2'>
                                            <label class='full' for='person2' title='2 ".pll__('persons')."'></label>


                                            <input type='radio' id='person1' name='f' value='1'>
                                            <label class='full' for='person1' title='1 ".pll__('persons')."'></label>
                                            <div class='clearfix'></div>
                                        </fieldset>
                                        <div class='double-meter-fields'>
                                            <div class='field general-energy'>
                                                <i></i>
                                                <input type='text' name='du'/>
                                                <label>kwh</label>
												<span class='question-circle custom-tooltip' data-toggle='tooltip' title='".pll__('Informational text for electricty')."'></span>
                                            </div>
                                            <div class='field day-night-energy hide'>
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
            $productResp .= '<a href="#" class="link block-link all-caps">Detail</a>';
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

}

