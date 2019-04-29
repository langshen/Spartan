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
        'name_space'=>'Model\\Entity',//命名空间
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
        $arrTempClass = explode('\\',$this->config['name_space']);
        array_walk($arrTempClass,function(&$v){$v = ucfirst($v);});unset($v);
        $this->config['name_space'] = implode('\\',$arrTempClass);
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
        $strKey = request()->param('key','');
        !preg_match('/^[A-Za-z0-9\-\_]+$/',$strKey) && $strKey = '';
        $arrTable = Db()->getTables('',$intLimit,$intPage,$strKey);
        foreach ($arrTable['data'] as &$value){
            $clsTemp = model()->getModel($value['name'],$this->config['name_space']);
            $value['status'] = is_object($clsTemp)?'已生成':'未建立';
        }
        unset($value);
        return Array('success',0,$arrTable);
    }

    /**
     * 返回指定表的所有字段
     * @param string $strTableName
     * @return array|string
     */
    public function tableInfo($strTableName = ''){
        !$strTableName && $strTableName = strip_tags(request()->param('table',''));
        if (!$strTableName){
            return Array('表名不能为空。',1);
        }
        $getFunc = function ($type,$long = 0){
            if (in_array($type,['int','tinyint','smallint','bigint','mediumint'])){
                return 'number';
            }elseif (in_array($type,['varchar','char','text'])){
                return 'length'.($long>0?':1,'.$long:'');
            }elseif(in_array($type,['decimal','float','double','real'])) {
                return 'float';
            }elseif (in_array($type,['datetime','date'])){
                return 'date';
            }else{
                return '';
            }
        };
        $clsDalTable = model()->getModel($strTableName,$this->config['name_space']);
        $arrCondition = $arrRequire = [];
        $strComment = '';
        if (is_object($clsDalTable)){
            $arrCondition = $clsDalTable->arrCondition;
            $arrRequire = $clsDalTable->arrRequire;
            $strComment = $clsDalTable->strComment;
        }
        $arrInfo = db()->getFullFields($strTableName);
        if (!$arrInfo){
            return Array('没有找到表的相关信息。',1);
        }
        foreach ($arrInfo as $k=>&$v){
            $arrTempRequire = array_key_exists($k,$arrRequire)?$arrRequire[$k]:['','',''];
            !isset($arrTempRequire[0]) && $arrTempRequire[0] = '';
            !isset($arrTempRequire[1]) && $arrTempRequire[1] = '';
            !isset($arrTempRequire[2]) && $arrTempRequire[2] = '';
            if (isset($arrTempRequire[0]) && $arrTempRequire[0]){
                if (mb_substr($arrTempRequire[0],0,8)=='require|'){//如果有内容，判断是否必填写
                    $arrTempRequire[0] = ['require',mb_substr($arrTempRequire[0],8)];
                }else{
                    $arrTempRequire[0] = ['null',$arrTempRequire[0]];
                }
            }else{
                $arrTempRequire[0] = ['',$getFunc($v[0],$v[1])];//如果为空，给出默认函数
                !$arrTempRequire[1] && $arrTempRequire[1] = $v[8];//给出默认提示
                !$arrTempRequire[2] && $arrTempRequire[2] = $arrTempRequire[0][1]=='number'?intval($v[7]):'';//给出默认值
            }
            if ($v[7] == 'CURRENT_TIMESTAMP'){//有默认时间的不设置，$v[0] == 'datetime';
                $arrTempRequire[0] = ['no',''];
            }
            $v = Array(
                'condition'=>array_key_exists($k,$arrCondition)?'1':'',
                'require'=>$arrTempRequire,
                'name'=>$k,
                'type'=>$v[0],
                'long'=>$v[1] . ',' .$v[2],
                'collation'=>$v[3],
                'pri'=>$v[4],
                'null'=>$v[6],
                'default'=>$v[7],
                'comment'=>$v[8]
            );
        }
        unset($v);
        return Array('success',0,['name'=>$strTableName,'comment'=>$strComment,'fields'=>$arrInfo]);
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
            'require'=>request()->param('require',[]),
            'function'=>request()->param('function',[]),
            'tip'=>request()->param('tip',[]),
            'default'=>request()->param('default',[]),
        ),$arrData);
        if (!$arrData['condition']){
            return Array('至少勾选一个查询条件。',1);
        }
        if ($arrData['condition'] && !is_array($arrData['condition'])){
            return Array('查询条件condition应该是个数组。',1);
        }
        if ($arrData['require'] && !is_array($arrData['require'])){
            return Array('必填项require应该是个数组。',1);
        }
        !$strTableName && $strTableName = strip_tags(request()->param('table_name',''));
        if (!$strTableName){
            return Array('表名不能为空。',1);
        }
        $arrFields = db()->getFullFields($strTableName);
        if (!$arrFields){
            return Array('查询表字段失败。',1);
        }
        $arrTableInfo = db()->getTables($strTableName);
        if (!isset($arrTableInfo['data']) || !$arrTableInfo['data'] || !isset($arrTableInfo['data'][0])){
            return Array('查询表的信息失败。',1);
        }
        $arrTableInfo = $arrTableInfo['data'][0];
        $arrTable = db()->showCreateTable($strTableName);
        if (!$arrTable || !isset($arrTable[1])){
            return Array('查询表SQL信息失败。',1);
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
            return Array('解析表SQL错误',1);
        }
        $arrVars['strTableSql'] = 'CREATE "." TABLE `".$this->strPrefix."'.$taleInfo['name'].'` '.$arrVars['strTableSql'];
        if (stripos($arrVars['strTableName'],'_') > 0){
            $arrTempClass = explode('_',$arrVars['strTableName']);
            array_walk($arrTempClass,function(&$v){$v = ucfirst(strtolower($v));});
            $arrVars['strTablePath'] = array_shift($arrTempClass);
            $arrVars['strTableClass'] = implode('',$arrTempClass);
        }
        $arrVars['strPrimary'] = $arrVars['strFields'] = $arrVars['strCondition'] = $arrVars['strRequire'] = '';
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
        //开始require判断
        foreach ($arrConfig['require'] as $key=>$value){
            if ($value && array_key_exists($key,$tableField)){
                $strFunction = isset($arrConfig['function'][$key])?$arrConfig['function'][$key]:'';
                $strTip = isset($arrConfig['tip'][$key])?$arrConfig['tip'][$key]:'';
                $strDefault = trim(isset($arrConfig['default'][$key])?$arrConfig['default'][$key]:'');
                !$strTip && $v[8] = explode('#',$tableField[$key][8])[0];
                if ($value == 'require') {
                    $value .= '|';
                }else {
                    $value = '';
                }
                $arrVars['strRequire'] .= "\t\t'{$key}'=>['{$value}{$strFunction}','{$strTip}','{$strDefault}'],".PHP_EOL;
            }
        }unset($value);
        $strContent = file_get_contents(FRAME_PATH.'Tpl'.DS.'default_table.tpl');
        preg_match_all('/\{\$(.*?)\}/',$strContent,$arrValue);
        foreach ($arrValue[1] as $item) {
            $value = isset($arrVars[$item])?$arrVars[$item]:'';
            $strContent = str_replace('{$'.$item.'}', $value, $strContent);
        }
        $strFilePath = $this->config['save_path'].$this->config['name_space'].DS.$arrVars['strTablePath'];
        $strFilePath = str_replace(['\\','/'],[DS,DS],$strFilePath);
        if (!is_dir($strFilePath)){
            if (!@mkdir($strFilePath,0755,true)){
                return Array('目录不可写:'.$strFilePath,1);
            }
        }
        $strFilePath .= DS.$arrVars['strTableClass'].'.class.php';
        if (!@file_put_contents($strFilePath,$strContent)){
            return Array('目录子目录写入失败:'.$strFilePath,1);
        }
        return Array('模型：'.(str_ireplace($this->config['save_path'],'',$strFilePath)).'生成完成。',0);
    }

    /**
     * 已经存在的模型文件
     * @param int $intLimit
     * @param int $intPage
     *  @return array|mixed
     */
    public function modelList($intLimit = 0,$intPage = 1){
        unset($intLimit,$intPage);
        $this->loadTableModel(APP_ROOT.$this->config['name_space'],$arrFiles);
        $arrTableList = Db()->getTables();
        if (!isset($arrTableList['data']) || !$arrTableList['data']){
            $arrTableList = ['data'=>[],'total'=>0];
        }
        $arrTableKey = array_column($arrTableList['data'],'name');
        $arrTableList['data'] = array_combine($arrTableKey,$arrTableList['data']);
        $arrTable = [];
        foreach ($arrFiles as $v){
            $clsTemp = model()->getModel($v,$this->config['name_space']);
            if (!is_object($clsTemp)){
                $arrTable[] = Array(
                    'name'=>str_replace('\\','/',$v),
                    'comment'=>'模型失败',
                    'rows'=>'模型失败',
                    'create_time'=>'模型失败',
                    'collation'=>'模型失败',
                    'engine'=>'模型失败',
                    'status'=>'模型失败',
                );
            }elseif (!isset($clsTemp->strTable)){
                $arrTable[] = Array(
                    'name'=>str_replace('\\','/',$v),
                    'comment'=>'模型异常',
                    'rows'=>'模型异常',
                    'create_time'=>'模型异常',
                    'collation'=>'模型异常',
                    'engine'=>'模型异常',
                    'status'=>'模型异常',
                );
            }elseif (!isset($arrTableList['data'][$clsTemp->strTable])){
                $arrTable[] = Array(
                    'name'=>$clsTemp->strTable,
                    'comment'=>'未建立表',
                    'rows'=>'未建立表',
                    'create_time'=>'未建立表',
                    'collation'=>'未建立表',
                    'engine'=>'未建立表',
                    'status'=>'未建立表',
                );
            }else{
                $arrTableList['data'][$clsTemp->strTable]['status'] = '表模正常';
                $arrTable[] = $arrTableList['data'][$clsTemp->strTable];
            }
        }
        return Array('success',0,['data'=>$arrTable,'total'=>count($arrTable),'table_total'=>$arrTableList['total']]);
    }

    /**
     * 根据模型文件建立数据表
     * @return array
     */
    public function modelCreate(){
        $strModelName = request()->param('table','');
        if (!$strModelName || !is_scalar($strModelName)){
            return Array('模型名称丢失。',1);
        }
        $arrConfig = array_filter(config('DB'));
        if (!isset($arrConfig['NAME']) || !isset($arrConfig['USER'])){
            return Array('请配置数据库连接。',1);
        }
        $clsModel = model()->getModel($strModelName,$this->config['name_space']);
        if (!is_object($clsModel)){
            return Array('模型类和文件不存在。',1);
        }
        if (!isset($clsModel->strTable)){
            return Array('模型中的表名丢失。',1);
        }
        $arrFields = db()->getFullFields($clsModel->strTable);
        if ($arrFields){
            return Array('数据表已经存在，无需重新生成。',1);
        }
        if (!method_exists($clsModel,'sql')){
            return Array($strModelName.'获取Sql失败。',1);
        }
        $result = db()->execute($clsModel->sql());
        if ($result === false){
            return Array($strModelName.'建立表失败，请检查权限。',1);
        }
        return Array($strModelName.'建立表成功。',0);
    }
    /**
     * 找到Table下的所有已生成的模型
     * @param $strDir
     * @param array $arrFileName
     */
    private function loadTableModel($strDir,&$arrFileName = []){
        $arrDir = is_array($strDir)?$strDir:explode(',',$strDir);
        $intExtLen = strlen(CLASS_EXT);
        $arrNextPath = [];
        foreach($arrDir as $dir){
            $arrCore = new \RecursiveDirectoryIterator(rtrim(str_replace(['\\','/'],[DS,DS],$dir),DS).DS);
            foreach($arrCore as $objFile){
                $strFile = $objFile->getPathname();
                if ($objFile->isDir()){
                    !in_array($objFile->getFilename(),['.','..']) && $arrNextPath[] = $strFile;
                }else{
                    if (substr($strFile,0 - $intExtLen) == CLASS_EXT){
                        $strFile = trim(explode(APP_ROOT,strstr($strFile,'.',true))[1],DS.'.');
                        $arrTemp = explode(DS,$strFile);
                        if ($arrTemp[0].'\\'.$arrTemp[1] != $this->config['name_space']){
                            continue;
                        }
                        $arrFileName[] = $strFile;
                    }
                }
            }
        }
        $arrNextPath && $this->loadTableModel($arrNextPath,$arrFileName);
    }
}