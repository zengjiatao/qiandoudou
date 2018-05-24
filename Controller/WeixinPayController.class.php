<?php
/**
 * Created by 银迅通支付网络公司.
 * User: Jeffery
 * Date: 2017/12/19
 * Time: 16:43
 * 银联快捷支付 支付交易接口
 */

namespace Payapi\Controller;

class WeixinPayController
{
    public $uid;
    public $table;
    public $jstable;

    public function _initialize()
    {
        $this->uid = trim($_REQUEST['uid']);
        $open = 1;
        if ($open == 1) # 正式数据表
        {
            $table = "money_detailed";
            $jstable = "moneyjs_detailed";
        } else { # 测试数据表
            $table = "money_zf_detailed";
            $jstable = "money_zfjs_detailed";
        }
        $this->table = $table;
        $this->jstable = $jstable;

        /**/
        # 加密控制
        if (ACTION_NAME != "payReturn") {
            $this->parem = array(
                'signType' => $_REQUEST['signType'],
                'timestamp' => $_REQUEST['timestamp'],
                'dataType' => $_REQUEST['dataType'],
                'inputCharset' => $_REQUEST['inputCharset'],
                'version' => $_REQUEST['version'],
            );
            $this->sign = $_REQUEST['sign'];
        }

    }

    # h5跳转
    public function H5tkpaypagebak()
    {
        $weObj = R('Func/Wxapi/getConfig');

    }

    # h5页面支付1
    public function H5tkpaypage()
    {
        $weObj = R('Func/Wxapi/getConfig');
        $openid=session('openid');

        if(!$openid)
        {
            $res = R('Func/Wxapi/getConfig');
            $url ="https://wallet.insoonto.com/index.php/Payapi/WeixinPay/H5tkpaypage?weid=2";
            $souquanurl = $res->getOauthRedirect($url,'STATE');
            header("location:".$souquanurl);exit;
        }

        /*
            ["access_token"] => string(88) "6_5fPNpqLtb0U7WJFG4CfHRif4ukF5iTe1aZ-DS-h_K9OLmnmbVQ4NA26HmZALC8UnIgnLzB_jqzNeDLGKEwfTcQ"
            ["expires_in"] => int(7200)
            ["refresh_token"] => string(88) "6__r5g7NEuQGgAlReAiAgwd49MYNBIhK7MEBdT9zGrXdirQpD0bWNsWgfPSIC2nCcwhJRqgnM6SJyis-6krYdPqw"
            ["openid"] => string(28) "oMZ4r1WTKzp1D546fVtn9RL5noEs"
            ["scope"] => string(15) "snsapi_userinfo"111
        */

        $res = $weObj->getOauthAccessToken();
        $openid = $weObj->getRevFrom(); //拿到openid
        $userInfo = $weObj->getUserInfo($openid); //根据openid获取到用户信息
        $where = array('openid'=>$userInfo['openid']);
        $re = M('mapping_fans')->where($where)->find();

        dump($userInfo);



        if ($re) {

            $fans['mapping_fans_id']=$re['mapping_fans_id'];

            $fans['uniacid']=$_REQUEST['weid'];

            $fans['openid']=$userInfo['openid'];

            $fans['nickname']=$userInfo['nickname'];

            $fans['headimg']=$userInfo['headimgurl'];

            $fans['sex']=$userInfo['sex'];

            $fans['follow']=$userInfo['subscribe'];

            $fans['updatetime']=time()+3;

//            M('mapping_fans')->save($fans);

        }else{

            $fanss['uniacid']=$_REQUEST['weid'];

            $fanss['openid']=$userInfo['openid'];

            $fanss['nickname']=$userInfo['nickname'];

            $fanss['headimg']=$userInfo['headimgurl'];

            $fanss['sex']=$userInfo['sex'];

            $fanss['follow']=$userInfo['subscribe'];

            $fans['updatetime']=time()+4;



//            M('mapping_fans')->add($fanss);

        }
        $_SESSION['openid'] = trim($res['openid']);

        exit;
    }

    # 微信支付
    public function H5tkpay()
    {
        $getCgOrder = "TKPAY".date("YmdHis",time()).mt_rand(10,88); // 生成订单
        $order['pt_ordersn'] = $getCgOrder;
        $order['user_id'] = $_REQUEST['uid'];
        $order['price'] = 10;
        $res = R("Func/WeixinPay/H5payParam",array(2,$order));
        echo json_encode($res);exit;
    }

