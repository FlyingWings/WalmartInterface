<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 18-5-7
 * Time: 上午10:55
 */

include_once(dirname(__DIR__)."/base.php");

use PHPUnit\Framework\Testcase;


class WalmartFeaturesDeleteTest extends TestCase{
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
    public function testRetireAnItem(\libraries\WalmartAccount $account){
        $sku = "Random-ASSIGNED-SKU";//illegal SKU
        $item = new \libraries\Item\WalmartItem(['sku'=>$sku]);
        $res = \libraries\WalmartFeatures::retireAnItem($account, $item);
        $this->assertObjectHasAttribute("error", $res);

        global $legal_sku_to_delete;
        $sku = $legal_sku_to_delete;//legal SKU
        $item = new \libraries\Item\WalmartItem(['sku'=>$sku]);
        $res = \libraries\WalmartFeatures::retireAnItem($account, $item);
        $this->assertObjectNotHasAttribute("error", $res);
        $this->assertObjectHasAttribute("sku", $res);
        $this->assertEquals($res->sku, $sku);
    }
}