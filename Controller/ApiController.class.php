<?php
# 银钱包对接接口 - 所有第三方API接口对接
# one: 四要素认证接口
# two: 快捷支付接口 - 合利宝同人进出接口（信用卡，银行支付卡）
# three: 翰银支付接口
# four: 智付支付接口
namespace Payapi\Controller;
use Think\Controller;

class ApiController extends Controller{

    public function _initialize()
    {

    }


    # 四要素(银行卡)认证调用
    # $param = 验证参数
    # ## name = 姓名  idNo = 身份证号码  accountNo = 银行卡号  bankPreMobile = 预留手机号码
    # ## sn = 订单号，最好自己传参
    public function getBankRz($param=array(),$sn="")
    {
        import('Vendor.shiJianRz.Rz');
        $cfg = C("SHIJIANRZ"); // 四要素认证
        $transDate = date("Y-m-d H:i:s",time());
        $entityAuthCode = date('YmdHis').rand(10000,99999);
//        if(empty($sn))
//        {
//            $sn = "sj".date('YmdHis').rand(10000,99999);
//        }
        $header=array(
            'transNo'=>$sn,
            'transDate'=>$transDate,
            'orgId'=>'10018',
            'ukey'=>'exam',
            'url'=>array(
                'module'=>'product',
                'action'=>'single',
                'apiVer'=>'v1',
//                'do'=>'bankrz'
            )
        );

        $d = array(
            'name'=>$param['name'],
            'idNo'=>$param['idNo'],
            'bankPreMobile'=>$param['bankPreMobile'],
            'accountNo'=>$param['accountNo'],
            'entityAuthCode'=>$entityAuthCode,
            'entityAuthDate'=>$transDate,
        );

        $client_request_data=array(
            'header'=>$header,
            'busiData'=>array('productId'=>'PRT000007',
                'records'=>array($d)
            ));

        $Rz = new \Rz();
        $res = $Rz->sa($client_request_data,$cfg);
        return $res; // 具体返回
//        return array(
//            'responseData' => $responseData,  //原json 数据为
//            'busiData' => $busiData, // 原业务数据解析为
//            'verifyData' => verifyData($responseData['busiData'],$responseData['securityInfo']['signatureValue'],$cfg)   // 验签结果 -- 看验证结果
//        );
    }












    # 快捷支付-合利宝同人进出接口(银行卡)认证调用
    # $param = 验证参数
    # ## name = 姓名  idNo = 身份证号码  accountNo = 银行卡号  bankPreMobile = 预留手机号码
    # ---------------------------------------------------------------------------------------
    # $myBankInfo = 我的信用卡信息(已开通快捷支付了)
    # ##
    # $order = 订单信息
    #  ---------------------------------------------------------------- (首次下单支付步骤操作)---------------------------------
    public function ajPay($param=array(),$myBankInfo=array(),$order=array())
    {
        $use = 1;
        if($use==0)
        {
            echo '摩擦失措，请重新来!';
            exit;
        }

        import('Vendor.payPerson.HttpClient');
        $Client = new \HttpClient($param['ip']);
        if($_REQUEST)
        {
            $P4_orderId="HLBQP".get_timeHm();//订单号
            $P5_timestamp=date('Ymdhis',time());//时间戳
            $phone = trim($myBankInfo["P13_phone"]);
            $money = trim($order["P15_orderAmount"]);

            if($phone<>"" && $money <> ""){//判断必要参数非空

                //获取form表单参数
                $P1_bizType =  trim($param['P1_bizType']);
                $P2_customerNumber =  trim($param["P2_customerNumber"]);
                $P3_userId =  trim($order['userId']);
                $P4_orderId =  $P4_orderId;
                $P5_timestamp = $P5_timestamp;
                $P6_payerName =  trim($myBankInfo["P6_payerName"]);  // 绑定的银行卡信息-持卡人
                $P7_idCardType =  trim($myBankInfo["P7_idCardType"]);  // 绑定的银行卡信息-证件类型
                $P8_idCardNo =  trim($myBankInfo["P8_idCardNo"]);  // 绑定的银行卡信息-证件类型
                $P9_cardNo =  trim($myBankInfo["P9_cardNo"]);  // 绑定的银行卡信息-证件号码
                $P10_year =  trim($myBankInfo["P10_year"]);   // 绑定的银行卡信息-有效年期限(信用卡必填)
                $P11_month =  trim($myBankInfo["P11_month"]); // 绑定的银行卡信息-有效月期限(信用卡必填)
                $P12_cvv2 =  trim($myBankInfo["P12_cvv2"]); // 绑定的银行卡信息-信用卡安全码，最后三位数(信用卡必填)
                $P13_phone =  trim($myBankInfo["P13_phone"]); // 绑定的银行卡信息-预留手机号码(信用卡)
                $P14_currency =  trim($param["P14_currency"]); // 交易币种
                $P15_orderAmount =  trim($order["P15_orderAmount"]); // 交易金额
                $P16_goodsName =  trim($order["P16_goodsName"]);  // 商品名称
                $P17_goodsDesc =  trim($order["P17_goodsDesc"]); // 商品描述
                $P18_terminalType =  trim($param["P18_terminalType"]); // 终端类型
                $P19_terminalId =  trim($param["P19_terminalId"]);  // 终端标识
//


                # 绑定关联的IP地址
                $P20_orderIp =  $param['ip'];

                $P21_period =  trim($order["P21_period"]);  // 订单有效时间
                $P22_periodUnit =  trim($order["P22_periodUnit"]);  // 有效时间单位

                # 回调地址
                $hostUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'];
                $hostUrl = "https://wallet.insoonto.com";
                $returnURL = $hostUrl."/Payapi/PayReturn/quickpayReturn?czorder=".trim($order["czorder"]);  # 新增参数(充值订单)
                $P23_serverCallbackUrl =  $returnURL;  // 回调地址

                $signkey_quickpay = $param['signkey_quickpay'];//密钥key

                //构造签名字符串  &$P25_isIntegral&$P26_aptitudeCode&$P27_integralType
                $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_userId&$P4_orderId&$P5_timestamp&$P6_payerName&$P7_idCardType&$P8_idCardNo&$P9_cardNo&$P10_year&$P11_month&$P12_cvv2&$P13_phone&$P14_currency&$P15_orderAmount&$P16_goodsName&$P17_goodsDesc&$P18_terminalType&$P19_terminalId&$P20_orderIp&$P21_period&$P22_periodUnit&$P23_serverCallbackUrl&$signkey_quickpay";


                $sign = md5($signFormString);//MD5签名

                # 测试请求接口地址
//                $url = "https://test.trx.helipay.com/trx/quickPayApi/interface.action";//网银请求的页面地址
                # 生产请求接口地址
//                http://pay.trx.helipay.com/trx/quickPayApi/interface.action  -- 1
                $url = "https://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";

                # 有/无积分通道
                if($order['is_jifen'] == 1)
                {
                    $P25_isIntegral = 'TRUE'; // 送积分通道 (最新新增参数)
                    $P27_integralType = $order['jifen_type']; //积分类型
                    //post的参数  ,"P25_isIntegral" => $P25_isIntegral,"P26_aptitudeCode" => $P26_aptitudeCode,"P27_integralType" => $P27_integralType
                    $paramss = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_userId'=>$P3_userId,'P4_orderId'=>$P4_orderId,'P5_timestamp'=>$P5_timestamp,'P6_payerName'=>$P6_payerName,'P7_idCardType'=>$P7_idCardType,'P8_idCardNo'=>$P8_idCardNo,'P9_cardNo'=>$P9_cardNo,'P10_year'=>$P10_year,'P11_month'=>$P11_month,'P12_cvv2'=>$P12_cvv2,'P13_phone'=>$P13_phone,'P14_currency'=>$P14_currency,'P15_orderAmount'=>$P15_orderAmount,'P16_goodsName'=>$P16_goodsName,'P17_goodsDesc'=>$P17_goodsDesc,'P18_terminalType'=>$P18_terminalType,'P19_terminalId'=>$P19_terminalId,'P20_orderIp'=>$P20_orderIp,'P21_period'=>$P21_period,'P22_periodUnit'=>$P22_periodUnit,'P23_serverCallbackUrl'=>$P23_serverCallbackUrl,'P25_isIntegral'=>$P25_isIntegral,'P27_integralType'=>$P27_integralType,'sign'=>$sign);

                }else{
                    $P25_isIntegral = 'FALSE'; // 无积分通道 (最新新增参数)


                    //post的参数  ,"P25_isIntegral" => $P25_isIntegral,"P26_aptitudeCode" => $P26_aptitudeCode,"P27_integralType" => $P27_integralType
                    $paramss = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_userId'=>$P3_userId,'P4_orderId'=>$P4_orderId,'P5_timestamp'=>$P5_timestamp,'P6_payerName'=>$P6_payerName,'P7_idCardType'=>$P7_idCardType,'P8_idCardNo'=>$P8_idCardNo,'P9_cardNo'=>$P9_cardNo,'P10_year'=>$P10_year,'P11_month'=>$P11_month,'P12_cvv2'=>$P12_cvv2,'P13_phone'=>$P13_phone,'P14_currency'=>$P14_currency,'P15_orderAmount'=>$P15_orderAmount,'P16_goodsName'=>$P16_goodsName,'P17_goodsDesc'=>$P17_goodsDesc,'P18_terminalType'=>$P18_terminalType,'P19_terminalId'=>$P19_terminalId,'P20_orderIp'=>$P20_orderIp,'P21_period'=>$P21_period,'P22_periodUnit'=>$P22_periodUnit,'P23_serverCallbackUrl'=>$P23_serverCallbackUrl,'P25_isIntegral'=>$P25_isIntegral,'sign'=>$sign);
                }
//                $P26_aptitudeCode = 'BM00000001'; //资质编码




                //调用支付请求
                $pageContents = $Client->quickPost($url, $paramss);  //发送请求 send request

//                dump($paramss);
//                dump($pageContents);
//                die;

                # 保存日志
                $this->PaySetLog("./PayLog","Api_ajPay__",json_encode($paramss)."-------  首单请求返回"."---------".$pageContents."------- \r\n");

                # 保存到交易明细表
                $moneyde['user_id'] =  $order['user_id'];
                $moneyde['goods_name'] =  $order["P16_goodsName"];
                $moneyde['service_charge'] =  ''; # 手续费
                $moneyde['pt_ordersn'] = $P4_orderId;
                $moneyde['jy_status'] = 2; #2交易中
                $moneyde['user_pay_supply_id'] = $order['pay_type'];
                $moneyde['pay_name'] = $order['pay_name'];
                $moneyde['pay_money'] = $P15_orderAmount;
                $moneyde['money_type_id'] =  11; # 快捷支付
                $moneyde['t'] = time();
                $moneyde['timestamp'] = $P5_timestamp;
                $moneyde['bank_cart'] = $P9_cardNo; # 信用卡卡号
                $moneyde['phone'] = $phone;
                $moneyde['goods_type'] = '消费';

                $pay_supply_type_id = M("sktype")->where(array('user_id'=>$order['user_id']))->getField('pay_supply_type_id');
                $moneyde['pay_supply_type_id'] = trim($pay_supply_type_id);
                M("money_detailed")->add($moneyde);


                //解析返回报文,成功才跳转到验证码支付页面
                $obj = json_decode($pageContents);

                if ($obj->rt2_retCode == "0000") {

                    # 保存订单信息
                    $ldata['ordersn'] = $P4_orderId;
                    $ldata['userId'] = $P3_userId;  # phone
                    $ldata['user_id'] = $order['user_id'];
                    $ldata['phone'] = $phone;
                    $ldata['payerName'] = $P6_payerName;
                    $ldata['cardNo'] = $P9_cardNo;
                    $ldata['timestamp'] = $P5_timestamp;
                    $ldata['order_amount'] = $money;
                    $ldata['ip'] = get_client_ip(); # 下单IP地址
                    $ldata['t'] = time();
                    M("crepay_log")->add($ldata);

                    return array(1,$P4_orderId,$P5_timestamp,$P2_customerNumber,$sign);exit;
//                    $url="/Payapi/Api/quickpaySend";
//                    echo "<script language='javascript'>";
//                    echo "location='".$url."?P2_customerNumber=$P2_customerNumber&P4_orderId=$P4_orderId&P5_timestamp=$P5_timestamp&P13_phone=$P13_phone'";
//                    echo "</script>";
//                    exit;
                }else{
                    return array(0,$obj->rt3_retMsg);exit;
                }
            }else{
                return array(0,'请填写必要参数');exit;
            }
        }else{
            return array(0,'请用POST提交');exit;
        }
    }

