<?php
namespace Spartan\Extend\Sender;

defined('APP_NAME') or exit('404');

interface MailSender{
    public function __construct($_arrConfig = []);//初始化
    public function setCallBack($fun);//设置回调函数
    public function setConfig($name,$value);//设置配置信息
    public function setMailInfo($strTitle,$strBody,$attachment);//设置邮件主题
    public function setReceiver($strEmail);//设置接受者
    public function getResult();//返回结果
    public function getErrors();//返回错误
    public function sendMail();//发送

}