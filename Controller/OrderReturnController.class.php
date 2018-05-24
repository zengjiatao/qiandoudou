<?php
/**
 * Created by 银迅通支付网络公司.
 * User: Jeffery
 * Date: 2017/12/12
 * Time: 17:43
 * 订单操作异常/查询（第三方）
 * # one1 ：合利宝HLB
 * # one2 : 智付
 */

namespace Payapi\Controller;
use Think\Controller;

class OrderReturnController extends Controller
{

    /**
     * @return array
     ************************ 合利宝HLB *******************************
     */
    /**
     * # 支付订单查询状态
     * @pdata = orderId
     */

    public function HLBSELECTPAYORDER($orderId)
    {
        import('Vendor.payPerson.HttpClient');
        $param = C("KJPAY");
        $Client = new \HttpClient($param['ip']);
        $signkey = $param['signkey_quickpay'];

//        $userId = 13112157790
        if ($orderId <> '') {//校验必要参数值
            //表单值
            $P1_bizType =  "QuickPayQuery";  # 绑定卡变量名
            $P2_orderId =  $orderId;
            $P3_customerNumber =  $param["P2_customerNumber"];
            $signFormString = "&$P1_bizType&$P2_orderId&$P3_customerNumber&$signkey";
            $sign=md5($signFormString);
            //构造请求参数
            $params = array('P1_bizType'=>$P1_bizType,'P2_orderId'=>$P2_orderId,'P3_customerNumber'=>$P3_customerNumber,'sign'=>$sign);
//            $url = "http://test.trx.helipay.com/trx/quickPayApi/interface.action";
//            $url = "http://transfer.trx.helipay.com/trx/transfer/interface.action";
            $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";
            $pageContents = $Client->quickPost($url, $params);  //发送请求 send request


            echo $pageContents;exit;

            # 保存日志
            R("Payapi/Api/PaySetLog",array("./OrderLog","OrderReturn_HLBSELECTUSERMONEY__","-------  用户余额查询"."---------".$pageContents."------- \r\n"));
            $obj = json_decode($pageContents,true);
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
            echo json_encode($res);exit;
//            return $res;

        }
    }
    /**
     * # 结算卡提现查询状态
     * @pdata = orderId
     */
    public function HLBSELECTJSTXORDER($orderId)
    {
//        $pdata=array()
        import('Vendor.payPerson.HttpClient');
        $param = C("KJPAY");
//        $pdata = array('orderId'=>'HLBQP20171224062537');
        $Client = new \HttpClient($param['ip']);
        //RSA私钥
        $privatekey = "MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBALOjm7Kw+w5lMN2Wvc8UTl76sz2y7Rikow/+d8aKOnHXHEhxgZpwHeMqfS5b55lplpCHjrwZamu4nA8/m0Q/ES9qRMQp4c5jdkBjTOkMNcOlns5Mti5/nLXxPq1Ka4wvWNKiZ0O9m2N/pU8X2sw68nyGn7Y44ghkbpmtbJUyVRltAgMBAAECgYAgKcz4w4NP4oJLSnAVoZcenlh1VZHp9aBUfsVHQPyR4Wfo+Jmx4x0WzUa4hDAFYchZfEvsFcjeHKGkgUj1gS08OUWRY3CW1cNL6EPtgzwAus3L+u1w+uWyk2zY0mSExHI5pCgKyrlgwjXeUVJRffx8yHZkmDy9VhjlvBfiR2+h6QJBANsB1R+blhitdb6/OjEfcM+bZGd5q11LHJbYhrF1/gOV6U7pdglOSlbYis2WDAJJmqdNVCIwQOnTptgztuV4a1MCQQDR+3JxgzbNB1N8+ypQeRFeGI8Zb2QwtAFDuNTjSDdcsmp5X166A3Sf/nRIgmNGRh8+l7HAGICvvwmWknv23JA/AkAsKTdvczEV8sw+VVMHmr5lroDVeKw8WKwAItMuL4uz72OnPN5HTBkjX/DFOc9cGrlrqOUhK7e7LqmDCRKFPP3vAkBneIYuVUAdy+xh+8ogGWhre6KYIAG41hqBaoTM8nsFXI2G/W3KL4W6iUJ3sHiG2mrvBwT56ZkQAQ0Se2BGhu01AkEAuphooslCNPsriGD/AKd4h/x39+x7xBkRprGDEN4DhhgeB0DPDh70ROP3f4+DX3ySl1BT9PiHes+0AIPL8nRBCw==";

        if ($orderId <> '') {//检查必要参数
            $P1_bizType = "TransferQuery";
            $P2_orderId = $orderId;
            $P3_customerNumber =  $param["P2_customerNumber"];

            //构造签名串
            $signFormString = "&$P1_bizType&$P2_orderId&$P3_customerNumber";
            import('Vendor.payPerson.Crypt_RSA');   // rsa 加密
            //获取加密报文
            $rsa = new \Crypt_RSA();
            $rsa->setHash('md5');
            $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
            $rsa->loadKey($privatekey);
            $sign = base64_encode($rsa->sign($signFormString));

            //构造请求参数
            $params = array('P1_bizType'=>$P1_bizType,'P2_orderId'=>$P2_orderId,'P3_customerNumber'=>$P3_customerNumber,'sign'=>$sign);


//            $url = "http://test.trx.helipay.com/trx/transfer/interface.action";
            $url = "http://transfer.trx.helipay.com/trx/transfer/interface.action";
//            $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";
            $pageContents = $Client->quickPost($url, $params);  //发送请求 send request

//            dump($pageContents);die;

            # 保存日志
            R("Payapi/Api/PaySetLog",array("./OrderLog","OrderReturn_HLBSELECTJSTXORDER__","-------  结算卡查询"."---------".$pageContents."------- \r\n"));
            $obj = json_decode($pageContents,true);
            $resarr = array();
            if($obj['rt2_retCode'] == "0000")
            {
                $resarr['code'] = "200";
            }else{
                $resarr['code'] = "400";
            }
            $resarr['msg'] = trim($obj['rt3_retMsg']);
            $resarr['orderStatus'] = trim($obj['rt7_orderStatus']); // 打款状态
            $resarr['serialNumber'] = trim($obj['rt6_serialNumber']);

            echo json_encode($resarr);exit;
            return $resarr;
        }
    }

