<?php

include("classApi.php");
include ('wechat.php');

$we = new wechat();
//验证（第一次接入需要认证，后面就不需要再认证）
  $we->valid();
if (!isset($_GET['echostr'])) {
    $wechatObj->responseMsg();
}else{
    $wechatObj->valid();
}

echo $we->access_token();









 ?>