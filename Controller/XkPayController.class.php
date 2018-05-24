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

class XkPayController extends Controller
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

    #商户进件
    public function xjPay(){
        $td = intval($_REQUEST['td']);
        $tytd = intval($_REQUEST['tytd']); // 收款通道
        $bankid = intval($_REQUEST['bankid']);
        $price = $_REQUEST['price'];
        $user_id=trim($_REQUEST['user_id']);
        /**/
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'td' => $_REQUEST['td'],  //1 快捷支付
            'tytd' => $_REQUEST['tytd'], //星洁  19有积分   20 无积分
            'price' => $_REQUEST['price'], //金额
            'bankid' => $_REQUEST['bankid'] //储蓄卡
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
        $tranDate = date("Ymd");
        $tranId = "XJ".date("YmdHis").mt_rand(10000,99999);
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
        $tranAmt = $price*100; //实际交易金额  //单位分 所以*100
        $orderDesc = "o2o交易";
        if ($tytd == '19'){ //有积分
            $shanghu = M('xj_info')->where(array('user_id'=>$user_id,'jifen_type'=>0))->find();
            if (!$shanghu){
                //进件参数
                $bankcard=$myBankNormal['cart'];//银行卡号
                $nick_name=$myBankNormal['nickname']; //姓名
                $idcard=$myBankNormal['idcard'];  //身份证
                $phone=$myBankNormal['mobile']; //手机号
                $pt_ordersn=date('YmdHis').mt_rand(10000,99999);  //订单号
                $sheng=M('province')->where(array('provids'=>$myBankNormal['province_id']))->getField('province');
                $shi=M('city')->where(array('cityids'=>$myBankNormal['city_id']))->getField('city');
                $address=$sheng.$shi; //地址
                $provinceCode='440000';//省份编码  广东
                $cityCode='440100';//城市编码  广州
                $fee0=4.8; //费率
                $d0fee=$yyrate; //提现费
                $pointsType=0;// 0带积分
                $pInfo=array(
                    'bankcard'=>$bankcard,
                    'nick_name'=>$nick_name,
                    'idcard'=>$idcard,
                    'phone'=>$phone,
                    'pt_ordersn'=>$pt_ordersn,
                    'address'=>$address,
                    'provinceCode'=>$provinceCode,
                    'cityCode'=>$cityCode,
                    'fee0'=>$fee0,
                    'd0fee'=>$d0fee,
                    'pointsType'=>$pointsType,
                );
                $returnInfo = $this->shanghujj($pInfo);
                if ($returnInfo['isSuccess'] === true){  //进件成功
//                    $mch_id = M('xj_info')->where(array('user_id'=>$user_id))->getField('mch_id');
                    $addShanghu['name']=$nick_name;
                    $addShanghu['idcard']=$idcard;
                    $addShanghu['bankcard']=$bankcard;
                    $addShanghu['phone']=$phone;
                    $addShanghu['user_id']=$user_id;
                    $addShanghu['mch_id']=$returnInfo['data'];
                    $addShanghu['jifen_type']=$pointsType;
                    $addShanghu['t']=time();
                    M('xj_info')->add($addShanghu);
                    $jy_order=array(
                        'totalFee'=>$tranAmt,
                        'agentOrderNo'=>date('YmdHis').mt_rand(10000,99999),
                        'notifyUrl'=>'https://wallet.insoonto.com/index.php/Payapi/XkPay/xjPayReturn',
                        'mchId'=>$returnInfo['data'],
                        'returnUrl'=>'https://wallet.insoonto.com/index.php/Payapi/XkPay/xjPayUrl',
                        'bankcard'=>$myBank['cart'],
                        'nick_name'=>$myBank['nickname'],
                        'idcard'=>$myBank['idcard'],
                        'phone'=>$myBank['mobile'],
                    );
                    $order_info =$this->placeOrder($jy_order);
                    if ($order_info['isSuccess'] === true){  //下单成功
                        $js_order['user_id']=$user_id; //$post['user_id']
                        $js_order['pt_ordersn']=$jy_order['agentOrderNo'];
                        $js_order['sh_ordersn']="";  // $order_info['data']['orderNo'] --
                        $js_order['price']=$tranAmt; //$post['price']
                        $js_order['jy_status']=1;
                        $js_order['js_status']=1;
                        $js_order['user_pay_supply_id']=$tytd;
                        $js_order['is_pay']=2;
                        $js_order['mch_id']=$returnInfo['data']; //$post['mch_id']
                        $js_order['t']=time();
                        M('xj_jyorder')->add($js_order);
                        echo json_encode(array('code'=>3445,'msg'=>$order_info['message'],'html'=>$order_info['data']['returnHtml']));die;
                    }else{   //订单失败
                        echo json_encode(array('code'=>3656,"msg"=>$order_info['message']));die;
                    }
                }else{
                    echo json_encode(array('code'=>3443,'msg'=>$returnInfo['message']));die;
                }
            }
        }else{ //无积分


        }
    }
    #下单
    public function placeOrder($order=array()){
        import('Vendor.xjPay.xjPay'); # 直接对接星洁
        $config = C("XJPAY");
        $rep = new \xjPay($config);
        import('Vendor.xjPay.Chongzhi'); # 对接银融通星洁
        $cz = new \Chongzhi();
//        dump($cz);die;
//        //测试
        $pdata=array(
            'totalFee'=>100*100,
            'agentOrderNo'=>date('YmdHis').mt_rand(10000,99999),
            'notifyUrl'=>'https://wallet.insoonto.com/index.php/Payapi/XkPay/xjPayReturn',
            'mchId'=>'19040736',
            'returnUrl'=>'https://wallet.insoonto.com/index.php/Payapi/XkPay/xjPayUrl',
            'bankcard'=>'6226890176450178',
            'nick_name'=>'曾加涛',
            'idcard'=>'431024199901260073',
            'phone'=>'13548750126',
        );
        $data = $cz->xjDeal($order);
        $returnInfo=json_decode($data,true);
        if ($returnInfo['code'] == '0001'){ #交易成功
            $FhInfo = array(
                'isSuccess'=>true,
                'data'=>$returnInfo['data'],
                'message'=>$returnInfo['msg'],
                'code'=>$returnInfo['code']
            );
            return $FhInfo;
        }else{
            $FhInfo = array(
                'isSuccess'=>false,
                'data'=>$returnInfo['data'],
                'message'=>$returnInfo['msg'],
                'code'=>$returnInfo['code'],
            );
            return $FhInfo;
        }
//        dump($data);die;
//        $data = $rep->orderBuy($order);
//        dump($data);die;
//        return $data;
    }
    #商户进件接口(不要修改 这是线上接口)
    public function shanghujj($pdata=array()){
        import('Vendor.xjPay.xjPay');
        $config = C("XJPAY");
        $rep = new \xjPay($config);
        import('Vendor.xjPay.Chongzhi'); # 对接银融通星洁
        $cz = new \Chongzhi();
        $selfInfo = M('myrealname')->where(array('nickname'=>$pdata['nick_name'],'idcard'=>$pdata['idcard']))->find();
        $zm = M('mybank')->where(array('cart'=>$pdata['bankcard']))->getField('cart_img');
        $pdata['cardFmImg'] = $selfInfo['card_fm'];
        $pdata['cardZmImg'] = $selfInfo['card_zm'];
        $pdata['jsCardImg'] = $zm;
        //        $bankcard='6212263602105815484';//银行卡号
//        $nick_name='曾加涛'; //姓名
//        $idcard='431024199901260073';  //身份证
//        $phone='13667467921'; //手机号
//        $pt_ordersn=date('YmdHis').mt_rand(10000,99999);  //订单号
//        $address='广东省广州市'; //地址
//        $provinceCode='430000';//省份编码
//        $cityCode='431000';//城市编码
//        $fee0=0.48; //费率
//        $d0fee=200; //提现费
//        $pointsType=0;// 0带积分
//        $pInfo=array(
//            'bankcard'=>$bankcard,
//            'nick_name'=>$nick_name,
//            'idcard'=>$idcard,
//            'phone'=>$phone,
//            'pt_ordersn'=>$pt_ordersn,
//            'address'=>$address,
//            'provinceCode'=>$provinceCode,
//            'cityCode'=>$cityCode,
//            'fee0'=>$fee0,
//            'd0fee'=>$d0fee,
//            'pointsType'=>$pointsType,
//        );
        $pdata['fee0'] = $pdata['fee0']/10;
        $data = $cz->xjSend($pdata);
        $returnInfo = json_decode($data,true);
        if ($returnInfo['code'] == '0000'){ #进件成功
            $FhInfo = array(
                'isSuccess'=>true,
                'data'=>$returnInfo['data'],
                'message'=>$returnInfo['msg']
            );
            return $FhInfo;
        }else{ # 进件失败
            $FhInfo = array(
                'isSuccess'=>false,
                'data'=>$returnInfo['data'],
                'message'=>$returnInfo['msg']
            );
            return $FhInfo;
        }
//        dump($data);die;
//        $data= $rep->createSh($pdata);
//        dump($data);die;
//        return $data;
    }

    #异步回调
    public function xjPayReturn(){
//        error_reporting(0);
        $input = file_get_contents('php://input');
//        $output=$_REQUEST;
//        $param = explode('&',$input);
        R("Payapi/Api/PaySetLog",array("./PayLog","XjPay_Return_","----星洁回调返回信息参数----".$input."-- 时间:".date('Y-m-d H:i:s')."------\r\n"));
        //        error_reporting(0);
        $getJson = json_decode($input,true);

        # 回调订单
        $pt_ordersn = trim($getJson['agentOrderNo']);  //平台订单
        $iforder = M('money_detailed')->field('user_id,user_pay_supply_id,service_charge')->where(array('pt_ordersn'=>$pt_ordersn))->find();
        $user_js_supply_id = M("user_pay_supply")->where(array('user_pay_supply_id'=>$iforder['user_pay_supply_id']))->getField('user_js_supply_id');
            if($getJson['state'] == '4')   # 交易成功, 结算成功
            {
                $jsdata['js_status'] = 2;
                $jsdata['j_success_time'] = time()+30;
                $js_status = 2;
                $ifjsorder = M('moneyjs_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->getField('pt_ordersn');
                if($ifjsorder)
                {
                    M('money_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->save(array('jy_status'=>1,'js_status'=>$js_status,'d_t'=>time()));
                    M('moneyjs_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->save($jsdata);
                }
                if($pt_ordersn)
                {
//                        # 产生分销/分润订单 - 交易
//                        R("Func/Fenxiao/fenxiaoByOrder",array($pt_ordersn));
                    # 结算成功订单分销
                    R("Func/Fenxiao/jiesuanfenxiaoByOrder",array($pt_ordersn));

                    R("Func/Fenxiao/fenxiaoTkJsLevelOrder",array($pt_ordersn)); # 结算分 - 固定收益

                }

                # 产生分销/分润订单 - 交易
                R("Func/Fenxiao/fenxiaoByOrder",array($pt_ordersn));
                R("Func/Fenxiao/fenxiaoTkLevelOrder",array($pt_ordersn)); # 交易分 - 固定收益
            }elseif($getJson['state'] == '0'){ #支付中,未结算
                $jsdata['js_status'] = 1;
                $jsdata['j_success_time'] = "";
                $js_status = 4;
                $ifjsorder = M('moneyjs_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->getField('pt_ordersn');
                if($ifjsorder)
                {
                    M('money_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->save(array('jy_status'=>4,'js_status'=>$js_status));
                    M('moneyjs_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->save($jsdata);
                }

            }elseif($getJson['state'] == '1'){ #支付失败,不结算
                $jsdata['js_status'] = 3;
                $jsdata['j_success_time'] = "";
                $js_status = 4;
                $ifjsorder = M('moneyjs_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->getField('pt_ordersn');
                if($ifjsorder)
                {
                    M('money_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->save(array('jy_status'=>2,'js_status'=>$js_status));
                    M('moneyjs_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->save($jsdata);
                }

            }elseif($getJson['state'] == '2'){ #支付完成还未结算
                $jsdata['js_status'] = 1;
                $jsdata['j_success_time'] = "";
                $js_status = 4;
                $ifjsorder = M('moneyjs_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->getField('pt_ordersn');
                if($ifjsorder)
                {
                    M('money_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->save(array('jy_status'=>1,'js_status'=>$js_status));
                    M('moneyjs_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->save($jsdata);
                }
                # 产生分销/分润订单 - 交易
                R("Func/Fenxiao/fenxiaoByOrder",array($pt_ordersn));
                R("Func/Fenxiao/fenxiaoTkLevelOrder",array($pt_ordersn)); # 交易分 - 固定收益

            }elseif($getJson['state'] == '3'){ #支付完成,结算中
                $jsdata['js_status'] = 1;
                $jsdata['j_success_time'] = "";
                $js_status = 1;
                $ifjsorder = M('moneyjs_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->getField('pt_ordersn');
                if($ifjsorder)
                {
                    M('money_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->save(array('jy_status'=>1,'js_status'=>$js_status));
                    M('moneyjs_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->save($jsdata);
                }
                # 产生分销/分润订单 - 交易
                R("Func/Fenxiao/fenxiaoByOrder",array($pt_ordersn));
                R("Func/Fenxiao/fenxiaoTkLevelOrder",array($pt_ordersn)); # 交易分 - 固定收益

            }elseif($getJson['state'] == '3'){  # 预支付
                $jsdata['js_status'] = 1;
                $jsdata['j_success_time'] = "";
                $js_status = 4;
                $ifjsorder = M('moneyjs_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->getField('pt_ordersn');
                if($ifjsorder)
                {
                    M('money_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->save(array('jy_status'=>4,'js_status'=>$js_status));
                    M('moneyjs_detailed')->where(array('pt_ordersn'=>$pt_ordersn))->save($jsdata);
                }
            }



        echo 'success';exit;
//        echo 'SUCCESS';die;
    }
    #同步返回地址
    public function xjPayUrl(){
        echo '请返回上一页,进行操作';die;
//        goback('支付成功,请返回上一页');die;
//        echo '支付成功';die;
    }
    #h5页面
    public function h5Pay(){
        $this->display();
    }
    #交易查询接口 指定开始日期 和结束日期
    public function O2OOrder($order=array()){
        import('Vendor.xjPay.xjPay');
        $config = C("XJPAY");
        $rep = new \xjPay($config);
        $pdata=array(
            'startDate'=>'2018-01-18',  //开始日期
            'endDate'=>'2018-01-24',     //结束日期
        );
       $data = $rep->O2OSelect($pdata);
       dump($data);
       die;
    }
    #订单查询
    public function orderAssign($order=array()){
        import('Vendor.xjPay.xjPay');
        $config = C("XJPAY");
        $rep = new \xjPay($config);
        $pdata=array(
            'agentOrderNo'=>'XJF2018012512193466466',   //订单号
        );
        $data =  $rep->orderAssign($pdata);
        return $data;
//        dump($data);
        die;
    }
    #修改费率
    public function updateFee(){
        import('Vendor.xjPay.xjPay');
        $config = C("XJPAY");
        $rep = new \xjPay($config);
        $pinfo=array(
            'nonceStr'=>date('YmdHis').mt_rand(10000,99999),
            'mchId'=>14118763,
            'fee0'=>3.8,
            'd0fee'=>200,
            'bankcard'=>'6228480606400544171',
            'nick_name'=>'林良振',
            'idcard'=>'440881199402215552',
            'phone'=>'13790957780',
            'provinceCode'=>430000, //省份编码
            'cityCode'=>431000,  //城市编码
        );
        $data = $rep->updateInfo($pinfo);
        dump($data);
        die;
    }
}