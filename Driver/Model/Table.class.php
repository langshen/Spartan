<?php
namespace Spartan\Driver\Model;
use Spartan\Lib\Model;

class Table extends Model
{
    public $arrConfig = [
        'filter'=>[],//不自动select的字段
        'auto'=>true,//自动使用request内容做匹配
        'array'=>true,//返回数组的格式
        'count'=>false,//select时，是否需要汇总
        'action'=>'update',//update时，指定的动作,update或insert
        'limit'=>20,//select时的默认记录数
    ];
    public $strPrefix = '';//表名前缀
    public $strTable = '';//表名
    public $strComment = '';//表备注
    public $strAlias = 'a';//别名
    public $arrPrimary = [];//唯一主键 = ['主键名',主键值]
    public $arrCondition = [];//支持外露的查询条件
    public $arrRequired = [];//添加时必的字段
    public $arrFields = [];//所有的字段名,[类型,长度,小数,字段格式,主键,增值,否空,默认值,注释]

    /**
     * 返回一个表的可查询条件
     * @return array
     */
    public function getSearchCondition(){
        $arrCondition = !$this->arrCondition?[]:$this->arrCondition;
        $arrResultCondition = [];
        foreach ($arrCondition as $k=>$value){
            if (isset($value[2]) && !$value[2]){continue;}
            $arrResultCondition[] = Array('id'=>'a.'.$k,'text'=>isset($value[2])?explode('#',$value[2])[0]:$k);
        }
        return $arrResultCondition;
    }

    /**
     * 返回一个操作符数组
     * @return array
     */
    public function getSearchSymbol(){
        return Array(
            ['id'=>'like','text'=>'模糊'],
            ['id'=>'eq','text'=>'等于'],
            ['id'=>'gt','text'=>'大于'],
            ['id'=>'lt','text'=>'小于'],
            ['id'=>'neq','text'=>'不等于'],
            ['id'=>'egt','text'=>'大于等于'],
            ['id'=>'elt','text'=>'小于等于'],
            ['id'=>'between','text'=>'范围区间'],
        );
    }

    /**
     * 读取单一记录，返回一个记录的Array();
     * @param array $options
     * @param string $math
     * @return mixed
     */
    public function find($options = [],$math = ''){
        if (!$math){
            $options = $this->parseCondition($options);
        }
        $arrInfo = db()->find([$this->strTable,$this->strAlias],$options,$math);
        return $this->getConfig('array')?['success',$arrInfo!==false?1:0,$arrInfo]:$arrInfo;
    }


    /**
     * 读取一个列表记录，返回一个列表的Array();
     * @param array $options
     * @return mixed
     */
    public function select($options = []){
        $options = $this->parseCondition($options);
        $options = $this->commonVariable($options);//自动POST或GET来的变量
        $arrResult = Array('data'=>[],'count'=>0);
        if ($this->getConfig('count') == true){//如果需要总条数
            $arrResult['count'] = db()->find(
                [$this->strTable,$this->strAlias],
                $options,'count(*)'
            );
        }
        $arrResult['data'] = db()->select([$this->strTable,$this->strAlias],$options);
        $bolArray = $this->getConfig('array');
        if ($this->getConfig('count') == true){
            return $bolArray?['success',$arrResult['data']!==false?1:0,$arrResult]:$arrResult;
        }else{
            return $bolArray?['success',$arrResult['data']!==false?1:0,$arrResult['data']]:$arrResult['data'];
        }
    }

    /**
     * 删除记录。
     * @param array $options
     * @return mixed
     */
    public function delete($options = []){
        $bolArray = $this->getConfig('array');
        if (isset($options['where']['id'])){
            if (is_array($options['where']['id'])){//如果是个数据
                if (isset($options['where']['id'][0]) && strtolower($options['where']['id'][0]) != 'in'){
                    $options['where']['id'] = Array('in',$options['where']['id']);//固定第一个为IN
                }
                if (!isset($options['where']['id'][1]) || !$options['where']['id'][1]){
                    return $bolArray?['删除条件为空。',0]:false;
                }
                if (!is_numeric(implode('',$options['where']['id'][1]))){
                    return $bolArray?['删除ID数组不是数字。',0]:false;
                }
            }else{
                if (!is_numeric($options['where']['id'])){
                    return $bolArray?['删除ID不是数字。',0]:false;
                }
            }
        }
        $result = db()->delete($this->strTable,$options);
        return $bolArray?[$result?'删除成功':'删除失败',$result?1:0,[]]:$result;
    }

