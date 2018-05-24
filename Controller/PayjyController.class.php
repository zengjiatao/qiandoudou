<?php
/**
 * Created by 银迅通支付网络公司.
 * User: Jeffery
 * Date: 2017/12/19
 * Time: 16:43
 * o2o 支付交易接口
 */

namespace Payapi\Controller;


class PayjyController extends BaseController
{
    public $uid;
    public $parem;
    public $sign;
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
        # 加密控制
        if(ACTION_NAME != "zfPayWap" && ACTION_NAME != "zfPayReturn" && ACTION_NAME != "zfPay" && ACTION_NAME != "zfPayWapReturn")
        {
            $this->uid = $_REQUEST['uid'];
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


    # 智付支付 --- app接口（单独）
    public function zfPayapi()
    {
//        echo '请调用测试连接地址';exit;
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
        $tranDate = date("Ymd");
        if ($tytd == '19'){
            $tranId = "XJF".date("YmdHis").mt_rand(10000,99999);
        }else{
            $tranId = "ZFQP".date("YmdHis").mt_rand(10000,99999);
        }
        $tranTime = date("His");
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
        $tranAmt = $price*100;
        $bankId = trim($jbank['hlb_bank_code']);
        $orderDesc = "o2o交易";

        # 回调地址 - 线上地址
        $hostUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'];
        $hostUrl = "https://wallet.insoonto.com/";
        $returnURL = $hostUrl."Payapi/Payjy/zfPayReturn";


        # 回调地址 - 本地测试地址
        /*
        $hostUrl = "http://192.168.199.39/qiandoudou/";
        $returnURL = $hostUrl."Payapi/Payjy/zfPayReturn";
            # 测试参数
            $payCardNo = "6221558812340000";
            $cvn = "123";
            $expriy = "1123";
            $payMobile = "13112157790";
            $idCard = "445221199702234558";
            $mobile = "13112157790";
            $accountNo = "6217003320015057576";
            $accountName = "蔡俊锋";
            $accountBank = "中国建设银行";
            $sumer_fee = C("ZHIFUPAY");
        */
        $trustBackUrl =  $returnURL; // 后台地址  (修改订单状态的url
        $trustFrontUrl = $hostUrl."Payapi/Payjy/zfPayWap"; // 前台支付成功返回页面  (即支付成功后返回成功页面的url)
        $ZHIFUPAY = C("ZHIFUPAY");


        # 正式参数 (智付)
        /**/
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

        if ($tytd == '19'){    //星洁的交易通道 19   默认有积分
//            $d = array(
//                'code' => 200,
//                'msg' => '该通道正在维护中!'
//            );
//            echo json_encode($d);
//            exit;

            //判断用户是否有商户进件
            $shanghu = M('xj_info')->where(array('user_id'=>$this->uid,'jifen_type'=>0))->find();
            if ($shanghu['mch_id']){  //存在 商户号   先添加订单 再发起交易
                # 保存订单  第一步
                $mdata['user_id'] = $this->uid;
                $mdata['goods_name'] = "O2O收款";
                $mdata['goods_type'] = "消费";
                $mdata['user_pay_supply_id'] = $tytd;
                $mdata['pay_name'] = "O2O交易";
                $mdata['service_charge'] = $service_charge;
                $mdata['pt_ordersn'] = $tranId;
                $mdata['timestamp'] = $tranDate.$tranTime;
                $mdata['sh_ordersn'] = ""; //
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
                //正式参数
                $PayCanShu=array(  //信用卡 $myBank
                    'totalFee'=>$tranAmt,  //单位 分 已经*100
                    'agentOrderNo'=>$tranId,
                    'notifyUrl'=>'https://wallet.insoonto.com/index.php/Payapi/XkPay/xjPayReturn', //异步
                    'mchId'=>$shanghu['mch_id'], //商户号
                    'returnUrl'=>'https://wallet.insoonto.com/index.php/Payapi/XkPay/xjPayUrl',  //同步
                    'bankcard'=>$myBank['cart'], //交易卡号(信用卡)
                    'nick_name'=>$myBank['nickname'],
                    'idcard'=>$myBank['idcard'],
                    'phone'=>$myBank['mobile'],
                );
                $res = R('Payapi/XkPay/placeOrder',array($PayCanShu));

                if ($res['isSuccess'] === true){
                    $d = array(
                        'code' => 200,
                        'msg' => '支付发起',
                        'tranId' => $tranId,
                        'res' => $res['data']['returnHtml']
                    );
                    echo json_encode($d);
                    exit;
                }else{
                    $d = array(
                        'code' => 400,
                        'msg' => $res['message'],
                    );
                    echo json_encode($d);
                    exit;
                }
            }else{ //不存在 需要进件
            //商户进件参数
                $sheng_id=M('province')->field('adcode,province')->where(array('provids'=>$myBankNormal['province_id']))->find();
                if (!$sheng_id['adcode']){
                    $sheng_id['adcode']='430000';
                }
                $city_id=M('city')->field('adcode,city')->where(array('cityids'=>$myBankNormal['city_id']))->find();
                if (!$city_id['adcode']){
                    $city_id['adcode']='431000';
                }

                $address=$sheng_id['province'].$city_id['city']; //地址
                $fee0=4.8; //费率  单位:千分
                $d0fee=200; //提现费
                $pointsType=0;// 0带积分
                $ShJj=array(
                    'bankcard'=>$myBankNormal['cart'],
                    'nick_name'=>$myBankNormal['nickname'],
                    'idcard'=>$myBankNormal['idcard'],
                    'phone'=>$myBankNormal['mobile'],
                    'address'=>$address,
                    'provinceCode'=>$sheng_id['adcode'],
                    'cityCode'=>$city_id['adcode'],
                    'fee0'=>$fee0,
                    'd0fee'=>$d0fee,
                    'pointsType'=>$pointsType,
                );
                $ShJjInfo=R('Payapi/XkPay/shanghujj',array($ShJj));
                if ($ShJjInfo['isSuccess'] === true){ //进件成功   //发起交易
                    //保存商户信息
                    $addShanghu['name']=$myBankNormal['nickname'];
                    $addShanghu['idcard']=$myBankNormal['idcard'];
                    $addShanghu['bankcard']=$myBankNormal['cart'];
                    $addShanghu['phone']=$myBankNormal['mobile'];
                    $addShanghu['user_id']=$this->uid;
                    $addShanghu['mch_id']=$ShJjInfo['data'];
                    $addShanghu['jifen_type']=$pointsType;
                    $addShanghu['t']=time();
                    $isSh=M('xj_info')->add($addShanghu);
                    if ($isSh){ //发起交易
                        # 保存订单  第一步
                        $mdata['user_id'] = $this->uid;
                        $mdata['goods_name'] = "O2O收款";
                        $mdata['goods_type'] = "消费";
                        $mdata['user_pay_supply_id'] = $tytd;
                        $mdata['pay_name'] = "O2O交易";
                        $mdata['service_charge'] = $service_charge;
                        $mdata['pt_ordersn'] = $tranId;
                        $mdata['timestamp'] = $tranDate.$tranTime;
                        $mdata['sh_ordersn'] = "";//
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
                        //正式参数
                        $PayCanShu=array(  //信用卡 $myBank
                            'totalFee'=>$tranAmt,  //单位 分 已经*100
                            'agentOrderNo'=>$tranId,
                            'notifyUrl'=>'https://wallet.insoonto.com/index.php/Payapi/XkPay/xjPayReturn', //异步
                            'mchId'=>$ShJjInfo['data'], //商户号
                            'returnUrl'=>'https://wallet.insoonto.com/index.php/Payapi/XkPay/xjPayUrl',  //同步
                            'bankcard'=>$myBank['cart'], //交易卡号(信用卡)
                            'nick_name'=>$myBank['nickname'],
                            'idcard'=>$myBank['idcard'],
                            'phone'=>$myBank['mobile'],
                        );
                        $res = R('Payapi/XkPay/placeOrder',array($PayCanShu));
                        if ($res['isSuccess'] === true){
                            $d = array(
                                'code' => 200,
                                'msg' => '支付发起',
                                'tranId' => $tranId,
                                'res' => $res['data']['returnHtml']
                            );
                            echo json_encode($d);
                            exit;
                        }else{
                            $d = array(
                                'code' => 400,
                                'msg' => $res['message'],
                            );
                            echo json_encode($d);
                            exit;
                        }
                    }else{
                        echo json_encode(array('code'=>3449,'msg'=>'商户信息异常:'.$ShJjInfo["message"]));die;
                    }
                }else{
                    echo json_encode(array('code'=>3443,'msg'=>$ShJjInfo['message']));die;
                }
            }
        }else{     //智付的交易
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
            /*
             * 转正式阶段
             * */

            if(M($this->table)->where(array('pt_ordersn'=>$tranId))->getField('pt_ordersn'))
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
            $res = $this->zfPay($pdata);

            $d = array(
                'code' => 200,
                'msg' => '支付发起',
                'tranId' => $tranId,
                'res' => $res
            );
            echo json_encode($d);
            exit;
        }
    }

    # 前台返回地址
    public function zfPayWap()
    {
//        xxx
    }

    # 智付支付 ---- 前台支付成功返回页面
    public function zfPayWapReturn()
    {
//        error_reporting(0);
        R("Payapi/Api/PaySetLog",array("./PayLog","PayReturn_zfPayWap__",'----智付支付前台通知参数----'.json_encode($_REQUEST)));
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
//        M("money_detailed")->where(array(''=>));

//        ZFQP2018010214380979430
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
                'msg' => '未支付成功'
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

       /*
        # 查询订单是否交易成功 ( 这是一开始调用最初返回订单 )
        $res = R("Payapi/OrderReturn/ZFSELECTPAYORDER",array($pdata));
        if($res['origRespCode'] != '00')
        {
            $d = array(
                'code' => 200,
                'msg' => '未支付成功'
            );
            echo json_encode($d);
            exit;
        }
        if($res['origRespCode'] == '00') # 交易成功
        {
            $msg = "支付成功";
            $d = array(
                'code' => 400,
                'msg' => $msg
            );
        }else if($res['origDfStatus'] == "01") # 失败
        {
            $msg = "支付失败";
            $d = array(
                'code' => 200,
                'msg' => $msg
            );
        }else if($res['origDfStatus'] == "05") # 处理中
        {
            $msg = "支付处理中";
            $d = array(
                'code' => 200,
                'msg' => $msg
            );
        }else{
            $msg = trim($res['respMsg']);
            $d = array(
                'code' => 200,
                'msg' => $msg
            );
        }
       */
        echo json_encode($d);
        exit;
    }


    # 智付支付 ---- 后台通知回调
    public function zfPayReturn()
    {
//        error_reporting(0);
//        $getJson = json_decode($_REQUEST,true);
        R("Payapi/Api/PaySetLog",array("./PayLog","PayReturn_zfPayReturn__",'----智付支付后台返回通知参数----'.json_encode($_REQUEST)));
        $getJson = $_REQUEST;

        # 回调订单
        $pt_ordersn = trim($getJson['tranId']);
        $iforder = M($this->table)->field('user_id,user_pay_supply_id,service_charge')->where(array('pt_ordersn'=>$pt_ordersn))->find();

        $user_js_supply_id = M("user_pay_supply")->where(array('user_pay_supply_id'=>$iforder['user_pay_supply_id']))->getField('user_js_supply_id');

        /*
        # checkorder
        $pdata = array(
            'tranDate' => trim($getJson['tranDate']),
            'tranId' => trim($pt_ordersn),
            'tranTime' => trim($getJson['tranTime']),
            'origTranDate' => trim($getJson['tranDate']),
            'origTranId' => trim($pt_ordersn),
        );
         查询订单状态接口
            $res = R("Payapi/OrderReturn/ZFSELECTPAYORDER",array($pdata));
            R("Payapi/Api/PaySetLog",array("./PayLog","PayReturn_zfPayReturn__",'----智付支付后台查询订单返回通知参数----'.json_encode($res)));
        */

        $res['origRespCode'] = trim($getJson['origRespCode']);
        $res['origTranId'] = $pt_ordersn;
        $res['origDfAmount'] = trim($getJson['origDfAmount']);
        $res['origDfStatus'] = trim($getJson['origDfStatus']);
        $res['origDfMsg'] = '结算成功';

        if($getJson['respCode'] == '00') # 交易成功
        {
            /*
            # 结算后的金额 / 结算前的金额
            $beforemoney = M("user")->where(array('user_id'=> trim($iforder['user_id'])))->getField('money');

            # 订单号
            $origTranId = trim($res['origTranId']);
            M($this->table)->where(array('pt_ordersn'=>$origTranId))->save(array('jy_status'=>1));
            $origDfAmount = trim($res['origDfAmount']/100); // 入账金额/分
            $jsdata['user_id'] = trim($iforder['user_id']);
            $jsdata['relation_order'] = "";
            $jsdata['sh_ordersn'] = "";
            $jsdata['user_js_supply_id'] = intval($user_js_supply_id);
            $jsdata['js_money'] = trim($origDfAmount);
//            $jsdata['tx_service'] = 0;
            $jsdata['sx_money'] = $iforder['service_charge'];
            $jsdata['after_money'] = $beforemoney+$origDfAmount;
            $jsdata['before'] = $beforemoney;
            $jsdata['js_ordersn'] = "";
            $jsdata['serial_num'] = $origTranId;
//            $jsdata['js_card'] = "";
            $jsdata['js_status'] = 1;
            $jsdata['t'] = time();
            $jsdata['js_type'] = 1;
            $jsdata['type'] = 2;
            $jsdata['is_duixiang'] = "";
            $jsdata['order_status'] = trim($res['origDfStatus']);
            $jsdata['order_msg'] = trim($res['origDfMsg']);
            $jsdata['pt_ordersn'] = trim($origTranId);
            $jsdata['rz_money'] = trim($origDfAmount);
            */

            /**/
            # 是否结算(代付)成功
            if($res['origDfStatus'] == "00")  # 成功
            {
                $jsdata['js_status'] = 2;
                $jsdata['j_success_time'] = time()+30;
                $js_status = 2;
            }else if($res['origDfStatus'] == "01") # 失败
            {
                $jsdata['js_status'] = 3;
                $jsdata['j_success_time'] = "";
                $js_status = 3;
            }else if($res['origDfStatus'] == "05") # 处理中
            {
                $jsdata['js_status'] = 1;
                $jsdata['j_success_time'] = "";
                $js_status = 1;
            }


            #-- 交易成功，结算成功
            $jsdata['js_status'] = 2;
            $jsdata['j_success_time'] = time()+30;
            $ifjsorder = M($this->jstable)->where(array('pt_ordersn'=>$pt_ordersn))->getField('pt_ordersn');
            if($ifjsorder)
            {
                M($this->table)->where(array('pt_ordersn'=>$pt_ordersn))->save(array('jy_status'=>1,'js_status'=>$js_status));
                M($this->jstable)->where(array('pt_ordersn'=>$pt_ordersn))->save($jsdata);
            }
            if($pt_ordersn)
            {
                # 产生分销/分润订单 - 交易
                R("Func/Fenxiao/fenxiaoByOrder",array($pt_ordersn));

                # 结算成功订单分销
                R("Func/Fenxiao/jiesuanfenxiaoByOrder",array($pt_ordersn));

                R("Func/Fenxiao/fenxiaoTkLevelOrder",array($pt_ordersn)); # 交易分 - 固定收益

                R("Func/Fenxiao/fenxiaoTkJsLevelOrder",array($pt_ordersn)); # 结算分 - 固定收益

            }
        }else
        {
          # xxxx 交易不成功 ( 上游返回结算失败，验签失败.... )
        }

        echo 'success';exit;
    }

    # 智付支付 ---- app调用查询接口
    public function zfPayCheckOrder()
    {
        $tranId = $_REQUEST['tranId'];
    }

    # 测试智付 --- H5模式
    public function zfH5()
    {
        $tranDate = date("Ymd");
        $tranId = "ZF".date("YmdHis").mt_rand(1000,9999);
        $tranTime = date("His");
        $tranAmt = "10000";
        $bankId = "CCB";
        $orderDesc = "测试";

        # 回调地址
        $hostUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'];
        $hostUrl = "https://wallet.insoonto.com";
        $returnURL = $hostUrl."/Payapi/PayReturn/zhifupayReturn";
        $trustBackUrl =  $returnURL; // 后台地址
        $trustFrontUrl = $hostUrl; // 前台支付成功返回页面
        $payCardNo = "6221558812340000";
        $cvn = "123";
        $expriy = "1123";
        $payMobile = "13112157790";
        $idCard = "445221199702234558";
        $mobile = "13112157790";
//        $accountNo = "6212263602018610659";
        $accountNo = "6217003320015057576";
        $accountName = "蔡俊锋";
        $accountBank = "中国建设银行";
        $sumer_fee = "0.40";

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
            'sumer_fee' => $sumer_fee // 持卡人费率
        );

//        dump($pdata);die;
//        R("Payapi/Api/PaySetLog",array("./PayLog","Api_ajZhiFu_",'---- 智付发送请求测试----'.json_encode($pdata)));

        $res = $this->zfPay($pdata);

//        dump($res);
        $this->assign("res",$res);
        $this->display("Payjy_submit");
    }

