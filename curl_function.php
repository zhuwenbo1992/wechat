<?php
/**
 * Created by PhpStorm.
 * User: hasee
 * Date: 2017/4/17
 * Time: 16:33
 */
function  curl($url,$method,$data=array(),$setcooke=false,$cookie_file='1.txt'){
    $ch = curl_init();	 //1.初始化
    curl_setopt($ch, CURLOPT_URL, $url); //2.请求地址
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);//3.请求方式
    //4.参数如下	绕过服务器端SSL的验证
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    //伪装请求来源，绕过防盗
    //curl_setopt($ch,CURLOPT_REFERER,"http://wthrcdn.etouch.cn/");
    //curl_setopt($ch,CURLOPT_REFERER,"http://upload1.techweb.com.cn");

    //配置curl解压缩方式（默认的压缩方式）
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Encoding:gzip'));
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");
     //配置代理
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:44.0) Gecko/20100101 Firefox/44.0'); //指明以哪种方式进行访问
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    if($method=="POST"){//5.post方式的时候添加数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    //模拟登陆
    if($setcooke==true){
        //如果设置要请求的cookie，那么把cookie值保存在指定的文件中
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    }else{
        //就从文件中读取cookie的信息,并验证
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    }
      //不直接输出内容
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //执行
    $tmpInfo = curl_exec($ch);

    if (curl_errno($ch)) {
        return curl_error($ch);
    }
    //释放资源
    curl_close($ch);
    //返回获取的信息
    return $tmpInfo;
}