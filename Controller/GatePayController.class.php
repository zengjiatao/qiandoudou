<?php  
namespace Payapi\Controller;
use Think\Aes;

//app支付控制器
class GatePayController extends BaseController
 {
     public $uid;
     public $parem;
     public $sign;
    public $table;
    public $jstable;
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

         $this->table = "money_detailed";
         $this->jstable = "moneyjs_detailed";
     }
     
     public  function  paynotify()
     {
         error_reporting(0);
         
         $input = file_get_contents('php://input');
         
         $param = explode('&',$input);
         
         R("Payapi/Api/PaySetLog",array("./PayLog","GatePay_paynotify_Return__","----回调返回信息参数----".$input."----\r\n"));
         
         ob_clean(); //处理网页多余的字符
         
         
         
         //         nonce_str=cMqLBiuOje&out_trade_no=SFOTO1518178859&trade_state=SUCCESS&sp_id=1000&total_fee=100000&sign=E864422F34CC9547FF4A7EFFFDC10D92&trade_no=201802111512188555387266254158&mch_id=100050002464505
         
         
         
         # 订单处理
         
         $pt_ordersn = explode("=",$param[2]); // 订单号
         
         $status = explode("=",$param[4]); // 回调状态
         
         $total_fee = explode("=",$param[5]); // 交易金额
         
         
         
         if (empty($pt_ordersn[1]))
         
         {
             
             echo 'error订单号不能为空=信息';exit;
             
         }
         
         $pt_ordersn = $pt_ordersn[1];
         
         $iforder = M("money_detailed")->field('user_id,user_pay_supply_id,jy_status,service_charge')->where(array('pt_ordersn'=>$pt_ordersn))->find();
         
         $user_js_supply_id = M("user_pay_supply")->where(array('user_pay_supply_id'=>$iforder['user_pay_supply_id']))->getField('user_js_supply_id');
         
         
         
         if($status[1] == 'SUCCESS')  # 交易成功
         
         {
             
           
             
             $res = $this->qry($pt_ordersn,$iforder['user_pay_supply_id']);
             
             
             $jy_status=1;
             
             $jsdata['js_status'] = 2;
             
             $jsdata['j_success_time'] = time()+30;
             
             if($res['code'] == '200') # 结算成功
             
             {
                 $jy_status=1;
                 
                 $jsdata['js_status'] = 2;
                 
                 $jsdata['j_success_time'] = time()+30;
                 
                 $js_status = 2;
                 
             }else if($res['code'] == '300') # 结算中
             
             {
                 $jy_status=1;
                 
                 $jsdata['js_status'] =2;
                 
                 $jsdata['j_success_time'] = time()+30;
                 
                 $js_status = 2;
                 
             }else if($res['code'] == '500') # 未结算
             
             {
                 $jy_status=1;
                 
                 $jsdata['js_status'] = 0;
                 
                 $jsdata['j_success_time'] = "";
                 
                 $js_status = 0;
                 
             }else if($res['code'] == '400') # 结算失败
             
             {
                 $jy_status=3;
                 
                 $jsdata['js_status'] = 3;
                 
                 $jsdata['j_success_time'] = "";
                 
                 $js_status = 3;
                 
             }else{
                 
                 $jy_status=3;
                 
                 $jsdata['js_status'] = 3;
                 
                 $jsdata['j_success_time'] = "";
                 
                 $js_status = 3;
             }
             
             $ifjsorder = M($this->jstable)->where(array('pt_ordersn'=>$pt_ordersn))->getField('pt_ordersn');
             
             if($ifjsorder)
             
             {
                 
                 M($this->table)->where(array('pt_ordersn'=>$pt_ordersn))->save(array('jy_status'=>$jy_status,'js_status'=>$js_status));
                 
                 M($this->jstable)->where(array('pt_ordersn'=>$pt_ordersn))->save($jsdata);
                 
                 
                 
             }
             
             if($pt_ordersn)
             
             {
                 R("Func/Fenxiao/fenxiaoByOrder",array($pt_ordersn));
                 
                 R("Func/Fenxiao/fenxiaoTkLevelOrder",array($pt_ordersn)); # 交易分 - 固定收益
                 
                 # 结算成功订单分销
                 
                 R("Func/Fenxiao/jiesuanfenxiaoByOrder",array($pt_ordersn));
                 
                 R("Func/Fenxiao/fenxiaoTkJsLevelOrder",array($pt_ordersn)); # 结算分 - 固定收益
                 
             }
             
         }else{
             $res = $this->qry($pt_ordersn,$iforder['user_pay_supply_id']);
             
             
             $jy_status=1;
             
             $jsdata['js_status'] = 2;
             
             $jsdata['j_success_time'] = time()+30;
             
             if($res['code'] == '200') # 结算成功
             
             {
                 $jy_status=1;
                 
                 $jsdata['js_status'] = 2;
                 
                 $jsdata['j_success_time'] = time()+30;
                 
                 $js_status = 2;
                 
             }else if($res['code'] == '300') # 结算中
             
             {
                 $jy_status=1;
                 
                 $jsdata['js_status'] = 2;
                 
                 $jsdata['j_success_time'] = time()+30;
                 
                 $js_status = 2;
                 
             }else if($res['code'] == '500') # 未结算
             
             {
                 $jy_status=1;
                 
                 $jsdata['js_status'] = 0;
                 
                 $jsdata['j_success_time'] = "";
                 
                 $js_status = 0;
                 
             }else if($res['code'] == '400') # 结算失败
             
             {
                 $jy_status=3;
                 
                 $jsdata['js_status'] = 3;
                 
                 $jsdata['j_success_time'] = "";
                 
                 $js_status = 3;
                 
             }else{
                 
                 $jy_status=3;
                 
                 $jsdata['js_status'] = 3;
                 
                 $jsdata['j_success_time'] = "";
                 
                 $js_status = 3;
             }
             
             $ifjsorder = M($this->jstable)->where(array('pt_ordersn'=>$pt_ordersn))->getField('pt_ordersn');
             
             if($ifjsorder)
             
             {
                 
                 M($this->table)->where(array('pt_ordersn'=>$pt_ordersn))->save(array('jy_status'=>$jy_status,'js_status'=>$js_status));
                 
                 M($this->jstable)->where(array('pt_ordersn'=>$pt_ordersn))->save($jsdata);
                 
             }
             
             if($pt_ordersn)
             
             {
                 
                 R("Func/Fenxiao/fenxiaoByOrder",array($pt_ordersn));
                 
                 R("Func/Fenxiao/fenxiaoTkLevelOrder",array($pt_ordersn)); # 交易分 - 固定收益
                 
                 # 结算成功订单分销
                 
                 R("Func/Fenxiao/jiesuanfenxiaoByOrder",array($pt_ordersn));
                 
                 R("Func/Fenxiao/fenxiaoTkJsLevelOrder",array($pt_ordersn)); # 结算分 - 固定收益
                 
             }
         }
         
         
         
         echo "SUCCESS";exit;
         
     }
     
     //生成随机数
     public  function createNonceStr($length = 16)
     {
         $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
         $str = '';
         for ($i = 0; $i < $length; $i++) {
             $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
         }
         return $str;
     }
     
     //商户进件并开通服务
     private function openuser($user_id,$user_pay_supply_id)
     {
         $code=200;
         $msg="开通成功";

         # 新增多个机构号判断
         if($user_pay_supply_id == 25)
         {
             $configS = C('SHANGFUPUBLIC');
             $whereuser['sp_id']=$configS['SP_ID'];
         }

         $whereuser['user_id']=$user_id;
         $gatepay_user=M('gatepay_user')->where($whereuser)->find();


         # 写入日志
         R("Payapi/Api/PaySetLog",array("./PayLog","GatePayController_jjopen__","商户进件并开通服务 - 请求参数".json_encode($gatepay_user)."、、、、、uid:".$user_id."-".$user_pay_supply_id.json_encode($whereuser)."----\r\n\------"));

         if(!$gatepay_user){
             $state=$this->intomerchant($user_id,$user_pay_supply_id); //商户入驻接口

             if($state==1) //入驻成功
             {
                 $whereuser['user_id']=$user_id;
                 $gatepay_user=M('gatepay_user')->where($whereuser)->find();
                 $rstate=$this->sendrate($gatepay_user['gatepay_user_id'],$user_pay_supply_id);
                 if($rstate==1)
                 {
                     $code=200;
                     $msg="开通成功";
                 }else if($rstate==-1){
                     $code=400;
                     $msg="通道正在维护中";
                 }else {
                     
                     $where['user_id']=$user_id;
                     $where['gatepay_user_id']=$gatepay_user['gatepay_user_id'];
                     $where['user_pay_supply_id']=$user_pay_supply_id;
                     $gatepay_user=M('gatepay_open')->where($where)->find();
                     
                     $code=400;
                     $msg=$gatepay_user['message'];
                     
                 }
                 
             }else  if($state==-1){
                 $code=400;
                 $msg="请先绑定结算卡";
                 
             }else {
                 $whereuser['user_id']=$user_id;
                 $gatepay_user=M('gatepay_user')->where($whereuser)->find();
                 
                 $code=400;
                 $msg=$gatepay_user['message'];
             }
         }else
         {

             R("Payapi/Api/PaySetLog",array("./PayLog","GatePayController_jjopen__","商户进件并开通状态111 - 请求参数".json_encode($gatepay_user)."----\r\n\------"));
             if($gatepay_user['state']==1)
             {
                 $where['user_id']=$user_id;
                 $where['gatepay_user_id']=$gatepay_user['gatepay_user_id'];
                 $where['user_pay_supply_id']=$user_pay_supply_id;
                 $gatepay_open=M('gatepay_open')->where($where)->find();
                 if($gatepay_open['state']==1){
                    
                     $code=200;
                     $msg="开通成功";
                 }else{
                     
                     $rstate=$this->sendrate($gatepay_user['gatepay_user_id'],$user_pay_supply_id);
                     if($rstate==1)
                     {
                         $code=200;
                         $msg="开通成功";
                     }else if($rstate==-1){
                         $code=400;
                         $msg="通道正在维护中";
                     }else {
                         
                         $where['user_id']=$user_id;
                         $where['gatepay_user_id']=$gatepay_user['gatepay_user_id'];
                         $where['user_pay_supply_id']=$user_pay_supply_id;
                         $gatepay_user=M('gatepay_open')->where($where)->find();
                         
                         $code=400;
                         $msg=$gatepay_user['message'];
                         
                     }
                 } 
                     
       
                 
             }else {

                 # 重新入驻
                 $state = $this->intomerchant($user_id,$user_pay_supply_id); //商户入驻接口
                 if($state==1) //入驻成功
                 {
                     $whereuser['user_id']=$user_id;
                     $gatepay_user=M('gatepay_user')->where($whereuser)->find();
                     $rstate=$this->sendrate($gatepay_user['gatepay_user_id'],$user_pay_supply_id);
                     if($rstate==1)
                     {
                         $code=200;
                         $msg="开通成功";
                     }else if($rstate==-1){
                         $code=400;
                         $msg="通道正在维护中";
                     }else {

                         $where['user_id']=$user_id;
                         $where['gatepay_user_id']=$gatepay_user['gatepay_user_id'];
                         $where['user_pay_supply_id']=$user_pay_supply_id;
                         $gatepay_user=M('gatepay_open')->where($where)->find();

                         $code=400;
                         $msg=$gatepay_user['message'];

                     }

                 }else  if($state==-1){
                     $code=400;
                     $msg="请先绑定结算卡";

                 }else{
                     $code=400;
                     $msg=$gatepay_user['message'];
                 }

             }
         }
         
         
         
         $returndate['code']=$code; 
         $returndate['msg']=$msg; 
         
         return $returndate;
     }
     
     #商户入驻数据处理
     private  function intomerchant($user_id,$tdid='')
     {
         
         $wherebank['user_id']=array('eq',$user_id);
         $wherebank['is_normal']=array('eq',1);

         $mybany=M('mybank')->JOIN('LEFT JOIN y_bank on y_bank.bank_id=y_mybank.bank_id')->field('y_mybank.*,y_bank.name as bname,y_bank.lianhanghao')->where($wherebank)->find();


         if(!$mybany)
         {
       //  goback("请先绑定默认结算卡！");
             return -1;
         }
         R("Payapi/Api/PaySetLog",array("./PayLog","GatePayController_jjopen__","cehi请先绑定默认结算卡！".$user_id."---\r\n"));


         # 写入日志
//         R("Payapi/Api/PaySetLog",array("./PayLog","GatePayController_jjopen__","商户入驻数据处理:请求参数".json_encode($mybany)."----\r\n\------"));


         $wherep['provid']=$mybany['province_id'];
         
         $province=M('province')->where($wherep)->find();
         
         $wherec['cityid']=$mybany['city_id'];
         
         $city=M('city')->where($wherec)->find();
         
         $wherea['areaid']=$mybany['area_id'];
         
         $area=M('area')->where($wherea)->find();
         
         
         
         $data['mcht_type']="PERSONNEL"; // 商户类型 PERSONNEL  个人  CORPORATE  企业
         
         $data['mcht_name']=$mybany['nickname']."广州钱兜兜公司个体商户"; // 商户名称
         
         $data['mcht_short_name']=$mybany['nickname']."广州钱兜兜公司个体商户"; //商户简称
         
         $data['business_license']=""; //营业执照编号 当商户类型为CORPORATE 时必填
         
         $data['province']=$province['adcode']; //省 国家统一行政区划代码
         
         $data['city']=$city['adcode']; //市 国家统一行政区划代码
         
         $data['area']=$area['adcode']; //区 国家统一行政区划代码
         
         $data['address']=$province['province'].$city['city'].$area['area']; //具体街道门牌号地址
         
         $data['leg_name']=$mybany['nickname']; //法人姓名
         
         $data['leg_phone']=$mybany['mobile']; //法人电话
         
         $data['leg_email']=$mybany['mobile']."@139.com"; //法人邮箱
         
         $data['id_type']="ID_CARD"; //ID_CARD  身份证（固定值ID_CARD）
         
         $data['id_no']=$mybany['idcard']; //证件号码   传输需加密
         
         $data['acc_type']="PERSONNEL"; //结算账户类型 PERSONNEL  对私   CORPORATE  对公
         
         $data['acc_name']=$mybany['nickname']; //结算账户名称
         
         $data['acc_no']=$mybany['cart']; //结算账号
         
         $data['acc_bank_name']=$mybany['bname']; //结算银行名称
         
         $data['acc_bank_no']=$mybany['lianhanghao']; //结算银行联行号
         
         $data['acc_bank_mobile']=$mybany['mobile']; //银行预留手机号 结算账户类型为PERSONNEL  时必填，用于银行卡四要素认证  传输需加密
         
         $data['nonce_str']=$this->createNonceStr(32); //随机数
         
         $data['user_id']=$user_id; //用户id
         
         $data['addtime']=time(); 
         
         return $this->register($data,$tdid);
     }
     
     #商户入驻
     private  function register($mydata,$tdid=''){

         if($tdid == 25) # 有积分有短信 - 调用
         {
             $config = C('SHANGFUPUBLIC');
         }else{
             $config = C('SHANGFU');
         }
         
         $url=$config['API_URL']."/gate/merchant/register";
         
         
         $data['sp_id']=$config['SP_ID']; //服务商号
         
         $data['mcht_type']="PERSONNEL"; // 商户类型 PERSONNEL  个人  CORPORATE  企业
         
         $data['mcht_name']=$mydata['mcht_name']; // 商户名称
         
         $data['mcht_short_name']=$mydata['mcht_short_name']; //商户简称
         
//          $data['business_license']=$mydata['business_license']; //营业执照编号 当商户类型为CORPORATE 时必填
         
         $data['province']=$mydata['province']; //省 国家统一行政区划代码
         
         $data['city']=$mydata['city']; //市 国家统一行政区划代码
         
         $data['area']=$mydata['area']; //区 国家统一行政区划代码
         
         $data['address']=$mydata['address']; //具体街道门牌号地址
         
         $data['leg_name']=$mydata['leg_name']; //法人姓名


         # 法人号码
         if($tdid == 25) # 有积分有短信 - 调用
         {
//             $mydata['leg_phone'] = substr($mydata['leg_phone'],0,3).mt_rand(10000000,99999999);  # 传值接口

             $mydata1['leg_phone'] =  substr($mydata['leg_phone'],0,3).mt_rand(10000000,99999999);  # 传值接口
             $data['leg_phone']=$mydata1['leg_phone']; //法人电话
         }else{
             $data['leg_phone']=$mydata['leg_phone']; //法人电话
         }

         
         $data['leg_email']=$mydata['leg_email']; //法人邮箱
         
         $data['id_type']=$mydata['id_type']; //ID_CARD  身份证（固定值ID_CARD）
         
         $data['id_no']=$mydata['id_no']; //证件号码   传输需加密
         
         $data['acc_type']=$mydata['acc_type']; //结算账户类型 PERSONNEL  对私   CORPORATE  对公
         
         $data['acc_name']=$mydata['acc_name']; //结算账户名称
         
         $data['acc_no']=$mydata['acc_no']; //结算账号
         
         $data['acc_bank_name']=$mydata['acc_bank_name']; //结算银行名称
         
         $data['acc_bank_no']=$mydata['acc_bank_no']; //结算银行联行号
         
         $data['acc_bank_mobile']=$mydata['acc_bank_mobile']; //银行预留手机号 结算账户类型为PERSONNEL  时必填，用于银行卡四要素认证  传输需加密
        
         $data['nonce_str']=$mydata['nonce_str']; //随机数
         
         
         ksort($data, SORT_NATURAL); //按照字母排序
         
         $keys = array_keys($data); //获取key数组
         
//          var_dump($keys);
         
         $values=array_values($data); //获取values数组
         
//          var_dump($values);
         
         $signstr="";
         
         for ($i = 0; $i < count($values); $i++) {
             $signstr=$signstr.$keys[$i]."=".$values[$i]."&";
         }
         
         $signstr=$signstr."key=".$config['SECRET_KEY'];
         
         $sign= strtoupper(Md5($signstr)); //转大写
         
         $data['sign']=$sign; //MD5 签名结果，详见“签名说明”
         
         $key=substr($config['DATA_KEY'],0,16);
        
         
         //传输需要加密的字段
         $data['id_no']=$this->myEncode($data['id_no'],$key); //证件号码   传输需加密
         $data['acc_name']=$this->myEncode($data['acc_name'],$key); // 传输需加密
         $data['acc_no']=$this->myEncode($data['acc_no'],$key); //   传输需加密
         $data['acc_bank_mobile']=$this->myEncode($data['acc_bank_mobile'],$key); //   传输需加密
        
//          var_dump($data);
       
         $result=cURLSSLHttp($url,$data);
        
//          var_dump($result);
         
         $nowtime= date("Y-m-d h:i:s", time());
         # 写入日志
         R("Payapi/Api/PaySetLog",array("./PayLog","GatePayController_register_Return__",$nowtime.'请求参数'.implode(',',$data).'----回调返回信息参数----'.$result));
   
         $msg=json_decode($result);
         
         if($msg->status=='SUCCESS')
         {
             $mydata['status']=$msg->status;
             $mydata['message']="开通成功";
             $mydata['mcht_no']=$msg->mcht_no;
             $mydata['secret_key']=$msg->secret_key;
             $mydata['data_key']=$msg->data_key;
             $mydata['state']=1;
         }else 
         {
             $mydata['status']=$msg->status;
             $mydata['message']=$msg->message;
             $mydata['state']=2;
         }
         
         $mydata['sp_id']= $data['sp_id'];

         $where['user_id']=$mydata['user_id'];
         $where['sp_id']=$data['sp_id'];  # 新增多个服务商号判断


         $wherebank['user_id']=array('eq',$mydata['user_id']);
         $wherebank['is_normal']=array('eq',1);
         $mybanyTrue=M('mybank')->JOIN('LEFT JOIN y_bank on y_bank.bank_id=y_mybank.bank_id')->field('y_mybank.*,y_bank.name as bname,y_bank.lianhanghao')->where($wherebank)->find();
         $where['leg_phone']=$mybanyTrue['mobile'];
         $gatepay_user=M('gatepay_user')->where($where)->find();
         
         if($gatepay_user)
         {
             if($gatepay_user['state']!=1)
             {
                 $gatepay_user=M('gatepay_user')->where($where)->save($mydata);
             }
             
         }else {
             $gatepay_user=M('gatepay_user')->add($mydata);
         }
         
         $where['user_id']=$mydata['user_id'];
         $where['leg_phone']=$mydata['leg_phone'];
         $gatepay_user=M('gatepay_user')->where($where)->find();
         
         return $gatepay_user['state'];
         
         
     }
     
     //商户开通数据处理
     private function sendrate($gatepay_user_id,$user_pay_supply_id)
     {
         # 新增多个服务号进行判断进件
         if($user_pay_supply_id == 25)
         {
             $configS = C('SHANGFUPUBLIC');
             $wheregatepay['sp_id']=$configS['SP_ID'];
         }
         $wheregatepay['gatepay_user_id']=$gatepay_user_id;
         $gatepay_user=M('gatepay_user')->where($wheregatepay)->find();
         
         $wheregatespay['y_user_pay_supply.user_pay_supply_id']=$user_pay_supply_id;
         $wheregatespay['y_user_pay_supply.status']=1;
         $user_pay_supply=M('user_pay_supply')->JOIN('LEFT JOIN y_user_js_supply on y_user_js_supply.user_js_supply_id=y_user_pay_supply.user_js_supply_id')->field('y_user_pay_supply.*,y_user_js_supply.yy_rate as txyy_rate')->where($wheregatespay)->find();
         
         if(!$user_pay_supply)
         {
             
             return -1;
         }
         
         $data['gatepay_user_id']=$gatepay_user_id; 
         
         $data['user_id']=$gatepay_user['user_id']; 
         
         $data['user_pay_supply_id']=$user_pay_supply_id; 
       
         $data['mcht_no']=$gatepay_user['mcht_no']; // 商户编码
         
         if($user_pay_supply['is_send']==1){
             
             $data['busi_type']="EPAY_SAPPLY_ALL"; //业务类型如下：  微信：WX_ALL 支付宝：ALIPAY_ALL  快捷-有短-同名：EPAY_SAPPLY _ALL  快捷-无短-同名：EPAY_SPAY_ALL
             
         }else{
             $data['busi_type']="EPAY_SPAY_ALL"; //业务类型如下：  微信：WX_ALL 支付宝：ALIPAY_ALL  快捷-有短-同名：EPAY_SAPPLY _ALL  快捷-无短-同名：EPAY_SPAY_ALL
             
         }
         
         $data['catalog']="APP"; // 业务经营类目，参考：附件经营类目
         
         $data['settle_type']="REAL_PAY"; // REAL_PAY  交易实时结算   BALANCE  交易T+1结算
         
         $data['settle_rate']=$user_pay_supply['yy_rate']/1000; // 结算费率 “0.003” 代表费率千分之3
         
         $data['extra_rate_type']="AMOUNT"; // 额外手续费 AMOUNT-按固定值
         
         $data['extra_rate']=$user_pay_supply['txyy_rate']*100; // 额外费率 固定值单位：分  提现手续费
         
         $data['reason']="商户入驻费率设置"; // 变更原因 业务费率变更原因
         
         $data['nonce_str']=$this->createNonceStr(32); //随机数
         
         $data['addtime']=time(); //用户id
         
         return $this->rate($data,$user_pay_supply_id);
         
         
     }
     
     //业务开通及费率变更
     private function rate($mydata,$tdid='')
     {
         if($tdid == 25) # 有积分有短信 - 调用
         {
             $config = C('SHANGFUPUBLIC');
         }else{
             $config = C('SHANGFU');
         }
         
         $url=$config['API_URL']."/gate/merchant/rate";
         
         $data['sp_id']=$config['SP_ID']; //服务商号
         
         $mydata['sp_id']=$data['sp_id']; //服务商号
         
         $data['mcht_no']=$mydata['mcht_no']; // 商户编码
         
         $data['busi_type']=$mydata['busi_type']; //业务类型如下：  微信：WX_ALL 支付宝：ALIPAY_ALL  快捷-有短-同名：EPAY_SAPPLY _ALL  快捷-无短-同名：EPAY_SPAY_ALL
         
         $data['catalog']=$mydata['catalog']; // 业务经营类目，参考：附件经营类目
         
         $data['settle_type']=$mydata['settle_type']; // REAL_PAY  交易实时结算   BALANCE  交易T+1结算
         
         $data['settle_rate']=$mydata['settle_rate']; // 结算费率 “0.003” 代表费率千分之3
         
         $data['extra_rate_type']=$mydata['extra_rate_type']; // 额外手续费 AMOUNT-按固定值
         
         $data['extra_rate']=$mydata['extra_rate']; // 额外费率 固定值单位：分
         
         $data['reason']=$mydata['reason']; // 变更原因 业务费率变更原因
         
         $data['nonce_str']=$mydata['nonce_str']; // 随机数
         
         
         ksort($data, SORT_NATURAL); //按照字母排序
         
         $keys = array_keys($data); //获取key数组
         
         //          var_dump($keys);
         
         $values=array_values($data); //获取values数组
         
         //          var_dump($values);
         
         $signstr="";
         
         for ($i = 0; $i < count($values); $i++) {
             $signstr=$signstr.$keys[$i]."=".$values[$i]."&";
         }
         
         $signstr=$signstr."key=".$config['SECRET_KEY'];
         
         $sign= strtoupper(Md5($signstr)); //转大写
         
         $data['sign']=$sign; //MD5 签名结果，详见“签名说明”
         
//          var_dump($data);
          
         $result=cURLSSLHttp($url,$data);
         
//          var_dump($result);
         
         $nowtime= date("Y-m-d h:i:s", time());
         # 写入日志
         R("Payapi/Api/PaySetLog",array("./PayLog","GatePayController_rate_Return__",$nowtime.'请求参数'.implode(',',$data).'----回调返回信息参数----'.$result));
         
         $msg=json_decode($result);
         
         if($msg->status=='SUCCESS')
         {
             $mydata['status']=$msg->status;
             $mydata['message']="开通成功";
             $mydata['state']=1;
         }else
         {
             $mydata['status']=$msg->status;
             $mydata['message']=$msg->message;
             $mydata['state']=2;
         }

         $where['user_id']=$mydata['user_id'];
         $where['sp_id']=$mydata['sp_id'];
         $where['gatepay_user_id']=$mydata['gatepay_user_id'];
         
         $where['user_pay_supply_id']=$mydata['user_pay_supply_id'];
         
         $gatepay_user=M('gatepay_open')->where($where)->find();
         
         if($gatepay_user)
         {
             if($gatepay_user['state']!=1)
             {
                 $gatepay_user=M('gatepay_open')->where($where)->save($mydata);
             }
             
         }else {
             $gatepay_user=M('gatepay_open')->add($mydata);
         }
         
         $where['user_id']=$mydata['user_id'];
         $where['gatepay_user_id']=$mydata['gatepay_user_id'];
         $where['user_pay_supply_id']=$mydata['user_pay_supply_id'];
         
         $gatepay_user=M('gatepay_open')->where($where)->find();
         
         return $gatepay_user['state'];
         
         
     }
     
     //发送验证码   快捷支付 $user_id : 用户id ；$money：交易金额 ；$mybank_id：交易卡 ；支付通道 $out_trade_no：订单号
     public function sendcode($user_id,$money,$mybank_id,$user_pay_supply_id,$out_trade_no)
     {
         
         $returndata = $this->openuser($user_id,$user_pay_supply_id);
         if($returndata['code']!=200)
         {
            return $returndata;
         }
         
         
         $wheregatespay['y_user_pay_supply.user_pay_supply_id']=$user_pay_supply_id;
         $wheregatespay['y_user_pay_supply.status']=1;
         $user_pay_supply=M('user_pay_supply')->JOIN('LEFT JOIN y_user_js_supply on y_user_js_supply.user_js_supply_id=y_user_pay_supply.user_js_supply_id')->field('y_user_pay_supply.*,y_user_js_supply.yy_rate as txyy_rate')->where($wheregatespay)->find();
         
         if(!$user_pay_supply)
         {
             
//              return -1;
             $returndate['code']=400;
             $returndate['msg']="通道正在维护中";
             return $returndate;
         }
         
         if($user_pay_supply['is_send']==1){
             
             $url='https://'.$_SERVER['HTTP_HOST'].'/Payapi/GatePay';
             //          $url = str_replace(basename($url),"",$url);
             
             $whereuser['user_id']=$user_id;

             # 新增多个服务号进行判断进件
             if($user_pay_supply_id == 25)
             {
                 $configS = C('SHANGFUPUBLIC');
                 $whereuser['sp_id']=$configS['SP_ID'];
             }

             $gatepay_user=M('gatepay_user')->where($whereuser)->find();
             
             //          echo M('gatepay_user')->getLastSql();
             
             //          var_dump($gatepay_user);
             
             
             
             $wherebank['mybank_id']=$mybank_id;
             $mybank=M('mybank')->JOIN('LEFT JOIN y_bank on  y_bank.bank_id =y_mybank.bank_id ')->JOIN('LEFT JOIN y_gatepay_bank on  y_gatepay_bank.bank_id =y_bank.bank_id ')->field('y_mybank.*,y_bank.name as bank_name,y_gatepay_bank.bankcode as bank_code')->where($wherebank)->find();
             
             $data['user_id']=$user_id; // 
             
             $data['addtime']=time(); 
             
             $data['secret_key']=$gatepay_user['secret_key']; //由系统分配密钥
             
             $data['data_key']=$gatepay_user['data_key']; //由系统分配密钥
             
             $data['mch_id']=$gatepay_user['mcht_no']; //由系统分配的商户号
             
             $data['out_trade_no']=$out_trade_no; //商户订单号，确保唯一
             
             $data['total_fee']=$money; //订单总金额，单位为分
             
             $data['body']="在线消费"; //商品名称  按格式“应用-商品名称”赋值，如“手提包-路易威登女式手提包”，包含中文
             
             $data['notify_url']=$url."/paynotify"; //通知地址 快捷支付提交成功后回调的通知地址：务必确保外网可以访问
             
//              $data['notify_url']="http://wallet.insoonto.com/Payapi/GatePay/paynotify";
             
             $data['card_type']="CREDIT"; //卡类型 DEBIT 借记卡  CREDIT 贷记卡
             
             $data['bank_code']=$mybank['bank_code']; // 银行编码银行列表
             
             $data['bank_name']=$mybank['bank_name']; // 开户行名称 银行卡所在支行
             
             $data['card_name']=$mybank['nickname']; // 持卡人姓名 必须与商户法人一致，传输需加密
             
             $data['card_no']=$mybank['cart']; // 卡号，传输需加密
             
             $data['bank_mobile']=$mybank['mobile']; // 银行预留手机号，传输需加密
             
             $data['id_type']="ID_CARD"; // 证件类型 ID_CARD 身份证
             
             $data['id_no']=$mybank['idcard']; // 必须与法人证件号码一致，传输需加密
             
             $data['card_valid_date']=$mybank['useful']; //信用卡有效期 贷记卡必填，格式MMYY，传输需加密
             
             $data['cvv2']=$mybank['cw_two']; //贷记卡必填，传输需加密
             
             $data['nonce_str']=$this->createNonceStr(32); //随机数
             
             //          var_dump($data);
             
             //          return;
             
//              return $this->epsapply($data);
             $epstate = $this->epsapply($data,$user_pay_supply_id);
//             R("Payapi/Api/PaySetLog",array("./PayLog","GatePay_ceshi_Return__",'----回调返回信息参数----'.json_encode($epstate)));

             if($epstate==1)
             {
                 $returndate['code']=200;
                 $returndate['msg']="提交成功";
                 return $returndate;
                 
             }else if($epstate==2){
                 
                 return $this->qry($out_trade_no,$user_pay_supply_id);
                 
             }else {
                 
                 $where['out_trade_no']=$out_trade_no;
                 $gatepay_spay=M('gatepay_spay')->where($where)->find();
                 
                 if($gatepay_spay['state']==1)
                 {
                     $returndate['code']=200;
                     $returndate['msg']=$gatepay_spay['message'];
                     return $returndate;
                 }else if($gatepay_spay['state']==2)
                 {
                     $returndate['code']=300;
                     $returndate['msg']=$gatepay_spay['message'];
                     return $returndate;
                 }else {
                     $returndate['code']=400;
                     $returndate['msg']=$gatepay_spay['message'];
                     return $returndate;
                 }
             }
             
             
         }else{
             
//              return -1;
             
             $returndate['code']=400;
             $returndate['msg']="该通道不知道短信验证码";
             return $returndate;
         }
         
         
         
     }
     
     //快捷支付 $user_id : 用户id ；$money：交易金额 ；$mybank_id：交易卡 ；$code：验证码；支付通道 $out_trade_no：订单号
     public function addspay($user_id,$money,$mybank_id,$code,$user_pay_supply_id,$out_trade_no)
     {
         
         $returndata = $this->openuser($user_id,$user_pay_supply_id);
         if($returndata['code']!=200)
         {
//              var_dump($returndata);
             return $returndata;
         }
         
         
         $wheregatespay['y_user_pay_supply.user_pay_supply_id']=$user_pay_supply_id;
         $wheregatespay['y_user_pay_supply.status']=1;
         $user_pay_supply=M('user_pay_supply')->JOIN('LEFT JOIN y_user_js_supply on y_user_js_supply.user_js_supply_id=y_user_pay_supply.user_js_supply_id')->field('y_user_pay_supply.*,y_user_js_supply.yy_rate as txyy_rate')->where($wheregatespay)->find();
         
         if(!$user_pay_supply)
         {
             
             $returndate['code']=400;
             $returndate['msg']="通道正在维护中";
//              var_dump($returndata);
             return $returndate;
         }
         
         
         if($user_pay_supply['is_send']==1){
             
             $whereuser['user_id']=$user_id;

             # 新增多个服务号进行判断进件
             if($user_pay_supply_id == 25)
             {
                 $configS = C('SHANGFUPUBLIC');
                 $whereuser['sp_id']=$configS['SP_ID'];
             }

             $gatepay_user=M('gatepay_user')->where($whereuser)->find();
             
             $data['mch_id']=$gatepay_user['mcht_no']; //由系统分配的商户号
             
             $data['secret_key']=$gatepay_user['secret_key']; //由系统分配的商户号
             
             $data['user_id']=$user_id; //由系统分配的商户号
             
             $data['out_trade_no']=$out_trade_no; //商户订单号，确保唯一
             
             $data['password']=$code; //验证码
             
             $data['nonce_str']=$this->createNonceStr(32); //随机数
             
             $epstate= $this->epsubmit($data,$user_pay_supply_id);
             
             if($epstate==1)
             {
                 $returndate['code']=200;
                 $returndate['msg']="支付成功";
//                  var_dump($returndata);
                 return $returndate;
                 
             }else if($epstate==2){
//                  var_dump($this->qry($out_trade_no));
                 return $this->qry($out_trade_no,$user_pay_supply_id);
                 
             }else {
                 
                 $where['out_trade_no']=$out_trade_no;
                 $gatepay_spay=M('gatepay_spay')->where($where)->find();
                 
                 if($gatepay_spay['state']==1)
                 {
                     $returndate['code']=200;
                     $returndate['msg']=$gatepay_spay['message'];
//                   var_dump($returndate);
                     return $returndate;
                 }else if($gatepay_spay['state']==2)
                 {
                     $returndate['code']=300;
                     $returndate['msg']=$gatepay_spay['message'];
//                      var_dump($returndate);
                     return $returndate;
                 }else {
                     $returndate['code']=400;
                     $returndate['msg']=$gatepay_spay['message'];
//                      var_dump($returndate);
                     return $returndate;
                 }  
             }
             
            
             
             
         }
         
    
         
         $url='https://'.$_SERVER['HTTP_HOST'].'/Payapi/GatePay';
//          $url = str_replace(basename($url),"",$url);
         
         $whereuser['user_id']=$user_id;
         # 新增多个服务号进行判断进件
         if($user_pay_supply_id == 25)
         {
             $configS = C('SHANGFUPUBLIC');
             $whereuser['sp_id']=$configS['SP_ID'];
         }
         $gatepay_user=M('gatepay_user')->where($whereuser)->find();
         
//          echo M('gatepay_user')->getLastSql();
         
//          var_dump($gatepay_user);
         
         
         
         $wherebank['mybank_id']=$mybank_id;
         $mybank=M('mybank')->JOIN('LEFT JOIN y_bank on  y_bank.bank_id =y_mybank.bank_id ')->JOIN('LEFT JOIN y_gatepay_bank on  y_gatepay_bank.bank_id =y_bank.bank_id ')->field('y_mybank.*,y_bank.name as bank_name,y_gatepay_bank.bankcode as bank_code')->where($wherebank)->find();
         
         $data['user_id']=$user_id; // 
         
         $data['addtime']=time(); 
         
         $data['secret_key']=$gatepay_user['secret_key']; //由系统分配密钥
         
         $data['data_key']=$gatepay_user['data_key']; //由系统分配密钥
         
         $data['mch_id']=$gatepay_user['mcht_no']; //由系统分配的商户号
         
         $data['out_trade_no']=$out_trade_no; //商户订单号，确保唯一
         
         $data['total_fee']=$money; //订单总金额，单位为分
         
         $data['body']="在线消费"; //商品名称  按格式“应用-商品名称”赋值，如“手提包-路易威登女式手提包”，包含中文
         
         $data['notify_url']=$url."/paynotify"; //通知地址 快捷支付提交成功后回调的通知地址：务必确保外网可以访问
         
//          $data['notify_url']="http://wallet.insoonto.com/Payapi/GatePay/paynotify";
         
         $data['card_type']="CREDIT"; //卡类型 DEBIT 借记卡  CREDIT 贷记卡
         
         $data['bank_code']=$mybank['bank_code']; // 银行编码银行列表
         
         $data['bank_name']=$mybank['bank_name']; // 开户行名称 银行卡所在支行
         
         $data['card_name']=$mybank['nickname']; // 持卡人姓名 必须与商户法人一致，传输需加密
         
         $data['card_no']=$mybank['cart']; // 卡号，传输需加密
         
         $data['bank_mobile']=$mybank['mobile']; // 银行预留手机号，传输需加密
         
         $data['id_type']="ID_CARD"; // 证件类型 ID_CARD 身份证
         
         $data['id_no']=$mybank['idcard']; // 必须与法人证件号码一致，传输需加密
         
         $data['card_valid_date']=$mybank['useful']; //信用卡有效期 贷记卡必填，格式MMYY，传输需加密
         
         $data['cvv2']=$mybank['cw_two']; //贷记卡必填，传输需加密
         
         $data['nonce_str']=$this->createNonceStr(32); //随机数
         
//          var_dump($data);
         
//          return;
         
//          return $this->spay($data);
         
         $epstate = $this->spay($data,$user_pay_supply_id);
         
         if($epstate==1)
         {
             $returndate['code']=200;
             $returndate['msg']="支付成功";
//              var_dump($returndate);
             return $returndate;
             
         }else if($epstate==2){
//              var_dump($this->qry($out_trade_no));
             return $this->qry($out_trade_no,$user_pay_supply_id);
             
         }else {
             
             $where['out_trade_no']=$out_trade_no;
             $gatepay_spay=M('gatepay_spay')->where($where)->find();
             
             if($gatepay_spay['state']==1)
             {
                 $returndate['code']=200;
                 $returndate['msg']=$gatepay_spay['message'];
//                  var_dump($returndate);
                 return $returndate;
             }else if($gatepay_spay['state']==2)
             {
                 $returndate['code']=300;
                 $returndate['msg']=$gatepay_spay['message'];
//                  var_dump($returndate);
                 return $returndate;
             }else {
                 $returndate['code']=400;
                 $returndate['msg']=$gatepay_spay['message'];
//                  var_dump($returndate);
                 return $returndate;
             }
         }
         
     }
     
     //订单查询
     public function qry($out_trade_no,$tdid='')
     {
         if($tdid == 25) # 有积分有短信 - 调用
         {
             $config = C('SHANGFUPUBLIC');
         }else{
             $config = C('SHANGFU');
         }
         
         $url=$config['API_URL']."/gate/spsvr/order/qry";
         
         $where['out_trade_no']=$out_trade_no;

         $gatepay_spay=M('gatepay_spay')->where($where)->find();
         
         if(!$gatepay_spay)
         {
             $returndate['code']=400;
             $returndate['msg']="订单不存在";
             return $returndate;
         }
         
         $wheregatepay['user_id']=$gatepay_spay['user_id'];

         if($tdid == 25) # 有积分有短信 - 调用
         {
             $wheregatepay['sp_id'] = $config['SP_ID'];
         }
         $gatepay_user=M('gatepay_user')->where($wheregatepay)->find();
         
         $data['mch_id']=$gatepay_spay['mch_id']; //由系统分配的商户号
         
         $data['out_trade_no']=$out_trade_no; //商户订单号，确保唯一
         
         $data['nonce_str']=$this->createNonceStr(32); //随机数
         
         ksort($data, SORT_NATURAL); //按照字母排序
         
         $keys = array_keys($data); //获取key数组
         
         $values=array_values($data); //获取values数组
         
         $signstr="";
         
         for ($i = 0; $i < count($values); $i++) {
             $signstr=$signstr.$keys[$i]."=".$values[$i]."&";
         }
         
         $signstr=$signstr."key=".$gatepay_user['secret_key'];
         
         $sign= strtoupper(Md5($signstr)); //转大写
         
         $data['sign']=$sign; //MD5 签名结果，详见“签名说明”
         
         
         $result=cURLSSLHttp($url,$data);
         
         //          var_dump($result);
         
         $nowtime= date("Y-m-d h:i:s", time());



//         R("Payapi/Api/PaySetLog",array("./PayLog","GatePayController__Return__","测试：".json_encode($result)));


         # 写入日志
         R("Payapi/Api/PaySetLog",array("./PayLog","GatePayController_qry_Return__",$nowtime.'请求参数'.implode(',',$data).'----回调返回信息参数----'.$result));
         
         $msg=json_decode($result);
         if(empty($msg->err_msg) || $msg->err_msg=='null')
         {
             $msg->err_msg = '卡信息错误/余额不足!';
         }
         if($msg->status=='SUCCESS')
         {
             $mydata['status']=$msg->status;
             $mydata['message']="支付成功";
             $mydata['ch_trade_no']=$msg->ch_trade_no;
             $mydata['err_code']=$msg->err_code;
             $mydata['err_msg']=$msg->err_msg;
             $mydata['trade_state']=$msg->trade_state;
            
             if($msg->trade_state=='SUCCESS')
             {
                 
                 $mydata['settle_state']=$msg->settle_state;
                 $mydata['settle_state_desc']=$msg->settle_state_desc;
                 
                 
                 if($msg->settle_state=='SUCCESS')
                 {
                     $mydata['state']=1;
                 }else if($msg->settle_state=='PROCESSING')
                 {
                     $mydata['state']=2;
                 }else  if($msg->settle_state=='NOTPAY'){
                     $mydata['state']=4;
                 }else {
                     $mydata['state']=3;
                 }
             }else if($msg->trade_state=='PROCESSING')
             {
                 $mydata['state']=2;
             }else if($msg->trade_state=='NOTPAY'){
                 
                 $mydata['state']=4;
                 
             }else {
                 
                 $mydata['state']=3;
             }
             
           
             
         }else
         {
             $mydata['status']=$msg->status;
             $mydata['code']=$msg->code;
             $mydata['message']=$msg->message;
             $mydata['state']=3;
         }
         
         
         $where['out_trade_no']=$out_trade_no;
         $gatepay_spay=M('gatepay_spay')->where($where)->find();
         
         if($gatepay_spay)
         {
             if($gatepay_spay['state']!=1)
             {
                 $gatepay_spay=M('gatepay_spay')->where($where)->save($mydata);
             }
             
         }
         
         $where['out_trade_no']=$out_trade_no;
         
         $gatepay_spay=M('gatepay_spay')->where($where)->find();

         if(empty($gatepay_spay['settle_state_desc']) || $gatepay_spay['settle_state_desc']=='null')
         {
             $gatepay_spay['settle_state_desc'] = '卡信息错误/余额不足!';
         }


         if($gatepay_spay['state']==1)
         {
             $returndate['code']=200;
             $returndate['msg']=$gatepay_spay['settle_state_desc'];
             return $returndate;
         }else if($gatepay_spay['state']==2)
         {
             $returndate['code']=300;
             $returndate['msg']=$gatepay_spay['settle_state_desc'];
             return $returndate;
         }else if($gatepay_spay['state']==4){
             $returndate['code']=500;
             $returndate['msg']=$gatepay_spay['settle_state_desc'];
             return $returndate;
         }else {
             $returndate['code']=400;
             $returndate['msg']=$gatepay_spay['settle_state_desc'];
             return $returndate;
         }
         
         
     }
  
     //交易支付 快捷-无短信
     private function spay($mydata,$tdid='')
     {

         if($tdid == 25) # 有积分有短信 - 调用
         {
             $config = C('SHANGFUPUBLIC');
         }else{
             $config = C('SHANGFU');
         }
         
         $url=$config['API_URL']."/gate/epay/spay";
         
//          $data['sp_id']=$config['SP_ID']; //服务商号
         
         $data['mch_id']=$mydata['mch_id']; //由系统分配的商户号
         
         $data['out_trade_no']=$mydata['out_trade_no']; //商户订单号，确保唯一
         
         $data['total_fee']=$mydata['total_fee']*100; //订单总金额，单位为分
         
         $data['body']=$mydata['body']; //商品名称  按格式“应用-商品名称”赋值，如“手提包-路易威登女式手提包”，包含中文
         
         $data['notify_url']=$mydata['notify_url']; //通知地址 快捷支付提交成功后回调的通知地址：务必确保外网可以访问
         
         $data['card_type']=$mydata['card_type']; //卡类型 DEBIT 借记卡  CREDIT 贷记卡
         
         $data['bank_code']=$mydata['bank_code']; // 银行编码银行列表
         
         $data['bank_name']=$mydata['bank_name']; // 开户行名称 银行卡所在支行
         
         $data['card_name']=$mydata['card_name']; // 持卡人姓名 必须与商户法人一致，传输需加密
         
         $data['card_no']=$mydata['card_no']; // 卡号，传输需加密
         
         $data['bank_mobile']=$mydata['bank_mobile']; // 银行预留手机号，传输需加密
         
         $data['id_type']=$mydata['id_type']; // 证件类型 ID_CARD 身份证
         
         $data['id_no']=$mydata['id_no']; // 必须与法人证件号码一致，传输需加密
         
         $data['card_valid_date']=$mydata['card_valid_date']; //信用卡有效期 贷记卡必填，格式MMYY，传输需加密
         
         $data['cvv2']=$mydata['cvv2']; //贷记卡必填，传输需加密
         
         $data['nonce_str']=$mydata['nonce_str']; //随机字符串，不长于 32 位
         
         
         
         
         ksort($data, SORT_NATURAL); //按照字母排序
         
         $keys = array_keys($data); //获取key数组
         
         $values=array_values($data); //获取values数组
         
         $signstr="";
         
         for ($i = 0; $i < count($values); $i++) {
             $signstr=$signstr.$keys[$i]."=".$values[$i]."&";
         }
         
         $signstr=$signstr."key=".$mydata['secret_key'];
         
         $sign= strtoupper(Md5($signstr)); //转大写
         
         $data['sign']=$sign; //MD5 签名结果，详见“签名说明”
         
         $key=substr($mydata['data_key'],0,16);
         
         
         //传输需要加密的字段
         $data['card_name']=$this->myEncode($data['card_name'],$key); // 
         $data['card_no']=$this->myEncode($data['card_no'],$key); //  
         $data['bank_mobile']=$this->myEncode($data['bank_mobile'],$key); //    
         $data['id_no']=$this->myEncode($data['id_no'],$key); //    
         $data['card_valid_date']=$this->myEncode($data['card_valid_date'],$key); //
         $data['cvv2']=$this->myEncode($data['cvv2'],$key); //
         
//          var_dump($data);
         
         $result=cURLSSLHttp($url,$data);
         
//          var_dump($result);
         
         $nowtime= date("Y-m-d h:i:s", time());
         # 写入日志
         R("Payapi/Api/PaySetLog",array("./PayLog","GatePayController_spay_Return__",$nowtime.'请求参数'.implode(',',$data).'----回调返回信息参数----'.$result));
         
         $msg=json_decode($result);
         
         if($msg->status=='SUCCESS')
         {
             $mydata['status']=$msg->status;
             $mydata['message']="支付成功";
             $mydata['ch_trade_no']=$msg->ch_trade_no;
             $mydata['err_code']=$msg->err_code;
             $mydata['err_msg']=$msg->err_msg;
             $mydata['trade_state']=$msg->trade_state;
             
             if($msg->trade_state=='SUCCESS')
             {
                 $mydata['state']=1;
             }else if($msg->trade_state=='PROCESSING')
             {
                 $mydata['state']=2;
             }else {
                 $mydata['state']=3;
             }
             
         }else
         {
             $mydata['status']=$msg->status;
             $mydata['code']=$msg->code;
             $mydata['message']=$msg->message;
             $mydata['state']=3;
         }
         
         
         $where['user_id']=$mydata['user_id'];
         $where['out_trade_no']=$mydata['out_trade_no'];
         
         
         $gatepay_spay=M('gatepay_spay')->where($where)->find();
         
         if($gatepay_spay)
         {
             if($gatepay_spay['state']!=1)
             {
                 $gatepay_spay=M('gatepay_spay')->where($where)->save($mydata);
             }
             
         }else {
             $gatepay_spay=M('gatepay_spay')->add($mydata);
         }
         
         $where['user_id']=$mydata['user_id'];
         $where['out_trade_no']=$mydata['out_trade_no'];
         
         $gatepay_spay=M('gatepay_spay')->where($where)->find();
         
         return $gatepay_spay['state'];
         
         
         
         
         
     }
     
     
     //交易支付 快捷-有短信
     private function epsapply($mydata,$tdid='')
     {
         if($tdid == 25) # 有积分有短信 - 调用
         {
             $config = C('SHANGFUPUBLIC');
         }else{
             $config = C('SHANGFU');
         }

         $url=$config['API_URL']."/gate/epay/epsapply";
         
         //          $data['sp_id']=$config['SP_ID']; //服务商号
         
         $data['mch_id']=$mydata['mch_id']; //由系统分配的商户号
         
         $data['out_trade_no']=$mydata['out_trade_no']; //商户订单号，确保唯一
         
         $data['total_fee']=$mydata['total_fee']*100; //订单总金额，单位为分
         
         $data['body']=$mydata['body']; //商品名称  按格式“应用-商品名称”赋值，如“手提包-路易威登女式手提包”，包含中文
         
         $data['notify_url']=$mydata['notify_url']; //通知地址 快捷支付提交成功后回调的通知地址：务必确保外网可以访问
         
         $data['card_type']=$mydata['card_type']; //卡类型 DEBIT 借记卡  CREDIT 贷记卡
         
         $data['bank_code']=$mydata['bank_code']; // 银行编码银行列表
         
         $data['bank_name']=$mydata['bank_name']; // 开户行名称 银行卡所在支行
         
         $data['card_name']=$mydata['card_name']; // 持卡人姓名 必须与商户法人一致，传输需加密
         
         $data['card_no']=$mydata['card_no']; // 卡号，传输需加密
         
         $data['bank_mobile']=$mydata['bank_mobile']; // 银行预留手机号，传输需加密
         
         $data['id_type']=$mydata['id_type']; // 证件类型 ID_CARD 身份证
         
         $data['id_no']=$mydata['id_no']; // 必须与法人证件号码一致，传输需加密
         
         $data['card_valid_date']=$mydata['card_valid_date']; //信用卡有效期 贷记卡必填，格式MMYY，传输需加密
         
         $data['cvv2']=$mydata['cvv2']; //贷记卡必填，传输需加密
         
         $data['nonce_str']=$mydata['nonce_str']; //随机字符串，不长于 32 位
         
         
         
         
         ksort($data, SORT_NATURAL); //按照字母排序
         
         $keys = array_keys($data); //获取key数组
         
         //          var_dump($keys);
         
         $values=array_values($data); //获取values数组
         
         //          var_dump($values);
         
         $signstr="";
         
         for ($i = 0; $i < count($values); $i++) {
             $signstr=$signstr.$keys[$i]."=".$values[$i]."&";
         }
         
         $signstr=$signstr."key=".$mydata['secret_key'];
         
         $sign= strtoupper(Md5($signstr)); //转大写
         
         $data['sign']=$sign; //MD5 签名结果，详见“签名说明”
         
         $key=substr($mydata['data_key'],0,16);
         
         
         //传输需要加密的字段
         $data['card_name']=$this->myEncode($data['card_name'],$key); //
         $data['card_no']=$this->myEncode($data['card_no'],$key); //
         $data['bank_mobile']=$this->myEncode($data['bank_mobile'],$key); //
         $data['id_no']=$this->myEncode($data['id_no'],$key); //
         $data['card_valid_date']=$this->myEncode($data['card_valid_date'],$key); //
         $data['cvv2']=$this->myEncode($data['cvv2'],$key); //
         
//                 var_dump($data);
         
         $result=cURLSSLHttp($url,$data);
         
//                var_dump($result);
         
         $nowtime= date("Y-m-d h:i:s", time());
         # 写入日志
         R("Payapi/Api/PaySetLog",array("./PayLog","GatePayController_epsapply_Return__",$nowtime.'请求参数'.implode(',',$data).'----回调返回信息参数----'.$result));
         
         $msg=json_decode($result);
         
         if($msg->status=='SUCCESS')
         {
             $mydata['status']=$msg->status;
             $mydata['message']="发送成功";
             $mydata['ch_trade_no']=$msg->ch_trade_no;
             $mydata['err_code']=$msg->err_code;
             $mydata['err_msg']=$msg->err_msg;
             $mydata['trade_state']=$msg->trade_state;
             
             if($msg->trade_state=='SUCCESS')
             {
                 $mydata['state']=1;
             }else if($msg->trade_state=='PROCESSING')
             {
                 $mydata['state']=2;
                 $mydata['message']='发送中';
             }else {
                 $mydata['state']=3;
                 $mydata['message']=$msg->err_msg;
             }
             
         }else
         {
             $mydata['status']=$msg->status;
             $mydata['code']=$msg->code;
             $mydata['message']=$msg->message;
             $mydata['state']=3;
         }
         
         
         $where['user_id']=$mydata['user_id'];

         $where['out_trade_no']=$mydata['out_trade_no'];
         
         
         $gatepay_spay=M('gatepay_spay')->where($where)->find();
         
         if($gatepay_spay)
         {
             if($gatepay_spay['state']!=1)
             {
                 $gatepay_spay=M('gatepay_spay')->where($where)->save($mydata);
             }
             
         }else {
             $gatepay_spay=M('gatepay_spay')->add($mydata);
         }
         
         $where['user_id']=$mydata['user_id'];
         $where['out_trade_no']=$mydata['out_trade_no'];
         
         $gatepay_spay=M('gatepay_spay')->where($where)->find();
         
         return $gatepay_spay['state'];
         
         
         
         
         
     }
     
     //用户输入银行验证码后，发送支付提交请求，完成快捷支付  ---有短信接口
     private function epsubmit($mydata,$tdid='')
     {
         if($tdid == 25) # 有积分有短信 - 调用
         {
             $config = C('SHANGFUPUBLIC');
         }else{
             $config = C('SHANGFU');
         }


         $url=$config['API_URL']."/gate/epay/epsubmit";
         
         $data['mch_id']=$mydata['mch_id']; //由系统分配的商户号
         
         $data['out_trade_no']=$mydata['out_trade_no']; //商户订单号，确保唯一
         
         $data['password']=$mydata['password']; //银行发送的动态口令
         
         $data['nonce_str']=$mydata['nonce_str']; //随机字符串，不长于 32 位
         
         ksort($data, SORT_NATURAL); //按照字母排序
         
         $keys = array_keys($data); //获取key数组
         
         //          var_dump($keys);
         
         $values=array_values($data); //获取values数组
         
         //          var_dump($values);
         
         $signstr="";
         
         for ($i = 0; $i < count($values); $i++) {
             $signstr=$signstr.$keys[$i]."=".$values[$i]."&";
         }
         
         $signstr=$signstr."key=".$mydata['secret_key'];
         
         $sign= strtoupper(Md5($signstr)); //转大写
         
         $data['sign']=$sign; //MD5 签名结果，详见“签名说明”
         
         //          var_dump($data);
         
         $result=cURLSSLHttp($url,$data);
         
         //          var_dump($result);
         
         $nowtime= date("Y-m-d h:i:s", time());
         # 写入日志
         R("Payapi/Api/PaySetLog",array("./PayLog","GatePayController_epsubmit_Return__",$nowtime.'请求参数'.implode(',',$data).'----回调返回信息参数----'.$result));
         
         $msg=json_decode($result);
         
         if($msg->status=='SUCCESS')
         {
             $mydata['status']=$msg->status;
             $mydata['message']="支付成功";
             $mydata['ch_trade_no']=$msg->ch_trade_no;
             $mydata['err_code']=$msg->err_code;
             $mydata['err_msg']=$msg->err_msg;
             $mydata['trade_state']=$msg->trade_state;
             
             if($msg->trade_state=='SUCCESS')
             {
                 $mydata['state']=1;
             }else if($msg->trade_state=='PROCESSING')
             {
                 $mydata['state']=2;
             }else {
                 $mydata['state']=3;
             }
             
         }else
         {
             $mydata['status']=$msg->status;
             $mydata['code']=$msg->code;
             $mydata['message']=$msg->message;
             $mydata['state']=3;
         }
         
         
         $where['user_id']=$mydata['user_id'];
         $where['out_trade_no']=$mydata['out_trade_no'];
         $gatepay_spay=M('gatepay_spay')->where($where)->find();
         
         
         if($gatepay_spay)
         {
             if($gatepay_spay['state']!=1)
             {
                 $gatepay_spay=M('gatepay_spay')->where($where)->save($mydata);
             }
             
         }else {
             $gatepay_spay=M('gatepay_spay')->add($mydata);
         }
         
         $where['user_id']=$mydata['user_id'];
         $where['out_trade_no']=$mydata['out_trade_no'];
         
         $gatepay_spay=M('gatepay_spay')->where($where)->find();
         
         return $gatepay_spay['state'];
         
         
     }
     
     
     
     /**
      * aes加密
      */
     private  function myEncode($string,$key)
     {
//          $key = "A9B7BA70783B617E";
//          echo  $key;
         $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
         $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
         
         $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
         $padding = $size - (strlen($string) % $size);
         $phone_padding = $string . str_repeat(chr($padding), $padding);
         
         mcrypt_generic_init($td, $key, $iv);
         $cyper_text = mcrypt_generic($td, $phone_padding);
         $result = strtoupper(bin2hex($cyper_text));
         mcrypt_generic_deinit($td);
         mcrypt_module_close($td);
         
         return $result;
     }
     
     /**
      * aes解密
      */
     private function myDecode($string,$key)
     {
//          $key = "A9B7BA70783B617E";
         
         $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
         $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
         
         mcrypt_generic_init($td, $key, $iv);
         $text = mdecrypt_generic($td, hex2bin($string));
         
         $pad = ord($text{strlen($text) - 1});
         $string = substr($text, 0, -1 * $pad);
         
         mcrypt_generic_deinit($td);
         mcrypt_module_close($td);
         
         return $string;
     }
     
}

    
?>