<?php
/**
 * Created by PhpStorm.
 * User: arslan
 * Date: 07/08/17
 * Time: 12:49
 */

namespace AnbSearch;

use AnbApiClient\Aanbieders;
use AnbTopDeals\AnbProduct;

class Base
{
    /**
     * @var string
     */
    public $crmApiEndpoint = "http://api.econtract.be/";//Better to take it from Admin settings

    /**
     * @var mixed
     */
    public $anbApi;

    /**
     * @var array
     */
    public $apiConf = [
        'staging'  => ANB_API_STAGING,
        'key'      => ANB_API_KEY,
        'secret'   => ANB_API_SECRET
    ];

    /**
     * @var mixed
     */
    public $anbTopDeals;

    /**
     * @var int
     */
    public $defaultNumberOfResults = 4;

    /**
     * AnbCompare constructor.
     */
    public function __construct()
    {
        $this->anbApi = wpal_create_instance(Aanbieders::class, [$this->apiConf]);

        $this->anbTopDeals = wpal_create_instance(AnbProduct::class);
    }

    /**
     * @var array
     */
    public $productTypes = [
        'internet',
        'packs'
    ];

    /**
     * @var array
     */
    public $productStatus = [
        '1', // 1=active
       // '2'  // 2=archived
    ];

    /**
     * @return bool|string
     */
    public function getCurrentLang()
    {
        $lang = 'nl';
        if (method_exists('pll_current_language')) {
            $lang = (pll_current_language()) ? pll_current_language() : 'nl';
        }
        return $lang;
    }

}