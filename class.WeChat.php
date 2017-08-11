<?php
/**
 * Created by PhpStorm.
 * User: hasee
 * Date: 2017/2/21
 * Time: 9:48
 */
define("TOKEN", "weixin");
//微信公众平台开发类
class  WeChat{
    private $appid;
    private $secret;

    public function __construct($arr=array()){
        $this->appid=isset($arr['appid']) ? $arr['appid'] : 'wxee6f8b2e1deba237';
        $this->secret=isset($arr['secret']) ?   $arr['secret'] : '5e83b08b20829116b37b21cafcfcb323';
    }
    //上传素材
    /*
     * @param1 $type 为要上传的多媒体文件类型
     * @param2 $file  要上传的多媒体文件
     * @return 成功返回 媒体文件上传到公众平台上的唯一标识，方便以后使用
     */
    public function uploadMedia($type,$file){
        //上传临时多媒体文件
        $url="https://api.weixin.qq.com/cgi-bin/media/upload";
        $data['access_token']=$this->access_token();
        $data['type']='image';
        $data['media']='@'.$file;  //@区别该内容为文件
        $result=$this->curl($url,'POST',$data);
        $media_id=json_decode($result)->media_id;
        file_put_contents('media',$media_id);
    }

    //获取多媒体文件
    public function getMedia(){
        //$this->uploadMedia('image','1.jpg');
        $access_token=$this->access_token();
        $media_id=file_get_contents('media');  //从开发者的服务器文件读取上传到微信公众平台的文件而保存在本地服务器的media_id
        // echo $media_id;exit;
        $url="https://api.weixin.qq.com/cgi-bin/media/get?access_token={$access_token}&media_id={$media_id}";
        //echo $url;exit;
        header("content-type:image/jpg");
        header("Content-disposition:attachment;filename=".$media_id.".jpg");
        header("Cache-Control: no-cache");
        //file_get_contents($url);
        $this->curl($url,'GET');
    }

