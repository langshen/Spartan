<?php
namespace Spartan\Extend\BankPay;
use Spartan\Extend\Payment\LogicPayment;

/**
 * @description ISV替商户发起请求的授权，即得到：app_auth_token
 * Class AlipayAuthCode
 * @package Api\Extend
 */
class AliPayment extends LogicPayment
{
    private $version = '1.0';
    private $charset = 'utf-8';
    private $appId = '2017072207851134';//AppId - 2017072207851134，沙：2016080500176261
    private $gatewayUrl = "https://openapi.alipay.com/gateway.do";//网关,https://openapi.alipaydev.com/gateway.do https://openapi.alipay.com/gateway.do
    private $signType = "RSA2";//签名类型
    private $privateKey = 'MIIEogIBAAKCAQEArgNRL/uYM3VsGfaTZlPRM0pQJ1/QOFWpUWG7PZ+GlALHtIQ4b0izUEfZ/ddzhx8HRvK+qPTgQmBBxGa0TynHvSg+jVw7Wsp1V1v26E665P544aLAHXYfCKSz3d1c9b5jStYbpACEHi687/7Ee7F866JzvoiDenHj/DFFN+E3najuRfVaLMgG8Khk+ZD/foltnQTghqEzPtvPY3SrTBr/NfJLcGCUsbyQRhJLh4RAw4AvFZoeU7S6PMkGLBfXLDlTzQJ8a9StgTdEgq7WJv64l98mVbaOVwoHBi7WZTMpuE/lFPSuuQkfQyvLqnTzVAPbz6W9XCvA9kPcX/NeWZqQzwIDAQABAoIBAG3MyAjX34Tw3eJQFVgnIUUU6hi+O3ugibNBUM2kgF2al3rPR3Do1cSdYe7raQlkycm52BZyVaNsa3NLPxEIkvFHmJjIDufOAla6P8T8EK/35jyx3jl41EI28wvW5xZlKPAKw+wrKzKEWVGyVzaZmvJwUkpyh0vW233BdSS6ZsaCyzdz3JYrLf/laDobD4ru0KQ4tABVP69aaAHhNfBnrzFhWgATVXFySLfdmsZHfHm/8jIGKaoT5Mtjy91bSKOUPAA1PFU+u495dW95PrND3mkBS3YmnFDZsajKkGpH+lxrVyOJZdFAch2gDr1FrevTmjxpwkQQvuOZDjOGBcbss1ECgYEA58kHvLvAQD/7ZbY0xhv8CMbJY45idgrMVTOGtFz9wh+EwM51qPsa+R0uQlCHgHgmserX7zJCd7rLQjmycitCNNmpxWQ2QLcR8+Hpf5hTLmAXPAUEjw4SMU30Yy3rJTJOZ6kE3IFUHhl3+0YZyfcewFaK2B+BRgbSdeOYUPk+KocCgYEAwDEys68l+QzbhmHadxF0BXB+bpUgM2JqRctcopbmhCSYRd1YarvKKA5ZgtegKn+1Atpwfq2sIvPYXc0MGFpCZSF1eSB/m/3f03RWMOJjZYoTDc8zJ7HGYyjXFvCVCxoDOd1FYq8F6derbgCyepSYQbyxsCqArOQwVQfnIaBOkXkCgYB76JktSQV2k322mxhNTAqJOpPQl/6E8jLX3WrGouu5ShYy6Gw4AL0jrXjcVKaLhC/TbyMuqSSlUwN0DNobdIq5LB84+eCS6gs74GpuHqVhJldla51LSI9rMixSlOqfAOyvN8j0hGLOkHj2qDwDHwuecOVaskTuhZkfEqOLriKQowKBgEZ0gqq+RYRkQ0GjM6w8mLS5xY+SWYicxmqpn173RLAinjPWbehKyVQf1o6Rr2SFBn1ySJUX46e4jpPsbEetJvPd7SunT0CHM/tXhZVMGLYLhOqmD5G4qQqG2TrOnUTBl3cp95qyoM9VwcGEvekT+jD3FIiJPDylNlalnASGuOa5AoGAQCDcCtyUPRU/ddQK30QNshkbK4pXozjVKjJJv9Z71351chui4LyrI0DlQ0I18mmod0C2q6ydrIj0ZCn0hLGCp8C7A6tbrAEBXyVYLCmM2AjDoDKhj9wGY2juokdAjb0NHICQbhyA5NsGZ+oeD9yd+2Hqo3hglUV/M0cHnSY1iuQ=';
    private $taoBaoKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqyKZBAyYEgfdVcPtQuslhImHQfK4KAB42KGV0N62ckEtX9+exAHAdZqS7B7aXLjIKsNb1tbDPVLgXyvENKowE4ZEnQx6ozVCtd9RrYES6ayU3AxmAGNoRjTsnqt3oCW2mM25jVRz3qQ4xBO0oitwAUcXbCLzWzRFVoY+UtpPKi+45AMcQSCbFnqcmgSp+2G3MupvCnwupJgsiaus5X8Q88dOzq+xUdY5wbfdd3vUFetNfSjP2LVM20RefColzbX0U5OAa5hCy3SBJc7QtZBIF9VBJOo0XN6r3cQQlG+ulbV8hgLDH46Ya8zlsaMRXvR/g42E89SKTOsB1A1AL88jDQIDAQAB';
    //private $publicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArgNRL/uYM3VsGfaTZlPRM0pQJ1/QOFWpUWG7PZ+GlALHtIQ4b0izUEfZ/ddzhx8HRvK+qPTgQmBBxGa0TynHvSg+jVw7Wsp1V1v26E665P544aLAHXYfCKSz3d1c9b5jStYbpACEHi687/7Ee7F866JzvoiDenHj/DFFN+E3najuRfVaLMgG8Khk+ZD/foltnQTghqEzPtvPY3SrTBr/NfJLcGCUsbyQRhJLh4RAw4AvFZoeU7S6PMkGLBfXLDlTzQJ8a9StgTdEgq7WJv64l98mVbaOVwoHBi7WZTMpuE/lFPSuuQkfQyvLqnTzVAPbz6W9XCvA9kPcX/NeWZqQzwIDAQAB';
    private $mergeData = [];
    public $callBackApiUrl = '';//回调、通信的Api域名

