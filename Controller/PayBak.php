<?php
# 银钱包对接接口 - 所有支付/收银台/付款码APP接口
namespace Payapi\Controller;
use Think\Controller;
class PayController extends BaseController{
    public $uid;
    public $parem;
    public $sign;
    //修改资料
    public function _initialize(){
        parent::_initialize();
        $this->uid = intval($_REQUEST['uid']);
        if(empty($this->uid))
        {
            $d = array(
                'code' => 200,
                'msg' => '用户ID不存在'
            );
            echo json_encode($d);exit;
        }
        $this->parem=array(
            'signType'=>$_REQUEST['signType'],
            'timestamp' => $_REQUEST['timestamp'],
            'dataType' => $_REQUEST['dataType'],
            'inputCharset' => $_REQUEST['inputCharset'],
            'version' => $_REQUEST['version'],
        );
        $this->sign = $_REQUEST['sign'];
    }
    # 收银台收款(选择收款通道)
    public function checkSkTd()
    {
        $td = intval($_REQUEST['td']); // 默认为1
        $price = number_format($_REQUEST['price'],2);
        if (!$this->sign){
            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;
        }
        $array=array(
            'uid'=>$this->uid,
            'td'=>$td,
            'price'=>$_REQUEST['price']
        );
        $this->parem = array_merge($this->parem,$array);
        $msg = R('Func/Func/getKey',array($this->parem));//返回加密
//        dump($msg);die;
        if ($this->sign !== $msg){
            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;
        }
        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        if($td != 1)  # 先开发快捷支付,后续再增加其他支付（增加了，记住要把它注释掉）
        {
            $d = array(
                'code' => 7000,
                'msg' => '请先调用快捷支付'
            );
            echo json_encode($d);exit;
        }

        # 新增话费/流量充值
        $payType = 2;  # 1余额支付2快捷支付
        $phone = trim($_REQUEST['phone']);
        $goodsid = trim($_REQUEST['goodsid']);

        # 交易类型名称
        $tdtype = R("Func/Func/getPayType",array($td));
        $tylist = R("Func/Func/getSkTd",array($td));
        $d = array(
            'code' => 7001,
            'msg' => '选择收款通道',
            'data' => array(
                'tdtype' => $tdtype,
                'tylist' => $tylist,
                'price' => $price,
                'goodsid' => $goodsid,
                'phone' => $phone,
            )
        );
        echo json_encode($d);exit;
    }

    # 收银台收款(选择收款通道)下一步
    public function checkSkTdNext()
    {
        $td = intval($_REQUEST['td']); // 默认为1
        $tytd = intval($_REQUEST['tytd']); // 收款通道
        $price = number_format($_REQUEST['price'],2);
        $myrea = R("Func/Func/getMyInfo",array('uid'=>$this->uid));
        $bankId = intval($_REQUEST['bankid']);

        if (!$this->sign){
            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;
        }
        $array=array(
            'uid'=>$this->uid,
            'td'=>$_REQUEST['td'],
            'price'=>$_REQUEST['price'],
            'tytd'=>$_REQUEST['tytd'],
            'bankid'=>$_REQUEST['bankid']
        );
        $this->parem = array_merge($this->parem,$array);
        $msg = R('Func/Func/getKey',array($this->parem));//返回加密
//        dump($msg);die;
        if ($this->sign !== $msg){
            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;
        }
        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        if($bankId)
        {
            $mybank = M("mybank")->where(array('id'=>$bankId))->find();
            $mybank['bankinfo'] = M("bank")->where(array('status'=>1,'id'=>$mybank['bankid']))->find();
        }else{
            $mybank = R("Func/Func/getMyBank",array('uid'=>$this->uid,2,1));
        }

        if(!$td)
        {
            $d = array(
                'code' => 200,
                'msg' => '请选择支付类型'
            );
            echo json_encode($d);exit;
        }
        if(!$tytd)
        {
            $d = array(
                'code' => 200,
                'msg' => '请选择收款通道'
            );
            echo json_encode($d);exit;
        }
        if(!$price)
        {
            $d = array(
                'code' => 200,
                'msg' => '金额不存在'
            );
            echo json_encode($d);exit;
        }
        if(!$mybank)
        {
            $d = array(
                'code' => 200,
                'msg' => '请先添加银行卡'
            );
            echo json_encode($d);exit;
        }
        $d = array(
            'code' => 7002,
            'msg' => '(选择收款通道)下一步',
            'data' => array(
                'mybank' => $mybank,
                'myrea' => $myrea,
                'price' => $price
            )
        );
        echo json_encode($d);exit;
    }