    # APP支付 - 推客缴费
    public function APPtkpay()
    {
        $parem=array(

            'signType'=>$_REQUEST['signType'],

            'timestamp' => $_REQUEST['timestamp'],

            'dataType' => $_REQUEST['dataType'],

            'inputCharset' => $_REQUEST['inputCharset'],

            'version' => $_REQUEST['version'],

        );

//        $sign = $_REQUEST['sign'];
//        if (!$sign){
//
//            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;
//
//        }
//
//        $array=array(
//
//            'uid'=>$_REQUEST['uid'],
//
//        );

//        $parem = array_merge($parem,$array);
//
//        $msg = R('Func/Func/getKey',array($parem));//返回加密
//
//        if ($sign !== $msg){
//
//            echo json_encode(array('code'=>10004,'msg'=>'网络异常,请重新登录'));die;
//
//        }
//
//        R('Func/Func/getTwoSign',array($sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
//
//        $_SESSION['last_sign'] = $sign;//把sign存入session 为作判断

        if(!$_REQUEST['level']){
            echo json_encode(array('code'=>4203,'msg'=>'level为空'));die;
        }
        //生成订单
        $getCgOrder = "PPF".get_timeHm(); // 生成订单
        $order['pt_ordersn'] = $getCgOrder;
        $order['user_id'] = trim($_REQUEST['uid']);
        $order['body'] = '品牌服务费';
        $order['price'] = trim($_REQUEST['price']);
        $order['level']=trim($_REQUEST['level']);
        $order['is_kfp']=trim($_REQUEST['is_kfp']);
        $order['typepe'] = "TKPAY";
        $res = R("Func/WeixinPay/APPpayParam",array(3,$order));
        $res['data']['pt_ordersn'] = $order['pt_ordersn'];
//        $res['data']['pt_ordersn'] = $order['pt_ordersn'];

        echo json_encode($res);exit;
    }
    # APP支付 - 充值app
    public function APPczpay($data)
    {
        //生成订单
        $order['pt_ordersn'] = $data['order_number'];
        $order['user_id'] = $data['user_id'];
        $order['body'] = '便民充值';
        $order['price'] = $data['fee'];
//        $order['price'] = '0.01';
//        $order['level']=trim($_REQUEST['level']);
        $order['typepe'] = "CZPAY";
        $res = R("Func/WeixinPay/APPpayParam",array(3,$order));
        $res['data']['pt_ordersn'] = $order['pt_ordersn'];
//        $res['data']['pt_ordersn'] = $order['pt_ordersn'];
        return $res;
    }
    //查询订单
    public function Queryorder($transaction_id='HFCZ1518915365604')
    {
        $res = R('Func/Wxapi/getConfig');
        $souquanurl = $res->getOrderByID($transaction_id);
        dump($souquanurl);exit;
        exit;

    }
    # return - 回调
    public function payReturn()
    {
        error_reporting(0);
        define('IN_MOBILE', true);
        $input = file_get_contents('php://input');
        libxml_disable_entity_loader(true);
        if (!empty($input) && empty($_GET['out_trade_no']))
        {
            $obj = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
            $data = json_decode(json_encode($obj), true);
            if (empty($data))
            {
                exit('fail');
            }
            if (($data['result_code'] != 'SUCCESS') || ($data['return_code'] != 'SUCCESS'))
            {
                $result = array('return_code' => 'FAIL', 'return_msg' => (empty($data['return_msg']) ? $data['err_code_des'] : $data['return_msg']));
                echo ToXml($result);
                exit();
            }
            $get = $data;
        }
        else
        {
            $get = $_GET;
        }
        $strs = explode(':', $get['attach']);
        $type = trim($strs[1]);
        $is_kfp = trim($strs[2]); # 是否开发票

        $tid = $get['out_trade_no'];  // 订单号
        $serial_number = $get['transaction_id']; // 交易流水号
        ksort($get);
        $string1 = '';
        foreach ($get as $k => $v)
        {
            if (($v != '') && ($k != 'sign'))
            {
                $string1 .= $k . '=' . $v . '&';
            }
        }
        $config = M('wxconfig')->where(array('weid'=>3))->find();

        $sign = strtoupper(md5($string1 . 'key=' . trim($config['paysignkey'])));

        if ($sign == $get['sign'])  # 验签成功则...
        {
            # 查询订单正确性，金额
            M("jfpay_log")->where(array('pt_ordersn'=>$tid))->save(array('is_pay'=>1,'serial_number'=>$serial_number));
            #推客缴费
            if($type == "TKPAY")
            {
                $user_id = M("jfpay_log")->where(array('pt_ordersn'=>$tid))->getField('user_id');
                $price = M("jfpay_log")->where(array('pt_ordersn'=>$tid))->getField('price');
                $level = M("jfpay_log")->where(array('pt_ordersn'=>$tid))->getField('level');

                R("Payapi/Api/PaySetLog",array("./PayLog","App_Shenji",'---- 升级参数开始 ----'.'用户ID:'.$user_id.'--金额:'.$price.'--要升级的等级:'.$level.'---时间:'.date('Y-m-d H:i:s').'-------------结束'));
                if($user_id)
                {
                    $_REQUEST['uid'] = $user_id;
                    $where['user_id'] = $user_id;
//                    $where['beizhu']='推客缴费';
                    $one=M('agent')->where($where)->find();
                    if ($one){
                        M('agent')->where($where)->data(array('beizhu'=>'推客缴费','status'=>1,'c_t'=>time()))->save();
                    }else{
                        $userInfo=M('myrealname')->field('nickname,idcard')->where(array('user_id'=>$user_id))->find();
                        $qq['user_id']=$user_id;
                        $qq['username']=$userInfo['nickname'];
//                        $qq['beizhu']='推客升级';
                        $qq['type']=1;
//                        $qq['idcard']=$userInfo['idcard'];
                        $qq['idcard']=$userInfo['idcard'];
                        $qq['status']=1;
                        $qq['beizhu']='推客缴费';
                        $qq['t']=time();
                        M('agent')->add($qq);
                    }
                    $two = M('user')->where(array('user_id'=>$user_id))->find();
                    if($two){
                        M('user')->where(array('user_id'=>$_REQUEST['uid']))->data(array('isopen'=>1,'level'=>$level,'utype'=>'20'))->save();
                    }
                    $dd= M('user_operation')->where(array('user_id'=>$_REQUEST['uid']))->find();
//                    if ($dd){
//                        $ee['op_name']='自动';
//                        $ee['op_level']='钱兜兜互联网综合服务平台';
//                        $ee['time']=time();
//                        $ee['is_pay']=1;
//                        $ee['sj_money']=$price;
//                        $ee['yj_money']=$price;
//                        $ee['order_sn']=$tid;
//                        $ee['tk_level']=$level;
//                        $ee['is_kfp'] = $is_kfp;
//

//                        M('user_operation')->where(array('user_id'=>$_REQUEST['uid']))->data($ee)->save();
//                        exit();
//                    }else{
                        $tt['op_status']=6;
                        $tt['user_id']=$_REQUEST['uid'];
                        $tt['mark']='推客审核';
                        $tt['time']=time();
                        $tt['send_mail']=1;
                        $tt['send_phone']=1;
                        $tt['op_name']='自动';
                        $tt['op_level']='钱兜兜互联网综合服务平台';
                        $tt['order_sn']=$tid;
                        $tt['sq_type']=1;
                        $tt['sj_money']=$price;
                        $tt['yj_money']=$price;
                        $tt['is_pay']=1;
    //                    $tt['tk_level']=1;
                        $tt['tk_level']=$level;
                        $tt['is_kfp'] = $is_kfp;

                        # 是否已开发票
                        if($is_kfp == 1)
                        {
                            $ifxieYi = M("xieyi_registerinfo")->where(array('user_id'=>$user_id,'xieyi_id'=>5))->getField('xieyi_id');
                            if($ifxieYi)
                            {
                                $xieyiData['t'] = time();
                                $xieyiData['is_download'] = 2;
                                $xieyiData['level'] = $level;
                                M("xieyi_registerinfo")->where(array('user_id'=>$user_id))->save($xieyiData);
                            }else{
                                $xieyiData['user_id'] = $user_id;
                                $xieyiData['is_download'] = 2;
                                $xieyiData['xieyi_id'] = 5;  # 发票
                                $xieyiData['t'] = time();
                                $xieyiData['level'] = $level;
                                M("xieyi_registerinfo")->add($xieyiData);
                            }
                        }

//                        if($level == 9)  # 品牌服务费分销
//                        {
                            R("Func/Fenxiao/fenxiaoPartnerJgOrder",array($tid));
//                        }

                        M('user_operation')->add($tt);

                        $result = array('return_code' => 'SUCCESS', 'return_msg' => 'OK');
                        echo ToXml($result);
                        exit();
//                    }
                }
            }

            # 充值支付
            else if($type == "CZPAY")
            {
                // code...
                //修改订单状态为已支付

                $returnfee=$data['total_fee']/100;

                $info=M("chongzhi_order")->where(array('order_number'=>$tid))->find();//查询信息
                if($info['fee']==$returnfee){//支付价格正确 修改状态
                    //编辑状态
                    $data['pay_time']=time();//支付时间

                    $data['pay_status']=1;//付款状态

                    $data['isfukuan'] = '已付款';
                    if($info['activ']==1){
                        $where['last_order_number']=$info['order_number'];
                        M("chongzhi_order")->where($where)->save($data);//修改活动话费第二个订单的状态
                    }
                    M("chongzhi_order")->where(array('order_number'=>$tid))->save($data);//修改状态
                    if($info['activ']==2){
                        R("Payapi/Chongzhi/wxchongzhi",array($tid));
                    }else{
                        R("Payapi/Chongzhi/activpay",array($info['chongzhi_order_id']));
                    }

                }

                $result = array('return_code' => 'SUCCESS', 'return_msg' => 'OK');
                echo ToXml($result);
                exit();
            }
            # 充值支付
            else if($type == "WXPAY")
            {
                // code...
                //修改订单状态为已支付
                $returnfee=$data['total_fee']/100;

                $info=M("chongzhi_order")->where(array('order_number'=>$tid))->find();//查询信息
//                file_put_contents('./Application/Payapi/123456.txt',$info['fee'].'+++'.$returnfee);
                if($info['fee']==$returnfee){
                    //编辑状态
                    $data['pay_time']=time();//支付时间

                    $data['pay_status']=1;//付款状态

                    $data['isfukuan'] = '已付款';
                    if($info['activ']==1){

                        $where['last_order_number']=$info['order_number'];
                        M("chongzhi_order")->where($where)->save($data);//修改活动话费第二个订单的状态
                    }
                    M("chongzhi_order")->where(array('order_number'=>$tid))->save($data);//修改状态
                    if($info['activ']==2){
                        R("Payapi/Chongzhi/wxchongzhi",array($tid));
                    }else{
                        R("Payapi/Chongzhi/activpay",array($info['chongzhi_order_id']));
                    }
                }

//
                $result = array('return_code' => 'SUCCESS', 'return_msg' => 'OK');
                echo ToXml($result);
                exit();
            }
        }
        exit();
    }

