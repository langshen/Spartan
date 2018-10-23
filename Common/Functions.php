<?php
/**
 * 返回当前框架版本
 * @return string
 */
function version(){
    return Spt::$version;
}
/**
 * 获取和设置配置参数
 * @param string|array  $name 参数名
 * @param mixed         $value 参数值或默认值
 * @return mixed
 */
function config($name = '', $value = null)
{
    if ((substr($name,4)==='get.' || is_null($value)) && is_string($name)) {
        return \Spt::getConfig($name,$value);
    }else{
        return \Spt::setConfig($name,$value);
    }
}

/**
 * Cookie管理
 * @param string|array  $name cookie名称，如果为数组表示进行cookie设置
 * @param mixed         $value cookie值
 * @param mixed         $option 参数
 * @return mixed
 */
function cookie($name, $value = '', $option = null)
{
    $clsCookie = \Spartan\Lib\Cookie::instance();
    if (is_array($name)) {// 初始化
        return $clsCookie->init($name);
    } elseif (is_null($name)) {// 清除
        return $clsCookie->clear($value);
    } elseif ('' === $value) {// 获取
        return 0 === strpos($name, '?')?
            $clsCookie->has(substr($name, 1), $option):
            $clsCookie->get($name);
    } elseif (is_null($value)) {// 删除
        return $clsCookie->delete($name);
    } else {// 设置
        return $clsCookie->set($name, $value, $option);
    }
}

/**
 * Session管理
 * @param string|array  $name session名称，如果为数组表示进行session设置
 * @param mixed         $value session值
 * @param string        $prefix 前缀
 * @return mixed
 */
function session($name, $value = '', $prefix = null)
{
    $clsSession = \Spartan\Lib\Session::instance();
    if (is_array($name)) {// 初始化
        return $clsSession->init($name);
    } elseif (is_null($name)) {// 清除
        return $clsSession->clear($value);
    } elseif ('' === $value) {// 判断或获取
        return 0 === strpos($name, '?')?
            $clsSession->has(substr($name, 1), $prefix):
            $clsSession->get($name, $prefix);
    } elseif (is_null($value)) {// 删除
        return $clsSession->delete($name, $prefix);
    } else {// 设置
        return $clsSession->set($name, $value, $prefix);
        //function_exists('session_commit') && session_commit();
    }
}

/**
 * 获取和设置语言
 * @param string|array  $name 参数名
 * @param mixed         $value 参数值或默认值
 * @return mixed
 */
function lang($name = '', $value = null)
{
    if (is_null($value) && is_string($name)) {
        return \Spt::getLang($name);
    }else{
        return \Spt::setLang($name,$value);
    }
}

/**
 * 获取Download对象实例
 * @param string  $filename 要下载的文件
 * @param string  $name 显示文件名
 * @param bool    $content 是否为内容
 * @param integer $expire 有效期（秒）
 * @return \Spartan\Driver\Response\Download
 */
function download($filename, $name = '', $content = false, $expire = 180)
{
    return Spartan\Lib\Response::instance()->create($filename, 'download')->name($name)->isContent($content)->expire($expire);
}

/**
 * 获取xml对象实例
 * @param mixed   $data    返回的数据
 * @param integer $code    状态码
 * @param array   $header  头部
 * @param array   $options 参数
 * @return \Spartan\Driver\Response\Xml
 */
function xml($data = [], $code = 200, $header = [], $options = [])
{
    return Spartan\Lib\Response::instance()->create($data, 'xml', $code, $header, $options);
}

/**
 * 获取Json对象实例
 * @param mixed   $data 返回的数据
 * @param integer $code 状态码
 * @param array   $header 头部
 * @param array   $options 参数
 * @return \Spartan\Driver\Response\Json
 */
function json($data = [], $code = 200, $header = [], $options = [])
{
    return \Spartan\Lib\Response::instance()->create($data, 'json', $code, $header, $options);
}

/**
 * 获取Jsonp对象实例
 * @param mixed   $data    返回的数据
 * @param integer $code    状态码
 * @param array   $header 头部
 * @param array   $options 参数
 * @return \Spartan\Driver\Response\Jsonp
 */
function jsonp($data = [], $code = 200, $header = [], $options = [])
{
    return \Spartan\Lib\Response::instance()->create($data, 'jsonp', $code, $header, $options);
}

/**
 * 获取Redirect对象实例
 * @param mixed         $url 重定向地址 支持Url::build方法的地址
 * @param array|integer $params 额外参数
 * @param integer       $code 状态码
 * @return \Spartan\Driver\Response\Redirect
 */
