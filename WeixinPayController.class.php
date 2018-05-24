<?php
namespace Func\Controller;
use Think\Controller;

/**
 * Class WeixinPayController
 * @package Func\Controller
 * 微信支付。 APP支付 / 公众号支付
 */
class WeixinPayController extends Controller
{

    public function H5payParam($weid=2,$order=array())
    {
        $wechat = M('wxconfig')->where(array('weid'=>$weid))->find();
        $package = array();
        $package['appid'] = $wechat['appid'];
        $package['mch_id'] = $wechat['paymchid'];
        $package['nonce_str'] = mt_rand(10000000,99999999);
        $package['body'] = "缴费支付";
        $package['device_info'] = 'h5pay';
        $package['attach'] = $weid . ':' . "TKPAY"; //附加数据  -- TKPAY 推客缴费支付;
        $package['out_trade_no'] = $order['pt_ordersn'];
        $package['total_fee'] = $order['price'] * 100;
        $package['spbill_create_ip'] = get_client_ip();
        if (!empty($params['goods_tag']))
        {
            $package['goods_tag'] = $params['goods_tag'];
        }
        # 回调地址 - 线上地址
        $hostUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'];
        $hostUrl = "https://wallet.insoonto.com/";
        $package['notify_url'] = $hostUrl."payment/weixin/notify.php";
        $package['trade_type'] = 'JSAPI';

        $openid = "oJOshs6gidsZ5KhfWw0VS-po3hBg";

        $package['openid'] = $openid;
        ksort($package, SORT_STRING);
        $string1 = '';
        foreach ($package as $key => $v )
        {
            if (empty($v))
            {
                continue;
            }
            $string1 .= $key . '=' . $v . '&';
        }
        $string1 .= 'key=' . $wechat['paysignkey'];
        $package['sign'] = strtoupper(md5($string1));
        $dat = ToXml($package);
        dump(FromXml($dat));


        $response = globalCurlPost('https://api.mch.weixin.qq.com/pay/unifiedorder', $dat);
        $reslt = FromXml($response);
        dump($reslt);


        if ($reslt['return_code'] == "FAIL")
        {
            return array('code'=>200,'msg'=>$reslt['return_msg']);
        }
        $xml = @simplexml_load_string($response['content'], 'SimpleXMLElement', LIBXML_NOCDATA);
        if (strval($xml->return_code) == 'FAIL')
        {
            return array('code'=>200,'msg'=>strval($xml->return_msg));
        }
        if (strval($xml->result_code) == 'FAIL')
        {
            return array('code'=>200,'msg'=>strval($xml->err_code) . ': ' . strval($xml->err_code_des));
        }
        $prepayid = $xml->prepay_id;
        $wOpt['appId'] = $wechat['appid'];
        $wOpt['timeStamp'] = TIMESTAMP . '';
        $wOpt['nonceStr'] =  mt_rand(10000000,99999999);
        $wOpt['package'] = 'prepay_id=' . $prepayid;
        $wOpt['signType'] = 'MD5';
        ksort($wOpt, SORT_STRING);
        $string = '';
        foreach ($wOpt as $key => $v )
        {
            $string .= $key . '=' . $v . '&';
        }
        $string .= 'key=' . $wechat['paysignkey'];
        $wOpt['paySign'] = strtoupper(md5($string));

        $logData['user_id'] = trim($order['user_id']);
        $logData['pt_ordersn'] = trim($order['pt_ordersn']);
        $logData['user_pay_supply_id'] = 18;
        $logData['price'] = trim($order['price']);
        $logData['type'] = 1;
        $logData['t'] = time();
//        M("jfpay_log")->add($logData);

        return array('code' => 400, 'msg' => '微支付调起支付参数', 'data' => array("wechat"=>$wOpt));
    }


