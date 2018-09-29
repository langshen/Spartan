<?php
namespace Spartan\Extend\Sender;

defined('APP_NAME') or exit('404');

interface SmsSender{
    public function __construct($_arrConfig = []);//初始化
    public function setCallBack($fun);//设置回调函数
    public function setConfig($name,$value);//设置配置信息
    public function setBody($strBody);//设置发送内容
    public function setMobile($mobile);//设置手机号
    public function getResult();//返回结果
    public function getErrors();//返回错误
    public function send();//发送


}