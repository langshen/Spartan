<?php
namespace Spartan\Lib;

defined('APP_NAME') OR die('404 Not Found');

class Controller{

    /** @var \Spartan\Lib\View */
    protected $view;
    /** @var \Spartan\Lib\Request */
    protected $request;
    /** @var \Spartan\Lib\Response */
    protected $response;

    public function __construct($_arrConfig = []){
        $this->request = Request::instance($_arrConfig);
        $this->response = Response::instance($_arrConfig);
        $this->view    = View::instance($_arrConfig);
        $this->view->init();
    }
    public function _empty(){
        return view('hello, 404啦~，'.config('URL').' 未能显示。');
    }

    /**
     * 加载模板输出
     * @access protected
     * @param  string $template 模板文件名
     * @param  array  $vars     模板输出变量
     * @param  array  $config   模板参数
     * @return mixed
     */
    protected function fetch($template = '', $vars = [], $config = [])
    {
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
    public function get($name = '',$default = ''){
        return isset($this->view->{$name})?$this->view->{$name}:$default;
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param  mixed $name  要显示的模板变量
     * @param  mixed $value 变量的值
     * @return $this
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
     * @return void
     */
    protected function success($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
        }
        $result = [
            'code' => 1,
            'msg'  => $msg,
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];
        $type = $this->request->isAjax()?'json':'html';
        // 把跳转模板的渲染下沉，这样在 response_send 行为里通过getData()获得的数据是一致性的格式
        if ('html' == strtolower($type)) {
            $type = 'jump';
        }
        $this->response->create($result, $type)
            ->header($header)
            ->options(['jump_template' => FRAME_PATH.'Tpl/dispatch_jump.tpl'])->send();
    }

    /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param  mixed     $msg 提示信息
     * @param  string    $url 跳转的URL地址
     * @param  mixed     $data 返回的数据
     * @param  integer   $wait 跳转等待时间
     * @param  array     $header 发送的Header信息
     * @return void
     */
    protected function error($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        $type = $this->request->isAjax()?'json':'html';
        if (is_null($url)) {
            $url = $this->request->isAjax() ? '' : 'javascript:history.back(-1);';
        }
        $result = [
            'code' => 0,
            'msg'  => $msg,
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];
        if ('html' == strtolower($type)) {
            $type = 'jump';
        }
        $this->response->create($result, $type)
            ->header($header)
            ->options(['jump_template' => FRAME_PATH.'Tpl/dispatch_jump.tpl'])->send();
    }

    /**
     * URL重定向
     * @access protected
     * @param  string         $url 跳转的URL表达式
     * @param  array|integer  $params 其它URL参数
     * @param  integer        $code http code
     * @param  array          $with 隐式传参
     * @return void
     */
    protected function redirect($url, $params = [], $code = 302, $with = [])
    {
        if (is_integer($params)) {
            $code   = $params;
            $params = [];
        }
        $this->response->create($url, 'redirect', $code)->params($params)->with($with)->send();
    }

    /**
     * 返回封装后的API数据到客户端
     * @access protected
     * @param  mixed     $data 要返回的数据
     * @param  integer   $code 返回的code
     * @param  mixed     $msg 提示信息
     * @param  array     $header 发送的Header信息
     * @return void
     */
    protected function result($data, $code = 0, $msg = '', array $header = [])
    {
        $data = [
            'code' => $code,
            'msg'  => $msg,
            'time' => time(),
            'data' => $data,
        ];
        $this->response->create($data, 'json')->header($header)->send();
    }
}