    //执行一个动作
    private function execute($method,$bizContent=[]){
        $arrSysParams = Array(
            'version'=>$this->version,
            'app_id'=>$this->appId,
            'method'=>$method,
            'format'=>'JSON',
            'charset'=>$this->charset,
            'sign_type'=>$this->signType,
            'timestamp'=>date('Y-m-d H:i:s'),
        );
        $authToken = isset($bizContent['app_auth_token'])?$bizContent['app_auth_token']:'';
        unset($bizContent['app_auth_token']);
        $notifyUrl = isset($bizContent['notify_url'])?$bizContent['notify_url']:'';
        unset($bizContent['notify_url']);
        $returnUrl = isset($bizContent['return_url'])?$bizContent['return_url']:'';
        unset($bizContent['return_url']);
        $this->mergeData && $arrSysParams = array_merge($arrSysParams,$this->mergeData);
        $authToken && $arrSysParams['app_auth_token'] = $authToken;
        $notifyUrl && $arrSysParams['notify_url'] = $notifyUrl;
        $returnUrl && $arrSysParams['return_url'] = $returnUrl;
        $bizContent && $arrSysParams['biz_content'] = json_encode($bizContent,JSON_UNESCAPED_UNICODE);
        //集合主要参所有参数完毕
        $strSign = $this->getSignContentData($arrSysParams,'alipay');//得到需要签名的URL字符串
        $arrSysParams['sign'] = $this->RsaSign($strSign,$this->privateKey);
        $strPostData = $this->getSignContentData($arrSysParams,'url');//组装成URL字段串
        //开始提交动作
        $arrInfo = $this->getCUrl()->send($this->gatewayUrl,$strPostData,'POST');
        $strMethodResponse = str_ireplace('.','_',$method.'_response');//返回的数据集
        $this->mergeData = [];
        //print_r($arrSysParams);
        //print_r($arrInfo);die();
        if (!is_array($arrInfo) || !isset($arrInfo[$strMethodResponse]) || !is_array($arrInfo[$strMethodResponse])){
            return Array('支付宝Token信息不正确，请联系管理员。',0,$arrInfo,20002);
        }
        $arrInfo = $arrInfo[$strMethodResponse];//得到当前最主要的记录集
        if (!$arrInfo){
            return Array('支付宝Token未能正常返回，请联系管理员。',0,$arrInfo,20003);
        }
        $arrInfo['method'] = $method;
        return $arrInfo;
    }

