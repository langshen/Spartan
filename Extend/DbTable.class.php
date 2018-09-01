<?php
namespace Spartan\Extend;

defined('APP_NAME') OR die('404 Not Found');

/**
 * 生成表结构
 * Class DbTable
 * @package Spartan\Extend
 */
class DbTable {

    public $config = [//配置文件
        'save_path'=>APP_ROOT,//保存目录,以/结尾
        'name_space'=>'Table',//命名空间
    ];
    /**
     * @param array $arrConfig
     * @return DbTable
     */
    public static function instance($arrConfig = []) {
        return \Spt::getInstance(__CLASS__,$arrConfig);
    }

    /**
     * DbTable constructor.
     * @param array $_arrConfig
     */
    public function __construct($_arrConfig = []){
        $this->config = array_merge($this->config,$_arrConfig);
    }

    /**
     * 返回所有表的结构
     * @param int $intLimit
     * @param int $intPage
     * @return array|string
     */
    public function tableList($intLimit = 0,$intPage = 1){
        $intLimit < 1 && $intLimit = max(0,intval(request()->param('limit',0)));
        $intPage <= 1 && $intPage = max(0,intval(request()->param('page',1)));
        $arrTable = Db()->getTables('',$intLimit,$intPage);
        foreach ($arrTable['data'] as &$value){
            $clsTemp = model()->getModel($value['name'],$this->config['name_space']);
            $value['status'] = is_object($clsTemp)?'已生成':'未建立';
        }
        unset($value);
        return Array('success',1,$arrTable);
    }

    /**
     * 返回指定表的所有字段
     * @param string $strTableName
     * @return array|string
     */
    public function tableInfo($strTableName = ''){
        !$strTableName && $strTableName = strip_tags(request()->param('table',''));
        if (!$strTableName){
            return Array('表名不能为空。',0);
        }
        $clsDalTable = model()->getModel($strTableName,$this->config['name_space']);
        $arrCondition = $arrRequired = [];
        $strComment = '';
        if (is_object($clsDalTable)){
            $arrCondition = $clsDalTable->arrCondition;
            $arrRequired = $clsDalTable->arrRequired;
            $strComment = $clsDalTable->strComment;
        }
        $arrInfo = db()->getFullFields($strTableName);
        if (!$arrInfo){
            return Array('没有找到表的相关信息。',0);
        }
        foreach ($arrInfo as $k=>&$v){
            $arrTempRequired = array_key_exists($k,$arrRequired)?$arrRequired[$k]:['','',['',''],'',''];
            count($arrTempRequired) && $arrTempRequired[] = '';
            $v = Array(
                'condition'=>array_key_exists($k,$arrCondition)?'1':'',
                'required'=>$arrTempRequired,
                'name'=>$k,
                'type'=>$v[0],
                'long'=>$v[1] . ',' .$v[2],
                'collation'=>$v[3],
                'pri'=>$v[4],
                'null'=>$v[6],
                'default'=>$v[7],
                'comment'=>$v[8],
            );
        }
        unset($v);
        return Array('success',1,['name'=>$strTableName,'comment'=>$strComment,'fields'=>$arrInfo]);
    }

    /**
     * 提交一个表的配置，生成对应的类
     * @param string $strTableName 表名
     * @param array $arrData 配置信息
     * @return array|string
     */
    public function tableCreate($strTableName = '',$arrData = []){
        $arrData = array_merge(Array(
            'condition'=>request()->param('condition',[]),
            'required'=>request()->param('required',[]),
            'function'=>request()->param('function',[]),
            'argv1'=>request()->param('argv1',[]),
            'argv2'=>request()->param('argv2',[]),
            'tip'=>request()->param('tip',[]),
            'default'=>request()->param('default',[]),
        ),$arrData);
        if (!$arrData['condition']){
            return Array('至少勾选一个查询条件。',0);
        }
        if ($arrData['condition'] && !is_array($arrData['condition'])){
            return Array('查询条件condition应该是个数组。',0);
        }
        if ($arrData['required'] && !is_array($arrData['required'])){
            return Array('必填项required应该是个数组。',0);
        }
        !$strTableName && $strTableName = strip_tags(request()->param('table_name',''));
        if (!$strTableName){
            return Array('表名不能为空。',0);
        }
        $arrFields = db()->getFullFields($strTableName);
        if (!$arrFields){
            return Array('查询表字段失败。',0);
        }
        $arrTableInfo = db()->getTables($strTableName);
        if (!isset($arrTableInfo['data']) || !$arrTableInfo['data'] || !isset($arrTableInfo['data'][0])){
            return Array('查询表的信息失败。',0);
        }
        $arrTableInfo = $arrTableInfo['data'][0];
        $arrTable = db()->showCreateTable($strTableName);
        if (!$arrTable || !isset($arrTable[1])){
            return Array('查询表SQL信息失败。',0);
        }
        $arrTableInfo['sql'] = $arrTable[1];
        $arrTableInfo['prefix'] = $arrTable[2];
        return $this->tableSave($arrTableInfo,$arrFields,$arrData);
    }