function redirect($url = [], $params = [], $code = 302)
{
    if (is_integer($params)) {
        $code   = $params;
        $params = [];
    }
    return \Spartan\Lib\Response::instance()->create($url, 'redirect', $code)->params($params);
}

/**
 * 渲染模板输出
 * @param string    $template 模板文件
 * @param array     $vars 模板变量
 * @param integer   $code 状态码
 * @param callable  $filter 内容过滤
 * @return \Spartan\Driver\Response\View
 */
function view($template = '', $vars = [], $code = 200, $filter = null)
{
    return \Spartan\Lib\Response::instance()->create($template, 'view', $code)->assign($vars)->filter($filter);
}

/**
 * 创建普通 Response 对象实例
 * @param mixed      $data   输出数据
 * @param int|string $code   状态码
 * @param array      $header 头信息
 * @param string     $type
 * @return \Spartan\Lib\Response
 */
function response($data = [], $code = 200, $header = [], $type = 'html')
{
    return \Spartan\Lib\Response::instance()->create($data, $type, $code, $header);
}

/**
 * 获取当前Request对象实例
 * @param array $_arrConfig   配置
 * @return \Spartan\Lib\Request
 */
function request($_arrConfig = [])
{
    return \Spartan\Lib\Request::instance($_arrConfig);
}

/**
 * 返回URL的第几个或全部
 * @param int $number
 * @param string $default
 * @return string
 */
function getUrl($number = 0,$default = ''){
    $strUrl = config('URL');
    if (!$strUrl){return $default;}
    $arrUrl = explode('/',$strUrl);
    return isset($arrUrl[$number])?$arrUrl[$number]:$default;
}

/**
 * 获取输入数据 支持默认值和过滤
 * @param string    $key 获取的变量名
 * @param mixed     $default 默认值
 * @param string    $filter 过滤方法
 * @return mixed
 */
function input($key = '', $default = null, $filter = '')
{
    if (0 === strpos($key, '?')) {
        $key = substr($key, 1);
        $has = true;
    }
    if ($pos = strpos($key, '.')) {// 指定参数来源
        $method = substr($key, 0, $pos);
        if (in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'route', 'param', 'request', 'session', 'cookie', 'server', 'env', 'path', 'file'])) {
            $key = substr($key, $pos + 1);
        } else {
            $method = 'param';
        }
    } else {// 默认为自动判断
        $method = 'param';
    }
    if (isset($has)) {
        return request()->has($key, $method, $default);
    } else {
        return request()->$method($key, $default, $filter);
    }
}

/**
 * 浏览器友好的变量输出
 * @param mixed     $var 变量
 * @param boolean   $echo 是否输出 默认为true 如果为false 则返回输出字符串
 * @param string    $label 标签 默认为空
 * @param  integer       $flags htmlspecialchars flags
 * @return string
 */
function dump($var, $echo = true, $label = null, $flags = ENT_SUBSTITUTE)
{
    $label = (null === $label) ? '' : rtrim($label) . ':';
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
    if (PHP_SAPI == 'cli') {
        $output = PHP_EOL . $label . $output . PHP_EOL;
    } else {
        if (!extension_loaded('xdebug')) {
            $output = htmlspecialchars($output, $flags);
        }
        $output = '<pre>' . $label . $output . '</pre>';
    }
    if ($echo) {
        echo($output);
        return '';
    }
    return $output;
}

/**
 * Url生成
 * @param string        $url 路由地址
 * @param string|array  $vars 变量
 * @param bool|string   $suffix 生成的URL后缀
 * @param bool|string   $domain 域名
 * @return string
 */
function url($url = '', $vars = '', $suffix = true, $domain = false)
{
    if ($domain){
        $url = $domain . $url;
    }
    if ($suffix){
        $url .= $suffix;
    }
    if ($vars){
        if (is_array($vars)){
            $temp = [];
            foreach ($vars as $k=>$v){
                $temp[] = $k . '=' . urlencode($v);
            }
            $vars = implode('&',$temp);
        }
        $url .= '?' . $vars;
    }
    return $url;
}

/**
 * 实例化数据库类
 * @param array $_arrConfig   配置
 * @return \Spartan\Lib\Db
 */
function db($_arrConfig = [])
{
    return \Spartan\Lib\Db::instance($_arrConfig);
}

/**
 * 实例Http请求类
 * @param array $_arrConfig   配置
 * @return \Spartan\Lib\Http
 */
