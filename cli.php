<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 18-5-3
 * Time: 上午10:12
 */

require_once("base.php");
use \libraries;
require_once("secret_config.php");// All the configuration, including declaration of $config_array


$wal_account = new libraries\WalmartAccount($config_array);
$res = libraries\WalmartFeatures::getAllFeedStatus($wal_account);
var_dump($res);exit;