<?php
namespace Spartan\Driver\Http;

defined('APP_NAME') OR exit('404 Not Found');

class Curl{
	private $curlHandle = null;
	private $content = null;
	private $headers = null;
	private $headersOut = null;
	private $config = [];
	private $openCookie = false;//是否开启COOKIES
	private $cookies = [];//开启COOKIES时的变量
    private $arrHeader = [];//设置发送头

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
		$this->arrHeader = ['Expect:100-continue'];
		$options = array(
			CURLOPT_RETURNTRANSFER => true,//结果为文件流
			CURLOPT_TIMEOUT => 30,//超时时间，为秒。
			CURLOPT_HEADER => true,//是否需要头部信息，如果去掉头部信息，请求会快很多。
			CURLOPT_FRESH_CONNECT => true,//每次请求都是新的，不缓存
			CURLINFO_HEADER_OUT => true,//启用时追踪句柄的请求字符串。
			CURLOPT_FORBID_REUSE => true,//在完成交互以后强迫断开连接，不能重用
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,//强制使用 HTTP/1.1
			CURLOPT_ENCODING => 'gzip,deflate',//是否支持压缩
			CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
		);
		if (isset($this->config['referer']) && $this->config['referer']){
		    $options[CURLOPT_REFERER] = $this->config['referer'];
        }
        if (isset($this->config['follow_location']) && $this->config['follow_location'] == 1){
            $options[CURLOPT_FOLLOWLOCATION] = 1;//递归的抓取http头中Location中指明的url
            $options[CURLOPT_MAXREDIRS] = 5;//递归的次数
        }
		foreach ($options as $key => $value){
			$this->setOpt($key,$value);
		}
	}

    /**
     * 设置一个头部
     * @param $mixHeader
     * @return $this;
     */
	public function setHeader($mixHeader){
	    if (!is_array($mixHeader)){
            $this->arrHeader[] = $mixHeader;
        }else{
            $this->arrHeader = array_merge($this->arrHeader,$mixHeader);
        }
        return $this;
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
        $cookies != 'not null' && $this->setCookieString($cookies);
        if ($this->openCookie && $this->cookies){
            $this->setOpt(CURLOPT_COOKIE,$this->getCookieToString());
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getCookie(){
        return $this->cookies;
    }

    /**
     * @return string
     */
    public function getCookieToString(){
        $arrCookies = [];
        foreach ($this->cookies as $k=>$v){
            $arrCookies[] = trim($v)?trim($k).'='.trim($v):trim($k);
        }
        return implode(';',$arrCookies);
    }

    /**
     * @return $this
     */
    public function clearCookie(){
        $this->cookies = [];
        return $this;
    }

    /**
     * @return $this
     */
    public function clearHeader(){
        $this->arrHeader = ['Expect:100-continue'];
        return $this;
    }
    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setCookie($key,$value=''){
        if (!$value && stripos($key,'=') > 0){
            list($key,$value) = explode('=',$key);
        }
        $this->cookies[$key] = $value;
        return $this;
    }

    /**
     * @example __cfduid=d69c7ca7a4ceeabaab46d5101320cc47d1543934234;expires=Wed, 04-Dec-19 14:37:14 GMT;path=/;domain=.eunex.co;;Secure
     * @param $value
     * @return $this
     */
    public function setCookieString($value){
        $arrTemp = explode(';',trim($value));
        foreach ($arrTemp as $tempV){
            if (!$tempV){continue;}
            $tempV = explode('=',$tempV);
            $tempV[0] = trim($tempV[0]);
            if (count($tempV) > 1){
                $this->cookies[$tempV[0]] = trim($tempV[1]);
            }else{
                $this->cookies[$tempV[0]] = '';
            }
        }
        $this->cookies = array_unique(array_filter($this->cookies));
        return $this;
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
		if($method == 'POST' || $method == 'DELETE'){
            curl_setopt($this->curlHandle, CURLOPT_CUSTOMREQUEST,strtoupper($method));
			if($postFields){
			    if (is_array($postFields)){
			        if ($postType == 'JSON'){
                        $postFields = json_encode($postFields);
                    }else{
                        $postFields = $this->toUrl($postFields);
                    }
                }
				curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $postFields);
			}
            if ($postType == 'JSON'){
                curl_setopt($this->curlHandle, CURLOPT_HEADER, true);
                $this->setHeader('Content-Type: application/json; charset=utf-8');
            }
		}else{
			curl_setopt($this->curlHandle, CURLOPT_POST, false);
		}
		if (stripos($url,'https://')===0){
			curl_setopt($this->curlHandle,CURLOPT_SSL_VERIFYPEER, false); //不验证证书下同
			curl_setopt($this->curlHandle,CURLOPT_SSL_VERIFYHOST, false); //
		}
        curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, array_unique($this->arrHeader));
		curl_setopt($this->curlHandle,CURLOPT_URL,$url);
		$data = explode("\r\n\r\n",curl_exec($this->curlHandle));
		foreach ($data as $v){
            $this->headers = array_shift($data);
            if (stripos($this->headers,'HTTP/1.1 200 OK')===0 || ((stripos($this->headers,'Content-Type:')>0) &&
                stripos($this->headers,'HTTP/1.1 301 Moved')===false)
            ){
                break;
            }
        }
        $this->content = implode("\r\n\r\n",$data);
		$this->headersOut = curl_getinfo($this->curlHandle,CURLINFO_HEADER_OUT);
		if(stripos($this->headers,'charset=GBK')!==false){
            $this->content = iconv('GBK','utf-8//IGNORE',$this->content);
        }elseif(stripos($this->headers,'charset=gb2312')!==false){
            $this->content = iconv('gb2312','utf-8//IGNORE',$this->content);
        }
        if ($this->openCookie){
            preg_match_all("/set\-cookie:([^\r\n]*)/i", $this->headers, $matches);
            if (isset($matches[1]) && is_array($matches[1]) && $matches[1]){
                foreach ($matches[1] as $v){
                    $v = str_replace('HttpOnly','',$v);
                    $this->setCookieString($v);
                }
            }
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
     * 读取请求头
     * @return null
     */
    public function requestHeader(){
	    return $this->headers;
    }

    /**
     * 读取返回头
     * @return null
     */
    public function responseHeader(){
        return $this->headersOut;
    }

    /**
     * 析构函数
     */
	public function __destruct(){
		$this->close();
		$this->curlHandle = null;
	}
}