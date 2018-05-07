<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 18-5-7
 * Time: 下午3:26
 */

include_once(dirname(__DIR__)."/base.php");

use \PHPUnit\Framework\TestCase;

class WalmartFeaturesPutTest extends TestCase{
    public function testAccountExists(){
        global $config_array;
        $this->assertNotEmpty($config_array);
        $wal_account = new libraries\WalmartAccount($config_array);
        $this->assertObjectHasAttribute("shop_name", $wal_account);
        return $wal_account;
    }


    /**
     * @depends testAccountExists
     * @param \libraries\WalmartAccount $account
     * @param $sku
     */
    public function testUpdateInventory(\libraries\WalmartAccount $account){
        $illegal_sku = "RANDOM-ASSIGN-SKU";
        $item = new \libraries\Item\WalmartItem(['sku'=>$illegal_sku, "qty"=>10, 'lag_time'=>2]);
        $res = \libraries\WalmartFeatures::updateInventory($account, $item);
        $this->assertObjectHasAttribute("error", $res);

        global $legal_sku;
        $item = new \libraries\Item\WalmartItem(['sku'=>$legal_sku, "qty"=>10, 'lag_time'=>2]);
        $res = \libraries\WalmartFeatures::updateInventory($account, $item);
        $this->assertObjectHasAttribute("sku", $res);
        $this->assertEquals($legal_sku, $res->sku);
        $this->assertEquals("2", $res->fulfillmentLagTime);
    }
}