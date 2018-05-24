<?php  
namespace Payapi\Controller;
use Think\Controller;

//app支付控制器
class AppPayController extends BaseController
 {
     public $uid;
     public $parem;
     public $sign;
     public function _initialize(){
         parent::_initialize();
         $this->uid = $_REQUEST['uid'];
         $this->parem=array(
             'signType'=>$_REQUEST['signType'],
             'timestamp' => $_REQUEST['timestamp'],
             'dataType' => $_REQUEST['dataType'],
             'inputCharset' => $_REQUEST['inputCharset'],
             'version' => $_REQUEST['version'],
         );
         $this->sign = $_REQUEST['sign'];
     }
     
     public  function  paynotify()
     {
         $input = file_get_contents('php://input');
         
         $param = explode('&',$input);
         # 收银台 - 快捷支付
         R("Payapi/Api/PaySetLog",array("./PayLog","AppPay_paynotify_Return__",'----回调返回信息参数----'.$input));
        
         $msg=json_decode($input);
         
         
     }
     
     //商家结算
     public function settlement(){
          
        if (!$this->sign){
              echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;
         } 
         
         $array=array(
             'uid'=>$this->uid,
         );
         $this->parem = array_merge($this->parem,$array);
          $msg = R('Func/Func/getKey',array($this->parem));//返回加密
         
         if ($this->sign !== $msg){
              echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;
         }
         R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
         $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
           
         
         $user_id=$this->uid;
         $money=$_REQUEST['money'];
         
         $wherejsway['sid']=array('eq',14);
         $wherejsway['status']=array('eq',1);
         $userjsway=M('user_js_supply')->where($wherejsway)->find();
         if(!$userjsway)
         {
             echo json_encode(array('code'=>6505,'msg'=>'结算通道未开启！','data'=>array()));die;
         }
         $user_js_supply_id=$userjsway['user_js_supply_id'];
         
         if((float)$money>(float)$userjsway['single_maxprice'])
         {
             echo json_encode(array('code'=>6502,'msg'=>'提现大于'.(float)$userjsway['single_maxprice'].'元单笔限额！','data'=>array()));die;
         }
         
         if((float)$money<(float)$userjsway['single_minprice'])
         {
             echo json_encode(array('code'=>6502,'msg'=>'提现小于'.(float)$userjsway['single_minprice'].'元单笔限额！','data'=>array()));die;
         }
         
         $money=(float)$money-(float)$userjsway['yy_rate'];
         $sx_money=(float)$userjsway['yy_rate'];
         $pt_money=(float)$userjsway['pt_rate'];
         if($money<=0)
         {
             echo json_encode(array('code'=>6502,'msg'=>'请填写大于'.(float)$userjsway['yy_rate'].'元金额！','data'=>array()));die;
         }
         
         
         
        
         
         $wherebusiness['user_id']=array('eq',$user_id);
         $business=M('business')->where($wherebusiness)->find();
         
         $business_id=$business['business_id'];
         $business_wallet=(float)$business['business_wallet'];
         if($business_wallet<($money+$sx_money))
         {
             echo json_encode(array('code'=>6501,'msg'=>'提现金额不能大于商户余额！','data'=>array()));die;
         }
         
         $wherebusinessorder['business_id']=array('eq',$business_id);
         $wherebusinessorder['jy_status']=array('eq',1);
         $ordermoney=M('business_order')->where($wherebusinessorder)->sum('money');
         if((float)$ordermoney<$business_wallet)
         {
             echo json_encode(array('code'=>6506,'msg'=>'该商户余额有操作违法记录！','data'=>array()));die;
         }
         
         $wherebusinesssettle['business_id']=array('eq',$business_id);
//          $wherebusinesssettle['js_status']=array('eq',2);
         $wherebusinesssettle['_string'] = 'js_status=1 OR js_status=2';
         $settlemoney=M('business_settlement')->where($wherebusinesssettle)->sum('js_money');
         $canmoney=(float)$ordermoney-(float)$settlemoney;
         if(($money+$sx_money)>$canmoney)
         {
             echo json_encode(array('code'=>6507,'msg'=>'提现金额不能大于可结算金额！','data'=>array()));die;
         }
         
         
         $wherebank['user_id']=array('eq',$user_id);
         $wherebank['is_normal']=array('eq',1);
         $mybany=M('mybank')->where($wherebank)->find();
         if(!$mybany)
         {
             echo json_encode(array('code'=>6503,'msg'=>'请先绑定默认结算卡！','data'=>array()));die;
         }
         $js_card=$mybany['cart'];
         $moneycheck=$this->moneycheck();
         if(!$moneycheck)
         {
             echo json_encode(array('code'=>6504,'msg'=>'系统正在维护，请稍后再操作！','data'=>array()));die;
         }
         
         $moneyjson=json_decode($moneycheck);
         if(($money+$pt_money)>$moneyjson->r2_AvailableBalance)
         {
             echo json_encode(array('code'=>6505,'msg'=>'系统正在清算，请稍后再操作！','data'=>array()));die;
         }
         
         
         //判断提现金额是否大于t1结算金额
         if($moneyjson->r4_AvailableSettAmount<($money+$pt_money))
         {
             //判断提现金额是否大于D0结算金额
             if($moneyjson->r7_AvailableAdvanceAmount<($money+$pt_money))
             {
                 echo json_encode(array('code'=>6504,'msg'=>'系统正在清算中，请稍后提现！','data'=>array()));die;
             }
         }
         
         $wherecity['cityid']=array('eq',$mybany['city_id']);
         $city=M('city')->where($wherecity)->getField('city');
         
         $config = C('HJPAY');
         $order="SHSKJS".date("YmdHis",time());
         $desc="商家收款结算";
         $data['p1_MerchantNo']=$config['MERCHANTNO'];
         $data['p2_BatchNo']=$order;
         $data['p3_Details']=$order.$user_id."|".$mybany['nickname']."|".$mybany['cart']."|".$money."|".$desc."|".$city."|"."0"."|"."2"."|".$mybany['lianhang'];
         $data['p4_ProductType']="3";
         
         $sign=$data['p1_MerchantNo'].$data['p2_BatchNo'].$data['p3_Details'].$data['p4_ProductType'];
         
         $key=$config['KEY'];
         
         $hmac=MD5($sign.$key); //签名数据
         
         $data['hmac']=$hmac;
         
         $url="https://www.joinpay.com/trade/batchProxyPayNew.action";
         
         $result=cURLSSLHttp($url,$data);
//          var_dump($data);
//          var_dump($result);
         $nowtime= date("Y-m-d h:i:s", time());
         # 写入日志
         R("Payapi/Api/PaySetLog",array("./PayLog","AppPay_settlement_Return__",$nowtime.'请求参数'.implode(',',$data).'----回调返回信息参数----'.$result));
         
         $msg=json_decode($result);
         if($msg->rb_Code=='100')
         {
             $ret_msg="请求成功";
             $ret_code="200";
             $ret_order=$msg->r2_BatchNo;
             $rc_CodeMsg=$msg->rc_CodeMsg;
             $data['state']=1;
             $data['rc_CodeMsg']=$msg->rc_CodeMsg;
             $data['rb_Code']=$msg->rb_Code;
         }else if($msg->rb_Code=='102'){
             $ret_msg="正在处理中";
             $ret_code="200";
             $ret_order=$msg->r2_BatchNo;
             $rc_CodeMsg=$msg->rc_CodeMsg;
             $order=$msg->r2_BatchNo;
             $code=$this->ordercheck($order);
             if($code==='100')
             {
                 $ret_msg="交易成功";
                 $ret_code="200";
                 $data['state']=1;
                 $data['rc_CodeMsg']="交易成功";
                 $data['rb_Code']=$code;
             }else if($code==='102')
             {
                 $ret_msg="正在处理中";
                 $ret_code="200";
                 $data['state']=2;
                 $data['rc_CodeMsg']=$msg->rc_CodeMsg;
                 $data['rb_Code']=$code;
             }else {
                 $ret_msg=$msg->rc_CodeMsg;
                 $ret_code="201";
                 $data['state']=3;
                 $ret_order=$msg->r2_BatchNo;
                 $rc_CodeMsg="请求失败";
                 $data['rc_CodeMsg']="请求失败";
                 $data['rb_Code']=$code;
             }
             
         }else {
             $ret_msg=$msg->rc_CodeMsg;
             $ret_code="201";
             $data['state']=3;
             $ret_order=$msg->r2_BatchNo;
             $rc_CodeMsg=$msg->rc_CodeMsg;
             $data['rc_CodeMsg']=$msg->rc_CodeMsg;
             $data['rb_Code']=$msg->rb_Code;
         }
         $js_card=$mybany['cart'];
         $data['user_id']=$user_id;
         $data['addtime']=time();
         $data['business_id']=$business_id;
         M('unipay_settlement')->add($data);
         $saveres=$this->businessorder($order,$data['state'],$money,$sx_money,$user_id,$business_id,$desc,$user_js_supply_id,$js_card);
//          $msg="{\"msg\":\"$ret_msg\",\"code\":\"$ret_code\",\"ret_order\":\"$ret_order\"}";
//          echo $msg;
         if($saveres){
             echo json_encode(array('code'=>$ret_code,'msg'=>$ret_msg,'ret_order'=>$ret_order,'data'=>array()));die;
         }else 
         {
             echo json_encode(array('code'=>6504,'msg'=>'系统正在维护！','data'=>array()));die;
         }
         
         
     }
     
     //生成提现订单
     private function businessorder($order,$state,$money,$sx_money,$user_id,$business_id,$desc,$user_js_supply_id,$js_card)
     {
         $js_money=$money+$sx_money;
         $tx_service=$sx_money;
         $rz_money=$money;
         $wherebusiness['user_id']=array('eq',$user_id);
         $business=M('business')->where($wherebusiness)->find();
         $before_money=(float)$business['business_wallet'];
         $js_status=1;
         $after_money=$before_money;
         if($state==3)
         {
             $js_status=3;
             $after_money=$before_money;
         }else if($state==2)
         {
             $js_status=1;
             $after_money=$before_money-($money+$tx_service);
         }else if($state==1)
         {
             $js_status=2;
             $after_money=$before_money-($money+$tx_service);
         }
         $datasave['business_wallet']=$after_money;
         $business=M('business')->where($wherebusiness)->save($datasave);
         $js_ordersn=$order;
         $pt_ordersn="SHSKJSORDER".date("YmdHis",time());
         $js_type=1;
         
         $data['js_ordersn']=$js_ordersn;
         $data['pt_ordersn']=$pt_ordersn;
         $data['user_id']=$user_id;
         $data['business_id']=$business_id;
         $data['js_status']=$js_status;
         $data['order_msg']=$desc;
         $data['user_js_supply_id']=$user_js_supply_id;
         $data['js_money']=$js_money;
         $data['tx_service']=$tx_service;
         $data['rz_money']=$rz_money;
         $data['before_money']=$before_money;
         $data['after_money']=$after_money;
         $data['js_type']=$js_type;
         $data['js_card']=$js_card;
         $data['type']=1;
         $data['sx_money']=0; 
         $data['t']=time();
         if($js_status==2)
         {
             $data['j_succee_time']=time(); 
         }
         
         $saveres=M('business_settlement')->add($data);
         
         if($state==3) //提现失败
         {
             sendToUser("{\"fromid\":\"1\",\"fromname\":\"钱兜兜\",\"user_id\":\"".$user_id."\",\"name\":\"\",\"token\":\"cebced47c9d6c22c9ed9c6df528caace83f04edb1a52780b49f53e975001bed4\",\"msg\":\"您提现的".$js_money."元提现失败，已转回您的余额\",\"operation\":\"text\",\"sound\":\"default\",\"content-available\":\"1\"}");
             
         }else if($state==2)  //提现中
         {
             sendToUser("{\"fromid\":\"1\",\"fromname\":\"钱兜兜\",\"user_id\":\"".$user_id."\",\"name\":\"\",\"token\":\"cebced47c9d6c22c9ed9c6df528caace83f04edb1a52780b49f53e975001bed4\",\"msg\":\"您提现的".$js_money."元正在处理中\",\"operation\":\"text\",\"sound\":\"default\",\"content-available\":\"1\"}");
             
         }else if($state==1) //提现成功
         {
             sendToUser("{\"fromid\":\"1\",\"fromname\":\"钱兜兜\",\"user_id\":\"".$user_id."\",\"name\":\"\",\"token\":\"cebced47c9d6c22c9ed9c6df528caace83f04edb1a52780b49f53e975001bed4\",\"msg\":\"您提现的".$js_money."元已经提现成功，请查询您的银行卡余额\",\"operation\":\"text\",\"sound\":\"default\",\"content-available\":\"1\"}");
             
         }
         
         return $saveres;
         
     }
     
     //商户结算订单查询
     public function ordercheck($order)
     {
         $config = C('HJPAY');
         $data['p1_MerchantNo']=$config['MERCHANTNO'];
         $data['p2_BatchNo']=$order;
         $sign=$data['p1_MerchantNo'].$data['p2_BatchNo'];
         $key=$config['KEY'];
         $hmac=MD5($sign.$key); //签名数据
         $data['hmac']=$hmac;
         $url="https://www.joinpay.com/trade/queryBatchProxyPay.action";
         $result=cURLSSLHttp($url,$data);
//          var_dump($data);
//          var_dump($result);
         $nowtime= date("Y-m-d h:i:s", time());
         # 写入日志
         R("Payapi/Api/PaySetLog",array("./PayLog","AppPay_ordercheck_Return__",$nowtime.'请求参数'.implode(',',$data).'----回调返回信息参数----'.$result));
         $msg=json_decode($result);
         
         return $msg->rb_Code;
         
     }
     
     //查询账户余额
     public function moneycheck()
     {
         $config = C('HJPAY');
         
         $data['p1_MerchantNo']=$config['MERCHANTNO'];
         
         $sign=$data['p1_MerchantNo'];
         
         $key=$config['KEY'];
         
         $hmac=MD5($sign.$key); //签名数据
         
         $data['hmac']=$hmac;
         
         $url="https://www.joinpay.com/trade/queryAccount.action";
         
         $result=cURLSSLHttp($url,$data);
//          var_dump($data);
//          var_dump($result);
         $nowtime= date("Y-m-d h:i:s", time());
         # 写入日志
         R("Payapi/Api/PaySetLog",array("./PayLog","AppPay_moneycheck_Return__",$nowtime.'请求参数'.implode(',',$data).'----回调返回信息参数----'.$result));
         
         $msg=json_decode($result);
         if($msg->rb_Code=='100')
         {
             return $result;
         }else 
         {
             return "";
         }
         
     }
     
     //分销结算列表详情接口
     public function settlementlist()
     {
         if (!$this->sign){
             echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;
         }
         
         $array=array(
             'uid'=>$this->uid,
         );
         $this->parem = array_merge($this->parem,$array);
         $msg = R('Func/Func/getKey',array($this->parem));//返回加密
         
         if ($this->sign !== $msg){
             echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;
         }
         R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
         $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
            
         
         $business_id=$_REQUEST['business_id'];
         
         $page = $_REQUEST['page'];  //页数
         $state = $_REQUEST['state'];  //状态
         
         
         //查询正在提现中状态的订单
         $whereorderjsz['js_status']=1;
         $jszorder=M('business_settlement')->where($whereorderjsz)->select();
         if($jszorder)
         {
             //处理正在结算中的订单
             for ($i = 0; $i < count($jszorder); $i++) {
                 $code=$this->ordercheck($jszorder[$i]['js_ordersn']);
                 $whereorder['p2_BatchNo']=$jszorder[$i]['js_ordersn'];
                 
                 if($code==='100')
                 {
                     $data['state']=1;
                     $data['rc_CodeMsg']="交易成功";
                     $data['rb_Code']=$code;
                     
                     $wherejsorder['business_settlement_id']=$jszorder[$i]['business_settlement_id'];
                     $dataorder['js_status']=2;  //更改订单状态为交易成功
                     $dataorder['j_succee_time']=time();  //更改订单状态为交易成功时间
                     $jszordersave=M('business_settlement')->where($whereorderjsz)->save($dataorder);
                     
                     $js_money=$jszorder[$i]['js_money'];
                     $user_id=$jszorder[$i]['user_id'];
                     sendToUser("{\"fromid\":\"1\",\"fromname\":\"钱兜兜\",\"user_id\":\"".$user_id."\",\"name\":\"\",\"token\":\"cebced47c9d6c22c9ed9c6df528caace83f04edb1a52780b49f53e975001bed4\",\"msg\":\"您提现的".$js_money."元已经提现成功，请查询您的银行卡余额\",\"operation\":\"text\",\"sound\":\"default\",\"content-available\":\"1\"}");
                     
                     
                 }else if($code==='102')
                 {
                     $data['state']=2;
                     $data['rc_CodeMsg']=$msg->rc_CodeMsg;
                     $data['rb_Code']=$code;
                 }else {
                     $data['state']=3;
                     $data['rc_CodeMsg']="请求失败";
                     $data['rb_Code']=$code;
                     
                     $wherejsorder['business_settlement_id']=$jszorder[$i]['business_settlement_id'];
                     $dataorder['js_status']=3;  //更改订单状态为交易失败
                     $dataorder['after_money']=$jszorder[$i]['before_money'];  //更改订单结算后的金额
                     $jszordersave=M('business_settlement')->where($whereorderjsz)->save($dataorder);
                     
                     $wherewallet['business_id']=$business_id;
                     $walletMoney=M('business')->where($wherewallet)->getField('business_wallet');
                     $datewallet['business_wallet']=(float)$walletMoney+(float)$jszorder[$i]['js_money'];
                     $walletMoneyres=M('business')->where($wherewallet)->save($datewallet); //将金额返回给用户
                     
                     $js_money=$jszorder[$i]['js_money'];
                     $user_id=$jszorder[$i]['user_id'];
                     sendToUser("{\"fromid\":\"1\",\"fromname\":\"钱兜兜\",\"user_id\":\"".$user_id."\",\"name\":\"\",\"token\":\"cebced47c9d6c22c9ed9c6df528caace83f04edb1a52780b49f53e975001bed4\",\"msg\":\"您提现的".$js_money."元提现失败，已转回您的余额\",\"operation\":\"text\",\"sound\":\"default\",\"content-available\":\"1\"}");
                     
                 }
                 
                 M('unipay_settlement')->where($whereorder)->save($data);
             }
            
         }
         
         if(!$page)
         {
             $page=1;
         }
         
         if($state)
         {
             $where['y_business_settlement.js_status']=array('eq',$state);
         }
         
         $pagenum=15;
         $startnum=($page-1)*$pagenum;
         $endnum=$page*$pagenum;
         
         $limit=''.$startnum.','.$endnum.'';
         $w['business_id']=$business_id;
         $w['user_id']=$this->uid;
         $w['type'] = 1;
         $count = M('business_settlement')->where($w)->order('t desc')->count();
         $datalist = M('business_settlement')->where($w)->order('t desc')->limit($limit)->select();
        
         $whereorder['business_id']=$business_id;
         $whereorder['jy_status']=1;
         $money=M('business_order')->where($whereorder)->sum('money');
         
         $start = date('Y-m-d 00:00:00');
         $end = date('Y-m-d H:i:s');
         $beginToday=strtotime($start);
         $endToday=strtotime($end);
         
         $whereorder['t']=array('BETWEEN',array($beginToday,$endToday));
         $nowMoney=M('business_order')->where($whereorder)->sum('money');
         if(!$nowMoney)
         {
             $nowMoney=0.00;
         }
//           echo M('business_order')->getLastSql();
         
         $whereorderjs['js_status']=2;
         $successSum=M('business_settlement')->where($whereorderjs)->sum('rz_money');
         $wherebusiness['business_id']=$business_id;
         $canMoney=M('business')->where($wherebusiness)->getField('business_wallet');
         
        
         
         $data = array(
             
             'canMoney' => $canMoney,//可结算金额
             
             'successSum' => $successSum,//成功结算金额
             
             'money' => $money, //总收益
             
             'nowMoney' => $nowMoney, //今日收益
             
             'info' => array('data'=>$datalist,'pagesum'=>$count,'pagesize'=>ceil($count/$pagenum)), //结算记录
            );
         // print_r($data);
         
         echo json_encode(array(
             
             'code'=>'2013',
             
             'msg'=>'提现记录',
             
             'data'=>$data,
             
         )); exit();
     }
     
     //分销结算
     
     public function retailClose()
     
     {
         
         if (!$this->sign){
             echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;
         }
         
         $array=array(
             'uid'=>$this->uid,
         );
         $this->parem = array_merge($this->parem,$array);
         $msg = R('Func/Func/getKey',array($this->parem));//返回加密
         
         if ($this->sign !== $msg){
             echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;
         }
         R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
         $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
           
         
         
         $user_id=$this->uid;
         
         $wherejsway['sid']=array('eq',14);
         $wherejsway['status']=array('eq',1);
         $userjsway=M('user_js_supply')->where($wherejsway)->find();
         $sx_money=(float)$userjsway['yy_rate'];
    
         
         $wherebusiness['user_id']=array('eq',$user_id);
         $business=M('business')->where($wherebusiness)->find();
         $business_id=$business['business_id'];
    
         $wherebusiness['business_id']=$business_id;
         $canMoney=M('business')->where($wherebusiness)->getField('business_wallet');
         // 获取默认的结算卡
         $wherebank['user_id']=array('eq',$user_id);
         $wherebank['is_normal']=array('eq',1);
         $mybank=M('mybank')->where($wherebank)->find();
         
      
         
         
         
       
         
         
         $mybank['icon'] = M("bank")->where(array('bank_id'=>$mybank['bank_id']))->getField('icon');
         
         $mybank['bank_name'] = M("bank")->where(array('bank_id'=>$mybank['bank_id']))->getField('name');
         
         
         
         // 可结算金额
         $canMoney=sprintf("%.2f",$canMoney);
         
         
         
         echo json_encode(array(
             
             'code'=>'2014',
             
             'msg'=>'商户结算',
             
             'data'=>$canMoney,
             
             'mybank'=> $mybank, // 我的银行卡信息
             
             'sx_money' => $sx_money
             
         )); exit();
         
     }
     
}

    
?>