<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 17-12-26
 */
namespace libraries;

class WalmartAccount {

    private $account_name;

    public function __construct($construct_array){
        foreach($construct_array as $key=>$value){
            $this->$key = $value;// Init Account information
        }
    }

    public function get_signature($url, $method, $timestamp){
        $res = $this->_GetWalmartAuthSignature($url, $method, $timestamp);
        return $res;
    }
    public function __get($name)
    {
        return $this->$name;
    }

    public function __set($name, $value){
        $this->$name = $value;
    }

    public function get($key){
        return $this->$key;
    }


    protected function _GetWalmartAuthSignature($URL, $RequestMethod, $Timestamp) {
        $WalmartConsumerID = $this->customer_id;//Your Walmart Private Key;
        $WalmartPrivateKey = $this->api_key;
        // CONSTRUCT THE AUTH DATA WE WANT TO SIGN
        $AuthData = $WalmartConsumerID."\n";
        $AuthData .= $URL."\n";
        $AuthData .= $RequestMethod."\n";
        $AuthData .= $Timestamp."\n";
        // GET AN OPENSSL USABLE PRIVATE KEY FROMM THE WARMART SUPPLIED SECRET
        $Pem = $this->_ConvertPkcs8ToPem(base64_decode($WalmartPrivateKey));//dd($Pem);
        $PrivateKey = openssl_pkey_get_private($Pem);
        // SIGN THE DATA. USE sha256 HASH
        $Hash = defined("OPENSSL_ALGO_SHA256") ? OPENSSL_ALGO_SHA256 : "sha256";
        if (!openssl_sign($AuthData, $Signature, $PrivateKey, $Hash))
        { // IF ERROR RETURN NULL
            return null;
        }
        //ENCODE THE SIGNATURE AND RETURN
        return base64_encode($Signature);
    }

    protected function _ConvertPkcs8ToPem($der){
        static $BEGIN_MARKER = "-----BEGIN PRIVATE KEY-----";
        static $END_MARKER = "-----END PRIVATE KEY-----";
        $key = base64_encode($der);
        $pem = $BEGIN_MARKER . "\n";
        $pem .= chunk_split($key, 64, "\n");
        $pem .= $END_MARKER . "\n";
        return $pem;
    }


}

