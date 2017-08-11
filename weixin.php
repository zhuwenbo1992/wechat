<?php


//include("curl_function.php");
include("classApi.php");

$we = new wechat();
var_dump($we);die;
if (!isset($_GET['echostr'])) {
    $wechatObj->responseMsg();
}else{
    $wechatObj->valid();
}

echo $we->access_token();









 ?>