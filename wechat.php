<?php
  define('TOKEN','weixin');
  class Wechat{
      private $appid;
      private $appsecret;

      //��֤����һ�ν�����Ҫ��֤������Ͳ���Ҫ����֤��
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
       * �Զ�����,��ȡappid��appsecret
       */
      public function __construct($arr=array())
      {
          $this->appid = isset($arr['appid']) ? $arr['appid'] : 'wx50909fb4a2f19f65';
          $this->appsecret = isset($arr['appsecret']) ? $arr['appsecret'] : 'e09eabf607564c6e41ae79d094c21a12';
      }

      /*
     * ������ά���Ʊ��  ticket
     * @param1  $type Ĭ��ֵ tmp ����������ʱ��ά��
     * @scene_id  $scene_id ����ID
     * @expire   ��ʱ��ά��Ĺ���ʱ�䣬Ĭ��ֵΪ 604800
     * return  ����һ��ticketƱ��
     */
      private function ticket($scene_id,$type=0,$expire=604800){
          //��ȡaccess_token
         $access_token = $this->access_token();
         $url="https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$access_token;
         if($type==0){
              //��ʱ��ά��
             $data='{"expire_seconds": %s, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": %s}}}';
             $data=sprintf($data,$expire,$scene_id);
          }
          elseif($type==1){
              //�����ַ�����ά��
              $data='{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "%s"}}}';
              $data=sprintf($data,$scene_id);
          }
          else{
              $data='{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": %s}}}';
              $data=sprintf($data,$scene_id);
          }
          //ģ��post����
          $tiket=$this->curl($url,'POST',$data);
          return json_decode($tiket,true)['ticket'];
      }





      /**
       * ��ȡaccess_token
       *
       */
        public function access_token(){
            $filename = 'access_token.txt';
            if (file_exists($filename) && (time()-filemtime($filename))<7200){
                //���ļ��ж�ȡ
                return file_get_contents($filename);
            }
            else{
                //��ȡ�ӿڻ�ȡ�������ļ�
                $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appid}&secret={$this->appsecret}";
                $str = $this->curl($url,'GET');
                $access_token = json_decode($str,1)['access_token'];
                file_put_contents($filename,$access_token);
                return $access_token;
            }

        }

      /*
       * curl����
       */
      public  function curl($url,$method,$data=array(),$setcooke=false,$cookie_file='1.txt'){
          $ch = curl_init();	 //1.��ʼ��
          curl_setopt($ch, CURLOPT_URL, $url); //2.�����ַ
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);//3.����ʽ
          //4.��������	�ƹ���������SSL����֤
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
          curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
          //αװ������Դ���ƹ�����
          //curl_setopt($ch,CURLOPT_REFERER,"http://wthrcdn.etouch.cn/");
          //curl_setopt($ch,CURLOPT_REFERER,"http://upload1.techweb.com.cn");

          //����curl��ѹ����ʽ��Ĭ�ϵ�ѹ����ʽ��
          curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Encoding:gzip'));
          curl_setopt($ch, CURLOPT_ENCODING, "gzip");
          //���ô���
          curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:44.0) Gecko/20100101 Firefox/44.0'); //ָ�������ַ�ʽ���з���
          curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
          curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
          if($method=="POST"){//5.post��ʽ��ʱ���������
              curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
          }
          //ģ���½
          if($setcooke==true){
              //�������Ҫ�����cookie����ô��cookieֵ������ָ�����ļ���
              curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
          }else{
              //�ʹ��ļ��ж�ȡcookie����Ϣ,����֤
              curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
          }
          //��ֱ���������
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          //ִ��
          $tmpInfo = curl_exec($ch);

          if (curl_errno($ch)) {
              return curl_error($ch);
          }
          //�ͷ���Դ
          curl_close($ch);
          //���ػ�ȡ����Ϣ
          return $tmpInfo;
      }

      //΢�Ź���ƽ̨���Զ������������֤����
      private function checkSignature()
      {
          // you must define TOKEN by yourself
          if (!defined("TOKEN")) {
              throw new Exception('TOKEN is not defined!');
          }

          $signature = $_GET["signature"];  //�ӹ���ƽ̨���ݹ��������� �ڹ���ƽ̨�Ѿ����ɺõ�ǩ��
          $timestamp = $_GET["timestamp"];  //�ӹ���ƽ̨���ݹ���������
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