    /**
     * @param array $options
     * @param array $arrData
     * @return mixed
     */
    public function update($arrData = [], $options = []){
        $bolArray = $this->getConfig('array');
        $bolUpdate = false;
        //主键中的自增字段不允许在data里。
        $strPrimary = '';//自增主键名
        $arrPrimary = [];//主键数组
        foreach ($this->arrPrimary as $key=>$value){
            if (!array_key_exists($key,$this->arrFields)){
                return $bolArray?["主键：{$key}不在字段中",0,[]]:0;
            }
            //主键是自增
            if ($this->arrFields[$key][5] == 'true'){
                $strPrimary = $key;//这是自增主键的名
                if (array_key_exists($key,$arrData) && $arrData[$key]){
                    $options['where'][$key] = $arrData[$key];
                    $arrPrimary[$key] = $arrData[$key];
                    unset($arrData[$key]);
                }else{
                    $arrPrimary[$key] = 0;
                }
            }
        }
        //传递数据不是表字段的，删除
        foreach ($arrData as $key=>$value){
            if (!array_key_exists($key,$this->arrFields)){
                unset($arrData[$key]);
            }
        }
        if (isset($options['where']) && $options['where']){
            foreach ($this->arrPrimary as $key=>$value){
                if (//只要找到一个自增主键，就是更新，不然都是添加
                    isset($options['where'][$key]) &&
                    $options['where'][$key] &&
                    array_key_exists($key,$this->arrFields) &&
                    $this->arrFields[$key][5] == 'true'
                ){
                    $bolUpdate = true;
                    break;
                }else{
                    $bolUpdate = false;
                }
            }
        }else{
            $bolUpdate = false;
            unset($options['where']);
        }
        if (!$bolUpdate || $this->getConfig('action') == 'insert'){
            if ($this->getConfig('action') == 'insert' && isset($options['where']) && is_array($options['where'])){
                $arrData = array_merge($arrData,$options['where']);
            }
            $result = db()->insert($this->strTable,$arrData,$options);
            $arrPrimary[$strPrimary] = max(0,$result);
        }else{
            $result = db()->update($this->strTable,$arrData,$options);
        }
        return $bolArray?[$result===false?'操作失败':'操作成功',$result===false?0:1,[$arrPrimary]]:$result;
    }

    /**
     * 添加和修改，返回的Data中，
     * @param array $arrData
     * @param array $options
     * @return mixed
     */
    public function updateField($arrData = [],$options = []){
        $result = db()->update($this->strTable,$arrData,$options);
        return $this->getConfig('array')?[$result===false?'操作失败':'操作成功',$result===false?0:1,[]]:$result;
    }

    /**
     * 自动识别常用的POST或GET变量，并合并到options里
     * @param $options
     * @return mixed
     */
    private function commonVariable($options){
        //常用分页和排序
        $data['page'] = max(0, request()->param('pageIndex',0));
        $data['limit'] = request()->param('pageSize',0);
        !$data['page'] && $data['page'] = max(0, request()->param('page',0));
        !$data['limit'] && $data['limit'] = max(0,request()->param('limit',20));
        $data['order'] = request()->param('sortField','');
        if ($data['order']) {
            $data['order'] .= ' ' . request()->param('sortOrder','');
        }else{
            unset($data['order']);
        }
        //常用的搜索
        $arrSymbol = Array('eq','like','gt','lt','neq','egt','elt','between');
        $searchType = request()->param('search_type');
        $searchSymbol = request()->param('search_symbol');
        $searchKey = trim(request()->param('search_key',''));
        if (stripos($searchKey,'\u') === 0){
            $searchKey = json_decode('"'.$searchKey.'"');
        }
        !in_array($searchSymbol, $arrSymbol) && $searchSymbol = 'eq';
        if ($searchType && $searchKey) {
            if ($searchSymbol == 'like'){
                $arrSearchKey = explode(' ',$searchKey);
                $arrSearchSymbol = [];
                foreach ($arrSearchKey as $value){
                    $arrSearchSymbol[] = "%{$value}%";
                }
                if (count($arrSearchSymbol) > 1){
                    $data['where'][$searchType] = Array(
                        $searchSymbol,
                        $arrSearchSymbol,
                        'and'
                    );
                }else{
                    $data['where'][$searchType] = Array(
                        $searchSymbol,
                        $arrSearchSymbol[0]
                    );
                }
            }elseif ($searchSymbol == 'between'){
                if (stripos($searchKey,' ')>0){
                    list($key1,$key2) = explode(' ',$searchKey);
                }elseif (stripos($searchKey,'-')>0){
                    list($key1,$key2) = explode('-',$searchKey);
                }elseif(stripos($searchKey,'，')>0){
                    list($key1,$key2) = explode('，',$searchKey);
                }else{
                    list($key1,$key2) = explode(',',$searchKey);
                }
                $data['where'][$searchType] = Array($searchSymbol, $key1,$key2);
            }else{
                $data['where'][$searchType] = Array($searchSymbol, $searchKey);
            }
        }
        return $this->mergeOptions($options,$data);
    }