    /**
     * # 用户余额查询
     * @pdata = userId
     */
    public function HLBSELECTUSERMONEY($userId)
    {
        import('Vendor.payPerson.HttpClient');
        $param = C("KJPAY");
        $Client = new \HttpClient($param['ip']);
        $signkey = $param['signkey_quickpay'];

//        $userId = 13112157790
        $pdata = array(
            'userId' => $userId,
            'timestamp' => date('Ymdhis',time())//时间戳
        );

        if ($pdata <> '') {//校验必要参数值
            //表单值
            $P1_bizType =  "AccountQuery";  # 绑定卡变量名
            $P2_customerNumber =  $param["P2_customerNumber"];
            $P3_userId =  $pdata['userId'];
            $P4_timestamp =  $pdata['timestamp'];
            $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_userId&$P4_timestamp&$signkey";
            $sign=md5($signFormString);
            //构造请求参数
            $params = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_userId'=>$P3_userId,'P4_timestamp'=>$P4_timestamp,'sign'=>$sign);
//            $url = "http://test.trx.helipay.com/trx/quickPayApi/interface.action";
//            $url = "http://transfer.trx.helipay.com/trx/transfer/interface.action";
            $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";
            $pageContents = $Client->quickPost($url, $params);  //发送请求 send request

            /*
                dump($pageContents);
                die;
            */
            # 保存日志
            R("Payapi/Api/PaySetLog",array("./OrderLog","OrderReturn_HLBSELECTUSERMONEY__","-------  用户余额查询"."---------".$pageContents."------- \r\n"));
            $obj = json_decode($pageContents,true);
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
            echo json_encode($res);exit;
//            return $res;

        }
    }


