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
