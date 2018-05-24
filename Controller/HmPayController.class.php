<?php
/**
 * Created by 银迅通支付网络公司.
 * User: Jeffery
 * Date: 2017/12/19
 * Time: 16:43
 * o2o 支付交易接口
 */

namespace Payapi\Controller;
use Think\Controller;

class HmPayController extends Controller
{
    public $uid;
    public $parem;
    public $sign;

    public function _initialize()
    {
//        parent::_initialize();
        $this->uid = trim($_REQUEST['uid']);
        $this->uid = $_REQUEST['uid'];
        $this->parem = array(
            'signType' => $_REQUEST['signType'],
            'timestamp' => $_REQUEST['timestamp'],
            'dataType' => $_REQUEST['dataType'],
            'inputCharset' => $_REQUEST['inputCharset'],
            'version' => $_REQUEST['version'],
        );
        $this->sign = $_REQUEST['sign'];
    }

    #app接口
    public function hmPay()
    {
        //汇铭支付
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
        $myBank = M("mybank")->where(array('user_id' => $this->uid, 'jq_status' => 3, 'status' => 1, 'type' => 2, 'mybank_id' => $bankid))->find();
        if (!$myBank) {
            $d = array(
                'code' => 200,
                'msg' => '!请先选择银行卡'
            );
            echo json_encode($d);
            exit;
        }
        # 获取默认的结算卡
        $myBankNormal = M("mybank")->where(array('user_id' => $this->uid, 'jq_status' => 3, 'status' => 1, 'type' => 1, 'is_normal' => 1))->find();
        if (!$myBankNormal) {
            $d = array(
                'code' => 200,
                'msg' => '请先绑定一张默认结算卡'
            );
            echo json_encode($d);
            exit;
        }
        # 银行卡信息
        $jbank = M("bank")->field('name,hlb_bank_code')->where(array('bank_id' => $myBank['bank_id']))->find();
        $tranDate = date("Ymd");
        $tranId = 'P490010' . date("YmdHis") . mt_rand(10000, 99999);
        $tranTime = date("His");
        // if utype
        $ifutype = M("user")->where(array('user_id' => $this->uid))->getField('utype');
        # 手续费
        $pay_supply_id = M('user_pay_supply')->field('pay_supply_id,user_js_supply_id,yy_rate,tk_rate,pt_rate')->where(array('user_pay_supply_id' => $tytd))->find();
        if (!$pay_supply_id) {
            $d = array(
                'code' => 200,
                'msg' => '该通道正在维护中!'
            );
            echo json_encode($d);
            exit;
        }
        # 结算提现费
        $yyrate = M("user_js_supply")->where(array('user_js_supply_id' => $pay_supply_id['user_js_supply_id']))->getField('yy_rate'); # 提现费
        if (!$yyrate) {
            $d = array(
                'code' => 200,
                'msg' => '该通道正在维护中!'
            );
            echo json_encode($d);
            exit;
        }
        $tranAmt = $price * 100;   //金额
        $orderDesc = "o2o交易";  //名称

        # 支付交易通道 (对接自动结算通道，目前固定DOBANK，结算到银行卡)
        $yy_rate = M("user_pay_supply")->where(array('user_pay_supply_id' => $tytd))->getField('yy_rate');


        # 新增结算通道信息 - 入账金额
        $pay_supply_id = M('user_pay_supply')->where(array('user_pay_supply_id' => $tytd))->getField('pay_supply_id');
        $payrate = M('user_pay_supply')->where(array('user_pay_supply_id' => $tytd))->getField('yy_rate');
        $rzprice = 0;
        if ($pay_supply_id) {
            $yyrate = M("user_js_supply")->where(array('sid' => $pay_supply_id))->getField('yy_rate'); # 提现费
            $rzprice = trim($price) - (trim($price) * $payrate / 1000) - $yyrate;
        } else {
            $d = array(
                'code' => 200,
                'msg' => '参数不全!'
            );
            echo json_encode($d);
            exit;
        }

        # 手续提现费率
        $service_charge = $price * ($yy_rate / 1000);
        $sumer_amt = $yyrate * 100; // 结算元/笔


        # 扣取费率+手续费
        $sumer_fee = $payrate / 10; // 0.38或者？


        #判断是否 有商户号
        $shanghu = M('hm_info')->where(array('user_id' => $this->uid, 'jifen_type' => 0))->find();
        if ($shanghu) { //存在 商户号
            #直接下单   #保存订单信息
            # 保存订单  第一步
            $mdata['user_id'] = $this->uid;
            $mdata['goods_name'] = "O2O收款";
            $mdata['goods_type'] = "消费";
            $mdata['user_pay_supply_id'] = $tytd;
            $mdata['pay_name'] = "O2O交易";
            $mdata['service_charge'] = $service_charge;
            $mdata['pt_ordersn'] = $tranId;
            $mdata['timestamp'] = $tranDate . $tranTime;
            $mdata['sh_ordersn'] = ""; //
            $mdata['jy_status'] = 2;
            $mdata['pay_money'] = $price;
            $mdata['money'] = $price - $service_charge;
            $mdata['money_type_id'] = 11; // 快捷支付
            $mdata['t'] = time();
            $mdata['bank_cart'] = $myBank['cart'];
            $mdata['phone'] = $myBank['mobile'];
            $mdata['shop_rate'] = $sumer_fee;
            $pay_supply_type_id = M("sktype")->where(array('user_id' => $this->uid))->getField('pay_supply_type_id');
            $mdata['pay_supply_type_id'] = trim($pay_supply_type_id);
            if (M('money_detailed')->where(array('pt_ordersn' => $tranId))->getField('pt_ordersn')) {
                $d = array(
                    'code' => 200,
                    'msg' => '此订单号已存在，勿重复!'
                );
                echo json_encode($d);
                exit;
            }
            $add = M('money_detailed')->add($mdata);
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
                M('moneyjs_detailed')->add($jsdata);
            }
            #构建下单参数
//            $pinfo=array( //测试固定林良振的账户
//                'user_id'=>'Q00000068',
//                'merCode'=>'32083000580346',//商户号
//                'orderNo'=>'P490010'.date("YmdHis").mt_rand(10000,99999),//商户订单号  以P字母开头，跟6位机构号
//                'orderDate'=>date('Ymd',time()),//商户订单日期   YYYYMMDD
//                'orderTime'=>date('YmdHis'),//商户订单时间格式：YYYYMMDD24HHMMSS
//                'orderAmount'=>100*100,//订单金额 单位 :分
//                'accType'=>'CREDIT',//账号类型  不支持借记卡 支付.
//                'accNo'=>'6226890118005577',//卡号'5187187004729108'
//                'telNo'=>'13790957780',//手机号
////            'cardPassword'=>$cardpwd,//卡密  3des加密
//                'cvn2'=>'475', //cvn2
//                'useful'=>'0622', //有效期
////            'creCardId'=>''
//            );
            $PayCanShu = array(  //信用卡 $myBank
                'user_id' => $this->uid,
                'merCode' => $shanghu['mch_id'],  //商户号
                'orderNo' => 'P490010' . date("YmdHis") . mt_rand(10000, 99999),//商户订单号  以P字母开头，跟6位机构号
                'orderDate' => date('Ymd', time()),//商户订单日期   YYYYMMDD
                'orderTime' => date('YmdHis'),//商户订单时间格式：YYYYMMDD24HHMMSS
                'orderAmount' => $tranAmt,//订单金额 单位 :分
                'accType' => 'CREDIT',//账号类型  不支持借记卡 支付.
                'accNo' => $myBank['cart'],//卡号'5187187004729108'
                'telNo' => $myBank['mobile'],
                'cvn2' => $myBank['cw_two'],
                'useful' => $myBank['useful'], //有效期
            );
            $zhiFuCs = $this->orderBuy($PayCanShu); //失败会返回 错误信息  支付成功就返回支付成功状态
        }else {   //不存在  商户号
            #进件参数
            //'merName'=>$order['nick_name'],//商户名称
//            'realName'=>$order['name'],//姓名
//            'merState'=>$order['province'],//商户所在省
//            'merCity'=>$order['city'],//商户所在市
//            'merAddress'=>$order['address'],//商户所在详细地址
//            'certType'=>'01',//证件类型
//            'certId'=>$order['idcard'],//身份证
//            'mobile'=>$order['mobile'],//手机号码
//            'accountId'=>$order['bankCard'],//结算卡号  姓名和结算户名必须一致   //
//            'accountName'=>$order['name'],//结算户名
//            'bankName'=>$order['bankName'], // 银行名称
//            'bankCode'=>$order['lianhanghao'], // 总行联行号
//            'operFlag'=>'A',//操作标识
//            't0drawFee'=>$order['d0Fee'],//单笔D0提现交易手续费   如0.2元/笔则填0.2
////            't0drawRate'=>$order['d0Rate'],//D0提现交易手续费扣率  如0.6%笔则填0.006 小数点后最多不超过4位
//            't1consFee'=>$order['t1Fee'],//单笔消费交易手续费
//            't1consRate'=>$order['t1Rate'],//消费交易手续费扣率
            import("ORG.Util.Pinyin");
            $py = new \PinYin();
            $pinyin = $py->getAllPY($myBankNormal['nickname']);
            $sheng = M('province')->where(array('provids'=>$myBankNormal['province_id']))->getField('province');
            $city=M('city')->where(array('cityids'=>$myBankNormal['city_id']))->getField('city');
            $bankName=M('bank')->where(array('bank_id'=>$myBankNormal['bank_id']))->getField('hm_name');
            if ($myBankNormal['bank_id'] == '6'){ //广发银行联行号不一样
                $lianhanghao='306331003281';
            }else{
                $lianhanghao=M('bank')->where(array('bank_id'=>$myBankNormal['bank_id']))->getField('lianhanghao');
            }
            $jinJian = array(
                'nick_name' =>$pinyin,
                'name' => $myBankNormal['nickname'],
                'province'=>$sheng,
                'city'=>$city,
                'address'=>'详细地址',
                'idcard'=>$myBankNormal['idcard'],
                'mobile'=>$myBankNormal['mobile'],
                'bankCard'=>$myBankNormal['cart'],
                'bankName'=>$bankName,
                'lianhanghao'=>$lianhanghao,
                'd0Fee'=>'2',
                't1Fee'=>'0',
                't1Rate'=>'0.0048'
            );
            $sjInfo=$this->shanghujj($jinJian);  //进件返回参数


        }
    }


    #商户进件接口(不要修改 这是线上接口)
    public function shanghujj($pdata = array())
    {
        import('Vendor.hmPay.hmPay');
        $rep = new \hmPay();
//        $pinfo=array(
//            'merName'=>$order['nick_name'],//商户名称
//            'realName'=>$order['name'],//姓名
//            'merState'=>$order[''],//商户所在省
//            'merCity'=>$order['city'],//商户所在市
//            'merAddress'=>$order['address'],//商户所在详细地址
//            'certType'=>'01',//证件类型
//            'certId'=>$order['idcard'],//身份证
//            'mobile'=>$order['mobile'],//手机号码
//            'accountId'=>$order['bankCard'],//结算卡号  姓名和结算户名必须一致   //
//            'accountName'=>$order['name'],//结算户名
//            'bankName'=>$order['bankName'], // 银行名称
//            'bankCode'=>$order['lianhanghao'], // 总行联行号
//            'operFlag'=>'A',//操作标识
////            't0drawFee'=>,//单笔D0提现交易手续费   如0.2元/笔则填0.2
//            't0drawRate'=>$order['d0Rate'],//D0提现交易手续费扣率  如0.6%笔则填0.006 小数点后最多不超过4位
//            't1consFee'=>$order['t1Fee'],//单笔消费交易手续费
//            't1consRate'=>$order['t1Rate'],//消费交易手续费扣率
//        );
        //广发银行 联行号不对 记得改
        $pinfo = array(
            'nick_name' => 'linliangzheng',//商户名称
            'name' => '林良振',//姓名
            'province' => '广东省',//商户所在省
            'city' => '广州市',//商户所在市
            'address' => '天河区棠东',//商户所在详细地址
            'idcard' => '440881199402215552',//身份证
            'mobile' => '13790957780',//手机号码
            'bankCard' => '6228480606400544171',//结算卡号  姓名和结算户名必须一致   //
            'bankName' => '农业银行', // 银行名称
            'lianhanghao' => '103100000026', // 总行联行号
//            'operFlag'=>'A',//操作标识
            'd0Fee' => '2',//单笔D0提现交易手续费   如0.2元/笔则填0.2
//            'd0Rate'=>'0.0048',//D0提现交易手续费扣率  如0.6%笔则填0.006 小数点后最多不超过4位
            't1Fee' => '0',//单笔消费交易手续费
            't1Rate' => '0.0048',//消费交易手续费扣率
        );

//        $data= $rep->tiXian(); //提现接口
//        $data= $rep->orderSelect(); //交易查询接口
        $data = $rep->createSh($pdata); //商户进件接口
//        dump($data);die;
        return $data;
    }

    #商户交易接口
    public function orderBuy($pdata = array())
    {
        import('Vendor.hmPay.hmPay');
        $rep = new \hmPay();
//        $pinfo=array( //测试固定林良振的账户
//            'user_id'=>'Q00000068',
//            'merCode'=>'32083000580346',//商户号
//            'orderNo'=>'P490010'.date("YmdHis").mt_rand(10000,99999),//商户订单号  以P字母开头，跟6位机构号
//            'orderDate'=>date('Ymd',time()),//商户订单日期   YYYYMMDD
//            'orderTime'=>date('YmdHis'),//商户订单时间格式：YYYYMMDD24HHMMSS
//            'orderAmount'=>100*100,//订单金额 单位 :分
//            'accType'=>'CREDIT',//账号类型  不支持借记卡 支付.
//            'accNo'=>'6226890118005577',//卡号'5187187004729108'
//            'telNo'=>'13790957780',//手机号
////            'cardPassword'=>$cardpwd,//卡密  3des加密
//            'cvn2'=>'475', //cvn2
//            'useful'=>'0622', //有效期
////            'creCardId'=>''
//        );
        $data = $rep->orderBuy($pdata);   //交易接口
//        dump($data);die;
        return $data;
    }


    #商户提现
    public function tiXian($pdata = array())
    {
        import('Vendor.hmPay.hmPay');
        $pinfo = array(
            'merCode' => '32083000580346',//商户号
            'orderNo' => 'T490010' . mt_rand(10000, 99999),//商户订单号
            'transDate' => date('YmdHis'),//交易时间
            'transAmount' => ''//提现金额
        );
        $rep = new \hmPay();
        $data = $rep->tiXian($pinfo);
        dump($data);
        die;
        return $data;
    }

    #查询订单
    public function selectOrder()
    {
        import('Vendor.hmPay.hmPay');
        $rep = new \hmPay();
        $pinfo = array(
            'merCode' => '32083000580346',//商户号
            'orderNo' => 'P4900102018020716185525051',//订单号
            'transDate' => '20180207161855',//商户订单发送时间
            'transSeq' => '566741276',//流水号
        );
        $data = $rep->orderSelect($pinfo);
        dump($data);
        die;
        return $data;
    }

    #异步回调
    public function hmPayReturn()
    {

        $data = I('post.');
        $input2 = json_encode($data);
//        error_reporting(0);
        $input = file_get_contents('php://input');
//        $output=$_REQUEST;
//        $param = explode('&',$input);
        R("Payapi/Api/PaySetLog", array("./PayLog", "XjPay_Return_", "----星洁回调返回信息参数----" . $input2 . "-- 时间:" . date('Y-m-d H:i:s') . "------\r\n"));
        //        error_reporting(0);
        $getJson = json_decode($input, true);

        # 回调订单
        $pt_ordersn = trim($getJson['agentOrderNo']);  //平台订单
        $iforder = M('money_detailed')->field('user_id,user_pay_supply_id,service_charge')->where(array('pt_ordersn' => $pt_ordersn))->find();
        $user_js_supply_id = M("user_pay_supply")->where(array('user_pay_supply_id' => $iforder['user_pay_supply_id']))->getField('user_js_supply_id');
        //判断是否是多次返回回调
//        if (!$iforder){ //不存在订单交易
//
//            echo 'success';exit;
//        }elseif($iforder['jy_status'] == '1'){  //交易成功
//
//            echo 'success';exit;
//        }else{   //未成功
        if ($getJson['state'] == '5')   # 支付成功
        {
            # 是否结算(代付)成功
            if ($getJson['settleStatus'] == "0")  # 成功
            {
                $jsdata['js_status'] = 2;
                $jsdata['j_success_time'] = time() + 30;
                $js_status = 2;
                $ifjsorder = M('moneyjs_detailed')->where(array('pt_ordersn' => $pt_ordersn))->getField('pt_ordersn');
                if ($ifjsorder) {
                    M('money_detailed')->where(array('pt_ordersn' => $pt_ordersn))->save(array('jy_status' => 1, 'js_status' => $js_status));
                    M('moneyjs_detailed')->where(array('pt_ordersn' => $pt_ordersn))->save($jsdata);
                }
                if ($pt_ordersn) {
//                        # 产生分销/分润订单 - 交易
//                        R("Func/Fenxiao/fenxiaoByOrder",array($pt_ordersn));
                    # 结算成功订单分销
                    R("Func/Fenxiao/jiesuanfenxiaoByOrder", array($pt_ordersn));

                    R("Func/Fenxiao/fenxiaoTkJsLevelOrder", array($pt_ordersn)); # 结算分 - 固定收益

                }
            } else if ($getJson['settleStatus'] == "1") # 失败
            {
                $jsdata['js_status'] = 3;
                $jsdata['j_success_time'] = "";
                $js_status = 3;
                $ifjsorder = M('moneyjs_detailed')->where(array('pt_ordersn' => $pt_ordersn))->getField('pt_ordersn');
                if ($ifjsorder) {
                    M('money_detailed')->where(array('pt_ordersn' => $pt_ordersn))->save(array('jy_status' => 1, 'js_status' => $js_status));
                    M('moneyjs_detailed')->where(array('pt_ordersn' => $pt_ordersn))->save($jsdata);
                }
                if ($pt_ordersn) {
                    # 产生分销/分润订单 - 交易
//                        R("Func/Fenxiao/fenxiaoByOrder",array($pt_ordersn));
//                    # 结算成功订单分销
//                    R("Func/Fenxiao/jiesuanfenxiaoByOrder",array($pt_ordersn));
                }
            } else { # 处理中
//                    R("Payapi/Api/PaySetLog",array("./PayLog","Xj_shjjReturn_","--产生分销的用户--".$jsdata."-- 时间:".date('Y-m-d H:i:s')."------\r\n"));
                $jsdata['js_status'] = 1;
                $jsdata['j_success_time'] = "";
                $js_status = 1;
                $ifjsorder = M('moneyjs_detailed')->where(array('pt_ordersn' => $pt_ordersn))->getField('pt_ordersn');
                if ($ifjsorder) {
                    M('money_detailed')->where(array('pt_ordersn' => $pt_ordersn))->save(array('jy_status' => 1, 'js_status' => $js_status));
                    M('moneyjs_detailed')->where(array('pt_ordersn' => $pt_ordersn))->save($jsdata);
                }

                if ($pt_ordersn) {
//                    # 结算成功订单分销
//                    R("Func/Fenxiao/jiesuanfenxiaoByOrder",array($pt_ordersn));
                }
            }

            # 产生分销/分润订单 - 交易
            R("Func/Fenxiao/fenxiaoByOrder", array($pt_ordersn));
            R("Func/Fenxiao/fenxiaoTkLevelOrder", array($pt_ordersn)); # 交易分 - 固定收益

        } else {
            # xxxx 交易不成功 ( 上游返回结算失败，验签失败.... )
        }
//        }


        echo 'success';
        exit;
//        echo 'SUCCESS';die;
    }

    #同步返回地址
    public function hmPayUrl()
    {
        echo '请返回上一页,进行操作';
        die;
//        goback('支付成功,请返回上一页');die;
//        echo '支付成功';die;
    }
    #测试
    public  function hanzi()
        {
            import("ORG.Util.Pinyin");
            $py = new \PinYin();
            echo $py->getAllPY("林良振").'<BR/>'; //shuchuhanzisuoyoupinyin
            echo $py->getFirstPY("输出汉字首拼音"); //schzspy

            die;
        }


}