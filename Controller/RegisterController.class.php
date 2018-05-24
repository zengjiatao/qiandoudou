<?php
# 银钱包对接接口 - 微信api接口

namespace Payapi\Controller;

class RegisterController extends BaseController{

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

     public function register(){
         $phone=$_REQUEST['phone'];
         $code =$_REQUEST['code'];
         $pwd = $_REQUEST['pawd'];
         //查询是否手机号码存在
         $Pinfo =M('user')->where(array('phone'=>$phone))->find();
         if ($Pinfo) {
         	echo json_encode(array('code'=>4201,'msg'=>'该手机号已注册'));die;
         }
         $Cinfo =M('mobleyzm')->where(array('phone'=>$phone,'type'=>4))->find();
         if ($code != $Cinfo['code']) {
         	echo json_encode(array('code'=>4201,'msg'=>'验证码不正确'));die;
         }else{
         	
         }
     }

    #输入邀请码绑定上级
    public function BindUp(){
        $uid = $_REQUEST['uid'];
        $yqm = $_REQUEST['yqm'];
//        $paremcs=array(
//
//            'signType'=>$_REQUEST['signType'],
//
//            'timestamp' => $_REQUEST['timestamp'],
//
//            'dataType' => $_REQUEST['dataType'],
//
//            'inputCharset' => $_REQUEST['inputCharset'],
//
//            'version' => $_REQUEST['version'],
//
//        );
//        $sign = $_REQUEST['sign'];
//
//        if (!$sign){
//
//            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;
//
//        }
//
//        $array = array(
//
//            'uid'=>$uid,
//            'yqm'=>$yqm
//
//        );
//
//
//
//        $paremcs=array_merge($paremcs,$array);
//
//
//
//        $msg = R('Func/Func/getKey',array($paremcs));//返回加密
//
//        //        dump($msg);die;
//
//        if ($this->sign !== $msg){
//
//            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;
//
//        }
//
//        R('Func/Func/getTwoSign',array($sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
//        $_SESSION['last_sign'] = $sign;//把sign存入session 为作判断

        if (!$uid){
            echo json_encode(array('code'=>'1020','msg'=>'请传输用户Id'));die;
        }
        if (!$yqm){
            echo json_encode(array('code'=>'1020','msg'=>'请输入邀请码'));die;
        }
        $info = M('user')->where(array('user_id'=>trim($uid)))->find();
        $yqmInfo = M('user')->where(array('tg_code'=>trim($yqm)))->find();
//        echo json_encode(array('code'=>'1234','msg'=>json_encode($yqmInfo)));die;
        if (!$yqmInfo){
            echo json_encode(array('code'=>'1024','msg'=>'输入的邀请码无效'));die;
        }
        if ($info['pid']){
            echo json_encode(array('code'=>'1025','msg'=>'您已存在上级!不需要输入邀请码!'));die;
        }
        if ($yqmInfo['utype'] == '20'){
            $userInfo['pid']=trim($yqmInfo['user_id']);
            $userInfo['tk_pid']=trim($yqmInfo['user_id']);
            $userInfo['institution_id']=trim($yqmInfo['institution_id']);
        }else{
            $userInfo['pid']=trim($yqmInfo['user_id']);
            $userInfo['tk_pid']=trim($yqmInfo['tk_pid']);
            $userInfo['institution_id']=trim($yqmInfo['institution_id']);
        }

//        dump($yqm);die;

        if ($yqm == $info['tg_code']){
            echo json_encode(array('code'=>'1024','msg'=>'不能输入自己的邀请码'));die;
        }
        $res = M('user')->where(array('user_id'=>trim($uid)))->data($userInfo)->save();

        if ($res !== false){
            $one = M('user')->where(array('user_id'=>trim($uid)))->find();
            #记录 绑定上级的参数
            R("Payapi/Api/PaySetLog",array("./PayLog","Wx_BindUp_","--没绑定之前的参数-".json_encode($info)."--绑定的邀请码--".$yqm."--绑定后---".json_encode($one)."- 时间:".date('Y-m-d H:i:s')."------\r\n"));
            echo json_encode(array('code'=>'1028','msg'=>'绑定成功'));die;
        }else{
            echo json_encode(array('code'=>'1030','msg'=>'系统繁忙'));die;
        }

     }