    # 匹配验证码并支付
    public function ajPaySend($param=array(),$myBankInfo=array(),$order=array(),$pdata=array())
    {
        $use = 1;
        if($use==0)
        {
            echo '摩擦失措，请重新来!';
            exit;
        }
        error_reporting(E_ALL ^ E_NOTICE);
        import('Vendor.payPerson.HttpClient');
        $Client = new \HttpClient($param['ip']);

        $P1_bizType='QuickPaySendValidateCode';
        $cusNum = trim($param['P2_customerNumber']);
        $orderId = trim($order['P4_orderId']);
        $timestamp = trim($order['P5_timestamp']);
        $phone = trim($myBankInfo['P13_phone']);
        $ip = $param['ip'];
        $signkey=$param['signkey_quickpay'];


        //发送验证码的签名串
//        $orinMessage = "&QuickPaySendValidateCode&$cusNum&$orderId&$timestamp&$phone&$signkey";
//        $sign = md5($orinMessage);

//        # 判断是否之前绑定了支付卡
//        $uid = $order['uid'];
//        $ismybank = array();
//        if($uid)
//        {
//            $userId = R("Wap/Pay/getUserId",array($uid));
//            $ismybank = M("mybank_bind")->where(array("userId"=>$userId))->find();
//        }


        if($_REQUEST)
        {
                $code = trim($pdata['P5_validateCode']);
                $cusNum = trim($param['P2_customerNumber']);
                $orderId = trim($order['P3_orderId']);
                $timestamp = trim($order['P4_timestamp']);
                $phone = trim($myBankInfo['P13_phone']);
                if($code <> ""){//检查必要参数

    //              $url = "https://test.trx.helipay.com/trx/quickPayApi/interface.action";
                    # 生产请求接口地址
                    // http://pay.trx.helipay.com/trx/quickPayApi/interface.action
                    $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";  # 交易绑卡接口
                    //支付MD5的签名串
                    $orinSendMessage = "&QuickPayConfirmPay&$cusNum&$orderId&$timestamp&$code&$ip&$signkey";
                    $sendSign = md5($orinSendMessage);

                    //支付请求参数
                    $paramss=array('P1_bizType'=>'QuickPayConfirmPay','P2_customerNumber'=>$cusNum,
                        'P3_orderId'=>$orderId,'P4_timestamp'=>$timestamp,'P5_validateCode'=>$code,'P6_orderIp'=>$ip,'sign'=>$sendSign);

                    //调用支付请求
                    $pageContents = $Client->quickPost($url, $paramss);  //发送请求 send request


                    # 保存日志
                    $this->PaySetLog("./PayLog","Api_ajPaySend__",json_encode($paramss)."-------  首次下单请求返回"."---------".$pageContents."------- \r\n");


                    # 添加鉴权表 ( 首次支付成功鉴权银行卡 )
                    $obj = json_decode($pageContents,true);
                    if($obj['rt2_retCode'] == "0000")
                    {
                        $bindId = $obj['rt10_bindId'];
                        # 首次下单绑定支付卡 -
                        $mybindbank = M("mybank_bind")->where(array('user_id'=>$pdata['user_id'],'type'=>1,'bindId'=>$bindId))->find();
                        # 保存日志
                        $this->PaySetLog("./PayLog","Api_ajPayIndexJqBankCard_Paycard__",json_encode($mybindbank)."------- 返回首次下单支付鉴权的银行卡(支付信用卡)信息 ------- ");

                        $ordersn = $obj['rt5_orderId'];
                        if(empty($mybindbank))
                        {
                            # tab card # 支付卡号
                            $myBind['card'] = M("money_detailed")->where(array('pt_ordersn'=>$ordersn))->getField('bank_cart');
                            $myBind['user_id'] = $pdata['user_id'];
                            $myBind['bindId'] = $bindId;
                            $myBind['retMsg'] = $obj['rt3_retMsg'];
                            $myBind['orderId'] = $ordersn;
                            $myBind['userId'] = $obj['rt14_userId'];
                            $myBind['retCode'] = $obj['rt2_retCode'];
                            $myBind['bindStatus'] = $obj['rt2_retCode'];
                            $myBind['type'] = 1; # 1支付卡2结算卡
                            $myBind['t'] = time();
                            M("mybank_bind")->add($myBind);

                            # 鉴权银行卡合利宝保存日志
                            $jqlog['bankid'] = $bindId;
                            $jqlog['json'] = '';
                            $jqlog['serial_number'] = '';
                            $jqlog['t'] = time();
                            $jqlog['user_id'] = $pdata['user_id'];
                            $jqlog['userId'] = $obj['rt14_userId'];
                            $jqlog['ordersn'] = $ordersn;
                            $jqlog['card'] = M("money_detailed")->where(array('pt_ordersn'=>$ordersn))->getField('bank_cart');
                            $jqlog['jq_td_id'] = 1; //1合利宝鉴权通道2松顺鉴权通道
                            M("jq_log")->add($jqlog);
                        }
                    }
                    return array(1,$pageContents);

                }

        }

    }


    #  ---------------------------------------------------------------- (第N次下单支付步骤操作)---------------------------------
    # 发送短信
    public function ajNPaySend($param=array(),$order=array())
    {
        import('Vendor.payPerson.HttpClient');
        $Client = new \HttpClient($param['ip']);
        $phone = $order['phone'];
        $signkey = $param['signkey_quickpay'];

        if ($phone <> '') {//校验必要参数值
            //表单值
            $P1_bizType =  "QuickPayBindPayValidateCode";  # 绑定卡变量名
            $P2_customerNumber =  $param["P2_customerNumber"];
            $P3_bindId =  $order["bindId"];
            $P4_userId =  $order['userId'];
            $P5_orderId =  $order['orderId'];
            $P6_timestamp =  $order['timestamp'];
            $P7_currency =  "CNY";  // 暂只支持人民币：CNY
            $P8_orderAmount =  $order['orderAmount'];
            $P9_phone =  $order['phone'];

            //构造签名串
            $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_bindId&$P4_userId&$P5_orderId&$P6_timestamp&$P7_currency&$P8_orderAmount&$P9_phone&$signkey";

            $sign=md5($signFormString);

            //构造请求参数
            $params = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_bindId'=>$P3_bindId,'P4_userId'=>$P4_userId,'P5_orderId'=>$P5_orderId,'P6_timestamp'=>$P6_timestamp,'P7_currency'=>$P7_currency,'P8_orderAmount'=>$P8_orderAmount,'P9_phone'=>$P9_phone,'sign'=>$sign);

//            $url = "http://test.trx.helipay.com/trx/quickPayApi/interface.action";
//            $url = "http://transfer.trx.helipay.com/trx/transfer/interface.action";
            $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";
            $pageContents = $Client->quickPost($url, $params);  //发送请求 send request

//            dump($pageContents);
//            dump($params);die;

            # 保存日志
            $this->PaySetLog("./PayLog","Api_ajNPaySend__",json_encode($params)."-------  第N次下单支付步骤操作 - 绑卡支付短信"."---------".$pageContents."------- \r\n");
//            $obj = json_decode($pageContents);
            return $pageContents;
        }
    }