    /**
     * 并合二个Db的options
     * @param $options1 array 主选
     * @param $options2 array 设选
     * @return mixed
     */
    private function mergeOptions($options1,$options2){
        if (isset($options2['page']) && intval($options2['page']) > 0){
            $options1['page'] = $options2['page'];//如果第二个有就要了
            unset($options2['page']);
        }
        if (!isset($options1['limit']) && isset($options2['limit']) && intval($options2['limit']) > 0){
            $options1['limit'] = $options2['limit'];//如果第一个没有，第二个有就要了
            unset($options2['limit']);
        }
        if (isset($options2['order']) && $options2['order']){
            $options1['order'] = $options2['order'];//如果第二个有就要了
            unset($options2['order']);
        }
        if (isset($options1['where']) && isset($options2['where'])){
            $options1['where'] = array_merge($options1['where'],$options2['where']);
            unset($options2['where']);
        }elseif (!isset($options1['where']) && isset($options2['where'])){
            $options1['where'] = $options2['where'];
        }
        return $options1;
    }

    /**
     * 自动加入where条件，目前只有int和str两种，
     * @param array $arrOptions
     * @param string $strAction
     * @return array
     */
    private function parseCondition($arrOptions = [],$strAction = 'select'){
        $tempWhere = [];//需要重写的where
        $arrKeyType = [
            'int'=>['int','tinyint','smallint','mediumint','integer','bigint','float','numeric','decimal','double'],
            'str'=>['char','varchar','tinytext','text','mediumtext','longtext'],
            'time'=>['datetime','timestamp','date','time','year','timestamp']
        ];//目前支持的key类型
        $arrKeyExp = ['in','like','between','exp','gt','egt','lt','elt','neq','eq'];//支付的item对比类型
        foreach ($this->arrCondition as $key=>$item) {
            $strValueKey = stripos($key,'.')===false?$key:array_pop(explode('.',$key));
            $strValue = request()->param($strValueKey,null);//传递过来的数据
            if (!is_array($item) || isset($item[0]) || !$strValue){continue;};
            $strKeyType = '';
            foreach($arrKeyType as $k=>$v){
                if(in_array($item[0],$v)){
                    $strKeyType = $k;break;
                }
            }
            if(!$strKeyType){continue;}//找不到Key的类型
            $strSymbol = $this->getConfig($key);
            (!$strSymbol && !in_array($strSymbol,$arrKeyExp)) && $strSymbol = 'eq';
            $bolResult = $this->parseValue($strKeyType,$strSymbol,$strValue);
            if (!$bolResult || !$strValue){continue;}
            $tempWhere[$strAction == 'delete'?$strValueKey:$key] = $this->parseWhere($strSymbol,$strValue);
        }
        $arrWhere = (isset($arrOptions['where']) && is_array($arrOptions['where']))?$arrOptions['where']:[];
        $arrOptions['where'] = !$arrWhere?$tempWhere:array_merge($arrWhere,$tempWhere);
        return $arrOptions;
    }

    /**
     * 根据类型条件，判断自动的条件的值
     * @param $strKeyType string 字段类型
     * @param $strSymbol string 操作符
     * @param $strValue mixed 变量值
     * @return mixed
     */
    private function parseValue($strKeyType,$strSymbol,&$strValue){
        if ($strSymbol == 'between' || $strSymbol == 'in'){
            if (stripos($strValue,' ')>0){
                $strValue = explode(' ',$strValue);
            }elseif (stripos($strValue,'-')>0){
                $strValue = explode('-',$strValue);
            }elseif(stripos($strValue,'，')>0){
                $strValue = explode('，',$strValue);
            }elseif(stripos($strValue,',')>0){
                $strValue = explode(',',$strValue);
            }
            !isset($strValue[1]) && $strValue[1] = '';
        }
        $strValue = [$strValue];
        switch ($strKeyType){
            case 'int':
                foreach ($strValue as &$v){
                    $v = max(0,intval($v));
                }unset($v);
                break;
            case 'str':
                foreach ($strValue as &$v){
                    $v = htmlspecialchars(strval($v));
                }unset($v);
                break;
            case 'time':
                foreach ($strValue as $k=>$v){
                    if (!strtotime($v)){unset($strValue[$k]);}
                }
                break;
            default:
                return false;
        }
        return true;
    }

    /**
     * 解析WHERE最后的样式
     * @param $strSymbol
     * @param $arrValue
     * @return array
     */
    private function parseWhere($strSymbol,$arrValue){
        if ($strSymbol == 'like'){
            $arrSymbol = [];
            foreach ($arrValue as $value){
                $arrSymbol[] = "%{$value}%";
            }
            if (count($arrSymbol) > 1){
                return Array($strSymbol,$arrSymbol,'and');
            }else{
                return Array($strSymbol,$arrValue[0]);
            }
        }elseif ($strSymbol == 'between'){
            return Array($strSymbol, $arrValue[0],$arrValue[2]);
        }elseif ($strSymbol == 'in'){
            return Array($strSymbol, implode(',',$arrValue));
        }elseif ($strSymbol == 'exp'){
            return Array($strSymbol, implode('',$arrValue));
        }else{
            return Array($strSymbol, $arrValue[0]);
        }
    }
}