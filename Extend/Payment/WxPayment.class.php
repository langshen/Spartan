<?php
namespace Spartan\Extend\Payment;

/**
 * @description 得到：app_auth_token
 */
class WxPayment extends LogicPayment
{
    private $arrConfig = Array(
        'API_URL'=>'https://api.mch.weixin.qq.com/',
        'SIGNKEY_URL'=>'https://api.mch.weixin.qq.com/pay/getsignkey',
        'OPEN_AUTH_URL'=>'https://open.weixin.qq.com/connect/oauth2/authorize?',//拿OPEN_ID
        'ACCESS_TOKEN_URL'=>'https://api.weixin.qq.com/sns/oauth2/access_token?',
        'JS_CODE_URL'=>'https://api.weixin.qq.com/sns/jscode2session?',
    );

    public function __construct($_arrConfig = []){
        $arrConfig = config('get.WX_PAYMENT',[]);
        $this->arrConfig = array_merge($this->arrConfig,$arrConfig,$_arrConfig);
    }

    //创立一个签名
    private function makeSign($arrParams,$type='md5'){
        $strSign = $this->getSignContentData($arrParams,'wxpay');//得到需要签名的URL字符串
        $strSign .= "&key=".$this->arrConfig['APP_KEY'];
        if($type == 'md5'){
            $string = md5($strSign);
        } else {
            $string = hash_hmac("sha256",$strSign ,$this->arrConfig['APP_KEY']);
        }
        return strtoupper($string);
    }

    //通过错误码显示具体的错误信息
    private function getMsg($key){
        static $_arrMsg = Array(
            'NOAUTH'=>'商户未开通此接口权限,请商户前往申请此接口权限',
            'NOTENOUGH'=>'用户帐号余额不足,请用户充值或更换支付卡后再支付',
            'ORDERPAID'=>'商户订单已支付，无需重复操作',
            'ORDERCLOSED'=>'订单已关闭，无法支付，请重新下单',
            'SYSTEMERROR'=>'系统异常，请用相同参数重新调用',
            'APPID_NOT_EXIST'=>'请检查APPID是否正确',
            'MCHID_NOT_EXIST'=>'请检查MCHID是否正确',
            'APPID_MCHID_NOT_MATCH'=>'请确认appid和mch_id是否匹配',
            'LACK_PARAMS'=>'请检查参数是否齐全',
            'OUT_TRADE_NO_USED'=>'同一笔交易不能多次提交,请核实商户订单号是否重复提交',
            'SIGNERROR'=>'请检查签名参数和方法是否都符合签名算法要求',
            'XML_FORMAT_ERROR'=>'请检查XML参数格式是否正确',
            'REQUIRE_POST_METHOD'=>'请检查请求参数是否通过post方法提交',
            'POST_DATA_EMPTY'=>'请检查post数据是否为空',
            'NOT_UTF8'=>'请使用NOT_UTF8编码格式',
            'USER_ACCOUNT_ABNORMAL'=>'退款请求失败',
            'INVALID_TRANSACTIONID'=>'无效的订单编号',
            'PARAM_ERROR'=>'请求参数错误，请重新检查再调用退款申请',
        );
        return isset($_arrMsg[$key])?$_arrMsg[$key]:'未知错误';
    }

