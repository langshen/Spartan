<?php
namespace Spartan\Lib;

defined('APP_NAME') OR die('404 Not Found');

class Controller{

    /** @var \Spartan\Lib\View */
    protected $view;
    /** @var \Spartan\Lib\Response */
    protected $response;
    /** @var string 模版，Web为PC端，Mobile为手机端,默认为不分端 */
    protected $tplName = '';

    public function __construct($_arrConfig = []){
        $this->response = Response::instance($_arrConfig);
        $this->view    = View::instance($_arrConfig);
        $this->view->init();
    }

    /**
     * 一个空操作，默认显示的404页面
     * @return \Spartan\Driver\Response\View
     */
    public function _empty(){
        return $this->display('hello, 404啦~，'.config('URL').' 未能显示。');
    }

    public function request($name,$default){
        return request()->param($name,$default);
    }
    /**
     * 加载模板输出
     * @access protected
     * @param  string $template 模板文件名
     * @param  array  $vars     模板输出变量
     * @param  array  $config   模板参数
     * @return mixed
     */
    protected function fetch($template = '', $vars = [], $config = []){
        if (!$template && $this->tplName){
            $template = $this->tplName.'@'.\Spt::$arrConfig['CONTROL'].'@'.\Spt::$arrConfig['ACTION'];
        }elseif ($this->tplName && stripos($template,'@') === false){
            $template = $this->tplName.'@'.\Spt::$arrConfig['CONTROL'].'@'.$template;
        }
        return $this->view->fetch($template, $vars, $config);
    }

