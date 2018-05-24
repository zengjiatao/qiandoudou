<?php

# 银钱包对接接口 - 所有支付/收银台/付款码APP接口
# Jeffery

namespace Payapi\Controller;

class PayController extends BaseController
{

    public $uid;

    public $parem;

    public $sign;
    public $table;
    public $jstable;
    //修改资料

    public function _initialize()
    {

        parent::_initialize();

        $this->uid = $_REQUEST['uid'];

        if (empty($this->uid)) {

            $d = array(
                'code' => 200,
                'msg' => '用户ID不存在'
            );
            echo json_encode($d);
            exit;

        }
        $open = 1;
        if($open==1) # 正式数据表
        {
            $table = "money_detailed";
            $jstable = "moneyjs_detailed";
        }else{ # 测试数据表
            $table = "money_zf_detailed";
            $jstable = "money_zfjs_detailed";
        }


        $this->table = $table;
        $this->jstable = $jstable;
        $this->parem = array(

            'signType' => $_REQUEST['signType'],

            'timestamp' => $_REQUEST['timestamp'],

            'dataType' => $_REQUEST['dataType'],

            'inputCharset' => $_REQUEST['inputCharset'],

            'version' => $_REQUEST['version'],

        );

        $this->sign = $_REQUEST['sign'];

    }


    /***
     * 新增收款类型
     */
    public function getSkType()
    {
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid'=>$this->uid
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
        $type = M("pay_supply_type")->order('sn asc')->select();
        $myrea = R("Func/Func/getMyInfo", array('uid' => $this->uid));
        $d = array(
            'code' => 200,
            'msg' => '收款类型',
            'data' => $type,
            'myrea' => $myrea
        );
        echo json_encode($d);
        exit;
    }

    # 收款类型保存
    public function addsktype()
    {
        $sktype = trim($_REQUEST['pay_supply_type_id']);
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'pay_supply_type_id' => $sktype
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $ifsk = M("sktype")->where(array('user_id'=>$this->uid))->find();
        if($ifsk)
        {
            M("sktype")->where(array('user_id'=>$this->uid))->save(array('pay_supply_type_id'=>$sktype));
        }else{
            M("sktype")->add(array('pay_supply_type_id'=>$sktype,'user_id'=>$this->uid));
        }

        $d = array(
            'code' => 200,
            'msg' => '添加成功'
        );
        echo json_encode($d);
        exit;
    }



    # 收银台收款(选择收款通道)

    public function checkSkTd()

    {
        $td = intval($_REQUEST['td']); // 默认为1

        $price = $_REQUEST['price'];
        /*

        if (!$this->sign) {

            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;

        }

        $array = array(

            'uid' => $this->uid,

            'td' => $td,

            'price' => $_REQUEST['price']

        );

        $this->parem = array_merge($this->parem, $array);

        $msg = R('Func/Func/getKey', array($this->parem));//返回加密

//        dump($msg);die;

        if ($this->sign !== $msg) {

            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;

        }

        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
*/

        if ($td != 1)  # 先开发快捷支付,后续再增加其他支付（增加了，记住要把它注释掉）
        {
            $d = array(
                'code' => 7000,
                'msg' => '请先调用快捷支付'
            );
            echo json_encode($d);
            exit;
        }

        # 新增话费/流量充值
        $payType = 2;  # 1余额支付2快捷支付
        $phone = trim($_REQUEST['phone']);
        $goodsid = trim($_REQUEST['goodsid']);
        # 交易类型名称
        $tdtype = R("Func/Func/getPayType", array($td)); //快捷支付
        $tylist = R("Func/Func/getSkTd", array($td));

        $this->tdClose();

        $newtylist = array();
        foreach ($tylist as $k => $v)
        {
            # 支持的银行
            $bank_id = M("pay_supply")->where(array('pay_supply_id'=>$v['pay_supply_id']))->getField('bank_id');
            $smsg = M("pay_supply")->where(array('pay_supply_id'=>$v['pay_supply_id']))->getField('smsg');
            $bankList = M('bank')->where(array('bank_id'=>array('in',$bank_id)))->select();

            $newBank = array();
            foreach ($bankList as $k1 => $v1)
            {
                $newBank[$k1]['name'] = $v1['name'];
            }
            $v['bankList'] = $newBank;

            # 特别说明 smsgxxx
            $v['smsg'] = trim($smsg);

            unset($v['mch_id']);
            unset($v['mch_secretkey']);
            unset($v['mch_session']);
            unset($v['sub_address']);
            unset($v['return_address']);
            unset($v['web_address']);
            unset($v['web_address']);

            $v['typename'] = trim($v['englistname']);
            $v['user_js_supply_id'] = trim($v['user_js_supply_id']);
            $newtylist[] = $v;
        }
        $tylist = $newtylist;

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

        echo json_encode($d);
        exit;

    }


    # 收银台收款(选择收款通道)下一步
    public function checkSkTdNext()

    {

        $td = intval($_REQUEST['td']); // 默认为1

        $sktype = intval($_REQUEST['sktype']); // 收款类型

        $tytd = intval($_REQUEST['tytd']); // 收款通道

        $price = $_REQUEST['price'];

        $myrea = R("Func/Func/getMyInfo", array('uid' => $this->uid));

        $bankId = intval($_REQUEST['bankid']);

/**/
        if (!$this->sign) {

            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;

        }

        $array = array(

            'uid' => $this->uid,

            'td' => $_REQUEST['td'],

            'price' => $_REQUEST['price'],

            'tytd' => $_REQUEST['tytd'],

            'bankid' => $_REQUEST['bankid']

        );

        $this->parem = array_merge($this->parem, $array);

        $msg = R('Func/Func/getKey', array($this->parem));//返回加密

//        dump($msg);die;

        if ($this->sign !== $msg) {

            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }

        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断


        if ($bankId) {

            $mybank = M("mybank")->where(array('mybank_id' => $bankId))->find();

            $mybank['bankinfo'] = M("bank")->where(array('status' => 1, 'bank_id' => $mybank['bank_id']))->find();

        } else {

            $mybank = R("Func/Func/getMyBank", array('uid' => $this->uid, 2, 1,intval($_REQUEST['tytd'])));

        }


        if (!$td) {

            $d = array(

                'code' => 200,

                'msg' => '请选择支付类型'

            );

            echo json_encode($d);
            exit;

        }

        if (!$tytd) {

            $d = array(

                'code' => 200,

                'msg' => '请选择收款通道'

            );

            echo json_encode($d);
            exit;

        }

        if (!$price) {

            $d = array(

                'code' => 200,

                'msg' => '金额不存在'

            );

            echo json_encode($d);
            exit;

        }



        $jy_time=M('user_pay_supply')->where(array('user_pay_supply_id'=>$tytd))->getField('jy_time');
        $time = explode('-', $jy_time);
        $start_time=strtotime(date('Y-m-d '.$time[0].':i'));
        $curr_time=time();
        $end_time=strtotime(date('Y-m-d '.$time[1].':i'));
//        dump($end_time);die;

        if($curr_time >= $end_time || $curr_time <= $start_time){
            echo json_encode(array('code'=>4209,'msg'=>'请在时间段内交易!'));die;
        }

        # 新增结算通道信息 - 入账金额
        $pay_supply_id = M('user_pay_supply')->where(array('user_pay_supply_id'=>$tytd))->getField('pay_supply_id');
        $payrate = M('user_pay_supply')->where(array('user_pay_supply_id'=>$tytd))->getField('yy_rate');
        $rzprice = 0;

        if($pay_supply_id)
        {
            $yyrate = M("user_js_supply")->where(array('sid'=>$pay_supply_id))->getField('yy_rate'); # 提现费
            $rzprice = trim($_REQUEST['price']) - (trim($_REQUEST['price'])*$payrate/1000) - $yyrate;
        }else{
            $d = array(
                'code' => 200,
                'msg' => '参数不全!'
            );
            echo json_encode($d);
            exit;
        }



//        if (!$mybank) {
//
//            $d = array(
//
//                'code' => 200,
//
//                'msg' => '请先添加银行卡'
//
//            );
//
//            echo json_encode($d);
//            exit;
//        }
        if(!$mybank)
        {
            $mybank = array();
        }

//dump(trim($_REQUEST['price']));
//        dump($payrate);
//        echo $yyrate;
        $d = array(

            'code' => 7002,

            'msg' => '(选择收款通道)下一步',

            'data' => array(

                'mybank' => $mybank,

                'myrea' => $myrea,

                'price' => $price,

                'tx_service' => (trim($_REQUEST['price'])*$payrate/1000) + $yyrate, // 手续费

                # 到账金额
                'rzprice' => sprintf("%.2f",$rzprice)

            )

        );

        echo json_encode($d);
        exit;

    }


    # 判断是否首次/第N次下单支付
    public function ifIndexPay()
    {

        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'card' => trim($_REQUEST['card'])
        );

        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }

        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        # 判断是否首单支付过 ，切换第N次绑卡支付
        $isindexpay = M("mybank_bind")->where(array('user_id'=>$this->uid,'card' => trim($_REQUEST['card']),'type'=>1))->getField('mybank_bind_id');
        if(!$isindexpay)
        {
            echo json_encode(array('code' => 1000000, 'msg' => '首次下单'));
            die;
        }else{
            echo json_encode(array('code' => 1000001, 'msg' => '第N次已经绑卡下单'));
            die;
        }
    }

    ###################################### (返回首次下单支付参数)
    # 确认信息(返回首次下单支付参数)
    public function subPay()
    {
        $param = C("KJPAY");  // 调用function.php文件中的配置参数  # 认证参数

        $td = intval($_REQUEST['td']);

        $tytd = intval($_REQUEST['tytd']); // 收款通道

        $bankid = intval($_REQUEST['bankid']);

        $price = $_REQUEST['price'];

        $phone = trim($_REQUEST['phone']);

        $goodsid = trim($_REQUEST['goodsid']);

        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }

        $array = array(

            'uid' => $this->uid,

            'td' => $_REQUEST['td'],

            'price' => $_REQUEST['price'],

            'tytd' => $_REQUEST['tytd'],

            'bankid' => $_REQUEST['bankid'],

            'phone' => $_REQUEST['phone'],

            'goodsid' => $_REQUEST['goodsid'],

        );

        $this->parem = array_merge($this->parem, $array);

        $msg = R('Func/Func/getKey', array($this->parem));//返回加密

//        dump($msg);die;

        if ($this->sign !== $msg) {

            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;

        }

        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断


        # 新增话费/流量充值

        $payType = 2;  # 1余额支付2快捷支付

        $reorder = R("Func/Func/downOrder", array($goodsid, $phone, $payType, $this->uid));

        if ($reorder['errCode'] == 0) {

            $czorder = $reorder['data']['order_id'];  # 充值订单号码

        }


        R("Payapi/Api/PaySetLog", array("./PayLog", "cztest_", '----充值快捷支付返回信息参数----' . json_encode($reorder)));


        if (!$td) {

            $d = array(

                'code' => 200,

                'msg' => '请选择支付类型'

            );

            echo json_encode($d);
            exit;

        }

        if (!$tytd) {

            $d = array(

                'code' => 200,

                'msg' => '请选择收款通道'

            );

            echo json_encode($d);
            exit;

        }

        if (!$price) {

            $d = array(

                'code' => 200,

                'msg' => '金额不存在'

            );

            echo json_encode($d);
            exit;

        }

        $myBank = M("mybank")->where(array('user_id' => $this->uid, 'status' => 1, 'type' => 2, 'mybank_id' => $bankid))->find();

//        dump($myBank);die;

        if (!$myBank) {

            $d = array(

                'code' => 200,

                'msg' => '!请先验证'

            );

            echo json_encode($d);
            exit;

        }

        # 银行卡卡信息
        R("Payapi/Api/PaySetLog", array("./PayLog", "banktest_", '---- 我的银行卡信息 ----' . json_encode($myBank)));

        # phone 获取用户的用户名(手机号码)
//        $phone = M("user")->where(array('user_id'=>$this->uid))->getField('phone');
        $myBankInfo = array(

            'P13_phone' => $myBank['mobile'],

            'P6_payerName' => $myBank['nickname'],

            'P7_idCardType' => 'IDCARD', // 证件类型 - 身份证

            'P8_idCardNo' => $myBank['idcard'],

            'P9_cardNo' => $myBank['cart'],

            'P10_year' => substr($myBank['useful'], 2),

            'P11_month' => substr($myBank['useful'], 0, 2),

            'P12_cvv2' => $myBank['cw_two']

        );


//        $price = 1;# 固定金额（转正之后，请注释，至少大于0.1元）

        $userId = M('user')->where(array('user_id' => $this->uid))->getField('phone');
//        $userId = $myBank['cart'];// 转换卡号


        $is_jifen = M('user_pay_supply')->where(array('user_pay_supply_id'=>intval($tytd)))->getField('is_jifen'); # 是否有积分

        $jifen_type = M('user_pay_supply')->where(array('user_pay_supply_id'=>intval($tytd)))->getField('jifen_type');  # 积分类型



        $order = array(
            'userId' => $userId, # 使用用户手机号码
            'user_id' => $this->uid,
            'pay_type' => $tytd,
            'pay_name' => $td,
            'P15_orderAmount' => $price,
            'P16_goodsName' => 'O2O收款',
            'P17_goodsDesc' => 'O2O收款',
            'czorder' => $czorder,
            'is_jifen' => $is_jifen,
            'jifen_type' => $jifen_type
        );


        $res = R("Payapi/Api/ajPay", array($param, $myBankInfo, $order));

        $signkey = trim($param['signkey_quickpay']);
        //发送验证码的签名串
        $orinMessage = "&QuickPaySendValidateCode&$res[3]&$res[1]&$res[2]&$myBank[mobile]&$signkey";
        $sign = md5($orinMessage);
        if ($res[0] == 1) {

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

                    'bank_id' => $bankid

                )

            );

            echo json_encode($d);
            exit;

        } else {

            $d = array(

                'code' => 200,

                'msg' => $res[1]

            );

            echo json_encode($d);
            exit;

        }


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

        $myBank = M("mybank")->where(array('user_id'=>$this->uid,'status'=>1,'type'=>2,'mybank_id'=>$bankid))->find();

        R("Payapi/Api/PaySetLog", array("./PayLog", "mybindmybank__", '---- 我的银行卡信息 ----' . json_encode($myBank)));

