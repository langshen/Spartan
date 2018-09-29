<?php
namespace Spartan\Lib;

defined('APP_NAME') OR exit('404 Not Found');

class Model {
    public $arrRequest = [];//寄存的数据池
    public $arrConfig = [];//当前配置

    /**
     * Logic constructor.
     * @param $arrData
     */
    public function __construct($arrData = []){
        $this->setData($arrData);
    }

    /**
     * 实例化当前主模型类
     * @param array $arrData '初始化的setData
     * @return object | Model
     */
    public static function instance($arrData = []){
        return \Spt::getInstance(__CLASS__,$arrData);
    }

    /**
     * 实例化一个静态调用的Model子类自己，可以理解为初始化自身
     * @param array $arrData '初始化的setData
     * @return object | Model
     */
    public static function init($arrData = []){
        return \Spt::getInstance(get_called_class())->setData($arrData);
    }

    /**
     * 实例化一个Table的表类
     * @param Object|string $objClass
     * @return mixed
     */
    public function getTable($objClass){
        $clsTable = $this->getModel($objClass,'Table');
        if (!is_object($clsTable)){
            \Spt::halt(['表类不存在,请生成:'.$objClass,$clsTable]);
        }
        return $clsTable;
    }

    /**
     * 实例化一个Model类或指定名称的Model子类
     * @param $objClass \stdClass|string
     * @param $strType string
     * @return mixed|Model|StdCalss
     */
    public function getModel($objClass,$strType = 'Model'){
        if (is_object($objClass)){//是一个类
            return \Spt::setInstance(get_class($objClass),$objClass);
        }else{
            if (stripos($objClass,'_') > 0){
                $arrTempClass = explode('_',$objClass);
                array_walk($arrTempClass,function(&$v){$v = ucfirst($v);});unset($v);
                $strPathName = array_shift($arrTempClass);
                $strClassName = ucfirst($strType).'\\'.$strPathName.'\\'.implode('',$arrTempClass);
            }else{
                $strClassName = trim(str_ireplace('/','\\',$objClass),'\\');
                if (substr($strClassName,0,strlen($strType)) != $strType){
                    $strClassName = $strType . '\\' . $strClassName;
                }
            }
            if (!class_exists($strClassName)){
                print_r($strClassName.' not exists.');
                return $strClassName;
            }else{
                return \Spt::getInstance($strClassName);
            }
        }
    }

    /**
     * 设置一个共用的寄存的数据池
     * @param array $arrData
     * @return mixed|Model
     */
    public function setData($arrData = []){
        is_array($arrData) && $this->arrRequest = array_merge($this->arrRequest,$arrData);
        return $this;
    }

    /**
     * 获取调寄存数据池的值
     * @param string $name 要获取的$this->request_data[$key]数据
     * @param string $default 当$key数据为空时，返回$value的内容
     * @return mixed
     */
    public function getData($name = '',$default = null){
        is_array($name) && list($name,$default) = $name;
        if (!$name && !$default){
            return $this->arrRequest;
        }
        return isset($this->arrRequest[$name])?$this->arrRequest[$name]:$default;
    }

    /**
     * 批量提取寄存变量
     * @param string|mixed $arrName
     * @return array
     */
    public function getFieldData($mixName){
        $arrTempName = [];
        if (is_array($mixName)){
            $arrName = $mixName;
            foreach ($arrName as $key=>$value){
                $arrTempName[$key] = $this->getData($key,$value);
            }
        }else{
            $arrName = explode(',',$mixName);
            foreach ($arrName as $value){
                $arrTempName[$value] = $this->getData($value,null);
            }
        }
        unset($mixName);
        return $arrTempName;
    }

    /**
     * 按表单顺序，选择第一个有用的用户
     * @param $mixValue
     * @param null $backFun
     * @return mixed|string
     */
    public function choose($mixValue,$backFun = null){
        !is_array($mixValue) && $mixValue = [$mixValue];
        $value = '';
        foreach ($mixValue as $k=>$v){
            if (is_callable($backFun)){
                if ($backFun($v,$k) == true){
                    $value = $v;break;
                }
            }else{
                if (!$v){$value = $v;break;}
            }
        }
        return $value;
    }
    /**
     * 重置配置
     * @return $this
     */
    public function reset(){
        $this->arrConfig = [];
        return $this;
    }

    /**
     * 设置一个配置变量
     * 'auto'=>true,'array'=>true,'count'=>true
     * 自动使用过滤   返回数据格式  返回条数
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
     * 返回当前配置信息
     * @param string $name
     * @return array|mixed|string
     */
    public function getConfig($name = ''){
        return !$name?$this->arrConfig:(isset($this->arrConfig[$name])?$this->arrConfig[$name]:'');
    }


    /**
     * 简单的错误记录器，使用SQL
     * //'level','class','info','err'
     * @param null $arrInfo
     */
    final public function sysError($arrInfo = null){
        $arrData = Array(
            'level' => isset($arrInfo['level'])?$arrInfo['level']:0,
            'class' => isset($arrInfo['class'])?$arrInfo['class']:'',
            'info'  => isset($arrInfo['info'])?$arrInfo['info']:'',
            'err'   => isset($arrInfo['err'])?$arrInfo['err']:'',
            'add_time'   => date('Y-m-d H:i:s',time()),
        );
        $arrData['err'] .= PHP_EOL.
            "getData:".json_encode($this->getData(),JSON_UNESCAPED_UNICODE).PHP_EOL.
            "SQL:".json_encode(db()->getAllSql(),JSON_UNESCAPED_UNICODE).PHP_EOL.
            "SQL ERR:".json_encode(db()->getError(),JSON_UNESCAPED_UNICODE);
        db()->insert('system_errors',$arrData);
    }


} 