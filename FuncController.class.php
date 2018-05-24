<?php

# 银钱包 - 全站公共函数



namespace Func\Controller;

use Think\Controller;

class FuncController extends Controller {
    
    
    
    # 获取缩略图
    
    public function getLunbo($type=1)
    
    {
        
        $w['type'] = $type;
        
        $w['status'] = 1;
        
        $list = M('banner')->where($w)->select();
        
        foreach ($list as $k=> $v)
        
        {
            $list[$k]['icon'] = $v['thumb'];
            $list[$k]['thumb'] = enThumb('/Public/',$v['thumb']);
            
        }
        
        return $list;
        
    }
    
    
    
    
    
    public function index(){
        
        return '';
        
    }
    
    
    
    # 跳转到无效页面
    
    public function errortpl()
    
    {
        
        echo '无效页面';
        
    }
    
    
    
    # get请求
    
    public function globalCurlGet($url){
        
        $curl = curl_init(); // 启动一个CURL会话
        
        curl_setopt($curl, CURLOPT_URL, $url);
        
        curl_setopt($curl, CURLOPT_HEADER, 0);
        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        
        $tmpInfo = curl_exec($curl);     //返回api的json对象
        
        //关闭URL请求
        
        curl_close($curl);
        
        return $tmpInfo;    //返回json对象
        
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
    
    
    
    # post请求
    
    public function globalCurlPostbak($url,$data='',$second=30){
        
        $ch = curl_init();
        
        $header = "Accept-Charset: utf-8";
        
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        
        curl_setopt($ch, CURLOPT_URL, $url);
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $temp = curl_exec($ch);
        
        curl_close($ch);
        
        return $temp;
        
    }
    
    
    
    # 计算两点的距离km
    
    public function getDistance($lat1, $lng1, $lat2, $lng2)
    
    {
        
        $earthRadius = 6370996.81;
        
        $lat1 = $lat1 * pi() / 180;
        
        $lng1 = $lng1 * pi() / 180;
        
        $lat2 = $lat2 * pi() / 180;
        
        $lng2 = $lng2 * pi() / 180;
        
        $calcLongitude = $lng2 - $lng1;
        
        $calcLatitude = $lat2 - $lat1;
        
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        
        
        
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        
        $calculatedDistance = $earthRadius * $stepTwo;
        
        
        
        return round($calculatedDistance);
        
    }
    
    
    
    # 获取当前ip地址(针对PC/微信端) - 暂时不用
    
    public function getIpAdr() {
        
        $unknown = 'unknown';
        
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)){
            
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            
        }elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)) {
            
            $ip = $_SERVER['REMOTE_ADDR'];
            
        }
        
        /**
        
        * 处理多层代理的情况
        
        * 或者使用正则方式：$ip = preg_match("/[\d\.]{7,15}/", $ip, $matches) ? $matches[0] : $unknown;
        
        */
        
        if (false !== strpos($ip, ',')) $ip = reset(explode(',', $ip));
        
        return $ip;
        
    }
    
    
    
    # 判断用户表中的tg_code推广码
    
    public function creatTgCode($code="")
    
    {
        
        global $code;
        
        $code = createCode(5);
        
        $is = M("user")->where(array("tg_code"=>$code))->getField("user_id");
        
        if($is)
        
        {
            
            return $this->creatTgCode($code);
            
        }else{
            
            return $code;
            
        }
        
    }
    
    
    
    # 发送短信
    
    public function sendMessage($mobile,$msg,$needstatus=true,$end_time=86400){
        
        
        
        //$account = 'jiekou-clcs-10';
        
        //$pswd = 'Aa456123';
        
        // $account = 'VIP8huazhe';
        
        // $pswd = 'Aa666666';
        
        $account = 'VIP8insoonto';
        
        $pswd = 'Tch547447';
        
        if($needstatus){
            
            $needstatus = 'true';
            
        }else{
            
            $needstatus = 'false';
            
        }
        
        
        
        //提交的路径
        
        $url = "http://222.73.117.156/msg/HttpBatchSendSM?";
        
        //参数
        
        $param = "account={$account}&pswd={$pswd}&mobile={$mobile}&msg={$msg}&needstatus={$needstatus}";
        
        // $data['account']=$account;
        
        // $data['pswd']=$pswd;
        
        // $data['mobile']=$mobile;
        
        // $data['msg']=$msg;
        
        // $data['needstatus']=$needstatus;
        
        $status = $this->globalCurlPost($url.$param);
        
        return $status;
        
        
        
    }
    
    
    
    # 我的实名认证信息
    
    public function getMyInfo($uid=0)
    
    {
        //array('user_id'=>$uid,'status'=>1)
        $d = M("myrealname")->where(" user_id ='{$uid}' and status != 3 ")->find();
        
        return $d;
        
    }
    
    
    
    # 查询我所绑定的银行卡
    
    # type = 1 为储蓄卡，2为信用卡  mo = 1 1为默认第一个 tytd = 1所属支付通道ID

    public function getMyBank($uid=0,$type=1,$mo=1,$tytd=0)
    {
        if($mo == 1)
        {
            $wbank['user_id'] = $uid;
            $wbank['status'] = 1;
            $wbank['type'] = $type;
            $wbank['jq_status'] = 3;
            // 通道ID
            if($tytd>0)
            {
                $pay_supply_id = M("user_pay_supply")->where(array('user_pay_supply_id'=>$tytd))->getField('pay_supply_id');
                $tdbank_id = M("pay_supply")->where(array('pay_supply_id'=>$pay_supply_id))->getField('bank_id');
                $wbank['bank_id'] = array('in',$tdbank_id);
                $d = M("mybank")->where($wbank)->order('is_normal asc')->find();
            }else{
                $d = M("mybank")->where($wbank)->order('is_normal asc')->find();
            }

//            R("Payapi/Api/PaySetLog", array("./PayLog", "mybanktest_", '----测试银行列表记录----' . json_encode($tdbank_id).'----'.json_encode($tytd).'---'.json_encode($wbank)));

            if($d)
            {
                $d['bankinfo'] = M("bank")->where(array('status'=>1,'bank_id'=>$d['bank_id']))->find();
            }
        }else{

            $wbank['user_id'] = $uid;
            $wbank['status'] = array('neq',3);
            $wbank['type'] = $type; //1
            $wbank['jq_status'] = 3;

            // 通道ID
            if($tytd>0)
            {
                $pay_supply_id = M("user_pay_supply")->where(array('user_pay_supply_id' => $tytd))->getField('pay_supply_id');
                $tdbank_id = M("pay_supply")->where(array('pay_supply_id' => $pay_supply_id))->getField('bank_id');
//                var_dump($tdbank_id);
//                $wbank['bank_id'] = array('in', $tdbank_id);
                $d = M("mybank")->where($wbank)->order('is_normal asc')->select();
                foreach ($d as $k => $v)
                {
                    if(in_array($v['bank_id'],explode(",",$tdbank_id)))
                    {
                        $d[$k]['iszc'] = 1;  # 支持
                    }else{
                        $d[$k]['iszc'] = 2;  # 不支持
                    }
//                    $d[$k]['arrayzc'] = array($v['bank_id'],explode(",",$tdbank_id));

                }
            }else{

                $d = M("mybank")->where($wbank)->order('is_normal asc')->select();


            }
            foreach ($d as $k => $v)
            {
                $d[$k]['bankinfo'] = M("bank")->where(array('status'=>1,'bank_id'=>$v['bank_id']))->find();
            }
        }
        
        
        
        return $d;
        
    }
    
    //限制验证码次数
    
    public function limitYzm(){
        
        $yzmSj = intval(session('yzm')) == 0 ? 0 : intval(session('yzm'));
        
        if ($yzmSj >= 10){
            
            echo json_encode(array('code'=>3,'error'=>'一天内只能发送10次验证码'));die;
            
        }
        
        session('yzm',++$yzmSj);
        
    }
    
    
    
    # 支付通道编码
    
    public function payTd()
    
    {
        
        $d = array(
            
            'HLBKJDFL' => '合利宝-快捷(低费率)',
            
            'HLBKJDE' => '合利宝-快捷(大额)',
            
            'WFTWXSM' => '威富通-微信扫码',
            
            'HYBKJDFL' => '翰银-快捷'
            
        );
        
        return $d;
        
    }
    
    
    
    # 结算通道
    
    public function payJsTd()
    
    {
        
        $d = array(
            
            'HLB' => '合利宝',
            
            'WFT' => '威富通',
            
            'HY' => '翰银'
            
        );
        
        return $d;
        
    }
    
    
    
    # 结算方式
    
    public function payJsType()
    
    {
        
        $d = array(
            
            'DOWALLET' => 'D0实时到钱包余额',
            
            'D0BANK' => 'D0实时到默认结算卡'
            
        );
        
        return $d;
        
    }
    
    
    
    # 提现方式
    
    public function payYxType()
    
    {
        
        $d = array(
            
            'DAIFU' => '代付',
            
            'ZHIQING' => '直清'
            
        );
        
        return $d;
        
    }
    
    
    
    # 收款通道
    
    public function getSkTd($td=1)
    
    {

        $tdid = M('pay_supply_cate')->where(array('pay_supply_cate_id'=>intval($td)))->getField('pay_supply_cate_id');
        $res = array();

        $tyname = $this->getPayType($td); //快捷支付
        if($tdid)
        
        {
            
            $sid = M('pay_supply')->field('pay_supply_id,englistname')->where(array('pay_supply_cate_id'=>$tdid))->order('pay_supply_id desc')->select();
            if($sid)
            
            {
                
                $exsid = "";
                
                foreach ($sid as $k => $v){
                    
                    $exsid .= ",".$v['pay_supply_id'];
                    
                }
                $exsid = ltrim($exsid,',');
//                $exsid = substr($exsid,0,-1);


//                dump($exsid);die;



                $res = M('user_pay_supply')->where(array('pay_supply_id'=>array('in',$exsid),'status'=>1))->order('user_pay_supply_id desc')->select();
                
                
                
                # 支付名称
                
                foreach ($res as $k => $v)
                
                {
                    
                    $res[$k]['typename'] = $sid[$k]['englistname'].'-'.$tyname;
                    
                    //                    $doinfo = $this->payJsType();
                    
                    
                    
                    //                    var_dump(key($doinfo));
                    
                    if($v['do_type'] == 'DOWALLET')
                    
                    {
                        
                        $do_type_name = "D0我的余额";
                        
                    }else if($v['do_type'] == 'D0BANK'){
                        
                        $do_type_name = "D0默认结算卡";
                        
                    }else{
                        $do_type_name = "D0其他";
                    }
                    
                    $res[$k]['do_type_name'] = $do_type_name;


                    # 新增结算提现/元-笔
                    $yy_rate = M("user_js_supply")->where(array('sid'=>$v['pay_supply_id']))->getField('yy_rate');
                    $res[$k]['js_txmoney'] = $yy_rate;

                    
                }
                
            }
            
        }

//                dump($res);
        
        return $res;
        
    }
    
    
    
    # 支付类型名称
    
    public function getPayType($td=1)
    
    {
        
        return M('pay_supply_cate')->where(array('pay_supply_cate_id'=>$td))->getField('name');
        
    }
    
    //话费充值生成订单
    
    public function downOrder($goodsid=0,$phone='',$pay_type='',$uid=0){
        
        if (!$goodsid){
            
            $array['errCode']=1;
            
            $array['msg']='未选择产品';
            
            return $array;
            
        }
        
        if (!$phone){
            
            $array['errCode']=2;
            
            $array['msg']='手机号为空';
            
            return $array;
            
        }
        
        if (!$pay_type){
            
            $array['errCode']=3;
            
            $array['msg']='充值方式未选择';
            
            return $array;
            
        }
        
        if(!preg_match("/^1[34578]{1}\d{9}$/",$phone)){
            
            $array['errCode']=4;
            
            $array['msg']='手机号码格式错误';
            
            return $array;
            
        }
        
        //查询出产品
        
        $goods=M("chongzhi")->where(array('chongzhi_id'=>$goodsid))->find();  //查询产品
        
        $type=$goods['type'];  //产品类型 分为money(话费)和flow(流量)
        
        $fee=$goods['fee']; //价格
        
        $lirun=$goods['lirun']; //利润
        
        if(!$type){
            
            $array['errCode']=5;
            
            $array['msg']='充值类型错误';
            
            return $array;
            
        }
        
        //        $uid = session('uid');
        
        $data = array(
            
            "user_id" => $uid,
            
            //            "openid" => cookie('openid'),
            
            "phone" => $phone,
            
            'cztongdao_id'=>$goods['cztongdao_id'],
            
            "goods_id" => $goods['chongzhi_id'],
            
            "fee" => $fee,
            
            "lirun" => $lirun,
            
            "goodsname" => $goods['pname'],
            
            "isfukuan" => "未付款",
            
            "ischongzhi" => "未充值",
            
            "order_number" => create_orderno(),
            
            "type" => $type,
            
            "area" => $goods['area'],
            
            "time"=>time(),
            
            "tongdao" => $goods['tongdao'],  //充值通道
            
            "pay_type"=>$pay_type
            
        );
        
        $inId=M('chongzhi_order')->add($data);
        
        if($inId){
            
            $o = M('chongzhi_order')->field('chongzhi_id,order_number')->where(array('chongzhi_id'=>$inId))->find();
            
            $array['errCode']=0;
            
            $array['msg']='生成订单成功';
            
            $array['data']=array(
                
                "order_number" => $o['order_number'],   # 订单号
                
                "order_id" => $o['chongzhi_id']   # 订单ID
                
            );
            
            return $array;
            
        }else{
            
            $array['errCode']=6;
            
            $array['msg']='订单生成错误';
            
            return $array;
            
        }
        
    }
    
    //调用充值接口
    
    public function callChongzhi($order_id=0){
        
        if (!$order_id){
            
            $array['errCode']=1;
            
            $array['msg']='订单参数不存在';
            
            return $array;
            
        }
        
        $order_info=M('chongzhi_order')->where(array('chongzhi_id'=>$order_id))->find();
        
        if (!$order_info){
            
            $array['errCode']=2;
            
            $array['msg']='订单不存在';
            
            return $array;
            
        }
        
        $pay = new \Com\Kaixin\Chongzhi;
        
        //开心充值话费
        
        if ($order_info['tongdao_id'] == 1){
            
            //话费参数
            
            $array['order_number'] = $order_info['order_number']; //订单号
            
            $array['phone'] = $order_info['phone'];
            
            $miane['price'] = 1;//面额
            
            $res = $pay->moneyCharge($array,$miane); //话费充值
            
            $res = json_decode($res,true);
            
            return $res;
            
        }
        
        //开心充值流量
        
        if ($order_info['tongdao_id'] == 2){
            
            //流量参数
            
            $liuliang['order_number'] = $order_info['order_number'];
            
            $liuliang['phone']=$order_info['phone'];
            
            $liuliang['goods_id']=$order_info['goods_id'];
            
            $liuliang['type']=$order_info['cztongdao_id'];
            
            $qitaC['unit']=10;
            
            $res = $pay->flowCharge($liuliang,$qitaC);
            
            $res = json_decode($res,true);
            
            return $res;
            
        }
        
        //19e充值话费
        
        if ($order_info['cztongdao_id'] == 3){
            
            $array['errCode']=3;
            
            $array['msg']='暂未测试19e通道';
            
            return $array;
            
        }
        
    }
    
    //查询微信菜单 分等级
    
    public function menuLevel($data,$pid=0,$level=0){
        
        static $array = array();
        
        foreach ($data as $k=>$v){
            
            if ($v['menu_pid'] == $pid){
                
                $v['level'] = $level;
                
                $array[]=$v;
                
                $this->menuLevel($data,$v['wxmenus_id'],$level+1);
                
            }
            
        }
        
        return $array;
        
    }
    
    //获得一级菜单
    
    public function selectOne($data){
        
        static $list = array();
        
        foreach ($data as $k=>$v){
            
            if ($v['menu_pid'] == 0){
                
                $list[]=$v;
                
            }
            
        }
        
        return $list;
        
    }
    
    //获得当前id的自己和下级ID
    
    public function getChild($data,$id=0){
        
        static $list = array();
        
        foreach ($data as $k => $v){
            
            if($v['menu_pid'] == $id){
                
                $list[] = $v['auth_id'];
                
                $this->getChild($data, $v['wxmenus_id']);
                
            }
            
        }
        
        return $list;
        
    }
    
    //返回加密后的sign
    
    public function getKey($param=array()){

//        echo json_encode(array('code'=>10004,'msg'=>'正在全力的优化中，更多精彩尽在钱兜兜，请稍后再来!'));die;
        
        $arg='';$url='';
        
        foreach($param as $key=>$val){
            
            //$arg.=$key."=".urlencode($val)."&amp;";
            
            $arg.=$key."=".urlencode($val)."&amp;";
            
        }
        
        $url.= $arg;
        
        $str=rtrim($url, "&amp;");
        
        $str=str_replace("&amp;","&",$str);
//        return $str;
        //        dump($str);die;
//        return strtoupper(md5($str));
        $sign=md5(strtoupper(md5($str)).'YINXUNTONG');

        //

        $sign = strtoupper($sign);

        //        dump($sign);die;

        return $sign;
//
    }
    
    //禁止相同sign参数第二次访问(如果正常调用接口因为参数中有时间戳所以不可能sign相同,防止抓包再发送,抓包再发送sign肯定和session中的sign相同,所以禁止访问)
    
    public function getTwoSign($sign=''){
        
        if ($sign == $_SESSION['last_sign']){
            
            echo json_encode(array('code'=>10006,'msg'=>'禁止第二次访问'));die;
            
        }
        
    }


    //返回当前的毫秒时间戳

    function msectime() {

        list($msec, $sec) = explode(' ', microtime());

        $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

        $douke =2;

        $shuzi = substr($msectime,4,11);

        //        dump(intval($douke.$shuzi));die;

        return intval($douke.$shuzi);

        //        var_dump();

    }


    //返回当前的毫秒时间戳

    function getMillisecond() {

        list($msec, $sec) = explode(' ', microtime());

        $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

        $douke =3;

        $shuzi = substr($msectime,4,11);

        //        dump($douke.$shuzi);die;

        return $douke.$shuzi;

        //        var_dump();

    }


    //返回当前的毫秒时间戳  机构

    function getMillise($douke=8,$length=4) {

        list($msec, $sec) = explode(' ', microtime());

        $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

        //        $douke =8;

        $shuzi = substr($msectime,$length,11);

        //        dump($douke.$shuzi);die;

        return $douke.$shuzi;

        //        var_dump();

        //        89995 812

    }

    
    // 查询佣金/分润可结算金额
    
    public function getdisJsYj($uid)
    
    {
        
        //可结算金额
        
        $moneyData = R('Func/User/index',array($uid)); //总分销额
        
        $retailData = R('Func/User/successClose',array($uid));//已经结算的金额
        
        
        
        $money = 0;
        
        $sumClose = 0;
        
        foreach ($moneyData as $k=>$v){
            
            $money+=$v['money'];
            
        }
        
        foreach ($retailData as $k=>$v){
            
            $sumClose+=$v['money'];
            
        }
        
        $money = sprintf("%.2f",$money);//总分销额
        
        $sumClose = sprintf("%.2f",$sumClose);//成功结算功能
        
        $canMoney = $money - $sumClose;
        
        $canMoney = sprintf("%.2f",$canMoney);  # 可结算金额
        
        return array($money,$sumClose,$canMoney);
        
    }

    
    /**
    
    * [send_mobile description]
    
    * @param  string $msg 短信模板 + 验证码
    
    * @return [type]
    
    */
    
    public function send_mobile($phone="",$msg = "")
    
    {
        
        header('Content-Type:text/html;charset=utf-8');
        
        // $code = rand(100000,999999);
        
        $msg = $msg;
        
        // $_SESSION['code'] = $code;
        
        $post_data = array();
        
        
        
        $sendConfig = C("SEND");
        
        // var_dump($sendConfig);exit;
        
        $post_data['account'] ="VIP8insoonto";
        
        $post_data['pswd'] = "Tch547447";
        
        $post_data['msg']="{$msg}";
        
        $post_data['mobile'] =$phone;
        
        $post_data['needstatus']='true';
        
        $url='http://sapi.253.com/msg/HttpBatchSendSM';
        
        $res=$this->curlPost($url,$post_data);


        # 保存日志
//        R("Payapi/Api/PaySetLog",array("./PayLog","SEND__","------- 短信验证码请求参数 -------  "."---------".json_encode($res)."------- \r\n"));
        
        return $res;
        
        // dump($res);
        
        
        
        // $msg = iconv("GB2312", "UTF-8", "$msg");
        
        // $res = R("Func/Func/sendMessage",array('13726937991',$msg));
        
        // dump($res);exit;
        
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

    /**

     * 验证token是否相同,保存token
     * @param token //唯一标识
     * @user_id   //用户id
     */
    public function saveToken($token,$user_id){
       $res = M('user')->where(array('user_id'=>$user_id))->find();
       if($res['token'] != $token){
           $res['token'] = $token;
           $re = M('user')->where(array('user_id'=>$user_id))->save($res);
           if ($re){
               echo json_encode(array('code'=>7777,'msg'=>'token与原token不一致.'));die;
           }else{
               echo json_encode(array('code'=>7776,'msg'=>'token更新失败.'));die;
           }
       }
       if ($res['token'] == ''){
           $res['token'] = $token;
           M('user')->where(array('user_id'=>$user_id))->save($res);
       }
    }
    /**

     * 登录保存token
     * @param token //唯一标识
     * @user_id   //用户id
     */
    public function updToken($token,$user_id){
        $res = M('user')->where(array('user_id'=>$user_id))->find();
        if ($res['token'] == ''){
            $res['token'] = $token;
            M('user')->where(array('user_id'=>$user_id))->save($res);
        }
        if($res['token'] != $token){
            $res['token'] = $token;
            $re = M('user')->where(array('user_id'=>$user_id))->save($res);
            if ($re){
                echo json_encode(array('code'=>7778,'msg'=>'token更新成功.'));die;
            }else{
                echo json_encode(array('code'=>7776,'msg'=>'token保存失败.'));die;
            }
        }
    }
        /*
         * 扫码生成的id  未注册时
         */
        public function createRegisterId(){
           $str=time();
           $res = $str.mt_rand(1,1000);
           return $res;
        }




## -- 重新生成方法
//    会员：DK300000000   商家：SJ400000000   推客：TK200000000   机构：JG100000000
# 生成用户ID方法 - 兜客、商家、推客 - user表
//    utype == 1; 会员 10 商家 20推客
    public function setUserIdRandBak($vcha = "DK", $utype = 1)
    {
        # 按照时间排序
        $currentUid = M('user')->where(array('utype'=>$utype))->order('user_id desc')->getField('user_id');

        R("Payapi/Api/PaySetLog",array("./PayLog","Func_setUserIdRand__ ",json_encode($currentUid)));
        
        # 截取后面8位数，进行+1;
        if($utype == 1)
        {
            $num = 3;
        }else if($utype == 10){
            $num = 4;
        }else if($utype == 20)
        {
            $num = 2;
        }
        if($currentUid)
        {
            $substr = substr($currentUid,3);
            $ressubstr = $vcha.$num.str_pad(((int)$substr+1),8,"0",STR_PAD_LEFT); # 最终顺序生成方式
//            var_dump($currentUid);
        }else{
            # 没有数据的情况下，则重新生成第一个

            $ressubstr = $vcha.$num.'00000001';
        }
        return $ressubstr;
    }

    ## - 重新生成会员user_id - 新方法
    public function setUserIdRand($vcha = "DK", $utype = 1)
    {
        # 按照时间排序
        $currentUid = M('user')->order('user_id desc')->getField('user_id');

        R("Payapi/Api/PaySetLog",array("./PayLog","Func_setUserIdRand__ ",json_encode($currentUid)));

        # 截取后面8位数，进行+1;
        if($utype == 1)
        {
            $num = 3;
        }else if($utype == 10){
            $num = 4;
        }else if($utype == 20)
        {
            $num = 2;
        }
        if($currentUid)
        {
            $substr = substr($currentUid,3);
            $ressubstr = "Q".str_pad(((int)$substr+1),8,"0",STR_PAD_LEFT); # 最终顺序生成方式
//            var_dump($currentUid);
        }else{
            # 没有数据的情况下，则重新生成第一个

            $ressubstr = "Q".'00000001';
        }
//        echo $ressubstr;
        return $ressubstr;
    }


    # 生成机构用户ID
    public function setInstitutionIdRand($vcha = "JG")
    {
        $currentUid = M('institution')->order('institution_id desc')->getField('institution_id');
        # 截取后面8位数，进行+1;
        if($currentUid)
        {
            $substr = substr($currentUid,3);
            $ressubstr = $vcha.'1'.str_pad(((int)$substr+1),8,"0",STR_PAD_LEFT); # 最终顺序生成方式
//            var_dump($substr);
//            var_dump($ressubstr);
        }else{
            # 没有数据的情况下，则重新生成第一个
            $ressubstr = $vcha.'100000001';
        }
        return $ressubstr;
    }


    # 生成推广二维码id
    public function setTgCodeIdRand($vcha = "QD")
    {
        $currentUid = M('user')->order('tg_code desc')->getField('tg_code');
        # 截取后面8位数，进行+1;
        if($currentUid)
        {
            $substr = substr($currentUid,2);
            $ressubstr = $vcha.str_pad(((int)$substr+1),8,"0",STR_PAD_LEFT); # 最终顺序生成方式
//            var_dump($substr);
//            var_dump($ressubstr);
        }else{
            # 没有数据的情况下，则重新生成第一个
            $ressubstr = $vcha.'00000001';
        }
//        dump($ressubstr);die;
        return $ressubstr;
    }



}