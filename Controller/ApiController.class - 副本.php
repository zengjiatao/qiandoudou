<?php
# 银钱包对接接口 - 所有第三方API接口对接
# one: 四要素认证接口
# two: 快捷支付接口 - 合利宝同人进出接口（信用卡，银行支付卡）
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
    # ##
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
            $P4_orderId="creadition__".date('Ymdhis',time());//订单号
            $P5_timestamp=date('Ymdhis',time());//时间戳
            $phone = trim($myBankInfo["P13_phone"]);
            $money = trim($order["P15_orderAmount"]);

            if($phone<>"" && $money <> ""){//判断必要参数非空

                //获取form表单参数
                $P1_bizType =  trim($param['P1_bizType']);
                $P2_customerNumber =  trim($param["P2_customerNumber"]);
                $P3_userId =  trim($param['userId']);
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
                $P25_isIntegral = 'TRUE'; // 送积分通道 (最新新增参数)
//                $P26_aptitudeCode = 'BM00000001'; //资质编码
                $P27_integralType = ''; //积分类型


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

                //post的参数  ,"P25_isIntegral" => $P25_isIntegral,"P26_aptitudeCode" => $P26_aptitudeCode,"P27_integralType" => $P27_integralType
                $paramss = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_userId'=>$P3_userId,'P4_orderId'=>$P4_orderId,'P5_timestamp'=>$P5_timestamp,'P6_payerName'=>$P6_payerName,'P7_idCardType'=>$P7_idCardType,'P8_idCardNo'=>$P8_idCardNo,'P9_cardNo'=>$P9_cardNo,'P10_year'=>$P10_year,'P11_month'=>$P11_month,'P12_cvv2'=>$P12_cvv2,'P13_phone'=>$P13_phone,'P14_currency'=>$P14_currency,'P15_orderAmount'=>$P15_orderAmount,'P16_goodsName'=>$P16_goodsName,'P17_goodsDesc'=>$P17_goodsDesc,'P18_terminalType'=>$P18_terminalType,'P19_terminalId'=>$P19_terminalId,'P20_orderIp'=>$P20_orderIp,'P21_period'=>$P21_period,'P22_periodUnit'=>$P22_periodUnit,'P23_serverCallbackUrl'=>$P23_serverCallbackUrl,'P25_isIntegral'=>$P25_isIntegral,'P27_integralType'=>$P27_integralType,'sign'=>$sign);


                //调用支付请求
                $pageContents = $Client->quickPost($url, $paramss);  //发送请求 send request

//                dump($pageContents);die;
//                echo $pageContents;die;

                # 保存日志
                $this->PaySetLog("./PayLog","creadition__",json_encode($paramss)."-------  首单请求返回"."---------".$pageContents."------- \r\n");

                //解析返回报文,成功才跳转到验证码支付页面
                $obj = json_decode($pageContents);



                if ($obj->rt2_retCode == "0000") {

                    # 保存订单信息
                    $ldata['ordersn'] = $P4_orderId;
                    $ldata['userId'] = $P3_userId;
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

    # 提交发送验证
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

        if($_REQUEST)
        {
            $code = trim($pdata['P5_validateCode']);
            $cusNum = trim($param['P2_customerNumber']);
            $orderId = trim($order['P3_orderId']);
            $timestamp = trim($order['P4_timestamp']);
            $phone = trim($myBankInfo['P13_phone']);
            if($code <> ""){//检查必要参数

//                $url = "https://test.trx.helipay.com/trx/quickPayApi/interface.action";//网银请求的页面地址
                # 生产请求接口地址
                // http://pay.trx.helipay.com/trx/quickPayApi/interface.action
                $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";
                //支付MD5的签名串
                $orinSendMessage = "&QuickPayConfirmPay&$cusNum&$orderId&$timestamp&$code&$ip&$signkey";
                $sendSign = md5($orinSendMessage);

                //支付请求参数
                $paramss=array('P1_bizType'=>'QuickPayConfirmPay','P2_customerNumber'=>$cusNum,
                    'P3_orderId'=>$orderId,'P4_timestamp'=>$timestamp,'P5_validateCode'=>$code,'P6_orderIp'=>$ip,'sign'=>$sendSign);

                //调用支付请求
                $pageContents = $Client->quickPost($url, $paramss);  //发送请求 send request


                # 保存日志
                $this->PaySetLog("./PayLog","creadition__",json_encode($paramss)."-------  发送验证码请求返回"."---------".$pageContents."------- \r\n");

//                $obj = json_decode($pageContents);

//                # 不在这里做支付回调信息
//                if ($obj->rt2_retCode == "0000") {
//
//                    M("crepay_log")->where(array("ordersn"=>$obj->rt5_orderId))->save(array(
//                        'order_status'=>'SUCCESS',
//                        'serial_number'=>$obj->rt6_serialNumber
//                    ));
//                }

                return array(1,$pageContents);

            }
        }

    }

    # 绑卡结算提现
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

            //调用支付接口
//            $url = "http://test.trx.helipay.com/trx/quickPayApi/interface.action";
            $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";
            $pageContents = $Client->quickPost($url, $params);  //发送请求 send request

//            dump($signFormString);
//            dump($params);

            $obj = json_decode($pageContents);
            if ($obj->rt2_retCode == "0000") {  # 绑卡记录
                # 保存订单信息
                $ldata['ordersn'] = $P4_orderId;
                $ldata['userId'] = $P3_userId;
                $ldata['phone'] = $phone;
                $ldata['payerName'] = $P5_payerName;
                $ldata['cardNo'] = $P8_cardNo;
                $ldata['ip'] = get_client_ip(); # 下单IP地址
                $ldata['t'] = time();
                M("crepayjs_log")->add($ldata);
            }
            return array(1,$pageContents);
//            echo "back msg:".$pageContents."<br/>";  //返回的结果   The returned result
        }
    }

    #  商户提现接口  ---  代付 ( 不用此接口，此接口作废... )
    public function ajPayJsCardDf($param=array(),$order=array())
    {
        import('Vendor.payPerson.HttpClient');
        $Client = new \HttpClient($param['ip']);

        //RSA私钥
//        $privatekey = "MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAK5GQSOPqzt7o4xcgygdikqN1uY13J7Uu0nJdm/BtxPH1Y1qolPUw/lSCd83f7KnS/xS/THCVEwvUm2iOtQKIDj2A/SC7Jy+bZbbbrJqkx+61pgjuIFsKo7Wf/2OX59Nj1qQlWa99J3ZH/kEFxKd5V1moV9cCNpBZVoEYyhmBbajAgMBAAECgYAIDqt4T24lQ+Qd2zEdK7B3HfOvlRHsLf2yvaPCKvyh531SGnoC0jV1U3utXE2FHwL+WX/nSwrGsvFmrDd4EjfHFsqRvHm+TJfXoHtmkfvbVGI7bFl/3NbYdi76tqbth6W8k0gkPUsACs2ix8a4K7zxOO+UpOeUBIXrchDxFmj9sQJBAOgSHQAI5hr/3+rSQXlq2lET87Ew9Ib72Lwqri3vsHO/sysVTLAznuA+V8s+a4tUeA839a/tGLp1SaJhvma9/30CQQDAPoNFw4rYTm9vbQnrCb6Mm0l9GNpCD1c4ShTxHJyt8Gql0e1Sl3vc28AxyqHLq66abYDzOpnPGJ8AIpri4qifAkArJsMRsJXoy089gJ8ADqhNjyIu/mVZfBbO1jjQ/dKXkzujdTBvSwnttGnqts6Ud75jRgp/Dd0dPpXUhcw7mnSZAkEAmYeTKP0Afr0tS6ymNhojHoHJz+kwLX+45VBspx51loghc+pSgRpPplOti1ZLnq+uktAPIrDTM0xzdxUr4zSm+wJAV54yRQsZBkRmhPibmLeoe3lM6hcAwLqS+E1H09X92fBLqCtBAybDnf++hT2ATgtW+xgI+tFVbOqbi1QbLyw1kA==";

        $privatekey = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDPVJC5xbre6J/eGfKw2yJ9xLOiYsj5QP3SUnYX62FNcT4J668SlS+X0m0FHt2kzQB+RGQyqYFQ31ZszpsnMQ5IK087Oqh6tjEDJLVjRi4a3qgp8H5Pxi2KZocCiE1SQfRR90BKj7jjJcAoYcZOiXhI2sS979e4yloRiG5vI01ghQIDAQAB";

        $P5_amount = $order['amount'];
//        $signkey=$param['signkey_quickpay'];

//        H1Fr9uZuzb1L7njorrznfVNog3XKuNfS    5qcCONK8KRUlLK8Ppr5cS3xm   H1Fr9uZuzb1L7njorrznfVNog3XKuNfS
        $signkey='KYPoorS53LVWLsPj4QlJKLPYeCVAKMwN';

//        &MerchantWithdraw&C1800001834&tx__20171114105055&1&CCB&6217003320015057576&蔡俊锋&B2C&&PAYER&测试提现&KYPoorS53LVWLsPj4QlJKLPYeCVAKMwN


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



            //调用支付请求
//            $url = "http://test.trx.helipay.com/trx/transfer/interface.action";
            $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";
            $pageContents = $Client->quickPost($url, $params);  //发送请求 send request

            dump($signFormString);
            dump($pageContents);
            dump($params);die;
            # 保存日志 - 商户提现
            $this->PaySetLog("./PayLog","tx__",json_encode($params)."-------  商户提现数据请求返回"."---------".$pageContents."------- \r\n");

            $obj = json_decode($pageContents);

            # 添加金额
            M("crepayjs_log")->add(array(
                'amount'=>$P4_amount,
                'ordersn' => $P3_orderId
            ));
        
            if ($obj->rt2_retCode == "0000") {
                M("crepayjs_log")->where(array("ordersn"=>$P3_orderId))->save(array(
                    'order_status'=>'SUCCESS'
                ));
            }
            return array(1,$pageContents);
        }
    }


    #  商户提现接口  ---  提现





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
//        error_reporting(E_ALL ^ E_NOTICE);


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
//            {
//                "rt2_retCode":"0000",
//                "sign":"330ac3fa40e5280d99f33cf68ff8692d",
//                "rt1_bizType":"QuickPayBankCardPay",
//                "rt5_orderId":"p_20171102101022",
//                "rt4_customerNumber":"C1800001108",
//                "rt3_retMsg":"成功"
//            }

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
            'transNo'=>date('YmdHis').rand(10000,99999),
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
    public function PaySetLog($is_dir="./PayLog",$cname="creadition__",$txt="")
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
}