    /**
     * 生成数据表类
     * @param $taleInfo array 表信息
     * @param $tableField array 表字段信息
     * @param $arrConfig array 用户配置信息
     * @return array|string
     */
    public function tableSave($taleInfo,$tableField,$arrConfig){
        $arrVars = Array(
            'strTableName'=>$taleInfo['name'],
            'strTableSql'=>strstr($taleInfo['sql'],'('),
            'strAlias'=>'a',
            'strComment'=>$taleInfo['comment'],
            'strPrefix'=>$taleInfo['prefix'],
        );
        if (!$arrVars['strTableSql']){
            return Array('解析表SQL错误',0);
        }
        $arrVars['strTableSql'] = 'CREATE "." TABLE `".$this->strPrefix."'.$taleInfo['name'].'` '.$arrVars['strTableSql'];
        if (stripos($arrVars['strTableName'],'_') > 0){
            $arrTempClass = explode('_',$arrVars['strTableName']);
            array_walk($arrTempClass,function(&$v){$v = ucfirst(strtolower($v));});
            $arrVars['strTablePath'] = array_shift($arrTempClass);
            $arrVars['strTableClass'] = implode('',$arrTempClass);
        }
        $arrVars['strPrimary'] = $arrVars['strFields'] = $arrVars['strCondition'] = $arrVars['strRequired'] = '';
        foreach ($tableField as $key=>$value){
            $arrVars['strFields'] .= "\t\t'{$key}'=>['".implode("','",$value)."'],".PHP_EOL;
            $value[4]=='true' && $arrVars['strPrimary'] .= ",'{$key}'=>'{$value[0]}'";
        }
        if ($arrVars['strPrimary']){
            $arrVars['strPrimary'] = '['.substr($arrVars['strPrimary'],1).']';
        }
        //开始config判断
        foreach ($arrConfig['condition'] as $key=>$value){
            if ($value == 1 && array_key_exists($key,$tableField)){
                $v = $tableField[$key];
                $v[8] = explode('#',$v[8])[0];
                $arrVars['strCondition'] .= "\t\t'{$key}'=>['{$v[0]}',{$v[1]},'{$v[8]}'],".PHP_EOL;
            }
        }
        //开始required判断
        foreach ($arrConfig['required'] as $key=>$value){
            if ($value && array_key_exists($key,$tableField)){
                $strFunction = isset($arrConfig['function'][$key])?$arrConfig['function'][$key]:'';
                $strArgv1 = isset($arrConfig['argv1'][$key])?$arrConfig['argv1'][$key]:'';
                $strArgv2 = isset($arrConfig['argv2'][$key])?$arrConfig['argv2'][$key]:'';
                $strTip = isset($arrConfig['tip'][$key])?$arrConfig['tip'][$key]:'';
                $strDefault = trim(isset($arrConfig['default'][$key])?$arrConfig['default'][$key]:'');
                !$strTip && $v[8] = explode('#',$tableField[$key][8])[0];
                $strDefault = strlen($strDefault)?",'{$strDefault}'":'';
                $arrVars['strRequired'] .= "\t\t'{$key}'=>['{$value}','{$strFunction}',['{$strArgv1}','{$strArgv2}'],'{$strTip}'{$strDefault}],".PHP_EOL;
            }
        }
        $strContent = file_get_contents(FRAME_PATH.'Tpl'.DS.'default_table.tpl');
        preg_match_all('/\{\$(.*?)\}/',$strContent,$arrValue);
        foreach ($arrValue[1] as $item) {
            $value = isset($arrVars[$item])?$arrVars[$item]:'';
            $strContent = str_replace('{$'.$item.'}', $value, $strContent);
        }
        $strFilePath = $this->config['save_path'].
            ucfirst(strtolower($this->config['name_space'])).
            '\\'.$arrVars['strTablePath'];
        if (!is_dir($strFilePath)){
            if (mkdir($strFilePath,0755,true)){
                return Array('目录不可写:'.$strFilePath,0);
            }
        }
        $strFilePath .= '\\'.$arrVars['strTableClass'].'.class.php';
        if (!file_put_contents($strFilePath,$strContent)){
            return Array('目录子目录写入失败:'.$strFilePath,0);
        }
        return Array('类：'.(str_ireplace($this->config['save_path'],'',$strFilePath)).'生成完成。',1);
    }

}