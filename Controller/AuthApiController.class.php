<?php# 银钱包对接接口 - 所有鉴权/认证的接口# one: 银行卡四要素认证接口# two: 身份证二要素认证接口namespace Payapi\Controller;use Think\Controller;class AuthApiController extends Controller{    public function _initialize()    {    }    /* 银行卡四要素认证接口     * 'user_id' => '', 用户ID     * R("Payapi/AuthApi/AuthBank",array($pdata,$uid))        # $pdata = array(            'name' => '',  // 姓名            'idNumber' => '',  // 身份证            'bankCard' => '',  // 卡号/ - 信用卡/储蓄卡            'mobile' => '',  // 手机号码            'requestId' => '', // each : 'ab__'.date('Ymdhis',time()); // 订单号            'timestamp' => '', // each : date('Ymdhis',time()); // 时间戳        );    */    public function AuthBank($pdata=array(),$uid=0)    {        import('Vendor.AuthReturn.AuthReturn');        $config = C("AUTHRETURN");        $AuthReturn = new \AuthReturn($config,1); # 2 测试连接地址 url ,1 正式链接地址 url//        $requsetId = 'ab__'.date('Ymdhis',time());//        $timestamp = date('Ymdhis',time());//        $pdata = array(//            'user_id' => 'TKDPODOODO',//            'name' => '蔡俊锋',//            'idNumber' => '445221199702234558',//            'bankCard' => '6217003320015057576',//            'mobile' => '13112157790',//            'requestId' => $requsetId,//            'timestamp' => $timestamp,//        );//        dump($pdata);die;        $res = $AuthReturn->AuthBankSrv($pdata);        # 重新定义返回        $deres = json_decode($res,true);        # 保存日志        R("Payapi/Api/PaySetLog",array("./PayLog","Payapi_AuthApi__",json_encode($pdata)."-------  鉴权银行卡请求参数  "."---------".json_encode($pdata)."------- \r\n"));        R("Payapi/Api/PaySetLog",array("./PayLog","Payapi_AuthApi__",json_encode($pdata)."-------  鉴权银行卡通知返回参数  "."---------".$res."------- \r\n"));        $jres = array();        if($deres)        {            if($deres['key'] == '0000') # success  认证通过            {                $jres['res_code'] = "0000";                $jres['res_msg'] = $deres['msg'];            }else{                      # error 认证不通过                $jres['res_code'] = "5555";                $jres['res_msg'] = $deres['msg'];            }            # 保存日志鉴权            $jqdata['bankid'] = '';            $jqdata['json'] = $res;            $jqdata['userId'] = $uid;            $jqdata['ordersn'] = $pdata['requestId'];            $jqdata['serialn_number'] = '';            $jqdata['card'] = $pdata['bankCard'];            $jqdata['phone'] = $pdata['mobile'];            $jqdata['user_id'] = $uid;            $jqdata['jq_status'] = 3;  # 状态            $jqdata['jq_td_id'] = 2;  # 松顺            $jqdata['t'] = time();            M("jq_log")->add($jqdata);        }else{            $jres['res_code'] = "9999"; # 接口报错            $jres['res_msg'] = '接口报错';        }        return $jres;    }    /* 身份证二要素认证接口     * 'user_id' => '', 用户ID     * R("Payapi/AuthApi/AuthBank",array($pdata,$uid))        # $pdata = array(            'name' => '',  // 姓名            'idNumber' => '',  // 身份证            'requestId' => '', // each : 'ab__'.date('Ymdhis',time()); // 订单号            'timestamp' => '', // each : date('Ymdhis',time()); //        );    */    public function AuthRealname($pdata=array(),$uid=0)    {        import('Vendor.AuthReturn.AuthReturn');        $config = C("AUTHRETURN");        $AuthReturn = new \AuthReturn($config,1); # 2 测试连接地址 url ,1 正式链接地址 url        $res = $AuthReturn->AuthRealnameSrv($pdata);# 重新定义返回        $deres = json_decode($res,true);        # 保存日志        R("Payapi/Api/PaySetLog",array("./PayLog","Payapi_Realname__","-------  身份证二要素认证请求参数".json_encode($pdata)."  ------- \r\n"));        R("Payapi/Api/PaySetLog",array("./PayLog","Payapi_Realname__","-------  身份证二要素认证通知返回参数".json_encode($deres)."  ------- \r\n"));        $jres = array();        if($deres)        {            if($deres['key'] == '0000') # success  认证通过            {                $jres['res_code'] = "0000";                $jres['res_msg'] = $deres['msg'];            }else{                      # error 认证不通过                $jres['res_code'] = "5555";                $jres['res_msg'] = $deres['msg'];            }            # 保存日志鉴权            $jqdata['json'] = $res;            $jqdata['ordersn'] = $pdata['requestId'];            $jqdata['name'] = trim($pdata['name']);            $jqdata['idcard'] = trim($pdata['idcard']);            $jqdata['user_id'] = $uid;            $jqdata['jq_td_id'] = 1;  # 松顺            $jqdata['t'] = time();            M("jq_realname_log")->add($jqdata);        }else{            $jres['res_code'] = "9999"; # 接口报错            $jres['res_msg'] = '接口报错';        }        return $jres;    }    # 银行卡四要素认证接口 -- 测试    public function AuthBankceshi()    { //$name,$idNumber,$bankCard,$mobile        import('Vendor.AuthReturn.AuthReturn');        $config = C("AUTHRETURN");        $AuthReturn = new \AuthReturn($config,1); # 2 测试连接地址 url ,1 正式链接地址 url        $requsetId = 'ab__'.date('Ymdhis',time());        $timestamp = date('Ymdhis',time());        //        $pdata = array(////            'name' => $name,////            'idNumber' => $idNumber,////            'bankCard' => $bankCard,////            'mobile' => $mobile,////            'requestId' => $requsetId,////            'timestamp' => $timestamp,////        );        $pdata = array(            'name' => '邓炜敏',            'idNumber' => '350427198501220035',            'bankCard' => '6217001870004337399',            'mobile' => '18960592088',            'requestId' => $requsetId,            'timestamp' => $timestamp,        );//        dump($pdata);die;        $res = $AuthReturn->AuthBankSrv($pdata);        dump($res);    }    #测试智能付交易    public function csZnf(){        $result='{"success":true,"return_info":"下单成功","return_data":{"pay_info":{"signMethod":"01","txnType":"01","encoding":"UTF-8","channelType":"07","bizType":"000301","txnTime":"20180508142807","currencyCode":"156","accessType":"0","encryptCertId":"69042905377","accNo":"VTHnvtRolGvT6iwWizIPLYrIGxZL9ZTFIURqWRTqktA5WO99bNDU3RZZGWAxrOmNRE9DVvX%2FXQahIT4x3xZotrOcEkA6Bz7mS9kQqovTisyItk3fQdmkbsSkfA64uqqWjZukTQxHggFrIHKgTcdmino%2BRrGG1idjyJk%2BFGFjBf%2BQ4Xfrlx6348GevEcZx06gG9oQ1InTMtofVqIqMxr8rSfwUrclGIXSdxlnHtMZZdIiq%2FHGwORy4MUEIXgl9BS91g6osokdeST0x2biYedeJ1f6vfwO8KxWtXUZqqVtH0HgFT%2FwOeJGLfBA1E4qKHEXZzKX%2BHkjRwro2RJK5tTpMw%3D%3D","signature":"CTZn26sUn6K4no6bcqWYv%2F%2FnTDr6jSBAaIfR3ZkcY6YHyY6JUR9wBjElFynAi4suMLj0G5LrMPhpVHxEbWdhWsh5ZFOlIqyGW1HzDhLJxeJi4NDkqXIt63RmI02vuiFsQO4RvONJjE2LuT8EI%2BSMSSOcbBGN9MWEKPIpfkumxjnjXmMyvZQo81iSiKcYzSkAs21dXa63AXJYe9nCnC6MDgzo6NZzSXWLRNbowqrkEVguRmMBPeHbdckqKnnnedOoqKObyMer6fdEey1G%2BwcbEDnHOSDWv3r05%2BdAgxibaqsvMHuvnkA83VGeIoE%2BQ9LGIArfoo7b6uI4Okqb%2FlLEBg%3D%3D","accType":"01","merId":"883521045110001","frontUrl":"https%3A%2F%2Fnotify.allscore.com%2Fnotify%2FCHANNEL-B-CUP-001%2FCALLBACK%2FNOCARD","backUrl":"https%3A%2F%2Fnotify.allscore.com%2Fnotify%2FCHANNEL-B-CUP-001%2FNOTIFY%2FNOCARD","version":"5.0.0","txnAmt":"100000","certId":"74195550517","txnSubType":"01","orderId":"2018050814280716519569"},"order_no":"18050814280600140460","merc_cd":"800000041312300","pay_code":"https://gateway.95516.com/gateway/api/frontTransReq.do","fee_amt":"7.00","order_amt":"1000"},"return_code":"00","token":""}';        $ktsdata = json_decode($result);        $ktsdata = $ktsdata->return_data;        $pay_code = $ktsdata->pay_code;        $pay_info = $ktsdata->pay_info;        $orderNo = $ktsdata->order_no;        $mercCd = $ktsdata->merc_cd;        $jdpayinfo = json_decode(json_encode($pay_info),true);        $mycodess = "<form name='submit' action='" . $pay_code . "' accept-charset='utf-8' method='post'>";        foreach ($jdpayinfo as $k => $v)        {            $mycodess.="<input type='hidden' name='".$k."' value='".urldecode($v)."'/>";        }        $mycodess.="<script>document.forms['submit'].submit();</script></form>";        echo $mycodess;    }}