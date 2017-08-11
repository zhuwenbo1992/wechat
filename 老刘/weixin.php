<?php
/**
 * Created by PhpStorm.
 * User: hasee
 * Date: 2017/5/3
 * Time: 11:28
 */
   //我们的服务器的接口地址文件
header("content-type:text/html;charset=utf-8");

     include 'class.wechat.php';
    $wechat=new Wechat();
//验证（第一次接入需要认证，后面就不需要再认证）
//    $wechat->valid();
//$wechat->responseMsg();
//$wechat->uploadMedia('image','1.jpg');exit;
$wechat->createMenu();
//$wechat->deleteMenu();
$wechat->tuisong();
$wechat->responseMsg();
//header('content-type:text/xml');
//$wechat->msgImage(2);


