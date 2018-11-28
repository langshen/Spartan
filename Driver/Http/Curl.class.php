<?php
namespace Spartan\Driver\Http;

defined('APP_NAME') OR exit('404 Not Found');

class Curl{
	private $curlHandle = null;
	private $content = null;
	private $headers = null;
	private $config = [];
	private $openCookie = false;//是否开启COOKIES
	private $cookies = '';//开启COOKIES时的变量

    /**
     * Curl constructor.
     * @param array $config
     */
	public function __construct($config = []){
		!function_exists('curl_init') && die('CURL_INIT_ERROR');
		$config = array_change_key_case($config,CASE_LOWER);
		$this->config = $config;
		$this->init();
	}

    /**
     *
     */
	private function init(){
		$this->curlHandle = curl_init();
		$options = array(
			CURLOPT_RETURNTRANSFER => true,//结果为文件流
			CURLOPT_TIMEOUT => 30,//超时时间，为秒。
			CURLOPT_HEADER => true,//是否需要头部信息，如果去掉头部信息，请求会快很多。
			CURLOPT_FRESH_CONNECT => true,//每次请求都是新的，不缓存
			CURLINFO_HEADER_OUT => true,//启用时追踪句柄的请求字符串。
			CURLOPT_FORBID_REUSE => true,//在完成交互以后强迫断开连接，不能重用
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,//强制使用 HTTP/1.1
			CURLOPT_ENCODING => 'gzip,deflate',//是否支持压缩
			CURLOPT_HTTPHEADER => array('Expect:100-continue'),//大于1024K
			CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
		);
		if (isset($this->config['referer']) && $this->config['referer']){
		    $options['CURLOPT_REFERER'] = $this->config['referer'];
        }
		foreach ($options as $key => $value){
			$this->setOpt($key,$value);
		}
	}

    /**
     * 设置一个头部
     * @param $mixHeader
     */
	public function setHeader($mixHeader){
	    !is_array($mixHeader) && $mixHeader = [$mixHeader];
        curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $mixHeader);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
	public function setOpt($key,$value){
        curl_setopt($this->curlHandle,$key,$value);
        return $this;
    }

    /**
     * @param string $cookies
     * @return $this
     */
    public function startCookie($cookies='not null'){
        $this->openCookie = true;
        $cookies != 'not null' && $this->cookies = $cookies;
        if ($this->openCookie && $this->cookies){
            $this->setOpt(CURLOPT_COOKIE,$this->cookies);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getCookie(){
        return $this->cookies;
    }

	/**
	 * 关闭本次请求
	 */
	public function close(){
		curl_close($this->curlHandle);
		$this->init();
	}

    /**
     * @param string|array $key
     * @param $value
     * @return $this
     */
	public function setConfig($key,$value = null){
	    if (is_array($key)){
            $this->config = array_merge($this->config,$key);
        }else{
            $this->config[$key] = $value;
        }
        return $this;
    }

	/**提交请请求。
	 * @param $url
	 * @param string $postFields
	 * @param string $method
	 * @param string $dataType
	 * @return null
	 */
	public function send($url,$postFields='',$method='GET', $dataType='json'){
	    if (isset($this->config['data_type']) && $this->config['data_type']){
	        $dataType = $this->config['data_type'];
        }
	    if (stripos($method,'.') > 0){
            list($method,$postType) = explode('.',$method);
        }else{
            $postType = '';
        }
		if($method == 'POST'){
			curl_setopt($this->curlHandle, CURLOPT_POST, TRUE);
			if($postFields){
			    is_array($postFields) && $postFields = $this->toUrl($postFields);
				curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $postFields);
			}
            if ($postType == 'JSON'){
                curl_setopt($this->curlHandle, CURLOPT_HEADER, true);
                curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, Array('Content-Type: application/json; charset=utf-8'));
            }
		}else{
			curl_setopt($this->curlHandle, CURLOPT_POST, false);
		}
		if (stripos($url,'https://')===0){
			curl_setopt($this->curlHandle,CURLOPT_SSL_VERIFYPEER, false); //不验证证书下同
			curl_setopt($this->curlHandle,CURLOPT_SSL_VERIFYHOST, false); //
		}
		curl_setopt($this->curlHandle,CURLOPT_URL,$url);
		$data = explode("\r\n\r\n",curl_exec($this->curlHandle));
		//var_dump($data);
		foreach ($data as $v){
            $this->headers = array_shift($data);
            if (stripos($this->headers,'HTTP/1.1 200 OK')===0 || ((stripos($this->headers,'Content-Type:')>0) &&
                stripos($this->headers,'HTTP/1.1 301 Moved')===false)
            ){
                break;
            }
        }
        $this->content = implode("\r\n\r\n",$data);
        //$requestInfo = curl_getinfo($this->curlHandle);print_r($requestInfo);print_r($data);die();
		if(stripos($this->headers,'charset=GBK')!==false){
            $this->content = iconv('GBK','utf-8//IGNORE',$this->content);
        }
        if ($this->openCookie){
            preg_match("/set\-cookie:([^\r\n]*)/i", $this->headers, $matches);
            (isset($matches[1]) && $matches[1]) && $this->cookies = $matches[1];
        }
        if ($dataType == 'json'){
            $arrJson = json_decode($this->content,true);
            return is_null($arrJson)?$this->content:$arrJson;
        }else{
            return $this->content;
        }
	}

    /**
     * @param $arrPost
     * @return string
     */
	public function toUrl($arrPost){
        $arrTemp = [];
        foreach ($arrPost as $k=>$v){
            $arrTemp[] = $k . '='. (is_array($v)?json_encode($v):$v);
        }
        return implode('&',$arrTemp);
    }

    /**
     * 析构函数
     */
	public function __destruct(){
		$this->close();
		$this->curlHandle = null;
	}
}