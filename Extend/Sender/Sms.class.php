<?php
namespace Spartan\Extend\Sender;

defined('APP_NAME') OR exit('404 Not Found');

class Sms implements SmsSender {
    public $arrMobile = Array();
    public $body = '';
    public $arrErrors = Array();
    public $arrResult = Array();//发送结果的消息，成功或是失败
    private $arrConfig = [];
    /** @var $clsHttp null|\Spartan\Driver\Http\Curl  */
    public $clsHttp = null;
    public $callBack = null;

    public function __construct($_arrConfig = []){
        !isset($_arrConfig['PROTOCOL']) && $_arrConfig['PROTOCOL'] = 'http://';
        !isset($_arrConfig['SERVER']) && $_arrConfig['SERVER'] = '';
        !isset($_arrConfig['USER_NAME']) && $_arrConfig['USER_NAME'] = '';
        !isset($_arrConfig['PASS_WORD']) && $_arrConfig['PASS_WORD'] = '';
        !isset($_arrConfig['PORT']) && $_arrConfig['PORT'] = 80;
        !isset($_arrConfig['INTERVAL']) && $_arrConfig['INTERVAL'] = 3;
        !isset($_arrConfig['CHARSET']) && $_arrConfig['CHARSET'] = 'utf-8';
        !isset($_arrConfig['DEBUG']) && $_arrConfig['DEBUG'] = true;
        !isset($_arrConfig['ACTION']) && $_arrConfig['ACTION'] = '';//发送动作
        $this->arrConfig = $_arrConfig;
        $this->callBack = $this->getCallBack();
        $this->clsHttp = \Spt::getInstance('Spartan\\Driver\\Http\\Curl');
    }

    /**
     * @param $mobile
     * @return $this
     */
    public function setMobile($mobile){
        !is_array($mobile) && $mobile = [$mobile];
        $this->arrMobile = array_merge($this->arrMobile,$mobile);
        return $this;
    }

    /**
     * 设置发送内容
     * @param string $strBody
     * @return $this
     */
    public function setBody($strBody = ''){
        if (strtolower($this->arrConfig['CHARSET']) != 'utf-8'){
            $strBody = iconv('UTF-8','GBK//IGNORE',$strBody);
        }
        $this->body = $strBody;
        return $this;
    }

    /**
     * @param $name
     * @param string $value
     * @return $this
     */
    public function setConfig($name,$value = ''){
        if (is_array($name)){
            $this->arrConfig = array_merge($this->arrConfig,$name);
        }else{
            $this->arrConfig[$name] = $value;
        }
        return $this;
    }

    /**
     * @param $fun
     * @return $this
     */
    public function setCallBack($fun){
        $this->callBack = $fun;
        return $this;
    }

    /**
     * @return array
     */
    public function getErrors(){
        return $this->arrErrors;
    }

    /**
     * @return array
     */
    public function getResult(){
        return $this->arrResult;
    }

	/**
	 * 发送动作
	 * @return array
	 */
	public function send(){
		if (!$this->arrMobile || !$this->body) {
			return Array('手机号码或内容为空',0);
		}
        foreach ($this->arrMobile as $key=>$mobile) {
            unset($this->arrMobile[$key]);
		    if ($this->arrConfig['DEBUG'] == true){
                $this->arrResult[$mobile] = Array(true,'测试发送成功');
                return Array('测试发送成功',1);
            }
            $strMessageInfo = $this->clsHttp->send(
                $this->arrConfig['PROTOCOL'].$this->arrConfig['SERVER'].':'.$this->arrConfig['PORT'].$this->arrConfig['ACTION'],
                $this->getSendInfo($mobile),
                'POST',
                'html'
            );
            if (is_callable($this->callBack)){
                $callBackFun = $this->callBack;
                $this->arrResult[$mobile] = Array(
                    $strMessageInfo,$callBackFun($strMessageInfo)
                );
            }
            count($this->arrMobile) > 1 && sleep($this->arrConfig['INTERVAL']);
		}
		return Array('success',1);
	}

    /**
     * 得到一个发送内容
     * @param $mobile
     * @return mixed|string
     */
	public function getSendInfo($mobile){
        $arrBody = Array(
            'sdk.entinfo.cn'=>"SN=".
                $this->arrConfig['USER_NAME'].
                "&PWD=".
                strtoupper(md5($this->arrConfig['USER_NAME'].$this->arrConfig['PASS_WORD'])).
                "&Mobile={$mobile}&Content=".$this->body.'&ext=&stime=&rrid=',
        );
        return isset($arrBody[$this->arrConfig['SERVER']])?$arrBody[$this->arrConfig['SERVER']]:'';
    }

    /**
     * 判断是否成功的处理函数
     * @return mixed|null
     */
    public function getCallBack(){
	    $arrFunction = Array(
	        'sdk.entinfo.cn'=>function($body){
                $strPreg = "/<string.*?>(.*?)<\/string>/";
                preg_match($strPreg, $body,$matches);
                $body = isset($matches[1])?$matches[1]:0;
                return stripos($body,'-')===false && $body > 0?1:0;
            }
        );
	    return isset($arrFunction[$this->arrConfig['SERVER']])?$arrFunction[$this->arrConfig['SERVER']]:null;
    }
}