     #还没缴费 修改等级接口(Register / saveLevel)  返回 刚刚注册
    public function saveLevel(){
        $uid= $_REQUEST['user_id'];
        $level = $_REQUEST['level'];
        if (!$this->sign){
            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;
        }
        $array = array(
            'user_id'=>$uid,
            'level'=>$level
        );
        $this->parem=array_merge($this->parem,$array);
        $msg = R('Func/Func/getKey',array($this->parem));//返回加密
        //        dump($msg);die;
        if ($this->sign !== $msg){
            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;
        }
        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
        if (!$uid){
            echo json_encode(array('code'=>'1024','msg'=>'请传输用户Id'));die;
        }
        if (!$level){
            echo json_encode(array('code'=>'1024','msg'=>'请传输用户等级'));die;
        }
        if ($level == '1'){
            $utype = '1';
        }else{
            $utype = '20';
        }
            $saveInfo['level'] = $level;
            $saveInfo['utype'] = $utype;
           $res =  M('user')->where(array('user_id'=>$uid))->save($saveInfo);
            if ($res !== false){
                    echo json_encode(array('code'=>'1028','msg'=>'等级更改成功!'));die;
            }else{
                echo json_encode(array('code'=>'1025','msg'=>'系统繁忙,稍后再试'));die;
            }
    }

    #绑定微信号 接口
    public function addAndSaveWechatId(){

        $parem=array(

            'signType'=>$_REQUEST['signType'],

            'timestamp' => $_REQUEST['timestamp'],

            'dataType' => $_REQUEST['dataType'],

            'inputCharset' => $_REQUEST['inputCharset'],

            'version' => $_REQUEST['version'],

        );

        $sign = $_REQUEST['sign'];
        $user_id = $_REQUEST['user_id']; //用户Id
        $wechatId = trim($_REQUEST['wechat_id']);//微信号
        if (empty($user_id)){
            echo json_encode(array('code'=>'1011','msg'=>'请传输用户Id'));die;
        }
        if (empty($wechatId)){
            echo json_encode(array('code'=>'1011','msg'=>'请传输微信号'));die;
        }
        $one = M('user')->where(array('user_id'=>$user_id))->find();
        if (!$one){
            echo json_encode(array('code'=>'1013','msg'=>'用户不存在'));die;
        }
        if (!$sign){
            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;
        }
        $array = array(
            'user_id'=>$user_id,
        );
        $parem=array_merge($parem,$array);
        /**/
//        $arg='';$url='';
//
//        foreach($parem as $key=>$val){
//
//            //$arg.=$key."=".urlencode($val)."&amp;";
//
//            $arg.=$key."=".urlencode($val)."&amp;";
//
//        }
//
//        $url.= $arg;
//
//        $str=rtrim($url, "&amp;");
//
//        $str=str_replace("&amp;","&",$str);
//
//        $sign=md5(strtoupper(md5($str)).'YINXUNTONG');
//        echo json_encode(array('data'=>$str,'sign'=>$sign));die;
        /**/
        $msg = R('Func/Func/getKey',array($parem));//返回加密
        //        dump($msg);die;
        if ($sign !== $msg){
            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;
        }
        R('Func/Func/getTwoSign',array($sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $sign;//把sign存入session 为作判断

            $res =M('user')->where(array('user_id'=>$user_id))->data(array('wechat_id'=>$wechatId))->save();
            if ($res !== false){
                echo json_encode(array('code'=>'1000','msg'=>'微信号修改成功'));die;
            }else{
                echo json_encode(array('code'=>'1005','msg'=>'系统繁忙,微信号修改失败'));die;
            }

    }

}