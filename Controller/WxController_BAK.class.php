<?php
# 银钱包对接接口 - 微信api接口

namespace Payapi\Controller;
use Think\Controller;
class WxController extends Controller{

    public $wxconfig;
    public function _initialize()
    {
        $this->wxconfig = M('wxconfig')->where(array('weid'=>1))->find();
    }

    public function delt()
    {
        M("test")->where("id!=0")->delete();
    }

    // 微信被动执行器 -- 去掉
    public function responseMsg()
    {
        $postArr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postArr)){
            $postObj = simplexml_load_string($postArr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $MsgType = trim($postObj->MsgType);
            M("test")->add(array('text'=>json_encode($MsgType)));
        }else {
            echo "";
            exit;
        }
    }

    // 自动处理服务器配置验证
    public function index()
    {
        $weObj = R('Func/Wxapi/getConfig');
//        ob_clean();
        // $weObj->getRev()->getRevType();
                $type = $weObj->getRev()->getRevType();
                // $data=$weObj->getRevEvent(); //接收事件推送是否等于订阅(subscribe)事件,可获得事件和key
//        M("test")->add(array("text"=>$type));

//              foreach ($info as $k=>$v){
//                  if (trim($msg) == $v['textKey']){
//                      $weObj->text($v['textReply'])->reply();
//                  }
//              }
        $returnMsg = M('wx_text')->where(array('id'=>1))->find();
            switch($type) {
                   case "text":
//                       getRevContent   接收文本消息
                    $msg = $weObj->getRevContent();
                    $info = R('Func/Wxapi/getText');
                    $condtion['textKey']=array("like","%".trim($msg)."%");
                    $returnInfo = M('wx_text')->where($condtion)->find();
                    if ($returnInfo){
                        $weObj->text($returnInfo['textReply'])->reply();
                    }else{
//                        $weObj->text('该功能正在完善,谢谢光顾')->reply();
                        $weObj->text($returnMsg['textKey'])->reply();
                    }
                       exit;
                       break;
                   case "event":
                       $weObj->text($returnMsg['textKey'])->reply();
                       $data=$weObj->getRevEvent(); //接收事件推送是否等于订阅(subscribe)事件,可获得事件和key
                       //扫码事件
                       //要根据  ..订阅..  公众号事件接下面的逻辑
                       if ($data['event'] == 'SCAN') {  # 已关注扫码

                            $openid = $weObj->getRevFrom(); //拿到openid
                            $canshu = $weObj->getRevSceneId();//获取到二维码参数值
                            $userInfo = $weObj->getUserInfo($openid); //根据openid获取到用户信息


                       }elseif($data['event'] == 'subscribe') # 未关注扫码( 绑定上下级 )
                       {
                            $openid = $weObj->getRevFrom(); //拿到openid
                            $canshu = $weObj->getRevSceneId();//获取到二维码参数值
                            // M('test')->add(array('text'=>$canshu));
                            $userInfo = $weObj->getUserInfo($openid); //根据openid获取到用户信息
                            $where = array('openid'=>$userInfo['openid']);
                            $re = M('mapping_fans')->where($where)->find();
                            $w['text']=$canshu;
                            M('cs')->add($w);
                             if ($re) {
                                 $fans['fanid']=$re['fanid'];
                                 $fans['uniacid']=1;
                                 $fans['openid']=$userInfo['openid'];
                                 $fans['nickname']=$userInfo['nickname'];
                                 $fans['headimg']=$userInfo['headimgurl'];
                                 $fans['sex']=$userInfo['sex'];
                                 $fans['follow']=$userInfo['subscribe'];
                                 $fans['updatetime']=time();
                                 M('mapping_fans')->save($fans);
                             }else{
                                 $fanss['uniacid']=1;
                                 $fanss['openid']=$userInfo['openid'];
                                 $fanss['nickname']=$userInfo['nickname'];
                                 $fanss['headimg']=$userInfo['headimgurl'];
                                 $fanss['sex']=$userInfo['sex'];
                                 $fanss['follow']=$userInfo['subscribe'];
                                 $fans['updatetime']=time();

                                M('mapping_fans')->add($fanss);
                             }
//                             if ($canshu == ''){
//                              $canshu='111111';
//                             }

                             //用户表
                            $userShuju = M('user')->where(array('openid'=>$userInfo['openid']))->find();
//                             if ($userShuju['pid'] != 0){
//
//                             }
                            $parentInfo = M('user')->where(array('id'=>$userShuju['pid']))->find();
                           if($canshu)
                           {
                               $userPinfoid = M('user')->where(array('tg_code'=>$canshu))->getField('id');
                               // if ($userPinfo) {
                               //   $res = M('user')->add($userInfo);
                               // }else{
                               // }
                               if ($parentInfo['pid'] == 0){
                                   if($userPinfoid)
                                   {
                                       if (empty($userInfo['pid'])){
                                           $userInfoo['pid']=$userPinfoid;
                                       }
                                   }
                               }
                           }
                           if ($userShuju) {
                               $userInfoo['id']=$userShuju['id'];
                               $userInfoo['openid']=$userInfo['openid'];
                               $userInfoo['nick_name']=$userInfo['nickname'];
                               $userInfoo['head_img']=$userInfo['headimgurl'];
                               $userInfoo['sex']=$userInfo['sex'];
                               $userInfoo['utype']='1';
                               M('user')->save($userInfoo);
                           }else{
                               $userInfoo['openid']=$userInfo['openid'];
                               $userInfoo['nick_name']=$userInfo['nickname'];
                               $userInfoo['head_img']=$userInfo['headimgurl'];
                               $userInfoo['sex']=$userInfo['sex'];
                               $userInfoo['utype']='1';
                               M('user')->add($userInfoo);
                           }

                            // M('test')->add(array('text'=>$canshu));
                       }
                       break;
                   default:
                       $weObj->text($returnMsg['textKey'])->reply();
            }
    }

    # 暂不使用该方法
    public function subscribe(){
        $weObj = R('Func/Wxapi/getConfig');
        // M('test')->add(array('text'=>'123'));
        // $res->text("help info")->reply();
        // // $openid = $res->getRevTo();  # openid
        // // $type = $res->getRev()->getRevType();  # 接收微信方法
        // $dingyue = $res->getRevEvent();
        // if ($dingyue['event'] == Wechat::EVENT_SUBSCRIBE) {
        //     M('test')->add(array('text'=>'123'));
        // }
        // getRevTicket
        // getRevEvent
        // // 捕捉扫码事件
        $type = $weObj->getRev()->getRevType();
            switch($type) {
                   case Wechat::MSGTYPE_TEXT:
                       $weObj->text("欢迎关注银钱包公众号")->reply();
                       exit;
                       break;
                   case Wechat::EVENT_SCAN:
                       //扫码事件
                       //要根据  ..订阅..  公众号事件接下面的逻辑
                       $data=$weObj->getRevEvent(); //接收事件推送是否等于订阅(subscribe)事件,可获得事件和key
                       if ($data['Event'] == 'subscribe') {
                        $openid = $weObj->getRevFrom(); //拿到openid
                        $canshu = $weObj->getRevSceneId();//获取到二维码参数值
                        $userInfo = $weObj->getUserInfo($openid); //根据openid获取到用户信息
                        $uInfo['subscribe']=$userInfo['subscribe'];
                        $uInfo['openid']=$userInfo['openid'];
                         $uInfo['nickname']=$userInfo['nickname'];
                         $uInfo['sex']=$userInfo['sex'];
                         $uInfo['city']=$userInfo['city'];
                         $uInfo['country']=$userInfo['country'];
                         $uInfo['province']=$userInfo['province'];
                         $uInfo['headimgurl']=$userInfo['headimgurl'];
                         $uInfo['subscribe_time']=$userInfo['subscribe_time'];
                         $uInfo['unionid']=$userInfo['unionid'];
                         $uInfo['remark']=$userInfo['remark'];
                         $uInfo['groupid']=$userInfo['groupid'];
                         $uInfo['tagid_list']=$userInfo['tagid_list'];
                         $uInfo['ewcode']=$canshu;
                         M('ceshi')->add($uInfo);
                        //保存关注用户的信息
                        // $where=array('openid'=>$openid);
                        // $fans = M('mapping_fans')->where($where)->find(); //粉丝表
                        // if ($fans) {
                        //     $fansInfo['']=$userInfo
                        // }
                        // $res = M('user')->where($where)->find();//用户表
                       }
                       break;
                   case Wechat::MSGTYPE_IMAGE:
                       $weObj->text("欢迎关注银钱包公众号")->reply(); 
                       break;
                   default:
                       $weObj->text("欢迎关注银钱包公众号")->reply();
            }
    }
    //自定义创建菜单
    public function createWxMenu(){
        $shuju = M('wxmenus')->where(array('is_show'=>1,'menu_pid'=>0))->select();
        // dump($shuju);die;
        $news = array();
        foreach ($shuju as $k => $v) {
            $v1['type']='view';
            $v1['name']=$v['menu_name'];
            $v1['url']=$this->codeurl($v['menu_url']);
            $news[]=$v1;
            $pid = array('menu_pid'=>$v['id'],'is_show'=>1);
            $erji = M('wxmenus')->where($pid)->select();
            foreach ($erji as $kk => $vv) {
                if ($vv['menu_pid']==$v['id']) {
                    $vv1['type']='view';
                    $vv1['name']=$vv['menu_name'];
                    $vv1['url']=$this->codeurl($vv['menu_url']);
                    $news[$k]['sub_button'][]=$vv1;
                    unset($news[$k]['type']);
                    unset($news[$k]['url']);
                }
            }
        }
        $array =array('button'=>$news);
        // echo "<pre>";
        // dump($array);die;
        $weObj = R('Func/Wxapi/getConfig');
        // $weObj->valid();
        $res = $weObj->createMenu($array);
        return $res;
    }

    // 测试加密
    public function duijiewx(){
        import('Vendor.WechatDj.wxBizMsgCrypt');
        $encodingAesKey = "EnktmIJJL68qb1RHD67uRV19samLTrZo8mnvCHZvIjG";
        $token = "weixin";
        $timeStamp = "1409304348";
        $nonce = "321123";
        $appId = "wxe833c53aafe42ff9";
        $text = "<xml><ToUserName><![CDATA[oia2Tj我是中文jewbmiOUlr6X-1crbLOvLw]]></ToUserName><FromUserName><![CDATA[gh_7f083739789a]]></FromUserName><CreateTime>1407743423</CreateTime><MsgType><![CDATA[video]]></MsgType><Video><MediaId><![CDATA[eYJ1MbwPRJtOvIEabaxHs7TX2D-HV71s79GUxqdUkjm6Gs2Ed1KF3ulAOA9H1xG0]]></MediaId><Title><![CDATA[testCallBackReplyVideo]]></Title><Description><![CDATA[testCallBackReplyVideo]]></Description></Video></xml>";
        $pc = new \WXBizMsgCrypt($token, $encodingAesKey, $appId);
        $encryptMsg = '';
        $errCode = $pc->encryptMsg($text, $timeStamp, $nonce, $encryptMsg);
        if ($errCode == 0) {
            print("加密后: " . $encryptMsg . "\n");
        } else {
            print($errCode . "\n");
        }

        $xml_tree = new \DOMDocument();
        $xml_tree->loadXML($encryptMsg);
        $array_e = $xml_tree->getElementsByTagName('Encrypt');
        $array_s = $xml_tree->getElementsByTagName('MsgSignature');
        $encrypt = $array_e->item(0)->nodeValue;
        $msg_sign = $array_s->item(0)->nodeValue;

        $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
        $from_xml = sprintf($format, $encrypt);

        // 第三方收到公众号平台发送的消息
        $msg = '';
        $errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
        if ($errCode == 0) {
            print("解密后: " . $msg . "\n");
        } else {
            print($errCode . "\n");
        }
    }
    public function decode(){
        $a = urldecode('https%3A%2F%2Fwallet.insoonto.com%2Findex.php%2FWap%2FDai%2FgetList');
        echo $a;
    }
    //授权登录
    public function codeurl($url=''){
        $res = R('Func/Wxapi/getConfig');
        //获得code
        // $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx29d53cc438330ecf&redirect_uri=https%3A%2F%2Fwallet.insoonto.com%2FPayapi%2FWx%2Fsouquan&response_type=code&scope=SCOPE&state=STATE#wechat_redirect";
        $souquanurl = $res->getOauthRedirect($url,'STATE');
        // $code = $_GET['code'];
        // dump($code);die;
        return $souquanurl;
        // dump($souquanurl);die;
        // return $souquanurl;
    }

    # 于2017.10.26日编写
    # 生成带参数的二维码
    # $scene_id 存的字符串值  例子参数，5位数。例子：   45dyh 、33yuw
    # return 二维码路径
    public function creatEwCode($scene_id="")
    {
        $wx = R('Func/Wxapi/getConfig');
        $ticket = $wx->getQRCode($scene_id,2); // 0:临时二维码；1:数值型永久二维码(此时expire参数无效)；2:字符串型永久二维码(此时expire参数无效)
        $url = $wx->getQRUrl($ticket);
        $d['ticket'] = $ticket['ticket'];
        $d['url'] = $url;
        M("tk_ewcode")->add($d);
        return $url.$ticket['ticket'];
    }
}