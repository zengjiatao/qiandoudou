<?php
/**
 * Jeffery于20180210
 */

# 银钱包对接接口 - 所有支付通道
namespace Payapi\Controller;

use Think\Controller;

class PaytdController extends Controller
{
    public $uid;
    public $parem;
    public $sign;
    public $table;
    public $jstable;
    private $yrturl = "http://payportal.dutiantech.com/service/pay/mercApply"; # 商户进件(智能付)
    # private $yrtpayurl = "http://payportal.dutiantech.com/service/pay/formOrder"; # 商户下单支付(智能付)
    private $yrtpayurl = "http://payportal.dutiantech.com/service/pay/formOrderV2"; # 商户下单支付(智能付)
    private $selectorderurl = "http://payportal.dutiantech.com/service/pay/orderInfo"; # 订单查询(智能付)
    public $isquest;

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
        $this->isquest = $_REQUEST;
    }

    # 星洁（有积分）通道
    public function xjTdPay($tytd = '', $service_charge = '', $tranId = '', $tranDate = '', $tranTime = '', $price = '', $payCardNo = '', $payMobile = '', $sumer_fee = '', $rzprice = '', $yyrate = '', $tranAmt = '', $myBank = '', $myBankNormal = '', $Sform)
    {
        //判断用户是否有商户进件
        $shanghu = M('xj_info_new')->where(array('user_id' => $this->uid, 'jifen_type' => 0))->find();
        if ($shanghu['mch_id']) {  //存在 商户号   先添加订单 再发起交易
            # 保存订单  第一步
            $mdata['user_id'] = $this->uid;
            $mdata['goods_name'] = "O2O收款";
            $mdata['goods_type'] = "消费";
            $mdata['user_pay_supply_id'] = $tytd;
            $mdata['pay_name'] = "O2O交易";
            $mdata['service_charge'] = $service_charge;
            $mdata['pt_ordersn'] = $tranId;
            // 系统平台订单号
            $mdata['platform_ordersn'] = "200" . date('His') . get_timeHm();
            $mdata['timestamp'] = $tranDate . $tranTime;
            $mdata['sh_ordersn'] = ""; //
            $mdata['jy_status'] = 2;
            $mdata['pay_money'] = $price;
            $mdata['money'] = $price - $service_charge;
            $mdata['money_type_id'] = 11; // 快捷支付
            $mdata['t'] = time();
            $mdata['bank_cart'] = $payCardNo;
            $mdata['phone'] = $payMobile;
            $mdata['shop_rate'] = $sumer_fee;
            $pay_supply_type_id = M("sktype")->where(array('user_id' => $this->uid))->getField('pay_supply_type_id');
            $mdata['pay_supply_type_id'] = trim($pay_supply_type_id);
            if (M($this->table)->where(array('pt_ordersn' => $tranId))->getField('pt_ordersn')) {
                $d = array(
                    'code' => 200,
                    'msg' => '此订单号已存在，勿重复!'
                );
                echo json_encode($d);
                exit;
            }
            $add = M($this->table)->add($mdata);
            if ($add) {
                // 生成结算订单记录
                $jsdata['user_id'] = trim($this->uid);
                $jsdata['relation_order'] = "";
                $jsdata['sh_ordersn'] = "";
                $user_js_supply_id = M("user_pay_supply")->where(array('user_pay_supply_id' => $tytd))->getField('user_js_supply_id');
                # 结算后的金额 / 结算前的金额
                $beforemoney = M("user")->where(array('user_id' => trim($this->uid)))->getField('money');
                $jsdata['user_js_supply_id'] = intval($user_js_supply_id);
                $jsdata['js_money'] = trim($rzprice);  // 入账金额
                $jsdata['tx_service'] = $yyrate; // 提现费2元
                $jsdata['sx_money'] = $service_charge;
                $jsdata['after_money'] = $beforemoney + $rzprice;
                $jsdata['before'] = $beforemoney;
                $jsdata['js_ordersn'] = "";
                $jsdata['serial_num'] = trim($tranId);
                $cart = M("mybank")->where(array('type' => 1, 'is_normal' => 1, 'user_id' => $this->uid))->getField('cart');
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
            //正式参数
            $PayCanShu = array(  //信用卡 $myBank
                'totalFee' => $tranAmt,  //单位 分 已经*100
                'agentOrderNo' => $tranId,
                'notifyUrl' => 'https://wallet.insoonto.com/index.php/Payapi/XkPay/xjPayReturn', //异步
                'mchId' => $shanghu['mch_id'], //商户号
                'returnUrl' => 'https://wallet.insoonto.com/index.php/Payapi/XkPay/xjPayUrl',  //同步
                'bankcard' => $myBank['cart'], //交易卡号(信用卡)
                'nick_name' => $myBank['nickname'],
                'idcard' => $myBank['idcard'],
                'phone' => $myBank['mobile'],
            );
            $res = R('Payapi/XkPay/placeOrder', array($PayCanShu));

            if ($res['isSuccess'] === true) {
                $d = array(
                    'code' => 200,
                    'msg' => '支付发起',
                    'tranId' => $tranId,
                    'Sform' => $Sform,
                    'res' => $res['data']['returnHtml']
                );
                echo json_encode($d);
                exit;
            } else {
                $d = array(
                    'code' => 400,
                    'msg' => trim($res['message']) ? trim($res['message']) : "通道繁忙，稍后再试！",
                );
                echo json_encode($d);
                exit;
            }
        } else { //不存在 需要进件
            //商户进件参数
            $sheng_id = M('province')->field('adcode,province')->where(array('provids' => $myBankNormal['province_id']))->find();
            if (!$sheng_id['adcode']) {
                $sheng_id['adcode'] = '430000';
            }
            $city_id = M('city')->field('adcode,city')->where(array('cityids' => $myBankNormal['city_id']))->find();
            if (!$city_id['adcode']) {
                $city_id['adcode'] = '431000';
            }

            $address = $sheng_id['province'] . $city_id['city']; //地址
            $fee0 = 4.8; //费率  单位:千分
            $d0fee = 200; //提现费
            $pointsType = 0;// 0带积分
            $ShJj = array(
                'bankcard' => $myBankNormal['cart'],
                'nick_name' => $myBankNormal['nickname'],
                'idcard' => $myBankNormal['idcard'],
                'phone' => $myBankNormal['mobile'],
                'address' => $address,
                'provinceCode' => $sheng_id['adcode'],
                'cityCode' => $city_id['adcode'],
                'fee0' => $fee0,
                'd0fee' => $d0fee,
                'pointsType' => $pointsType,
            );
            $ShJjInfo = R('Payapi/XkPay/shanghujj', array($ShJj));
            if ($ShJjInfo['isSuccess'] === true) { //进件成功   //发起交易
                //保存商户信息
                $addShanghu['name'] = $myBankNormal['nickname'];
                $addShanghu['idcard'] = $myBankNormal['idcard'];
                $addShanghu['bankcard'] = $myBankNormal['cart'];
                $addShanghu['phone'] = $myBankNormal['mobile'];
                $addShanghu['user_id'] = $this->uid;
                $addShanghu['mch_id'] = $ShJjInfo['data'];
                $addShanghu['jifen_type'] = $pointsType;
                $addShanghu['t'] = time();
                $isSh = M('xj_info_new')->add($addShanghu);
                if ($isSh) { //发起交易
                    # 保存订单  第一步
                    $mdata['user_id'] = $this->uid;
                    $mdata['goods_name'] = "O2O收款";
                    $mdata['goods_type'] = "消费";
                    $mdata['user_pay_supply_id'] = $tytd;
                    $mdata['pay_name'] = "O2O交易";
                    $mdata['service_charge'] = $service_charge;
                    $mdata['pt_ordersn'] = $tranId;

                    // 系统平台订单号
                    $mdata['platform_ordersn'] = "200" . date('His') . get_timeHm();

                    $mdata['timestamp'] = $tranDate . $tranTime;
                    $mdata['sh_ordersn'] = "";//
                    $mdata['jy_status'] = 2;
                    $mdata['pay_money'] = $price;
                    $mdata['money'] = $price - $service_charge;
                    $mdata['money_type_id'] = 11; // 快捷支付
                    $mdata['t'] = time();
                    $mdata['bank_cart'] = $payCardNo;
                    $mdata['phone'] = $payMobile;
                    $mdata['shop_rate'] = $sumer_fee;
                    $pay_supply_type_id = M("sktype")->where(array('user_id' => $this->uid))->getField('pay_supply_type_id');
                    $mdata['pay_supply_type_id'] = trim($pay_supply_type_id);
                    if (M($this->table)->where(array('pt_ordersn' => $tranId))->getField('pt_ordersn')) {
                        $d = array(
                            'code' => 400,
                            'msg' => '此订单号已存在，勿重复!'
                        );
                        echo json_encode($d);
                        exit;
                    }
                    $add = M($this->table)->add($mdata);
                    if ($add) {
                        // 生成结算订单记录
                        $jsdata['user_id'] = trim($this->uid);
                        $jsdata['relation_order'] = "";
                        $jsdata['sh_ordersn'] = "";
                        $user_js_supply_id = M("user_pay_supply")->where(array('user_pay_supply_id' => $tytd))->getField('user_js_supply_id');
                        # 结算后的金额 / 结算前的金额
                        $beforemoney = M("user")->where(array('user_id' => trim($this->uid)))->getField('money');
                        $jsdata['user_js_supply_id'] = intval($user_js_supply_id);
                        $jsdata['js_money'] = trim($rzprice);  // 入账金额
                        $jsdata['tx_service'] = $yyrate; // 提现费2元
                        $jsdata['sx_money'] = $service_charge;
                        $jsdata['after_money'] = $beforemoney + $rzprice;
                        $jsdata['before'] = $beforemoney;
                        $jsdata['js_ordersn'] = "";
                        $jsdata['serial_num'] = trim($tranId);
                        $cart = M("mybank")->where(array('type' => 1, 'is_normal' => 1, 'user_id' => $this->uid))->getField('cart');
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
                    //正式参数
                    $PayCanShu = array(  //信用卡 $myBank
                        'totalFee' => $tranAmt,  //单位 分 已经*100
                        'agentOrderNo' => $tranId,
                        'notifyUrl' => 'https://wallet.insoonto.com/index.php/Payapi/XkPay/xjPayReturn', //异步
                        'mchId' => $ShJjInfo['data'], //商户号
                        'returnUrl' => 'https://wallet.insoonto.com/index.php/Payapi/XkPay/xjPayUrl',  //同步
                        'bankcard' => $myBank['cart'], //交易卡号(信用卡)
                        'nick_name' => $myBank['nickname'],
                        'idcard' => $myBank['idcard'],
                        'phone' => $myBank['mobile'],
                    );
                    $res = R('Payapi/XkPay/placeOrder', array($PayCanShu));
                    if ($res['isSuccess'] === true) {
                        $d = array(
                            'code' => 200,
                            'msg' => '支付发起',
                            'tranId' => $tranId,
                            'Sform' => $Sform,
                            'res' => $res['data']['returnHtml']
                        );
                        echo json_encode($d);
                        exit;
                    } else {
                        $d = array(
                            'code' => 400,
                            'msg' => trim($res['message']) ? trim($res['message']) : "通道繁忙，稍后再试！",
                        );
                        echo json_encode($d);
                        exit;
                    }
                } else {
                    echo json_encode(array('code' => 3449, 'msg' => '商户信息异常:' . $ShJjInfo["message"]));
                    die;
                }
            } else {
                echo json_encode(array('code' => 3443, 'msg' => $ShJjInfo['message']));
                die;
            }
        }
    }


    # 智付（无积分）通道
    public function zfTdPay($tranDate, $tranId, $tranTime, $tranAmt, $bankId, $orderDesc, $trustBackUrl, $trustFrontUrl, $payCardNo, $cvn, $expriy, $payMobile, $idCard, $mobile, $accountNo, $accountName, $accountBank, $sumer_fee, $sumer_amt, $tytd, $service_charge, $price, $rzprice, $yyrate, $Sform)
    {
        $pdata = array(
            'tranDate' => $tranDate,
            'tranId' => $tranId, // 交易订单号
            'tranTime' => $tranTime,  // 时间
            'tranAmt' => $tranAmt, // 金额
            'bankId' => $bankId, // 银行编号
            'orderDesc' => $orderDesc, // 描述
            'trustBackUrl' => $trustBackUrl, // 后台通知地址 - 即修改订单状态的url
            'trustFrontUrl' => $trustFrontUrl, // 前台通知地址 - 即支付成功后返回成功页面的url
            'payCardNo' => $payCardNo, // 支付卡号，即消费卡号，必须和收款卡号属于同一个人
            'cvn' => $cvn, // 贷记卡背后 3 位验证码
            'expriy' => $expriy, // 贷记卡有效期，格式：MMYY，例例如 0120
            'payMobile' => $payMobile, // 支付手机号
            'idCard' => $idCard, // 收款人身份证
            'mobile' => $mobile, // 收款人手机号
            'accountNo' => $accountNo, // 收款卡号
            'accountName' => $accountName, // 收款账户名
            'accountBank' => $accountBank, // 收款行名称
            'sumer_fee' => $sumer_fee, // 持卡人费率
            'sumer_amt' => $sumer_amt // D0代付手续费(单位分) 200
        );
//        R("Payapi/Api/PaySetLog",array("./PayLog","Api_ajZhiFu_","---- 智付发送请求测试----".json_encode($pdata)."----\r\n"));
        # 保存订单
        $mdata['user_id'] = $this->uid;
        $mdata['goods_name'] = "O2O收款";
        $mdata['goods_type'] = "消费";
        $mdata['user_pay_supply_id'] = $tytd;
        $mdata['pay_name'] = "O2O交易";
        $mdata['service_charge'] = $service_charge;
        $mdata['pt_ordersn'] = $tranId;

        // 系统平台订单号
        $mdata['platform_ordersn'] = "200" . date('His') . get_timeHm();

        $mdata['timestamp'] = $tranDate . $tranTime;
        $mdata['sh_ordersn'] = "";
        $mdata['jy_status'] = 2;
        $mdata['pay_money'] = $price;
        $mdata['money'] = $price - $service_charge;
        $mdata['money_type_id'] = 11; // 快捷支付
        $mdata['t'] = time();
        $mdata['bank_cart'] = $payCardNo;
        $mdata['phone'] = $payMobile;
        $mdata['shop_rate'] = $sumer_fee;
        $pay_supply_type_id = M("sktype")->where(array('user_id' => $this->uid))->getField('pay_supply_type_id');
        $mdata['pay_supply_type_id'] = trim($pay_supply_type_id);
        /*
         * 转正式阶段
         * */

        if (M($this->table)->where(array('pt_ordersn' => $tranId))->getField('pt_ordersn')) {
            $d = array(
                'code' => 400,
                'msg' => '此订单号已存在，勿重复!'
            );
            echo json_encode($d);
            exit;
        }
        $add = M($this->table)->add($mdata);
        if ($add) {
            // 生成结算订单记录
            $jsdata['user_id'] = trim($this->uid);
            $jsdata['relation_order'] = "";
            $jsdata['sh_ordersn'] = "";
            $user_js_supply_id = M("user_pay_supply")->where(array('user_pay_supply_id' => $tytd))->getField('user_js_supply_id');
            # 结算后的金额 / 结算前的金额
            $beforemoney = M("user")->where(array('user_id' => trim($this->uid)))->getField('money');
            $jsdata['user_js_supply_id'] = intval($user_js_supply_id);
            $jsdata['js_money'] = trim($rzprice);  // 入账金额
            $jsdata['tx_service'] = $yyrate; // 提现费2元
            $jsdata['sx_money'] = $service_charge;
            $jsdata['after_money'] = $beforemoney + $rzprice;
            $jsdata['before'] = $beforemoney;
            $jsdata['js_ordersn'] = "";
            $jsdata['serial_num'] = trim($tranId);
            $cart = M("mybank")->where(array('type' => 1, 'is_normal' => 1, 'user_id' => $this->uid))->getField('cart');
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

        import('Vendor.payZhiFu.zfPay');
        $config = C("ZHIFUPAY");
        $ZfPay = new \zfPay($config);
        $res = $ZfPay->submitPay($pdata);
        $d = array(
            'code' => 200,
            'msg' => '支付发起',
            'tranId' => $tranId,
            'Sform' => $Sform,
            'res' => $res
        );
        echo json_encode($d);
        exit;
    }


    # 智能付通道

    ## 提交订单
    public function znfTdPay($tytd = '', $service_charge = '', $tranId = '', $tranDate = '', $tranTime = '', $price = '', $payCardNo = '', $payMobile = '', $sumer_fee = '', $rzprice = '', $yyrate = '', $tranAmt = '', $myBank = '', $myBankNormal, $Sform, $cvn = "", $expriy = "")
    {
        $sh = $this->znfTdPaySj();
        $MERC_CD = $sh['merch_id'];
        $this->znfTdPayPayorder($tytd, $service_charge, $tranId, $tranDate, $tranTime, $price, $payCardNo, $payMobile, $sumer_fee, $rzprice, $yyrate, $tranAmt, $myBank, $myBankNormal, $Sform, $MERC_CD, $cvn, $expriy);
    }

    ## 商户进件
    public function znfTdPaySj()
    {
        if (!$this->uid) {
            $d = array(
                'code' => 400,
                'msg' => "用户不存在",
            );
            echo json_encode($d);
            exit;
        }
        $infoMyBank = M("mybank")->where(array('type' => 1, 'is_normal' => 1, 'user_id' => $this->uid))->find();
        if (!$infoMyBank) {
            $d = array(
                'code' => 400,
                'msg' => "请绑定结算卡!",
            );
            echo json_encode($d);
            exit;
        }
        $cznf = C("YRTPAY");
        $znfInfo = M("znf_info")->where(array('ID_NAME' => trim($infoMyBank['nickname']), 'PHONE_NO' => trim($infoMyBank['mobile']), 'CARD_NO' => $infoMyBank['cart']))->find();
        if (empty($znfInfo)) {
            # 商户进件
            $url = $this->yrturl;
            /*
            $sheng_id=M('province')->field('adcode,province')->where(array('provids'=>$infoMyBank['province_id']))->find();
            if (!$sheng_id['adcode']){
                $sheng_id['adcode']='430000';
            }
            $city_id=M('city')->field('adcode,city')->where(array('cityids'=>$infoMyBank['city_id']))->find();
            if (!$city_id['adcode']){
                $city_id['adcode']='431000';
            }
            $area_id=M('area')->field('adcode,area')->where(array('areaid'=>$infoMyBank['area_id']))->find();
            if (!$area_id['adcode']){
                # $city_id['adcode']='431000';
                # 暂无区县xxxxx
            }
            */

            //智能付加密
            $signData = array(
                'AGENT_CD' => $cznf['AGENT_CD'],#机构代码
                'PHONE_NO' => $infoMyBank['mobile'],#手机号
                'ID_NO' => $infoMyBank['idcard'],#身份证号
                'ID_NAME' => $infoMyBank['nickname'],#身份姓名
                'CARD_NO' => $infoMyBank['cart'],#结算卡号
                'FEE_RATE' => $cznf['FEE_RATE'],#商户费率
                'CAP_AMT' => $cznf['CAP_AMT'],#封顶手续费单位元  封顶通道必填
                'FIX_AMT' => $cznf['FIX_AMT'],#固定手续费
                'MIN_AMT' => $cznf['MIN_AMT'],#保底手续费
                'BUS_TYPE' => $cznf['BUS_TYPE']#交易类型
            );

            ksort($signData);
            $keys = array_keys($signData); //获取key数组
            $values = array_values($signData); //获取values数组
            $signstr = "";
            for ($i = 0; $i < count($values); $i++) {
                $signstr = $signstr . $keys[$i] . "=" . $values[$i] . "&";
            }
            $signstr = $signstr . "key=" . $cznf['signkey'];
            $sign = strtoupper(Md5($signstr)); //转大写
            $data = array(
                'AGENT_CD' => $cznf['AGENT_CD'],#机构代码
                'PHONE_NO' => $infoMyBank['mobile'],#手机号
                'ID_NO' => $infoMyBank['idcard'],#身份证号
                'ID_NAME' => $infoMyBank['nickname'],#身份姓名
                'CARD_NO' => $infoMyBank['cart'],#结算卡号
                'FEE_RATE' => $cznf['FEE_RATE'],#商户费率
                'CAP_AMT' => $cznf['CAP_AMT'],#封顶手续费单位元  封顶通道必填
                'FIX_AMT' => $cznf['FIX_AMT'],#固定手续费
                'MIN_AMT' => $cznf['MIN_AMT'],#保底手续费
                'BUS_TYPE' => $cznf['BUS_TYPE'],#交易类型
                'SIGN' => $sign
            );
            $data = json_encode($data);
            $result = $this->globalCurlPost($url, $data);
            $ktsdata = json_decode($result);
            $return_code = $ktsdata->return_code;
            $return_info = $ktsdata->return_info;
            $ktsdata = $ktsdata->return_data;
            $merc_cd = $ktsdata->merc_cd;
            $merc_name = $ktsdata->merc_name;
            # if success

            R("Payapi/Api/PaySetLog", array("./PayLog", "Api_ajZhiNengFu_", "---- 智能付商户进件发送请求测试----" . $data . "，返回：" . $result . "----\r\n"));

            if ($return_code == "00") {
                $CDATA = $cznf;
                $dae['PHONE_NO'] = $infoMyBank['mobile'];
                $dae['ID_NO'] = $infoMyBank['idcard'];
                $dae['ID_NAME'] = $infoMyBank['nickname'];
                $dae['CARD_NO'] = $infoMyBank['cart'];
                $dae['FEE_RATE'] = $CDATA['FEE_RATE']; # 商户费率
                $dae['CAP_AMT'] = $CDATA['CAP_AMT'];  # 封顶手续费
                $dae['MIN_AMT'] = $CDATA['MIN_AMT'];  # 保底手续费
                $dae['FIX_AMT'] = $CDATA['FIX_AMT'];  # 固定手续费
                $dae['BUS_TYPE'] = 'WKPAY';  #
                $dae['STATUS'] = $return_code;  #
                $dae['MERC_CD'] = $merc_cd;#商户编号
                $dae['MERC_NADM'] = $merc_name;#商户名称
                M("znf_info")->add($dae);
                $data = array(
                    'merch_id' => $merc_cd,
                    'name' => $merc_name
                );
                return $data;
            } else {
                if ($return_info == '商户已经存在') {
                    $where['PHONE_NO'] = $infoMyBank['mobile'];
                    $where['ID_NAME'] = $infoMyBank['nickname'];
                    $payznfinfo = M('znf_info')->where($where)->find();
                    $data = array(
                        'merch_id' => $payznfinfo['MERC_CD'],
                        'name' => $payznfinfo['MERC_NADM']
                    );
                    return $data;
                } else {
                    return $return_info;
                }
            }
        } else {
            # 已进件
            # $znfInfo
            # 可交易
            $where['PHONE_NO'] = $infoMyBank['mobile'];
            $where['ID_NAME'] = $infoMyBank['nickname'];
            $payznfinfo = M('znf_info')->where($where)->find();
            $data = array(
                'merch_id' => $payznfinfo['MERC_CD'],
                'name' => $payznfinfo['MERC_NADM']
            );
            return $data;
        }

    }

    ## 下单 ## 提交支付
    public function znfTdPayPayorder($tytd = '', $service_charge = '', $tranId = '', $tranDate = '', $tranTime = '', $price = '', $payCardNo = '', $payMobile = '', $sumer_fee = '', $rzprice = '', $yyrate = '', $tranAmt = '', $myBank = '', $myBankNormal, $Sform, $MERC_CD, $cvn = "", $expriy = "")
    {
        $cznf = C("YRTPAY");
        # 保存订单  第一步
        $mdata['user_id'] = $this->uid;
        $mdata['goods_name'] = "O2O收款";
        $mdata['goods_type'] = "消费";
        $mdata['user_pay_supply_id'] = $tytd;
        $mdata['pay_name'] = "O2O交易";
        $mdata['service_charge'] = $service_charge;
        $mdata['pt_ordersn'] = $tranId;
        // 系统平台订单号
        $mdata['platform_ordersn'] = "200" . date('His') . get_timeHm();
        $mdata['timestamp'] = $tranDate . $tranTime;
        $mdata['sh_ordersn'] = ""; //
        $mdata['jy_status'] = 2;
        $mdata['pay_money'] = $price;
        $mdata['money'] = $price - $service_charge;
        $mdata['money_type_id'] = 11; // 快捷支付
        $mdata['t'] = time();
        $mdata['bank_cart'] = $payCardNo;
        $mdata['phone'] = $payMobile;
        $mdata['shop_rate'] = $sumer_fee;
        $pay_supply_type_id = M("sktype")->where(array('user_id' => $this->uid))->getField('pay_supply_type_id');
        $mdata['pay_supply_type_id'] = trim($pay_supply_type_id);
        if (M($this->table)->where(array('pt_ordersn' => $tranId))->getField('pt_ordersn')) {
            $d = array(
                'code' => 200,
                'msg' => '此订单号已存在，勿重复!'
            );
            echo json_encode($d);
            exit;
        }
        $add = M($this->table)->add($mdata);
        if ($add) {
            // 生成结算订单记录
            $jsdata['user_id'] = trim($this->uid);
            $jsdata['relation_order'] = "";
            $jsdata['sh_ordersn'] = "";
            $user_js_supply_id = M("user_pay_supply")->where(array('user_pay_supply_id' => $tytd))->getField('user_js_supply_id');
            # 结算后的金额 / 结算前的金额
            $beforemoney = M("user")->where(array('user_id' => trim($this->uid)))->getField('money');
            $jsdata['user_js_supply_id'] = intval($user_js_supply_id);
            $jsdata['js_money'] = trim($rzprice);  // 入账金额
            $jsdata['tx_service'] = $yyrate; // 提现费2元
            $jsdata['sx_money'] = $service_charge;
            $jsdata['after_money'] = $beforemoney + $rzprice;
            $jsdata['before'] = $beforemoney;
            $jsdata['js_ordersn'] = "";
            $jsdata['serial_num'] = trim($tranId);
            $cart = M("mybank")->where(array('type' => 1, 'is_normal' => 1, 'user_id' => $this->uid))->getField('cart');
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

        //智能付加密
        $signData = array(
            'AGENT_CD' => $cznf['AGENT_CD'],#机构代码
            'ORDER_NO' => $tranId,
            'MERC_CD' => $MERC_CD,
            'ORDER_AMT' => $price,
            'CARD_NO' => $payCardNo,
            'PHONE_NO' => $payMobile,
            'CVN2' => $cvn,
            'EXP_DT' => $expriy,
            'BUS_TYPE' => $cznf['BUS_TYPE'],
        );
        ksort($signData);
        $keys = array_keys($signData); //获取key数组
        $values = array_values($signData); //获取values数组
        $signstr = "";
        for ($i = 0; $i < count($values); $i++) {
            $signstr = $signstr . $keys[$i] . "=" . $values[$i] . "&";
        }
        $signstr = $signstr . "key=" . $cznf['signkey'];
        $sign = strtoupper(Md5($signstr)); //转大写
        $data = array(
            'AGENT_CD' => $cznf['AGENT_CD'],#机构代码
            'ORDER_NO' => $tranId,
            'MERC_CD' => $MERC_CD,
            'ORDER_AMT' => $price,
            'CARD_NO' => $payCardNo,
            'PHONE_NO' => $payMobile,
            'CVN2' => $cvn,
            'EXP_DT' => $expriy,
            'BUS_TYPE' => $cznf['BUS_TYPE'],
            'SIGN' => $sign
        );
        $data = json_encode($data);
        $result = $this->globalCurlPost($this->yrtpayurl, $data);
        $ktsdata = json_decode($result);
        $return_code = $ktsdata->return_code;
        $return_info = $ktsdata->return_info;


        //$ktsdata='{"success":true,"return_info":"00","return_data":{"fee_amt":"6.00","order_no":"18032617381200102423","pay_info":"mercCd=800000041276456&orderNo=18032617381200102423&authType=02","pay_code":"http://payportal.dutiantech.com/app/pay/rediect4pay","order_amt":"500","merc_cd":"800000041276456"},"return_code":"00","token":""}';

        R("Payapi/Api/PaySetLog", array("./PayLog", "Api_ajZhiNengPayorderFu_", "---- 智能付发起下单发送请求测试----" . $data . "，返回：" . $result . "----\r\n"));
        if ($return_code == "00") {
            $ktsdata = $ktsdata->return_data;
            $pay_code = $ktsdata->pay_code;
            $pay_info = $ktsdata->pay_info;
            $orderNo = $ktsdata->order_no;
            $mercCd = $ktsdata->merc_cd;
            $jdpayinfo = json_decode(json_encode($pay_info),true);

            $mycodess = "<form name='submit' action='" . $pay_code . "' accept-charset='utf-8' method='post'>";
            foreach ($jdpayinfo as $k => $v)
            {
                $mycodess.="<input type='hidden' name='".$k."' value='".urldecode($v)."'/>";
            }
            $mycodess.="<script>document.forms['submit'].submit();</script></form>";
            if ($ktsdata) {
                $d = array(
                    'code' => 200,
                    'msg' => '支付发起',
                    'tranId' => $tranId,
                    'Sform' => $Sform,
                    'res' => $mycodess
                );
                echo json_encode($d);
                exit;
            } else {
                $d = array(
                    'code' => 400,
                    'msg' => $return_info ? $return_info : "通道繁忙，稍后再试！",
                );
                echo json_encode($d);
                exit;
            }
        }else{  #已超过最大查询次数或操作过于频繁[6100087],错误码:99
            $d = array(
                'code' => 400,
                'msg' => $return_info ? $return_info : "通道繁忙，稍后再试！",
            );
            echo json_encode($d);
            exit;
        }
    }


    ## 前台回调通知
    public function znfTdPayWapReturn()
    {
        echo "支付成功，请返回！";
        exit;
    }

    ## 回调通知
    public function znfTdPayReturn()
    {
        $tran_sts = $this->isquest['tran_sts'];
        $out_order_no = $this->isquest['out_order_no'];
        $time = time();
        if ($tran_sts == 'S1') {//支付成功
                M('money_detailed')->where(array('pt_ordersn' => $out_order_no))->save(array('jy_status' => 1, 'js_status' => 2, 'success_time' => $time));
                M('moneyjs_detailed')->where(array('pt_ordersn' => $out_order_no))->save(array('js_status' => 2, 'j_succee_time' => $time));

                R("Func/Fenxiao/fenxiaoByOrder",array($out_order_no));
                R("Func/Fenxiao/jiesuanfenxiaoByOrder",array($out_order_no));

            } else if ($tran_sts == "B1" or $tran_sts == "W2") # 失败
            {
                M('money_detailed')->where(array('pt_ordersn' => $out_order_no))->save(array('jy_status' => 3, 'js_status' => 3));
                M('moneyjs_detailed')->where(array('pt_ordersn' => $out_order_no))->save(array('js_status' => 3));
            } else if ($tran_sts == "F2") # 处理中
            {
                M('money_detailed')->where(array('pt_ordersn' => $out_order_no))->save(array('jy_status' => 4, 'js_status' => 1));
                M('moneyjs_detailed')->where(array('pt_ordersn' => $out_order_no))->save(array('js_status' => 1));
            }
        /*
        $mydata = array(
            'mch_id' => $this->isquest['merc_cd'],
            'out_trade_no' => $this->isquest['out_trade_no'],
            'trade_no' => $this->isquest['order_no'],#平台订单号
            'trade_state' => 'success',#交易状态
            'total_fee' => $this->isquest['tran_amt'],#交易金额
            'nonce_str' => $this->isquest['nonce_str']
        );

        $ptdd = M('money_detailed')->where(array('pt_ordersn' => $out_order_no))->find();
        $user_info = M('user')->where(array('merchId' => $ptdd['merchId']))->find();
        $sign = $this->getSign($mydata, $user_info['signkey']);
        $mydata['sign'] = $sign;
        */
        R("Payapi/Api/PaySetLog", array("./PayLog", "Api_znfTdPayReturn", "---- 智能付回调请求----" . json_encode($_REQUEST) ."----\r\n"));
        echo 'SUCCESS';exit;
    }

    public function globalCurlPost($url, $data)
    { // 模拟提交数据函数

        $curl = curl_init(); // 启动一个CURL会话

        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在

        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转

        curl_setopt($curl, CURLOPT_HEADER, false);

//        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer

        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包

        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环

//        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回

        $tmpInfo = curl_exec($curl); // 执行操作

        if (curl_errno($curl)) {

            echo 'Errno' . curl_error($curl);//捕抓异常

        }

        curl_close($curl); // 关闭CURL会话

        return $tmpInfo; // 返回数据，json格式

    }

    ## 查询交易结果订单
    public function znfTdPayOrderInfo($OUT_ORDER_NO="")
    {
        # $OUT_ORDER_NO = $this->isquest['OUT_ORDER_NO'];
        $cznf = C('YRTPAY');

        $user_id = M("money_detailed")->where(array('pt_ordersn'=>$OUT_ORDER_NO))->getField('user_id');
        # 获取默认的结算卡
        $myBankNormal = M("mybank")->where(array('user_id' => $user_id,'jq_status'=>3, 'status' => 1, 'type' => 1, 'is_normal' => 1))->find();# 获取默认的结算卡
        $znf = M('znf_info')->where(array('PHONE_NO'=>$myBankNormal['mobile'],'ID_NAME'=>$myBankNormal['nickname']))->find();
        if(!$znf)
        {
            echo '暂无信息';exit;
        }
        $tranId = '';
        //智能付加密
        $signData = array(
            'AGENT_CD' => $cznf['AGENT_CD'],#机构代码
            'MERC_CD' => $znf['MERC_CD'],
            'OUT_ORDER_NO' => $OUT_ORDER_NO
        );
        ksort($signData);
        $keys = array_keys($signData); //获取key数组
        $values = array_values($signData); //获取values数组
        $signstr = "";
        for ($i = 0; $i < count($values); $i++) {
            $signstr = $signstr . $keys[$i] . "=" . $values[$i] . "&";
        }
        $signstr = $signstr . "key=" . $cznf['signkey'];

        $sign = strtoupper(Md5($signstr)); //转大写
        $data = array(
            'AGENT_CD' => $cznf['AGENT_CD'],#机构代码
            'ORDER_NO' =>  $tranId,
            'MERC_CD' => $znf['MERC_CD'],
            'OUT_ORDER_NO' => $OUT_ORDER_NO,
            'SIGN' => $sign
        );
        $data = json_encode($data);
        $result = $this->globalCurlPost($this->selectorderurl, $data);
        return $result;
    }


    public function getSign($Parameters,$key)
    {


        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        $String = $String."&key=".$key;
        //echo "【string2】".$String."</br>";
        //签名步骤三：MD5加密
        $String = md5($String);
        //echo "【string3】 ".$String."</br>";
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        //echo "【result】 ".$result_."</br>";
        return $result_;
    }

}