    # 绑卡支付
    public function ajNPay($param=array(),$order=array())
    {
        import('Vendor.payPerson.HttpClient');
        $Client = new \HttpClient($param['ip']);
        $phone = $order['phone'];
        $signkey = $param['signkey_quickpay'];
        if ($phone <> '') {//校验必要参数值
            //表单值
            $P1_bizType =  "QuickPayBindPay";  # 绑定卡变量名
            $P2_customerNumber =  $param["P2_customerNumber"];
            $P3_bindId =  $order["bindId"];
            $P4_userId =  $order['userId'];
            $P5_orderId =  $order['orderId'];
            $P6_timestamp =  $order['timestamp'];
            $P7_currency =  "CNY";  // 暂只支持人民币：CNY
            $P8_orderAmount =  $order['orderAmount'];
            $P9_goodsName =  $order['goodsName'];
            $P10_goodsDesc =  $order['goodsDesc'];
            $P11_terminalType =  $order['terminalType'];
            $P12_terminalId =  $order['terminalId'];
            $P13_orderIp =  get_client_ip();
            $P14_period =  $order['period'];
            $P15_periodUnit =  $order['eriodUnit'];
            # 回调地址
            $hostUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'];
            $hostUrl = "https://wallet.insoonto.com";
            $returnURL = $hostUrl."/Payapi/PayReturn/quickpayReturn?czorder=".trim($order["czorder"]);  # 新增参数(充值订单)
            $P16_serverCallbackUrl =  $returnURL;  // 回调地址
            $P17_validateCode = $order['validateCode'];



            $P19_aptitudeCode = '';  # 资质编码

            //构造签名串
            $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_bindId&$P4_userId&$P5_orderId&$P6_timestamp&$P7_currency&$P8_orderAmount&$P9_goodsName&$P10_goodsDesc&$P11_terminalType&$P12_terminalId&$P13_orderIp&$P14_period&$P15_periodUnit&$P16_serverCallbackUrl&$signkey";

            $sign=md5($signFormString);

            # 有/无积分通道
            if($order['is_jifen'] == 1)
            {
                $P18_isIntegral = 'TRUE'; // 送积分通道 (最新新增参数)
                $P20_integralType = $order['jifen_type']; //积分类型

                //构造请求参数
                $params = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_bindId'=>$P3_bindId,'P4_userId'=>$P4_userId,'P5_orderId'=>$P5_orderId,'P6_timestamp'=>$P6_timestamp,'P7_currency'=>$P7_currency,'P8_orderAmount'=>$P8_orderAmount,'P9_goodsName' => $P9_goodsName,'P10_goodsDesc' => $P10_goodsDesc,'P11_terminalType' => $P11_terminalType,'P12_terminalId' => $P12_terminalId,'P13_orderIp' => $P13_orderIp,'P14_period' => $P14_period,'P15_periodUnit' => $P15_periodUnit,'P16_serverCallbackUrl' => $P16_serverCallbackUrl,'P17_validateCode'=>$P17_validateCode,'P18_isIntegral'=>$P18_isIntegral,'P19_aptitudeCode'=>$P19_aptitudeCode,'P20_integralType'=>$P20_integralType,'sign'=>$sign);

            }else{
                $P18_isIntegral = 'FALSE'; // 无积分通道 (最新新增参数)
//                $P20_integralType = ''; //积分类型

                //构造请求参数
                $params = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_bindId'=>$P3_bindId,'P4_userId'=>$P4_userId,'P5_orderId'=>$P5_orderId,'P6_timestamp'=>$P6_timestamp,'P7_currency'=>$P7_currency,'P8_orderAmount'=>$P8_orderAmount,'P9_goodsName' => $P9_goodsName,'P10_goodsDesc' => $P10_goodsDesc,'P11_terminalType' => $P11_terminalType,'P12_terminalId' => $P12_terminalId,'P13_orderIp' => $P13_orderIp,'P14_period' => $P14_period,'P15_periodUnit' => $P15_periodUnit,'P16_serverCallbackUrl' => $P16_serverCallbackUrl,'P17_validateCode'=>$P17_validateCode,'P18_isIntegral'=>$P18_isIntegral,'P19_aptitudeCode'=>$P19_aptitudeCode,'sign'=>$sign);
            }




//            $url = "http://test.trx.helipay.com/trx/quickPayApi/interface.action";
//            $url = "http://transfer.trx.helipay.com/trx/transfer/interface.action";
            $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";


            $pageContents = $Client->quickPost($url, $params);  //发送请求 send request



            # 保存日志
            $this->PaySetLog("./PayLog","Api_ajNPay__",json_encode($params)."-------  第N次下单支付步骤操作 - 绑卡支付"."---------".$pageContents."------- \r\n");
//            $obj = json_decode($pageContents);
            return array(1,$pageContents);
        }
    }
















    ## 银行卡列表
    # 鉴权绑卡短信
    public function ajJqSend($param=array(),$order="")
    {
        import('Vendor.payPerson.HttpClient');
        $Client = new \HttpClient($param['ip']);
        $signkey = $param['signkey_quickpay'];

        if ($order['phone'] <> '') {//校验必要参数值
            //表单值
            $P1_bizType =  "QuickPayBindCardValidateCode";  # 绑定卡变量名
            $P2_customerNumber =  $param["P2_customerNumber"];
            $P3_userId =  $order['userId'];
            $P4_orderId =  $order['orderId'];
            $P5_timestamp =  $order['timestamp'];
            $P6_cardNo =  $order['cardNo'];
            $P7_phone =  $order['phone'];
            $P8_isEncrypt = ''; # 银行卡信息参数是否加密

            //构造签名串
            $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_userId&$P4_orderId&$P5_timestamp&$P6_cardNo&$P7_phone&$signkey";

            $sign=md5($signFormString);

            //构造请求参数
            $params = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_userId'=>$P3_userId,'P4_orderId'=>$P4_orderId,'P5_timestamp'=>$P5_timestamp,'P6_cardNo'=>$P6_cardNo,'P7_phone'=>$P7_phone,'sign'=>$sign);

//            $url = "http://test.trx.helipay.com/trx/quickPayApi/interface.action";
//            $url = "http://transfer.trx.helipay.com/trx/transfer/interface.action";
            $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";
            $pageContents = $Client->quickPost($url, $params);  //发送请求 send request
//
//            dump($pageContents);
//            dump($params);die;

            # 保存日志
            $this->PaySetLog("./PayLog","Api_ajJqSend__",json_encode($params)."-------  银行卡鉴权绑定 (鉴权绑卡短信) -----"."---------".$pageContents."------- \r\n");
            $obj = json_decode($pageContents,true);

            # 返回是否成功，或者失败
            $res = array();
            if($obj)
            {
                if($obj['rt2_retCode'] == '0000')
                {
                    $res['ret_code'] = 1; # 成功
                }else{
                    $res['ret_code'] = 2; # 失败
                }
                $res['ret_msg'] = $obj['rt3_retMsg'];
            }else{
                $res['ret_code'] = 0; # 错误信息
                $res['ret_msg'] = '错误';
            }
            return $res;
        }
    }

