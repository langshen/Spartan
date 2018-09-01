<?php
namespace Spartan\Lib;

defined('APP_NAME') OR exit('404 Not Found');

class Sender {
    private $arrConfig = [];
    private $type = '';
    private $arrInstance = [];
	/**
	 * 取得数据库类实例
	 * @param array $arrConfig
	 * @return \Spartan\Lib\Sender
	 */
	public static function instance($arrConfig = []) {
	    !isset($arrConfig['type']) && $arrConfig['type'] = 'sms';
        $arrConfig['type'] != 'email' && $arrConfig['type'] = 'sms';
		return \Spt::getInstance(__CLASS__,$arrConfig);
	}

	public function __construct($_arrConfig = []){
        $this->arrConfig = $_arrConfig;
        $this->{$this->arrConfig['type']}();
    }

    /**
     * 得到一个Sms发送者
     * @param array $arrConfig
     * @return $this
     */
    public function sms($arrConfig = []){
        $this->type = 'sms';
        $tmpConfig = C('SMS');
        !is_array($tmpConfig) && $tmpConfig = [];
        $arrConfig = array_merge($this->arrConfig,$tmpConfig,$arrConfig);
        $this->arrInstance['sms'] = \Spt::getInstance('Spartan\\Driver\\Sender\\Sms',$arrConfig);
        return $this;
    }

    /**
     * 得到一个邮件发送者
     * @param array $arrConfig
     * @return $this
     */
    public function email($arrConfig = []){
        $this->type = 'email';
        $tmpConfig = C('EMAIL');
        !is_array($tmpConfig) && $tmpConfig = [];
        $arrConfig = array_merge($this->arrConfig,$tmpConfig,$arrConfig);
        $this->arrInstance['email'] = \Spt::getInstance('Spartan\\Driver\\Sender\\Mailer',$arrConfig);
        return $this;
    }


    public function send(){
        if ($this->type == 'sms'){
            return $this->arrInstance[$this->type]->send();
        }else{
            return $this->arrInstance[$this->type]->sendMail();
        }
    }

    /**
     * @return array
     */
    public function getErrors(){
        return $this->arrInstance[$this->type]->getErrors();
    }

    /**
     * @return array
     */
    public function getResult(){
        return $this->arrInstance[$this->type]->getResult();
    }

    /**
     * 设置需要发送的手机
     * @param string $name
     * @return $this
     */
    public function setType($name = 'sms'){
        $this->type = $name;
        return $this;
    }

    /**
     * 设置需要发送的手机
     * @param string|array $mobile
     * @return $this
     */
    public function setMobile($mobile){
        $this->arrInstance['sms']->setMobile($mobile);
        return $this;
    }

    /**
     * 设置需要发送的邮件
     * @param string|array $strEmail
     * @return $this
     */
    public function setEmail($strEmail){
        $this->arrInstance['email']->setReceiver($strEmail);
        return $this;
    }

    /**
     * 设置发送内容
     * @param string $strBody
     * @return $this
     */
    public function setBody($strBody = ''){
        $this->arrInstance['sms']->setBody($strBody);
        return $this;
    }

    /**
     * 设置发送内容
     * @param string $strTitle
     * @param string $strBody
     * @param string $attachment
     * @return $this
     */
    public function setMailInfo($strTitle,$strBody = '',$attachment = ''){
        $this->arrInstance['email']->setMailInfo($strTitle,$strBody,$attachment);
        return $this;
    }
    /**
     * 设置回调函数
     * @param mixed $name
     * @param mixed $value
     * @return $this
     */
    public function setConfig($name,$value = ''){
        $this->arrInstance[$this->type]->setConfig($name,$value);
        return $this;
    }

    /**
     * 设置回调函数
     * @param mixed $fun
     * @return $this
     */
    public function setCallBack($fun){
        $this->arrInstance[$this->type]->setCallBack($fun);
        return $this;
    }

} 