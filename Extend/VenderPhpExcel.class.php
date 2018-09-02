<?php
namespace Spartan\Extend;

include_once "PHPExcel-1.8.1/PHPExcel.php";

/**
 * Class PhpExcel
 * 项目地址：https://github.com/PHPOffice/PHPExcel
 */
class VenderPhpExcel{
    public $arrConfig = [];

    public function __construct($arrConfig = []){

    }

    public static function instance($arrConfig = []) {
        return \Spt::getInstance(__CLASS__,$arrConfig);
    }

    function outExcel($array,$fileName='',$fileType='xls'){
        if(!$fileName){
            $fileName = date('Y-m-d',time());
        }
        $fileName.='.'.$fileType;
        $data = '';
        $data_header = Array();
        // Create new PHPExcel object
        $objPHPExcel = new \PHPExcel();
        // Set properties
        $objPHPExcel->getProperties()->setCreator("spartan framework")
            ->setLastModifiedBy("spartan framework")
            ->setTitle($fileName);
        $numToEng = "A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,AA,AB,AC,AD,AE,AF,AG,AH,AI,AJ,AK,AL,AM,AN,AO,AP,AQ,AR,AS,AT,AU,AV,AW,AX,AY,AZ";
        $numToEng = explode(',',$numToEng);
        // set table  工作簿
        $objPHPExcel->setActiveSheetIndex(0);
        if(is_array($array)){
            if (isset ($array [0])) {
                $i = 0 ;
                //设置标头
                foreach($array [0] as $key => $value){
                    $objPHPExcel->getActiveSheet()->setCellValue($numToEng[$i].'1',$key);
                    $i++;
                }
                $data .= implode(',',$data_header)."\r\n";
            }

            //填充内容
            for($i = 0; $i < count($array); $i++){
                $k = 0;
                foreach($array[$i] as $value){
                    $value = str_replace('\'','',$value);
                    $objPHPExcel->getActiveSheet()->setCellValueExplicit($numToEng[$k].($i+2),$value,\PHPExcel_Cell_DataType::TYPE_STRING);
                    $k++;
                }
            }
        }
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=".$fileName);
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        echo iconv('UTF-8','GBK//IGNORE',$data);
        die();
    }

}


