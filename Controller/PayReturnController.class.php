<?php
# 银钱包对接接口 - 所有支付回调地址模块接口
# 2017.11.2
namespace Payapi\Controller;
use Think\Controller;

class PayReturnController extends Controller{

    public function _initialize()
    {

    }

    # 快捷支付 -  合利宝同人进出接口回调操作地址
    public function quickpayReturn()
    {
        error_reporting(0);
        $input = file_get_contents('php://input');

        $param = explode('&',$input);
        # 收银台 - 快捷支付
        R("Payapi/Api/PaySetLog",array("./PayLog","PayReturn_quickpayReturn__",'----回调返回信息参数----'.$input));


        # 话费/流量充值订单
        $czorder = explode("=",$param[0]); // 充值订单号

        $orderId = explode("=",$param[11]); // 订单号
        $serialNumber = explode("=",$param[5]); // 返回流水号
        $ordersn = $orderId[1];
        $uid = M("money_detailed")->where(array('pt_ordersn'=>$ordersn))->getField('user_id'); # 交易明细uid
        M("crepay_log")->where(array("ordersn"=>$ordersn))->save(array(
                        'order_status'=>'SUCCESS',
                        'serial_number'=>$serialNumber[1]
                    ));

        # 获取交易信息
        $money_detail = M("money_detailed")->field('money_detailed_id,user_pay_supply_id,pay_money')->where(array("pt_ordersn"=>$ordersn))->find();

        # 支付交易通道 (对接自动结算通道，目前固定DOBANK，结算到银行卡)
        $yy_rate = M("user_pay_supply")->where(array('user_pay_supply_id'=>$money_detail['user_pay_supply_id']))->getField('yy_rate'); # 运营费率 == 交易明细表的手续费
        # 手续费
        $service_charge = $money_detail['pay_money']*($yy_rate/1000); # 千分比例
        # 交易明细
        M("money_detailed")->where(array("pt_ordersn"=>$ordersn))->save(array(
            'jy_status'=>1,
            'd_t'=>time(),
            'sh_ordersn'=>$serialNumber[1],
            'money' => $money_detail['pay_money']-$service_charge, # 实际到账金额 ，减去运营费率(手续费)
            'service_charge' => $service_charge # 手续费
        ));

        $bindId = explode("=",$param[1]);
        # 首次下单绑定支付卡 -
        $mybindbank = M("mybank_bind")->where(array('user_id'=>$uid,'type'=>1,'bindId'=>$bindId[1]))->find();
        $rt3_retMsg = explode("=",$param[7]);
        $rt6_orderId = $ordersn;
        $rt5_userId = explode("=",$param[6]);
        $rt2_retCode = explode("=",$param[7]);
        $rt7_bindStatus = explode("=",$param[4]);
        if(empty($mybindbank))
        {
            # tab card # 支付卡号
            $myBind['card'] = M("money_detailed")->where(array('pt_ordersn'=>$ordersn))->getField('bank_cart');
            $myBind['user_id'] = $uid;
            $myBind['bindId'] = $bindId[1];
            $myBind['retMsg'] = $rt3_retMsg[1];
            $myBind['orderId'] = $rt6_orderId[1];
            $myBind['userId'] = $rt5_userId[1];
            $myBind['retCode'] = $rt2_retCode[1];
            $myBind['bindStatus'] = $rt7_bindStatus[1];
//            $myBind['json'] = $res[1];
            $myBind['type'] = 1; # 1支付卡2结算卡
            $myBind['t'] = time();
            M("mybank_bind")->add($myBind);

            # 鉴权银行卡合利宝保存日志
            $jqlog['bankid'] = $bindId[1];
            $jqlog['json'] = '';
            $jqlog['serial_number'] = '';
            $jqlog['t'] = time();
            $jqlog['user_id'] = $uid;
            $jqlog['userId'] = $rt5_userId[1];
            $jqlog['ordersn'] = $rt6_orderId[1];
            $jqlog['card'] = M("money_detailed")->where(array('pt_ordersn'=>$ordersn))->getField('bank_cart');
            $jqlog['jq_td_id'] = 1; //1合利宝鉴权通道2松顺鉴权通道
            M("jq_log")->add($jqlog);
        }

        if(empty($czorder[1]))  # 不参与充值分销.充值只返回积分
        {
            # 产生分销/分润订单
            R("Func/Fenxiao/fenxiaoByOrder",array($ordersn));

            R("Func/Fenxiao/fenxiaoTkLevelOrder",array($ordersn)); # 交易分 - 固定收益

        }

        if(empty($czorder[1]))
        {
            # 用户提现(直结算)
            if($serialNumber[1])
            {
                $uid = M("money_detailed")->where(array('pt_ordersn'=>$ordersn))->getField('user_id'); # 交易明细uid
                $bindres = $this->ajPayBindCardPay($uid);   // 方法一 查询所绑定的结算卡

    ////            $bindres = R('Wap/Pay/ajPayBindCard');  方法二
                if($bindres)
                {
                    $pdata['userId'] = $bindres['userId'];
//                    M("money_detailed")->where(array('pt_ordersn'=>$ordersn))->getField('user_id')
//                    $pdata['orderId'] = $bindres['orderId'];
                    $pdata['orderId'] = $ordersn; // 取相同的交易订单号

                    $amoney = M("money_detailed")->where(array('pt_ordersn'=>$ordersn))->getField('pay_money');
                    # 结算通道 [ 默认第一条 ]
                    $jssid = M("user_pay_supply")->where(array('user_pay_supply_id'=>$money_detail['user_pay_supply_id']))->getField('pay_supply_id');
                    $pdata['user_js_supply_id'] = M("user_js_supply")->where(array('sid'=>$jssid))->getField('user_js_supply_id');
                    $js_rate = M("user_js_supply")->where(array('sid'=>$jssid))->getField('yy_rate');


                    $yy_rate = M("user_pay_supply")->where(array('user_pay_supply_id'=>$money_detail['user_pay_supply_id']))->getField('yy_rate');  // 运营费率
                    # 结算通道
                    $amount = $amoney - ($amoney*($yy_rate/1000)) - $js_rate; # 扣取默认的手续费（转正请用默认的结算通道的收取费率）

                    $pdata['amount'] = $amount;  # 实际到账金额
                    $pdata['js_money'] = $amoney - ($amoney*($yy_rate/1000)); # 结算金额
                    $pdata['bindId'] = $bindres['bindId'];


                    $pdata['js_card'] = $bindres['card']; # 结算卡
                    $pdata['sx_money'] = ($amoney*($yy_rate/1000));   // 提现服务费(运费费率)
                    $pdata['tx_service'] = $js_rate;   // 手续费

                    $pdata['sid'] = $money_detail['user_pay_supply_id'];
    //
                    R("Payapi/Api/PaySetLog",array("./PayLog","PayReturn_binduser__ ",json_encode($pdata)));
    //                $money_detail['pay_type'];# 支付收款通道ID
                    # 用户提现结算
                    $txres = $this->ajPayJsCardPay($pdata,$uid);  // 方法一
                    $jdtxres = json_decode($txres[1],true);
                    if($jdtxres['rt2_retCode'] == "0000")
                    {
                        M("money_detailed")->where(array('pt_ordersn'=>$ordersn))->save(array(
                            'js_status' => 2
                        ));

                        # 结算成功订单分销
                        R("Func/Fenxiao/jiesuanfenxiaoByOrder",array($ordersn));

                        R("Func/Fenxiao/fenxiaoTkJsLevelOrder",array($ordersn)); #  固定收益
                    }else{
                        // 结算失败  # 多次查询结算卡订单是否成功
                        $jsstatus = R("Payapi/OrderReturn/HLBSELECTJSTXORDER",array($ordersn));
                        if($jsstatus['code'] == 200)
                        {
                            M("money_detailed")->where(array('pt_ordersn'=>$ordersn))->save(array(
                                'js_status' => 2
                            ));

                            M("crepayjs_log")->where(array("ordersn"=>$ordersn))->save(array(
                                'order_status'=>'SUCCESS',
                                'serial_number'=>trim($jdtxres['rt7_serialNumber']),
                                'wc_t' => time()
                            ));

                            # 结算后的金额 / 结算前的金额
                            $beforemoney = M("user")->where(array('user_id'=> trim($uid)))->getField('money');
                            M("moneyjs_detailed")->where(array("pt_ordersn"=>$ordersn))->save(array(
                                'js_status' => 2,
                                'type' => 2,
                                'before_money' => $beforemoney, # 结算前的金额
                                'after_money' => $beforemoney + $pdata['amount'], # 结算后的金额
                                'order_status'=>'SUCCESS',
                                'order_msg'=>$jsstatus['msg'],
                                'j_succee_time' => time()
                            ));
                            # 结算成功订单分销
                            R("Func/Fenxiao/jiesuanfenxiaoByOrder",array($ordersn));

                            R("Func/Fenxiao/fenxiaoTkJsLevelOrder",array($ordersn)); #  固定收益
                        }else{
                            # 结算失败
                            M("money_detailed")->where(array('pt_ordersn'=>$ordersn))->save(array(
                                'js_status' => 3
                            ));
                        }

                    }
    ////                $txres = R('Wap/Pay/ajPayJsCard',array($pdata));  方法二

                }

            }
        }


//        {"userId":"13112157790","orderId":"bc__20171115120112","amount":4,"bindId":"901754cb1a114edf92f3b25da92a7d3e"}{"userId":"13112157790","orderId":"bc__20171115120112","amount":4,"bindId":"901754cb1a114edf92f3b25da92a7d3e"}





        R("Payapi/Api/PaySetLog",array("./PayLog","cztest1_",'----充值快捷支付返回信息参数----'.json_encode($czorder)));
        if($czorder[1])
        {
            # 订单改变状态 -
            M("chongzhi_order")->where(array('chongzhi_order_id'=>$czorder[1]))->save(array('isfukuan'=>'已付款','pay_time'=>time()));

            $czreturn = R("Func/Func/callChongzhi",array($czorder[1]));
            # 充值成功
            if($czreturn['result']['ret_code'] == 10000000)
            {
                M("chongzhi_order")->where(array('chongzhi_order_id'=>$czorder[1]))->save(array('ichongzhi'=>'已充值','return_msg'=>$czreturn['result']['ret_msg'],'other_order_number'=>$czreturn['body']['stream_id']));
            }else{
                M("chongzhi_order")->where(array('chongzhi_order_id'=>$czorder[1]))->save(array('ichongzhi'=>'未充值','return_msg'=>$czreturn['result']['ret_msg']));
            }

            R("Payapi/Api/PaySetLog",array("./PayLog","czorder_",'----充值快捷支付返回信息参数----'.json_encode($czreturn)));
        }

        echo 'success';exit;
    }


