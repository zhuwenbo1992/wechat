<?php
  define('TOKEN','weixin');
  class Wechat{
      private $appid;
      private $appsecret;

      //验证（第一次接入需要认证，后面就不需要再认证）
      public function valid()
      {
          $echoStr = $_GET["echostr"];

          //valid signature , option
          if($this->checkSignature()){
              echo $echoStr;
              exit;
          }
      }
      /*
       * 自动函数,获取appid和appsecret
       */
      public function __construct($arr=array())
      {
          $this->appid = isset($arr['appid']) ? $arr['appid'] : 'wx50909fb4a2f19f65';
          $this->appsecret = isset($arr['appsecret']) ? $arr['appsecret'] : 'e09eabf607564c6e41ae79d094c21a12';
      }

      /*
     * 创建二维码的票据  ticket
     * @param1  $type 默认值 tmp 代表生成临时二维码
     * @scene_id  $scene_id 场景ID
     * @expire   临时二维码的过期时间，默认值为 604800
     * return  返回一个ticket票据
     */
      private function ticket($scene_id,$type=0,$expire=604800){
          //获取access_token
         $access_token = $this->access_token();
         $url="https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$access_token;
         if($type==0){
              //临时二维码
             $data='{"expire_seconds": %s, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": %s}}}';
             $data=sprintf($data,$expire,$scene_id);
          }
          elseif($type==1){
              //永久字符串二维码
              $data='{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "%s"}}}';
              $data=sprintf($data,$scene_id);
          }
          else{
              $data='{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": %s}}}';
              $data=sprintf($data,$scene_id);
          }
          //模拟post请求
          $tiket=$this->curl($url,'POST',$data);
          return json_decode($tiket,true)['ticket'];
      }





      /**
       * 获取access_token
       *
       */
        public function access_token(){
            $filename = 'access_token.txt';
            if (file_exists($filename) && (time()-filemtime($filename))<7200){
                //从文件中读取
                return file_get_contents($filename);
            }
            else{
                //调取接口获取并存入文件
                $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appid}&secret={$this->appsecret}";
                $str = $this->curl($url,'GET');
                $access_token = json_decode($str,1)['access_token'];
                file_put_contents($filename,$access_token);
                return $access_token;
            }

        }

      /*
       * curl函数
       */
      private function curl($url,$method,$data=array(),$setcooke=false,$cookie_file='1.txt'){
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

      //微信公众平台和自定义服务器的验证规则
      private function checkSignature()
      {
          // you must define TOKEN by yourself
          if (!defined("TOKEN")) {
              throw new Exception('TOKEN is not defined!');
          }

          $signature = $_GET["signature"];  //从公众平台传递过来的数据 在公众平台已经生成好的签名
          $timestamp = $_GET["timestamp"];  //从公众平台传递过来的数据
          $nonce = $_GET["nonce"];

          $token = TOKEN;
          $tmpArr = array($token, $timestamp, $nonce);
          // use SORT_STRING rule
          sort($tmpArr, SORT_STRING);
          $tmpStr = implode( $tmpArr );
          $tmpStr = sha1( $tmpStr );

          if( $tmpStr == $signature ){
              return true;
          }else{
              return false;
          }
      }

  }
