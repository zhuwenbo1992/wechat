<?php
/**
 * Created by PhpStorm.
 * User: hasee
 * Date: 2017/5/3
 * Time: 11:28
 */
   //���ǵķ������Ľӿڵ�ַ�ļ�
header("content-type:text/html;charset=utf-8");

     include 'class.wechat.php';
    $wechat=new Wechat();
//��֤����һ�ν�����Ҫ��֤������Ͳ���Ҫ����֤��
//    $wechat->valid();
//$wechat->responseMsg();
//$wechat->uploadMedia('image','1.jpg');exit;
$wechat->createMenu();
//$wechat->deleteMenu();
$wechat->tuisong();
$wechat->responseMsg();
//header('content-type:text/xml');
//$wechat->msgImage(2);