    //通过错误码显示具体的错误信息
    private function getMsg($key){
        static $_arrMsg = Array(
            'AOP.INVALID-AUTH-TOKEN'=>'无效的访问令牌,请重新授权获取新的令牌',
            'AOP.INVALID-APP-AUTH-TOKEN'=>'无效的应用授权令牌,请重新授权获取新的令牌',
            'ACQ.SYSTEM_ERROR'=>'接口返回错误',
            'ACQ.INVALID_PARAMETER'=>'参数无效',
            'ACQ.ACCESS_FORBIDDEN'=>'无权限使用接口',
            'ACQ.EXIST_FORBIDDEN_WORD'=>'订单信息中包含违禁词',
            'ACQ.PARTNER_ERROR'=>'应用APP_ID填写错误',
            'ACQ.TOTAL_FEE_EXCEED'=>'订单总金额超过限额',
            'ACQ.CONTEXT_INCONSISTENT'=>'交易信息被篡改',
            'ACQ.TRADE_HAS_SUCCESS'=>'交易已被支付',
            'ACQ.TRADE_HAS_CLOSE'=>'交易已经关闭',
            'ACQ.BUYER_SELLER_EQUAL'=>'买卖家不能相同',
            'ACQ.TRADE_BUYER_NOT_MATCH'=>'交易买家不匹配',
            'ACQ.BUYER_ENABLE_STATUS_FORBID'=>'买家状态非法',
            'ACQ.BUYER_PAYMENT_AMOUNT_DAY_LIMIT_ERROR'=>'买家付款日限额超限',
            'ACQ.BEYOND_PAY_RESTRICTION'=>'商户收款额度超限',
            'ACQ.BEYOND_PER_RECEIPT_RESTRICTION'=>'商户收款金额超过月限额',
            'ACQ.BUYER_PAYMENT_AMOUNT_MONTH_LIMIT_ERROR'=>'买家付款月额度超限',
            'ACQ.SELLER_BEEN_BLOCKED'=>'商家账号被冻结',
            'ACQ.ERROR_BUYER_CERTIFY_LEVEL_LIMIT'=>'买家未通过人行认证,请用户联系支付宝小二并更换其它付款方式',
            'ACQ.INVALID_STORE_ID'=>'商户门店编号无效,检查传入的门店编号是否符合规则'
        );
        $key = strtoupper($key);
        return isset($_arrMsg[$key])?$_arrMsg[$key]:'未知错误';
    }

    //验签
    public function verifySign($arrData){
        $strSign = isset($arrData['sign'])?$arrData['sign']:'';
        unset($arrData['sign'],$arrData['sign_type']);
        print_r($arrData);
        $strNewSign = $this->getSignContentData($arrData,'alipay');//得到需要签名的URL字符串
        print_r("\r\n");
        print_r($strNewSign);
        return $this->verifyKeyRSA($strNewSign,$strSign,$this->taoBaoKey);
    }

    //支付宝授权，下面是保存授权
    public function gotoAuth(){
        $strUrl = 'https://openauth.alipay.com/oauth2/appToAppAuth.htm?app_id='.$this->appId.
            '&redirect_uri='.urlencode($this->callBackApiUrl.'/AuthCode/AliAuth');
        return $strUrl;
    }

    //使用app_auth_code换取app_auth_token，也就是授权成功之后，需要保存的信息
    public function getAuthInfo(){
        $strSource = trim(request()->get('source',''));
        $strAuthCode = trim(request()->get('app_auth_code',''));
        $strAppId = trim(request()->get('app_id',''));
        if ($strSource != 'alipay_app_auth' || mb_strlen($strAuthCode,'utf-8') != 32 || $strAppId != $this->appId){
            return Array('/Qr/message/20001.html',0);
        }
        //看看session是不是对了。
        $arrData = session('auth_info');
        session('auth_info',null);
        if ($arrData['merchant_id'] < 1 || $arrData['user_id'] < 1){
            return Array('/Qr/message/30002.html',0);
        }
        //查询,使用app_auth_code换取app_auth_token
        $arrParams = Array(
            'grant_type'=>'authorization_code',
            'code'=>$strAuthCode,
        );
        $arrResultInfo = $this->execute('alipay.open.auth.token.app',$arrParams);
        //下面是提交保存
        $arrData['ali_pay_info'] = json_encode($arrResultInfo,JSON_UNESCAPED_UNICODE);

        return Array('成功',1,$arrData);
    }

