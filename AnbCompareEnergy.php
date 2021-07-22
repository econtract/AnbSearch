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

    /** @var int */
    public $defaultNumberOfResults = 10;

    /**
     * enqueue ajax scripts
     */
    function enqueueScripts()
    {
        if($this->sector == pll__('energy')) {
            if($this->pagename == pll__('results') || $this->pagename == 'energenie' ) {
                wp_enqueue_script('compare-results-energy', plugins_url('/js/compare-results-energy.js', __FILE__), array('jquery'), '1.2.7', true);
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
                        'trans_loading_dots'    => pll__('Loading...'),
                        'trans_idontknow' => pll__('I dont know the contract'),
                        'trans_customerrating' => pll__('Customer Score'),
                        'trans_guarantee1year' => pll__('guaranteed 1st year'),
                        'trans_guarantee1month' => pll__('guaranteed 1st month'),
                        'trans_guarantee1yearinfo' => pll__('guaranteed 1st year info text'),
                        'trans_guarantee1monthinfo' => pll__('guaranteed 1st month info text'),
                        'trans_potentialsaving' => pll__('Potential saving'),
                        'trans_youradvantage' => pll__('Your advantage')
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

    /**
     * @param object $prd
     * @param string $sec
     * @return string   Generated html
     */
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

        if ($sec && isset($prd->$sec)) {
            $productType = $sec;
        } else {
            $productType = $prdObj->producttype;
        }
        $html = '';
        $html.= '<li class="packOption">
                    <div class="packageDetail">
                        <div class="packageDesc">' . sprintf('%s - %s', ucfirst(pll__($productType)), strtolower($prdObj->specifications->tariff_type->label)) . '</div>
                        <div class="packagePrice">
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
     * @return array
     */
    function getCompareOverviewData($firstProduct, $secondProduct)
    {
        $products = [
            json_decode(json_encode($firstProduct), true),
            json_decode(json_encode($secondProduct), true),
        ];

        $compareData = [
            'products' => [],
            'pbs'      => [],
            'costs'    => [],
            'savings'  => [],
        ];

        foreach ($products as $productIndex => $product) {
            $productType                            = $product['product']['producttype'];
            $compareData['products'][$productIndex] = [
                'id'           => $product['product']['product_id'],
                'logo'         => $product['product']['supplier']['logo']['200x140']['transparent']['color'],
                'title'        => $product['product']['product_name'],
                'total_yearly' => formatPrice($product['pricing']['yearly']['promo_price'], 2, '&euro; '),
            ];

            if (in_array($productType, ['dualfuel_pack', 'electricity'])) {
                $compareData['pbs']['electricity']['main'] = pll__('electricity');
                $compareData['pbs']['electricity']['total'] = pll__('Total yearly electricity costs');

                if ($productType === 'electricity') {
                    $compareData['pbs']['electricity']['sub_total_yearly'][$productIndex] = formatPrice($product['pricing']['yearly']['promo_price'], 2, '&euro; ');
                    $pbsData                                                              = $product['pricing']['yearly']['price_breakdown_structure'];
                } else {
                    $compareData['pbs']['electricity']['sub_total_yearly'][$productIndex] = formatPrice($product['product']['electricity']['pricing']['yearly']['promo_price'], 2, '&euro; ');
                    $pbsData                                                              = $product['product']['electricity']['pricing']['yearly']['price_breakdown_structure'];
                }

                foreach ($pbsData as $pbsKey => $priceSection) {
                    $compareData['pbs']['electricity']['data'][$pbsKey]['label'] = $priceSection['label'];
                    if (isset($priceSection['pbs_total'])) {
                        $compareData['pbs']['electricity']['data'][$pbsKey]['total'][$productIndex] = formatPrice($priceSection['pbs_total']['value'], 2, $priceSection['pbs_total']['unit'] . ' ');
                    }
                    foreach ($priceSection['pbs_lines'] as $lineIndex => $pbsLine) {
                        $compareData['pbs']['electricity']['data'][$pbsKey]['lines'][$lineIndex]['label']                   = $pbsLine['label'];
                        $compareData['pbs']['electricity']['data'][$pbsKey]['lines'][$lineIndex]['products'][$productIndex] = [
                            'label'             => $pbsLine['label'],
                            'multiplicand'      => $pbsLine['multiplicand'],
                            'multiplier'        => $pbsLine['multiplier'],
                            'product'           => $pbsLine['product'],
                        ];
                    }
                }
            }

            if (in_array($productType, ['dualfuel_pack', 'gas'])) {
                $compareData['pbs']['gas']['main']  = pll__('gas');
                $compareData['pbs']['gas']['total'] = pll__('Total annual gas costs');

                if ($productType === 'gas') {
                    $compareData['pbs']['gas']['sub_total_yearly'][$productIndex] = formatPrice($product['pricing']['yearly']['promo_price'], 2, '&euro; ');
                    $pbsData                                                      = $product['pricing']['yearly']['price_breakdown_structure'];
                } else {
                    $compareData['pbs']['gas']['sub_total_yearly'][$productIndex] = formatPrice($product['product']['gas']['pricing']['yearly']['promo_price'], 2, '&euro; ');
                    $pbsData                                                      = $product['product']['gas']['pricing']['yearly']['price_breakdown_structure'];
                }

                foreach ($pbsData as $pbsKey => $priceSection) {
                    $compareData['pbs']['gas']['data'][$pbsKey]['label'] = $priceSection['label'];
                    if (isset($priceSection['pbs_total'])) {
                        $compareData['pbs']['gas']['data'][$pbsKey]['total'][$productIndex] = formatPrice($priceSection['pbs_total']['value'], 2, $priceSection['pbs_total']['unit'] . ' ');
                    }
                    foreach ($priceSection['pbs_lines'] as $lineIndex => $pbsLine) {
                        $compareData['pbs']['gas']['data'][$pbsKey]['lines'][$lineIndex]['label']                   = $pbsLine['label'];
                        $compareData['pbs']['gas']['data'][$pbsKey]['lines'][$lineIndex]['products'][$productIndex] = [
                            'label'             => $pbsLine['label'],
                            'multiplicand'      => $pbsLine['multiplicand'],
                            'multiplier'        => $pbsLine['multiplier'],
                            'product'           => $pbsLine['product'],
                        ];
                    }
                }
            }
            if ($productType === 'dualfuel_pack') {
                $compareData['costs']['main'] = pll__('Total dualfuel pack');
            } else {
                $compareData['costs']['main'] = pll__('Total ' . $productType);
            }
            $compareData['costs']['total']['label']            = pll__('Total yearly costs');
            $compareData['costs']['costpermonth']['label']     = pll__('Estimated monthly deposit');
            $compareData['costs']['yearlynodiscount']['label'] = pll__('Total yearly costs without discount');
            $compareData['costs']['advoneyear']['label']       = pll__('Total advantage 1st year');

            if ($secondProduct->product->segment !== 'consumer') {
                $compareData['costs']['total']['label']            .= ' ' . pll__('(excl. VAT)');
                $compareData['costs']['costpermonth']['label']     .= ' ' . pll__('(excl. VAT)');
                $compareData['costs']['advoneyear']['label']       .= ' ' . pll__('(excl. VAT)');
            } else {
                $compareData['costs']['total']['label']            .= ' ' . pll__('(incl. VAT)');
                $compareData['costs']['costpermonth']['label']     .= ' ' . pll__('(incl. VAT)');
                $compareData['costs']['advoneyear']['label']       .= ' ' . pll__('(incl. VAT)');
            }

            $compareData['costs']['costpermonth']['products'][$productIndex]     = formatPrice($product['pricing']['monthly']['promo_price'], 2, '&euro; ');
            $compareData['costs']['yearlynodiscount']['products'][$productIndex] = formatPrice($product['pricing']['yearly']['price'], 2, '&euro; ');
            $advOneYearTotal                                                     = $product['pricing']['yearly']['advantage'] > 0 ? formatPrice($product['pricing']['yearly']['advantage'], 2, '&euro; ') : null;
            $estimatedSavingTotal                                                = isset($product['savings']['yearly']['promo_price']) && $product['savings']['yearly']['promo_price'] > 0 ? formatPrice($product['savings']['yearly']['promo_price'], 2, '&euro; ') : null;
            $compareData['costs']['advoneyear']['products'][$productIndex]       = $advOneYearTotal;
            $compareData['costs']['total']['products'][$productIndex] = formatPrice($product['pricing']['yearly']['promo_price'], 2, '&euro; ');

            $compareData['savings']['main']                    = pll__('Estimated Savings');
            $compareData['savings']['products'][$productIndex] = $estimatedSavingTotal;
        }

        return $compareData;
    }
}