    # 确认信息(返回首次下单支付参数)
    public function subPay()
    {
        $param = C("KJPAY");  // 调用function.php文件中的配置参数  # 认证参数
        $td = intval($_REQUEST['td']);
        $tytd = intval($_REQUEST['tytd']); // 收款通道
        $bankid = intval($_REQUEST['bankid']);
        $price = number_format($_REQUEST['price'],2);
        $phone = trim($_REQUEST['phone']);
        $goodsid = trim($_REQUEST['goodsid']);
        if (!$this->sign){
            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;
        }
        $array=array(
            'uid'=>$this->uid,
            'td'=>$_REQUEST['td'],
            'price'=>$_REQUEST['price'],
            'tytd'=>$_REQUEST['tytd'],
            'bankid'=>$_REQUEST['bankid'],
            'phone'=>$_REQUEST['phone'],
            'goodsid'=>$_REQUEST['goodsid'],
        );
        $this->parem = array_merge($this->parem,$array);
        $msg = R('Func/Func/getKey',array($this->parem));//返回加密
//        dump($msg);die;
        if ($this->sign !== $msg){
            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;
        }
        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        # 新增话费/流量充值

        $payType = 2;  # 1余额支付2快捷支付


        $reorder = R("Func/Func/downOrder",array($goodsid,$phone,$payType,$this->uid));
        if($reorder['errCode'] == 0)
        {
            $czorder = $reorder['data']['order_id'];  # 充值订单号码
        }

        R("Payapi/Api/PaySetLog",array("./PayLog","cztest_",'----充值快捷支付返回信息参数----'.json_encode($reorder)));

        if(!$td)
        {
            $d = array(
                'code' => 200,
                'msg' => '请选择支付类型'
            );
            echo json_encode($d);exit;
        }
        if(!$tytd)
        {
            $d = array(
                'code' => 200,
                'msg' => '请选择收款通道'
            );
            echo json_encode($d);exit;
        }
        if(!$price)
        {
            $d = array(
                'code' => 200,
                'msg' => '金额不存在'
            );
            echo json_encode($d);exit;
        }
        $myBank = M("mybank")->where(array('uid'=>$this->uid,'status'=>1,'type'=>2,'id'=>$bankid))->find();
//        dump($myBank);die;
        if(!$myBank)
        {
            $d = array(
                'code' => 200,
                'msg' => '!请先验证'
            );
            echo json_encode($d);exit;
        }
        # 银行卡卡信息
        $myBankInfo = array(
            'P13_phone' => $myBank['mobile'],
            'P6_payerName' => $myBank['nickname'],
            'P7_idCardType' => 'IDCARD', // 证件类型 - 身份证
            'P8_idCardNo' => $myBank['idcard'],
            'P9_cardNo' => $myBank['cart'],
            'P10_year' => substr($myBank['useful'],2),
            'P11_month' => substr($myBank['useful'],0,2),
            'P12_cvv2' => $myBank['cw_two']
        );

        $price = 1;# 固定金额（转正之后，请注释，至少大于0.1元）
        $order = array(
            'P15_orderAmount' => $price,
            'P16_goodsName' => '信用卡快捷支付',
            'P17_goodsDesc' => '信用卡快捷支付-66',
            'czorder' => $czorder
        );

        $res = R("Payapi/Api/ajPay",array($param,$myBankInfo,$order));

        $signkey = trim($param['signkey_quickpay']);
        //发送验证码的签名串
        $orinMessage = "&QuickPaySendValidateCode&$res[3]&$res[1]&$res[2]&$myBank[mobile]&$signkey";
        $sign = md5($orinMessage);

        if($res[0] == 1)
        {
            $d = array(
                'code' => 7005,
                'msg' => '快捷支付参数',
                'data' => array(
                    'base' => array(
                        'P4_orderId' => $res[1],
                        'P5_timestamp' => $res[2],
                        'P2_customerNumber' => $res[3],
                        'P5_phone' => $myBank['mobile'],
                        'sign' => $sign,
                    ),
                    'bankid' => $bankid
                )
            );
            echo json_encode($d);exit;
        }else{
            $d = array(
                'code' => 200,
                'msg' => $res[1]
            );
            echo json_encode($d);exit;
        }

    }