    /**
     * 渲染内容输出
     * @access protected
     * @param  string $content 模板内容
     * @param  array  $vars    模板输出变量
     * @param  array  $config  模板参数
     * @return mixed
     */
    protected function display($content = '', $vars = [], $config = [])
    {
        return $this->view->display($content, $vars, $config);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param  mixed $name  要显示的模板变量
     * @param  mixed $value 变量的值
     * @return $this
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);
        return $this;
    }

    /**
     * 视图过滤
     * @access protected
     * @param  Callable $filter 过滤方法或闭包
     * @return $this
     */
    protected function filter($filter)
    {
        $this->view->filter($filter);
        return $this;
    }

    /**
     * 初始化模板引擎
     * @access protected
     * @param  array|string $engine 引擎参数
     * @return $this
     */
    protected function engine($engine)
    {
        $this->view->engine($engine);
        return $this;
    }

    /**
     * 取得模板变量的值
     * @access public
     * @param string $name
     * @param string $default
     * @return mixed
     */
    protected function get($name = '',$default = ''){
        return isset($this->view->{$name})?$this->view->{$name}:$default;
    }

    /**
     * 返回URL的第几个或全部
     * @param int $number
     * @param string $default
     * @return string
     */
    protected function getUrl($number = 0,$default = ''){
        return getUrl($number,$default);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param  mixed $name  要显示的模板变量
     * @param  mixed $value 变量的值
     * @return $this|mixed
     */
    protected function set($name, $value = '')
    {
        $this->view->assign($name, $value);
        return $this;
    }

    /**
     * 操作成功跳转的快捷方法
     * @access protected
     * @param  mixed     $msg 提示信息
     * @param  string    $url 跳转的URL地址
     * @param  mixed     $data 返回的数据
     * @param  integer   $wait 跳转等待时间
     * @param  array     $header 发送的Header信息
     * @return mixed
     */
    protected function success($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
        }
        $result = [
            config('GET.API.CODE','code') => 1,
            config('GET.API.MSG','msg')  => $msg,
            config('GET.API.DATA','data') => $data,
            'url'  => $url,
            'wait' => $wait,
        ];
        $type = request()->isAjax()?'json':'html';
        // 把跳转模板的渲染下沉，这样在 response_send 行为里通过getData()获得的数据是一致性的格式
        if ('html' == strtolower($type)) {
            $type = 'jump';
        }
        $this->response->create($result, $type)
            ->header($header)
            ->options(['jump_template' => FRAME_PATH.'Tpl/dispatch_jump.tpl'])->send();
        exit(0);
    }

    /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param  mixed     $msg 提示信息
     * @param  string    $url 跳转的URL地址
     * @param  mixed     $data 返回的数据
     * @param  integer   $wait 跳转等待时间
     * @param  array     $header 发送的Header信息
     * @return mixed
     */
    protected function error($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        $type = request()->isAjax()?'json':'html';
        if (is_null($url)) {
            $url = request()->isAjax() ? '' : 'javascript:history.back(-1);';
        }
        $result = [
            config('GET.API.CODE','code') => 0,
            config('GET.API.MSG','msg')  => $msg,
            config('GET.API.DATA','data') => $data,
            'url'  => $url,
            'wait' => $wait,
        ];
        if ('html' == strtolower($type)) {
            $type = 'jump';
        }
        $this->response->create($result, $type)
            ->header($header)
            ->options(['jump_template' => FRAME_PATH.'Tpl/dispatch_jump.tpl'])->send();
        exit(0);
    }

    /**
     * URL重定向
     * @access protected
     * @param  string         $url 跳转的URL表达式
     * @param  array|integer  $params 其它URL参数
     * @param  integer        $code http code
     * @param  array          $with 隐式传参
     * @return mixed
     */
    protected function redirect($url, $params = [], $code = 302, $with = [])
    {
        redirect($url, $params, $code)->with($with)->send();
        exit();
    }

    /**
     * 输出一个下载请求
     * @param string  $filename 要下载的文件
     * @param string  $name 显示文件名
     * @return mixed
     */
    protected function download($filename, $name = '')
    {
        return download($filename, $name)->send();
    }

    /**
     * 输出一个获取xml对象实例
     * @param mixed   $data    返回的数据
     * @param integer $code    状态码
     * @param array   $header  头部
     * @param array   $options 参数
     * @return mixed
     */
    protected function xml($data = [], $code = 200, $header = [], $options = [])
    {
        return download($data, $code, $header, $options)->send();
    }

    /**
     * 输出一个Json对象实例
     * @param mixed   $data 返回的数据
     * @param integer $code 状态码
     * @param array   $header 头部
     * @param array   $options 参数
     * @return mixed
     */
    protected function json($data = [], $code = 200, $header = [], $options = [])
    {
        return json($data,$code,$header,$options)->send();
    }

    /**
     * 输出一个Jsonp对象实例
     * @param mixed   $data    返回的数据
     * @param integer $code    状态码
     * @param array   $header 头部
     * @param array   $options 参数
     * @return mixed
     */
    protected function jsonp($data = [], $code = 200, $header = [], $options = [])
    {
        return jsonp($data,$code,$header,$options)->send();
    }

    /**
     * 返回封装后的API数据到客户端
     * @access protected
     * @param  mixed     $data 要返回的数据
     * @param  integer   $code 返回的code
     * @param  mixed     $msg 提示信息
     * @param  array     $header 发送的Header信息
     * @return mixed
     */
    protected function api($msg = '', $code = 0,$data = [], array $header = [])
    {
        $data = [
            config('GET.API.CODE','code') => $code,
            config('GET.API.MSG','msg')  => $msg,
            config('GET.API.DATA','data') => $data,
            'time' => time(),
        ];
        return json($data,200,$header)->send();
    }

    /**
     * 快捷输出API数据到客户端
     * @param array|string $minxData 要返回的数据
     * @param int $intCode 状态
     * @param array $arrData 数据
     * @return mixed
     */
    protected function toApi($minxData,$intCode = 1,$arrData = []){
        $strMsg = '';
        if (!is_array($minxData)){
            $strMsg = $minxData;
        }else{
            if (isset($minxData[0])){
                $strMsg = $minxData[0];
            }
            if (isset($minxData[1])){
                $intCode = $minxData[1];
            }
            if (isset($minxData[2])){
                $arrData = $minxData[2];
            }
        }
        unset($minxData);
        return $this->api($strMsg,$intCode,$arrData);
    }

    /**
     * @param $data
     * 返回可执行的js脚本
     * @return mixed
     */
    protected function toJs($data){
        header('Content-Type:text/html; charset=utf-8');
        echo '<script language="javascript">';
        echo $data;
        exit('</script>');
        return;
    }

    /**
     * 批量提取寄存变量
     * @param string|mixed $mixName
     * @return array
     */
    public function getFieldData($mixName){
        $arrTempName = [];
        if (is_array($mixName)){
            $arrName = $mixName;
            foreach ($arrName as $key=>$value){
                $arrTempName[$key] = request()->param($key,$value);
            }
        }else{
            $arrName = explode(',',$mixName);
            $arrName = array_filter($arrName);
            foreach ($arrName as $value){
                $arrTempName[$value] = request()->param($value,'');
            }
        }
        unset($mixName);
        return $arrTempName;
    }
}
