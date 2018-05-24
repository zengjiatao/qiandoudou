<?php
namespace Payapi\Controller;
use Think\Controller;
//智能还款接口
class SmartcardController extends BaseController {
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
    
   //智能还款首页列表
    public function index(){
        
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
        
        
        $page = $_REQUEST['page'];  //页数
        $state = $_REQUEST['state'];  //计划状态
         
        
        
        
        if(!$page)
        {
            $page=1;
        }
        
        if($state)
        {
            $where['y_smart_card.state']=array('eq',$state);
        }
        
        $pagenum=20;
        $startnum=($page-1)*$pagenum;
        $endnum=$page*$pagenum;
        
        $limit=''.$startnum.','.$endnum.'';
        
        $where['y_smart_card.user_id']=array('eq',$this->uid);
        
        $where['_string'] = ' y_smart_card.state!=0 ';
        
        $pagecount=M('smart_card')->join('LEFT JOIN y_mybank  on  y_smart_card.mybank_id=y_mybank.mybank_id ')->join('LEFT JOIN y_bank  on  y_bank.bank_id=y_mybank.bank_id ')->field('y_smart_card.*,y_mybank.cart,y_bank.icon,y_bank.bg,y_bank.name')->where($where)->count();
        
        
        $pagecount=ceil($pagecount/$pagenum);
        
        
        $smart_cardlsit=M('smart_card')->join('LEFT JOIN y_mybank  on  y_smart_card.mybank_id=y_mybank.mybank_id ')->join('LEFT JOIN y_bank  on  y_bank.bank_id=y_mybank.bank_id ')->field('y_smart_card.*,y_mybank.cart,y_bank.icon,y_bank.bg,y_bank.name')->where($where)->limit($limit)->order('smart_card_id desc')->select();
        
//         var_dump($smart_cardlsit);
//         return;
        if ($smart_cardlsit){
            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>array('smart_cardlsit'=>$smart_cardlsit),'page'=>$page,'pagecount'=>$pagecount));die;
        }else{
            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;
        }
    }
   
    
    
    //信用卡指定还款计划页
    public  function  addplant()
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
        
        
        $xyInfo = $this->getMyBank($this->uid,2,2);  
//              dump($xyInfo);die;
        if ($xyInfo){
            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>array('xyInfo'=>$xyInfo)));die;
        }else{
            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;
        }
        
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
            $wbank['is_ztype'] = 2;
            // 通道ID
            if($tytd>0)
            {
                $pay_supply_id = M("user_pay_supply")->where(array('user_pay_supply_id'=>$tytd))->getField('pay_supply_id');
                $tdbank_id = M("pay_supply")->where(array('pay_supply_id'=>$pay_supply_id))->getField('bank_id');
                $wbank['bank_id'] = array('in',$tdbank_id);
                $d = M("mybank")->where($wbank)->order('mybank_id desc')->find();
            }else{
                $d = M("mybank")->where($wbank)->order('mybank_id desc')->find();
            }
            
            R("Payapi/Api/PaySetLog", array("./PayLog", "mybanktest_", '----测试银行列表记录----' . json_encode($tdbank_id).'----'.json_encode($tytd).'---'.json_encode($wbank)));
            
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
                $wbank['bank_id'] = array('in', $tdbank_id);
                $d = M("mybank")->where($wbank)->order('mybank_id desc')->select();
            }else{
                
                $d = M("mybank")->where($wbank)->order('mybank_id desc')->select();
                
                
            }
            foreach ($d as $k => $v)
            {
                $d[$k]['bankinfo'] = M("bank")->where(array('status'=>1,'bank_id'=>$v['bank_id']))->find();
            }
        }
        
        
        
        return $d;
        
    }
    
    
    //还款计划详情页面
    public function smartplant()
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
        
        
        $smart_card_id=$_REQUEST['smart_card_id'];
        
        $where['smart_card_id'] = $smart_card_id;
        $where['type'] = 1;
        $list =  M('repayment_plan')->where($where)->order('paydate')->select();
        
        for ($i = 0; $i < count($list); $i++) {
            
            $whereplant['link_payid']=$list[$i]['repayment_plan_id'];
            $cplant=M('repayment_plan')->where($whereplant)->order('paydate asc')->select();
            $list[$i]['payplants']=$cplant;
        }
        $wheres['smart_card_id'] = $smart_card_id;
        $smartinfo=M('smart_card')->join('LEFT JOIN y_mybank  on  y_smart_card.mybank_id=y_mybank.mybank_id ')->join('LEFT JOIN y_bank  on  y_bank.bank_id=y_mybank.bank_id ')->field('y_smart_card.*,y_mybank.bill_day,y_mybank.refund_day,y_mybank.amount,y_mybank.cart,y_bank.icon,y_bank.bg,y_bank.name')->where($wheres)->find();
        
        if ($smartinfo){
            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>array('list'=>$list,'smartinfo'=>$smartinfo)));die;
        }else{
            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;
        }
        
//         $this->assign('smartinfo',$smartinfo);
//         $this->assign('list',$list);
//         $this->display();
        
    }
    
    //取消还款计划
    public function cancleplant()
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
        $ret_code=200;
        $ret_msg="计划取消成功";
        
        $smart_card_id=$_REQUEST['smart_card_id'];
        
        $where['smart_card_id'] =$smart_card_id;
        $where['state']=array('eq',3);
        $smart_card=M('smart_card')->where($where)->find();
        if($smart_card)
        {
            $wheres['smart_card_id'] = $smart_card_id;
            $wheres['state'] = 1;
            $list =  M('repayment_plan')->where($wheres)->order('paydate')->select();
            if($list)
            {
                
                $wheres['smart_card_id'] = $smart_card_id;
                $smartinfos=M('smart_card')->where($wheres)->find();
                if($smartinfos['enddate']<time())
                {
                    $respCode=$this->delCard($smart_card_id,$user_id);
                    if($respCode=='0000')
                    {
                        $wherecard['smart_card_id']=$smart_card_id;
                        $smart_card=M('smart_card')->where($wherecard)->find();
                        
                        $savedate['state'] = 5;
                        
                        $saveres=M('smart_card')->where($wherecard)->save($savedate);
                        
                        $ret_code=200;
                        $ret_msg="计划取消成功";
                    }else
                    {
                        $wherecard['smart_card_id']=$smart_card_id;
                        $smart_card=M('smart_card')->where($wherecard)->find();
                        
                        $ret_code=201;
                        $ret_msg=$smart_card['message'];
                        
                    }
                    echo json_encode(array('code'=>$ret_code,$ret_msg));die;
                }else {
                    echo json_encode(array('code'=>201,'msg'=>'计划不能删除'));die;
                }
                
                $ret_code=201;
                $ret_msg="计划已经在开始执行中，不能取消";
            }else{
                $respCode=$this->delCard($smart_card_id,$user_id);
                if($respCode=='0000')
                {
                    $wherecard['smart_card_id']=$smart_card_id;
                    $smart_card=M('smart_card')->where($wherecard)->find();
                     
                    $ret_code=200;
                    $ret_msg="计划取消成功";
                }else 
                {
                    $wherecard['smart_card_id']=$smart_card_id;
                    $smart_card=M('smart_card')->where($wherecard)->find();
                    
                    $ret_code=201;
                    $ret_msg=$smart_card['message'];
                    
                }
                
            }  
            
        }else {
            $ret_code=201;
            $ret_msg="计划不在执行中";
        }
        echo "{\"code\":\"$ret_code\",\"msg\":\"$ret_msg\"}";
    }
    
    
    //添加还款计划信息界面
    public function addnewplant()
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
        
        
        
        $mybank_id = $_REQUEST['mybank_id'];  //银行卡id
        if(!$mybank_id)
        {
            echo json_encode(array('code'=>6503,'msg'=>'银行卡mybank_id为空','data'=>array()));die;
        }
        
        $where['mybank_id']=array('eq',$mybank_id);
        
        $bankinfo=M('mybank')->join('LEFT JOIN y_bank  on  y_bank.bank_id=y_mybank.bank_id ')->field('y_mybank.*,y_bank.icon,y_bank.bg,y_bank.name')->where($where)->find();
        
        $where['state']=3;
        $smart_card=M('smart_card')->where($where)->find();
        if($smart_card)
        {
//             goback("该卡已有在执行计划，请更换另一张卡");
            echo json_encode(array('code'=>6503,'msg'=>'该卡已有在执行计划，请更换另一张卡','data'=>array()));die;
        }
      