    //扫码支付，得到一个二维码,统一收单线下交易预创建（扫码支付）,alipay.trade.precreate
    //https://doc.open.alipay.com/doc2/apiDetail.htm?spm=a219a.7629065.0.0.PlTwKb&apiId=862&docType=4
    public function getQrCode($arrTradeInfo){
        $arrParams = Array(
            'app_auth_token'=>$arrTradeInfo['merchant_code'],
            'out_trade_no'=>$arrTradeInfo['order_num'],//商户订单号,64个字符以内、只能包含字母、数字、下划线；需保证在商户端不重复
            'total_amount'=>$arrTradeInfo['total_money'],
            'subject'=>$arrTradeInfo['subject'],//订单标题
            'body'=>$arrTradeInfo['desc'],
            'operator_id'=>$arrTradeInfo['operator_id']?$arrTradeInfo['operator_id']:'',
            'store_id'=>$arrTradeInfo['merchant_id']?$arrTradeInfo['merchant_id']:'',
            'terminal_id'=>$arrTradeInfo['terminal_id']?$arrTradeInfo['terminal_id']:'',
            'notify_url'=>$this->callBackApiUrl.'/AuthCode/AliQrCodeCallBack',
        );
        $arrResultInfo = $this->execute('alipay.trade.precreate',$arrParams);

        if ($arrResultInfo['code'] != '10000'){
            if ($arrResultInfo['code'] == '40004'){
                $strMsg = '错误:请签约后再授权？';
            }else{
                $strMsg = '错误:'.$arrResultInfo['sub_code'].'('.$arrResultInfo['code'].'),'.$this->getMsg($arrResultInfo['sub_code']);
            }
            return Array($strMsg,0);
        }
        //out_trade_no,trade_no
        $arrResultInfo['qrCode'] = $arrResultInfo['qr_code'];
        return Array('成功',1,$arrResultInfo);
    }

    //统一收单交易创建接口
    //https://doc.open.alipay.com/doc2/apiDetail.htm?spm=a219a.7629065.0.0.1hBqdQ&apiId=850&docType=4
    //https://doc.open.alipay.com/docs/doc.htm?&docType=1&articleId=105672
    public function getWebPay($arrTradeInfo){
        $arrParams = Array(
            'app_auth_token'=>$arrTradeInfo['merchant_code'],
            'out_trade_no'=>$arrTradeInfo['order_num'],//商户订单号,64个字符以内、只能包含字母、数字、下划线；需保证在商户端不重复
            'total_amount'=>$arrTradeInfo['total_money'],
            'subject'=>$arrTradeInfo['subject'],//订单标题
            'body'=>$arrTradeInfo['desc'],
            'buyer_id'=>$arrTradeInfo['buyer_id'],
            'operator_id'=>$arrTradeInfo['operator_id']?$arrTradeInfo['operator_id']:'',
            'store_id'=>$arrTradeInfo['merchant_id']?$arrTradeInfo['merchant_id']:'',
            'terminal_id'=>$arrTradeInfo['terminal_id']?$arrTradeInfo['terminal_id']:'',
            'notify_url'=>$this->callBackApiUrl.'/AuthCode/AliQrCodeCallBack',
        );
        $arrResultInfo = $this->execute('alipay.trade.create',$arrParams);
        if ($arrResultInfo['code'] != '10000'){
            $strMsg = '错误:'.$arrResultInfo['sub_code'].'('.$arrResultInfo['code'].'),'.$this->getMsg($arrResultInfo['sub_code']);
            return Array($strMsg,0);
        }
        //out_trade_no,trade_no
        return Array('成功',1,$arrResultInfo);
    }

    //支付宝当前客户端授权，下面是跳转
    public function gotoOpenUrl($url){//
        $strUrl = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id='.$this->appId.
            '&redirect_uri='.urlencode($url).'&scope=auth_base&state=17syt';
        redirect($strUrl);
    }

    //用户的，alipay.system.oauth.token，使用auth_code换取接口access_token及用户userId,
    public function getUserAuth(){
        $strAuthCode = trim(request()->get('auth_code',''));
        $this->mergeData = Array(
            'grant_type'=>'authorization_code',
            'code'=>$strAuthCode,
        );
        $arrInfo = $this->execute('alipay.system.oauth.token');
        !isset($arrInfo['user_id']) && $arrInfo['user_id'] = '';
//Array([access_token] => authbseB3552e6f9fa9d4b029878510a0245aX27 [alipay_user_id] => 20880072417622987820983252717927 [expires_in] => 31536000 [re_expires_in] => 31536000 [refresh_token] => authbseB369875d530764ea39ab4cb2fea21fX27 [user_id] => 2088002377671276 [method] => alipay.system.oauth.token )
        return Array('完成',($arrInfo['user_id'] ? 1: 0),['user_id'=>$arrInfo['user_id'],'payment'=>'ZFBZF']);
    }