    # 测试调用接口
    public function ceshiindex()
    {
//        R("Payapi/Api/PaySetLog",array("./PayLog","jsbn_",'----测试立刻绑卡结算提现第一步----'));
        $bindres = R('Wap/Pay/ajPayBindCard');  # 绑定结算卡返回的参数
        dump($bindres);die;
    }


    # 首次下单支付 - 绑结算卡
    public function ajPayBindCardPay($uid=0)
    {
        $param = C("KJPAY");  // 调用function.php文件中的配置参数  # 认证参数
        $oid = intval($_GET['oid'])?$_GET['oid']:28; // 测试参数28
        if(empty($oid))
        {
            echo '暂无可结算订单';
            exit;
        }

        # 蔡俊锋的测试号
//        $order = array(
//            'idCardNo' => '445221199702234558',
//            'cardNo' => '6217003320015057576',
//            'phone' => '13112157790',
//            'payerName' => '蔡俊锋',
//            'orderId' => "creadition__".date('Ymdhis',time())//订单号
//        );

        # 获取默认的结算卡
        $mybankType = M("mybank")->where(array('user_id'=>$uid,'type'=>1,'is_normal'=>1,'jq_status'=>3))->order('mybank_id asc')->find();  # 默认第一张结算卡

        if($mybankType)
        {

            $borderId =  "HLBBK".get_timeHm();
            $order = array(
                'idCardNo' => $mybankType['idcard'],
                'cardNo' => $mybankType['cart'],
                'phone' => $mybankType['mobile'],
                'payerName' => $mybankType['nickname'],
                'orderId' => $borderId //订单号b
            );
        }
//        $param['userId'] = $this->getUserId($this->uid);
        // 鉴权一次 (重新绑定)
        $mbinfo = M('mybank_bind')->where(array('user_id'=>$uid,'type'=>2,'is_normal'=>1))->find();


        $myBind = array();
        R("Payapi/Api/PaySetLog",array("./PayLog","ceshiBindJsCard__",'-----测试所绑定的结算记录是否差异---'.json_encode($mbinfo).'-----'.json_encode($mybankType)));

        if(empty($mbinfo))
        {
            $phone = M("user")->where(array('user_id'=>$uid))->getField('phone');
            $param['userId'] = $phone;
            $res = R("Payapi/Api/ajPayBindCard",array($param,$order));
//            dump($res);die;
            $resjson = json_decode($res[1],true);

            # 2017.11.15号最新测试数据00:02
//            {"rt10_bindId":"901754cb1a114edf92f3b25da92a7d3e","rt2_retCode":"0000","rt5_userId":"13112157790","rt6_orderId":"bc__20171115120112","rt7_bindStatus":"SUCCESS","sign":"71d012cce38a723fd8fcd7108eae6de8","rt1_bizType":"SettlementCardBind","rt4_customerNumber":"C1800001834","rt8_bankId":"CCB","rt3_retMsg":"认证成功","rt9_cardAfterFour":"7576"}

            # 查询绑卡记录
//            $card = M("mybank")->where(array('user_id'=>$uid,'type'=>1))->getField('cart');

            # 保存绑定结算卡鉴权
            $myBind['user_id'] = $uid;
            $myBind['bindId'] = $resjson['rt10_bindId'];
            $myBind['retMsg'] = $resjson['rt3_retMsg'];
            $myBind['orderId'] = $resjson['rt6_orderId'];
            $myBind['userId'] = $resjson['rt5_userId'];
            $myBind['retCode'] = $resjson['rt2_retCode'];
            $myBind['bindStatus'] = $resjson['rt7_bindStatus'];
            $myBind['card'] = $mybankType['cart'];  # 首次结算卡
            $myBind['json'] = $res[1];
            $myBind['t'] = time();
            $myBind['type'] = 2;
            $myBind['is_normal'] = 1; // 保存默认结算卡
            M("mybank_bind")->add($myBind);


            #鉴权银行卡合利宝保存日志 - 结算卡
            $jqlog['bankid'] = $resjson['rt10_bindId'];
            $jqlog['json'] = '';
            $jqlog['serial_number'] = '';
            $jqlog['t'] = time();
            $jqlog['user_id'] = $uid;
            $jqlog['userId'] = $resjson['rt5_userId'];
            $jqlog['ordersn'] = $resjson['rt6_orderId'];
            $jqlog['card'] = $mybankType['cart'];  # 首次结算卡
            $jqlog['jq_td_id'] = 1; //1合利宝鉴权通道2松顺鉴权通道
            M("jq_log")->add($jqlog);
        }else{
            $mbinfobind = M('mybank_bind')->where(array('user_id'=>$uid,'type'=>2))->find();
            $myBind['user_id'] = $uid;
            $myBind['bindId'] = $mbinfobind['bindId'];
            $myBind['retMsg'] = $mbinfobind['retMsg'];
            $myBind['orderId'] = $mbinfobind['orderId'];
            $myBind['userId'] = $mbinfobind['userId'];
            $myBind['retCode'] = $mbinfobind['retCode'];
            $myBind['bindStatus'] = $mbinfobind['bindStatus'];
            $myBind['card'] = $mybankType['cart'];
            $myBind['type'] = 2;
            $myBind['is_normal'] = 1; // 保存默认结算卡
        }

//        2.substr(microtime(true),0,2);  // 0.5555555555

        # 测试返回数据
        /*
        array(2) {
          array(2) {
              [0] => int(1)
              [1] => string(355) "{"rt10_bindId":"4b3b34f93f8845a19e7d0c0176385b35","rt2_retCode":"0000","rt5_userId":"851953024510","rt6_orderId":"creadition__20171114073403","rt7_bindStatus":"SUCCESS","sign":"8c63bddf1445cef9b9c8b02fb80b122e","rt1_bizType":"SettlementCardBind","rt4_customerNumber":"C1800001834","rt8_bankId":"CCB","rt3_retMsg":"认证成功","rt9_cardAfterFour":"7576"}"
            }
        */
//        dump($myBind);die;
/*        ----测试绑定银行卡数据----{"id":"4","uid":"31","nature":"1","type":"1","bankid":"2","lianhang":"0","nickname":"\u8521\u4fca\u950b","cart":"6217003320015057576","idcard":"445221199702234558","mobile":"13112157790","cart_img":"","cw_two":"0","useful":"","status":"1","t":"1509704479"}
          ----测试绑定银行卡数据----null
          ----测试绑定银行卡数据----{"id":"4","uid":"31","nature":"1","type":"1","bankid":"2","lianhang":"0","nickname":"\u8521\u4fca\u950b","cart":"6217003320015057576","idcard":"445221199702234558","mobile":"13112157790","cart_img":"","cw_two":"0","useful":"","status":"1","t":"1509704479"}
          ----测试绑定银行卡数据----null
*/

        R("Payapi/Api/PaySetLog",array("./PayLog","PayReturn_ajPayBindCardPay__",'----支付绑卡记录----'.json_encode($order)));

        return $myBind;
    }



//[1,"{\"rt2_retCode\":\"0000\",\"rt5_userId\":\"13112157790\",\"rt7_serialNumber\":\"7772009\",\"rt6_orderId\":\"tx__20171119010014\",\"sign\":\"93409d0d0d517810c07e9a91419a55a5\",\"rt1_bizType\":\"SettlementCardWithdraw\",\"rt4_customerNumber\":\"C1800001834\",\"rt3_retMsg\":\"\u63a5\u6536\u6210\u529f\"}"]