//          dump($bankinfo);

        /* $wherepay['user_pay_supply_id']=array('eq',8);
        $wherepay['status']=array('eq',1);
        
        $user_pay_supply=M('user_pay_supply')->where($wherepay)->find(); */
        
        $wherepaysub['y_user_pay_supply.pay_supply_id']=12;  //智能还款通道
        $wherepaysub['y_user_pay_supply.status']=1;
        $user_pay_supply=M('user_pay_supply')->JOIN('LEFT JOIN y_user_js_supply on y_user_js_supply.user_js_supply_id=y_user_pay_supply.user_js_supply_id')->field('y_user_pay_supply.*,y_user_pay_supply.single_minprice as msingle_minprice,y_user_js_supply.yy_rate as single_minprice')->where($wherepaysub)->find();
        
        if(!$user_pay_supply)
        {
       //   goback("该卡已有在执行计划，请更换另一张卡");
            echo json_encode(array('code'=>6503,'msg'=>'智能还款正在维护中，请稍后再试','data'=>array()));die;
        }
        
        //商户入驻
        $data = R("Payapi/Gspay/BusinessRegConfig",array($this->uid,$user_pay_supply['user_pay_supply_id']));
        
        //           var_dump($data);
        if($data['code']!='0000')
        {
//             goback($data['msg']);
            echo json_encode(array('code'=>6503,'msg'=>$data['msg'],'data'=>array()));die;
        }
        
        $this->assign('user_pay_supply',$user_pay_supply);
        
        $this->assign('bankinfo',$bankinfo);
        
        
        if ($bankinfo){
            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>array('bankinfo'=>$bankinfo,'user_pay_supply'=>$user_pay_supply)));die;
        }else{
            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;
        }
      
    }
    
    //添加还款计划
    public function  addtoplant()
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
        
//         repayment=4&days=1&money=100&bondmoney=25&service_charge=8.6&startmoney=33.6&startdate=2017-12-19&enddate=2017-12-20&mybank_id=319&out_trade_no=ZNHK20171213190024
        
        $data['repayment']= $_REQUEST['repayment'];
        $data['days']= $_REQUEST['days'];
        $data['money']= $_REQUEST['money'];
        $data['bondmoney']= $_REQUEST['bondmoney'];
        $data['service_charge']= $_REQUEST['service_charge'];
        $data['startmoney']= $_REQUEST['startmoney'];
        $data['startdate']= $_REQUEST['startdate'];
        $data['enddate']= $_REQUEST['enddate'];
        $data['mybank_id']= $_REQUEST['mybank_id'];
        
        $data['user_id']=$this->uid;
        
        $data['addtime']=time();
        
        $data['money_type_id']=16;
        
        $data['user_pay_supply_id']=8;
        
        $data['state']=2;
        
        $data['out_trade_no']="ZNHK". date("YmdHis",time());
        
//         echo strtotime($data['startdate']); 
//         echo date("Y-m-d H:i:s", strtotime($data['startdate']));
        
//         return;
        
        $wherebank['user_id']=array('eq',$data['user_id']);
        $wherebank['is_normal']=array('eq',1);
        
        $mybany=M('mybank')->where($wherebank)->find();
        if(!$mybany)
        {
//            goback("请先绑定默认结算卡！"); 
           echo json_encode(array('code'=>6503,'msg'=>'请先绑定默认结算卡！','data'=>array()));die;
//            return;
        }
        
        
        $data['startdate']=strtotime($data['startdate']);
        $data['enddate']=strtotime($data['enddate']);
        
        $where['user_id']=array('eq',$this->uid);
        $where['out_trade_no']=array('eq',$data['out_trade_no']);
          
        $ishas=M('smart_card')->where($where)->find();
        if($ishas)
        {
            
            if($ishas['state']==3)
            {
                echo json_encode(array('code'=>6503,'msg'=>'该计划已经开始执行了！','data'=>array()));die;
//                 goback("该计划已经支付！");
//                 return;
            }
//             echo "已经存在该记录";
            
            $res=M('smart_card')->where($where)->save($data);
            
            $whereplant['smart_card_id']= array('eq',$ishas['smart_card_id']);
            
            $smart_card_id=$ishas['smart_card_id'];
            
            if(M('repayment_plan')->where($whereplant)->find())
            { 
//                 echo "旧的计划";
                $isdele=M('repayment_plan')->where($whereplant)->delete();
                if($isdele){
//                     echo "删除旧的计划";
                    //重新生成计划
                    $this->createplant($smart_card_id, $data['money'],$data['startdate'],$data['enddate'],$data['repayment'],$data['mybank_id'],$data['service_charge']);
                }
            }else 
            {
//                 echo "新的计划";
                $this->createplant($smart_card_id, $data['money'],$data['startdate'],$data['enddate'],$data['repayment'],$data['mybank_id'],$data['service_charge']);
            }
            
        }else {
//             echo "插入该记录";
            $res=M('smart_card')->add($data);
            $ishas=M('smart_card')->where($where)->find();
            $smart_card_id=  $ishas['smart_card_id'];
            //生成还款计划
            
            
            $this->createplant($smart_card_id, $data['money'],$data['startdate'],$data['enddate'],$data['repayment'],$data['mybank_id'], $data['service_charge']);
            
        }
        $ishas=M('smart_card')->where($where)->find();
        
        $data['smart_card_id']= $ishas['smart_card_id'];
        
//         var_dump($data);
//         return;
        
//         $this->assign('mybany',$mybany);
//         $this->assign('data',$data);
        
        if ($data){
            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>array('mobile'=>$mybany['mobile'],'smart_card'=>$data)));die;
        }else{
            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;
        }
    }
    
    //发送短信
    public function sendcode()
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
        
        
        $ret_code=200;
        $ret_msg="发送成功";
        
//         $phone =$_GET['phone'];
        
        $phone=$_REQUEST['mobile'];
        $smart_card_id=$_REQUEST['smart_card_id'];
        
        $user_id=$this->uid;
        
        $info = M('mobleyzm')->where(array('phone'=>$phone,'type'=>9))->find();
        
        if ($phone == '') {
            
            echo json_encode(array('code'=>2,'msg'=>'手机号码不能为空啊,同志'));die;
            
        }
        
        $coco =create_yzm();
        
        //        $msg = iconv("GB2312","UTF-8", "您的验证码为:").$coco;
        
        //        R('Func/Func/sendMessage',array($phone,$msg));
        
        $msg = "验证码：".$coco."(为了您的账户安全，请勿告知他人，请在页面尽快完成验证)客服4008-272-278";
        
        $infooo = R("Func/Func/send_mobile",array($phone,$msg));
        
        //验证是否存在验证码,存在则覆盖
        
        if ($info) {
            
            $data['mobleyzm_id'] = $info['mobleyzm_id'];
            
            $data['phone']=$phone;
            
            $data['code']=$coco;
            
            $data['c_t']=time();
            
            $data['type']=9;
            
            $res = M('mobleyzm')->save($data);
            
            if ($res) {
                
                //                 echo json_encode(array('code'=>0,'info'=>'验证码发送成功!'));die;
                $ret_msg='验证码发送成功!';
                $ret_code=200;
                echo json_encode(array('code'=>$ret_code,'msg'=>$ret_msg));die;
                
            }else{
                
//                 echo json_encode(array('code'=>1,'info'=>'系统繁忙!'));die;
                $ret_msg='系统繁忙!';
                $ret_code=201;
                echo json_encode(array('code'=>$ret_code,'msg'=>$ret_msg));die;
                //                 echo "{\"code\":\"$ret_code\",\"msg\":\"$ret_msg\"}";die;
                
            }
            
        }else{
            
            $data['phone']=$phone;
            
            $data['code']=$coco;
            
            $data['c_t']=time();
            
            $data['type']=9;
            
            $res = M('mobleyzm')->add($data);
            
            if ($res) {
                
//                 echo json_encode(array('code'=>0,'info'=>'验证码发送成功!'));die;
                $ret_msg='验证码发送成功!';
                $ret_code=200;
                echo json_encode(array('code'=>$ret_code,'msg'=>$ret_msg));die;
                
            }else{
                $ret_msg='系统繁忙!';
                $ret_code=201;
                echo json_encode(array('code'=>$ret_code,'msg'=>$ret_msg));die;
//                 echo "{\"code\":\"$ret_code\",\"msg\":\"$ret_msg\"}";die;
                
            }
            
        }
    }
    
    //确认计划发送验证码
    public function  sendcodeold()
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
        
        
        $ret_code=200;
        $ret_msg="发送成功";
        
        $settlePhone=$_REQUEST['mobile'];
        $smart_card_id=$_REQUEST['smart_card_id'];
        
        $user_id=$this->uid;
        
        $where['settlePhone']=array('eq',$settlePhone);
        $where['user_id']=array('eq',$user_id);
        $where['state']=array('eq',1);
        $hasmer=M('smartcard_user')->where($where)->find();
        if($hasmer){
         //已经入驻过   
            $respCode=$this->smartcard_open($user_id);
            if($respCode=='0000')
            {
                $respCode=$this->sendtosys($settlePhone,$smart_card_id,$user_id);
                if($respCode=='0000')
                {
                    $ret_code=200;
                    $ret_msg="发送成功";
                    
                }else {
                    $wherecard['smart_card_id']=$smart_card_id;
                    $smart_card=M('smart_card')->join('LEFT JOIN y_mybank  on  y_smart_card.mybank_id=y_mybank.mybank_id ')->field('y_smart_card.*,y_mybank.cw_two,y_mybank.useful,y_mybank.amount,y_mybank.cart')->where($wherecard)->find();
                    
                    $ret_code=201;
                    $ret_msg=$smart_card['message'];
                    
                }
            }else {
                $whereuser['user_id']=array('eq',$user_id);
                $has=M('smartcard_open')->where($whereuser)->find();
                $ret_code=201;
                $ret_msg=$has['message'];
            }
            
        }else 
        {
            //还没驻过，先入驻商家财能进行信用卡还款业务   
            $respCode= $this->addtosys($settlePhone,$user_id);
            if($respCode=='0000')
            {
                $respCode=$this->smartcard_open($user_id);
                if($respCode=='0000')
                {
                    $this->sendtosys($settlePhone,$smart_card_id,$user_id);
                }else 
                {
                    $whereuser['user_id']=array('eq',$user_id);
                    $has=M('smartcard_open')->where($whereuser)->find();
                    $ret_code=201;
                    $ret_msg=$has['message'];
                    
                }
            }else {
                $wheres['user_id']=array('eq',$user_id);
                $hasmer=M('smartcard_user')->where($wheres)->find();
                $ret_code=201;
                $ret_msg=$hasmer['message'];
            }
        }
        
        
        
        echo "{\"code\":\"$ret_code\",\"msg\":\"$ret_msg\"}";
        
    }
    
    //请求支付执行新的还款计划
    public function uptonewplant()
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
        
        $ret_code=200;
        $ret_msg='计划启动成功';
        
        
        $paypassword=$_REQUEST['paypassword'];
        $code=$_REQUEST['code'];
        $smart_card_id=$_REQUEST['smart_card_id'];
        $user_id=$this->uid;
        
        $wherepassword['user_id']=array('eq',$user_id);
        $wherepassword['type']=array('eq',1);
        
        $pwd=M('password')->where($wherepassword)->find();
        
        
