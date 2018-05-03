<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 18-5-3
 * Time: 上午11:00
 */

namespace libraries;

use GuzzleHttp\Client;
class WalmartFeatures {
    protected static $api_url = "https://marketplace.walmartapis.com";
    protected static $api_test_url = "https://developer.walmart.com/proxy/item-api-doc-app/rest";
    protected static $_logging = true;
    protected static $_mode = "DEBUG";


    /**
     * GET all Feeds
     * @param $seller
     * @return mixed
     */
    public static function getAllFeedStatus($seller){
        $url = self::$api_url;
        $params = ["offset"=>0, "limit"=>5];
        $xml = self::_call_api_get($seller, $url."/v3/feeds", $params);
        $res = self::xml_to_obj($xml);
        return $res;
    }


    /**
     * GET items' detail for a single feed
     * @param $seller
     * @param $feedId
     * @param string $includeDetails
     * @param null $offset
     * @param null $limit
     * @return mixed
     */
    public static function getFeedItemsStatus($seller, $feedId, $includeDetails = "true", $offset=null, $limit= null){
        $url = self::$api_url;
        $params = $offset === null ? ["includeDetails"=>$includeDetails] : ["includeDetails"=>$includeDetails, "offset"=>$offset, "limit"=>$limit];
        $xml = self::_call_api_get($seller, $url."/v3/feeds/".$feedId, $params);
        $res = self::xml_to_obj($xml);
        return $res;
    }




    /**
     * GET method in Walmart API requires encoding params and appending them in the URL
     * Using guzzle to request
     * @param $seller
     * @param $url
     * @param array $params
     * @return mixed
     */
    protected static function _call_api_get($seller, $url, $params=array())
    {

        $escaped_params=array();
        foreach($params as $k=>$v)
        {
            $k=rawurlencode($k);
            $v=rawurlencode($v);
            $escaped_params[]="$k=$v";
        }
        $param_string=implode('&', $escaped_params);

        $final_url= empty($params)? $url :$url.'?'.$param_string;

        $time = intval(round(microtime(true) * 1000));
        // Log Requests
        $logid=self::_log_request($seller->shop_name, $seller->id, $url, $params);

        $client = new Client();
        $headers= self::generate_headers($seller, $final_url, $time, "GET");


        try {
            $xml=$client->request("GET", $final_url, [
                'headers'=>$headers
            ]);
            $xml = $xml->getBody()->getContents();

        }catch(\GuzzleHttp\Exception\ClientException $e){
            $response = $e->getResponse();
            $xml = $response->getBody()->getContents();
        }

        self::_log_response($logid, $xml, $url);

        return $xml;
    }


    private static function generate_headers(WalmartAccount $seller, $url, $time, $method, $other_header=[]){
        $signature = $seller->get_signature($url, $method, $time);
        if($signature === null){
            throw new Exception("Signature illegal");
            return [];
        }
        return array_merge([
            'WM_CONSUMER.ID'=>$seller->customer_id,
            'WM_SEC.TIMESTAMP'=>$time,
            'WM_SEC.AUTH_SIGNATURE'=>$signature,
            'WM_SVC.NAME'=>'Walmart',
            'WM_QOS.CORRELATION_ID'=>sha1(microtime()),
            'WM_CONSUMER.CHANNEL.TYPE'=> $seller->channel_type,
        ], $other_header);
    }



    /**
     * Example of logging request, it's better to use DB to store and retrieve logs
     * @param $market
     * @param $account
     * @param $api
     * @param $request
     * @return Int
     */
    static function _log_request($market, $account, $api, $request){
        if(!self::$_logging) return true;
        $log_position = ROOT."/log/api.log";
        $id = 0;
        // Log request parameters and fetch log id

        return $id;
    }

    /**
     * Retrieve log and set response part of it
     * @param $id
     * @param $response
     * @param null $api
     * @param null $request
     * @return bool
     */
    static function _log_response($id, $response, $api=null, $request=null){
        if(!self::$_logging) return true;

        // Retrieve log via id
        $log= [];//
        if($log){
            $log['response']= $response;
            $log['response_time']= time();
            //Save Log
            return true;

        }
        return false;
    }


    /**
     * Transfer XML file to object in PHP
     * @param $xml
     * @return mixed
     */
    public static function xml_to_obj($xml){
        $res = str_replace("ns2:", "", $xml);
        $res = str_replace("ns3:", "", $res);
        $res = str_replace("ns4:", "", $res);
        return json_decode(json_encode(simplexml_load_string($res), 1));
    }
}