    //执行一个操作。
    private function execute($method,$arrData=[]){
        $arrSysParams = Array(
            'appid'=>$this->arrConfig['APP_ID'],
            'mch_id'=>$this->arrConfig['MCH_ID'],
            'sign_type'=>'MD5',
            'spbill_create_ip'=>$this->getClientIp(),
            'nonce_str'=>$this->getRandomString(32),
            'notify_url'=>(isset($arrData['notify_url'])&&$arrData['notify_url'])?$arrData['notify_url']:$this->arrConfig['NOTIFY_URL']
        );
        unset($arrData['notify_url']);
        if (in_array($method,['pay/micropay','pay/closeorder','secapi/pay/refund'])){
            unset($arrSysParams['notify_url']);
            if ($method == 'pay/closeorder' || $method == 'secapi/pay/refund'){
                unset($arrSysParams['spbill_create_ip']);
            }
        }
        $arrSysParams = array_merge($arrSysParams,$arrData);
        $arrSysParams['sign'] = $this->makeSign($arrSysParams);
        $strPostData = $this->toXml($arrSysParams);
        if ($method == 'secapi/pay/refund'){
            $this->getCUrl()->setOpt(CURLOPT_SSLCERT,$this->arrConfig['API_CLIENT_CERT']);
            $this->getCUrl()->setOpt(CURLOPT_SSLKEY,$this->arrConfig['API_CLIENT_KEY']);
        }
        //开始提交动作
        $xml = $this->getCUrl()->send($this->arrConfig['API_URL'].$method,$strPostData,'POST');
        if (!$xml){
            return Array('请求返回的数据异常。',0);
        }
        return $this->formXml($xml);
    }

    //得到回传信息，验证签名
    public function checkSign($strXml,$type='md5'){
        $arrData = $this->formXml($strXml);
        $strSign = isset($arrData['sign'])?$arrData['sign']:'';
        unset($arrData['sign']);
        if (!$strSign){
            return Array('验证丢失。',0);
        }
        if ($strSign != $this->makeSign($arrData,$type)){
            return Array('验签失败。',0);
        }
        return Array('成功',1,$arrData);
    }

    //在微信端得到一个用户的OpenId
    //触发微信返回code码
    public function gotoOpenUrl($url){
        $arrData = Array(
            "appid" => $this->arrConfig['APP_ID'],
            "redirect_uri" => $url,
            "response_type" => "code",
            "scope" => "snsapi_base",
            "state" => "STATE#wechat_redirect",
        );
        redirect($this->arrConfig['OPEN_AUTH_URL'].$this->getSignContentData($arrData,'url'));
    }

    /**
     * 通过跳转获取用户的openid，跳转流程如下：
     * 1、设置自己需要调回的url及其其他参数，跳转到微信服务器https://open.weixin.qq.com/connect/oauth2/authorize
     * 2、微信服务处理完成之后会跳转回用户redirect_uri地址，此时会带上一些参数，如：code
     * @param $strCode
     * @return array
     */
    public function getUserCode($strCode){
        if (!$strCode){
           return array('code丢失',0);
        }
        $arrData = Array(
            'appid'=>$this->arrConfig['APP_ID'],
            'secret'=>$this->arrConfig['APP_SECRET'],
            'code'=>$strCode,
            'grant_type'=>'authorization_code',
        );
        $url = $this->arrConfig['ACCESS_TOKEN_URL'].$this->getSignContentData($arrData,'url');
        $arrInfo = $this->getCUrl()->send($url);
//{"access_token":"en_tz-OlU2bFZ8Eutt46HHnm0pKif5osjLzGIHZh6ZJv_JNkcH7YZdpPsoKEesAZn58tPH3tuq5eXYbEk_WWc_BQs8kyqUBs8qIQmTUH1Jw","expires_in":7200,"refresh_token":"57NmUMOBHAGW3BxnBgFifs5PzNW0Nh2k_7jImSA_p4ZaPFOW0KFc0AujPmDGgpjZLIogoa5f-TqIcKwvy95UHNq7WnUJqeEJRKc2HaMJyG4","openid":"oKxravy0EBXe9esZL4SikWagjk_E","scope":"snsapi_base"}
        !isset($arrInfo['openid']) && $arrInfo['openid'] = '';
        return Array('完成',($arrInfo['openid'] ? 1: 0),['open_id'=>$arrInfo['openid']]);
    }