//         if($pwd['pwd']==MD5(MD5($paypassword)))
        if($pwd['pwd']==$paypassword)
        {
            
            $whereuser['user_id']=array('eq',$user_id);
            $userinfo=M('user')->where($whereuser)->find();
            
            $wallet_money=$userinfo['wallet_money'];
            
            $wheresmart_card['smart_card_id']=array('eq',$smart_card_id);
            $smart_card=M('smart_card')->where($wheresmart_card)->find();
            $bondmoney=$smart_card['bondmoney'];
            $service_charge=$smart_card['service_charge'];
            
            $wherebank['user_id']=array('eq',$user_id);
            $wherebank['is_normal']=array('eq',1);
            $mybany=M('mybank')->where($wherebank)->find();
            if(!$mybany)
            {
                //            goback("请先绑定默认结算卡！");
                echo json_encode(array('code'=>6503,'msg'=>'请先绑定默认结算卡！','data'=>array()));die;
                //            return;
            }
            
            $yzmInfo = M('mobleyzm')->where(array('phone'=>$mybany['mobile'],'type'=>9))->find();
            
            if($yzmInfo['code'] ==$code)
            {
                //                             echo "验证码已正确";
                $ret_code=200;
                $ret_msg="计划启动成功";
                
                $type="16";
                $pay_type="6";
                $ratemoney= $bondmoney;
                
                $wheresmart_card['smart_card_id']=array('eq',$smart_card_id);
                $data['state']=3;
                $smart_card=M('smart_card')->where($wheresmart_card)->save($data);
                
            }else {
                //                             echo "验证码不正确";
//                 $wherecard['smart_card_id']=$smart_card_id;
//                 $smart_card=M('smart_card')->join('LEFT JOIN y_mybank  on  y_smart_card.mybank_id=y_mybank.mybank_id ')->field('y_smart_card.*,y_mybank.cw_two,y_mybank.useful,y_mybank.amount,y_mybank.cart')->where($wherecard)->find();
                $ret_code=201;
                $ret_msg="验证码不正确";
            }
            
          
        }else
        {
            $ret_code=201;
            $ret_msg='支付密码不对';
            
        }
        
        echo "{\"code\":\"$ret_code\",\"msg\":\"$ret_msg\"}";
        
    }
    
    //请求支付执行新的还款计划
    public function uptonewplantold()
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
        
        $ret_code=200;
        $ret_msg='计划启动成功';
        
        
        $paypassword=$_REQUEST['paypassword'];
        $code=$_REQUEST['code'];
        $smart_card_id=$_REQUEST['smart_card_id'];
        $user_id=$this->uid;
        
        $wherepassword['user_id']=array('eq',$user_id);
        $wherepassword['type']=array('eq',1);
        
        $pwd=M('password')->where($wherepassword)->find();
        
        
        //         if($pwd['pwd']==MD5(MD5($paypassword)))
        if($pwd['pwd']==$paypassword)
        {
            
            $whereuser['user_id']=array('eq',$user_id);
            $userinfo=M('user')->where($whereuser)->find();
            
            $wallet_money=$userinfo['wallet_money'];
            
            $wheresmart_card['smart_card_id']=array('eq',$smart_card_id);
            $smart_card=M('smart_card')->where($wheresmart_card)->find();
            $bondmoney=$smart_card['bondmoney'];
            $service_charge=$smart_card['service_charge'];
            
            $respCode=$this->comfiplant($smart_card_id,$code,$user_id);
            if($respCode=="0000")
            {
                //                             echo "验证码已正确";
                $ret_code=200;
                $ret_msg="计划启动成功";
                
                $type="16";
                $pay_type="6";
                $ratemoney= $bondmoney;
                
            }else {
                //                             echo "验证码不正确";
                $wherecard['smart_card_id']=$smart_card_id;
                $smart_card=M('smart_card')->join('LEFT JOIN y_mybank  on  y_smart_card.mybank_id=y_mybank.mybank_id ')->field('y_smart_card.*,y_mybank.cw_two,y_mybank.useful,y_mybank.amount,y_mybank.cart')->where($wherecard)->find();
                $ret_code=201;
                $ret_msg=$smart_card['message'];
            }
            
            
        }else
        {
            $ret_code=201;
            $ret_msg='支付密码不对';
            
        }
        
        echo "{\"code\":\"$ret_code\",\"msg\":\"$ret_msg\"}";
        
    }
    
    //卡签约发送短信验证码
    public function  sendtosys($settlePhone,$smart_card_id,$user_id)
    {
        $where['settlePhone']=array('eq',$settlePhone);
        $where['user_id']=array('eq',$user_id);
        $where['state']=array('eq',1);
        $smartcard_user=M('smartcard_user')->where($where)->find();
        
        $wherecard['smart_card_id']=$smart_card_id;
        
        $smart_card=M('smart_card')->join('LEFT JOIN y_mybank  on  y_smart_card.mybank_id=y_mybank.mybank_id ')->field('y_smart_card.*,y_mybank.cw_two,y_mybank.useful,y_mybank.amount,y_mybank.cart')->where($wherecard)->find();
        
        
        //卡签约发送短信验证码
//         $url="http://ooo.gytx.cc/ygww/sys/api/outer/geteway.do";
        $url="http://139.224.27.56/ygww/sys/api/outer/geteway.do";
        $data['agentNo']=$smartcard_user['agentNo'];
        $data['merNo']=$smartcard_user['merNo'];
        $data['out_trade_no']=$smart_card['out_trade_no'];
        $data['service']='nctjfd03';
        $data['lservice']='sendSMS';
        $data['accNo']=$smart_card['cart'];
        $data['cvn2']=$smart_card['cw_two'];
        
        $start= mb_substr($smart_card['useful'],0,2,'utf-8');
        $end= mb_substr($smart_card['useful'],2,4,'utf-8');
        
        $data['useTime']=$end.$start;
        
        //md5加密后字母大写
        $sign= strtoupper(md5("accNo=".$data['accNo']."&agentNo=".$data['agentNo']."&cvn2=".$data['cvn2']."&lservice=".$data['lservice']."&merNo=".$data['merNo']."&out_trade_no=".$data['out_trade_no']."&service=".$data['service']."&useTime=".$data['useTime']."&key=".$smartcard_user['merKey']));
        
        
        $data['sign']=$sign;
        
        
        $result=cURLSSLHttp($url,$data);
        //             var_dump($result);
        # 写入日志
        R("Payapi/Api/PaySetLog",array("./PayLog","Smartcard_sendtosysReturn__",'请求参数'.implode(',',$data).'----回调返回信息参数----'.$result));
        
        $msg=json_decode($result);
        
        if($msg->respCode=='0000')
        {
            $data['respCode']=$msg->respCode;
            $data['message']=$msg->message;
            $data['sign']=$msg->sign;
            if($msg->out_trade_no)
            {
                $data['out_trade_no']=$msg->out_trade_no;
            }
           
            
            $data['state']=2;
        }else
        {
            //   echo $msg->message;
            $data['respCode']=$msg->respCode;
            $data['message']=$msg->message;
        }
        
        $whereuser['smart_card_id']=array('eq',$smart_card_id);
//      $whereuser['out_trade_no']=array('eq',$data['out_trade_no']);
        $has=M('smart_card')->where($whereuser)->find();
        if(!$has)
        {
            M('smart_card')->add($data);
        }else {
            M('smart_card')->where($whereuser)->save($data);
        }
        
        
        return $msg->respCode;
        
        
    }
    
    //开通快捷
    public function smartcard_open($user_id)
    {
        $where['user_id']=array('eq',$user_id);
        $where['state']=array('eq',1);
        
        $smartcard_user=M('smartcard_open')->where($where)->find();
        if($smartcard_user['respCode']=='0000')
        {
            return '0000';
        }else {
            //开通快捷接口
//             $url="http://ooo.gytx.cc/ygww/sys/api/outer/geteway.do";
            $url="http://139.224.27.56/ygww/sys/api/outer/geteway.do";
            $smartcard_user=M('smartcard_user')->where($where)->find();
            $data['agentNo']=$smartcard_user['agentNo'];
            $data['merNo']=$smartcard_user['merNo'];
            $data['out_trade_no']="ZNHK". date("YmdHis",time());
            $data['service']='nctjfd03';
            $data['lservice']='checkName';
            
            $sign= strtoupper(md5("agentNo=".$data['agentNo']."&lservice=".$data['lservice']."&merNo=".$data['merNo']."&out_trade_no=".$data['out_trade_no']."&service=".$data['service']."&key=".$smartcard_user['merKey']));
            
            
            $data['sign']=$sign;
            
            
            
            $result=cURLSSLHttp($url,$data);
//             var_dump($result);
            # 写入日志
            R("Payapi/Api/PaySetLog",array("./PayLog","Smartcard_smartcard_openReturn__",'--------请求参数--------'.implode(',',$data).'----回调返回信息参数----'.$result));
            
            $msg=json_decode($result);
            
            if($msg->respCode=='0000')
            {
                $data['respCode']=$msg->respCode;
                $data['message']=$msg->message;
                $data['sign']=$msg->sign;
               
                $data['state']=1;
            }else
            {
                //   echo $msg->message;
                $data['respCode']=$msg->respCode;
                $data['message']=$msg->message;
            }
            
            $data['smartcard_user_id']=$smartcard_user['smartcard_user_id'];
            
            $data['user_id']=$user_id;
            $data['addtime']=time();
            
            
            $whereuser['user_id']=array('eq',$user_id);
            $has=M('smartcard_open')->where($whereuser)->find();
            if(!$has)
            {
                M('smartcard_open')->add($data);
            }else {
                M('smartcard_open')->where($whereuser)->save($data);
            }
            
            
            return $msg->respCode;
            
            
        }
        
        
       
        
        
        
        
    }
    
    
    //商家入驻智能还款系统
    public  function addtosys($settlePhone,$user_id)
    {
        $config = C('AGENTNOCONFIG');
        
        $wherebank['user_id']=array('eq',$user_id);
        $wherebank['is_normal']=array('eq',1);
        $mybany=M('mybank')->join('LEFT JOIN y_bank  on  y_bank.bank_id=y_mybank.bank_id ')->field('y_mybank.*,y_bank.icon,y_bank.bg,y_bank.name,y_bank.hlb_bank_code')->where($wherebank)->find();
        
        $wherebank_children['bank_children_id']=array('eq',$mybany['bank_children_id']);
        
        $bank_children=M('bank_children')->where($wherebank_children)->find();
        
        $whereProvince['provid']=array('eq',$mybany['province_id']);
        
        $settleSubProvince=M('province')->where($whereProvince)->find();
        
        $whereCity['cityid']=array('eq',$mybany['city_id']);
        
        $settleSubCity=M('city')->where($whereCity)->find();
        
        if(!$mybany)
        {
//          goback("请先绑定默认结算卡！");
            return '1111';
        }
        
        //商户入驻接口
//         $url="http://ooo.gytx.cc/ygww/sys/api/outer/addMer.do";
        $url="http://139.224.27.56/ygww/sys/api/outer/addMer.do";
        
        $data['agentNo']=$config['AGENTNO']; //机构号
        
        $data['merName']=$mybany['nickname']."个体商户"; //商户名称
        
        $data['merAddr']=$mybany['nickname']; //商户地址
        
        $data['settleName']=$mybany['nickname']; //结算账户开户名
        
        $data['settleIdCard']=$mybany['idcard']; //身份证号
        
        $data['settlePhone']=$settlePhone; //结算账户绑定手机号
        
        $data['settleBank']=$mybany['name']; //结算卡开户行
        
        
        $data['settleBankNo']=$mybany['hlb_bank_code']; //开户行简码
        
        $data['settleAccount']=$mybany['cart']; //结算卡号
        
        $data['settleBankSub']=$bank_children['name']; //结算卡开户支行
        
        $data['settleBankBranch']=$bank_children['line']; //联行号
        
        $data['settleSubProvince']=$settleSubProvince['province'];    //$mybany['lianhang']; //开户省份
        $data['settleSubCity']=$settleSubCity['city'];    //$mybany['lianhang']; //结算卡开户市
        
//         var_dump($data);
        
        $result=cURLSSLHttp($url,$data);
       
        # 写入日志
        R("Payapi/Api/PaySetLog",array("./PayLog","Smartcard_addtosysReturn__",'-------请求参数-------'.implode(',',$data).'----回调返回信息参数----'.$result));
//         var_dump($result);
        
        $msg=json_decode($result); 
        
        if($msg->respCode=='0000')
        {
            $data['respCode']=$msg->respCode;
            $data['message']=$msg->message;
            $data['merKey']=$msg->merKey;
            $data['merNo']=$msg->merNo;
            $data['state']=1;
        }else 
        {
         //   echo $msg->message;
            $data['respCode']=$msg->respCode;
            $data['message']=$msg->message;
        }
        
        $data['user_id']=$user_id;
        $data['addtime']=time();
       
        
        $whereuser['user_id']=array('eq',$user_id);
        $has=M('smartcard_user')->where($whereuser)->find();
        if(!$has)
        {
            M('smartcard_user')->add($data);
        }else {
            M('smartcard_user')->where($whereuser)->save($data);
        }
       
        
        return $msg->respCode;
   
        
        
    }
    
    //确认签约还款计划保证金
    public  function comfiplant($smart_card_id,$code,$user_id)
    {
        $config = C('AGENTNOCONFIG');
        //商户入驻接口
//         $url="http://ooo.gytx.cc/ygww/sys/api/outer/geteway.do";
        $url="http://139.224.27.56/ygww/sys/api/outer/geteway.do";
        
        $wheres['user_id']=array('eq',$user_id);
        $wheres['state']=array('eq',1);
        $smartcard_user=M('smartcard_user')->where($wheres)->find();
        
        $where['smart_card_id']=array('eq',$smart_card_id);
        
        $smart_card=M('smart_card')->where($where)->find();
        
        $data['agentNo']=$config['AGENTNO']; //机构号
        
        $data['out_trade_no']=$smart_card['out_trade_no']; //交易订单号
        
        
        $data['merNo']=$smartcard_user['merNo']; //商户号
        
        
        $data['service']="nctjfd03"; //通道
        
        $data['lservice']="openCard"; //接口
        
        $data['smsCode']=$code; //验证码
        
        
        $sign= "agentNo=".$data['agentNo']."&lservice=".$data['lservice']."&merNo=".$data['merNo']."&out_trade_no=".$data['out_trade_no']."&service=".$data['service']."&smsCode=".$data['smsCode']."&key=".$smartcard_user['merKey'];
    
        //md5加密后字母大写
        $sign= strtoupper(md5($sign));
        
        
        $data['sign']=$sign;
        
//         dump($data);
        
        
        $result=cURLSSLHttp($url,$data);
        //             var_dump($result);
        # 写入日志
        R("Payapi/Api/PaySetLog",array("./PayLog","Smartcard_comfiplantReturn__",'-----请求参数-----'.implode(',',$data).'----回调返回信息参数----'.$result));
        
        $msg=json_decode($result);
        
        if($msg->respCode=='0000')
        {
            $data['respCode']=$msg->respCode;
            $data['message']=$msg->message;
            $data['sign']=$msg->sign;
            if($msg->out_trade_no)
            {
                $data['out_trade_no']=$msg->out_trade_no;
            }
            if($msg->agreementNo)
            {
                $data['agreementNo']=$msg->agreementNo;
            }
            
            $data['state']=3;
            
           
        }else
        {
            //   echo $msg->message;
            $data['respCode']=$msg->respCode;
            $data['message']=$msg->message;
        }
        
        $whereuser['smart_card_id']=array('eq',$smart_card_id);
        //      $whereuser['out_trade_no']=array('eq',$data['out_trade_no']);
        $has=M('smart_card')->where($whereuser)->find();
        if(!$has)
        {
            M('smart_card')->add($data);
        }else {
            M('smart_card')->where($whereuser)->save($data);
        }
        
        
        return $msg->respCode;
        
        
        
    }
    
    //更改计划状态
    public function delCard($smart_card_id,$state=5,$user_id)
    {
        
        $data['state']=$state;
        
        $whereuser['smart_card_id']=array('eq',$smart_card_id);
        //      $whereuser['out_trade_no']=array('eq',$data['out_trade_no']);
        $has=M('smart_card')->where($whereuser)->find();
        if(!$has)
        {
            M('smart_card')->add($data);
        }else {
            M('smart_card')->where($whereuser)->save($data);
        }
        
        return '0000';
        
    }
    //卡解约
    public function delCardold($smart_card_id,$user_id)
    {
        
        $config = C('AGENTNOCONFIG');
        //商户入驻接口
//         $url="http://ooo.gytx.cc/ygww/sys/api/outer/geteway.do";
        $url="http://139.224.27.56/ygww/sys/api/outer/geteway.do";
        
        $wheres['user_id']=array('eq',$user_id);
        $wheres['state']=array('eq',1);
        $smartcard_user=M('smartcard_user')->where($wheres)->find();
        
        $where['smart_card_id']=array('eq',$smart_card_id);
        
        $smart_card=M('smart_card')->where($where)->find();
        
        $data['agentNo']=$config['AGENTNO']; //机构号
        
        $data['agreementNo']=$smart_card['agreementNo']; //签约协议号
        
        $data['out_trade_no']=$smart_card['out_trade_no']; //交易订单号
        
        
        $data['merNo']=$smartcard_user['merNo']; //商户号
        
        
        $data['service']="nctjfd03"; //通道
        
        $data['lservice']="delCard"; //接口
        
      
        
        $sign= "agentNo=".$data['agentNo']."&agreementNo=".$data['agreementNo']."&lservice=".$data['lservice']."&merNo=".$data['merNo']."&out_trade_no=".$data['out_trade_no']."&service=".$data['service']."&key=".$smartcard_user['merKey'];
        
        //md5加密后字母大写
        $sign= strtoupper(md5($sign));
        
        
        $data['sign']=$sign;
        
        //         dump($data);
        
        
        $result=cURLSSLHttp($url,$data);
        //             var_dump($result);
        # 写入日志
        R("Payapi/Api/PaySetLog",array("./PayLog","Smartcard_delCard_Return__",'------请求参数-----'.implode(',',$data).'----回调返回信息参数----'.$result));
        
        $msg=json_decode($result);
        
        if($msg->respCode=='0000')
        {
            $data['respCode']=$msg->respCode;
            $data['message']=$msg->message;
            $data['sign']=$msg->sign;
            if($msg->out_trade_no)
            {
                $data['out_trade_no']=$msg->out_trade_no;
            }
            if($msg->agreementNo)
            {
                $data['agreementNo']=$msg->agreementNo;
            }
            
            $data['state']=5;
            
            
        }else
        {
            //   echo $msg->message;
            $data['respCode']=$msg->respCode;
            $data['message']=$msg->message;
        }
        
        $whereuser['smart_card_id']=array('eq',$smart_card_id);
        //      $whereuser['out_trade_no']=array('eq',$data['out_trade_no']);
        $has=M('smart_card')->where($whereuser)->find();
        if(!$has)
        {
            M('smart_card')->add($data);
        }else {
            M('smart_card')->where($whereuser)->save($data);
        }
        
        
        return $msg->respCode;
        
        
    }
    
    
    
    
    //向第三方系统发起支付
    public function paytosys($smart_card_id,$repayment_plan_id,$user_id)
    {
        $config = C('AGENTNOCONFIG');
        //商户入驻接口
//         $url="http://ooo.gytx.cc/ygww/sys/api/outer/geteway.do";
        $url="http://139.224.27.56/ygww/sys/api/outer/geteway.do";
        
        $wheres['user_id']=array('eq',$user_id);
        $wheres['state']=array('eq',1);
        $smartcard_user=M('smartcard_user')->where($wheres)->find();
        
        $where['repayment_plan_id']=array('eq',$repayment_plan_id);
        
        $repayment_plan=M('repayment_plan')->where($where)->find();
        
        $data['agentNo']=$config['AGENTNO']; //机构号
        
        $data['merNo']=$smartcard_user['merNo']; //商户号
        
        $data['accNo']=$repayment_plan['accNo']; //信用卡号
        
        $data['cvn2']=$repayment_plan['cvn2']; //后三位
        
        
        $start= mb_substr($repayment_plan['useTime'],0,2,'utf-8');
        $end= mb_substr($repayment_plan['useTime'],2,4,'utf-8');
        
        $data['useTime']=$end.$start; //有效期
        
        $data['service']=$repayment_plan['service']; //通道
        
        $data['lservice']=$repayment_plan['lservice']; //接口
        
        $data['out_trade_no']=$repayment_plan['out_trade_no']; //交易订单号
        
        $data['amount']=$repayment_plan['amount']; // 
        
        $data['payRate']=$repayment_plan['payRate']; // 
        
        $data['settleFee']=$repayment_plan['settleFee']; // 
        
//         $data['settleAccount']=$repayment_plan['settleAccount']; // 
        
//         $data['settleBank']=$repayment_plan['settleBank']; // 
        
//         $data['settleType']=$repayment_plan['settleType']; // 
        
        
        
//         $sign= "accNo=".$data['accNo']."&agentNo=".$data['agentNo']."&amount=".$data['amount']."&cvn2=".$data['cvn2']."&lservice=".$data['lservice']."&merNo=".$data['merNo']."&out_trade_no=".$data['out_trade_no']."&payRate=".$data['payRate']."&service=".$data['service']."&settleAccount=".$data['settleAccount']."&settleBank=".$data['settleBank']."&settleFee=".$data['settleFee']."&useTime=".$data['useTime']."&key=".$smartcard_user['merKey'];
        
        $sign= "accNo=".$data['accNo']."&agentNo=".$data['agentNo']."&amount=".$data['amount']."&cvn2=".$data['cvn2']."&lservice=".$data['lservice']."&merNo=".$data['merNo']."&out_trade_no=".$data['out_trade_no']."&payRate=".$data['payRate']."&service=".$data['service']."&settleFee=".$data['settleFee']."&useTime=".$data['useTime']."&key=".$smartcard_user['merKey'];
        
        
        //md5加密后字母大写
        $sign= strtoupper(md5($sign));
        
        
        $data['sign']=$sign;
        
        //         dump($data);
        
        
        $result=cURLSSLHttp($url,$data);
        //             var_dump($result);
        # 写入日志
        R("Payapi/Api/PaySetLog",array("./PayLog","Smartcard_paytosys_Return__",'------请求参数-----'.implode(',',$data).'----回调返回信息参数----'.$result));
        
        $msg=json_decode($result);
        
        if($msg->respCode=='0000')
        {
            $data['respCode']=$msg->respCode;
            $data['message']=$msg->message;
            $data['sign']=$msg->sign;
            if($msg->out_trade_no)
            {
                $data['out_trade_no']=$msg->out_trade_no;
            }
            if($msg->agreementNo)
            {
                $data['agreementNo']=$msg->agreementNo;
            }
            
            $data['state']=1;
            
            
        }else
        {
            //   echo $msg->message;
            $data['respCode']=$msg->respCode;
            $data['message']=$msg->message;
        }
        
        if($msg->dfRespCode=='0000')
        {
            $data['dfRespCode']=$msg->dfRespCode;
            $data['dfMessage']=$msg->dfMessage;
            $data['dfOrderId']=$msg->dfOrderId;
        }else 
        {
            $data['dfRespCode']=$msg->dfRespCode;
            $data['dfMessage']=$msg->dfMessage;
            if($msg->dfOrderId)
            {
             $data['dfOrderId']=$msg->dfOrderId;
            }
        }
        
        $whereuser['repayment_plan_id']=array('eq',$repayment_plan_id);
        //      $whereuser['out_trade_no']=array('eq',$data['out_trade_no']);
        $has=M('repayment_plan')->where($whereuser)->find();
        if(!$has)
        {
            M('repayment_plan')->add($data);
        }else {
            M('repayment_plan')->where($whereuser)->save($data);
        }
        
        
        return $msg->respCode;
        
        
    }
    
    //快捷交易
    public  function pay()
    {
//         $where['smart_card_id']=array('eq',$smart_card_id);
        $where['state']=array('eq',3);
        $smart_card=M('smart_card')->where($where)->find();
        
        if($smart_card)
        {
//             $wheres['smart_card_id'] = $smart_card_id;
            $wheres['state'] = 2;
            
            $beginToday=time()-3600;
            $endToday=time()+3600;
            $wheres['paydate']  = array('BETWEEN',array($beginToday,$endToday));
            $list =  M('repayment_plan')->where($wheres)->order('paydate')->select();
            for ($i = 0; $i < count($list); $i++) {
                
                if($this->isintime($list[$i]['paydate'])) {
                   
                    
                    $smart_card_id=$list[$i]['smart_card_id'];
                    
                    $whereu['smart_card_id']=array('eq',$smart_card_id);
                    $userid=M('smart_card')->where($whereu)->getField('user_id');
                    
                    $repayment_plan_id=$list[$i]['repayment_plan_id'];
                    $respCode= $this->paytosys($smart_card_id,$repayment_plan_id);
                    if($respCode=='0000')
                    {
                        $whereuser['repayment_plan_id']=array('eq',$repayment_plan_id);
                        //      $whereuser['out_trade_no']=array('eq',$data['out_trade_no']);
                        $savedata['state']=1;
                        $has=M('repayment_plan')->where($whereuser)->save($savedata);
                        
                        $type="16";
                        $pay_type="6";
                        //生成交易流水
                        $this->createOrder($userid,$list[$i]['money'],"智能还款交易",$list[$i]['out_trade_no'],"智能还款交易",$pay_type,$type,1);
                        
                    }else {
                        $whereuser['repayment_plan_id']=array('eq',$repayment_plan_id);
                        //      $whereuser['out_trade_no']=array('eq',$data['out_trade_no']);
                        $savedata['state']=5;
                        $has=M('repayment_plan')->where($whereuser)->save($savedata);
                        
                        
                    }
                    
                    
                }
                
                
            }
            
        } 
  
    }
    
    
    
    
    //查询时间是否在之后的一分钟内
    public function isintime($time)
    {
        $time = time() - $time;
        
        if ($time < 60 && $time>-60) {
            return  true;
        }else {
            return  false;
        }
    }
    
    //执行定时任务
    public function dotask()
    {
        ignore_user_abort();//关掉浏览器，PHP脚本也可以继续执行.
        set_time_limit(0);// 通过set_time_limit(0)可以让程序无限制的执行下去
        $interval=60*1;// 每隔一分钟运行
        do{
            
            $run =$this->pay();  //执行还款计划
            
            sleep($interval);// 等待1分钟
        }
        while(true); 
        
    }
    
    
    //生成用户订单
    public  function createOrder($userid,$money,$title,$sh_ordersn,$pay_name,$pay_type,$type,$jy_status=4)
    {
        $pt_ordersn="ZNHK". date("YmdHis",time()).rand(0, 100).$userid;
        
        $where['user_id'] = array('eq',$userid);
        $user=M('user')->where($where)->select();
        
        $usermoneybf=$user[0]['wallet_money'];
        
//         $usermoneyat=$usermoneybf-$money;
        $usermoneyat=$user[0]['wallet_money'];
        
      /*   M('user')->where($where)->save(array(
            'wallet_money'=>$usermoneyat
        ));; */
        
        //交易明细
        $detailed = M("money_detailed");	// 实例化模型类
        
        // 构建写入的数据数组
        $data["goods_name"] =$title;
        $data["money"] = $money;
        $data["user_id"] = $userid;
        $data["sh_ordersn"] = $sh_ordersn;
        $data["user_pay_supply_id"] = $pay_type;
        $data["pay_name"] = $pay_name;
        $data["pt_ordersn"] = $pt_ordersn;
        $data["js_status"] = 1;
        $data["jy_status"] = $jy_status;
        $data["after_money"] = $usermoneyat;
        $data["pay_money"] = $money;
        $data["before_money"] = $usermoneybf;
        $data["money_type_id"] = $type;
        $data["t"] = time();
        $data["d_t"] = time();
        $data["service_charge"] = 0;
        $data["serial_number"] = 0;
        $data["bank_cart"] = 0;
        $data["pay_zhe"] = 0;
        $data["phone"] = 0;
        $data["timestamp"] = time();
        $data["freezing_money"] = 0;
        $data["benefit"] = $money;
        
        //         var_dump($data);
        // 写入数据
        if($lastInsId = $detailed->add($data)){
            //             echo "插入数据 id 为：$lastInsId";
            return true;
        } else {
            //             $this->error('数据写入错误！');
            return false;
        }
   
    }
    
    //生成用户退款订单
    public  function createTkOrder($userid,$money,$title,$sh_ordersn,$pay_name,$pay_type,$type,$jy_status=1)
    {
        $pt_ordersn="ZNHK". date("YmdHis",time()).rand(0, 100).$userid;
        
        $where['user_id'] = array('eq',$userid);
        $user=M('user')->where($where)->select();
        
        $usermoneybf=$user[0]['wallet_money'];
        
        $usermoneyat=$usermoneybf+$money;
        
        M('user')->where($where)->save(array(
            'wallet_money'=>$usermoneyat
        ));;
        
        //交易明细
        $detailed = M("money_detailed");	// 实例化模型类
        
        // 构建写入的数据数组
        $data["goods_name"] =$title;
        $data["money"] = $money;
        $data["user_id"] = $userid;
        $data["sh_ordersn"] = $sh_ordersn;
        $data["user_pay_supply_id"] = $pay_type;
        $data["pay_name"] = $pay_name;
        $data["pt_ordersn"] = $pt_ordersn;
        $data["js_status"] = 4;
        $data["jy_status"] = $jy_status;
        $data["after_money"] = $usermoneyat;
        $data["pay_money"] = $money;
        $data["before_money"] = $usermoneybf;
        $data["money_type_id"] = $type;
        $data["t"] = time();
        $data["d_t"] = time();
        $data["service_charge"] = 0;
        $data["serial_number"] = 0;
        $data["bank_cart"] = 0;
        $data["pay_zhe"] = 0;
        $data["phone"] = 0;
        $data["timestamp"] = time();
        $data["freezing_money"] = 0;
        $data["benefit"] = $money;
        
        //         var_dump($data);
        // 写入数据
        if($lastInsId = $detailed->add($data)){
            //             echo "插入数据 id 为：$lastInsId";
            return true;
        } else {
            //             $this->error('数据写入错误！');
            return false;
        }
        
    }
    
    //生成还款计划
    function  createplant($smart_card_id,$money,$startdate,$enddate,$repayment,$mybank_id,$service_charge,$user_pay_supply_id)
    {
        //         echo "生成还款计划";
        
        $state=2;
        $money=$money+$service_charge;
        
        $wheres['mybank_id']=array('eq',$mybank_id);
        
        $mybank_info=M('mybank')->where($wheres)->find();
        
        $where['user_pay_supply_id']=array('eq',8);
        
        //         $user_pay_supply=M('user_pay_supply')->where($where)->find();
        $user_pay_supply=M('user_pay_supply')->JOIN('LEFT JOIN y_user_js_supply on y_user_js_supply.user_js_supply_id=y_user_pay_supply.user_js_supply_id')->field('y_user_pay_supply.*,y_user_js_supply.yy_rate as pyy_rate')->where($where)->find();
        
        
        $payRate=$user_pay_supply['yy_rate']/1000;
        
        $settleFee=$user_pay_supply['pyy_rate'];
        
        $single_minprice=$user_pay_supply['single_minprice'];
        
        $centent=$enddate-$startdate;
        
        $paydate=$this->create_date_array($repayment,$startdate,$enddate);
        
        $tempcode=$centent/$repayment;
        
        $beginmoney=$money/$repayment-$service_charge;
        
        if($beginmoney<0)
        {
            $beginmoney=0;
        }
        
        $money = $this->create_money_array($repayment,$beginmoney,$money/$repayment+$service_charge,$money,$single_minprice);
        
        
        
        for ($i = 0; $i < count($paydate); $i++) {
            $date['type'] =1;
            $date['payRate']=$payRate;
            $date['settleFee']=$settleFee;
            
            $date['smart_card_id']=$smart_card_id;
            $date['service']="nctjfd03";
            $date['lservice']="pay";
            $date['money']=$money[$i];
            
            $date['paydate'] = $paydate[$i];
            $date['out_trade_no'] ="ZNHK". date("YmdHis",time()).$paydate[$i];
            
            $date['accNo']=$mybank_info['cart'];
            $date['cvn2']=$mybank_info['cw_two'];
            $date['useTime']=$mybank_info['useful'];
            
            $date['amount']=$money[$i];
            //             $date['settleType']=0;
            $date['state']=2;
            
            $res=M('repayment_plan')->add($date);
            
            $whereorder['out_trade_no']=$date['out_trade_no'];
            
            $repaymentorder=M('repayment_plan')->where($whereorder)->find();
            if($i>0)
            {
                $lastpaydate= $paydate[$i-1];
            }else
            {
                $lastpaydate=strtotime(date('Y-m-d',$paydate[$i]));
            }
            //生成消费计划
            $this->create_pay_order($repaymentorder['repayment_plan_id'],$single_minprice,$lastpaydate);
            
        }
        
        
        
        
        
        
    }
    
    //生成支付订单
    public function create_pay_order($repayment_plan_id,$single_minprice,$lastpaydate)
    {
        
        
        $whereorder['repayment_plan_id']=$repayment_plan_id;
        
        $repaymentorder=M('repayment_plan')->where($whereorder)->find();
        
        $money=$repaymentorder['money'];  //还款金额
        
        $num = floor($money/$single_minprice);
        
        if($num>3&&$num<=5)
        {
            //             $num=$num/2;
            $num=rand(3,4);
        }else if($num>5)
        {
            $num=rand(3,5);
        }
        
        $startdate=strtotime(date('Y-m-d',$repaymentorder['paydate']));
        
        $startdate=$lastpaydate;
        
        $paydate=$this->create_date_pay_array($num,$startdate,$repaymentorder['paydate']);
        
        $moneyarr = $this->create_money_array($num,$single_minprice,$money/$num,$money,$single_minprice);
        
        
        for ($i = 0; $i < count($paydate); $i++) {
            
            //             $date['out_trade_no'] ="ZNHKPAY". date("YmdHis",time()).$i;
            
            $date['link_payid'] =$repayment_plan_id;
            
            $date['type'] =2;
            
            $date['payRate']=$repaymentorder['payRate'];
            $date['settleFee']=$repaymentorder['settleFee'];
            
            $date['smart_card_id']=$repaymentorder['smart_card_id'];
            $date['service']=$repaymentorder['service'];
            $date['lservice']=$repaymentorder['lservice'];
            $date['money']=$moneyarr[$i];
            
            $date['paydate'] = $paydate[$i];
            $date['out_trade_no'] ="ZNHKPAY". date("YmdHis",time()).$paydate[$i].$i;
            
            $date['accNo']=$repaymentorder['accNo'];
            $date['cvn2']=$repaymentorder['cvn2'];
            $date['useTime']=$repaymentorder['useTime'];
            
            $date['amount']=$moneyarr[$i];
            //             $date['settleType']=0;
            $date['state']=2;
            
            $res=M('repayment_plan')->add($date);
            
        }
        
        
    }
    
    /**
     * 生成某个范围内的随机时间数组
     * @param <type> $num          随机个数 格式为 int
     * @param <type> $begintime  起始时间 格式为 Y-m-d H:i:s
     * @param <type> $endtime    结束时间 格式为 Y-m-d H:i:s
     */
    function create_date_pay_array($num = 2000 , $begintime, $endtime){
        $i=0;
        $date_array = array();
        while ($i < $num){
            $date = rand($begintime, $endtime);
            //生成9点到22点之间的时间
            $date=$this->changeToTime($date);
            if($i>0)
            {
                if($date_array[$i-1]-$date>0)
                {
                    if($date_array[$i-1]-$date<1200)
                    {
                        $date=$date-1200;
                        if($date>$endtime)
                        {
                            $date=$endtime-200;
                        }
                        
                        if($date<$begintime)
                        {
                            $date=$begintime+200;
                        }
                    }
                }else {
                    if($date-$date_array[$i-1]<1200)
                    {
                        $date=$date+1200;
                        if($date>$endtime)
                        {
                            $date=$endtime-200;
                        }
                        
                        if($date<$begintime)
                        {
                            $date=$begintime+200;
                        }
                    }
                }
            }
           
            if($date>$endtime)
            {
                $date=$endtime-300;
            }
            
            if($date<$begintime)
            {
                $date=$begintime+300;
            }
            
            $date_array[$i] = $date;
            sort($date_array);
            $i++;
        }
        sort($date_array);
        return $date_array;
    }
    
    //生成还款计划
    function  createplantold($smart_card_id,$money,$startdate,$enddate,$repayment,$mybank_id,$service_charge)
    {
        //         echo "生成还款计划";
        
        $state=2;
        
        $money=$money+$service_charge;
        
        $wheres['mybank_id']=array('eq',$mybank_id);
        
        $mybank_info=M('mybank')->where($wheres)->find();
        
        $where['user_pay_supply_id']=array('eq',8);
        
        $user_pay_supply=M('user_pay_supply')->where($where)->find();
        
        $payRate=$user_pay_supply['yy_rate']/1000;
        
        $settleFee=$user_pay_supply['single_minprice'];
        
        $centent=$enddate-$startdate;
        
        $paydate=$this->create_date_array($repayment , $startdate, $enddate);
        
        $tempcode=$centent/$repayment;
        
        $beginmoney=$money/$repayment-$service_charge;
        if($beginmoney<0)
        {
            $beginmoney=0;
        }
        
        $money= $this->create_money_array($repayment,$beginmoney,$money/$repayment+$service_charge,$money);
        
        
        
        for ($i = 0; $i < count($paydate); $i++) {
            
            
            $date['payRate']=$payRate;
            $date['settleFee']=$settleFee;
            
            $date['smart_card_id']=$smart_card_id;
            $date['service']="nctjfd03";
            $date['lservice']="pay";
            $date['money']=$money[$i];
            $date['paydate'] = $paydate[$i];
            $date['out_trade_no'] ="ZNHK". date("YmdHis",time()).$paydate[$i];
            
            $date['accNo']=$mybank_info['cart'];
            $date['cvn2']=$mybank_info['cw_two'];
            $date['useTime']=$mybank_info['useful'];
            
            $date['amount']=$money[$i];
            //             $date['settleType']=0;
            $date['state']=2;
            
            $res=M('repayment_plan')->add($date);
            
            
        }
        
        
        
        
        
        
    }
    
    //生成随机支付金额
    function create_money_array($num = 2000 , $beginmoney, $endmoney, $allmoney,$single_minprice){
        
        
        $i=0;
        $date_array = array();
        $sum=0;
        while ($i < $num){
            $date = rand($beginmoney, $endmoney);
            if($date<0)
            {
                $date = rand($beginmoney, $endmoney);
                if($date<$single_minprice)
                {
                    $date=$single_minprice;
                }
            }
            $date_array[$i] = $date;
            
            $i++;
            $sum=$sum+$date;
        }
        
        $date_array[$num-1]=$date_array[$num-1]+($allmoney-$sum);
        
        if($date_array[$num-1]<0)
        {
            $i=0;
            while ($i < $num){
                $date = $allmoney/$num;
                $date_array[$i] = $date;
                $i++;
            }
        }
        
        
        sort($date_array);
        return $date_array;
        
    }
    
    /**
     * 生成某个范围内的随机时间数组
     * @param <type> $num          随机个数 格式为 int
     * @param <type> $begintime  起始时间 格式为 Y-m-d H:i:s
     * @param <type> $endtime    结束时间 格式为 Y-m-d H:i:s
     */
    function create_date_array($num = 2000 , $begintime, $endtime){
        $i=0;
        $date_array = array();
        while ($i < $num){
            $date = rand($begintime, $endtime);
            
            //生成9点到22点之间的时间
            $date=$this->changeToTime($date);
            
            if($i>0)
            {
                
                if($date_array[$i-1]-$date>0)
                {
                    if($date_array[$i-1]-$date<2400)
                    {
                        $date=$date-2400;
                        if($date>$endtime)
                        {
                            $date=$endtime-200;
                        }
                        
                        if($date<$begintime)
                        {
                            $date=$begintime+200;
                        }
                    }
                }else {
                    if($date-$date_array[$i-1]<2400)
                    {
                        $date=$date+2400;
                        if($date>$endtime)
                        {
                            $date=$endtime-200;
                        }
                        
                        if($date<$begintime)
                        {
                            $date=$begintime+200;
                        }
                    }
                }
            }
            
            if($date>$endtime)
            {
                $date=$endtime-300;
            }
            
            if($date<$begintime)
            {
                $date=$begintime+300;
            }
            
            $date_array[$i] = $date;
            sort($date_array);
            $i++;
        }
        sort($date_array);
        return $date_array;
    }
    
    //生成9点到20点之间的时间
    function  changeToTime($date)
    {
        $alltime = date("Y-m-d H:i:s",$date);
        $ytime = date("Y",$date);
        $mtime = date("m",$date);
        $dtime = date("d",$date);
        
        $htime = date("H",$date);
        $ftime = date("i",$date);
        $stime = date("s",$date);
        
        //     echo "输入时间为".$alltime.",时为".$htime.",分为".$ftime.",秒为".$stime."<br/>";
        
        //重新生成时间为9点到20点内的
//      $htime=rand(9,19);
        //重新生成时间为9点到16点内的
        if($htime<6||$htime>21)
        {
            $htime=rand(6,21);
        }
        $ftime=rand(0,59);
        $ftime=rand(0,59);
        
        $alltime=$ytime."-".$mtime."-".$dtime." ".$htime.":".$ftime.":".$stime;
        
        $newtime = strtotime($alltime);
        
        //     echo "重新生成的时间为".$alltime.",时为".$htime.",分为".$ftime.",秒为".$stime.",时间戳为".$newtime;
        
        return $newtime;
    }
    
    
    /**
     * 生成某个范围内的随机时间
     * @param <type> $begintime  起始时间 格式为 Y-m-d H:i:s
     * @param <type> $endtime    结束时间 格式为 Y-m-d H:i:s
     * @param <type> $now         是否是时间戳 格式为 Boolean
     */
    function randomDate($begintime, $endtime="", $now = true) {
        $begin = strtotime($begintime);
        $end = $endtime == "" ? mktime() : strtotime($endtime);
        $timestamp = rand($begin, $end);
        // d($timestamp);
        return $now ? date("Y-m-d H:i:s", $timestamp) : $timestamp;
    }
    
   
    
    
    //计划详情
    public  function plantlist()
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
         
        
        $smart_card_id=$_REQUEST['smart_card_id'];
        
        $where['smart_card_id'] = $smart_card_id;
        $where['type'] = 1;
        $list =  M('repayment_plan')->where($where)->order('paydate')->select();
        
        for ($i = 0; $i < count($list); $i++) {
            
            $whereplant['link_payid']=$list[$i]['repayment_plan_id'];
            $cplant=M('repayment_plan')->where($whereplant)->order('paydate asc')->select();
            $list[$i]['payplants']=$cplant;
        }
        $wheres['smart_card_id'] = $smart_card_id;
        $smartinfo=M('smart_card')->join('LEFT JOIN y_mybank  on  y_smart_card.mybank_id=y_mybank.mybank_id ')->join('LEFT JOIN y_bank  on  y_bank.bank_id=y_mybank.bank_id ')->field('y_smart_card.*,y_mybank.bill_day,y_mybank.refund_day,y_mybank.amount,y_mybank.cart,y_bank.icon,y_bank.bg,y_bank.name')->where($wheres)->find();
        
        if ($smartinfo){
            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>array('list'=>$list,'smartinfo'=>$smartinfo)));die;
        }else{
            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;
        }
    }
    
    //计划详情
    public  function plantlistold()
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
        
        $smart_card_id=$_REQUEST['smart_card_id'];
        
        $where['smart_card_id'] = $smart_card_id;
        
        $list =  M('repayment_plan')->where($where)->order('paydate')->select();
        
        $smartinfo=M('smart_card')->join('LEFT JOIN y_mybank  on  y_smart_card.mybank_id=y_mybank.mybank_id ')->join('LEFT JOIN y_bank  on  y_bank.bank_id=y_mybank.bank_id ')->field('y_smart_card.*,y_mybank.bill_day,y_mybank.refund_day,y_mybank.amount,y_mybank.cart,y_bank.icon,y_bank.bg,y_bank.name')->where($where)->find();
        
        if ($smartinfo){
            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>array('list'=>$list,'smartinfo'=>$smartinfo)));die;
        }else{
            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;
        }
    }
    
  //计划交易流水
    public  function paydetail(){
        
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
        
        $smart_card_id=$_REQUEST['smart_card_id'];
        
        $where['smart_card_id'] = $smart_card_id;
        $where['state'] = 1;
        $list =  M('repayment_plan')->where($where)->order('paydate')->select();
        if(!$list)
        {
            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;
//             goback("该计划暂无订单");
//             return;
        }
        $orders=array();
        for ($i = 0; $i < count($list); $i++) {
            $orders[$i]=$list[$i]['out_trade_no'];
        }
        
        $whereorder['sh_ordersn']=array('in',$orders);
        
        $orderlist=M('money_detailed')->where($whereorder)->select();
        
//         echo M('money_detailed')->getLastSql();
        
//         $this->assign('list',$orderlist);
//         $this->display();

        
        if ($orderlist){
            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>array('list'=>$orderlist)));die;
        }else{
            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;
        }
    }
    
    public  function details()
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
        
        
        $where['money_detailed_id'] =$_REQUEST['money_detailed_id'];
        
        $money_detailed=M('money_detailed')->where($where)->find();
        