    /** APP支付
     * @param int $weid
     * @param string $type
     * @return array
     * $weid = 数据库中公众号ID ,  $order=array(); 订单信息
     */
    public function APPpayParam($weid=3,$order=array())
    {
        $uid = $order['user_id'];
//        if(!$uid){
//            return array('code'=>200,'msg'=>'你没有登录');
//        }
        $data = array();
//        $getCgOrder = $type.date("YmdHis",time()).mt_rand(10,88); // 生成订单
//        $order['pt_ordersn'] = $getCgOrder;
        $config = M('wxconfig')->where(array('weid'=>$weid))->find();

        /*
            $config['appid'] = 'wx1c257b2086f449c1';
            $config['paymchid'] = '1496407402';
            $config['paysignkey'] = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8A';
        */

        $iforder = M("jfpay_log")->where(array('pt_ordersn'=>$order['pt_ordersn']))->getField('jfpay_log_id');

        if($iforder){
            return array('code'=>200,'msg'=>'订单已存在');
        }
        if($iforder['is_pay'] == 1){
            return array('code'=>200,'msg'=>'订单已支付');
        }
        $data['appid']	= $config['appid']; //应用ID
        $data['attach']	= $weid . ':' . $order['typepe'].':'.$order['is_kfp'];//附加数据  -- TKPAY 推客缴费支付
        $data['body'] = $order['body']; //商品描述
        $data['mch_id']	= $config['paymchid']; //商户号
        $data['nonce_str'] = mt_rand(100000000000,9999999999999);	//随机字符串
        # 回调地址 - 线上地址
        $hostUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'];
        $hostUrl = "https://wallet.insoonto.com/";
        $data['notify_url'] = $hostUrl."Payapi/WeixinPay/payReturn";
        $data['out_trade_no'] = $order['pt_ordersn']; //商户订单号
        $data['spbill_create_ip']	= $_SERVER['REMOTE_ADDR'];//终端IP
        $data['total_fee'] = ($order['price'])*100; //总金额，单位：分
        $data['trade_type']	= 'APP'; //交易类型
        $data['sign'] = getSign($data,$config['paysignkey']);
        //print_r($data);exit;
        $set = array(
            'key' => trim($config['paysignkey']),
            'appid' => trim($config['appid']),
            'mch_id' => trim($config['paymchid']),
            'order_sn' => trim($data['out_trade_no'])
        );
        $logData['user_id'] = trim($order['user_id']);
        $logData['pt_ordersn'] = trim($order['pt_ordersn']);
        $logData['user_pay_supply_id'] = 18;
        $logData['price'] = trim($order['price']);
        $logData['level']=$order['level'];
        $logData['type'] = 1;
        if($order['is_kfp'] == 1)
        {
            $logData['sf']	= M("site_config")->where(array('site_config_id'=>1))->getField('sf'); // 税费
        }else{
            $logData['sf']	= 0;
        }
        $logData['t'] = time();
        M("jfpay_log")->add($logData);  // 记录微信支付
        $xmlData = ToXml($data);
        $postUrl = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $resXML	= globalCurlPost($postUrl,$xmlData);
        $resArr = FromXml($resXML);
        $set = array_merge($resArr,$set);
        //$data = returnData($set);
        // print_r($data);exit;
        return returnData($set); # 微信支付需要的参数
    }

    #查询订单状态
      public function selectOrder($weid=3,$order=''){

//        if(!$uid){
//            return array('code'=>200,'msg'=>'你没有登录');
//        }
          $data = array();
//        $getCgOrder = $type.date("YmdHis",time()).mt_rand(10,88); // 生成订单
//        $order['pt_ordersn'] = $getCgOrder;
          $config = M('wxconfig')->where(array('weid'=>$weid))->find();

          /*
          $config['appid'] = 'wx1c257b2086f449c1';
          $config['paymchid'] = '1496407402';
          $config['paysignkey'] = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8A';
          */
          //构造参数
          $data['appid']	= $config['appid']; //应用ID
          $data['mch_id']	= $config['paymchid']; //商户号
          $data['out_trade_no']=$order;
          $data['nonce_str'] = mt_rand(100000000000,9999999999999);	//随机字符串
          $data['sign'] = getSign($data,$config['paysignkey']);

          $xmlData = ToXml($data);
          $postUrl = "https://api.mch.weixin.qq.com/pay/orderquery";
          $resXML	= globalCurlPost($postUrl,$xmlData);
          $resArr = FromXml($resXML);
//                    dump($resArr);die;
          return $resArr;
      }

}




/**微信签名包
 * @param $arr
 * @param $key
 * @return string
 */
function getSign($arr,$key)
{
    ksort($arr);//排序
    $str	= ToUrlParams($arr);
//    $str 	= $str . "&key=".$key;
    $str 	= $str . "&key=".$key;

//    echo $str;

    $str 	= md5($str);
    return strtoupper($str);
}
/**
 * 输出xml字符
 * @throws WxPayException
 *数组转化成xml数据
 **/
