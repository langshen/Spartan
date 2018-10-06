<?php
namespace Spartan\Extend\Payment;

abstract class LogicPayment{

    function RsaSign($data,$priKey,$type = 'RSA2'){
        $priKey = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($priKey, 64, "\n", true)."\n-----END RSA PRIVATE KEY-----";
        if ("RSA2" == $type) {
            openssl_sign($data, $sign, $priKey, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $priKey);
        }
        return base64_encode($sign);
    }

    //RSA公钥验签
    function verifyKeyRSA($strData,$signData,$pubKey){
        $pubKey = "-----BEGIN PUBLIC KEY-----\n".wordwrap($pubKey, 64, "\n", true)."\n-----END PUBLIC KEY-----";
        $pubKey = openssl_pkey_get_public($pubKey);
        $a =  openssl_verify($strData,base64_decode($signData),$pubKey,OPENSSL_ALGO_SHA256);
        return $a;
    }

    function getSignContentData($arrData,$type='alipay'){
        ksort($arrData);
        $arrTemp = [];
        foreach ($arrData as $k=>$v){
            $arrTemp[] = $k . '='. ($type=='url'?urlencode($v):$v);
        }
        return implode('&',$arrTemp);
    }

    //得到一个CURL
    public function getCUrl(){
        return \Spt::getInstance('Spartan\\Driver\\Http\\Curl');
    }

    //AES加密
    public function encryptAes($strData,$priKey){
        return openssl_encrypt($strData, 'AES-128-ECB', $priKey);
    }

    //AES解密
    public function decryptAes($strData,$priKey){
        return openssl_decrypt($strData, 'AES-128-ECB', $priKey);
    }

    //RSA私钥加密
    public function encryptPrivateRSA($strData,$priKeyFile){
        $priKey = openssl_pkey_get_private(file_get_contents(APP_ROOT.'Common'.DS.$priKeyFile));
        openssl_private_encrypt($strData, $sign, $priKey);
        return base64_encode($sign);
    }
    //RSA公钥加密
    public function encryptPublicRSA($strData,$pubKeyFile){
        $priKey = openssl_pkey_get_public(file_get_contents(APP_ROOT.'Common'.DS.$pubKeyFile));
        openssl_public_encrypt($strData, $sign, $priKey);
        return base64_encode($sign);
    }
    //RSA公钥验签
    public function verifyRSA($strData,$signData,$priKeyFile){
        $priKey = openssl_pkey_get_public(file_get_contents(APP_ROOT.'Common'.DS.$priKeyFile));
        return openssl_verify($strData,base64_decode($signData),$priKey);
    }
    //RSA私钥加签
    public function signPrivateSign($strData,$priKeyFile){
        $priKey = openssl_pkey_get_private(file_get_contents(APP_ROOT.'Common'.DS.$priKeyFile));
        openssl_sign($strData, $sign, $priKey,OPENSSL_ALGO_SHA1);
        return base64_encode($sign);
    }
    //RSA私钥解密
    public function decryptPrivateRSA($strData,$priKeyFile){
        $priKey = openssl_pkey_get_private(file_get_contents(APP_ROOT.'Common'.DS.$priKeyFile));
        openssl_private_decrypt(base64_decode($strData),$decrypted,$priKey);//私钥解密
        return $decrypted;
    }
    //转化为xml
    public function toXml($arrBody){
        $strBody = '<?xml version="1.0" encoding="UTF-8"?>';
        $strBody .= "<xml>";
        foreach ($arrBody as $k => $v){
            if (!$v){continue;}
            if (is_string($v) && !is_numeric($v)){
                $strBody .= "<{$k}><![CDATA[{$v}]]></{$k}>";
            }else{
                $strBody .= "<{$k}>{$v}</{$k}>";
            }
        }
        return $strBody."</xml>";
    }
    //把xml转为数组
    public function formXml($xml){
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA),JSON_UNESCAPED_UNICODE), true);
    }

    public function getClientIp() {
        static $ip = null;
        if ($ip !== NULL){
            return $ip;
        }
        if (isset($_SERVER['HTTP_REMOTE_HOST'])) {
            $ip = $_SERVER['HTTP_REMOTE_HOST'];
        }elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown',$arr);
            if(false !== $pos){unset($arr[$pos]);}
            $ip = trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * 随机字符串
     * @param $length
     * @return string
     */
    public function getRandomString($length){
        $arr = array_merge(range(0, 9), range('A', 'Z'));
        $str = '';
        $arr_len = count($arr);
        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, $arr_len-1);
            $str.=$arr[$rand];
        }
        return $str;
    }
}