    /**
     * @return array
     ************************ 智付 *******************************
     */
    /**
     * # 订单查询状态
     * $pdata=array(
     *      'tranDate' => '', // 交易日期
     *      'tranId' => '', // 交易流水号
     *      'tranTime' => '', // 交易时间
     *      'origTranDate' => '', // 原交易日期
     *      'origTranId' => '', // 原交易流水号
     * )
     */

    public function ZFSELECTPAYORDER($pdata=array())
    {
        $jsonstr = [
            "tranTime=151535",
            "respCode=00",
            "tranDate=20171227",
            "tranAmt=50000",
            "origDfStatus=00",
            "origTranId=ZFQP2017122715153568278",
            "origRespCode=00",
            "origRespMsg=交易成功",
            "origDfMsg=后台通知：代付成功",
            "respMsg=成功",
            "tranId=ZFQP2017122715153568278",
            "origDfAmount=49810",
            "merchId=830581048161139",
            "signature=eMiqPga8FiMP4LQo4c5h4WkzPqkWJF2OTXY5fM14vYWUuCc/1WBOKqIz0di3USsor3DciA3RgaJfVpgBHc7XrCQW3pFvkgFkIp6ZYA75Gs6N/dMDs7SlteDDZ7KTddmsfiqKRxGOJ7dwt2TKzpPItea29ddNL4S1Ty6CewdY9YHb55+j1BpRtO6bYG3dvw8lqr1qg8pqz2zWmj09FBa9WcAwZ+494+cfDgJJWst43z90GaJNJcN/vPufeqdARUPgBQPaJdbE8NXeZ4ZBjtEOOX6VyLL8jTcg4eJOCKGd1DoY3rOHqCxK5S6OF+DKaNS3EUCosYT/17++2e2AMPJ+tA==
"
        ];

        /*测试
        $pdata = array(
            'tranDate' => '20171227',
            'tranId' => 'ZFQP2017122715153568278',
            'tranTime' => '151535',
            'origTranDate' => '20171227',
            'origTranId' => 'ZFQP2017122715153568278',
        );
        */
        import('Vendor.payZhiFu.zfPay');
        $config = C("ZHIFUPAY");
        $ZfPay = new \zfPay($config);
        $res = $ZfPay->selectOrder($pdata);
        $param = explode('&',$res);

        $res = array();
        $jdres = $param;
        foreach ($jdres as $k => $v) {
            // explode
            $exres = explode('=',$v);
            $res[$exres[0]] = $exres[1];
        }
        return $res;
    }


    /**
     * 订单查询状态完整案例版
     */
    public function ZFSELECTPAYJSORDER($pt_ordersn)
    {
        $tranDate = substr($pt_ordersn,4,8);
        $tranTime = substr($pt_ordersn,13,6);
        # checkorder
        $pdata = array(
            'tranDate' => $tranDate,
            'tranId' => $pt_ordersn,
            'tranTime' => $tranTime,
            'origTranDate' => $tranDate,
            'origTranId' =>  $pt_ordersn
        );
        $res = R("Payapi/OrderReturn/ZFSELECTPAYORDER",array($pdata));
        if($res['origRespCode']=='00')  # 有订单交易成功再进行查询结算
        {
            # 是否结算(代付)成功
            if($res['origDfStatus'] == "00")  # 成功
            {
                $jsdata['js_status'] = 2;
                $jsdata['j_success_time'] = time()+30;
                $js_status = 2;
            }else if($res['origDfStatus'] == "01") # 失败
            {

            }
        }
    }


    /**
     * @return array
     ************************ 柜银云代付 *******************************
     */
    /**
     * # 余额查询
     * @pdata = userId
     */
    public function balanceCheck()
    {
        import('Vendor.payJufu.jfPay');
        $config = C("JYYFPAY");
        $JfPay = new \jfPay($config);

        $res = $JfPay->balanceCheck();

//        dump($res);die;
        $jres = json_decode($res,true);
        $echo = array();
        if($jres['respCode'] == "0000")
        {
            $echo['code'] = 200;
        }else{
            $echo['code'] = 400;
        }
        $echo['msg'] = $echo['message'];
        echo json_encode($echo);die;
    }
}