//        if(!$myBank)

//        {

//            $d = array(

//                'code' => 200,

//                'msg' => '!请先验证信用卡'

//            );

//            echo json_encode($d);exit;

//        }

        if($_REQUEST)

        {

            # 银行卡卡信息

            # 获取用户的用户名(手机号码)
//            $p13_phone = M();
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

            $this->parem = array_merge($this->parem,$array);

//            dump($this->parem);die;

            $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        dump($msg);die;

            if ($this->sign !== $msg){

                echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

            }

            $pdata['user_id'] = $this->uid;
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


    ###################################### (返回第N次下单支付参数)

    # 确认信息(返回第N次下单支付参数)
    public function subNSSPay()
    {
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'tytd' => trim($_REQUEST['tytd']),
            'bankid' => trim($_REQUEST['bankid']),
            'price' => trim($_REQUEST['price'])
        );

        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }

        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
        if(IS_POST)
        {
            $bankid = intval($_POST['bankid']);
            $myBank = M("mybank")->where(array('user_id'=>$this->uid,'status'=>1,'type'=>2,'mybank_id'=>$bankid))->find();



//            $phone = M("mybank")->where(array('user_id'=>$this->uid))->getField('mobile');

            if(!$myBank)
            {
                echo json_encode(array('code'=>200,'msg'=>'!请先验证'));exit;
            }
            $price = trim($_POST['price']);



            $ordersn="HLBQP".get_timeHm();//订单号

            $timestamp=date('Ymdhis',time());//订单时间戳

            # 跳转链接

            # "/index.php/Wap/Pay/ajPaySend.html?bankid="+d['param']['bankid']+"&P4_orderId="+d['base'][1]+"&P5_timestamp="+d['base'][2]+"&P2_customerNumber="+d['base'][3];

            # 获取支付名称 - 支付通道
            $sid = M('user_pay_supply')->where(array('user_pay_supply_id'=>intval($_POST['tytd'])))->getField('pay_supply_id');
            $pay_name = M("pay_supply")->where(array('pay_supply_id'=>intval($sid)))->getField('name');

            $phone = M("user")->where(array('user_id'=>$this->uid))->getField('phone');// 获取用户的用户名(手机号码)
            # 保存到交易明细表
            $moneyde['user_id'] =  $this->uid;
            $moneyde['goods_name'] =  "O2O收款";
            $moneyde['goods_type'] =  "消费";
            $moneyde['user_pay_supply_id'] =  intval($_POST['tytd']);  # 支付交易通道
            $moneyde['pay_name'] =  $pay_name;
            $moneyde['service_charge'] = ''; # 手续费
            $moneyde['pt_ordersn'] = $ordersn;
            $moneyde['jy_status'] = 2; #2交易中
            $moneyde['pay_money'] = $price;
            $moneyde['money_type_id'] =  11; # y_money_type 表中的o2o收款
            $moneyde['t'] = time();
            $moneyde['bank_cart'] = $myBank['cart']; # 信用卡卡号
            $moneyde['phone'] = $phone;
            $moneyde['timestamp'] = $timestamp;

            $pay_supply_type_id = M("sktype")->where(array('user_id'=>$this->uid))->getField('pay_supply_type_id');
            $moneyde['pay_supply_type_id'] = trim($pay_supply_type_id);
            M("money_detailed")->add($moneyde);
            $param = C("KJPAY");  // 调用function.php文件中的配置参数  # 认证参数
            $base = array(
                'P4_orderId' => $ordersn,
                'P5_timestamp' => $timestamp,
                'P2_customerNumber' => $param['P2_customerNumber'],
                'P5_phone' => $myBank['mobile'],
                'sign' => ''
            );
            $d = array(
                'code' => 7005,
                'msg' => '快捷支付 - 第N次下单支付',
                'data' => array(
                    'base' => $base,
                    "bankid" => $bankid  // 我的绑定之后的信用卡ID
                )

            );
            echo json_encode($d);exit;
        }
    }

    # 确认短信
    public function subNSSPaySend()
    {

        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'tytd' => trim($_REQUEST['tytd']),
            'bankid' => trim($_REQUEST['bankid']), #
            'P3_orderId' => trim($_REQUEST['P3_orderId']),
            'P4_timestamp' => trim($_REQUEST['P4_timestamp'])
        );

        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }

        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $param = C("KJPAY");  // 调用function.php文件中的配置参数  # 认证参数
//        $phone = M('user')->where(array("user_id"=>$this->uid))->getField('phone'); # 已实名认证之后的
        $myBank = M("mybank")->where(array('user_id'=>$this->uid,'status'=>1,'type'=>2,'mybank_id'=>intval($_REQUEST['bankid'])))->find();
