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
     * @param boolean $returnAllRows default true
     * @return mixed
     */
    function queryApi($queryMethod, $queryParams = [], $returnAllRows = true) {
        $res = $this->httpClient->request('GET', $queryMethod, [
            'query' => array_merge(
                ['toolbox_key' => $this->apiConf['key']],
                $queryParams
            )
        ]);

        $jsonRes = json_decode($res->getBody()->getContents());
        if(!$returnAllRows) {
            return $jsonRes[0];
        }

        return $jsonRes;
    }

    function getCityOnZip($zip) {
        $res = $this->queryApi('cities', ['postcode' => $zip], false);
        return $res->name;
    }
}
