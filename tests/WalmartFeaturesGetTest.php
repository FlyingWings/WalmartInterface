<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 18-5-3
 * Time: 下午6:05
 */

include_once("../base.php");
include_once("../secret_config.php");

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
     * @depends testAccountExists
     */
    public function testGetAllFeedStatus(\libraries\WalmartAccount $account){

        $res = libraries\WalmartFeatures::getAllFeedStatus($account);
        $this->assertNotNull($res);
        $this->assertObjectHasAttribute("results", $res);
    }


    /**
     * @depends testAccountExists
     */
    public function testGetFeedItemsStatus(\libraries\WalmartAccount $account){
        $feedId = "tafdsfasf";// A random-assigned feed id
        $res = \libraries\WalmartFeatures::getFeedItemsStatus($account, $feedId);
        $this->assertObjectHasAttribute("error", $res);// If having it, means meeting an error
        $feedId = '87E2CB2195AD40E23C9423912A8DBDA4@AQMBAQA';// An Real Feed id
        $res = \libraries\WalmartFeatures::getFeedItemsStatus($account, $feedId);
        $this->assertObjectNotHasAttribute("error", $res);// If having it, means meeting an error
        $this->assertObjectHasAttribute("feedStatus", $res);
    }
}

