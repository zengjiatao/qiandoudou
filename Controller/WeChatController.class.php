<?php
# 银钱包对接接口 - 微信api接口

namespace Payapi\Controller;
use Think\Controller;
/**
 * 首页
 */
define("TOKEN", "weixin");//定义你公众号自己设置的token
define("APPID", "wxe833c53aafe42ff9");//填写你微信公众号的appid 千万要一致啊
define("APPSECRET", "6c4e8ccc1c681cc63ae291ce0fa48349");//填写你微信公众号的appsecret  千万要记得保存 以后要看的话就只有还原了  保存起来 有益无害
class WeChatController extends Controller
{
    //判断是介入还是用户  只有第一次介入的时候才会返回echostr
    public function index()
    {
        //这个echostr呢  只有说验证的时候才会echo  如果是验证过之后这个echostr是不存在的字段了
        $echoStr = $_GET["echostr"];
        if ($this->checkSignature()) {
        // M("ceshi")->add(array('text'=>json_encode($_GET)));
        ob_clean();
            echo $echoStr;
            //如果你不知道是否验证成功  你可以先echo echostr 然后再写一个东西
            exit;
        }
    }//index end
    //验证微信开发者模式接入是否成功
    public function checkSignature()
    {
        //signature 是微信传过来的 类似于签名的东西
        $signature = $_GET["signature"];
        //微信发过来的东西
        $timestamp = $_GET["timestamp"];
        //微信传过来的值  什么用我不知道...
        $nonce     = $_GET["nonce"];
        //定义你在微信公众号开发者模式里面定义的token
        $token  = "weixin";
        //三个变量 按照字典排序 形成一个数组
        $tmpArr = array(
            $token,
            $timestamp,
            $nonce
        );


        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        //哈希加密  在laravel里面是Hash::
        $tmpStr = sha1($tmpStr);
        //按照微信的套路 给你一个signature没用是不可能的 这里就用得上了
        if ($tmpStr == $signature) {
            return true;
        } else {
        // M("ceshi")->add(array('text'=>'false'));
            return false;
        }
    }

    // checkSignature end
    //构建一个发送请求的curl方法  微信的东西都是用这个 直接百度
   public  function https_request($url, $data = null)
    {
        //这个方法我不知道是怎么个意思  我看都是这个方法 就copy过来了
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }//https_request end
} //classend