//         $this->assign('money_detailed',$money_detailed);
//         $this->display();
        if ($money_detailed){
            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>array('money_detailed'=>$money_detailed)));die;
        }else{
            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;
        }
        
    }
    
    //计划删除接口
    public  function deleteplant()
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
        
        $smart_card_id=$_REQUEST['smart_card_id'];
        
        $where['smart_card_id'] = $smart_card_id;
        
        $where['user_id'] = $this->uid;
        
        $where['_string'] = ' state=1 or state=2 or state=5 ';
        
        $smartinfo=M('smart_card')->where($where)->find();
        
        if($smartinfo)
        {
            $wheredel['smart_card_id'] = $smart_card_id;
            
            $wheredel['user_id'] = $this->uid;
            
            $savedate['state'] = 0;
            
            $saveres=M('smart_card')->where($where)->save($savedate);
            
            if($saveres){
                echo json_encode(array('code'=>200,'msg'=>'删除成功'));die;
            }else {
                echo json_encode(array('code'=>201,'msg'=>'删除失败'));die;
            }
            
        }else {
            
            $wheres['smart_card_id'] = $smart_card_id;
            $smartinfos=M('smart_card')->where($wheres)->find();
            if($smartinfos['enddate']<time())
            {
                $respCode=$this->delCard($smart_card_id,$user_id);
                if($respCode=='0000')
                {
                    $wherecard['smart_card_id']=$smart_card_id;
                    $smart_card=M('smart_card')->where($wherecard)->find();
                    
                    $savedate['state'] = 0;
                    
                    $saveres=M('smart_card')->where($wherecard)->save($savedate);
                    
                    $ret_code=200;
                    $ret_msg="删除成功";
                }else
                {
                    $wherecard['smart_card_id']=$smart_card_id;
                    $smart_card=M('smart_card')->where($wherecard)->find();
                    
                    $ret_code=201;
                    $ret_msg=$smart_card['message'];
                    
                }
                echo json_encode(array('code'=>$ret_code,$ret_msg));die;
            }else {
                echo json_encode(array('code'=>201,'msg'=>'计划不能删除'));die;
            }
            
            
        }
        
        
        
        
        
    }
    
}