    # 鉴权绑卡
    public function ajJqSubmit($param=array(),$order=array(),$pdata=array())
    {
        import('Vendor.payPerson.HttpClient');
        $Client = new \HttpClient($param['ip']);
        $signkey = $param['signkey_quickpay'];

        if ($order['phone'] <> '') {//校验必要参数值
            //表单值
            $P1_bizType =  "QuickPayBindCard";  # 绑定卡变量名
            $P2_customerNumber =  $param["P2_customerNumber"];
            $P3_userId =  $order['userId'];
            $P4_orderId =  $order['orderId']; # 固定自定义生成
            $P5_timestamp =  $order['timestamp'];
            $P6_payerName =  $order['payerName']; # 姓名
            $P7_idCardType =  $order['idCardType'];
            $P8_idCardNo =  $order['idCardNo'];
            $P9_cardNo =  $order['cardNo'];
//            # 信用卡
            $P10_year =  $order['year'];
            $P11_month =  $order['month'];
            $P12_cvv2 =  $order['cvv2'];
            $P13_phone =  $order['phone'];
            $P14_validateCode =  $pdata['validateCode']; # 验证码
            $P15_isEncrypt = ''; # 银行卡信息参数是否加密

            //构造签名串
            $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_userId&$P4_orderId&$P5_timestamp&$P6_payerName&$P7_idCardType&$P8_idCardNo&$P9_cardNo&$P10_year&$P11_month&$P12_cvv2&$P13_phone&$P14_validateCode&$signkey";

            $sign=md5($signFormString);

            //构造请求参数
            $params = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_userId'=>$P3_userId,'P4_orderId'=>$P4_orderId,'P5_timestamp'=>$P5_timestamp,'P6_payerName'=>$P6_payerName,'P7_idCardType'=>$P7_idCardType,'P8_idCardNo'=>$P8_idCardNo,'P9_cardNo'=>$P9_cardNo,'P10_year'=>$P10_year,'P11_month'=>$P11_month,'P12_cvv2'=>$P12_cvv2,'P13_phone'=>$P13_phone,'P14_validateCode'=>$P14_validateCode,'sign'=>$sign);

//            $url = "http://test.trx.helipay.com/trx/quickPayApi/interface.action";
//            $url = "http://transfer.trx.helipay.com/trx/transfer/interface.action";
            $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";
            $pageContents = $Client->quickPost($url, $params);  //发送请求 send request

//            dump($pageContents);
//            dump($params);die;

            # 保存日志
            $this->PaySetLog("./PayLog","Api_ajJqSubmit__",json_encode($params)."-------  银行卡鉴权绑定成功/失败返回请求参数"."---------".$pageContents."------- \r\n");
            $obj = json_decode($pageContents,true);

            # 返回是否成功，或者失败
            $res = array();
            if($obj)
            {
                # 添加鉴权次数
                $jqdata['bankid'] = trim($obj['rt10_bindId']);
                $jqdata['json'] = $pageContents;
                $jqdata['userId'] = trim($obj['rt5_userId']);
                $jqdata['json'] = $pageContents;
                $jqdata['ordersn'] = trim($obj['rt6_orderId']);
                $jqdata['serialn_number'] = trim($obj['rt11_serialNumber']);
                $jqdata['card'] = trim($order['cardNo']);
                $jqdata['phone'] = $order['phone'];
                $jqdata['user_id'] = $pdata['uid'];
                M("jq_log")->add($jqdata);

                if($obj['rt2_retCode'] == '0000')
                {
                    # 添加鉴权认证记录( --- o2o收款交易支付必须所要的bindId )

                    # 判断是否是信用卡(支付1)，结算卡(2)
                    if($order['cvv2'] != '')
                    {
                        $type = 1;
                    }else{
                        $type = 2;
                    }
                    $jqsubmit['type'] = $type;# 2结算/1支付
                    $jqsubmit['card'] = $order['cardNo'];
                    $jqsubmit['user_id'] = $pdata['user_id'];
                    $jqsubmit['bindId'] = trim($obj['rt10_bindId']);
                    $jqsubmit['bankid'] = trim($pdata['bankid']);
                    $jqsubmit['retMsg'] = trim($obj['rt3_retMsg']);
                    $jqsubmit['orderId'] = trim($obj['rt6_orderId']);
                    $jqsubmit['userId'] = trim($obj['rt5_userId']);
                    $jqsubmit['retCode'] = trim($obj['rt2_retCode']);
                    $jqsubmit['bindStatus'] = trim($obj['rt3_retMsg']);
                    $jqsubmit['json'] = $pageContents;
                    $jqsubmit['t'] = time();
                    M("mybank_bind")->add($jqsubmit);

                    # 我的银行卡认证成功返回信息
                    $svdata['jq_status'] = 3; # 绑定成功
                    $svdata['status'] = 1;
                    $svdata['u_t'] = time();
                    $svdata['nature'] = 2;
                    M("mybank")->where(array('user_id'=>$pdata['uid'],'cart'=>$order['cardNo']))->save($svdata);

                    $res['ret_code'] = 1; # 成功
                }else{
                    $res['ret_code'] = 2; # 失败
                }
                $res['ret_msg'] = $obj['rt3_retMsg'];
            }else{
                $res['ret_code'] = 0; # 错误信息
                $res['ret_msg'] = '错误';
            }
            return $res;
        }
    }