    # 再次调用查询订单接口返回是否支付成功/失败
    public function zfPayReturnEcho()
    {
        $order = trim($_REQUEST['ordersn']);

    }

    # 智付
    public function zfPay($pdata=array())
    {
        import('Vendor.payZhiFu.zfPay');
        $config = C("ZHIFUPAY");
        $ZfPay = new \zfPay($config);
        $res = $ZfPay->submitPay($pdata);
        return $res;
    }

    # 测试验签
    public function zfsign()
    {
        import('Vendor.payZhiFu.zfPay');


        $ratemoney = 2323213213213;
        var_dump(number_format($ratemoney,4));
        die;

        $config = C("ZHIFUPAY");
        $ZfPay = new \zfPay($config);

        $res = $ZfPay->submitSelect();

    }


    # 测试h5跳转链接
    public function ceshiurl()
    {
        echo '<html> <h1> 支付成功，正在跳转中... </h1> </html>';
        header("location:".U("Payjy/toUrl")."?tranId=ZFQP2018010214380979430&merchId=830581048161139&signature=MrcpQw4oAmRpe+nJGmNq7dZmrj+RwAnOzKKzgUjEFQHdJMNF\/yk9iMZklqJFX0Lj4RSXG+tigA1G3SUsmalRpCaBpR823Lsonw\/3Yd6OlYAGBN1cqwk0OspQ5YeKs6hy3d2jWKlMbf9gxKxev3jWe8DA3xy10qd4n7hNS+D\/EBxMqmqtsdGV8zXUSuWOYFGftL7ZnldIetIGLL7hmOvhr9dEE\/oN4HphTUiQ5KerwDMeI52KhtD\/fHfoU819waycd4EkOJJm\/y4HYvKBKrR4KM32iccrs7H9lb3ibai2SRlkjrDiJmQouUIp4xIxQ+SsOGX6pZHZxowV5kCfjz2r2Q==");exit;
    }

    public function toUrl()
    {
        echo "订单ID：".$_REQUEST['tranId'];
    }
}