    //创建自定义菜单
    public function createMenu(){

        $access_token=$this->access_token();

        $url="https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$access_token}";
        $data='{
                     "button":[
                     {
                          "type":"click",
                          "name":"今日歌曲",
                          "key":"1"
                      },
                      {
                          "type":"click",
                          "name":"游戏",
                          "key":"2"
                      },

                      {
                           "name":"菜单",
                           "sub_button":[
                           {
                               "type":"view",
                               "name":"搜索",
                               "url":"http://www.baidu.com/"
                            },
                            {
                               "type":"view",
                               "name":"视频",
                               "url":"http://www.youku.com/"
                            },
                            {
                               "type":"click",
                               "name":"地图",
                               "key":"3"
                            }]
                       }]
                 }';
        $this->curl($url,'POST',$data);
    }



    //删除自定义菜单
    public function deleteMenu(){
        $access_token=$this->access_token();
        $url="https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=".$access_token;
        echo $this->curl($url,'GET');
    }


    //每日上午10点向我的微信公众号的关注者推送消息
    public function tuisong(){
        //接收关注者的账号
        $openIds=$this->getOpenId();
        //群发文本消息的接口
        $access_token=$this->access_token();
        $url="https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token=".$access_token;
        $data['touser']=$openIds;
        $data['msgtype']='text';
        $data['text']=['content'=>'hello world'];
        $data=json_encode($data);
        echo $this->curl($url,'POST',$data);
    }

    //获取所有的关注者open_id
    private function getOpenId(){
        //获取access_token
        $access_token=$this->access_token();
        $url="https://api.weixin.qq.com/cgi-bin/user/get?access_token=".$access_token;
        $str=$this->curl($url,'GET');
        return json_decode($str,true)['data']['openid'];  //返回关注者列表
    }

    //微信公众平台接收用户的信息进行响应（发送消息）
    public function responseMsg()
    {
        //接收客户端发送的消息（客户端的消息被转换为XML格式数据进行传输）
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
           the best way is to check the validity of xml by yourself */
        libxml_disable_entity_loader(true);  //禁止外部非法加载实体
        //解析从客户端接收的XML数据
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $fromUsername = $postObj->FromUserName;  //客户端的open_id
        $toUsername = $postObj->ToUserName;     //公众平台账号
        $content = trim($postObj->Content);     //接收的普通文本消息的内容
        $time = time();
        //判断从客户端接收的类型
        $msgType=$postObj->MsgType;
        switch($msgType){
            case "event" :
                //接收事件
                //此处填写处理动作
                //关注公众号的事件
                if($postObj->Event=='subscribe'){
                    $this->_doEvent($postObj);
                }elseif($postObj->Event=='SCAN'){
                    $this->_doScan($postObj);
                }elseif($postObj->Event=='CLICK'&&$postObj->EventKey==1){
                    //处理今日歌曲
                    $this->msgMusic($postObj);
                }elseif($postObj->Event=='CLICK'&&$postObj->EventKey==2){
                    //游戏

                }
                break;

            case  "text" :
                //文本消息
                $this->_doText($postObj);
                break;
            case  "image" :
                //图片消息
                $this->_doImage($postObj);
                break;
            case  "voice" :
                //语音消息
                $this->_doVoice($postObj);
                break;
            case  "video" :
                //视频消息
                $this->_doVideo($postObj);
                break;
            case  "shortvideo" :
                //小视频消息
                $this->_doShortVideo($postObj);
                break;
            case  "location" :
                //地理位置消息
                $this->_doLocation($postObj);
                break;
            case  "link" :
                //链接消息
                $this->_toLink($postObj);
                break;
        }
    }

    //关注后的扫码
    public function _doScan($postObj){
        $scene_id=$postObj->EventKey;  //为新闻表中的ID;

        //自己的业务逻辑代码
        mysql_connect("localhost","root","root2413");
        mysql_query("set names utf8");
        mysql_query("use test");
        $sql="delete from news where id=$scene_id";
        if(mysql_query($sql)&&mysql_affected_rows()){
            $content='删除成功';
        }else{
            $content='删除失败';
        }
        $this->msgText($postObj,$content);

    }


    //处理接收的地理位置
    private function _doLocation($postObj){
        // 回复文本消息
        //客户端账号
        $toUserName=$postObj->FromUserName;
        //公众平台账号
        $fromUserName=$postObj->ToUserName;
        //当前时间
        $time=time();
        //回复的内容
        $content=$this->getBank($postObj);
        $textTpl='<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA[%s]]></Content>
                </xml>';
        $textTpl=sprintf($textTpl,$toUserName,$fromUserName,$time,$content);
        echo $textTpl;
    }
    //调用百度LBS里面附近银行信息
    private function  getBank($postObj){
        $location_x=$postObj->Location_X;
        $location_y=$postObj->Location_Y;
        $url="http://api.map.baidu.com/place/v2/search?query=银行&page_size=2&page_num=0&scope=1&location={$location_x},{$location_y}&radius=10000&output=json&ak=LiOG0NPzuGhDOvz8NZEpTQs2";
        $data=json_decode($this->curl($url,'GET'),true);
        $str='距离您最近的银行的信息为:';
        foreach($data['results'] as $key=>$v){
            $str.=$v['name']."地址为".$v['address']."<br/>";
        }
        return $str;
    }


    //回复文本消息
    private function msgText($postObj,$content){
        //客户端账号
        $toUserName=$postObj->FromUserName;
        //公众平台账号
        $fromUserName=$postObj->ToUserName;
        //当前时间
        $time=time();
        $textTpl='<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA[%s]]></Content>
                </xml>';
        $textTpl=sprintf($textTpl,$toUserName,$fromUserName,$time,$content);
        echo $textTpl;
    }

    //回复音乐消息
    private function msgMusic($postObj){
        //客户端账号
        $toUserName=$postObj->FromUserName;
        //公众平台账号
        $fromUserName=$postObj->ToUserName;
        //当前时间
        $time=time();
        //标题
        $title="蝶儿蝶儿满天飞";
        //描述
        $desc="高胜美的经典古典音乐，非常打动人哦";
        //音乐地址
        $music_url="http://wx.wei09.xyz/1.mp3";
        $hmusic_url="http://wx.wei09.xyz/1.mp3";
        $tplMusic="<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[music]]></MsgType>
                <Music>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <MusicUrl><![CDATA[%s]]></MusicUrl>
                <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
                </Music>
                </xml>";
        $tplMusic=sprintf($tplMusic,$toUserName,$fromUserName,$time,$title,$desc,$music_url,$hmusic_url);
        echo $tplMusic;
    }

    //回复图文消息
    public  function msgImage($postObj,$num){
        $imageTpl='<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[news]]></MsgType>
                        <ArticleCount>%s</ArticleCount>
                        <Articles>%s</Articles>
                        </xml>';
        $articles='';
        mysql_connect("localhost","root","root2413");
        mysql_query("set names utf8");
        mysql_query("use test");
        $sql="select * from news limit $num";
        $res=mysql_query($sql);
        while($row=mysql_fetch_assoc($res)){
            $data[]=$row;
        }
        for($i=0;$i<$num;$i++){
            $title=$data[$i]['title'];
            $desc=$data[$i]['content'];
            $picurl=$data[$i]['img_url'];
            $url="https://www.baidu.com";
            $articles.='<item>
                            <Title><![CDATA[%s]]></Title>
                            <Description><![CDATA[%s]]></Description>
                            <PicUrl><![CDATA[%s]]></PicUrl>
                            <Url><![CDATA[%s]]></Url>
                            </item>';
            $articles=sprintf($articles,$title,$desc,$picurl,$url);
        }
        //echo $articles;exit;
        $toUserName=$postObj->FromUserName;
        $fromUserName=$postObj->ToUserName;
        $time=time();
        $articles_num=$num;
        $articles=$articles;
        $content=sprintf($imageTpl,$toUserName,$fromUserName,$time,$articles_num,$articles);
        echo $content;
    }



    //接收文本消息(根据客户端输入的内容给予回复不同的内容)
    private  function _doText($postObj){
        //获取客户端的关键字
        $keyword=$postObj->Content;
        if($keyword=='?' || $keyword=="？"){
            $content="客官，有什么能为您服务的么？我们有很多特殊服务哦？
                        【1】特种服务号码
                        【2】通讯服务号码
                        【3】银行服务号码
                        【4】用户反馈 ";
            $this->msgText($postObj,$content);
        }elseif($keyword==1){
            //$content="火警119,肥斤110,晚间热线电话请输入999";
            $content=$postObj->ToUserName;   //返回公众平台账号信息
            $this->msgText($postObj,$content);
        }elseif($keyword==2){
            $content=$postObj->FromUserName;  //返回关注者的open_id;
            $this->msgText($postObj,$content);
        }elseif($keyword==3){
            $content="支持在线支付，可提前预定，详情请输入4";
            $this->msgText($postObj,$content);
        }elseif($keyword==4){
            $content="您已经被五四派出所监控了，请带上500块钱保证金";
            $this->msgText($postObj,$content);
        }elseif($keyword=='音乐'){
            //回复音乐消息
            $this->msgMusic($postObj);

        }elseif($keyword=='单图文'){
            //回复图文消息（1条图文消息）
            $this->msgImage($postObj,1); //1代表一条图文

        }elseif($keyword=='多图文'){
            //回复图文消息（多条）
            $this->msgImage($postObj,6);
        }else{
            //调用图灵机器人  get请求
            $info=$keyword;
            $url="http://www.tuling123.com/openapi/api?key=e825286159f9f57db1b597995d72ae2b&info={$info}&userid=1234";
            $content=$this->curl($url,'GET');
            $content=json_decode($content)->text;
            //通过输入的内容调用相应的聊天接口，把聊天的回复内容获取到通过公众号进行发送
            $this->msgText($postObj,$content);
        }
    }

    //关注
    private function _doEvent($postObj){
        // 回复文本消息
        //回复的内容
        $content="欢迎关注php技术交流的公众平台";
        //获取场景ID值
        $this->msgText($postObj,$content);
    }


    /*
     * 根据票据生成二维码 qrcode
     * @param1  string $type 默认值 tmp 代表生成临时二维码
     * @scene_id  int/string $scene_id 场景ID
     * @expire   int 临时二维码的过期时间，默认值为 604800
     * @return   二进制流的图片
     */
    public function qrcode($secne_id,$type=0,$expire=604800){
        //获取票据
        $ticket=$this->ticket($secne_id,$type=0,$expire=604800);
        //echo $ticket;exit;
        $url="https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".urlencode($ticket);
        $result=$this->curl($url,'GET');
        //把图片保存在本地
        // file_put_contents('qrcode.jpg',$result);
        //直接输出
        header("Content-Type:image/jpg");
        echo $result;
    }


    //创建票据给生成二维码提供
    /*
     * 创建二维码的票据  ticket
     * @param1  $type 默认值 0 代表生成临时二维码
     * @scene_id  $scene_id 场景ID
     * @expire   临时二维码的过期时间，默认值为 604800
     * return  返回一个ticket票据
     */
    private function ticket($secne_id,$type=0,$expire=604800){
        //获取access_token
        $access_token=$this->access_token();
        $url="https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$access_token;
        if($type==0){
            //临时二维码
            $data='{"expire_seconds": %s, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": %s}}}';
            $data=sprintf($data,$expire,$secne_id);
        }elseif($type==1){
            //永久字符串二维码
            $data='{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "%s"}}}';
            $data=sprintf($data,$secne_id);
        }elseif($type==2){
            $data='{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": %s}}}';
            $data=sprintf($data,$secne_id);
        }
        //模拟post请求
        $tiket=$this->curl($url,'POST',$data);
        return json_decode($tiket,true)['ticket'];
    }


    //获取access_token
    public  function access_token(){
        //2小时过期,为了避免重复调用，把该token值保留在文件中
        $filename='access_token';
        if(file_exists($filename) && (time()-filemtime($filename))<7200)
        {
            //从文件中读取凭证
            return  file_get_contents($filename);
        }else{
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx50909fb4a2f19f65&secret=e09eabf607564c6e41ae79d094c21a12";
            $str=curl($url,'GET');
            echo $str;die;
            $str=json_decode($str,true)['access_token'];
            file_put_contents($filename, $str);

        }
        return $str;
    }

    //处理请求
    public  function  curl($url,$method,$data=array(),$setcooke=false,$cookie_file='1.txt'){
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
            //就从文件中读取cookie的信息
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        }
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