    public function getUserJsCode($strCode){
        if (!$strCode){
            return array('code丢失',0);
        }
        $arrData = Array(
            'appid'=>$this->arrConfig['APP_ID'],
            'secret'=>$this->arrConfig['APP_SECRET'],
            'js_code'=>$strCode,
            'grant_type'=>'authorization_code',
        );
        $url = $this->arrConfig['JS_CODE_URL'].$this->getSignContentData($arrData,'url');
        $arrInfo = $this->getCUrl()->send($url);
        !isset($arrInfo['openid']) && $arrInfo['openid'] = '';
        return Array('完成',($arrInfo['openid'] ? 1: 0),['open_id'=>$arrInfo['openid']]);
    }


    //得到一个支付的二维码
    //https://pay.weixin.qq.com/wiki/doc/api/native_sl.php?chapter=9_1
    public function getQrCode($arrTradeInfo){
        $arrParams = Array(
            'sub_mch_id'=>$arrTradeInfo['merchant_code'],
            'device_info'=>$arrTradeInfo['merchant_id'],//终端设备号(门店号或收银设备ID)，注意：PC网页或公众号内支付请传"WEB"
            'body'=>$arrTradeInfo['subject'],
            'attach'=>json_encode([
                'merchant_id'=>$arrTradeInfo['merchant_id'],
                'user_id'=>$arrTradeInfo['user_id'],
                'order_id'=>$arrTradeInfo['order_id'],
                'order_num'=>$arrTradeInfo['order_num'],
            ],JSON_UNESCAPED_UNICODE),//附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
            'out_trade_no'=>$arrTradeInfo['order_num'],//商户系统内部订单号，要求32个字符内
            'total_fee'=>$arrTradeInfo['total_money'] * 100,//订单总金额，只能为整数，详见支付金额
            'trade_type'=>'NATIVE',//JSAPI--公众号支付、NATIVE--原生扫码支付、APP--app支付，统一下单接口trade_type的传参可参考这里
            'product_id'=>$arrTradeInfo['merchant_id'].'-'.$arrTradeInfo['order_id'],//trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。
        );
        if (!$arrParams['out_trade_no']){
            return Array('缺少统一支付接口必填参数out_trade_no！',0);
        }elseif (!$arrParams['body']){
            return Array('缺少统一支付接口必填参数body！',0);
        }elseif ($arrParams['total_fee'] <= 0){
            return Array('缺少统一支付接口必填参数total_fee！',0);
        }
        if ($arrParams['trade_type'] == 'NATIVE' && !$arrParams['product_id']){
            return Array('统一支付接口中，缺少必填参数product_id！trade_type为NATIVE时，product_id为必填参数！',0);
        }

        $arrResultInfo = $this->execute('pay/unifiedorder',$arrParams);

        if ($arrResultInfo['return_code'] != 'SUCCESS'){
            return Array('错误:'.$arrResultInfo['return_msg'].'请联系管理员。',0);
        }elseif ($arrResultInfo['return_code'] == 'SUCCESS' && $arrResultInfo['result_code'] != 'SUCCESS'){
            $strMsg = '错误:'.$arrResultInfo['err_code_des'].'('.$arrResultInfo['err_code'].'),'.$this->getMsg($arrResultInfo['err_code']);
            return Array($strMsg,0);
        }

        $arrResultInfo = Array(
            'qrCode' => $arrResultInfo['code_url'],
            'prepay_id' => $arrResultInfo['prepay_id'],
            'result_code' => $arrResultInfo['result_code'],
            'return_code' => $arrResultInfo['return_code'],
        );
        return Array('成功',1,$arrResultInfo);
    }