//        $this->assign("nickname",$myBank['nickname']);

        # 首次下单绑定支付卡
        $mybindbank = M("mybank_bind")->where(array('user_id'=>$this->uid,'type'=>1))->find();
        if(IS_POST)
        {
            $price = M("money_detailed")->where(array('pt_ordersn'=>trim($_POST['P3_orderId'])))->getField('pay_money');

            $order = array(
                'phone' => trim($myBank['mobile']),   // 绑定银行卡的phone

                'bindId' => trim($mybindbank['bindId']),

                'userId' => trim($mybindbank['userId']),

                'orderId' => trim($_POST['P3_orderId']),

                'timestamp' => trim($_POST['P4_timestamp']),

                'orderAmount' => $price

            );
            # 发送短信
            $res = R("Payapi/Api/ajNPaySend",array($param,$order));
//            dump($res[1]);die;
            $recode = json_decode($res,true);
            echo json_encode(array(
                'code' => $recode['rt2_retCode'],
                'msg' =>  $recode['rt3_retMsg']
            ));exit;
        }
    }

    # 确认支付
    public function subNSSPayCardPay()
    {

        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'tytd' => trim($_REQUEST['tytd']),
            'bankid' => trim($_REQUEST['bankid']), #
            'P3_orderId' => trim($_REQUEST['P3_orderId']),
            'P4_timestamp' => trim($_REQUEST['P4_timestamp']),
            'P5_validateCode' => trim($_REQUEST['P5_validateCode'])
        );

        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }

        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $param = C("KJPAY");  // 调用function.php文件中的配置参数  # 认证参数

        $phone = M('user')->where(array("user_id"=>$this->uid))->getField('phone'); # 已实名认证之后的

        $nickname = M('myrealname')->where(array("user_id"=>$this->uid))->getField('nickname'); # 姓名

        # 首次支付绑定的信用卡信息

        # 首次下单绑定支付卡
        $cart = M('mybank')->where(array('mybank_id'=>trim($_REQUEST['bankid'])))->getField('cart');
        $mybindbank = M("mybank_bind")->where(array('user_id'=>$this->uid,'type'=>1,'card'=>$cart))->find();



        $orderId = trim($_REQUEST['P3_orderId']);//订单号

        $timestamp = trim($_REQUEST['P4_timestamp']);//时间戳

        if(IS_POST)

        {

            $price = M("money_detailed")->where(array('pt_ordersn'=>trim($orderId)))->getField('pay_money');

            $tytd = M("money_detailed")->where(array('pt_ordersn'=>trim($orderId)))->getField('user_pay_supply_id');



            $is_jifen = M('user_pay_supply')->where(array('user_pay_supply_id'=>intval($tytd)))->getField('is_jifen'); # 是否有积分

            $jifen_type = M('user_pay_supply')->where(array('user_pay_supply_id'=>intval($tytd)))->getField('jifen_type');  # 积分类型





            $order = array(

                'is_jifen' => $is_jifen,

                'jifen_type' => $jifen_type,

                'phone' => trim($phone),

                'bindId' => trim($mybindbank['bindId']),

                'userId' => trim($mybindbank['userId']),

                'orderId' => $orderId,

                'timestamp' => $timestamp,

                'orderAmount' => $price,

                'goodsName' => 'O2O收款',

                'goodsDesc' => 'O2O收款',

                'terminalType' => 'IMEI',

                'terminalId' => '122121212121',

                'period' => '',

                'eriodUnit' => '',

                'validateCode' => trim($_POST['P5_validateCode']),

                'aptitudeCode' => ''

            );

            # 请求支付

            $res = R("Payapi/Api/ajNPay",array($param,$order));

            if($res[1])

            {

                $res = json_decode($res[1],true);

                if($res['rt2_retCode'] == "0000"){

                    # 保存订单信息

                    $ldata['ordersn'] = $res['rt5_orderId'];

                    $ldata['user_id'] = $res['rt14_userId'];

                    $ldata['phone'] = $phone;

                    $ldata['payerName'] = $nickname;

                    $ldata['cardNo'] = "";

                    $ldata['timestamp'] = "";

                    $ldata['order_amount'] = $res['rt8_orderAmount'];

                    $ldata['ip'] = get_client_ip(); # 下单IP地址

                    $ldata['t'] = time();

                    M("crepay_log")->add($ldata);

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



    /*
     * 新增重新提交结算(第一次结算失败使用)
     * 方式，关联同一张结算表中的
     * author:Jeffery
     * date:2017-12-4
     * $pt_ordersn = 结算失败的订单
     */
    public function ajElationJs()
    {
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'pt_ordersn' => trim($_REQUEST['pt_ordersn'])
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        # 判断此订单是否失败
        $ifJsStatus = M("moneyjs_detailed")->field('pt_ordersn,rz_money,user_id,js_status,tx_service,js_money,user_js_supply_id,sx_money')->where(array('pt_ordersn'=>trim($_REQUEST['pt_ordersn'])))->find();
        if($ifJsStatus)
        {
            $param = C("KJPAY");  // 调用function.php文件中的配置参数  # 认证参数
            $user_id = $ifJsStatus['user_id'];# 用户ID
            $userId = M('user')->where(array('user_id' => $user_id))->getField('phone');
            if(!empty($userId))
            {
//                if($userId!=$this->uid)
//                {
//                    $d = array(
//                        'code' => 200,
//                        'msg' => '用户ID错误'
//                    );
//                    echo json_encode($d);exit;
//                }

                if($ifJsStatus['js_status'] == 1)
                {
                    $d = array(
                        'code' => 200,
                        'msg' => '此订单还在结算中，不需要重新提交结算！'
                    );
                    echo json_encode($d);exit;
                }
                if($ifJsStatus['js_status'] == 2)
                {
                    $d = array(
                        'code' => 200,
                        'msg' => '此订单结算成功，不需要重新提交结算！'
                    );
                    echo json_encode($d);exit;
                }

                # 取关联的订单信息
//                $amount = $ifJsStatus['js_money'];
                $amount = $ifJsStatus['rz_money']; // 最终结算需要入账的金额

                if($amount <= 0)
                {
                    $d = array(
                        'code' => 200,
                        'msg' => '金额错误，结算失败！'
                    );
                    echo json_encode($d);exit;
                }

                /*
                // 查询该结算订单是否没有结算成功，没有结算成功则重新提交结算
                $res = R("Payapi/OrderReturn/HLBSELECTJSTXORDER",array(trim($_REQUEST['pt_ordersn'])));
                if($res['code'] == 200)
                {
                    // 结算成功（解决未结算成功订单，但实际上是上游已结算成功了）
                    M("money_detailed")->where(array('pt_ordersn'=>trim($_REQUEST['pt_ordersn'])))->save(array('js_status'=>2,'d_t'=>time()));
                    M("moneyjs_detailed")->where(array('pt_ordersn'=>trim($_REQUEST['pt_ordersn'])))->save(array('js_status'=>2,'order_status'=>'0000','order_msg'=>'接收成功'));
                    $d = array(
                        'code' => 9991,
                        'msg' => '结算成功!'
                    );
                    echo json_encode($d);exit;
                }
                */

                # 判断bindId是否已经绑定过结算卡(默认)
                $mybankcard = M("mybank")->where(array('user_id'=>$this->uid,'type'=>1,'jq_status'=>3,'status'=>1,'is_normal'=>1))->find();
                if($mybankcard)
                {
                    $bindId = M("mybank_bind")->where(array('card'=>$mybankcard['cart'],'user_id'=>$this->uid,'type'=>2,'is_normal'=>1))->getField('bindId');
                    if(empty($bindId))
                    {
                        # 重新鉴权
                        $borderId =  "HLBBK".get_timeHm();
                        $order = array(
                            'idCardNo' => $mybankcard['idcard'],
                            'cardNo' => $mybankcard['cart'],
                            'phone' => $mybankcard['mobile'],
                            'payerName' => $mybankcard['nickname'],
                            'user_id' => $this->uid,
                            'orderId' => $borderId //订单号b
                        );
                        $phone = M("user")->where(array('user_id'=>$this->uid))->getField('phone');
                        $param['userId'] = $phone; # 用户名
                        $res = R("Payapi/Api/ajPayBindCard",array($param,$order));
                        $resjson = json_decode($res[1],true);
                        if($resjson['rt2_retCode'] == "0000")
                        {
                            # 鉴权成功，重新save
                            $savedata['bindId'] = $resjson['rt10_bindId'];
                            $savedata['orderId'] = $resjson['rt6_orderId'];
                            $savedata['retMsg'] = $resjson['rt3_retMsg'];
                            $savedata['bindStatus'] = $resjson['rt7_bindStatus'];
                            $savedata['userId'] = $userId;
                            $savedata['json'] = $res[1];
                            $savedata['t'] = time();

                            M("mybank_bind")->where(array('card'=>$mybankcard['cart'],'user_id'=>$this->uid,'type'=>2,'is_normal'=>1))->save($savedata);


                            $orderId = 'HLBTX'.get_timeHm();  // 重新生成订单
                            # 生成关联订单 - 结算订单
                            $order = array(
                                'userId' => $userId,
                                'orderId' => $orderId,
                                'relation_order' => trim($ifJsStatus['pt_ordersn']),  # 关联订单号
                                'amount' => $amount,
                                'js_money' => trim($ifJsStatus['js_money']),
                                'feeType' => 'PAYER', // PAYER:付款方收取手续费 RECEIVER:收款方收取手续费
                                'summary' => '重新结算提现',
                                'bindId' => $savedata['bindId'],
                                'uid' => $this->uid,
                                'user_js_supply_id' => trim($ifJsStatus['user_js_supply_id']),
                                'sx_money' => trim($ifJsStatus['sx_money']),
                                'relation_order' => trim($ifJsStatus['pt_ordersn']),  # 关联订单号
                                'tx_service' => trim($ifJsStatus['tx_service']),
                                'type' => 2, // 分销结算
                                'js_card' => $mybankcard['cart']
                            );

                            $jsres = R("Payapi/Api/ajPayJsCard",array($param,$order)); # 调用结算接口
                            $jsee = json_decode($jsres[1],true);
                            if($jsee['rt2_retCode'] == "0000")
                            {
                                $d = array(
                                    'code' => 9991,
                                    'msg' => '提交结算成功!'
                                );


                                // 结算成功（解决未结算成功订单，但实际上是上游已结算成功了）
                                M("money_detailed")->where(array('pt_ordersn'=>trim($ifJsStatus['pt_ordersn'])))->save(array('js_status'=>2,'d_t'=>time()));
//                                M("moneyjs_detailed")->where(array('pt_ordersn'=>trim($ifJsStatus['pt_ordersn'])))->save(array('js_status'=>2,'order_status'=>'0000','order_msg'=>'接收成功'));

                                # 结算成功订单分销
                                R("Func/Fenxiao/jiesuanfenxiaoByOrder",array($orderId));
                                echo json_encode($d);exit;
                            }else{
                                $d = array(
                                    'code' => 200,
                                    'msg' => '提交结算失败!'.$jsee['rt3_retMsg']
                                );
                                echo json_encode($d);exit;
                            }


                        }else{
                            $d = array(
                                'code' => 200,
                                'msg' => $resjson['rt3_retMsg']
                            );
                            echo json_encode($d);exit;
                        }
                    }else{
                        # 防止重复提交数据 ( 已经有鉴权过的数据 )

                        /*
                        # 重新鉴权
                        $borderId =  "HLBBK".date('Ymdhis',time());
                        $order = array(
                            'idCardNo' => $mybankcard['idcard'],
                            'cardNo' => $mybankcard['cart'],
                            'phone' => $mybankcard['mobile'],
                            'payerName' => $mybankcard['nickname'],
                            'user_id' => $this->uid,
                            'orderId' => $borderId //订单号b
                        );
                        $phone = M("user")->where(array('user_id'=>$this->uid))->getField('phone');
                        $param['userId'] = $phone; # 用户名
                        $res = R("Payapi/Api/ajPayBindCard",array($param,$order));
                        $resjson = json_decode($res[1],true);
                        if($resjson['rt2_retCode'] != "0000")  # 鉴权失败
                        {
                            $d = array(
                                'code' => 200,
                                'msg' => '请联系客服!'
                            );
                            echo json_encode($d);exit;
                            exit;
                        }
                        */

                        $orderId = 'HLBTX'.get_timeHm(); // 重新生成订单
                        # 生成关联订单 - 结算订单
                        $order = array(
                            'userId' => $userId,
                            'orderId' => $orderId,
                            'relation_order' => trim($ifJsStatus['pt_ordersn']),  # 关联订单号
                            'amount' => $amount,
                            'js_money' => trim($ifJsStatus['js_money']),
                            'feeType' => 'PAYER', // PAYER:付款方收取手续费 RECEIVER:收款方收取手续费
                            'summary' => '重新结算提现',
                            'bindId' => $bindId,
                            'uid' => $this->uid,
                            'user_js_supply_id' => trim($ifJsStatus['user_js_supply_id']),
                            'sx_money' => trim($ifJsStatus['sx_money']),
                            'tx_service' => trim($ifJsStatus['tx_service']),
                            'type' => 2, // 分销结算
                            'js_card' => $mybankcard['cart']
                        );

                        R("Payapi/Api/PaySetLog", array("./PayLog", "ajPayJsCardjs", '--------' . json_encode($order)));

                        $jsres = R("Payapi/Api/ajPayJsCard",array($param,$order)); # 调用结算接口
                        $jsee = json_decode($jsres[1],true);
                        if($jsee['rt2_retCode'] == "0000")
                        {
                            $d = array(
                                'code' => 9991,
                                'msg' => '提交结算成功!'
                            );

                            # 更新交易订单 结算状态
                            // 结算成功（解决未结算成功订单，但实际上是上游已结算成功了）
                            M("money_detailed")->where(array('pt_ordersn'=>trim($ifJsStatus['pt_ordersn'])))->save(array('js_status'=>2,'d_t'=>time()));
//                            M("moneyjs_detailed")->where(array('pt_ordersn'=>trim($ifJsStatus['pt_ordersn'])))->save(array('js_status'=>2,'order_status'=>'0000','order_msg'=>'接收成功'));

                            # 结算成功订单分销
                            R("Func/Fenxiao/jiesuanfenxiaoByOrder",array($orderId));

                            echo json_encode($d);exit;
                        }else{
                            $d = array(
                                'code' => 200,
                                'msg' => '提交结算失败!'.$jsee['rt3_retMsg']
                            );
                            echo json_encode($d);exit;
                        }
                    }
                }else{
                    $d = array(
                        'code' => 200,
                        'msg' => '请重新绑定储蓄卡!'
                    );
                    echo json_encode($d);exit;
                }
            }else{
                $d = array(
                    'code' => 200,
                    'msg' => '用户ID不存在'
                );
                echo json_encode($d);exit;
            }

        }else{
            $d = array(
                'code' => 200,
                'msg' => '此订单号不存在,请联系客服！'
            );
            echo json_encode($d);exit;
        }
    }

    /**
     * 提交结算 -- 储蓄卡提现接口--- 暂不用此接口
     * 针对我的收益-提交结算
     * ordersn = 'HLBTX'.date("YmdHis",time());
     */
    public function ajSyJsTxBak()
    {
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'price' => trim($_REQUEST['price'])
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $param = C("KJPAY");  // 调用function.php文件中的配置参数  # 认证参数

        # 收益金额提示
        # 兜客/推客
        $tkUtype = M("user")->field('utype,level')->where(array('user_id'=>$this->uid))->find();

        if($tkUtype)
        {
            if($tkUtype['utype'] != 20) # 兜客
            {
                $dislevel = M("distribution_level")->where(array('distribution_level_id'=>1))->find();
            }else{   # 推客
                $dislevel = M("distribution_level")->where(array('distribution_level_id'=>$tkUtype['level']))->find();
            }
            $min_tx_money = $dislevel['min_tx_money']; // 最低提现费

        }else{
            echo json_encode(array('code' => 400, 'msg' => '此用户不存在!'));
            die;
        }

        if(!$array['price'])
        {
            echo json_encode(array('code' => 400, 'msg' => '提现金额错误!'));
            die;
        }
        if($array['price'] < $min_tx_money)
        {
            echo json_encode(array('code' => 400, 'msg' => '提现金额不得小于!'.$min_tx_money.'!'));
            die;
        }

        # 判断最后一条交易明细记录，个人余额是否大于最后结算后金额，大于则提示金额错误
        $after_money = M("money_detailed")->where(array('user_id'=>$this->uid))->order('money_detailed_id')->getField('after_money');
        $money = M("user")->where(array('user_id'=>$this->uid))->getField('money');
        if($money > $after_money)
        {
            echo json_encode(array('code' => 400, 'msg' => '你的余额错误!'));
            die;
        }
        # 获取默认绑定的结算卡
        $myjsbank = M("mybank")->where(array('type'=>1,'is_normal'=>1,'user_id'=>$this->uid))->find();

        $userParam = array();
        $userParam['amount'] = trim($_REQUEST['price']); # 结算金额
        if($myjsbank)
        {
            # 查询所绑定的结算卡bindId
            $mybind = M("mybank_bind")->where(array('user_id'=>$this->uid,'type'=>2))->find();
            if($mybind)
            {
                $userParam['userId'] = $mybind['userId'];
                $userParam['orderId'] = "HLBJS".date('Ymdhis',time());//订单号;
                $userParam['bindId'] = $mybind['bindId'];
                $userParam['js_card'] = $mybind['card'];

                $tdid = 1; # 1合利宝收益结算通道
                $user_settlement = M("user_settlement")->where(array('user_settlement_id'=>$tdid))->find();
                $pdata['user_js_supply_id'] = 1; // 目前固定合利宝(1)，收益结算通道
                $order = array(
                    'uid' => $this->uid,
                    'userId' => $userParam['userId'],
                    'orderId' => $userParam['orderId'],
                    'user_js_supply_id' => $userParam['user_js_supply_id'],
                    'amount' => $userParam['amount'] - ($user_settlement['yy_rate']/1000) - $user_settlement['sx_money'], // 到账金额
                    'feeType' => 'PAYER',   // PAYER:付款方收取手续费 ,  RECEIVER:收款方收取手续费
                    'summary' => "结算", // 提现备注
                    'bindId' => $userParam['bindId'], // 绑卡ID
                    'js_card' => trim($userParam['js_card']),
                    'sx_money' => $user_settlement['sx_money'],
                    'tx_service' => ($user_settlement['yy_rate']/1000),
                    'type' => 3 # 分销/利润结算
                );
                $res = R("Payapi/Api/ajPayJsCard",array($param,$order));  // 注释
                if($res[''])
                {

                }
            }else{
                echo json_encode(array('code' => 400, 'msg' => '请先绑定结算卡!'));
            }
        }else{
            echo json_encode(array('code' => 400, 'msg' => '请绑定结算卡!'));
            die;
        }
    }

    /**
     * 解绑银行卡
     *
     */
    public function ajJb()
    {
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'card' => trim($_REQUEST['card'])  # 卡号
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $orderId = 'HLBJB'.date("YmdHis",time());
        $timestamp = date("YmdHis",time());

        $mybank = M("mybank_bind")->where(array('user_id'=>$this->uid,'card'=>trim($_REQUEST['card'])))->find();

        # 银行卡
        $order = array(
            'userId' => trim($mybank['userId']), // 用户ID (按照手机号码)
            'bindId' => trim($mybank['bindId']),  // (绑卡生成的绑卡ID)
            'orderId' => $orderId, // ymbank__20171225055403 // (订单号)
            'timestamp' => $timestamp // (时间戳)
        );

        # 解绑
        $saveres = M("mybank")->where(array('user_id'=>$this->uid,'cart'=>trim($_REQUEST['card'])))->save(array('jq_status'=>1));

        if($mybank['type'] == 2)
        {
            # 换绑结算卡
            M("mybank_bind")->where(array('user_id'=>$this->uid,'card'=>trim($_REQUEST['card'])))->save(array('is_normal'=>2));
        }


        # 解绑(查询如果有存在bindId绑定过的话)
        # 保存日志
        $bankjb['card'] = trim($_REQUEST['card']);
        $bankjb['userId'] = trim($mybank['userId']);
        $bankjb['user_id'] = $this->uid;
        $bankjb['bindId'] = trim($mybank['bindId']);
        $bankjb['status'] = 'SUCCESS';
        M("bank_jb")->add($bankjb);
        if($mybank)
        {
            $res = R("Payapi/Api/BankCardUnbind",array($order));
//        {"rt2_retCode":"0000","sign":"ef7cacdcd79ff6bad768542d51e3d556","rt1_bizType":"BankCardUnbind","rt4_customerNumber":"C1800001834","rt3_retMsg":"成功"}
            if($res['rt2_retCode'] == '0000')
            {
                # save解绑 .... code
                M("mybank_bind")->where(array('userId'=>$mybank['userId'],'bindId'=>$mybank['bindId']))->delete();

//                echo json_encode(array('code' => 200, 'msg' => '解绑成功'));
//                die;
            }else{
//                echo json_encode(array('code' => 400, 'msg' => $res['rt3_retMsg']));
//                die;
            }
        }
        if($saveres)
        {
            echo json_encode(array('code' => 200, 'msg' => '解绑成功'));
            die;
        }else{
            echo json_encode(array('code' => 200, 'msg' => '解绑失败'));
            die;
        }
    }


    /**
     * 用户收益结算
     */
    public function ajSyJs()
    {
        /**/
            if (!$this->sign) {
                echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
                die;
            }
            $array = array(
                'uid' => $this->uid,
                'mybank_id' => trim($_REQUEST['mybank_id']),
                'price' => trim($_REQUEST['price'])
            );
            $this->parem = array_merge($this->parem, $array);
            $msg = R('Func/Func/getKey', array($this->parem));//返回加密
            if ($this->sign !== $msg) {
                echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
                die;
            }
            R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
            $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $param = C("KJPAY");  // 调用function.php文件中的配置参数  # 认证参数

        # 收益金额提示
        # 兜客/推客
        $tkUtype = M("user")->field('utype,level')->where(array('user_id'=>$this->uid))->find();

        # 收益结算通道
        $user_settlement = M("user_settlement")->where(array('type'=>1,'status'=>1))->find();

        if(!$user_settlement)
        {
            echo json_encode(array('code' => 400, 'msg' => '通道正在维护中，请稍后结算!'));
            die;
        }

        $tdid = $user_settlement['user_settlement_id'];


        $mybank_id = trim($_REQUEST['mybank_id']);

        # 获取默认绑定的结算卡
        $myjsbank = M("mybank")->where(array('type'=>1,'mybank_id'=>$mybank_id,'jq_status'=>3,'status'=>1,'user_id'=>$this->uid,'is_normal'=>1))->find();
        if(!$myjsbank)
        {
            echo json_encode(array('code' => 400, 'msg' => '此银行卡不存在!'));
            die;
        }

        # 只支持相对应的结算通道
        # 不支持银行卡
        $supportBank = $user_settlement['bank_id'];
        if(!in_array($myjsbank['bank_id'],explode(',',$supportBank)))
        {
            echo json_encode(array('code' => 400, 'msg' => '此银行卡不支持,请切换!'));
            die;
        }

        if($tkUtype)
        {
            if($tkUtype['utype'] != 20) # 兜客
            {
                $dislevel = M("distribution_level")->where(array('distribution_level_id'=>1))->find();
            }else{   # 推客
                $dislevel = M("distribution_level")->where(array('distribution_level_id'=>$tkUtype['level']))->find();
            }
            $min_tx_money = $dislevel['min_tx_money']; // 最低提现费

        }else{
            echo json_encode(array('code' => 400, 'msg' => '此用户不存在!'));
            die;
        }
        $array['price'] = trim($_REQUEST['price']);

        if(!$array['price'])
        {
            echo json_encode(array('code' => 400, 'msg' => '提现金额错误!'));
            die;
        }
        if($array['price'] < $min_tx_money)
        {
            echo json_encode(array('code' => 400, 'msg' => '提现金额不得小于'.$min_tx_money.'!'));
            die;
        }
        if($array['price'] > $user_settlement['max_db_price'])
        {
            echo json_encode(array('code' => 400, 'msg' => '提现金额不超出'.$user_settlement['max_db_price'].'元!'));
            die;
        }

        $money = M("user")->where(array('user_id'=>$this->uid))->getField('money');
        if($array['price'] > $money)
        {
            echo json_encode(array('code' => 400, 'msg' => '你的余额错误!'));
            die;
        }

        $userParam = array();
        $userParam['amount'] = trim($_REQUEST['price']); # 结算金额

//        dump($tdid);die;

        if($myjsbank)
        {
            if($tdid == 1) # 合利宝收益结算通道
            {
                # 查询所绑定的结算卡bindId
                $mybind = M("mybank_bind")->where(array('user_id'=>$this->uid,'type'=>2,'card'=>$myjsbank['cart']))->find();
                if($mybind)
                {
                    $userParam['orderId'] = "HLBJS".get_timeHm();//订单号;
                    $pdata['user_js_supply_id'] = $tdid;

                    # 获取银行卡
                    $bankCode = M("bank")->where(array('bank_id'=>$myjsbank['bank_id']))->getField('hlb_bank_code');

                    # 获取银行卡信息
                    $bankAccountNo = trim($myjsbank['cart']);
                    $bankAccountName = trim($myjsbank['nickname']);
                    $biz = "B2C";

                    $order = array(
                        'uid' => $this->uid,
    //                    'userId' => trim($userParam['userId']),
                        'orderId' => $userParam['orderId'],
                        'user_js_supply_id' => $tdid,
                        'amount' => $userParam['amount'] - ($user_settlement['sx_money']), // 到账金额
                        'feeType' => 'PAYER',   // PAYER:付款方收取手续费 ,  RECEIVER:收款方收取手续费
                        'summary' => "收益结算", // 提现备注
                        'money' => $userParam['amount'],
                        'js_card' => $bankAccountNo,
                        'sx_money' => '0.00',
                        'bankCode' => $bankCode,
                        'bankAccountNo' => $bankAccountNo,
                        'bankAccountName' => $bankAccountName,
                        'biz' => $biz,
                        'bankUnionCode' => '', # 银行联行号 - 对公联行号必填
                        'tx_service' => $user_settlement['sx_money'],
                        'js_money' => $userParam['amount'] - ($user_settlement['sx_money']),
                    );
                    $res = R("Payapi/Api/ajPayJsCardDf",array($param,$order));  // 注释
                    echo json_encode($res);die;
                }
                else{
                    # 重新鉴权绑卡
                    $borderId =  "HLBBK".get_timeHm();
                    $order = array(
                        'idCardNo' => trim($myjsbank['idcard']),
                        'cardNo' => trim($myjsbank['cart']),
                        'phone' => trim($myjsbank['mobile']),
                        'payerName' => trim($myjsbank['nickname']),
                        'user_id' => $this->uid,
                        'orderId' => $borderId //订单号b
                    );
                    $phone = M("user")->where(array('user_id'=>$this->uid))->getField('phone');
                    $param['userId'] = $phone; # 用户名
                    $res = R("Payapi/Api/ajPayBindCard",array($param,$order));
                    $resjson = json_decode($res[1],true);
                    if($resjson['rt2_retCode'] == "0000") {
                        # 鉴权成功，重新save
                        $savedata['bindId'] = $resjson['rt10_bindId'];
                        $savedata['orderId'] = $resjson['rt6_orderId'];
                        $savedata['retMsg'] = $resjson['rt3_retMsg'];
                        $savedata['bindStatus'] = $resjson['rt7_bindStatus'];
                        $savedata['userId'] = $param['userId'];
                        $savedata['json'] = $res[1];
                        $savedata['t'] = time();

                        if(M("mybank_bind")->where(array('card' => trim($myjsbank['cart']),'user_id'=>$this->uid))->find())
                        {
                            M("mybank_bind")->where(array('card' => trim($myjsbank['cart']), 'user_id' => $this->uid, 'type' => 2, 'is_normal' => 1))->save($savedata);
                        }else{
                            M("mybank_bind")->add($savedata);
                        }


                        $userParam['orderId'] = "HLBJS".get_timeHm();//订单号;
                        $pdata['user_js_supply_id'] = $tdid; // 目前固定合利宝(1)，收益结算通道(表)

                        # 获取银行卡
                        $bankCode = M("bank")->where(array('bank_id'=>$myjsbank['bank_id']))->getField('hlb_bank_code');

                        # 获取银行卡信息
                        $bankAccountNo = trim($myjsbank['cart']);
                        $bankAccountName = trim($myjsbank['nickname']);
                        $biz = "B2C";

                        $order = array(
                            'uid' => $this->uid,
    //                    'userId' => trim($userParam['userId']),
                            'orderId' => $userParam['orderId'],
                            'user_js_supply_id' => $tdid,
                            'amount' => $userParam['amount'] - ($user_settlement['sx_money']), // 到账金额
                            'feeType' => 'PAYER',   // PAYER:付款方收取手续费 ,  RECEIVER:收款方收取手续费
                            'summary' => "收益结算", // 提现备注
                            'money' => $userParam['amount'],
                            'js_card' => $bankAccountNo,
                            'sx_money' => '0.00',
                            'bankCode' => $bankCode,
                            'bankAccountNo' => $bankAccountNo,
                            'bankAccountName' => $bankAccountName,
                            'biz' => $biz,
                            'bankUnionCode' => '', # 银行联行号 - 对公联行号必填
                            'tx_service' => $user_settlement['sx_money'],
                            'js_money' => $userParam['amount'] - ($user_settlement['sx_money']),
                        );
                        $res = R("Payapi/Api/ajPayJsCardDf",array($param,$order));  // 注释
                        echo json_encode($res);die;

                    }

                    echo json_encode(array('code' => 400, 'msg' => '请先绑定结算卡!'));die;
                }
            }
            else if($tdid == 2) # 柜银云付收益结算通道
            {
                if(empty($myjsbank['lianhang']))
                {
                    echo json_encode(array('code' => 400, 'msg' => '联行号不能为空!请联系客服'));
                    die;
                }
                import('Vendor.payJufu.jfPay');
                $config = C("JYYFPAY");
                $JfPay = new \jfPay($config);
                $order_Id = "JFJS".get_timeHm();

                $bankname = M("bank")->where(array('bank_id'=>$myjsbank['bank_id']))->getField('name');
                $postData = array(
                    'account_Name' => trim($myjsbank['nickname']),  # 账户名称
                    'account_Number' => trim($myjsbank['cart']),  # 账户卡号
                    'amount' => $userParam['amount'] - ($user_settlement['sx_money']),  # 交易金额元
                    'bank_Code' => trim($myjsbank['lianhang']), # 联行号
                    'bank_Name' => trim($bankname),  # 银行名称
                    'mobile' => trim($myjsbank['mobile']), # 手机号
                    'order_Id' => $order_Id # 订单号
                );
                $res = $JfPay->submitPay($postData);
                $jdecode = json_decode($res,true);


                $order['uid'] = $this->uid;
                $P3_orderId = $order_Id;
                $order['user_js_supply_id'] = $tdid;
                $order['money'] = $userParam['amount'];
                $P5_amount = $userParam['amount'] - ($user_settlement['sx_money']);
                # 结算明细表
                $moneyjsde['user_id'] = $order['uid'];
                $moneyjsde['pt_ordersn'] = $P3_orderId;
                $moneyjsde['user_js_supply_id'] = $order['user_js_supply_id'];  # 结算通道
                $moneyjsde['js_money'] = $order['money']; # 结算金额
                $moneyjsde['tx_service'] = ($user_settlement['sx_money']);
                $moneyjsde['rz_money'] = $P5_amount;
                $moneyjsde['sx_money'] = ($user_settlement['sx_money']);
                $moneyjsde['serial_num'] = "";

                $moneyjsde['type'] = 3; # 1余额结算/2分销结算/3收益结算
                $moneyjsde['js_card'] = trim($myjsbank['cart']); # 结算卡号
                $moneyjsde['t'] = time();
                $moneyjsde['js_type'] = 1;

                # 添加金额
                M("crepayjs_log")->add(array(
                    'order_amount'=>$P5_amount,
                    'user_id' =>  $order['uid'],
                    'ordersn' => $P3_orderId
                ));
                M("crepayjs_log")->where(array("ordersn"=>$P3_orderId))->save(array(
                    'order_status'=>'SUCCESS'
                ));

                # 减去收益结算金额
                $beforemoney = M("user")->where(array('user_id'=>$order['uid']))->getField('money');
                $djbeforemoney = M("user")->where(array('user_id'=>$order['uid']))->getField('dj_money');

                M("moneyjs_detailed")->where(array('pt_ordersn' => $P3_orderId))->save(array('before_money'=>$beforemoney,'after_money'=>$beforemoney - $userParam['amount'],'js_status'=>1,'js_success'=>time()));  // 结算成功
                
                $mondata = array(
                    'user_id' => $order['uid'],
                    'msg' => '收益结算',
                    'money' => $order['money'],
                    'pn' => '-',  // + -
                    'ordersn' => $P3_orderId,
                    'type' => 1,  // 1收益结算3余额结算
                    'is_type' => 5  // 1收益钱包结算2手机充值3充值失败退款4新年抢红包活动5提现到银行卡
                );

                $msg = "用户ID：".$order['uid']."于".date("Y-m-d H:i:s",time())."，结算入账：".$order['money'];
                $history_money = M('liquidation')->where(array('user_js_supply_id'=>9,'status'=>1))->order('liquidation_id desc')->getField('current_money');
                $tx_cb = M("user_js_supply")->where(array('user_js_supply_id'=>9))->getField('tx_cb');

                // 记录之前的余额
                R("Payapi/OrderReturn/balanceCheck");

                /*
                # 代付当前余额
                $current_money = $jsreturn['message'];
                */
                $current_money = $history_money-$order['money']-$tx_cb+$user_settlement['sx_money'];

                $d['money'] = $P5_amount;
                $d['pn'] = '-';
                $d['msg'] = $msg;
                $d['user_id'] = trim($order['uid']);
                $d['user_js_supply_id'] = 9; # 青岛
                $d['sh_ordersn'] = $P3_orderId;
                $d['sx_money'] = $tx_cb; // 提现成本
                $d['current_money'] = $current_money;
                $d['history_money'] = $history_money;
                $d['type'] = 3;
                $d['t'] = time();

                if($jdecode['respCode'] == "0000") # success
                {
                    $moneyjsde['js_status'] = 2;
                    $d['status'] = 1;
                    M("user")->where(array('user_id'=>$order['uid']))->save(array('money'=>$beforemoney - $userParam['amount']));
                    echo json_encode(array('code' => 200, 'msg' => '提交结算成功!'));
                }else{
                    # 全部统一为结算中/处理中，并且把金额分为冻结结算余额
                    $moneyjsde['js_status'] = 1;
                    $d['status'] = 2;
                    M("user")->where(array('user_id'=>$order['uid']))->save(array('money'=>$beforemoney - $userParam['amount'],'dj_money'=>$djbeforemoney + $userParam['amount']));

                    # 失败
                    /*
                    $moneyjsde['js_status'] = 3;
                    $d['status'] = 3;
                    */

                    echo json_encode(array('code' => 400, 'msg' => $jdecode['message']));
                }
                M("moneyjs_detailed")->add($moneyjsde);  // 添加记录表
                M("liquidation")->add($d);
                # 金额日志记录
                R("Func/Money/userMoneyLog",array($mondata));
                die;
            }
        }else{
            echo json_encode(array('code' => 400, 'msg' => '请先绑定结算卡!'));
            die;
        }
    }

    /**
     * 用户零钱提现
     */
    public function ajPocketMoney()
    {
        /**/
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'mybank_id' => trim($_REQUEST['mybank_id']),
            'price' => trim($_REQUEST['price'])
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $param = C("KJPAY");  // 调用function.php文件中的配置参数  # 认证参数

        # 兜客/推客
        $tkUtype = M("user")->field('utype,level')->where(array('user_id'=>$this->uid))->find();

        # 零钱结算通道
        $user_settlement = M("user_settlement")->where(array('type'=>3,'status'=>1))->find();
        if(!$user_settlement)
        {
            echo json_encode(array('code' => 400, 'msg' => '通道正在维护中，请稍后结算!'));
            die;
        }
        $tdid = $user_settlement['user_settlement_id'];
        $mybank_id = trim($_REQUEST['mybank_id']);

        # 获取默认绑定的结算卡
        $myjsbank = M("mybank")->where(array('type'=>1,'mybank_id'=>$mybank_id,'jq_status'=>3,'status'=>1,'user_id'=>$this->uid,'is_normal'=>1))->find();
        if(!$myjsbank)
        {
            echo json_encode(array('code' => 400, 'msg' => '此银行卡不存在!'));
            die;
        }

        # 只支持相对应的结算通道
        # 不支持银行卡
        $supportBank = $user_settlement['bank_id'];
        if(!in_array($myjsbank['bank_id'],explode(',',$supportBank)))
        {
            echo json_encode(array('code' => 400, 'msg' => '此银行卡不支持,请切换!'));
            die;
        }


        if($tkUtype)
        {
            /*
            if($tkUtype['utype'] != 20) # 兜客
            {
                $dislevel = M("distribution_level")->where(array('distribution_level_id'=>1))->find();
            }else{   # 推客
                $dislevel = M("distribution_level")->where(array('distribution_level_id'=>$tkUtype['level']))->find();
            }
            $min_tx_money = $dislevel['min_tx_money']; // 最低提现费
            */

            $min_tx_money = $user_settlement['min_tx_price'];
        }else{
            echo json_encode(array('code' => 400, 'msg' => '此用户不存在!'));
            die;
        }
        $array['price'] = trim($_REQUEST['price']);

        if(!$array['price'])
        {
            echo json_encode(array('code' => 400, 'msg' => '提现金额错误!'));
            die;
        }

        if($array['price'] < $min_tx_money)
        {
            echo json_encode(array('code' => 400, 'msg' => '提现金额不得小于'.$min_tx_money.'!'));
            die;
        }
        if($array['price'] > $user_settlement['max_db_price'])
        {
            echo json_encode(array('code' => 400, 'msg' => '提现金额不超出'.$user_settlement['max_db_price'].'元!'));
            die;
        }

        $money = M("user")->where(array('user_id'=>$this->uid))->getField('wallet_money');
        if($array['price'] > $money)
        {
            echo json_encode(array('code' => 400, 'msg' => '你的余额错误!'));
            die;
        }

        $userParam = array();
        $userParam['amount'] = trim($_REQUEST['price']); # 结算金额
        if($myjsbank)
        {
            if($tdid == 3) # 柜银云付
            {
                if(empty($myjsbank['lianhang']))
                {
                    echo json_encode(array('code' => 400, 'msg' => '联行号不能为空!请联系客服'));
                    die;
                }
                import('Vendor.payJufu.jfPay');
                $config = C("JYYFPAY");
                $JfPay = new \jfPay($config);
                $order_Id = "JFJS".get_timeHm();

                $bankname = M("bank")->where(array('bank_id'=>$myjsbank['bank_id']))->getField('name');
                $postData = array(
                    'account_Name' => trim($myjsbank['nickname']),  # 账户名称
                    'account_Number' => trim($myjsbank['cart']),  # 账户卡号
                    'amount' => $userParam['amount'] - $userParam['amount']*($user_settlement['sx_money']/100),  # 交易金额元
                    'bank_Code' => trim($myjsbank['lianhang']), # 联行号
                    'bank_Name' => trim($bankname),  # 银行名称
                    'mobile' => trim($myjsbank['mobile']), # 手机号
                    'order_Id' => $order_Id # 订单号
                );
                $order['uid'] = $this->uid;
                $res = $JfPay->submitPay($postData);
                $jdecode = json_decode($res,true);


                $P3_orderId = $order_Id;
                $order['user_js_supply_id'] = $tdid;
                $order['money'] = $userParam['amount'];
                $P5_amount = $userParam['amount'] - $userParam['amount']*($user_settlement['sx_money']/100);
                # 结算明细表
                $moneyjsde['user_id'] = $order['uid'];
                $moneyjsde['pt_ordersn'] = $P3_orderId;
                $moneyjsde['user_js_supply_id'] = $order['user_js_supply_id'];  # 结算通道
                $moneyjsde['js_money'] = $order['money']; # 结算金额
                $moneyjsde['tx_service'] = $userParam['amount']*($user_settlement['sx_money']/100);
                $moneyjsde['rz_money'] = $P5_amount;
                $moneyjsde['sx_money'] = $userParam['amount']*($user_settlement['sx_money']/100);
                $moneyjsde['serial_num'] = "";
                $moneyjsde['type'] = 1; # 1零钱结算/2分销结算/3收益结算
                $moneyjsde['js_card'] = trim($myjsbank['cart']); # 结算卡号
                $moneyjsde['t'] = time();
                $moneyjsde['js_type'] = 1;


                # 添加金额
                M("crepayjs_log")->add(array(
                    'order_amount'=>$P5_amount,
                    'user_id' =>  $order['uid'],
                    'ordersn' => $P3_orderId
                ));
                M("crepayjs_log")->where(array("ordersn"=>$P3_orderId))->save(array(
                    'order_status'=>'SUCCESS'
                ));

                # 减去收益结算金额
                $beforemoney = M("user")->where(array('user_id'=>$order['uid']))->getField('wallet_money');

                M("moneyjs_detailed")->where(array('pt_ordersn' => $P3_orderId))->save(array('before_money'=>$beforemoney,'after_money'=>$beforemoney - $userParam['amount'],'js_status'=>2,'js_success'=>time()));  // 结算成功
                
                $mondata = array(
                    'user_id' => $order['uid'],
                    'msg' => '零钱提现',
                    'money' => $userParam['amount'],
                    'pn' => '-',  // + -
                    'ordersn' => $P3_orderId,
                    'type' => 2,  // 1收益结算2余额结算
                    'is_type' => 5  // 1收益钱包结算2手机充值3充值失败退款4新年抢红包活动5提现到银行卡
                );

                $msg = "用户ID：".$order['uid']."于".date("Y-m-d H:i:s",time())."，提现入账：".$order['money'];
                $history_money = M('liquidation')->where(array('user_js_supply_id'=>9,'status'=>1))->order('liquidation_id desc')->getField('current_money');

                /*
                $jsreturn = R("Payapi/OrderReturn/balanceCheck");
                # 代付当前余额
                $current_money = $jsreturn['message'];
                */
                $current_money = $history_money-$order['money']-$user_settlement['sx_money'];

                $d['money'] = $P5_amount;
                $d['pn'] = '-';
                $d['msg'] = $msg;
                $d['user_js_supply_id'] = 9;
                $d['sh_ordersn'] = $P3_orderId;
                $d['sx_money'] = ($user_settlement['sx_money']);
                $d['current_money'] = $current_money;
                $d['history_money'] = $history_money;
                $d['type'] = 3;
                $d['t'] = time();

                if($jdecode['respCode'] == "0000") # success
                {
                    $moneyjsde['js_status'] = 2;
                    $d['status'] = 1;
                    M("user")->where(array('user_id'=>$order['uid']))->save(array('wallet_money'=>$beforemoney - $userParam['amount']));
                    echo json_encode(array('code' => 200, 'msg' => '提现成功!'));
                }else{
                    $moneyjsde['js_status'] = 3;
                    $d['status'] = 3;
                    echo json_encode(array('code' => 400, 'msg' => $jdecode['message']));
                }
                M("moneyjs_detailed")->add($moneyjsde);
                M("liquidation")->add($d);
                # 金额日志记录
                R("Func/Money/userMoneyLog",array($mondata));
                die;
            }
        }else{
            echo json_encode(array('code' => 400, 'msg' => '请先绑定结算卡!'));
            die;
        }
    }

    /**
     * 零钱记录
     */
    public function getPocketMoney()
    {
        /**/
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $w['user_id'] = $this->uid;
        $w['type'] = 2;
        $d = array();
        $d = M("usermoney_log")->where($w)->order('usermoney_log_id desc')->select();
        foreach ($d as $k => $v)
        {
            # 昨天
            $tomorrow = mktime(0,0,0,date("m"),date("d")-1,date("Y"));
            $today = date("Y-m-d", $tomorrow);
            if(date('Y-m-d',$v['t']) == $today)
            {
                $timemsg = "昨天".date("H:i",$v['t']);
            }
            # 今天
            else if(date('Y-m-d',$v['t']) == date("Y-m-d"))
            {
                $timemsg = "今天".date("H:i",$v['t']);
            }else{
                $timemsg = date("m-d H:i",$v['t']);
            }
            $d[$k]['timemsg'] = $timemsg;

            if($v['is_type'] == 2)
            {
                $istype = "手机充值";
            }else if($v['is_type'] == 3)
            {
                $istype = "手机充值失败退款";
            }else if($v['is_type'] == 4)
            {
                $istype = "新年抢红包活动";
            }else if($v['is_type'] == 5)
            {
                $istype = "提现到银行卡";
            }

            if($v['is_type'] == 2)
            {
                $jyorder = M('chongzhi_order')->field('status,time,other_order_number')->where(array('order_number'=>$v['ordersn']))->find();
                if($jyorder['status'] == 2)
                {
                    $jy_status = "未充值";
                }else if($jyorder['status'] == 1)
                {
                    $jy_status = "充值成功";
                }else if($jyorder['status'] == 3)
                {
                    $jy_status = "处理中";
                }else if($jyorder['status'] == 4)
                {
                    $jy_status = "交易关闭";
                }
                $isstatus = $jy_status;
            }
            else if($v['is_type'] == 3)
            {
                $jyorder = M('chongzhi_order')->field('is_refund,time')->where(array('order_number'=>$v['ordersn']))->find();
                if($jyorder['is_refund'] == 5)
                {
                    $jy_status = "不可退款";
                }else if($jyorder['is_refund'] == 1)
                {
                    $jy_status = "可申请退款";
                }else if($jyorder['is_refund'] == 3)
                {
                    $jy_status = "退款成功";
                }
                $isstatus = $jy_status;
            }
            else if($v['is_type'] == 4)
            {
                $jyorder = M('activity_win')->where(array('activitorder'=>$v['ordersn']))->find();
                if($jyorder['isaward'] == 1)
                {
                    $jy_status = "领取成功";
                }else
                {
                    $jy_status = "未领取";
                }
                $isstatus = $jy_status;
            }
            else if($v['is_type'] == 5)
            {
                $jsorder = M("moneyjs_detailed")->field('t,js_status,sx_money,js_card')->where(array('pt_ordersn'=>$v['ordersn']))->find();
                if($jsorder['js_status'] == 1) # 提现中
                {
                    $js_statusname = "提现中";
                }elseif($jsorder['js_status'] == 2) # 提现成功
                {
                    $js_statusname = "提现成功";
                }elseif($jsorder['js_status'] == 3) # 提现成功
                {
                    $js_statusname = "提现失败";
                }
                $isstatus = $js_statusname;
            }
            $d[$k]['istype'] = $istype;
            $d[$k]['isstatus'] = $isstatus;
        }
        $usemoney = M("user")->field('wallet_money,dj_wallet_money')->where(array('user_id'=>$this->uid))->find();
        echo json_encode(array('code' => 200, 'msg' => '零钱记录','usemoney'=>$usemoney['wallet_money'],'nousemoney'=>$usemoney['dj_wallet_money'],'data'=>$d));
        die;
    }

    /**
     * 零钱详情 - H5
     */
    public function getPocketMoneyDetail()
    {
        $usermoney_log_id = trim($_REQUEST['usermoney_log_id']);
        /**/
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'usermoney_log_id' => $usermoney_log_id
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $w['user_id'] = $this->uid;
        $w['type'] = 2;
        $w['usermoney_log_id'] = $usermoney_log_id;
        $d = M("usermoney_log")->where($w)->order('usermoney_log_id desc')->find();
        if(!$d)
        {
            echo json_encode(array('code' => 400, 'msg' => '无数据!'));
            die;
        }
        $ptordersn = trim($d['ordersn']);
        $pttime = date("Y-m-d H:i:s",$d['t']);

        if($d['is_type'] == 2)
        {
            $istype = "手机充值";

            $jyorder = M('chongzhi_order')->field('status,time,other_order_number')->where(array('order_number'=>$d['ordersn']))->find();
            if($jyorder['status'] == 2)
            {
                $jy_status = "未充值";
            }else if($jyorder['status'] == 1)
            {
                $jy_status = "充值成功";
            }else if($jyorder['status'] == 3)
            {
                $jy_status = "处理中";
            }else if($jyorder['status'] == 4)
            {
                $jy_status = "交易关闭";
            }
            $arrsx = array(
                '交易状态' => $jy_status,
                '付款方式' => '我的零钱',
                '充值时间' => date("Y-m-d H:i:s",$jyorder['time']),
                '订单号' => $ptordersn,
                '商户订单号' => $jyorder['other_order_number']
            );
        }
        else if($d['is_type'] == 3)
        {
            $istype = "手机充值失败退款";
            $jyorder = M('chongzhi_order')->field('is_refund,time')->where(array('order_number'=>$d['ordersn']))->find();
            if($jyorder['is_refund'] == 5)
            {
                $jy_status = "不可退款";
            }else if($jyorder['is_refund'] == 1)
            {
                $jy_status = "可申请退款";
            }else if($jyorder['is_refund'] == 3)
            {
                $jy_status = "退款成功";
            }
            $arrsx = array(
                '交易状态' => $jy_status,
                '申请时间' => date("Y-m-d H:i:s",$jyorder['time']),
                '到账时间' => $pttime?$pttime:"",
                '退款账号' => '我的零钱',
                '订单号' => $ptordersn
            );
        }
        else if($d['is_type'] == 4)
        {
            $istype = "新年抢红包活动";
            $jyorder = M('activity_win')->where(array('activitorder'=>$d['ordersn']))->find();
            if($jyorder['isaward'] == 1)
            {
                $jy_status = "领取成功";
            }else
            {
                $jy_status = "未领取";
            }
            $arrsx = array(
                '交易状态' => $jy_status,
                '到账时间' => date("Y-m-d H:i:s",$jyorder['addtime']),
                '到账账号' => '我的零钱',
                '到账金额' => $d['money'],
                '订单号' => $ptordersn
            );

        }
        else if($d['is_type'] == 5)
        {
            $istype = "提现到银行卡";
            $jsorder = M("moneyjs_detailed")->field('t,js_status,sx_money,js_card')->where(array('pt_ordersn'=>$d['ordersn']))->find();
            if($jsorder['js_status'] == 1) # 提现中
            {
                $js_statusname = "提现中";
            }elseif($jsorder['js_status'] == 2) # 提现成功
            {
                $js_statusname = "提现成功";
            }elseif($jsorder['js_status'] == 3) # 提现成功
            {
                $js_statusname = "提现失败";
            }
            $mybank = M("mybank")->where(array('type'=>1,'cart'=>$jsorder['js_card'],'user_id'=>$this->uid))->find();
            if($mybank)
            {
                $bankname = M("bank")->where(array('bank_id'=>$mybank['bank_id']))->getField('name');
            }else{
                $bankname = '暂无显示';
            }
            $arrsx = array(
                '交易状态' => $js_statusname,
                '申请时间' => date("Y-m-d H:i:s",$jsorder['t'])?date("Y-m-d H:i:s",$jsorder['t']):"",
                '到账时间' => $pttime?$pttime:"",
                '手续费' => $jsorder['sx_money'],
                '提现银行' => $bankname.'( '.substr($jsorder['js_card'],-1,4).' )'.trim($mybank['nickname']),
                '订单号' => $ptordersn,
            );
        }
        $d['istype'] = $istype;
        $d['arrsx'] = $arrsx;

        $this->assign("d",$d);
        $this->display();
    }



    /**
     * 智付支付接口测试
     */
    public function ajZfPayCeshi()
    {
        $td = intval($_REQUEST['td']); // 默认为1
        $price = $_REQUEST['price'];

        /**/
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }

        $array = array(
            'uid' => $this->uid,
            'td' => $td,
            'price' => $_REQUEST['price']
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }

        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        if ($td != 1)  # 先开发快捷支付,后续再增加其他支付（增加了，记住要把它注释掉）
        {
            $d = array(
                'code' => 7000,
                'msg' => '请先调用快捷支付'
            );
            echo json_encode($d);
            exit;
        }


        # 新增话费/流量充值
        $payType = 2;  # 1余额支付2快捷支付
        $phone = trim($_REQUEST['phone']);
        $goodsid = trim($_REQUEST['goodsid']);

        # 交易类型名称
        $tdtype = R("Func/Func/getPayType", array($td)); //快捷支付
        $tylist = $this->getSkTdCeshi($td);


        # 是否终止
        foreach ($tylist as $k => $v)
        {
            $is_return = M("pay_supply")->where(array('pay_supply_id'=>$v['pay_supply_id']))->getField('is_return');
            $tylist[$k]['is_return'] = 2;
        }


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
        echo json_encode($d);
        exit;
    }

    public function getSkTdCeshi($td=1)
    {
        $tdid = M('pay_supply_cate')->where(array('pay_supply_cate_id'=>intval($td)))->getField('pay_supply_cate_id');
        $res = array();
        $tyname =  R("Func/Func/getPayType", array($td)); //快捷支付
        if($tdid)
        {
            $sid = M('pay_supply')->field('pay_supply_id,englistname')->where(array('pay_supply_cate_id'=>$tdid))->select();
            if($sid)
            {
                $exsid = "";
                foreach ($sid as $k => $v){
                    $exsid .= ",".$v['pay_supply_id'];
                }
                $exsid = ltrim($exsid,',');
                $res = M('user_pay_supply')->where(array('pay_supply_id'=>array('in',$exsid)))->select();
                # 支付名称
                foreach ($res as $k => $v)
                {
                    $res[$k]['typename'] = $sid[$k]['englistname'].'-'.$tyname;
                    if($v['do_type'] == 'DOWALLET')
                    {
                        $do_type_name = "D0实时到钱包余额";
                    }else if($v['do_type'] == 'D0BANK'){
                        $do_type_name = "D0实时到默认结算卡";
                    }
                    $res[$k]['do_type_name'] = $do_type_name;
                    # 新增结算提现/元-笔
                    $yy_rate = M("user_js_supply")->where(array('sid'=>$v['pay_supply_id']))->getField('yy_rate');
                    $res[$k]['js_txmoney'] = $yy_rate;
                }
            }
        }
        return $res;
    }


    /*
     * 重新鉴权测试专用 - 结算卡(不使用该手机号码)
     * */
    public function jsceshi($uid)
    {
//        DK300000078
//        $uid = "TK200000014";
        $param = C("KJPAY");  // 调用function.php文件中的配置参数  # 认证参数
        # 获取默认的结算卡
        $mybankType = M("mybank")->where(array('user_id'=>$uid,'type'=>1,'is_normal'=>1,'jq_status'=>3))->order('mybank_id asc')->find();  # 默认第一张结算卡


        if($mybankType)
        {
            $borderId =  "HLBBK".get_timeHm();
            $order = array(
                'idCardNo' => $mybankType['idcard'],
                'cardNo' => $mybankType['cart'],
                'phone' => '',
                'payerName' => $mybankType['nickname'],
                'orderId' => $borderId, //订单号b
                'user_id'=>$uid
            );
            $phone = M("user")->where(array('user_id'=>$uid))->getField('phone');
            $param['userId'] = $phone; # 用户名
            $res = $this->ajPayBindCardCeshi($param,$order);
            $resjson = json_decode($res[1],true);
            dump($resjson);
        }
    }

    # 绑卡结算 - 测试
    public function ajPayBindCardCeshi($param=array(),$order=array())
    {
        import('Vendor.payPerson.HttpClient');
        $Client = new \HttpClient($param['ip']);

        $phone = $order['phone'];
        $signkey = $param['signkey_quickpay'];

        if ($signkey <> '') {//校验必要参数值
            //表单值
            $P1_bizType =  "SettlementCardBind";  # 绑定卡变量名
            $P2_customerNumber =  $param["P2_customerNumber"];
            $P3_userId =  $param["userId"];
            $P4_orderId =  $order['orderId'];
            $P5_payerName =  $order["payerName"];
            $P6_idCardType =  "IDCARD"; // 固定身份证类型
            $P7_idCardNo =  $order["idCardNo"];
            $P8_cardNo =  $order["cardNo"];
            $P9_phone =  $phone;
            $P10_bankUnionCode =  ''; // 行号

            //构造签名串
            $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_userId&$P4_orderId&$P5_payerName&$P6_idCardType&$P7_idCardNo&$P8_cardNo&$P9_phone&$P10_bankUnionCode&$signkey";

            $sign=md5($signFormString);

            //构造请求参数
            $params = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_userId'=>$P3_userId,'P4_orderId'=>$P4_orderId,'P5_payerName'=>$P5_payerName,'P6_idCardType'=>$P6_idCardType,'P7_idCardNo'=>$P7_idCardNo,'P8_cardNo'=>$P8_cardNo,'P9_phone'=>$P9_phone,'P10_bankUnionCode'=>$P10_bankUnionCode,'sign'=>$sign);

//            $url = "http://test.trx.helipay.com/trx/quickPayApi/interface.action";
//            $url = "http://transfer.trx.helipay.com/trx/transfer/interface.action";
            $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";
            $pageContents = $Client->quickPost($url, $params);  //发送请求 send request

            dump($signFormString);
            dump($params);
            dump($pageContents);
die;
            # 保存日志
            $this->PaySetLog("./PayLog","Api_ajPayBindCard__",json_encode($params)."-------  鉴权结算卡绑卡 ----"."---------".$pageContents."------- \r\n");

            $obj = json_decode($pageContents);
            if ($obj->rt2_retCode == "0000") {  # 绑卡记录
                # 保存订单信息

                /*
                $ldata['ordersn'] = $P4_orderId;
                $ldata['userId'] = $P3_userId;
                $ldata['phone'] = $phone;
                $ldata['payerName'] = $P5_payerName;
                $ldata['cardNo'] = $P8_cardNo;
                $ldata['ip'] = get_client_ip(); # 下单IP地址
                $ldata['t'] = time();
                M("crepayjs_log")->add($ldata);
                */


                # 鉴权银行卡
                $jqlog['bankid'] = $obj->rt10_bindId;
                $jqlog['json'] = $pageContents;
                $jqlog['serial_number'] = '';
                $jqlog['t'] = time();
                $jqlog['user_id'] = trim($order['user_id']);
                $jqlog['userId'] = $P3_userId;
                $jqlog['ordersn'] = trim($order['orderId']);
                $jqlog['card'] = trim($order['cardNo']);
                $jqlog['jq_td_id'] = 1; //1合利宝鉴权通道2松顺鉴权通道
                M("jq_log")->add($jqlog);

            }
            return array(1,$pageContents);
//            echo "back msg:".$pageContents."<br/>";  //返回的结果   The returned result
        }
    }



    # 结算卡信息查询
    public function JsCartCheck($userId)
    {
       $res = $this->ajJsCartCheck($userId);
       dump($res);
    }


    # 结算卡调用查询
    public function ajJsCartCheck($userId="",$orderId="")
    {
        import('Vendor.payPerson.HttpClient');
        $param = C("KJPAY");
        $Client = new \HttpClient($param['ip']);
        $signkey = $param['signkey_quickpay'];

        if ($signkey <> '') {//校验必要参数值
            //表单值
            $P1_bizType =  "SettlementCardQuery";  # 绑定卡变量名
            $P2_customerNumber =  $param["P2_customerNumber"];
            $P3_userId =  $userId;
            $P4_orderId =  $orderId;
            $P5_timestamp =  date("YmdHis",time());
            //构造签名串
            $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_userId&$P4_orderId&$P5_timestamp&$signkey";

            $sign=md5($signFormString);

            //构造请求参数
            $params = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_userId'=>$P3_userId,'P4_orderId'=>$P4_orderId,'P5_timestamp'=>$P5_timestamp,'sign'=>$sign);

//            $url = "http://test.trx.helipay.com/trx/quickPayApi/interface.action";
//            $url = "http://transfer.trx.helipay.com/trx/transfer/interface.action";
            $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";
            $pageContents = $Client->quickPost($url, $params);  //发送请求 send request

            dump($signFormString);
            dump($params);
            dump($pageContents);
            die;
            # 保存日志
            $this->PaySetLog("./PayLog","Api_ajPayBindCard__",json_encode($params)."-------  鉴权结算卡绑卡 ----"."---------".$pageContents."------- \r\n");

            $obj = json_decode($pageContents);
            if ($obj->rt2_retCode == "0000") {  # 绑卡记录
                # 保存订单信息

                /*
                $ldata['ordersn'] = $P4_orderId;
                $ldata['userId'] = $P3_userId;
                $ldata['phone'] = $phone;
                $ldata['payerName'] = $P5_payerName;
                $ldata['cardNo'] = $P8_cardNo;
                $ldata['ip'] = get_client_ip(); # 下单IP地址
                $ldata['t'] = time();
                M("crepayjs_log")->add($ldata);
                */


                # 鉴权银行卡
                $jqlog['bankid'] = $obj->rt10_bindId;
                $jqlog['json'] = $pageContents;
                $jqlog['serial_number'] = '';
                $jqlog['t'] = time();
                $jqlog['user_id'] = trim($order['user_id']);
                $jqlog['userId'] = $P3_userId;
                $jqlog['ordersn'] = trim($order['orderId']);
                $jqlog['card'] = trim($order['cardNo']);
                $jqlog['jq_td_id'] = 1; //1合利宝鉴权通道2松顺鉴权通道
                M("jq_log")->add($jqlog);

            }
            return array(1,$pageContents);
//            echo "back msg:".$pageContents."<br/>";  //返回的结果   The returned result
        }
    }
    /**
     * 用户收益结算测试例子
     */
    public function ajSyJsceshi()
    {
        /*
            if (!$this->sign) {
                echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
                die;
            }
            $array = array(
                'uid' => $this->uid,
                'mybank_id' => trim($_REQUEST['mybank_id']),
                'price' => trim($_REQUEST['price'])
            );
            $this->parem = array_merge($this->parem, $array);
            $msg = R('Func/Func/getKey', array($this->parem));//返回加密
            if ($this->sign !== $msg) {
                echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
                die;
            }
            R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
            $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
        */

        $param = C("KJPAY");  // 调用function.php文件中的配置参数  # 认证参数

        # 收益金额提示
        # 兜客/推客
        $tkUtype = M("user")->field('utype,level')->where(array('user_id'=>$this->uid))->find();

        $tdid = 1; # 1合利宝收益结算通道
        $user_settlement = M("user_settlement")->where(array('user_settlement_id'=>$tdid))->find();
        if($tkUtype)
        {
            if($tkUtype['utype'] != 20) # 兜客
            {
                $dislevel = M("distribution_level")->where(array('distribution_level_id'=>1))->find();
            }else{   # 推客
                $dislevel = M("distribution_level")->where(array('distribution_level_id'=>$tkUtype['level']))->find();
            }
            $min_tx_money = $dislevel['min_tx_money']; // 最低提现费（针对推客/兜客）

        }else{
            echo json_encode(array('code' => 400, 'msg' => '此用户不存在!'));
            die;
        }
        $array['price'] = trim($_REQUEST['price']);
        if(!$array['price'])
        {
            echo json_encode(array('code' => 400, 'msg' => '提现金额错误!'));
            die;
        }
        if($array['price'] < $min_tx_money)
        {
            echo json_encode(array('code' => 400, 'msg' => '提现金额不得小于!'.$min_tx_money.'元!'));
            die;
        }
        if($array['price'] > $user_settlement['max_db_price'])
        {
            echo json_encode(array('code' => 400, 'msg' => '提现金额不超出'.$user_settlement['max_db_price'].'元!'));
            die;
        }

        # 判断最后一条交易明细记录，个人余额是否大于最后结算后金额，大于则提示金额错误
        $after_money = M("money_detailed")->where(array('user_id'=>$this->uid))->order('money_detailed_id')->getField('after_money');
        $money = M("user")->where(array('user_id'=>$this->uid))->getField('money');
        if($money > $after_money)
        {
            echo json_encode(array('code' => 400, 'msg' => '你的余额错误!'));
            die;
        }
        # 获取默认绑定的结算卡
        $myjsbank = M("mybank")->where(array('type'=>1,'mybank_id'=>trim($_REQUEST['mybank_id']),'user_id'=>$this->uid))->find();

        if(!$myjsbank)
        {
            echo json_encode(array('code' => 400, 'msg' => '此银行卡不存在!'));
            die;
        }

        $userParam = array();
        $userParam['amount'] = trim($_REQUEST['price']); # 结算金额
        if($myjsbank)
        {
            # 查询所绑定的结算卡bindId
            $mybind = M("mybank_bind")->where(array('user_id'=>$this->uid,'type'=>2))->find();
            if($mybind)
            {
                $userParam['orderId'] = "HLBJS".get_timeHm();//订单号;
                $pdata['user_js_supply_id'] = $tdid; // 目前固定合利宝(1)，收益结算通道(表)
                # 获取银行卡
                $bankCode = M("bank")->where(array('bank_id'=>$myjsbank['bank_id']))->getField('hlb_bank_code');

                # 获取银行卡信息
                $bankAccountNo = trim($myjsbank['cart']);
                $bankAccountName = trim($myjsbank['nickname']);
                $biz = "B2C";

                $order = array(
                    'uid' => $this->uid,
                    'userId' => trim($userParam['userId']),
                    'orderId' => $userParam['orderId'],
                    'user_js_supply_id' => $tdid,
                    'amount' => $userParam['amount'] - ($user_settlement['sx_money']), // 到账金额
                    'feeType' => 'RECEIVER',   // PAYER:付款方收取手续费 ,  RECEIVER:收款方收取手续费
                    'summary' => "收益结算", // 提现备注
                    'js_card' => trim($userParam['js_card']),
                    'sx_money' => $user_settlement['sx_money'],
                    'bankCode' => $bankCode,
                    'bankAccountNo' => $bankAccountNo,
                    'bankAccountName' => $bankAccountName,
                    'biz' => $biz,
                    'bankUnionCode' => '', # 银行联行号 - 对公联行号必填
                    'tx_service' => ($user_settlement['sx_money']),
                    'js_money' => ($user_settlement['sx_money']),
                    'type' => 3 # 分销/利润结算
                );
//                echo json_encode($order);die;
                $res = R("Payapi/Api/ajPayJsCardDf",array($param,$order));  // 注释
                echo json_encode($res);die;
            }else{
                echo json_encode(array('code' => 400, 'msg' => '请先绑定结算卡!'));die;
            }
        }else{
            echo json_encode(array('code' => 400, 'msg' => '请先绑定结算卡!'));
            die;
        }
    }


    /***************************************************************************************************************************************
     *  ************************************************************************************************************************************
     * 统一快捷支付通道调用接口
     * Jeffery 于20180210重新整合
     ************************************************************************************************************************************
     *  ************************************************************************************************************************************
     */

    public function jcTdPack()
    {
        /**/
        $tytd = intval($_REQUEST['tytd']); // 收款通道
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'tytd' => $_REQUEST['tytd']
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $tytd = M("user_pay_supply")->field('user_pay_supply_id,sform')->where(array('user_pay_supply_id'=>$tytd))->find();
        if($tytd) {
            $tytd['sform'] = $tytd['sform'] ? trim($tytd['sform']) : "NOT";
        }
        echo json_encode(array('code' => 200, 'msg' => '接口形式','sform'=>$tytd['sform']));
        die;
    }

    public function tdPack()
    {
        $td = intval($_REQUEST['td']);
        $tytd = intval($_REQUEST['tytd']); // 收款通道
        $bankid = intval($_REQUEST['bankid']);
        $price = trim($_REQUEST['price']);

        /**/
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'td' => $_REQUEST['td'],
            'price' => $_REQUEST['price'],
            'tytd' => $_REQUEST['tytd'],
            'bankid' => $_REQUEST['bankid']
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断


        if (!$td) {
            $d = array(
                'code' => 200,
                'msg' => '请选择支付类型'
            );
            echo json_encode($d);
            exit;
        }
        if (!$tytd) {
            $d = array(
                'code' => 200,
                'msg' => '请选择收款通道'
            );
            echo json_encode($d);
            exit;
        }
        if (!$price) {
            $d = array(
                'code' => 200,
                'msg' => '金额不存在'
            );
            echo json_encode($d);
            exit;
        }
        # 支付卡(信用卡)
        $myBank = M("mybank")->where(array('user_id' => $this->uid,'jq_status' => 3, 'status' => 1, 'type' => 2, 'mybank_id' => $bankid))->find();
        if (!$myBank) {
            $d = array(
                'code' => 200,
                'msg' => '!请先选择银行卡'
            );
            echo json_encode($d);
            exit;
        }
        # 获取默认的结算卡
        $myBankNormal = M("mybank")->where(array('user_id' => $this->uid,'jq_status'=>3, 'status' => 1, 'type' => 1, 'is_normal' => 1))->find();
        if (!$myBankNormal){
            $d = array(
                'code' => 200,
                'msg' => '请先绑定一张默认结算卡'
            );
            echo json_encode($d);
            exit;
        }
        # 银行卡信息
        $jbank = M("bank")->field('name,hlb_bank_code')->where(array('bank_id'=>$myBank['bank_id']))->find();
        # 手续费
        $pay_supply_id = M('user_pay_supply')->field('pay_supply_id,user_js_supply_id,yy_rate,tk_rate,pt_rate')->where(array('user_pay_supply_id'=>$tytd))->find();
        if(!$pay_supply_id){
            $d = array(
                'code' => 200,
                'msg' => '该通道正在维护中!'
            );
            echo json_encode($d);
            exit;
        }
        # 结算提现费
        $yyrate = M("user_js_supply")->where(array('user_js_supply_id'=>$pay_supply_id['user_js_supply_id']))->getField('yy_rate'); # 提现费
        if(!$yyrate)
        {
            $d = array(
                'code' => 200,
                'msg' => '该通道正在维护中!'
            );
            echo json_encode($d);
            exit;
        }
        $tranAmt = $price*100;
        $bankId = trim($jbank['hlb_bank_code']);
        $orderDesc = "o2o交易";
        $tranDate = date("Ymd");

        if ($tytd == '19'){
            $tranId = "XJF";
        }else if($tytd == '11'){
            $tranId = "ZFQP";
        }else if($tytd == '23')
        {
            $tranId = "SFQP";
        }else if($tytd == '24')
        {
            $tranId = "SFQP";
        }else if($tytd == '27')
        {
            $tranId = "ZNFQP";
        }

        $tranId = $tranId.get_timeHm();
        $tranTime = date("His");

        # 回调地址 - 线上地址
        $hostUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'];
        $hostUrl = "https://wallet.insoonto.com/";
        $returnURL = $hostUrl."Payapi/Payjy/zfPayReturn";

        $trustBackUrl =  $returnURL; // 后台地址  (修改订单状态的url
        $trustFrontUrl = $hostUrl."Payapi/Payjy/zfPayWap"; // 前台支付成功返回页面  (即支付成功后返回成功页面的url)

        # 个人支付结算卡信息
        $payCardNo = trim($myBank['cart']);
        $cvn = trim($myBank['cw_two']);
        $expriy = trim($myBank['useful']);
        $payMobile = trim($myBank['mobile']);
        $idCard = trim($myBankNormal['idcard']);
        $mobile = trim($myBankNormal['mobile']);
        $accountNo = trim($myBankNormal['cart']);
        $accountName = trim($myBankNormal['nickname']);
        $accountBank = trim($jbank['name']);

        # 支付交易通道 (对接自动结算通道，目前固定DOBANK，结算到银行卡)
        $yy_rate = M("user_pay_supply")->where(array('user_pay_supply_id'=>$tytd))->getField('yy_rate');

        # 新增结算通道信息 - 入账金额
        $pay_supply_id = M('user_pay_supply')->where(array('user_pay_supply_id'=>$tytd))->getField('pay_supply_id');
        $payrate = M('user_pay_supply')->where(array('user_pay_supply_id'=>$tytd))->getField('yy_rate');
        $rzprice = 0;
        if($pay_supply_id)
        {
            $yyrate = M("user_js_supply")->where(array('sid'=>$pay_supply_id))->getField('yy_rate'); # 提现费
            $rzprice = trim($price) - (trim($price)*$payrate/1000) - $yyrate;
        }else{
            $d = array(
                'code' => 200,
                'msg' => '参数不全!'
            );
            echo json_encode($d);
            exit;
        }

        # 手续提现费率
        $service_charge = $price*($yy_rate/1000);
        $sumer_amt = $yyrate*100; // 结算元/笔

        # 扣取费率+手续费
        $sumer_fee = $payrate/10; // 0.38或者？


        $tytd = M("user_pay_supply")->field('user_pay_supply_id,sform')->where(array('user_pay_supply_id'=>$tytd))->find();
        if($tytd)
        {
            $tytd['sform'] = $tytd['sform']?trim($tytd['sform']):"NOT";


            if($tytd['sform'] == "API")
            {

                if($tytd['user_pay_supply_id'] == '1')  # 合利宝无积分
                {

                }
                if($tytd['user_pay_supply_id'] == '5')  # 合利宝有积分/1
                {

                }
                if($tytd['user_pay_supply_id'] == '10')  # 合利宝有积分/2
                {

                }
                if($tytd['user_pay_supply_id'] == '23')  # 上福无积分通道
                {
                    $this->addTdOrder($tytd['user_pay_supply_id'],$service_charge,$tranId,$tranDate,$tranTime,$price,$payCardNo,$payMobile,$sumer_fee,$rzprice,$yyrate,$tytd['sform']);
                }
                if($tytd['user_pay_supply_id'] == '24')  # 上福有积分通道无短信
                {
                    $this->addTdOrder($tytd['user_pay_supply_id'],$service_charge,$tranId,$tranDate,$tranTime,$price,$payCardNo,$payMobile,$sumer_fee,$rzprice,$yyrate,$tytd['sform']);
                }
                if($tytd['user_pay_supply_id'] == '25')  # 上福有积分通道有短信
                {
                    $this->addTdOrder($tytd['user_pay_supply_id'],$service_charge,$tranId,$tranDate,$tranTime,$price,$payCardNo,$payMobile,$sumer_fee,$rzprice,$yyrate,$tytd['sform']);
                }

            }else if($tytd['sform'] == "H5")
            {

                if($tytd['user_pay_supply_id'] == '19')  # 星洁有积分通道
                {
                    R("Payapi/Paytd/xjTdPay",array($tytd['user_pay_supply_id'],$service_charge,$tranId,$tranDate,$tranTime,$price,$payCardNo,$payMobile,$sumer_fee,$rzprice,$yyrate,$tranAmt,$myBank,$myBankNormal, $tytd['sform']));
                }

                if($tytd['user_pay_supply_id'] == '11')  # 智付无积分通道
                {
                    R("Payapi/Paytd/zfTdPay",array($tranDate,$tranId,$tranTime,$tranAmt,$bankId,$orderDesc,$trustBackUrl,$trustFrontUrl,$payCardNo,$cvn,$expriy,$payMobile,$idCard,$mobile,$accountNo,$accountName,$accountBank,$sumer_fee,$sumer_amt,$tytd['user_pay_supply_id'],$service_charge,$price,$rzprice,$yyrate, $tytd['sform']));
                }

                if($tytd['user_pay_supply_id'] == '27')  # 智能付有积分通道
                {
                    R("Payapi/Paytd/znfTdPay",array($tytd['user_pay_supply_id'],$service_charge,$tranId,$tranDate,$tranTime,$price,$payCardNo,$payMobile,$sumer_fee,$rzprice,$yyrate,$tranAmt,$myBank,$myBankNormal, $tytd['sform'],$cvn,$expriy));
                }

            }else{
                echo json_encode(array('code' => 400, 'msg' => '暂不支持该形式!'));
                die;
            }
        }else{
            echo json_encode(array('code' => 400, 'msg' => '无通道可用!'));
            die;
        }
    }



    # 发送验证码
    public function sendTd()
    {
        $td = intval($_REQUEST['td']);
        $tytd = intval($_REQUEST['tytd']); // 收款通道
        $bankid = intval($_REQUEST['bankid']);
        $price = trim($_REQUEST['price']);
        $tranId = trim($_REQUEST['tranId']);
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'td' => $_REQUEST['td'],
            'tytd' => $_REQUEST['tytd'],
            'bankid' => $_REQUEST['bankid'],
            'price' => $_REQUEST['price'],
            'tranId' => $_REQUEST['tranId']
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
        if (!$td) {
            $d = array(
                'code' => 1100,
                'msg' => '请选择支付类型'
            );
            echo json_encode($d);
            exit;
        }
        if (!$tytd) {
            $d = array(
                'code' => 1100,
                'msg' => '请选择收款通道'
            );
            echo json_encode($d);
            exit;
        }
        if (!$price) {
            $d = array(
                'code' => 1100,
                'msg' => '金额不存在'
            );
            echo json_encode($d);
            exit;
        }

        /**
         * 按照200，400返回信息
         */
        $mobile = M("mybank")->where(array('mybank_id'=>$bankid))->getField('mobile');
        if($tytd == '23') # 上福无积分 ( 有短信 )
        {
            $res = R("Payapi/GatePay/sendcode",array($this->uid,$price,$bankid,$tytd,$tranId));
        }else if($tytd == '25')# 上福有积分 ( 有短信 )
        {
            $res = R("Payapi/GatePay/sendcode",array($this->uid,$price,$bankid,$tytd,$tranId));
        }else if($tytd == "24") # 上福有积分( 无短信 )
        {
            $info = M('mobleyzm')->where(array('phone'=>$mobile,'type'=>3))->find();
            $code = rand(100000,999999);
            $msg =  "验证码：".$code."(为了您的账户安全，请勿告知他人，请在页面尽快完成验证)客服4008-272-278";
            R("Func/Func/send_mobile",array($mobile,$msg));
            if($info){
                $data['mobleyzm_id'] = $info['mobleyzm_id'];
                $data['phone']=$mobile;
                $data['code']=$code;
                $data['c_t']=time();
                $data['type']=8;
                $res = M('mobleyzm')->save($data);
                if ($res) {
                    echo json_encode(array('code'=>200,'msg'=>'验证码发送成功!','code'=>$data['code']));die;
                }else{
                    echo json_encode(array('code'=>400,'msg'=>'验证码发送失败!'));die;
                }
            }else{
                $data['phone']=$mobile;
                $data['code']=$code;
                $data['c_t']=time();
                $data['type']=8;
                $res = M('mobleyzm')->add($data);
                if ($res) {
                    echo json_encode(array('code'=>200,'msg'=>'验证码发送成功!','code'=>$data['code']));die;
                }else{
                    echo json_encode(array('code'=>400,'msg'=>'验证码发送失败!'));die;
                }
            }
        }
        else if($tytd == '10') # 合利宝有积分
        {

        }
        echo json_encode($res);
        exit;
    }




    # 确认支付
    public function submmmPay()
    {
        $td = intval($_REQUEST['td']);
        $tytd = intval($_REQUEST['tytd']); // 收款通道
        $bankid = intval($_REQUEST['bankid']);
        $price = trim($_REQUEST['price']);
        $tranId = trim($_REQUEST['tranId']);
        $code = trim($_REQUEST['code']);
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'td' => $_REQUEST['td'],
            'tytd' => $_REQUEST['tytd'],
            'bankid' => $_REQUEST['bankid'],
            'price' => $_REQUEST['price'],
            'tranId' => trim($_REQUEST['tranId']),
            'code' => $code
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
        if (!$td) {
            $d = array(
                'code' => 1100,
                'msg' => '请选择支付类型'
            );
            echo json_encode($d);
            exit;
        }
        if (!$tytd) {
            $d = array(
                'code' => 1100,
                'msg' => '请选择收款通道'
            );
            echo json_encode($d);
            exit;
        }
        if (!$price) {
            $d = array(
                'code' => 1100,
                'msg' => '金额不存在'
            );
            echo json_encode($d);
            exit;
        }

        /**
         * 按照200，400返回信息
         */

        if($tytd == '23') # 上福无积分
        {
            $res = R("Payapi/GatePay/addspay",array($this->uid,$price,$bankid,$code,$tytd,$tranId));
            if($res['code'] == '200') # 支付成功
            {

            }else if($res['code'] == '300') # 处理中
            {

            }else if($res['code'] == '400') # 支付失败
            {

            }else if($res['code'] == '500') # 未结算
            {

            }

        }
        if($tytd == '25') # 上福有积分，有短信
        {
            $res = R("Payapi/GatePay/addspay",array($this->uid,$price,$bankid,$code,$tytd,$tranId));
            if($res['code'] == '200') # 支付成功
            {

            }else if($res['code'] == '300') # 处理中
            {

            }else if($res['code'] == '400') # 支付失败
            {

            }else if($res['code'] == '500') # 未结算
            {

            }

        }
        elseif($tytd == '24') # 上福有积分
        {
            if (!$code) {
                $d = array(
                    'code' => 1100,
                    'msg' => '请选择收款通道'
                );
                echo json_encode($d);
                exit;
            }


            $res = R("Payapi/GatePay/addspay",array($this->uid,$price,$bankid,$code,$tytd,$tranId));
            if($res['code'] == '200') # 支付成功
            {

            }else if($res['code'] == '300') # 处理中
            {

            }else if($res['code'] == '400') # 支付失败
            {

            }else if($res['code'] == '500') # 未结算
            {

            }

        }else if($tytd == '10') # 合利宝有积分
        {

        }
        echo json_encode($res);
        exit;
    }

    # 下单
    private function addTdOrder($tytd,$service_charge,$tranId,$tranDate,$tranTime,$price,$payCardNo,$payMobile,$sumer_fee,$rzprice,$yyrate,$Sform)
    {
        $mdata['user_id'] = $this->uid;
        $mdata['goods_name'] = "O2O收款";
        $mdata['goods_type'] = "消费";
        $mdata['user_pay_supply_id'] = $tytd;
        $mdata['pay_name'] = "O2O交易";

        // 系统平台订单号
        $mdata['platform_ordersn'] = "200".date('His').get_timeHm();

        $mdata['service_charge'] = $service_charge;
        $mdata['pt_ordersn'] = $tranId;
        $mdata['timestamp'] = $tranDate.$tranTime;
        $mdata['sh_ordersn'] = "";
        $mdata['jy_status'] = 2;
        $mdata['pay_money'] = $price;
        $mdata['money'] = $price - $service_charge;
        $mdata['money_type_id'] = 11; // 快捷支付
        $mdata['t'] = time();
        $mdata['bank_cart'] = $payCardNo;
        $mdata['phone'] = $payMobile;
        $mdata['shop_rate'] = $sumer_fee;
        $pay_supply_type_id = M("sktype")->where(array('user_id'=>$this->uid))->getField('pay_supply_type_id');
        $mdata['pay_supply_type_id'] = trim($pay_supply_type_id);
        if(M($this->table)->where(array('pt_ordersn'=>$tranId))->getField('pt_ordersn'))
        {
            $d = array(
                'code' => 400,
                'msg' => '此订单号已存在，勿重复!'
            );
            echo json_encode($d);
            exit;
        }
        $add = M($this->table)->add($mdata);
        if($add)
        {
            // 生成结算订单记录
            $jsdata['user_id'] = trim($this->uid);
            $jsdata['relation_order'] = "";
            $jsdata['sh_ordersn'] = "";
            $user_js_supply_id = M("user_pay_supply")->where(array('user_pay_supply_id'=>$tytd))->getField('user_js_supply_id');
            # 结算后的金额 / 结算前的金额
            $beforemoney = M("user")->where(array('user_id'=> trim($this->uid)))->getField('money');
            $jsdata['user_js_supply_id'] = intval($user_js_supply_id);
            $jsdata['js_money'] = trim($rzprice);  // 入账金额
            $jsdata['tx_service'] = $yyrate; // 提现费
            $jsdata['sx_money'] = $service_charge;
            $jsdata['after_money'] = $beforemoney+$rzprice;
            $jsdata['before'] = $beforemoney;
            $jsdata['js_ordersn'] = "";
            $jsdata['serial_num'] = trim($tranId);
            $cart = M("mybank")->where(array('type'=>1,'is_normal'=>1,'user_id'=>$this->uid))->getField('cart');
            $jsdata['js_card'] = trim($cart); # 默认结算卡
            $jsdata['js_status'] = 1;
            $jsdata['t'] = time();
            $jsdata['js_type'] = 1;
            $jsdata['type'] = 2;
            $jsdata['is_duixiang'] = "";
            $jsdata['order_status'] = "";
            $jsdata['order_msg'] = "";
            $jsdata['pt_ordersn'] = trim($tranId);
            $jsdata['rz_money'] = trim($rzprice);
            M($this->jstable)->add($jsdata);
        }
        $d = array(
            'code' => 200,
            'msg' => '下单成功返回参数',
            'tranId' => $tranId,
            'Sform' => $Sform,
            'res' => array()
        );
        echo json_encode($d);
        exit;

    }

    # 订单查询
    public function orderTdCheck()
    {
        $tytd = intval($_REQUEST['tytd']); // 收款通道
        //#$tranId = trim($_REQUEST['tranId']);
            if (!$this->sign) {
                echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
                die;
            }
            $array = array(
                'uid' => $this->uid,
                'tytd' => $_REQUEST['tytd'],
                'tranId' => $_REQUEST['tranId']
            );
            $this->parem = array_merge($this->parem, $array);
            $msg = R('Func/Func/getKey', array($this->parem));//返回加密
            if ($this->sign !== $msg) {
                echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
                die;
            }
            R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
            $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        if($tytd == '19') # 星洁
        {

        }
        else if($tytd == '11') # 智付
        {
            # R("Payapi/Api/PaySetLog",array("./PayLog","PayReturn_zfPayWap__",'----智付支付前台通知参数----'.json_encode($_REQUEST)));
        }


        $getJson = $_REQUEST;
        if(!$getJson['tranId'])
        {
            $d = array(
                'code' => 200,
                'msg' => "请带上参数!"
            );
            echo json_encode($d);
            exit;
        }
        $tranDate = substr($getJson['tranId'],4,8);
        $tranTime = substr($getJson['tranId'],13,6);
        # checkorder
        $pdata = array(
            'tranDate' => $tranDate,
            'tranId' => trim($getJson['tranId']),
            'tranTime' => $tranTime,
            'origTranDate' => $tranDate,
            'origTranId' =>  trim($getJson['tranId'])
        );

        # 查询是否交易成功
        $jy_status = M($this->table)->where(array('pt_ordersn'=>trim($getJson['tranId'])))->getField('jy_status');
        if($jy_status != 1)
        {
            $d = array(
                'code' => 200,
                'msg' => '订单处理中'
            );
            echo json_encode($d);
            exit;
        }else{
            $msg = "支付成功";
            $d = array(
                'code' => 400,
                'msg' => $msg
            );
            # 执行定时器任务
//            R("Func/Time/add",array(trim($getJson['tranId']),$this->table,$this->jstable));
        }
        echo json_encode($d);
        exit;

    }


    # 关闭当前通道
    public function tdClose()
    {
        /*
        $w = date("w");
        $td = M("user_pay_supply")->select();
        foreach ($td as $k => $v)
        {
            if($v['user_pay_supply_id'] == 11)
            {
                if($w == 0) # 周日不显示
                {
                    M("user_pay_supply")->where(array('user_pay_supply_id'=>$v['user_pay_supply_id']))->save(array('is_return'=>1,'status'=>0));
                }else{
                    M("user_pay_supply")->where(array('user_pay_supply_id'=>$v['user_pay_supply_id']))->save(array('is_return'=>2,'status'=>1));
                }
            }
        }
        */
    }

    # 转json
    public function jsoned()
    {
        echo json_encode("上下级即时沟通beta版本上线");
    }

    # 开通道
    public function openTd()
    {
        $status = $_REQUEST['status'];
        if($status == 1)
        {
            $d['status'] = 1;
            $d['is_return'] = 2;
        }else if($status == 0){
            $d['status'] = 0;
            $d['is_return'] = 1;
        }
        M("user_pay_supply")->where(array('user_pay_supply_id'=>trim($_REQUEST['user_pay_supply_id'])))->save($d);
    }
}