function http($_arrConfig = [])
{
    return \Spartan\Lib\Http::instance($_arrConfig);
}

/**
 * 快捷实例化Model管理器
 * @param string $modelName 初始化的模型类，为空时为主类
 * @param array $arrData 初始化的数据
 * @return \Spartan\Lib\Model|mixed
 */
function model($modelName = '',$arrData = [])
{
    $clsModel = \Spartan\Lib\Model::instance($arrData);
    if (!$modelName){
        return $clsModel;//返回Model管理类
    }
    $clsModel = $clsModel->getModel($modelName);
    if (!is_object($clsModel)){
        \Spt::halt("类名：{$modelName}不存在。");
    }
    return $clsModel->setData($arrData);//返回指定类
}

/**
 * 快捷实例化Model管理器
 * @param string $modelName 初始化的模型类，为空时为主类
 * @param array $arrData 初始化的数据
 * @return \Spartan\Driver\Model\Table|\Spartan\Lib\Model|mixed
 */
function dal($modelName = '',$arrData = [])
{
    $clsModel = \Spartan\Lib\Model::instance($arrData);
    $clsModel = $clsModel->getTable($modelName);
    if (!is_object($clsModel)){
        \Spt::halt("表模型：{$modelName}不存在。");
    }
    return $clsModel->setData($arrData);//返回指定类
}
/**
 * 实例化验证器
 * @param array $rules 规则
 * @param array $message 提示信息
 * @param array $field 字段
 * @return \Spartan\Lib\Validate
 */
function validate(array $rules = [], array $message = [], array $field = [])
{
    return \Spartan\Lib\Validate::make($rules,$message,$field);
}

/**
 * @param $arrData
 */
function valid(&$arrData,&$errInfo = ''){
    $rules = $message = $field = $data = [];
    foreach ($arrData as $k=>$v){
        if (stripos($k,'.')>0){
            list($method,$k) = explode('.',$k);
        }else{
            $method = 'param';
        }
        if (!is_array($v) || $method == 'var'){
            $data[$k] = $v;
        }else{
            $data[$k] = request()->$method($k);
            $rules[$k] = isset($v[0])?$v[0]:'';
            $message[$k] = isset($v[1])?$v[1]:'';
            (isset($v[2]) && is_null($data[$k])) && $data[$k] = $v[2];
        }
    }
    $clsValidate = validate($rules,$message);
    $arrData = $data;//新的值
    if (!$clsValidate->check($data)){
        $errInfo = $clsValidate->getError();
        return false;
    }else{
        return true;
    }
}

/**
 * @param $strEmail
 * @return false|int
 */
function isEmail($strEmail){
    return filter_var($strEmail, FILTER_VALIDATE_EMAIL)?true:false;
}

/**
 * @param $strMobile
 * @return false|int
 */
function isMobile($strMobile){
    return preg_match('/^1[3-9][0-9]\d{8}$/', $strMobile);
}

/**
 * 数字转大写
 * @param $number
 * @return mixed
 */
function toUpperNumber($number){
    $cnynums = array("〇","一","二","三","四","五","六","七","八","九");
    return str_replace(array_keys($cnynums),$cnynums,$number);
}
/**
 * 金额转中文大写
 * @param $money
 * @return mixed
 */
function toChineseNumber($money){
    $money = number_format(round($money,2),2,'.','');
    $cnynums = array("零","壹","贰","叁","肆","伍","陆","柒","捌","玖");
    $cnyunits = array("圆","角","分");
    $cnygrees = array("拾","佰","仟","万","拾","佰","仟","亿");
    list($int,$dec) = explode(".",$money,2);
    $dec = array_filter(array($dec[1],$dec[0]));
    $ret = array_merge($dec,array(implode("",cnyMapUnit(str_split($int),$cnygrees)),""));
    $ret = implode("",array_reverse(cnyMapUnit($ret,$cnyunits)));
    return str_replace(array_keys($cnynums),$cnynums,$ret);
}

/**
 * 转换的数字顺序
 * @param $list
 * @param $units
 * @return array
 */
function cnyMapUnit($list,$units) {
    $ul = count($units);
    $xs = array();
    foreach (array_reverse($list) as $x) {
        $l = count($xs);
        if ($x != "0" || !($l % 4)){
            $l = ($l-1) % $ul;
            $n=($x=='0'?'':$x).(isset($units[$l])?$units[$l]:'');
        }else{
            $n = isset($xs[0][0]) && is_numeric($xs[0][0])?$x:'';
        }
        array_unshift($xs,$n);
    }
    return $xs;
};