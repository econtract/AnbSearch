<?php
/**
 * Created by PhpStorm.
 * User: imran
 * Date: 5/12/17
 * Time: 6:02 PM
 */

namespace AnbSearch;

use GuzzleHttp\Client;


class AnbToolbox
{

    public $apiConf = [
        'url' => TOOLKIT_API_URL,
        'key' => TOOLKIT_API_KEY,
    ];

    public $httpClient;

    function __construct()
    {
        $this->httpClient = new Client(['base_uri' => $this->apiConf['url']]);
    }

    /**
     * @param string $queryMethod like cities, streets
     * @param array $queryParams like ['postcode' => 3500, 'name' => Zand]
     * @param boolean $returnAllRows default true, when true will return first row
     * @return mixed
     */
    function queryApi($queryMethod, $queryParams = [], $returnAllRows = true) {

	    $res = $this->httpClient->request( 'GET', $queryMethod, [
		    'query' => [ 'toolbox_key' => $this->apiConf['key'] ] + $queryParams,
		    /*'debug' => true*/
	    ] );

        $jsonRes = json_decode($res->getBody()->getContents());
        if(!$returnAllRows) {
            return $jsonRes[0];
        }

        return $jsonRes;
    }

    /**
     * @param int|string $zip
     * @param bool       $cache
     * @return string|null
     */
    function getCityOnZip($zip, $cache = true)
    {
        if ($cache === true && function_exists('cache_remember')) {
            $response = cache_remember('anb_toolbox.city.' . $zip, function () use ($zip) {
                return $this->queryApi('cities', ['postcode' => $zip], false);
            });
        } else {
            $response = $this->queryApi('cities', ['postcode' => $zip], false);
        }

        return $response->name;
    }

    function getAdressInfo($zip, $city = "", $street = "", $house = "", $extraParams = []) {
    	$params = [
    		'postcode' => intval($zip),
		    'city' => $city,
		    'street' => $street,
		    'house_number' => $house
	    ] + $extraParams;

    	//remove all empty params
	    $params = array_filter($params);

	    return $this->queryApi('addresses', $params, false);
    }

    function getConnectionInfoOnAdressId($addressId, $extraParams = []) {
	    return $this->queryApi('connections', ['address_id' => $addressId] + $extraParams, false);
    }

	/**
	 * Ajax method
	 */
	public function ajaxQueryToolboxApi() {
		$res = null;
		if(!empty($_GET['query_method']) && !empty($_GET['query_params'])) {
			/** @var \AnbSearch\AnbToolbox $anbToolbox */
			$res = json_encode( $this->queryApi( sanitize_text_field( $_GET['query_method'] ), $_GET['query_params'], true ) );
		}

		echo $res;
		wp_die();
	}
}