    # 绑卡结算
    public function ajPayBindCard($param=array(),$order=array())
    {
        import('Vendor.payPerson.HttpClient');
        $Client = new \HttpClient($param['ip']);

        $phone = $order['phone'];
        $signkey = $param['signkey_quickpay'];

        if ($phone <> '') {//校验必要参数值
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

//            dump($signFormString);
//            dump($params);

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

    #  商户提现接口  ---  商户提现(储蓄卡提现)
    public function ajPayJsCardDf($param=array(),$order=array())
    {
        import('Vendor.payPerson.HttpClient');
        $Client = new \HttpClient($param['ip']);

        //RSA私钥
//        $privatekey = "MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAK5GQSOPqzt7o4xcgygdikqN1uY13J7Uu0nJdm/BtxPH1Y1qolPUw/lSCd83f7KnS/xS/THCVEwvUm2iOtQKIDj2A/SC7Jy+bZbbbrJqkx+61pgjuIFsKo7Wf/2OX59Nj1qQlWa99J3ZH/kEFxKd5V1moV9cCNpBZVoEYyhmBbajAgMBAAECgYAIDqt4T24lQ+Qd2zEdK7B3HfOvlRHsLf2yvaPCKvyh531SGnoC0jV1U3utXE2FHwL+WX/nSwrGsvFmrDd4EjfHFsqRvHm+TJfXoHtmkfvbVGI7bFl/3NbYdi76tqbth6W8k0gkPUsACs2ix8a4K7zxOO+UpOeUBIXrchDxFmj9sQJBAOgSHQAI5hr/3+rSQXlq2lET87Ew9Ib72Lwqri3vsHO/sysVTLAznuA+V8s+a4tUeA839a/tGLp1SaJhvma9/30CQQDAPoNFw4rYTm9vbQnrCb6Mm0l9GNpCD1c4ShTxHJyt8Gql0e1Sl3vc28AxyqHLq66abYDzOpnPGJ8AIpri4qifAkArJsMRsJXoy089gJ8ADqhNjyIu/mVZfBbO1jjQ/dKXkzujdTBvSwnttGnqts6Ud75jRgp/Dd0dPpXUhcw7mnSZAkEAmYeTKP0Afr0tS6ymNhojHoHJz+kwLX+45VBspx51loghc+pSgRpPplOti1ZLnq+uktAPIrDTM0xzdxUr4zSm+wJAV54yRQsZBkRmhPibmLeoe3lM6hcAwLqS+E1H09X92fBLqCtBAybDnf++hT2ATgtW+xgI+tFVbOqbi1QbLyw1kA==";

        $privatekey = "MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBALOjm7Kw+w5lMN2Wvc8UTl76sz2y7Rikow/+d8aKOnHXHEhxgZpwHeMqfS5b55lplpCHjrwZamu4nA8/m0Q/ES9qRMQp4c5jdkBjTOkMNcOlns5Mti5/nLXxPq1Ka4wvWNKiZ0O9m2N/pU8X2sw68nyGn7Y44ghkbpmtbJUyVRltAgMBAAECgYAgKcz4w4NP4oJLSnAVoZcenlh1VZHp9aBUfsVHQPyR4Wfo+Jmx4x0WzUa4hDAFYchZfEvsFcjeHKGkgUj1gS08OUWRY3CW1cNL6EPtgzwAus3L+u1w+uWyk2zY0mSExHI5pCgKyrlgwjXeUVJRffx8yHZkmDy9VhjlvBfiR2+h6QJBANsB1R+blhitdb6/OjEfcM+bZGd5q11LHJbYhrF1/gOV6U7pdglOSlbYis2WDAJJmqdNVCIwQOnTptgztuV4a1MCQQDR+3JxgzbNB1N8+ypQeRFeGI8Zb2QwtAFDuNTjSDdcsmp5X166A3Sf/nRIgmNGRh8+l7HAGICvvwmWknv23JA/AkAsKTdvczEV8sw+VVMHmr5lroDVeKw8WKwAItMuL4uz72OnPN5HTBkjX/DFOc9cGrlrqOUhK7e7LqmDCRKFPP3vAkBneIYuVUAdy+xh+8ogGWhre6KYIAG41hqBaoTM8nsFXI2G/W3KL4W6iUJ3sHiG2mrvBwT56ZkQAQ0Se2BGhu01AkEAuphooslCNPsriGD/AKd4h/x39+x7xBkRprGDEN4DhhgeB0DPDh70ROP3f4+DX3ySl1BT9PiHes+0AIPL8nRBCw==";

        $P5_amount = $order['amount'];
//        $signkey=$param['signkey_quickpay'];

//        H1Fr9uZuzb1L7njorrznfVNog3XKuNfS    5qcCONK8KRUlLK8Ppr5cS3xm
        $signkey='KYPoorS53LVWLsPj4QlJKLPYeCVAKMwN';


        if ($P5_amount <> '') {//检查必要参数
            $P1_bizType = "MerchantWithdraw"; // 商户提现
            $P2_customerNumber =  $param["P2_customerNumber"];
            $P3_orderId =  $order['orderId'];
            $P4_amount =  $order['amount'];
            $P5_bankCode = $order['bankCode'];
            $P6_bankAccountNo = $order['bankAccountNo'];
            $P7_bankAccountName = $order['bankAccountName'];
            $P8_biz = $order['biz'];
            $P9_bankUnionCode = $order['bankUnionCode'];
            $P10_feeType = $order['feeType'];
            $P11_summary = $order['summary'];

            //构造签名串
            $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_orderId&$P4_amount&$P5_bankCode&$P6_bankAccountNo&$P7_bankAccountName&$P8_biz&$P9_bankUnionCode&$P10_feeType&$P11_summary";

//            &MerchantWithdraw&C1800001834&tx__20171114102333&1&CCB&6217003320015057576&蔡俊锋&B2C&&PAYER&测试提现
//            &MerchantWithdraw&C1800001834&tx__20171114105055&1&CCB&6217003320015057576&蔡俊锋&B2C&&PAYER&测试提现&KYPoorS53LVWLsPj4QlJKLPYeCVAKMwN


            import('Vendor.payPerson.Crypt_RSA');   // rsa 加密
            //获取加密报文
            $rsa = new \Crypt_RSA();
            $rsa->setHash('md5');
            $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
            $rsa->loadKey($privatekey);

            $sign = base64_encode($rsa->sign($signFormString));
//            $sign = "5qcCONK8KRUlLK8Ppr5cS3xm";

            //构造请求参数
            $params = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_orderId'=>$P3_orderId,'P4_amount'=>$P4_amount,'P5_bankCode'=>$P5_bankCode,'P6_bankAccountNo'=>$P6_bankAccountNo,'P7_bankAccountName'=>$P7_bankAccountName,'P8_biz'=>$P8_biz,'P9_bankUnionCode'=>$P9_bankUnionCode,'P10_feeType'=>$P10_feeType,'P11_summary'=>$P11_summary,'sign'=>$sign);


//            $url = "http://test.trx.helipay.com/trx/transfer/interface.action";
            $url = "http://transfer.trx.helipay.com/trx/transfer/interface.action";
//            $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";
            $pageContents = $Client->quickPost($url, $params);  //发送请求 send request

//            dump(CRYPT_RSA_SIGNATURE_PKCS1);
//            dump($signFormString);
//            dump($pageContents);
//            dump($params);die;


            # 保存日志 - 商户提现
            $this->PaySetLog("./PayLog","Api_ajPayJsCardDf__",json_encode($params)."-------  商户提现请求接口返回参数"."---------".$pageContents."------- \r\n");

            $obj = json_decode($pageContents,true);


            # 结算明细表
            $moneyjsde['user_id'] = $order['uid'];
            $moneyjsde['pt_ordersn'] = $P3_orderId;
            $moneyjsde['user_js_supply_id'] = $order['user_js_supply_id'];  # 结算通道
            $moneyjsde['js_money'] = $order['money']; # 结算金额
            $moneyjsde['tx_service'] = $order['tx_service'];
            $moneyjsde['rz_money'] = $P5_amount;
            $moneyjsde['sx_money'] = $order['sx_money'];
            $moneyjsde['serial_num'] = "";
            $moneyjsde['js_status'] = 1;

            $moneyjsde['type'] = 3; # 1余额结算/2分销结算/3收益结算
            $moneyjsde['js_card'] = $order['js_card']; # 结算卡号

            $moneyjsde['t'] = time();
            $moneyjsde['js_type'] = 1;
            M("moneyjs_detailed")->add($moneyjsde);  // 添加记录表

            $resarr = array();
            if($obj['rt2_retCode'] == "0000")
            {
                $resarr['code'] = "200";
                $resarr['msg'] = $obj['rt3_retMsg'];

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

                M("moneyjs_detailed")->where(array('pt_ordersn' => $P3_orderId))->save(array('before_money'=>$beforemoney,'after_money'=>$beforemoney - $order['money'],'js_status'=>2,'js_success'=>time()));  // 结算成功
                M("user")->where(array('user_id'=>$order['uid']))->save(array('money'=>$beforemoney - $order['money']));


                $mondata = array(
                    'user_id' => $order['uid'],
                    'msg' => '收益结算',
                    'money' => $order['money'],
                    'pn' => '-',  // + -
                    'ordersn' => $P3_orderId,
                    'type' => 1,  // 1收益结算3余额结算
                    'is_type' => 1  // 1收益余额结算2钱包余额结算3等其他
                );
                # 金额日志记录
                R("Func/Money/userMoneyLog",array($mondata));

            }else{
                $resarr['code'] = "400";
                $resarr['msg'] = $obj['rt3_retMsg'];
                M("moneyjs_detailed")->where(array('pt_ordersn' => $P3_orderId))->save(array('js_status'=>3));  // 结算失败
            }

            return $resarr;
        }
    }


    #  商户提现接口  ---  结算卡提现(同名进出)
    public function ajPayJsCard($param=array(),$order=array())
    {
        import('Vendor.payPerson.HttpClient');
        $Client = new \HttpClient($param['ip']);

        //RSA私钥 - 测试版
//        $privatekey = "MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAK5GQSOPqzt7o4xcgygdikqN1uY13J7Uu0nJdm/BtxPH1Y1qolPUw/lSCd83f7KnS/xS/THCVEwvUm2iOtQKIDj2A/SC7Jy+bZbbbrJqkx+61pgjuIFsKo7Wf/2OX59Nj1qQlWa99J3ZH/kEFxKd5V1moV9cCNpBZVoEYyhmBbajAgMBAAECgYAIDqt4T24lQ+Qd2zEdK7B3HfOvlRHsLf2yvaPCKvyh531SGnoC0jV1U3utXE2FHwL+WX/nSwrGsvFmrDd4EjfHFsqRvHm+TJfXoHtmkfvbVGI7bFl/3NbYdi76tqbth6W8k0gkPUsACs2ix8a4K7zxOO+UpOeUBIXrchDxFmj9sQJBAOgSHQAI5hr/3+rSQXlq2lET87Ew9Ib72Lwqri3vsHO/sysVTLAznuA+V8s+a4tUeA839a/tGLp1SaJhvma9/30CQQDAPoNFw4rYTm9vbQnrCb6Mm0l9GNpCD1c4ShTxHJyt8Gql0e1Sl3vc28AxyqHLq66abYDzOpnPGJ8AIpri4qifAkArJsMRsJXoy089gJ8ADqhNjyIu/mVZfBbO1jjQ/dKXkzujdTBvSwnttGnqts6Ud75jRgp/Dd0dPpXUhcw7mnSZAkEAmYeTKP0Afr0tS6ymNhojHoHJz+kwLX+45VBspx51loghc+pSgRpPplOti1ZLnq+uktAPIrDTM0xzdxUr4zSm+wJAV54yRQsZBkRmhPibmLeoe3lM6hcAwLqS+E1H09X92fBLqCtBAybDnf++hT2ATgtW+xgI+tFVbOqbi1QbLyw1kA==";

//        $publickey = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCzo5uysPsOZTDdlr3PFE5e+rM9su0YpKMP/nfGijpx1xxIcYGacB3jKn0uW+eZaZaQh468GWpruJwPP5tEPxEvakTEKeHOY3ZAY0zpDDXDpZ7OTLYuf5y18T6tSmuML1jSomdDvZtjf6VPF9rMOvJ8hp+2OOIIZG6ZrWyVMlUZbQIDAQAB";
        
        $privatekey="MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBALOjm7Kw+w5lMN2Wvc8UTl76sz2y7Rikow/+d8aKOnHXHEhxgZpwHeMqfS5b55lplpCHjrwZamu4nA8/m0Q/ES9qRMQp4c5jdkBjTOkMNcOlns5Mti5/nLXxPq1Ka4wvWNKiZ0O9m2N/pU8X2sw68nyGn7Y44ghkbpmtbJUyVRltAgMBAAECgYAgKcz4w4NP4oJLSnAVoZcenlh1VZHp9aBUfsVHQPyR4Wfo+Jmx4x0WzUa4hDAFYchZfEvsFcjeHKGkgUj1gS08OUWRY3CW1cNL6EPtgzwAus3L+u1w+uWyk2zY0mSExHI5pCgKyrlgwjXeUVJRffx8yHZkmDy9VhjlvBfiR2+h6QJBANsB1R+blhitdb6/OjEfcM+bZGd5q11LHJbYhrF1/gOV6U7pdglOSlbYis2WDAJJmqdNVCIwQOnTptgztuV4a1MCQQDR+3JxgzbNB1N8+ypQeRFeGI8Zb2QwtAFDuNTjSDdcsmp5X166A3Sf/nRIgmNGRh8+l7HAGICvvwmWknv23JA/AkAsKTdvczEV8sw+VVMHmr5lroDVeKw8WKwAItMuL4uz72OnPN5HTBkjX/DFOc9cGrlrqOUhK7e7LqmDCRKFPP3vAkBneIYuVUAdy+xh+8ogGWhre6KYIAG41hqBaoTM8nsFXI2G/W3KL4W6iUJ3sHiG2mrvBwT56ZkQAQ0Se2BGhu01AkEAuphooslCNPsriGD/AKd4h/x39+x7xBkRprGDEN4DhhgeB0DPDh70ROP3f4+DX3ySl1BT9PiHes+0AIPL8nRBCw==";


//        &SettlementCardWithdraw&C1800001834&914569672197&tx__20171114054751&1&PAYER&测试
//        $signkey = "KYPoorS53LVWLsPj4QlJKLPYeCVAKMwN"; // 代付密钥

        $P5_amount = $order['amount'];
        if ($P5_amount <> '') {//检查必要参数
            $P1_bizType = "SettlementCardWithdraw";
            $P2_customerNumber =  $param["P2_customerNumber"];
            $P3_userId =  $order["userId"];
            $P4_orderId =  $order['orderId'];
            $P5_amount =  $order['amount'];
            $P6_feeType =  $order['feeType'];
            $P7_summary =  $order['summary'];
            $P8_bindId =  $order['bindId'];



            //构造签名串
            $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_userId&$P4_orderId&$P5_amount&$P6_feeType&$P7_summary";

            import('Vendor.payPerson.Crypt_RSA');
            //获取加密报文
            $rsa = new \Crypt_RSA();
            $rsa->setHash('md5');
            $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
            $rsa->loadKey($privatekey);
            $sign = base64_encode($rsa->sign($signFormString));

//            dump($sign);die;

            //构造请求参数   新增绑卡ID值(不参与签名)
            $params = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_userId'=>$P3_userId,'P4_orderId'=>$P4_orderId,'P5_amount'=>$P5_amount,'P6_feeType'=>$P6_feeType,'P7_summary'=>$P7_summary,'P8_bindId'=>$P8_bindId,'sign'=>$sign);


            $this->PaySetLog("./PayLog","Api_returnJs__",json_encode($params)."----------\r\n");


            $url = "http://transfer.trx.helipay.com/trx/transfer/interface.action";
//            $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";
            $pageContents = $Client->quickPost($url, $params);  //发送请求 send request

//
//            dump($signFormString);
//            dump($params);
//            dump($pageContents);
//            die;

            # 保存日志
            $this->PaySetLog("./PayLog","Api_ajPayJsCard__",json_encode($params)."-------  结算提现数据请求返回"."---------".$pageContents."------- \r\n");

            $obj = json_decode($pageContents,true);

            # 添加结算日志表
            M("crepayjs_log")->where(array("ordersn"=>$P4_orderId))->save(array(
                'amount'=>$P5_amount
            ));

            # 结算明细表
            $moneyjsde['user_id'] = $order['uid'];
            $moneyjsde['userId'] = $P3_userId;
            $moneyjsde['pt_ordersn'] = $P4_orderId;
            $moneyjsde['relation_order'] = trim($order['relation_order']);
            $moneyjsde['user_js_supply_id'] = $order['user_js_supply_id'];  # 结算通道
            $moneyjsde['js_money'] = $order['js_money']; # 结算金额
            $moneyjsde['tx_service'] = $order['tx_service'];
            $moneyjsde['rz_money'] = $P5_amount;
            $moneyjsde['sx_money'] = $order['sx_money'];
            $moneyjsde['serial_num'] = "";
            $moneyjsde['js_status'] = 3;

            $moneyjsde['type'] = trim($order['type']); # 1余额结算/2分销结算
            $moneyjsde['js_card'] = $order['js_card']; # 结算卡号

            $moneyjsde['t'] = time();
            $moneyjsde['js_type'] = 1;
            $mdetail = M("moneyjs_detailed")->where(array('pt_ordersn'=>$moneyjsde['pt_ordersn']))->find();
            if(!$mdetail)
            {
                M("moneyjs_detailed")->add($moneyjsde);
            }

            if ($obj['rt2_retCode'] == "0000") {
                M("crepayjs_log")->where(array("ordersn"=>$obj['rt6_orderId']))->save(array(
                    'order_status'=>'SUCCESS',
                    'serial_number'=>$obj['rt7_serialNumber'],
                    'wc_t' => time()
                ));

                # 结算后的金额 / 结算前的金额
                $beforemoney = M("user")->where(array('user_id'=> trim($order['uid'])))->getField('money');
                M("moneyjs_detailed")->where(array("pt_ordersn"=>$obj['rt6_orderId']))->save(array(
                    'js_status' => 2,
                    'type' => 2,
                    'before_money' => $beforemoney, # 结算前的金额
                    'after_money' => $beforemoney + $P5_amount, # 结算后的金额
                    'order_status'=>'SUCCESS',
                    'order_msg'=>$obj['rt3_retMsg'],
                    'j_succee_time' => time(),
                    'serial_num'=>$obj['rt7_serialNumber']
                ));

            }else{
                M("moneyjs_detailed")->where(array("pt_ordersn"=>$obj['rt6_orderId']))->save(array(
                    'js_status'=>3,
                    'order_status'=>$obj['rt2_retCode'],
                    'order_msg'=>$obj['rt3_retMsg'],
                ));
            }
            return array(1,$pageContents);
        }
    }


    #  解绑银行卡 - 信用卡/储蓄卡
    # 目前只支持合利宝接口
    # 请使用order数组方法进行传参
    /*
     * $order = array(
            'userId' => '717273976188', // 用户ID (按照手机号码)
            'bindId' => 'f82875d66ddd41c19fa32e09a4866141',  (绑卡生成的绑卡ID)
            'orderId' => '', // ymbank__20171225055403 (订单号)
            'timestamp' => '20171225055403'  (时间戳)
        );
     */
    public function BankCardUnbind($order=array())
    {
        $param = C("KJPAY");
        import('Vendor.payPerson.HttpClient');
        $Client = new \HttpClient($param['ip']);
        $signkey_quickpay = $param['signkey_quickpay'];//密钥key

//        $orderId = 'bc_'.date("YmdHis",time());
//        $timestamp = date("YmdHis",time());
//        $order = array(
//            'userId' => '13790957780',
//            'bindId' => '9f0d39f18d9c466d83524494a8e3a5f9',
//            'orderId' => $orderId,
//            'timestamp' => $timestamp
//        );

        if ($param['ip'] <> '') {//检查必要参数
            $P1_bizType = "BankCardUnbind";
            $P2_customerNumber = $param["P2_customerNumber"];
            $P3_userId = $order["userId"];
            $P4_bindId = $order['bindId'];
            $P5_orderId = $order['orderId'];
            $P6_timestamp = $order['timestamp'];

            //构造签名串
            $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_userId&$P4_bindId&$P5_orderId&$P6_timestamp&$signkey_quickpay";
            $sign = md5($signFormString);//MD5签名
//            dump($sign);die;

            //构造请求参数   新增绑卡ID值(不参与签名)
            $params = array('P1_bizType' => $P1_bizType, 'P2_customerNumber' => $P2_customerNumber, 'P3_userId' => $P3_userId, 'P4_bindId' => $P4_bindId, 'P5_orderId' => $P5_orderId, 'P6_timestamp' => $P6_timestamp,'sign' => $sign);
//            $url = "http://transfer.trx.helipay.com/trx/transfer/interface.action";
            $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";
            $pageContents = $Client->quickPost($url, $params);  //发送请求 send request

            //支付和绑卡等用：http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action
            //提现用：http://transfer.trx.helipay.com/trx/transfer/interface.action
            //http://transfer.trx.helipay.com/trx/transfer/interface.action

            # 保存日志
            $this->PaySetLog("./PayLog", "jiebank__", json_encode($params) . " -------  解绑请求参数 " . "---------" . $pageContents . "\r\n");

            $obj = json_decode($pageContents,true);

//            {"rt2_retCode":"0000","sign":"ef7cacdcd79ff6bad768542d51e3d556","rt1_bizType":"BankCardUnbind","rt4_customerNumber":"C1800001834","rt3_retMsg":"成功"}

            # 解绑日志记录
            if($obj['rt2_retCode'] == "0000")
            {
                # save解绑 .... code

            }
            return $obj;
        }
    }

    # 解绑卡
    public function BankCardUnbindceshi()
    {
        $param = C("KJPAY");
        import('Vendor.payPerson.HttpClient');
        $Client = new \HttpClient($param['ip']);
        $signkey_quickpay = $param['signkey_quickpay'];//密钥key


        $orderId = 'HLBJB'.date("YmdHis",time());
        $timestamp = date("YmdHis",time());


        $order = array(
            'userId' => '13828136102',
            'bindId' => '8d7bba9f17804b3faf6958e87796fc36',
            'orderId' => $orderId,
            'timestamp' => $timestamp
        );

        if ($param['ip'] <> '') {//检查必要参数
            $P1_bizType = "BankCardUnbind";
            $P2_customerNumber = $param["P2_customerNumber"];
            $P3_userId = $order["userId"];
            $P4_bindId = $order['bindId'];
            $P5_orderId = $order['orderId'];
            $P6_timestamp = $order['timestamp'];

            //构造签名串
            $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_userId&$P4_bindId&$P5_orderId&$P6_timestamp&$signkey_quickpay";
            $sign = md5($signFormString);//MD5签名
//            dump($sign);die;

            //构造请求参数   新增绑卡ID值(不参与签名)
            $params = array('P1_bizType' => $P1_bizType, 'P2_customerNumber' => $P2_customerNumber, 'P3_userId' => $P3_userId, 'P4_bindId' => $P4_bindId, 'P5_orderId' => $P5_orderId, 'P6_timestamp' => $P6_timestamp,'sign' => $sign);
//            $url = "http://transfer.trx.helipay.com/trx/transfer/interface.action";
            $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";
            $pageContents = $Client->quickPost($url, $params);  //发送请求 send request

        //支付和绑卡等用：http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action
        //提现用：http://transfer.trx.helipay.com/trx/transfer/interface.action
        //http://transfer.trx.helipay.com/trx/transfer/interface.action

            # 保存日志
            $this->PaySetLog("./PayLog", "jiebank__", json_encode($params) . " -------  解绑请求参数 " . "---------" . $pageContents . "\r\n");

            $obj = json_decode($pageContents,true);

            echo '--测试解绑';
            dump($obj);
//            {"rt2_retCode":"0000","sign":"ef7cacdcd79ff6bad768542d51e3d556","rt1_bizType":"BankCardUnbind","rt4_customerNumber":"C1800001834","rt3_retMsg":"成功"}

            # 解绑日志记录
//            if($obj['rt2_retCode'] == "0000")
//            {
//                # save解绑 .... code
//                M("mybank_bind")->where(array('userId'=>$order['userId'],'bindId'=>$order['bindId']))->delete();
//            }
            return $obj; //
        }
    }

    # 快捷支付-翰银支付接口
    # $param = 验证参数
    # ---------------------------------------------------------------------------------------
    # ##
    public function hyPay()  // 消费接口（前台类）
    {
        /* 测试数据
        *
       ---------------------------------------------------------------------------------------------------
       |卡号	                卡性质	机构名称	手机号码	    密码	    CVN2    有效期	证件号	            姓名 |
       ---------------------------------------------------------------------------------------------------
        6216261000000000018	借记卡	平安银行	13552535506	123456			        341126197709218366	全渠道
        6221558812340000	贷记卡	平安银行	13552535506	123456	123	    1711	341126197709218366	互联网
        网关、WAP短信验证码	111111	控件短信验证码	123456
        */
        $param = C("HYPAY");

        $realname = array();

        # 回调地址
        $hostUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'];
        $hostUrl = "https://wallet.insoonto.com";
        $returnWapURL = $hostUrl."/Payapi/PayReturn/hypayWapReturn";
        $returnURL = $hostUrl."/Payapi/PayReturn/hypayReturn";


        $orderNo = 'hp_'.date("YmdHis",time());
        $transDate = date("Ymd",time());
        $orderTime = date('Ymdhis',time());
        $transAmount = "1"; // 金额
        $payType = '2008';
        $productType = 'nocard';
        $accName = '互联网';
        $idNum = '341126197709218366';
        $accNum = '6221558812340000';
        $telNo = '13552535506';
        $version = '1.0.0';
        $reserved = "{'goodsDesc':'商品描述', 'attach':'附件'}";

        $signature = "$param[insMerchantCode]&$param[merCode]&$orderNo&$transDate&$orderTime&$transAmount&$payType&$productType&$accName&$idNum&$accNum&$telNo&version& $reserved&$param[signKey]";

        $res = array(
            'insCode' => $param['insMerchantCode'],
            'cfMerCode' => $param['merCode'],
            'orderNo' => $orderNo,  // 订单号
            'transDate' => $transDate,
            'orderTime' => $orderTime,
            'currencyCode' => '156',
            'transAmount' => $transAmount, // 订单金额
            'payType' => $payType,  // 2008  无卡支付 2009  微信公众号  2010  支付宝服务窗支付
            'productType' => $productType,  // nocard:无卡支付  mobilepay:移动支付
            'accName' => $accName,
            'idNum' => $idNum,
            'accNum' => $accNum,
            'telNo' => $telNo,
            'frontUrl' => $returnWapURL,   // 前台通知地址 (可为空)
            'backUrl' => $returnURL,   // 后台回调通知地址
            'version' => $version,
            'reserved' => $reserved, // 保留域，附带参数
            'signature' => MD5($signature)
        );

        $this->assign('res',$res);
        $this->display('HyPay');  // 用h5页面(必用form跳转)
    }

    # 快捷支付 - 智付(在线支付)
    public function ajZhiFu($pdata = array())
    {
        import('Vendor.payZhiFu.zfPay');
        $config = C("ZHIFUPAY");
        $config['ip'] = "39.108.57.53";
        $ZfPay = new \zfPay($config);

        $res = $ZfPay->submitPay($pdata);

        R("Payapi/Api/PaySetLog",array("./PayLog","Api_ajZhiFu_",'---- 智付请求参数 ----'.json_encode($pdata)));
        R("Payapi/Api/PaySetLog",array("./PayLog","Api_ajZhiFu_",'---- 智付支付返回参数 ----'.json_encode($res)));
        return $res;
    }

    # 快捷支付 - 智付()
    /**
     * 签名  生成签名串  基于sha1withRSA
     * @param string $data 签名前的字符串
     * @return string 签名串
     */
    public function setsign($data) {
        $certs = array();
        openssl_pkcs12_read(file_get_contents("你的.pfx文件路径"), $certs, "password"); //其中password为你的证书密码
        if(!$certs) return ;
        $signature = '';
        openssl_sign($data, $signature, $certs['pkey']);
        return base64_encode($signature);
    }

    /**
     * 验签  验证签名  基于sha1withRSA
     * @param $data 签名前的原字符串
     * @param $signature 签名串
     * @return bool
     * @link www.zh30.com
     */
    public function setverify($data, $signature) {
        $certs = array();
        openssl_pkcs12_read(file_get_contents("你的.pfx文件路径"), $certs,  "password");
        if(!$certs) return ;
        $result = (bool) openssl_verify($data, $signature, $certs['cert']); //openssl_verify验签成功返回1，失败0，错误返回-1
        return $result;
    }

    # ##########################################   案例 (只编写几个方法案例，其他具体看实际情况做)
    #################################################################################################################################
    # 快捷支付 - 合利宝同人进出接口（信用卡，银行支付卡）
    #  - 正式阶段 案例（请勿每次调用）
    # - 绑卡支付接口  （第一步）
    public function quickpay()
    {
        $use = 1;
        if($use==0)
        {
            echo '摩擦失措，请重新来!';
            exit;
        }
        $cfg = array(
            'ip' => "39.108.57.53",  // 绑定的IP地址
            'signkey_quickpay' => 'aFn0C3OTNYFAiKIK842uKt4kU58HueRL', // 密钥
            'userId' => rand("000000000000","999999999999")   // 用户ID随机数(暂时使用，正式则不使用该编号，请在数据表新增随机信用卡编号)
        );

        import('Vendor.payPerson.HttpClient');
        $Client = new \HttpClient($cfg['ip']);

        if($_POST)
        {
            $P4_orderId="p_".date('Ymdhis',time());//订单号
            $P5_timestamp=date('Ymdhis',time());//时间戳
            $phone = $_POST["P13_phone"];
            $money = $_POST["P15_orderAmount"];

            if($phone<>"" && $money <> ""){//判断必要参数非空

                //获取form表单参数
                $P1_bizType =  $_POST["P1_bizType"];
                $P2_customerNumber =  $_POST["P2_customerNumber"];
                $P3_userId =  $cfg['userId'];
                $P4_orderId =  $P4_orderId;
                $P5_timestamp = $P5_timestamp;
                $P6_payerName =  $_POST["P6_payerName"];
                $P7_idCardType =  $_POST["P7_idCardType"];
                $P8_idCardNo =  $_POST["P8_idCardNo"];
                $P9_cardNo =  $_POST["P9_cardNo"];
                $P10_year =  $_POST["P10_year"];
                $P11_month =  $_POST["P11_month"];
                $P12_cvv2 =  $_POST["P12_cvv2"];
                $P13_phone =  $_POST["P13_phone"];
                $P14_currency =  $_POST["P14_currency"];
                $P15_orderAmount =  $_POST["P15_orderAmount"];
                $P16_goodsName =  $_POST["P16_goodsName"];
                $P17_goodsDesc =  $_POST["P17_goodsDesc"];
                $P18_terminalType =  $_POST["P18_terminalType"];
                $P19_terminalId =  $_POST["P19_terminalId"];

//                $P25_isIntegral = 'TRUE'; // 送积分通道 (最新新增参数)

                # 下单IP地址
                $P20_orderIp =  $cfg['ip'];

                $P21_period =  $_POST["P21_period"];
                $P22_periodUnit =  $_POST["P22_periodUnit"];


                # 回调地址
                $hostUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'];
                $returnURL = $hostUrl."/Payapi/PayReturn/quickpayReturn";
                $P23_serverCallbackUrl =  $returnURL;  // 回调地址

                $signkey_quickpay = $cfg['signkey_quickpay'];//密钥key

                //构造签名字符串
                $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_userId&$P4_orderId&$P5_timestamp&$P6_payerName&$P7_idCardType&$P8_idCardNo&$P9_cardNo&$P10_year&$P11_month&$P12_cvv2&$P13_phone&$P14_currency&$P15_orderAmount&$P16_goodsName&$P17_goodsDesc&$P18_terminalType&$P19_terminalId&$P20_orderIp&$P21_period&$P22_periodUnit&$P23_serverCallbackUrl&$signkey_quickpay";


                $sign = md5($signFormString);//MD5签名

                # 测试请求接口地址
                $url = "https://test.trx.helipay.com/trx/quickPayApi/interface.action";//网银请求的页面地址
                # 生产请求接口地址
//                http://pay.trx.helipay.com/trx/quickPayApi/interface.action

                //post的参数
                $params = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_userId'=>$P3_userId,'P4_orderId'=>$P4_orderId,'P5_timestamp'=>$P5_timestamp,'P6_payerName'=>$P6_payerName,'P7_idCardType'=>$P7_idCardType,'P8_idCardNo'=>$P8_idCardNo,'P9_cardNo'=>$P9_cardNo,'P10_year'=>$P10_year,'P11_month'=>$P11_month,'P12_cvv2'=>$P12_cvv2,'P13_phone'=>$P13_phone,'P14_currency'=>$P14_currency,'P15_orderAmount'=>$P15_orderAmount,'P16_goodsName'=>$P16_goodsName,'P17_goodsDesc'=>$P17_goodsDesc,'P18_terminalType'=>$P18_terminalType,'P19_terminalId'=>$P19_terminalId,'P20_orderIp'=>$P20_orderIp,'P21_period'=>$P21_period,'P22_periodUnit'=>$P22_periodUnit,'P23_serverCallbackUrl'=>$P23_serverCallbackUrl,'sign'=>$sign);


                //调用支付请求
                $pageContents = $Client->quickPost($url, $params);  //发送请求 send request

//                echo "back msg:".$pageContents."<br/>";  //返回的结果   The returned result
//                exit;
//                dump($pageContents);die;

                //解析返回报文,成功才跳转到验证码支付页面
                $obj = json_decode($pageContents);
                if ($obj->rt2_retCode == "0000") {
                    $url="/Payapi/Api/quickpaySend";
                    echo "<script language='javascript'>";
                    echo "location='".$url."?P2_customerNumber=$P2_customerNumber&P4_orderId=$P4_orderId&P5_timestamp=$P5_timestamp&P13_phone=$P13_phone'";
                    echo "</script>";
                    exit;
                }
                else{
                    echo "$obj->rt3_retMsg";exit;
                }
            }else{
                echo '请填写必要参数';exit;
            }
        }


        # 测试 ‘Jeffery’ 的信用卡
        /*
            {
                "rt2_retCode":"0000",
                "sign":"330ac3fa40e5280d99f33cf68ff8692d",
                "rt1_bizType":"QuickPayBankCardPay",
                "rt5_orderId":"p_20171102101022",
                "rt4_customerNumber":"C1800001108",
                "rt3_retMsg":"成功"
            }
        */

        # 生成随机用户ID
        $this->assign("userId",$cfg['userId']);
        $this->display('quickpay');
    }


    # 快捷支付 - 合利宝同人进出接口（信用卡，银行支付卡）
    #  - 正式阶段 案例（请勿每次调用）
    # - 短信验证接口  （下一步）
    public function quickpaySend()
    {
        $use = 1;
        if ($use == 0) {
            echo '摩擦失措，请重新来!';
            exit;
        }
        error_reporting(E_ALL ^ E_NOTICE);

        $cfg = array(
            'ip' => "39.108.57.53",
            'signkey_quickpay' => 'aFn0C3OTNYFAiKIK842uKt4kU58HueRL',
        );

        import('Vendor.payPerson.HttpClient');
        $Client = new \HttpClient($cfg['ip']);

        $P1_bizType='QuickPaySendValidateCode';
        $cusNum = trim($_GET['P2_customerNumber']);
        $orderId = trim($_GET['P4_orderId']);
        $timestamp = trim($_GET['P5_timestamp']);
        $phone = trim($_GET['P13_phone']);
        $code = trim($_POST['P5_validateCode']);
        $ip = $cfg['ip'];
        $signkey=$cfg['signkey_quickpay'];


        //发送验证码的签名串
        $orinMessage = "&QuickPaySendValidateCode&$cusNum&$orderId&$timestamp&$phone&$signkey";
        $sign = md5($orinMessage);

        if($_POST)
        {
            $cusNum = trim($_POST['P2_customerNumber']);
            $orderId = trim($_POST['P3_orderId']);
            $timestamp = trim($_POST['P4_timestamp']);
            $phone = trim($_POST['P13_phone']);
            if($code <> ""){//检查必要参数

                $url = "https://test.trx.helipay.com/trx/quickPayApi/interface.action";//网银请求的页面地址
                # 生产请求接口地址
    //                http://pay.trx.helipay.com/trx/quickPayApi/interface.action

                //支付MD5的签名串
                $orinSendMessage = "&QuickPayConfirmPay&$cusNum&$orderId&$timestamp&$code&$ip&$signkey";
                $sendSign = md5($orinSendMessage);

                //支付请求参数
                $params=array('P1_bizType'=>'QuickPayConfirmPay','P2_customerNumber'=>$cusNum,
                    'P3_orderId'=>$orderId,'P4_timestamp'=>$timestamp,'P5_validateCode'=>$code,'P6_orderIp'=>$ip,'sign'=>$sendSign);

                //调用支付请求
                $pageContents = $Client->quickPost($url, $params);  //发送请求 send request
                echo "back msg:".$pageContents."<br/>";  //返回的结果   The returned result
            }
        }



        # 测试 ‘Jeffery’ 的信用卡
//        {
//            "rt10_bindId":"5b4fae129f3342c78709f7808f7dfc5b",
//            "sign":"2ba71ceec4c6e32d04ca00380ae4df02",
//            "rt1_bizType":"QuickPayConfirmPay",
//            "rt9_orderStatus":"FAILED",
//            "rt6_serialNumber":"QUICKPAY171102102644M3DR",
//            "rt14_userId":"220000000003",
//            "rt2_retCode":"8000",
//            "rt12_onlineCardType":"CREDIT",
//            "rt11_bankId":"CMBCHINA",
//            "rt13_cardAfterFour":"3874",
//            "rt5_orderId":"p_20171102102644",
//            "rt4_customerNumber":"C1800001108",
//            "rt8_orderAmount":"0.11",
//            "rt3_retMsg":"认证失败，支付卡已超过有效期",
//            "rt7_completeDate":"2017-11-02 10:41:22"
//        }

        $this->assign('cusNum',$cusNum);
        $this->assign('orderId',$orderId);
        $this->assign('timestamp',$timestamp);
        $this->assign('phone',$phone);
        $this->assign('P1_bizType',$P1_bizType);
        $this->assign('sign',$sign);
        $this->display('quickpaySend');
    }


    # 四要素认证接口 - 正式阶段 ( 请勿每次都调用，还未做限制，慎重使用，一次4毛钱)
    public function eachbankrz()
    {
        $use = 0;
        if($use==0)
        {
            echo '摩擦失措，请重新来!';
            exit;
        }
        import('Vendor.shiJianRz.Rz');
        $cfg=array(
            'keyStr'=>'shi_jian_zx_happy1234567',
            'publicKey'=>'credoo_stg.pem',
//            '3deskey'=>'shi_jian_zx_happy1234567',
            'app_privateKey'=>'test_private_stg.pem',
            'app_publicKey'=>'test_public_stg.pem',
            'app_keySecret'=>'test_sjzx_stg',
            'user_name'=>'exam',
            'pwd'=>'thisisatest',
        );
        $header=array(
            'transNo'=>get_timeHm(),
            'transDate'=>'2017-11-32 12:24:35',
            'orgId'=>'10018',
            'ukey'=>'exam',
            'url'=>array(
                'module'=>'product',
                'action'=>'single',
                'apiVer'=>'v1',
//                'do'=>'bankrz'
            )
        );

        $client_request_data=array(
            'header'=>$header,
            'busiData'=>array('productId'=>'PRT000007',
                'records'=>array('bankPreMobile'=>'13112157790','reasonCode'=>'01','name'=>'蔡俊锋','idNo'=>'445221199702234558','selectName'=>'0','idType'=>'0','entityAuthCode'=>'20171011320533','entityAuthDate'=>'2017-11-11 17:11:11','accountNo'=>'6217003320015057576')
            ));
        $Rz = new \Rz();
        $res = $Rz->sa($client_request_data,$cfg);
        dump($res);die;
    }


    # ##########################################   所有支付公共方法
    #################################################################################################################################

    # 保存支付日志
    public function PaySetLog($is_dir="./PayLog",$cname="HLBQP",$txt="")
    {
        if(!is_dir($is_dir)) {
            mkdir($is_dir);
        }elseif(!is_writeable($is_dir)) {
            header('Content-Type:text/html; charset=utf-8');
            exit('目录 [ '.$is_dir.' ] 不可写！');
        }
        $sh = fopen($is_dir."/".$cname.date("Ymd").".txt","a");
        fwrite($sh, $txt);
        fclose($sh);
        return true;
    }
    //四要素认证
    public function fourElement(){
        //携带的公共参数    按顺序排序
        $parem=array(
            'serviceCode'=>'SMRZ_SRV',
            'version'=>'1.0',
            'accCode'=>123,//注册方的账户ID
            'accessKeyId'=>123,//用户申请的密钥
                               //加密 signature 签名
            'timestamp'=>microtime(), //当前时间戳的微秒数
        );
        $order = array(
            'requestId'=>get_timeHm(), //商户流水号（唯一），不超过32位
            'serviceCode'=>'303',  //认证模式
            'bankCard'=>'6212263602105815484',//银行卡号
            'name'=>'曾加涛',//姓名
            'idNumber'=>'431024199901260073',//身份证号码
            'mobile'=>'13548750126',//手机号码
            'merchantId'=>'123',//商户号
        );

    }

    /**

     * 通过CURL发送HTTP请求

     * @param string $url  //请求URL

     * @param array $postFields //请求参数

     * @return mixed

     */

    private function curlPost($url,$postFields){

        $postFields = http_build_query($postFields);

        $ch = curl_init ();

        curl_setopt ( $ch, CURLOPT_POST, 1 );

        curl_setopt ( $ch, CURLOPT_HEADER, 0 );

        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );

        curl_setopt ( $ch, CURLOPT_URL, $url );

        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postFields );

        $result = curl_exec ( $ch );

        curl_close ( $ch );

        return $result;

    }


    # 删除测试数据
    public function delMybankBind()
    {
    }
}