    # 发送短信(确认/鉴权信用卡) ----
    public function xxxsend()
    {
        # 发送短信(确认/鉴权信用卡) ---- ---  已不用自己编写
    }

    # 确认支付
    public function subCardPay()
    {
        $param = C("KJPAY");  // 调用function.php文件中的配置参数  # 认证参数
        $bankid = intval($_REQUEST['bankid']);
        $td = intval($_REQUEST['td']);
        $tytd = intval($_REQUEST['tytd']); // 收款通道
        if (!$this->sign){
            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;
        }
        $array=array(
            'uid'=>$this->uid,
            'bankid'=>$_REQUEST['bankid'],
            'td'=>$_REQUEST['td'],
            'tytd'=>$_REQUEST['tytd'],
        );

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
//
        if(!$td)
        {
            $d = array(
                'code' => 200,
                'msg' => '请选择支付类型'
            );
            echo json_encode($d);exit;
        }
        if(!$tytd)
        {
            $d = array(
                'code' => 200,
                'msg' => '请选择收款通道'
            );
            echo json_encode($d);exit;
        }
        $myBank = M("mybank")->where(array('uid'=>$this->uid,'status'=>1,'type'=>2,'id'=>$bankid))->find();
        if(!$myBank)
        {
            $d = array(
                'code' => 200,
                'msg' => '!请先验证信用卡'
            );
            echo json_encode($d);exit;
        }
        if($_REQUEST)
        {
            # 银行卡卡信息
            $myBankInfo = array(
                'P13_phone' => $myBank['mobile']
            );
            $order = array(
                'P3_orderId' => trim($_REQUEST['P3_orderId']),
                'P4_timestamp' => trim($_REQUEST['P4_timestamp'])
            );
            $this->parem = array_merge($this->parem,$order);
            $pdata['P5_validateCode'] = trim($_REQUEST['P5_validateCode']);
            if(!$pdata['P5_validateCode'])
            {
                $d = array(
                    'code' => 200,
                    'msg' => '验证码错误，请重新返回支付'
                );
                echo json_encode($d);exit;
            }
            $this->parem = array_merge($this->parem,$pdata);
            $msg = R('Func/Func/getKey',array($this->parem));//返回加密
//        dump($msg);die;
            if ($this->sign !== $msg){
                echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;
            }
            $res = R("Payapi/Api/ajPaySend",array($param,$myBankInfo,$order,$pdata));


//            $d = array(
//                'code' => 200,
//                'msg' => $res
//            );
//            echo json_encode($d);exit;
            if($res[1])
            {
                $res = json_decode($res[1],true);
                if($res['rt2_retCode'] == 0000){
                    $d = array(
                        'code' => 7100,
                        'msg' => '支付成功'
                    );
                    echo json_encode($d);exit;
                }else{
                    $d = array(
                        'code' => 200,
                        'msg' => $res['rt3_retMsg']
                    );
                    echo json_encode($d);exit;
                }
            }else{
                $d = array(
                    'code' => 200,
                    'msg' => '参数错误，请重新支付'
                );
                echo json_encode($d);exit;
            }
        }
    }

}