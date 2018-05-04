<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 18-5-3
 * Time: 下午6:05
 */


include_once(dirname(__DIR__)."/base.php");

use PHPUnit\Framework\TestCase;

class WalmartFeaturesGetTest extends TestCase{
    public function testAccountExists(){
        global $config_array;
        $this->assertNotEmpty($config_array);
        $wal_account = new libraries\WalmartAccount($config_array);
        $this->assertObjectHasAttribute("shop_name", $wal_account);
        return $wal_account;
    }


    /**
     * Test Getting all feeds
     * @depends testAccountExists
     */
    public function testGetAllFeedStatus(\libraries\WalmartAccount $account){

        $res = libraries\WalmartFeatures::getAllFeedStatus($account);
        $this->assertNotNull($res);
        $this->assertObjectHasAttribute("results", $res);
    }


    /**
     * Test Getting detail of items in a single feed
     * @depends testAccountExists
     *
     */
    public function testGetFeedItemsStatus(\libraries\WalmartAccount $account){
        $feedId = "tafdsfasf";// A random-assigned feed id
        $res = \libraries\WalmartFeatures::getFeedItemsStatus($account, $feedId);
        $this->assertObjectHasAttribute("error", $res);// If having it, means meeting an error
        $feedId = '41B86C7F95AD40C98E9423912A8DBDA4@AQMBAQA';// An Real Feed id
        $res = \libraries\WalmartFeatures::getFeedItemsStatus($account, $feedId);
        $this->assertObjectNotHasAttribute("error", $res);// If having it, means meeting an error
        $this->assertObjectHasAttribute("feedStatus", $res);
    }


    /**
     * Test Getting an item
     * @depends testAccountExists
     */
    public function testGetAnItem(\libraries\WalmartAccount $account){
        $sku = "Random-Assign-1";// Illegal SKU
        $res = \libraries\WalmartFeatures::getAnItem($account, $sku);
        $this->assertObjectHasAttribute("error", $res);
        $sku = "MAN-AB-120x60";// legal SKU
        $res = \libraries\WalmartFeatures::getAnItem($account, $sku);
        $this->assertObjectHasAttribute("ItemResponse", $res);
        $this->assertObjectNotHasAttribute("error", $res);
        $item = new \libraries\Item\WalmartItem(['sku'=>$sku]);
        return $item;
    }

    /**
     * Test Getting batch items
     * @depends testAccountExists
     */
    public function testGetBatchItems(\libraries\WalmartAccount $account){
        $offset = 0;
        $limit = 5;
        $res= libraries\WalmartFeatures::getBatchItems($account, $limit, $offset);

        $this->assertObjectHasAttribute("ItemResponse", $res);
        $this->assertObjectNotHasAttribute("error", $res);

        $offset = -5;
        $res= libraries\WalmartFeatures::getBatchItems($account, $limit, $offset);
        $this->assertObjectHasAttribute("error", $res);

        $limit = 0;
        $offset = 0;
        $res= libraries\WalmartFeatures::getBatchItems($account, $limit, $offset);
        $this->assertObjectHasAttribute("error", $res);
    }


    /**
     * @depends testAccountExists
     * @depends testGetAnItem
     */
    public function testGetInventoryForItem(\libraries\WalmartAccount $account, \libraries\Item\WalmartItem $item){
        $res= libraries\WalmartFeatures::getInventoryForItem($account, $item);
        $this->assertObjectHasAttribute("sku", $res);
        $this->assertObjectHasAttribute("fulfillmentLagTime", $res);
    }


}