    //创建一个条码支付
    //https://docs.open.alipay.com/api_1/alipay.trade.pay
    public function getTradePay($arrTradeInfo){
        $arrParams = Array(
            'app_auth_token'=>$arrTradeInfo['merchant_code'],
            'out_trade_no'=>$arrTradeInfo['order_num'],//商户订单号,64个字符以内、只能包含字母、数字、下划线；需保证在商户端不重复
            'total_amount'=>$arrTradeInfo['total_money'],
            'scene'=>'bar_code',
            'auth_code'=>$arrTradeInfo['auth_code'],
            'subject'=>$arrTradeInfo['subject'],//订单标题
            'body'=>$arrTradeInfo['desc'],
            'operator_id'=>''.($arrTradeInfo['operator_id']?$arrTradeInfo['operator_id']:''),
            'store_id'=>''.($arrTradeInfo['merchant_id']?$arrTradeInfo['merchant_id']:''),
            'terminal_id'=>''.($arrTradeInfo['terminal_id']?$arrTradeInfo['terminal_id']:''),
            'notify_url'=>$this->callBackApiUrl.'/AuthCode/AliQrCodeCallBack',
        );
        $arrResultInfo = $this->execute('alipay.trade.pay',$arrParams);
        if ($arrResultInfo['code'] != '10000'){
            $strMsg = '错误:'.$arrResultInfo['sub_code'].'('.$arrResultInfo['code'].'),'.$this->getMsg($arrResultInfo['sub_code']);
            return Array($strMsg,0);
        }
        $arrResultInfo['trade_status'] = $arrResultInfo['msg']=='Success'?'TRADE_FINISHED':'TRADE_FAIL';
        //out_trade_no,trade_no
        //{"code":"10000","msg":"Success","buyer_logon_id":"181****1712","buyer_pay_amount":"0.01","buyer_user_id":"2088902023047586","fund_bill_list":[{"amount":"0.01","fund_channel":"PCREDIT"}],"gmt_payment":"2017-09-06 14:35:42","invoice_amount":"0.01","out_trade_no":"ZFBZF17090614331321400064779","point_amount":"0.00","receipt_amount":"0.01","total_amount":"0.01","trade_no":"2017090621001004580268243206","method":"alipay.trade.pay","order_num":"ZFBZF17090614331321400064779","order_id":617}
        return Array('成功',1,$arrResultInfo);
    }
    //创建一个APP支付
    //https://doc.open.alipay.com/docs/doc.htm?spm=a219a.7386797.0.0.QG3SQU&source=search&treeId=193&articleId=105465&docType=1
    public function getAppPay($arrTradeInfo){
        $arrSysParams = Array(
            'version'=>$this->version,
            'app_id'=>'2017041106651366',
            'method'=>'alipay.trade.app.pay',
            'format'=>'JSON',
            'charset'=>$this->charset,
            'sign_type'=>$this->signType,
            'timestamp'=>date('Y-m-d H:i:s'),
            'biz_content'=>json_encode(Array(
                'subject'=>'事业通会员充值',
                'body'=>'',
                'out_trade_no'=>$arrTradeInfo['pay_num'],
                'total_amount'=>$arrTradeInfo['money'],
                'product_code'=>'QUICK_MSECURITY_PAY',
            ),JSON_UNESCAPED_UNICODE),
            'passback_params'=>urlencode(json_encode([
                'merchant_id'=>'193',
                'user_id'=>$arrTradeInfo['user_id'],
                'inpour_id'=>$arrTradeInfo['id'],
                'pay_num'=>$arrTradeInfo['pay_num'],
            ])),
            'notify_url'=>$this->callBackApiUrl.'/AuthCode/AliQrCodeCallBack',
        );
        //集合主要参所有参数完毕
        $strSign = $this->getSignContentData($arrSysParams,'alipay');//得到需要签名的URL字符串
        $arrSysParams['sign'] = $this->RsaSign($strSign,$this->privateKey);
        foreach ($arrSysParams as &$v){
            $v = urlencode($v);
        }
        return Array('success',1,$arrSysParams);
    }


}
