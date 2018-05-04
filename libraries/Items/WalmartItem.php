<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 18-5-4
 * Time: 下午4:46
 */

namespace libraries\Item;


class WalmartItem {

    public function __construct($attributes_array){
        foreach($attributes_array as $key=>$value){
            $this->$key = $value;
        }
    }

    function get($key){
        return $this->$key;
    }

    function __set($key, $value){
        $this->$key = $value;
    }




}