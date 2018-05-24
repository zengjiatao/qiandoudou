<?php
/**
 * Created by 银迅通支付网络公司.
 * User: Jeffery
 * Date: 2017/12/19
 * Time: 16:43
 * 银联快捷支付 支付交易接口
 */

namespace Payapi\Controller;


class PaykjController extends BaseController
{
    public $uid;
    public $table;
    public $jstable;
    public function _initialize()
    {
        parent::_initialize();
        $this->uid = trim($_REQUEST['uid']);

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

        /**/
        # 加密控制
        if(ACTION_NAME != "kjPayReturn" && ACTION_NAME != "globalCurlPost")
        {
            //        if (empty($this->uid)) {
            //            $d = array(
            //                'code' => 200,
            //                'msg' => '用户ID不存在'
            //            );
            //            echo json_encode($d);
            //            exit;
            //        }
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

########## 5.4.快捷支付（API直联，快捷B）##########
    ### 5.4.1.快捷预下单（T01017）
    public function submitPay()
    {
        $td = intval($_REQUEST['td']);
        $tytd = intval($_REQUEST['tytd']); // 收款通道
        $bankid = intval($_REQUEST['bankid']);
        $price = $_REQUEST['price'];
        /**/
            if (!$this->sign) {
                echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
                die;
            }
            $array = array(
                'uid' => $this->uid,
                'td' => $_REQUEST['td'],
                'tytd' => $_REQUEST['tytd'],
                'price' => $_REQUEST['price'],
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
        if (!$myBankNormal) {
            $d = array(
                'code' => 200,
                'msg' => '请先绑定一张默认结算卡'
            );
            echo json_encode($d);
            exit;
        }
        # 银行卡信息
        $jbank = M("bank")->field('name,hlb_bank_code')->where(array('bank_id'=>$myBank['bank_id']))->find();
        // if utype
        $ifutype = M("user")->where(array('user_id'=>$this->uid))->getField('utype');

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


        /*
        if($ifutype!=20)
        {
            $sxmoney = ($pay_supply_id['yy_rate'] - $pay_supply_id['tk_rate'])/1000;
        }else{
            $sxmoney = ($pay_supply_id['tk_rate'] - $pay_supply_id['pt_rate'])/1000;
        }
        */

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

        $payerBankCode = trim($jbank['hlb_bank_code']);
        $productName = "商品交易";
        $orderDesc = "o2o交易";

        # 回调地址 - 线上地址
        $hostUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'];
        $hostUrl = "https://wallet.insoonto.com/";
        $bgUrl = $hostUrl."Payapi/Paykj/kjPayReturn";

        # 支付卡
        $payCardNo = trim($myBank['cart']);
        $cvn = trim($myBank['cw_two']);
        $expriy = trim($myBank['useful']);
        $payMobile = trim($myBank['mobile']);

        # 默认结算卡
        $idCard = trim($myBankNormal['idcard']);
        $mobile = trim($myBankNormal['mobile']);
        $accountNo = trim($myBankNormal['cart']);
        $accountName = trim($myBankNormal['nickname']);
        $accountBank = trim($jbank['name']);
        $jbankNormal = M("bank")->field('name,hlb_bank_code')->where(array('bank_id'=>$myBankNormal['bank_id']))->find();
        $normalBankCode = $jbankNormal['hlb_bank_code'];

        # 支付交易通道 (对接自动结算通道，目前固定DOBANK，结算到银行卡)
        $yy_rate = M("user_pay_supply")->where(array('user_pay_supply_id'=>$tytd))->getField('yy_rate');

        # 新增结算通道信息 - 入账金额
        $user_js_supply_id = M('user_pay_supply')->where(array('user_pay_supply_id'=>$tytd))->getField('user_js_supply_id');
        $payrate = M('user_pay_supply')->where(array('user_pay_supply_id'=>$tytd))->getField('yy_rate');
        $rzprice = 0;
        if($user_js_supply_id)
        {
            $yyrate = M("user_js_supply")->where(array('user_js_supply_id'=>$user_js_supply_id))->getField('yy_rate'); # 提现费
            $rzprice = trim($price) - (trim($price)*$payrate/1000) - $yyrate;
        }else{
            $d = array(
                'code' => 200,
                'msg' => '参数不全!'
            );
            echo json_encode($d);
            exit;
        }
        $extraFee = $yyrate; // 结算元/笔
        $transactionId = "KJF7".get_timeHm();
        $timestamp = date("mdHis").mt_rand(10,88);
        # 手续提现费率
        $service_charge = $price*($yy_rate/1000);
        # 同一个手机号限制一小时发6次,两次间隔必须大于一分钟,支付成功后会重置次数的

        $pdata = array(
            'reserve1' => '',
            'reserveJson' => '',
            'transactionId' => $transactionId, // 商户订单号
            'orderAmount' => $price,  // 商户订单金额
            'productName' => $productName, // 商品名称
            'orderDesc' => $orderDesc,  // 订单描述
            'bgUrl' => $bgUrl,  // 服务器接受支付结果的后台地址
            'payerBankCode' => $payerBankCode,  // 付款银行编码
            'payerAcc' => $payCardNo,   // 付款方银行卡号
            'payerName' => $accountName, //  付款方名称
            'payerPhoneNo' => $payMobile, // 手机号
            'cardType' => 'CC', // 付款方卡类型，借记：DC；贷记：CC
            'expiryDate' => $expriy, //  贷记卡有效期（YYMM）
            'cvv2' => $cvn, //  CVV2码
            'payerIdNum' => $idCard, //  付款方身份证号
            'privateFlag' => "C", //  对公：B；对私：C
            'payeeBankCode' => $normalBankCode, // 收款方银行编码
            'payeeAcc' => $accountNo, //  收款方银行卡号
            'payeePhoneNo' => $mobile, //  手机号
            'feeRate' => $payrate/10, //  费率
            'maxFee' => '', //  封顶手续费 (以元为单位，格式参考商户订单金额写法；该字段为空时，根据feeRate来计算手续费；该字段不为空时：当orderAmount*feeRate/100<maxFee时按feeRate计算手续费；当orderAmount*feeRate/100>=maxFee时按maxFee计算手续费)
            'extraFee' => $extraFee, //  额外手续费
            'ext' => '' //  扩展字段
        );
        import('Vendor.payKj.kjPay');
        $config = C("KJFPAY");
        $KJPay = new \kjPay($config);
        $res = $KJPay->submitPay($pdata);

        $ifOrderTime = M("order_time")->where(array('user_id'=>$this->uid,'user_pay_supply_id'=>$tytd))->getField('t');


        /*
            # 在一小时之内，不能超出6次。
            $nowTTime = strtotime("+1 hour");
            if($ifOrderTime['t']>= && $ifOrderTime['t']>=)
            {

            }
        */

        $orderTime['t'] = time();
        $orderTime['user_id'] = $this->uid;
        $orderTime['pt_ordersn'] = $res['data']['refTxnId'];
        $orderTime['user_pay_supply_id'] = $tytd;
        # 记录时间
        M("order_time")->add($orderTime);

        echo json_encode($res);exit;
    }


    ### 银行卡信息
    public function mybankInfo()
    {
        $td = intval($_REQUEST['td']);
        $tytd = intval($_REQUEST['tytd']); // 收款通道
        $bankid = intval($_REQUEST['bankid']);
        $price = $_REQUEST['price'];
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'td' => $td,
            'tytd' => $tytd,
            'price' => $price,
            'bankid' => $bankid
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断


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

        $d = array(
            'code' => 400,
            'msg' => '银行卡信息',
            'data' => $myBank
        );
        echo json_encode($d);
        exit;
    }


    ### 5.4.1.1.订单请求（商户->支付平台）
    public function orderPay()
    {
        $td = intval($_REQUEST['td']);
        $tytd = intval($_REQUEST['tytd']); // 收款通道
        $bankid = intval($_REQUEST['bankid']);
        $price = $_REQUEST['price'];
        $refTxnId = trim($_REQUEST['refid']);
        $verificationCode = trim($_REQUEST['code']);
        /**/
            if (!$this->sign) {
                echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
                die;
            }
            $array = array(
                'uid' => $this->uid,
                'td' => $td,
                'tytd' => $tytd,
                'price' => $price,
                'bankid' => $bankid,
                'refid' => $refTxnId,
                'code' => $verificationCode
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
        if (!$myBankNormal) {
            $d = array(
                'code' => 200,
                'msg' => '请先绑定一张默认结算卡'
            );
            echo json_encode($d);
            exit;
        }
        # 银行卡信息
        $jbank = M("bank")->field('name,hlb_bank_code')->where(array('bank_id'=>$myBank['bank_id']))->find();
        // if utype
        $ifutype = M("user")->where(array('user_id'=>$this->uid))->getField('utype');

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


        /*
        if($ifutype!=20)
        {
            $sxmoney = ($pay_supply_id['yy_rate'] - $pay_supply_id['tk_rate'])/1000;
        }else{
            $sxmoney = ($pay_supply_id['tk_rate'] - $pay_supply_id['pt_rate'])/1000;
        }
        */

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

        $payerBankCode = trim($jbank['hlb_bank_code']);
        $productName = "商品交易";
        $orderDesc = "o2o交易";

        # 回调地址 - 线上地址
        $hostUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'];
        $hostUrl = "https://wallet.insoonto.com/";
        $bgUrl = $hostUrl."Payapi/Paykj/kjPayReturn";

        # 支付卡
        $payCardNo = trim($myBank['cart']);
        $cvn = trim($myBank['cw_two']);
        $expriy = trim($myBank['useful']);
        $payMobile = trim($myBank['mobile']);

        # 默认结算卡
        $idCard = trim($myBankNormal['idcard']);
        $mobile = trim($myBankNormal['mobile']);
        $accountNo = trim($myBankNormal['cart']);
        $accountName = trim($myBankNormal['nickname']);
        $accountBank = trim($jbank['name']);
        $jbankNormal = M("bank")->field('name,hlb_bank_code')->where(array('bank_id'=>$myBankNormal['bank_id']))->find();
        $normalBankCode = $jbankNormal['hlb_bank_code'];

        # 支付交易通道 (对接自动结算通道，目前固定DOBANK，结算到银行卡)
        $yy_rate = M("user_pay_supply")->where(array('user_pay_supply_id'=>$tytd))->getField('yy_rate');

        # 新增结算通道信息 - 入账金额
        $user_js_supply_id = M('user_pay_supply')->where(array('user_pay_supply_id'=>$tytd))->getField('user_js_supply_id');
        $payrate = M('user_pay_supply')->where(array('user_pay_supply_id'=>$tytd))->getField('yy_rate');
        $rzprice = 0;
        if($user_js_supply_id)
        {
            $yyrate = M("user_js_supply")->where(array('user_js_supply_id'=>$user_js_supply_id))->getField('yy_rate'); # 提现费
            $rzprice = trim($price) - (trim($price)*$payrate/1000) - $yyrate;
        }else{
            $d = array(
                'code' => 200,
                'msg' => '参数不全!'
            );
            echo json_encode($d);
            exit;
        }
        $extraFee = $yyrate; // 结算元/笔
        $transactionId = "KJF7".get_timeHm();
        $timestamp = date("HmdHis");
        # 手续提现费率
        $service_charge = $price*($yy_rate/1000);
        # 同一个手机号限制一小时发6次,两次间隔必须大于一分钟,支付成功后会重置次数的

        if (!$refTxnId) {
            $d = array(
                'code' => 200,
                'msg' => '该订单号不存在'
            );
            echo json_encode($d);
            exit;
        }

        if(!$verificationCode)
        {
            $d = array(
                'code' => 200,
                'msg' => '该验证码不存在'
            );
            echo json_encode($d);
            exit;
        }

        $transactionId = "KJF7".get_timeHm();
        $pdata = array(
            'transactionId' => $transactionId,
            'refTxnId' => $refTxnId,
            'verificationCode' => $verificationCode
        );

        # 保存订单
        $mdata['user_id'] = $this->uid;
        $mdata['goods_name'] = "O2O收款";
        $mdata['goods_type'] = "消费";
        $mdata['user_pay_supply_id'] = $tytd;
        $mdata['pay_name'] = "O2O交易";
        $mdata['service_charge'] = $service_charge;
        $mdata['pt_ordersn'] = $refTxnId;
        $mdata['timestamp'] = $timestamp;
        $mdata['sh_ordersn'] = "";
        $mdata['jy_status'] = 2;
        $mdata['pay_money'] = $price;
        $mdata['money'] = $price - $service_charge;
        $mdata['money_type_id'] = 11; // 快捷支付
        $mdata['t'] = time();
        $mdata['bank_cart'] = $payCardNo;
        $mdata['phone'] = $payMobile;
        $mdata['shop_rate'] =  $payrate/10;
        if(M($this->table)->where(array('pt_ordersn'=>$refTxnId))->getField('pt_ordersn'))
        {
            $d = array(
                'code' => 200,
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
            $jsdata['tx_service'] = $yyrate; // 提现费2元
            $jsdata['sx_money'] = $service_charge;
            $jsdata['after_money'] = $beforemoney+$rzprice;
            $jsdata['before'] = $beforemoney;
            $jsdata['js_ordersn'] = "";
            $jsdata['serial_num'] = "";
            $cart = M("mybank")->where(array('type'=>1,'is_normal'=>1,'user_id'=>$this->uid))->getField('cart');
            $jsdata['js_card'] = trim($cart); # 默认结算卡
            $jsdata['js_status'] = 1;
            $jsdata['t'] = time();
            $jsdata['js_type'] = 1;
            $jsdata['type'] = 2;
            $jsdata['is_duixiang'] = "";
            $jsdata['order_status'] = "";
            $jsdata['order_msg'] = "";
            $jsdata['pt_ordersn'] = trim($refTxnId);
            $jsdata['rz_money'] = trim($rzprice);
            M($this->jstable)->add($jsdata);
        }

        import('Vendor.payKj.kjPay');
        $config = C("KJFPAY");
        $KJPay = new \kjPay($config);
        $res = $KJPay->qrPay($pdata);
        echo json_encode($res);exit;
    }

    ### 5.4.3.后台异步通知（bgurl，支付平台->商户）
    public function kjPayReturn()
    {
        header("Content-type: text/html; charset=utf-8");
        $get = $_REQUEST;
        $publicBusinessContext = trim($get['businessContext']);
        import('Vendor.payKj.kjPay');
        $config = C("KJFPAY");
        $KJPay = new \kjPay($config);

        $res = $KJPay->decryptPost($publicBusinessContext,"T01018快捷支付（API直联，快捷B）快捷确认支付业务报文(解密)后台异步通知响应：");
//        {"cur":"CNY","dealId":"6a7ff8565e884d92b05065cc30f1b4c7","dealTime":"20180113145424","orderAmount":"500.00","payAmount":"500.00","payType":"1010","retCode":"RC0000","retRemark":"交易成功，代付成功","signData":"LFU3xx/blnMUIFUDNHL+i4ThCPj9CMZIjMRpScnqvI1idMgz7KuKCX91O1qxFRnx4v2MH7rG9vRNsKZbiFNj+CHXeWDLP4U/4TF2CzamPMQz+sBWnoABA1KKV0EY+HTJ+5WK9BPletzYeUJ6Ypl13PPa+HfYnMPZW786d6xU7gE=","transStatus":"1","transactionId":"KJF7011314530119"}

        $pt_ordersn = trim($res['transactionId']);

        R("Payapi/Api/PaySetLog",array("./PayLog","Api_decryptPost_","---".json_encode($get)."-----返回解密密文结果：".json_encode($res)."---\r\n"));


        if($res['retCode'] == 'RC0000')  # 成功
        {
            R("Func/Fenxiao/fenxiaoByOrder",array($pt_ordersn));
            R("Func/Fenxiao/fenxiaoTkLevelOrder",array($pt_ordersn)); # 交易分 - 固定收益

            if($res['transStatus'] == "1") # 结算成功
            {
                $jsdata['js_status'] = 2;
                $jsdata['j_success_time'] = time()+30;

                R("Func/Fenxiao/jiesuanfenxiaoByOrder",array($pt_ordersn));
                R("Func/Fenxiao/fenxiaoTkJsLevelOrder",array($pt_ordersn)); # 结算分 - 固定收益

                $js_status = 2;
            }else if($res['transStatus'] == "2"){ # 结算失败
                $jsdata['js_status'] = 3;
                $jsdata['j_success_time'] = "";
                $js_status = 3;
            }else if($res['transStatus'] == "3") # 结算中
            {
                $jsdata['js_status'] = 1;
                $jsdata['j_success_time'] = "";
                $js_status = 1;
            }else{  # 入账成功，出款失败（此状态只有T0交易才会出现） --- 默认结算失败
                $jsdata['js_status'] = 3;
                $jsdata['j_success_time'] = "";
                $js_status = 3;
            }
        }else{
            # 失败
            $jsdata['js_status'] = 3;
            $jsdata['j_success_time'] = "";
            $js_status = 3;
        }

        $ifjsorder = M($this->jstable)->where(array('pt_ordersn'=>$pt_ordersn))->getField('pt_ordersn');
        if($ifjsorder)
        {
            M($this->table)->where(array('pt_ordersn'=>$pt_ordersn))->save(array('jy_status'=>1,'js_status'=>$js_status));
            M($this->jstable)->where(array('pt_ordersn'=>$pt_ordersn))->save($jsdata);
        }
        if($pt_ordersn)
        {
            /*
            # 产生分销/分润订单 - 交易
            R("Func/Fenxiao/fenxiaoByOrder",array($pt_ordersn));
            # 结算成功订单分销
            R("Func/Fenxiao/jiesuanfenxiaoByOrder",array($pt_ordersn));


            R("Func/Fenxiao/fenxiaoTkLevelOrder",array($pt_ordersn)); # 交易分 - 固定收益
            R("Func/Fenxiao/fenxiaoTkJsLevelOrder",array($pt_ordersn)); # 结算分 - 固定收益
            */
        }


        R("Payapi/Api/PaySetLog",array("./PayLog","Api_decryptPost_","---".json_encode($get)."---\r\n"));

        echo 'SUCCESS';exit;
    }

    # 解密方法
    public function kjfj()
    {
        header("Content-type: text/html; charset=utf-8");
//        ob_clean();
        $resultData = array(
//            'key' => 'v06b1G0UXOriPJcUGTcWz0o5GzuKOL28wbVx07iVD2g5v59s9selWBttzXUwp5nilm6Te\/l+skp5nNKaUhhivCwbrEloxSH5vD35ea2OKGSArJyYYgVWX6GM7VRUAFYIyr3nyxS4khW2ECh5R4t11IRBtKj60mZuSeClX4KjEt03q9IDxLU2bnbD5OBUrSBAjG2e3V7UahSsc2B0S7OzNDN+iItnpfQLyUIPPcpQWWoHkPd7s\/\/Chw5JeHe3AryK75nWpna\/eD9fOWC8IqUPzk5rwI5F0fWCeRCudixzUFb9WL5NcYo3ubjAJoBwYh9edLg24n9DcwkNI\/tKztxhy\/3EBeuYiBuYz7NwzD+oUreLVT2nqQHTKzy41NkuVLLEaNIDpgocZtWVPrpVchU0wK4Dj+ZVJ1lY+BP79FKP+w7yZtPgH97jGAYFBidbW9a6K5bHrl4VWMApJR9lIJyRbZyNk3wObKOycy89KZBLNnkn2s7JsGtacXjNSaDD5MHHNotaCJhEXKmJ6yVmtKesBsA\/BZc8p0IH9nice+O7XFH1Mb8zkclx+sWq2b6O9ZivAq6ZQdfrcwte\/Ucj3EmeqQ=='
            'key' => 'P9oMrtTN\/NZ6TbvPLxlCpF7NljeG\/xUAsjHooY\/K6cT5EzD7vuk5joE5BYVR9MS9MTUdCFZegukMQ6ajjJwqk1rzY\/aPPZsiURR\/0VanAaQ4maOadLAaSx1Q\/n1VSn5UJm73C\/sKzRXx64PgSUCOoLklhzVgF3WKir5COmzcF7HZS8VoqYtnfWam5Tdrn9MyZHnCMD4bTVK9xR0bB5hJP9Y7\/2Y6Vz72qqxN+cW0kZKzwniJ0Np7NNxYmo0PxzrHm7nq8vc+bd6OKhPrqK9IyR3NQ2cNvesVe9iQNw8JNW5QsgKxsnt+TttZnG3hP8sOWQ\/RZynTwkojO7lQC4RAicVz01yphlZPO24BEGUC6rQJuDTBZRyVK7+HOQp+k1OruUIZoT4fPpXJaG1tVligqomuypKaGIytsdnCqgr9wFJNKmD6DwEPMXUvJF\/L2C5CRV2wzLUvMkdQKH\/ozYxVt\/TbTHtZEz1+icXKlqp7TVa2DPMbCL\/yuYvm8hwqvztX1LLAddi\/omQOYCvgDZlF6w9g3EWQkAQdmv8JXp5MKhNiFBqtWJcoF1+TQ5qZuzq83NkLQnGAKVSbbzj+oKGY+w=='
        );
        import('Vendor.payKj.HttpClient');
        $http = new \HttpClient();
//        $resultData = $http->quickPost("http://192.168.199.31:8080/OnlineDayDemo/KJFJServer", $resultData); # 加密之后的报文信息( 应答密文 )
        $resultData = $http->quickPost("http://39.104.78.176:8080/zfPayDemo/KJFJServer", $resultData); # 加密之后的报文信息( 应答密文 )
        $resultCode = iconv("GB2312", "UTF-8", $resultData);

        $res = json_decode($resultCode,true);
        dump($res);
        R("Payapi/Api/PaySetLog",array("./PayLog","Api_kjfj_","---- KJFB_Return ----".$resultData."----\r\n"));
        die;
        echo $result;exit;
    }


    public function globalCurlPost($url,$data){ // 模拟提交数据函数

        $curl = curl_init(); // 启动一个CURL会话

        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在

        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转

        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer

        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包

        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环

        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回

        $tmpInfo = curl_exec($curl); // 执行操作

        if (curl_errno($curl)) {

            echo 'Errno'.curl_error($curl);//捕抓异常

        }

        curl_close($curl); // 关闭CURL会话

        return $tmpInfo; // 返回数据，json格式

    }

}