function ToXml($arr)
{
    if(!is_array($arr) || count($arr) <= 0)
    {
        return "";
    }

    $xml = "<xml>";
    foreach ($arr as $key=>$val)
    {
        if (is_numeric($val)){
            $xml.="<".$key.">".$val."</".$key.">";
        }else{
            $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
        }
    }
    $xml.="</xml>";
    return $xml;
}
/**
 * 将xml转为array
 * @param string $xml
 * @throws WxPayException
 */
function FromXml($xml)
{
    if(!$xml){
        return array();
    }
    //将XML转为array
    //禁止引用外部xml实体
    //libxml_disable_entity_loader(true);
    $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $array_data;
}
/**
 * 格式化参数格式化成url参数
 */
function ToUrlParams($arr)
{
    $buff = "";
    foreach ($arr as $k => $v)
    {
        if($k != "sign" && $v != "" && !is_array($v)){
            $buff .= $k . "=" . $v . "&";
        }
    }

    $buff = trim($buff, "&");
    return $buff;
}
/**发送微信支付请求
 * @param $url
 * @param string $data
 * @param int $second
 * @return mixed
 */
function globalCurlPost($url,$data='',$second=30)
{
    $ch = curl_init();
    $header = "Accept-Charset: utf-8";
    curl_setopt($ch, CURLOPT_TIMEOUT, $second);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $temp = curl_exec($ch);
    curl_close($ch);
    return $temp;
}
function getPayApiSign($data){
    $package				= "prepay_id=".trim($data['prepay_id']);
    $timeStamp				= strval(time());
    $nonceStr = mt_rand(100000000000,9999999999999);	//随机字符串

    $appid = trim($data['appid']);
    $key = trim($data['key']);
    $mch_id = trim($data['mch_id']);
    $arr = array();
    $arr['timestamp']	= $timeStamp;
    $arr['appid']		= $appid;
    $arr['partnerid']	= $mch_id;
    $arr['noncestr']	= $nonceStr;
    $arr['prepayid']	= trim($data['prepay_id']);
    $arr['package']		= "Sign=WXPay";//$arr['package'] = $package;
    $arr['sign']		= getSign($arr,$key);

    return $arr;
}
////接口返回数据
function returnData($arr)
{
    //print_r($arr);exit;
    if ($arr['return_code'] != "SUCCESS") {

        return array('code' => 200, 'msg' => "调用支付失败(" . $arr['return_msg'] . ")");

    }

    if ($arr['result_code'] == "SUCCESS") {
        //print_r($arr);exit;
        $signPackage = getPayApiSign($arr);        //jsapi 验签
        // print_r($signPackage);exit;

        if ($arr['addr'] != "order") {

            //成功调用接口，记录数据
            //$orderSn = $arr['order_sn'];
//            $where['order_sn'] = $arr['order_sn'];
//            M('product_cart2')->where($where)->setField("paysign",$arr['prepay_id']);
//
//            $content = date("Y-m-d H:i:s")."：调用支付成功（".json_encode($signPackage)."）\r\n";
//            R('Api/LogApi/write',array($content,$this->setTextFile));
        }
        return array('code' => 400, 'msg' => '微支付调起支付参数', 'data' => $signPackage);

    } else {
        //调起支付失败
        //错误代码：
        $errArr = array(
            "NOAUTH" => "商户无此接口权限",
            "NOTENOUGH" => "余额不足",
            "ORDERPAID" => "商户订单已支付",
            "ORDERCLOSED" => "订单已关闭",
            "SYSTEMERROR" => "系统错误/系统超时",
            "APPID_NOT_EXIST" => "APPID不存在",
            "MCHID_NOT_EXIST" => "MCHID不存在",
            "APPID_MCHID_NOT_MATCH" => "appid和mch_id不匹配",
            "LACK_PARAMS" => "缺少参数",
            "OUT_TRADE_NO_USED" => "商户订单号重复",
            "SIGNERROR" => "签名错误",
            "XML_FORMAT_ERROR" => "XML格式错误",
            "REQUIRE_POST_METHOD" => "XML格式错误",
            "REQUIRE_POST_METHOD" => "请使用post方法",
            "POST_DATA_EMPTY" => "post数据为空",
            "NOT_UTF8" => "编码格式错误"
        );
        $errCode = $arr['err_code'];    //记录错误码
        $errMsg = "";

        foreach ($errArr as $k => $v) {
            if ($k == $errCode) {
                $errMsg = $v;
            }
        }

        return array('code' => 400, 'msg' => $errMsg . "(" . $arr['err_code_des'] . ")");
    }
}