    /**
     * 统一下单并调用JSAPI完成支付
     * https://pay.weixin.qq.com/wiki/doc/api/native_sl.php?chapter=9_1
     * @param $arrTradeInfo
     * @return array
     */
    public function getWebPay($arrTradeInfo){
        $arrAttach = Array(
            'rnd'=>$arrTradeInfo['rnd_id'],
            'user_id'=>$arrTradeInfo['user_id'],
        );
        if (isset($arrTradeInfo['order_id']) && $arrTradeInfo['order_id']){
            $arrAttach['order_id'] = $arrTradeInfo['order_id'];
        }
        if (isset($arrTradeInfo['vip_id']) && $arrTradeInfo['vip_id']){
            $arrAttach['vip_id'] = $arrTradeInfo['vip_id'];
        }
        if (isset($arrTradeInfo['inpour_id']) && $arrTradeInfo['inpour_id']){
            $arrAttach['inpour_id'] = $arrTradeInfo['inpour_id'];
        }
        if (isset($arrTradeInfo['status']) && $arrTradeInfo['status']){
            $arrAttach['status'] = $arrTradeInfo['status'];
        }
        if (isset($arrTradeInfo['coupon_id']) && $arrTradeInfo['coupon_id']){
            $arrAttach['coupon_id'] = $arrTradeInfo['coupon_id'];
        }
        $arrParams = Array(
            'body'=>$arrTradeInfo['subject'],
            'attach'=>json_encode($arrAttach,JSON_UNESCAPED_UNICODE),//附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
            'out_trade_no'=>$arrTradeInfo['order_num'].'-'.$arrTradeInfo['rnd_id'],//商户系统内部订单号，要求32个字符内
            'total_fee'=>$arrTradeInfo['total_money'] * 100,//订单总金额，只能为整数，详见支付金额
            'trade_type'=>'JSAPI',//JSAPI--公众号支付、NATIVE--原生扫码支付、APP--app支付，统一下单接口trade_type的传参可参考这里
            'openid'=>$arrTradeInfo['buyer_id'],
        );
        if (!$arrParams['out_trade_no']){
            return Array('缺少统一支付接口必填参数out_trade_no！',0);
        }elseif (!$arrParams['body']){
            return Array('缺少统一支付接口必填参数body！',0);
        }elseif ($arrParams['total_fee'] <= 0){
            return Array('缺少统一支付接口必填参数total_fee！',0);
        }
        if ($arrParams['trade_type'] == 'JSAPI' && !$arrParams['openid']){
            return Array('统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！',0);
        }
        $arrResultInfo = $this->execute('pay/unifiedorder',$arrParams);
        if ($arrResultInfo['return_code'] != 'SUCCESS'){
            return Array('错误:'.$arrResultInfo['return_msg'].'请联系管理员。',0);
        }elseif ($arrResultInfo['return_code'] == 'SUCCESS' && $arrResultInfo['result_code'] != 'SUCCESS'){
            $strMsg = '错误:'.$arrResultInfo['err_code_des'].'('.$arrResultInfo['err_code'].'),'.$this->getMsg($arrResultInfo['err_code']);
            return Array($strMsg,0);
        }
        $arrResultInfo = Array(
            'prepay_id' => $arrResultInfo['prepay_id'],
            'result_code' => $arrResultInfo['result_code'],
            'return_code' => $arrResultInfo['return_code'],
        );
        return Array('成功',1,$arrResultInfo);
    }

