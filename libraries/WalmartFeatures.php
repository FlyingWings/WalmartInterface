<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 18-5-3
 * Time: 上午11:00
 */

namespace libraries;

use GuzzleHttp\Client;
use libraries\Item\WalmartItem;

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
    public static function getAllFeedStatus($seller, $offset=0, $limit = 5){
        $url = self::$api_url;
        $params = ["offset"=>$offset, "limit"=>$limit];
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
     * Get an Item with detail
     * @param $seller
     * @param $sku
     * @return mixed
     */
    public static function getAnItem($seller, $sku){
        $url = self::$api_url;
        $xml = self::_call_api_get($seller, $url."/v3/items/".$sku);
        $res = self::xml_to_obj($xml);
        return $res;
    }

    /**
     * Get batch items
     * @param $seller
     * @param null $limit
     * @param null $offset
     * @return mixed
     */
    public static function getBatchItems($seller, $limit= null, $offset = null){
        $url = self::$api_url;
        $params = $limit === null ? [] : ["offset"=>$offset, "limit"=>$limit];
        $xml = self::_call_api_get($seller, $url."/v3/items", $params);
        $res = self::xml_to_obj($xml);
        return $res;
    }

    /**
     * Get Inventory for an item
     * @param $seller
     * @param $item
     * @return mixed
     */
    public static function getInventoryForItem($seller,WalmartItem $item){
        $url = self::$api_url;
        $xml = self::_call_api_get($seller, $url."/v2/inventory/", ["sku"=>$item->sku]);
        $res = self::xml_to_obj($xml);
        return $res;
    }


    public static function updateInventory($seller, $item){
        $url = self::$api_url;
        $xml = self::_call_api_put($seller, $url."/v2/inventory?sku=".$item->sku, self::generateInventoryFile($seller, $item));
        $res = self::xml_to_obj($xml);
        return $res;

    }


    /**
     * Retire an item
     * @param $seller
     * @param $item
     * @return mixed
     */
    public static function retireAnItem($seller, $item){
        $url = self::$api_url;
        $xml = self::_call_api_detele($seller, $url."/v3/items/".$item->sku);
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
        }catch(\GuzzleHttp\Exception\ServerException $e){
            $response = $e->getResponse();
            $xml = $response->getBody()->getContents();
        }

        self::_log_response($logid, $xml, $url);

        return $xml;
    }

    /**
     * PUT method
     * @param $seller
     * @param $url
     * @param $params
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public static function _call_api_put($seller, $url, $params){
        $logid=self::_log_request($seller->shop_name, $seller->id, $url, []);
        $time = intval(round(microtime(true) * 1000));

        $client = new Client();
        $headers= self::generate_headers($seller, $url, $time, "PUT", ['Content-Type'=> 'application/xml']);// Directly request in body, set content-type to xml(fixed)
        try{
            $res = $client->request("PUT", $url, [
                'headers'=>$headers,
                'body'=>$params
            ]);

            $xml = $res->getBody()->getContents();

        }catch(\GuzzleHttp\Exception\ClientException $e){
            $response = $e->getResponse();
            $xml = $response->getBody()->getContents();
        }catch(\GuzzleHttp\Exception\ServerException $e){
            $response = $e->getResponse();
            $xml = $response->getBody()->getContents();
        }

        self::_log_response($logid, $xml, $url);

        return $xml;
    }

    /**
     * DELETE
     * @param $seller
     * @param $url
     * @return mixed
     */
    protected static function _call_api_detele($seller, $url)
    {
        $logid = self::_log_request($seller->get("shop_name"), $seller->id, $url, []);
        $client = new Client();

        $time = intval(round(microtime(true) * 1000));
        $headers= self::generate_headers($seller, $url, $time, "DELETE");
        try{
            $res = $client->request("DELETE", $url, [
                'headers'=>$headers
            ]);
            $xml = $res->getBody()->getContents();
        }catch(\GuzzleHttp\Exception\ServerException $e){
            $response = $e->getResponse();
            $xml = $response->getBody()->getContents();
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


    public static function generateInventoryFile($seller, $item){
        $content = <<<ITEM
<?xml version="1.0" encoding="UTF-8"?>
<inventory xmlns="http://walmart.com/">
    <sku>{$item->sku}</sku>
    <quantity>
        <unit>EACH</unit>
        <amount>{$item->qty}</amount>
    </quantity>
    <fulfillmentLagTime>{$item->lag_time}</fulfillmentLagTime>
</inventory>
ITEM;
        return $content;
    }

    public static function generateItemFeedFile($seller, $items, $process_type = "ALL"){
        $request_id = sha1(microtime());
        $time = date("Y-m-d\TH:i:s", time());
        $feedDate = date("c", time());

        $content =
            <<<FEED
<MPItemFeed xmlns:wal="http://walmart.com/">
  <MPItemFeedHeader>
    <version>3.1</version>
    
    <requestId>{$request_id}</requestId>
    
    <requestBatchId>{$request_id}</requestBatchId>
    
    <feedDate>{$time}</feedDate>
    
    <mart>WALMART_US</mart>
  </MPItemFeedHeader>

FEED;
        foreach($items as $item){
            $identifiers = ["UPC"=>$item->get("item.upc"), "GTIN"=>$item->get("item.gtin"), "ISBN"=>$item->get("item.isbn")];
            $second_images = $item->get("images");
            $process_mode = empty($item->get("fetch_timestamp")) ? "CREATE" : "REPLACE_ALL";
            $content.=
                <<<FEED
    <MPItem>
        <processMode>{$process_mode}</processMode>
        <feedDate>{$feedDate}</feedDate>
        <sku>{$item->sku}</sku>
        <productIdentifiers>

FEED;
            foreach($identifiers as $key=>$value) {
                if(!empty($value))
                    $content.=
                        <<<FEED
           <productIdentifier>
            <productIdType>{$key}</productIdType>
            <productId>{$value}</productId>
           </productIdentifier>     
FEED;
            }

            $content.=<<<FEED
        </productIdentifiers>
FEED;
            if($process_type == "ALL" || $process_type == "PRODUCT") {

                $content .= <<<FEED
        <MPProduct>
            <productName>{$item->item["productName"]}</productName>
            <ProductIdUpdate>Yes</ProductIdUpdate>
            <SkuUpdate>Yes</SkuUpdate>
            <category>
                <{$item->category["root_category_name"]}>
                    <{$item->category["leaf_category_name"]}>
                        <shortDescription>{$item->description}</shortDescription>
                        <brand>{$item->brand}</brand>
FEED;
                if ($item->get("color")) {
                    $content .= <<<FEED
                        <color>{$item->color}</color>
FEED;
                }
                if ($item->get("size")) {
                    $content .= <<<FEED
                        <size>{$item->size}</size>
FEED;
                }
                $extra = $item->get("extra");
                foreach ($extra as $k=>$v){
                    if(!is_array($v)) {
                        $content .= <<<FEED
                        <{$k}>{$v}</{$k}>
FEED;
                    }else{
                        $v_array = implode("", array_map(function($i)use($v){return "<$i>".$v[$i]."</$i>";}, array_keys($v)));

                        $content .= <<<FEED
                        <$k>{$v_array}</$k>
FEED;
                    }
                }

                if($item->get("features")){
                    $features = array_map(function($i){return "<keyFeaturesValue>{$i}</keyFeaturesValue>";}, $item->features);
                    $features = implode("",$features);
                    $content .= <<<FEED
                <keyFeatures>
                        {$features}
                </keyFeatures>
FEED;
                }
                $content .= <<<FEED
                        
                        <mainImageUrl>{$item->images[0]["url"]}</mainImageUrl>
FEED;
                if(count($second_images) > 1){
                    $content.= <<<FEED
                        <productSecondaryImageURL>
FEED;
                    for($i=1; $i< count($second_images); $i++){
                        $pic = $second_images[$i]["url"];
                        $content.= <<<FEED
                            <productSecondaryImageURLValue>{$pic}</productSecondaryImageURLValue>
FEED;
                    }
                    $content.= <<<FEED
                        </productSecondaryImageURL>
FEED;
                }

                if(in_array($item->variation_type, ["parent", "child"]) && @$item->variation_group_id ) {
                    $content .= <<<FEED
                        <variantGroupId>{$item->variation_group_id}</variantGroupId>
FEED;

                    $content .= <<<FEED
                        <variantAttributeNames>
FEED;
                    foreach ($item->variant_attribute_names as $name){
                        $content.= <<<FEED
                            <variantAttributeName>{$name}</variantAttributeName>
FEED;
                    }
                    $content.= <<<FEED
                        </variantAttributeNames>
                        <isPrimaryVariant>{$item->is_primary}</isPrimaryVariant>
FEED;
                    if(in_array("color", $item->variant_attribute_names)) {

                        $content .= <<<FEED
                        <swatchImages>
FEED;
                        foreach ($item->variant_attribute_names as $name) {

                            $content .= <<<FEED
                            <swatchImage>
                                <swatchVariantAttribute>{$name}</swatchVariantAttribute>
                                <swatchImageUrl>{$item->swatch_image}</swatchImageUrl>
                            </swatchImage>
FEED;
                        }

                        $content .= <<<FEED
                        </swatchImages>
FEED;
                    }
                }

                $content.= <<<FEED
                        <hasPricePerUnit>{$item->has_price_unit}</hasPricePerUnit>
                    </{$item->category["leaf_category_name"]}>
                </{$item->category["root_category_name"]}>
            </category>
        </MPProduct>
FEED;
            }
            if($process_type == "ALL" || $process_type == "PRICE") {
                $content .= <<<FEED
        <MPOffer>
            <price>{$item->item["price"]["amount"]}</price>
            <StartDate>{$item->sales_date["startDate"]}</StartDate>
            <EndDate>{$item->sales_date["endDate"]}</EndDate>
            <ShippingWeight>
                <measure>{$item->shipping_weight["measure"]}</measure>
                <unit>{$item->shipping_weight["unit"]}</unit>
            </ShippingWeight>
            <ProductTaxCode>{$item->tax_code}</ProductTaxCode>
        </MPOffer>
FEED;
            }
            $content .=<<<FEED
    </MPItem>
FEED;


        }
        $content.= <<<FEED
</MPItemFeed>
FEED;

//        dd($content);
        return $content;
    }
}