    # 用户提现结算
    public function ajPayJsCardPay($pdata=array(),$uid=0)
    {
        $param = C("KJPAY");  // 调用function.php文件中的配置参数  # 认证参数

        $oid = intval($_GET['oid'])?$_GET['oid']:28; // 测试参数28
        if(empty($oid))
        {
            echo '暂无可结算订单';
            exit;
        }

//        $orderId = "HLBTX".date('Ymdhis',time());
//        $js_tongdao = $pdata['js_tongdao'];
//        $js_tongdao =
        $order = array(
            'uid' => $uid,
            'userId' => $pdata['userId'],
            'orderId' => $pdata['orderId'],
            'user_js_supply_id' => $pdata['user_js_supply_id'],
            'amount' => $pdata['amount'],
            'feeType' => 'PAYER',   // PAYER:付款方收取手续费 ,  RECEIVER:收款方收取手续费
            'summary' => "提现", // 提现备注
            'bindId' => $pdata['bindId'], // 绑卡ID
            'js_card' => trim($pdata['js_card']),
            'sx_money' => trim($pdata['sx_money']),
            'tx_service' => trim($pdata['tx_service']),
            'js_money' => trim($pdata['js_money']),
            'type' => 2
        );
        $res = R("Payapi/Api/ajPayJsCard",array($param,$order));  // 注释

        R("Payapi/Api/PaySetLog",array("./PayLog","PayReturn_ajPayJsCardPay__",'----用户提现请求记录----'.json_encode($res)));

//        $res = array();
        return $res;
    }


    # 快捷支付 - 瀚银支付接口回调操作地址 (后台通知地址)
    public function hypayReturn()
    {
        echo 'success'; exit;
    }
    # 快捷支付 - 瀚银支付接口回调操作地址 (前台通知地址)
    public function hypayWapReturn()
    {
        echo 'success'; exit;
    }

    # 快捷支付 - 智付支付接口回调操作地址 (后台通知地址)
    public function zhifupayReturn()
    {
        echo 'success';exit;
    }

    # 快捷支付 - 智付支付接口回调操作地址 (前台通知地址)
    public function zhifupayWapReturn()
    {
        echo 'success';exit;
    }

}