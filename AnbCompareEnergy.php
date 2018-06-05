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
    const RESULTS_PAGE_URI = "/energy/results/";

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

	    $formNew = $this->getSearchBoxContentHtml($values, $needHelpHtml, $supplierHtml, pll__("Compare Energy Prices"), false, "", pll__('results'));

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
                                            <input type='radio' name='producttype' id='service_dual_fuel' value='dualfuel_pack' checked='checked'>
                                            <label for='service_dual_fuel' class='service-dual-fuel'>
                                                <i></i>
                                                <span class='service-label'>".pll__('Dual Fuel')."</span>
                                                <span class='check-box'></span>
                                            </label>
                                        </li>
                                        <li>
                                            <input type='radio' name='producttype' id='service_electricity' value='electricity'>
                                            <label for='service_electricity' class='service-electricity'>
                                                <i></i>
                                                <span class='service-label'>".pll__('Electricity')."</span>
                                                <span class='check-box'></span>
                                            </label>
                                        </li>
                                        <li>
                                            <input type='radio' name='producttype' id='service_gas' value='gas'>
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
                                            </div>
                                            <div class='field day-night-energy hide'>
                                                <div class='day-energy'>
                                                    <i></i>
                                                    <input type='text' name='du'/>
                                                    <label>kwh</label>
                                                </div>
                                                <div class='night-energy'>
                                                    <i></i>
                                                    <input type='text' name='nou'/>
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
                                                <input type='radio' name='houseType' id='single_house' value='single'/>
                                                <i class='houses'></i>
                                            </label>

                                            <label class='double-house' data-toggle='tooltip' title='House 2'>
                                                <input type='radio' name='houseType' id='double_house' value='double'/>
                                                <i class='houses'></i>
                                            </label>

                                            <label class='triple-house' data-toggle='tooltip' title='House 3'>
                                                <input type='radio' name='houseType' id='triple_house' value='tripple'/>
                                                <i class='houses'></i>
                                            </label>

                                            <label class='tetra-house' data-toggle='tooltip' title='House 4'>
                                                <input type='radio' name='houseType' id='tetra_house' value='tetra'/>
                                                <i class='houses'></i>
                                            </label>

                                            <label class='tower-house' data-toggle='tooltip' title='".pll__('appartment')."'>
                                                <input type='radio' name='houseType' id='tower_house' value='flat'/>
                                                <i class='houses'></i>
                                            </label>
                                        </div>
                                        <div class='field'>
                                            <i></i>
                                            <input type='text' name='ut' value=''/>
                                            <label>m3</label>
                                        </div>
                                    </div>
                                </div>
                                <div class='form-group'>
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
}