    # 调试测试查询
    public function tsOrderCheck($order_sn)
    {
        $order_sn = trim($_REQUEST['order_sn']);
        if (!$order_sn){
            echo json_encode(array('code'=>6333,'msg'=>'订单错误'));die;
        }
        $data =R('Func/WeixinPay/selectOrder',array(3,$order_sn));
        dump($data);
    }

    # 查询订单接口
    public function selectOrder(){
        $parem=array(

            'signType'=>$_REQUEST['signType'],

            'timestamp' => $_REQUEST['timestamp'],

            'dataType' => $_REQUEST['dataType'],

            'inputCharset' => $_REQUEST['inputCharset'],

            'version' => $_REQUEST['version'],

        );
//        $sign = $_REQUEST['sign'];
//        if (!$sign){
//
//            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;
//
//        }
//
//        $array=array(
//
//            'order_sn'=>$_REQUEST['order_sn'],
//
//        );
//
//        $parem = array_merge($parem,$array);

//        $msg = R('Func/Func/getKey',array($parem));//返回加密
//
//        if ($sign !== $msg){
//
//            echo json_encode(array('code'=>10004,'msg'=>'网络异常,请重新登录'));die;
//
//        }
//
//        R('Func/Func/getTwoSign',array($sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
//
//        $_SESSION['last_sign'] = $sign;//把sign存入session 为作判断

        //逻辑
        $order_sn = trim($_REQUEST['order_sn']);
        if (!$order_sn){
            echo json_encode(array('code'=>6333,'msg'=>'订单错误'));die;
        }
        $data =R('Func/WeixinPay/selectOrder',array(3,$order_sn));
       if($data['return_code'] == 'SUCCESS'){
           if ($data['trade_state'] == 'SUCCESS'){
               echo json_encode(array('code'=>6335,'msg'=>$data['trade_state_desc'],'order_sn'=>$data['out_trade_no']));die;
           }elseif($data['trade_state'] == 'REFUND'){
               echo json_encode(array('code'=>6336,'msg'=>'退款','order_sn'=>$data['out_trade_no']));die;
           }elseif($data['trade_state'] == 'NOTPAY'){
               echo json_encode(array('code'=>6337,'msg'=>'订单未支付','order_sn'=>$data['out_trade_no']));die;
           }elseif($data['trade_state'] == 'CLOSED'){
               echo json_encode(array('code'=>6338,'msg'=>'订单已关闭','order_sn'=>$data['out_trade_no']));die;
           }elseif($data['trade_state'] == 'REVOKED'){
               echo json_encode(array('code'=>6339,'msg'=>'已撤销（刷卡支付）','order_sn'=>$data['out_trade_no']));die;
           }elseif($data['trade_state'] == 'USERPAYING'){
               echo json_encode(array('code'=>6340,'msg'=>'用户支付中','order_sn'=>$data['out_trade_no']));die;
           }elseif($data['trade_state'] == 'PAYERROR'){
               echo json_encode(array('code'=>6341,'msg'=>'支付失败(其他原因，如银行返回失败)','order_sn'=>$data['out_trade_no']));die;
           }elseif($data['result_code'] == 'FAIL'){
               echo json_encode(array('code'=>6334,'msg'=>'该订单不存在或未生成'));die;
           }
        }else{
//           if ( $data['result_code'] == 'FAIL'){
               echo json_encode(array('code'=>6334,'msg'=>'该订单不存在或未生成'));die;
//           }
       }
    }

    #
}

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