    /**
     * 提交刷卡支付
     * https://pay.weixin.qq.com/wiki/doc/api/micropay_sl.php?chapter=9_10&index=1
     * @param $arrTradeInfo
     * @return array
     */
    public function getTradePay($arrTradeInfo){
        $arrParams = Array(
            //'sub_mch_id'=>$arrTradeInfo['merchant_code'],
            'body'=>$arrTradeInfo['subject'],
            'attach'=>json_encode([
                'status'=>$arrTradeInfo['status'],
                'user_id'=>$arrTradeInfo['user_id'],
                'order_id'=>$arrTradeInfo['order_id'],
                'coupon_id'=>$arrTradeInfo['coupon_id'],
                'rnd'=>$arrTradeInfo['rnd_id'],
            ],JSON_UNESCAPED_UNICODE),//附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
            'out_trade_no'=>$arrTradeInfo['order_num'],//商户系统内部订单号，要求32个字符内
            'total_fee'=>$arrTradeInfo['total_money'] * 100,//订单总金额，只能为整数，详见支付金额
            'auth_code'=>$arrTradeInfo['auth_code'],//扫码支付授权码，设备读取用户微信中的条码或者二维码信息
        );
        if (!$arrParams['out_trade_no']){
            return Array('缺少统一支付接口必填参数out_trade_no！',0);
        }elseif (!$arrParams['body']){
            return Array('缺少统一支付接口必填参数body！',0);
        }elseif ($arrParams['total_fee'] <= 0){
            return Array('缺少统一支付接口必填参数total_fee！',0);
        }
        $arrResultInfo = $this->execute('pay/micropay',$arrParams);
        if ($arrResultInfo['return_code'] != 'SUCCESS'){
            return Array('错误:'.$arrResultInfo['return_msg'].'请联系管理员。',0);
        }elseif ($arrResultInfo['return_code'] == 'SUCCESS' && $arrResultInfo['result_code'] != 'SUCCESS'){
            $strMsg = '错误:'.$arrResultInfo['err_code_des'].'('.$arrResultInfo['err_code'].'),'.$this->getMsg($arrResultInfo['err_code']);
            return Array($strMsg,0);
        }
        return Array('成功',1,$arrResultInfo);
    }

    /**
     * APP支付统一下单并调用JSAPI完成支付
     * https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_1
     * @param $arrTradeInfo
     */
    public function getAppPay($arrTradeInfo){
        //没有申请过
    }

    /**
     * 返回一个JS直接可以用的对像格式
     * @param $arrOrderInfo
     * @return array
     */
    public function getJSAPI($arrOrderInfo){
        $arrData = Array(
            'appId'=>$this->arrConfig['APP_ID'],
            'timeStamp'=>''.time(),
            'nonceStr'=>strtolower($this->getRandomString(30)),
            'package'=>"prepay_id={$arrOrderInfo['prepay_id']}",
            'signType'=>'MD5',
        );
        $arrData['paySign'] = $this->makeSign($arrData);
        return Array('成功',1,$arrData);
    }
    /**
     * 退款
     * @param $arrOrderInfo
     * @return array
     */
    public function refund($arrOrderInfo){
        $arrParams = Array(
            'transaction_id' => $arrOrderInfo['trade_no'],//微信订单号
            //'out_trade_no' => $arrOrderInfo['order_num'],//商户侧传给微信的订单号
            'out_refund_no' => $arrOrderInfo['refund_no'],//商户系统内部的退款单号，商户系统内部唯一，同一退款单号多次请求只退一笔
            'total_fee' => $arrOrderInfo['total_money']*100,//订单总金额，单位为分，只能为整数
            'refund_fee' => $arrOrderInfo['refund_fee']*100,//退款总金额，订单总金额，单位为分，只能为整数
            'op_user_id' => $this->arrConfig['MCH_ID'],//操作员帐号, 默认为商户号
        );
        $arrResultInfo = $this->execute('secapi/pay/refund',$arrParams);

        if ($arrResultInfo['return_code'] != 'SUCCESS'){
            return Array('错误:'.$arrResultInfo['return_msg'].'请联系管理员。',0);
        }elseif ($arrResultInfo['return_code'] == 'SUCCESS' && $arrResultInfo['result_code'] != 'SUCCESS'){
            $strMsg = '错误:'.$arrResultInfo['err_code_des'].'('.$arrResultInfo['err_code'].'),'.$this->getMsg($arrResultInfo['err_code']);
            return Array($strMsg,0);
        }
        return Array('成功',1,$arrResultInfo);
    }
    /**
     * 关闭订单
     * @param $arrOrderInfo
     * @return array
     */
    public function closeOrder($arrOrderInfo){
        $arrParams = Array(
            'out_trade_no' => $arrOrderInfo['order_num'],
        );
        $arrResultInfo = $this->execute('pay/closeorder',$arrParams);
        return Array('成功',1,$arrResultInfo);
    }


}