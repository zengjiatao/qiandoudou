<?php

namespace Payapi\Controller;

class UserController extends BaseController {

	public $uid;

    public $parem;

    public $sign;

    public $AppKey;

    public $AppSecret;

    public $AppCode;

    public $host;

    public $path;



    //修改资料

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

        $this->AppKey='24690055';

        $this->AppSecret='92aab17a6f66a39ed6dc0b383041dcd9';

        $this->AppCode='c9e2e959b905414a8f161489cfc7b7ac';

        $this->host="http://api43.market.alicloudapi.com";

        $this->path="/api/c43";

        $this->sign = $_REQUEST['sign'];

    }

    public function  ziliao(){

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $nick_name = $_REQUEST['nick_name'];

        $array=array(

            'uid'=>$this->uid,

        );

        $this->parem = array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        dump($msg);die;

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $sc = M('User'); 

        if($_REQUEST){

                $wh['nick_name'] = $_REQUEST['nick_name'];

                $res = $sc ->where(array("user_id"=>$this->uid))->data($wh)->save();

                if($res){

                    echo json_encode(array('code'=>2003,'msg'=>'昵称修改成功')); exit();

                }else{

                    echo json_encode(array('code'=>5001,'msg'=>'昵称修改失败')); exit();

                }

        }

    }



    //会员修改头像中心

    public function touxiang(){

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array=array(

            'uid'=>$this->uid,

        );

        $this->parem =array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        dump($msg);die;

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

    	if(IS_POST){   

    	    $sc = M('User'); 		

    		if($_FILES){

			    $filed = $_FILES;

			    // var_dump($filed);exit;

	    		if($_FILES['head_img']['error'] !== 4){

	              $upload = new \Think\Upload();// 实例化上传类    

	              $upload->maxSize   =  6*1024*1024 ;// 设置附件上传大小

	              $upload->exts      =  array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型    

	              $upload->savePath  =  ''; // 设置附件上传目录   

	              $upload->subName = array('date','Y/m/d'); // 采用date函数生成命名规则 传入Y-m-d参数 

	              // 上传单个文件     

	              $info   =   $upload->uploadOne($_FILES['head_img']);

	              // var_dump($info);

	              if(!$info) {// 上传错误提示错误信息        

//	                   $this->error($upload->getError());

                     echo json_encode(array('code'=>2105,'msg'=>$upload->getError()));die;

	              }else{



	                   $filed['head_img'] = $info['savepath'].$info['savename'];

	                   // var_dump($data);exit;

	                   $image = new \Think\Image();

                      if ($image->open('./Uploads/' . $filed['head_img'])) {

                          $image->thumb(100, 100)->save('./Uploads/' . $filed['head_img'] . '_thumb.jpg');

                      };

                      if($filed['head_img']){
                          // 查找出图片并删除图片
                         $headImg= M('user')->field('head_img,user_id')->where(array("user_id"=>$this->uid))->find();
                              $path = './Uploads/'.$headImg['head_img'];
                              @unlink($path);
                      }



	    		       $has = $sc ->where(array("user_id"=>$this->uid))->save($filed);

	                   // var_dump($str);exit;

			           if($has){

			           	    echo json_encode(array('code'=>2004,'msg'=>'头像修改成功','img'=>enThumb("./Uploads",$has['head_img']))); exit();

			           }else{

			           		echo json_encode(array('code'=>5002,'msg'=>'头像修改失败')); exit();

			           }

	              }

	          }else{

//                    $this->error('请上传封面图片');

	              echo json_encode(array('code'=>2104,'msg'=>'请上传封面图片'));die;

	          }

	    	}

    	}

    	

    }

   //会员中心

    public function getuser(){

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $nick_name = $_REQUEST['nick_name'];

        $array=array(

            'uid'=>$this->uid,

        );

        $this->parem = array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        dump($msg);die;

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        //我的分销参数

        $retailData = R('Func/User/orderRetailscs',array($this->uid));   //分销人数
//        dump($this->uid);die;
        $sum = count($retailData); //总数

        $list = M('coupon_data')->where(array("user_id"=>$this->uid))->select();

        $num = count($list);

        $one = M('user')->field('nick_name,head_img,phone')->where(array("user_id"=>$this->uid))->find();

        $info = M('myrealname')->field('user_id,status')->where(array("user_id"=>$this->uid))->find();

        //获得银行卡张数

        //array('uid'=>$this->uid,'status'=>1)

        $bankData = M('mybank')->where(" user_id = '{$this->uid}' and status != 3 and jq_status = 3")->select();

        $bankNumber = count($bankData);
        //金额

        $moneys = M('user')->field('wallet_money,money')->where(array('user_id'=>$this->uid))->find();

        $moneys['money'] = $moneys['wallet_money'];

	    $sc = M('User');

    	$one = $sc ->field('user_id,nick_name,head_img,phone,pid,level,utype,is_allow,is_upgrade')->where(array("user_id"=>$this->uid))->find();

    	$one['head_img'] = enThumb('./Uploads/',$one['head_img']);

    	$business = M('business')->field('business_id,user_id,status')->where(array("user_id"=>$this->uid))->find();

        $parentInfo=M('user')->field('user_id,utype,level')->where(array('user_id'=>trim($one['pid'])))->find();

        //提示升级
        if ($one['is_upgrade'] == 0){
            $parentInfo=M('user')->field('user_id,utype,level')->where(array('user_id'=>trim($one['pid'])))->find();
//                        echo json_encode(array('code'=>4399,'data'=>$parentInfo));die;
            if ($parentInfo['level'] == 9 ){
                $keSj=1;
            }elseif($parentInfo['level'] == 6){
                $keSj=1;
            }else{
                $keSj=0;
            }
        }else{
            $keSj=0;
        }

        if (!$parentInfo){
           $jigouInfo= M('institution')->field('institution_id')->where(array('institution_id'=>trim($one['pid'])))->find();
           if ($jigouInfo){

               if ($one['level'] == 9){
                   $is_allow=0;//不能升级
               }else{
//                   if ($one['is_allow'] == 2){
//                       $is_allow=0;
//                   }else{
                   if ($one['level'] == 4){  //见习
                       $is_allow=3;  //可以升级合伙人
//                       $wh['distribution_level_id']=9;
                       $shengji = M('distribution_level')->field("distribution_level_id,levelname,ordermoney")->where('distribution_level_id = 6 or distribution_level_id = 9')->select();
                   }elseif($one['level'] == 6){  //大咖
                       $is_allow=3;  //可以升级合伙人
                       $wh['distribution_level_id']=9;
                       $shengji = M('distribution_level')->field("distribution_level_id,levelname,ordermoney")->where($wh)->select();
                   }
//                   }
               }
           }else{
               echo json_encode(array('code'=>4512,'msg'=>'用户不存在或上级不存在'));die;
           }

        }else{

            if ($parentInfo['level'] == 9 || $parentInfo['level'] == 6){  //上级是合伙人 或  大咖

                   if ($parentInfo['level'] == 9){  //上级是合伙人    查出大咖 和见习
                       if ($one['level'] == 6 || $one['level'] == 9){
                           $is_allow=0;  //不能升级
                       }else{

                           $is_allow=1;  //可以升级
                       }
                       if($one['level'] == 1 || !$one['level']){  //兜客
                           $shengji = M('distribution_level')->field("distribution_level_id,levelname,ordermoney")->where(" distribution_level_id = 6 or distribution_level_id = 4")->select();
                       }elseif($one['level'] == 6){  //大咖
//                           $where['distribution_level_id']=9;
//                           $shengji = M('distribution_level')->field("distribution_level_id,levelname,ordermoney")->where($where)->select();
                       }elseif($one['level'] == 4){
                           $shengji = M('distribution_level')->field("distribution_level_id,levelname,ordermoney")->where(" distribution_level_id = 6")->select();
                       }

                   }elseif($parentInfo['level'] == 6){  //上级是大咖
                       if($one['level'] == 4 || $one['level'] == 6 || $one['level'] == 9){
                           $is_allow=0;//不能升级
                       }else{
                           $is_allow=1;  //可以升级
                       }

                       $where['distribution_level_id']=4;
                       $shengji = M('distribution_level')->field("distribution_level_id,levelname,ordermoney")->where($where)->select();
                   }
//                   $shengji = M('distribution_level')->field("distribution_level_id,levelname,ordermoney")->where(" distribution_level_id = 9")->select();
               }else{
                $is_allow=0;//不能升级
            }
//            else{
//            }
        }
//        }

//                        echo json_encode(array('code'=>4399,'data'=>$parentInfo));die;
//        if ($parentInfo['level'] == 9 || $parentInfo['level'] == 6){
//            echo json_encode(array('code'=>4399,'msg'=>'可以升级!','user_id'=>$one['user_id']));die;
//        }

        $data = array(

                'one' =>$one,  //昵称头像手机号码

                'business'=>$business,

                'info'=>$info, //实名认证状态

                'sum' =>$sum, //我的分销数量

                'num' =>$num, //优惠券数量

                'bankNumber' => $bankNumber,  //银行卡张数

                'moneys' => $moneys,//金额

                'is_allow'=>$is_allow,

                'data'=>$shengji,

                'keSj'=>$keSj,
                'level'=>$one['level'],
                'utype'=>$one['utype'],
            );

        echo json_encode(

            array(

                'code'=>200,

                'msg'=>'会员信息',

                'data'=>$data,

                'touxiang'=>enThumb("./Public/Payapi/images/",'touxiang.png')

            )

        ); exit();

    }

//会员中心 升级 测试接口
    public function getusercs(){

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $nick_name = $_REQUEST['nick_name'];

        $array=array(

            'uid'=>$this->uid,

        );

        $this->parem = array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        dump($msg);die;

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        //我的分销参数

        $retailData = R('Func/User/orderRetailscs',array($this->uid));   //分销人数
//        dump($this->uid);die;
        $sum = count($retailData); //总数

        $list = M('coupon_data')->where(array("user_id"=>$this->uid))->select();

        $num = count($list);

        $one = M('user')->field('nick_name,head_img,phone')->where(array("user_id"=>$this->uid))->find();

        $info = M('myrealname')->field('user_id,status')->where(array("user_id"=>$this->uid))->find();

        //获得银行卡张数

        //array('uid'=>$this->uid,'status'=>1)

        $bankData = M('mybank')->where(" user_id = '{$this->uid}' and status != 3 and jq_status = 3")->select();

        $bankNumber = count($bankData);
        //金额

        $moneys = M('user')->field('wallet_money,money')->where(array('user_id'=>$this->uid))->find();

        $moneys['money'] = $moneys['wallet_money'];

        $sc = M('User');

        $one = $sc ->field('user_id,nick_name,head_img,phone,pid,level,utype,is_allow,is_upgrade')->where(array("user_id"=>$this->uid))->find();

        $one['head_img'] = enThumb('./Uploads/',$one['head_img']);

        $business = M('business')->field('business_id,user_id,status')->where(array("user_id"=>$this->uid))->find();

        $parentInfo=M('user')->field('user_id,utype,level')->where(array('user_id'=>trim($one['pid'])))->find();

        //提示升级
        if ($one['is_upgrade'] == 0){
            $parentInfo=M('user')->field('user_id,utype,level')->where(array('user_id'=>trim($one['pid'])))->find();
//                        echo json_encode(array('code'=>4399,'data'=>$parentInfo));die;
            if ($parentInfo['level'] == 9 ){
                $keSj=1;
            }elseif($parentInfo['level'] == 6){
                $keSj=1;
            }else{
                $keSj=0;
            }
        }else{
            $keSj=0;
        }

        if (!$parentInfo){
            $jigouInfo= M('institution')->field('institution_id')->where(array('institution_id'=>trim($one['pid'])))->find();
            if ($jigouInfo){

                if ($one['level'] == 9){
                    $is_allow=0;//不能升级
                }else{
//                   if ($one['is_allow'] == 2){
//                       $is_allow=0;
//                   }else{
                    $is_allow=3;  //可以升级合伙人
                    $wh['distribution_level_id']=9;
                    $shengji = M('distribution_level')->field("distribution_level_id,levelname,ordermoney")->where($wh)->select();
//                   }
                }
            }else{
                echo json_encode(array('code'=>4512,'msg'=>'用户不存在或上级不存在'));die;
            }

        }else{

            if ($parentInfo['level'] == 9 || $parentInfo['level'] == 6){  //上级是合伙人 或  大咖

                if ($parentInfo['level'] == 9){  //上级是合伙人    查出大咖 和见习
                    if ($one['level'] == 6 || $one['level'] == 9){
                        $is_allow=0;  //不能升级
                    }else{

                        $is_allow=1;  //可以升级
                    }
                    if($one['level'] == 1 || !$one['level']){  //兜客
                        $shengji = M('distribution_level')->field("distribution_level_id,levelname,ordermoney")->where(" distribution_level_id = 6 or distribution_level_id = 4")->select();
                    }elseif($one['level'] == 6){  //大咖
//                           $where['distribution_level_id']=9;
//                           $shengji = M('distribution_level')->field("distribution_level_id,levelname,ordermoney")->where($where)->select();
                    }elseif($one['level'] == 4){
                        $shengji = M('distribution_level')->field("distribution_level_id,levelname,ordermoney")->where(" distribution_level_id = 6")->select();
                    }

                }elseif($parentInfo['level'] == 6){  //上级是大咖
                    if($one['level'] == 4 || $one['level'] == 6 || $one['level'] == 9){
                        $is_allow=0;//不能升级
                    }else{
                        $is_allow=1;  //可以升级
                    }

                    $where['distribution_level_id']=4;
                    $shengji = M('distribution_level')->field("distribution_level_id,levelname,ordermoney")->where($where)->select();
                }
//                   $shengji = M('distribution_level')->field("distribution_level_id,levelname,ordermoney")->where(" distribution_level_id = 9")->select();
            }else{
                $is_allow=0;//不能升级
            }
//            else{
//            }
        }
//        }

//                        echo json_encode(array('code'=>4399,'data'=>$parentInfo));die;
//        if ($parentInfo['level'] == 9 || $parentInfo['level'] == 6){
//            echo json_encode(array('code'=>4399,'msg'=>'可以升级!','user_id'=>$one['user_id']));die;
//        }

        $data = array(

            'one' =>$one,  //昵称头像手机号码

            'business'=>$business,

            'info'=>$info, //实名认证状态

            'sum' =>$sum, //我的分销数量

            'num' =>$num, //优惠券数量

            'bankNumber' => $bankNumber,  //银行卡张数

            'moneys' => $moneys,//金额

            'is_allow'=>$is_allow,

            'data'=>$shengji,

            'keSj'=>$keSj,
            'level'=>$one['level'],
            'utype'=>$one['utype'],
        );

        echo json_encode(

            array(

                'code'=>200,

                'msg'=>'会员信息',

                'data'=>$data,

                'touxiang'=>enThumb("./Public/Payapi/images/",'touxiang.png')

            )

        ); exit();

    }

    //会员中心

    public function getuserinfo(){

//        if (!$this->sign){

//            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

//        }

        $nick_name = $_REQUEST['nick_name'];

//        $array=array(

//            'uid'=>$this->uid,

//        );

//        $this->parem = array_merge($this->parem,$array);

//        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

////        dump($msg);die;

//        if ($this->sign !== $msg){

//            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

//        }

//        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

//        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        //我的分销参数

        $retailData = R('Func/User/orderRetailscs',array($this->uid));

//        dump($retailData);die;



        $sum = count($retailData);

        $list = M('coupon_data')->where(array("user_id"=>$this->uid))->select();

        $num = count($list);

        $one = M('user')->field('nick_name,head_img,phone')->where(array("user_id"=>$this->uid))->find();

        $info = M('myrealname')->field('user_id,status')->where(array("user_id"=>$this->uid))->find();



        //获得银行卡张数

        //array('uid'=>$this->uid,'status'=>1)

        $bankData = M('mybank')->where(" user_id = '{$this->uid}' and status != 3 and jq_status = 3")->select();

        $bankNumber = count($bankData);

        //金额

        $moneys = M('user')->field('wallet_money,money')->where(array('user_id'=>$this->uid))->find();

        $moneys['money'] = $moneys['wallet_money'];

        $sc = M('User');

        $one = $sc ->field('user_id,nick_name,head_img,phone')->where(array("user_id"=>$this->uid)) ->find();

        $one['head_img'] = enThumb('./Uploads/',$one['head_img']);

        $business = M('business')->field('user_id,status')->where(array("user_id"=>$this->uid))->find();

        $data = array(

            'one' =>$one,  //昵称头像手机号码

            'business'=>$business,

            'info'=>$info, //实名认证状态

            'sum' =>$sum, //我的分销数量

            'num' =>$num, //优惠券数量

            'bankNumber' => $bankNumber,  //银行卡张数

            'moneys' => $moneys //金额

        );

        echo json_encode(

            array(

                'code'=>200,

                'msg'=>'会员信息',

                'data'=>$data,

                'touxiang'=>enThumb("./Public/Payapi/images/",'touxiang.png')

            )

        ); exit();

    }

    

      //跟换手机号码 (暂停用)

    public function phone2(){

    	if($_REQUEST){

            $data['phone'] = $_REQUEST['phone'];

            $data['code'] = $_REQUEST['code'];

            $tt['phone'] = $data['phone'];

            // $tt['c_t'] = time();

            $sc = M('Mobleyzm');

            $one = $sc -> where(array('phone'=>$tt['phone']))->find();

            if($one['code'] == $data['code']){

                 $sc->where(array('phone'=>$tt['phone']))->save($tt);

                $res = M('User')->where(array('user_id'=>$this->uid))->save($tt);

                // var_dump(M('User')->_sql());exit;

                if($res){

                    echo json_encode(array('code'=>1001,'msg'=>'更换手机号码成功')); exit();

                }else{

                    echo json_encode(array('code'=>1000,'msg'=>'更换失败')); exit();

                }

            }else{

                 echo json_encode(array('code'=>1002,'msg'=>'验证码不正确')); exit();

            }

        

            

        }

    	

    }

    //验证码验证(暂停用)

    public function code(){



    	if($_REQUEST){

    		// $data = I('post.');

    		$tt['phone'] = $_REQUEST['phone'];

    		$tt['code'] = rand(100000,999999);

    		$tt['c_t'] = time();

    		// var_dump($tt);

    		$sc = M('Mobleyzm');

    		$wh['phone'] = $tt['phone'];

    		$one = $sc->where($wh)->find();

    		// var_dump($one);

            $d = time()+60;

            if($one){

            # 存在，判断该手机号最新时间是否>60

	            if($one['c_t'] >= $d){

                    echo json_encode(array('code'=>2005,'msg'=>'请60s后再发送验证码')); exit();

	            	// echo '<script>alert("请60s后再发送验证码")</script>';

	            	// $this->error('请60s后再发送验证码');

	            }else{

	            	$w = $sc->where(array('phone'=>$tt['phone']))->save($tt);

	            	if($w){

	                	echo json_encode(array('code'=>2006,'msg'=>'验证码发送成功')); exit();

		            }else{

		                echo json_encode(array('code'=>5003,'msg'=>'验证码发送失败')); exit();

		            }

	            }

		    }

    	}

    }

  

    //更换密码的验证码发送

    public function pwdcode(){

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $phone=$_REQUEST['phone'];

        $array = array(

            'uid'=>$this->uid,

            'phone'=>$phone,

            'type'=>$_REQUEST['type'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



    	if($_REQUEST){

            $aa['phone'] = $_REQUEST['phone'];

            // $tt['type'] = $data['type'];

            $aa['type'] = $_REQUEST['type'];

            $aa['code'] = rand(100000,999999);

            $tt['t'] = time();

            $aa['c_t'] = time();

            $sc = M('Mobleyzm');

            $wh['phone'] = $aa['phone'];

            $wh['type'] = $aa['type'];

            $d = time();
            $one = $sc ->where($wh)->find();


            if($d <= $one['c_t']+60 ){

                echo json_encode(array('code'=>2016,'msg'=>'请60s后再发送验证码'));die;

                // $this->error('请60s后再发送验证码');

            }

//            $contont = "您的验证码为:";

//            $msg = iconv('gb2312','utf-8', $contont).$aa['code'];

            $msg ="验证码：".$aa['code']."(为了您的账户安全，请勿告知他人，请在页面尽快完成验证)客服4008-272-278";

            $infooo = R("Func/Func/send_mobile",array($aa['phone'],$msg));

//            $info = R('Func/Func/sendMessage',array($aa['phone'],$msg));


//             var_dump($msg);exit;


            if($one){

                # 存在，判断该手机号最新时间是否>60

                if($d <= $one['c_t']+60 ){

                    echo json_encode(array('code'=>2016,'msg'=>'请60s后再发送验证码'));die;

                    // $this->error('请60s后再发送验证码');

                }else{

                    // $w1 = M('User') ->where('user_id = '.$this->uid)->find();

                    // $w = $sc->where('user_id = '.$w1['id'])->save($tt);

                    $res = M('Mobleyzm')->where(array('phone'=>$aa['phone'],'type'=>$aa['type']))->save($aa);

                    if($res !== false){

                        echo json_encode(array('code'=>2006,'msg'=>'发送成功','data'=>$aa['code'])); exit();

                    }else{

                        echo json_encode(array('code'=>5003,'msg'=>'发送失败')); exit();

                    }

                }

            }else{

                $res = M('Mobleyzm')->add($aa);

                if($res != false){

                    echo json_encode(array('code'=>2006,'msg'=>'发送成功','data'=>$aa['code'])); exit();

                }else{

                    echo json_encode(array('code'=>5003,'msg'=>'发送失败')); exit();

                }

            }

    	}

    }

    //验证码验证

    public function codeCheck(){

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }



        $array = array(

            'uid'=>$this->uid,

            'code'=>$_REQUEST['code'],

            'phone'=>$_REQUEST['phone'],

            'type'=>$_REQUEST['type'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

    	if($_REQUEST){

            $tt['code'] = $_REQUEST['code'];

            $tt['phone'] = $_REQUEST['phone'];

            $tt['type'] = $_REQUEST['type'];

            $sc = M('Mobleyzm');

            $one = $sc -> where(array('type'=>$tt['type'],'phone'=>$tt['phone']))->find();

            if($one['code'] == $tt['code']){

                echo json_encode(array('code'=>2007,'msg'=>'验证码正确')); exit();

            }else{

                echo json_encode(array('code'=>5004,'msg'=>'验证码不正确')); exit();

            }

    	}

    }

   

    //设置密码

    public function pay_pwd(){

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }



        $array = array(

            'uid'=>$this->uid,

            'pwd'=>$_REQUEST['pwd'],

            'type'=>$_REQUEST['type'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

    	if($_REQUEST){

            $tt['pwd'] = md5(md5($_REQUEST['pwd']));

            $tt['type'] = intval($_REQUEST['type']);

            $sc = M('Password');

//            $w = M('User')->where('user_id = '.$this->uid)->find();

            $d = $sc ->where(array('user_id'=>$this->uid,'type'=>$tt['type']))->find();

            // var_dump($tt['pwd']);

            // var_dump($d['pa']);exit;

            if($tt['pwd'] == $d['pwd']){

                echo json_encode(array('code'=>6888,'msg'=>'设置的密码与原密码重复,重新设置')); exit();

            }

            if($tt['type'] == $d['type']){

                $tt['update']=time();

                $res = $sc ->where(array('user_id'=>$this->uid,'type'=>$tt['type']))->save($tt);

            }else{

                $tt['user_id'] = $this->uid;

                $tt['t'] = time();

                $res = $sc ->add($tt);

            }

            // var_dump($sc->_sql());exit;

            if($res){

                echo json_encode(array('code'=>2008,'msg'=>'密码设置成功')); exit();

            }else{

                echo json_encode(array('code'=>5005,'msg'=>'密码设置失败')); exit();

            }

    	    ////////////////////////////////////////////////////////////

    	}

    	

    }



    //验证密码是否正确

//     public function pwdCheck(){

//         if($_REQUEST){

//             $pwd = md5(md5($_REQUEST['pwd']));

//             // var_dump($pwd);

//             $sc = M('password');

//             $one = $sc -> where('uid = '.$this->uid)->find();

//             // var_dump($one['code']);

//             // var_dump($code);exit;

//             if($one['pwd'] == $pwd){

//                 echo json_encode(array('code'=>2009,'msg'=>'密码一致，设置成功','data'=>$pwd)); exit();

//             }else{

//                 echo json_encode(array('code'=>5006,'msg'=>'密码输入错误，请再次输入')); exit();

//             }

//         }

//     }

    //商家认证

    public function business(){

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$this->uid,

            'nick_name'=>urldecode($_REQUEST['nick_name']),

            'phone'=>$_REQUEST['phone'],

            'address'=>urldecode($_REQUEST['address']),

            'shopsNumber'=>$_REQUEST['shopsNumber'],

            'name'=>urldecode($_REQUEST['name']),

            'industry_id'=>$_REQUEST['industry_id'],

            'tel'=>$_REQUEST['tel'],

        );

        $this->parem=array_merge($this->parem,$array);



        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        echo json_encode(array('sign'=>$msg));die;

//        dump($msg);die;

//        if ($this->sign !== $msg){

//            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

//        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        if(IS_POST){

            $data = $_POST;

            if($_FILES['zhizhaoPic']['error'] == 0){

                if($_FILES['thumb']['error'] == 0){

                    if($_FILES['goodsPic']['error'] == 0){

                        $upload = new \Think\Upload();// 实例化上传类    

                        $upload->maxSize   =  10*1024*1024 ;// 设置附件上传大小

                        $upload->exts      =  array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型    

                        $upload->savePath  =  ''; // 设置附件上传目录   

                        $upload->subName = array('date','Y/m/d'); // 采用date函数生成命名规则 传入Y-m-d参数 

                        // 上传单个文件     

                        $info   =   $upload->upload(); 

                        // var_dump($info);exit;

                        if(!$info) {// 上传错误提示错误信息        

//                             $this->error($upload->getError());

                             echo json_encode(array('code'=>2015,'msg'=>$upload->getError()));die;

                        }else{

                            $tt['zhizhaoPic'] = $info['zhizhaoPic']['savepath'].$info['zhizhaoPic']['savename'];

                            $tt['thumb'] = $info['thumb']['savepath'].$info['thumb']['savename'];

                            $tt['goodsPic'] = $info['goodsPic']['savepath'].$info['goodsPic']['savename'];



                            $image = new \Think\Image();

                            if ($image->open('./Uploads/' . $tt['zhizhaoPic'])) {

                                $image->thumb(100, 100)->save('./Uploads/' . $tt['zhizhaoPic'] . '_thumb.jpg');

                            };



                            if ($image->open('./Uploads/' . $tt['thumb']))

                            {

                                $image->thumb(100, 100)->save('./Uploads/' . $tt['thumb'] . '_thumb.jpg');

                            };

                            if($image->open('./Uploads/'.$tt['goodsPic']))

                            {

                                $image->thumb(100, 100)->save('./Uploads/' . $tt['goodsPic'] . '_thumb.jpg');

                            };

                            $tt['user_id'] = $this->uid;

                            $tt['address'] = urldecode($_REQUEST['address']);

                            $tt['nick_name']=urldecode($_REQUEST['nick_name']);

                            $tt['phone']=$_REQUEST['phone'];

                            $tt['shopsNumber']=$_REQUEST['shopsNumber'];

                            $tt['name']=urldecode($_REQUEST['name']);

                            $tt['industry_id']=$_REQUEST['industry_id'];

                            $tt['status']=1;

                            $tt['tel']=$_REQUEST['tel'];

                            $tt['t']= time();

                            $key = C('QQMAPAPI');

                            $url = "http://apis.map.qq.com/ws/geocoder/v1/?address={$data['address']}&key={$key}";

                            $infoo = cURLGetHttp($url);

                            $code = json_decode($infoo,true);

                            $tt['lng'] = $code['result']['location']['lng'];

                            $tt['lat'] = $code['result']['location']['lat'];

//                            $city = M('city') ->where(array('name'=>$data['city_name']))->find();

//                            $data['city_id'] = $city['cityids'];

//                            echo json_encode(array('data'=>$tt));die;

                            $op['op_status'] = 3;

                            $op['user_id'] = $this->uid;

                            $op['time'] = time();

                            $op['type'] = '10';

                            $op['send_mail'] = 2;

                            $op['send_phone'] = 1;

                           $us = M('user')->where(array('user_id'=>$this->uid))->find();

                           if($us){

                               $us['last_logintime']=time();

                               $us['email']=$_REQUEST['email'];

                               M('user')->where(array('user_id'=>$this->uid))->save($us);

                            }

                           /**/

                            if($op['send_mail'] == 2)

                            {

                                # 发送邮件

                                $to = $_REQUEST['email'];

                                // $to = "1149934712@qq.com";

                                $title = "尊敬的{$tt['nick_name']}：您的商户认证正在审核中";

                                $content = "

                            <style>

                            /*css 初始化 */

                            html, body, ul, li, ol, dl, dd, dt, p, h1, h2, h3, h4, h5, h6, form, fieldset, legend, img { margin:0; padding:0; }  /*让这些的标签内外边距都是0*/

                            fieldset, img {border:none;}

                            ul, ol,li{list-style:none;}

                            img{vertical-align:middle;}

                            input {padding-top:0; padding-bottom:0; font-family: '微软雅黑';color:#666;}

                            select,input{vertical-align:middle;font-family:'微软雅黑';}

                            select,input,textarea{font-size:12px; margin:0;}

                            textarea{resize:none;}

                            table{border-collapse:collapse;}

                            body{background:#fff;font:12px '微软雅黑','SimSun','宋体';/* 设置全局的文字 颜色 字号  字体  */ /*height:10000px;*/}

                            em,i{font-style:normal;}

                            a{color:#666;text-decoration:none;}

                            a:hover{color:#fff;}

                            html,body{min-width:1300px;}

                            /* 常用代码  */

                            .clearfix::after { content:''; display:block; height:0; visibility:hidden; clear:both; }

                            .clearfix { *zoom:1; }

                            

                            .content{width: 720px;margin:0 auto;}

                            .nav{background:#339fed;}

                            .nav nav{height:50px;}

                            .qdd_logo{width: 150px;height:100%;float:left;position:relative;}

                            .qdd_logo img{width: 75px;height: 20px;position:absolute;left:15px;top:50%;margin-top:-10px;}

                            .qdd_p{float:right;font-size:12px;color:#fff;line-height: 50px;padding-right:10px;}

                            .qdd_p span{font-size:16px;font-weight:700;margin-left:10px;float:right;}

                            .main{padding-top:44px;background:#fff;}

                            .h2_til{font-size:24px;color:#339fed;text-align:center;line-height: 24px;margin-bottom:15px;font-weight:700;}

                            .h3_til{font-size:18px;color:#339fed;text-align:center;line-height: 18px;margin-bottom:50px;}

                            .email_cont{padding:0 15px;}

                            .email_cont_til{font-size:16px;color:#333;line-height: 16px;margin-bottom:35px;}

                            .email_cont_p{font-size:14px;color:#333;line-height: 14px;margin-bottom:15px;}

                            .email_file li{font-size:14px;line-height: 14px;margin-bottom:15px;}

                            .email_file li em{color:#339fed;}

                            .email_file li i{color:#333;}

                            .email_file li a{color:#339fed;}

                            .email_file li a i{color:#339fed;}

                            .not_revert{height:62px;line-height: 62px;border-top:1px dashed #e5e5e5;box-sizing:border-box;margin-top:30px;}

                            .not_revert_p{padding:0 15px;font-size:12px;color:#999;box-sizing:border-box;}

                            footer{background:#f3f3f3;height:110px;}

                            .ft_cont{padding:5px 15px;box-sizing:border-box;}

                            .ft_cont h3{font-size:14px;color:#6f6e6f;line-height: 40px;}

                            .ft_cont_p{font-size:14px;color:#999;}

                            </style>

                            

                                <div class='content' style='border:1px solid #ccc;'>

                                <section class='conten nav content'>

                                    <nav class='content'>

                                        <section class='qdd_logo'><img src='https://wallet.insoonto.com/Uploads/qdd_logo.png' alt=''></section>

                                        <section class='qdd_p'>如有任何问题，请联系客服<span>4008-272-278</span></section>

                                    </nav>

                                </section>

                                <section class='main content'>

                                    <section class='main_cont content'>

                                        <section class='email_cont'>

                                            <h3 class='email_cont_til'>尊敬的".$tt['phone']."：</h3>

                                            <section class='email_cont_p'>您的商户认证正在审核中，请耐心等待，谢谢！</section>

                                            <section style='font-size:14px;line-height:14px;color:#333;margin-bottom:12px;margin-top:50px;'>钱兜兜</section>

                                            <section style='font-size:14px;line-height:14px;color:#333;'>".date('Y-m-d H:i:s',time())."</section>

                                        </section>

                                    </section>

                                </section>

                                <section class='not_revert content'>

                                    <section class='content not_revert_p'>此为系统邮件请勿回复</section>

                                </section>

                                </div>

                            ";

                                $name = $title;

                                think_send_mail($to,$name,$title,$content);

                            }



                            if($op['send_phone'] == 1 )

                            {

                                # 发送短信

                                //"尊敬的XXXXXXX，您的商户认证正在审核中，请耐心等待！客服4008-272-278";

                                $msg = "尊敬的".$tt['phone']."，您的商户认证正在审核中，请耐心等待！客服4008-272-278";

                                // $msg = iconv("GB2312", "UTF-8", "$msg");

                                R("Func/Func/send_mobile",array($tt['phone'],$msg));

                            }

                            /**/

                            $buType = M('user_operation')->where(array('user_id'=>$this->uid,'type'=>'10'))->find();

                            if($buType){

                                M('user_operation')->where(array('user_operation_id'=>$buType['user_operation_id']))->save($op);

                            }else{

                                M('user_operation')->add($op);

                            }

                            $busInfo = M('business')->where(array('user_id'=>$this->uid))->find();

                            if($busInfo){

//                                $busInfo['t']=time();

                                    $res = M('business')->where(array('user_id'=>$this->uid))->save($tt);

//                                    M('user_operation')->where(array('user_id'=>$this->uid))->save($op);

                                $w = array(

                                        'zhizhaoPic'=>$tt['zhizhaoPic'],

                                        'thumb'=>$tt['thumb'],

                                        'goodsPic'=>$tt['goodsPic']

                                    );

                                    if($res){

                                        echo json_encode(array('code'=>2010,'msg'=>'商家资料提交成功','img'=>enThumb("./Uploads",$w))); exit();

                                    }else{

                                        echo json_encode(array('code'=>5007,'msg'=>'商家资料提交失败')); exit();

                                    }

                            }else{

                                    $res = M('business')->add($tt);

                                    $w = array(

                                        'zhizhaoPic'=>$tt['zhizhaoPic'],

                                        'thumb'=>$tt['thumb'],

                                        'goodsPic'=>$tt['goodsPic']

                                    );

                                    if($res){

                                        echo json_encode(array('code'=>2010,'msg'=>'商家资料提交成功','img'=>enThumb("./Uploads",$w))); exit();

                                    }else{

                                        echo json_encode(array('code'=>5007,'msg'=>'商家资料提交失败')); exit();

                                    }

                                }

                            // var_dump($data);exit;

                        }

                    }else{   

                        echo json_encode(array('code'=>5008,'msg'=>'请提交相关资料')); exit();

                    } 

                }else{   

                    echo json_encode(array('code'=>5008,'msg'=>'请提交相关资料')); exit();

                } 

            }else{   

                 echo json_encode(array('code'=>5008,'msg'=>'请提交相关资料')); exit();

            }    

        }



        

    }

    //行业类别接口

    public function industry(){

        $res = M('industry')->where(array('type'=>1,'status'=>1))->select();

       echo json_encode(array('code'=>4403,'msg'=>'获取成功','data'=>$res));die;

    }



    public function saveBase64()

    {

        if($_POST)

        {

            $base64_image_content = $_POST['va'];

            if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){

                $type = $result[2];

                $time = time();

                $new_file = "/Uploads/bankUp/".$time.".".$type;



                if (!file_exists("./Uploads/bankUp")){

                    mkdir ("./Uploads/bankUp",0777,true);

                }

                base64_dstr_replace($result[1], '', $base64_image_content);





                if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))){

                    echo json_encode(array('error'=>'123'));exit;

                }else{

                    echo json_encode(array('error'=>4566));exit;

                }

            }

        }

    }



    //银行卡验证码认证

    public function yh_code(){

        if($_REQUEST){

            $mobile = $_REQUEST['mobile'];

            $info = M('mobleyzm')->where(array('phone'=>$mobile,'type'=>3))->find();

            $code = rand(100000,999999);

            $msg =  "验证码：".$code."(为了您的账户安全，请勿告知他人，请在页面尽快完成验证)客服4008-272-278";

            R("Func/Func/send_mobile",array($mobile,$msg));

            //验证是否存在验证码,存在则覆盖

            if($info){

                $data['mobleyzm_id'] = $info['mobleyzm_id'];

                $data['phone']=$mobile;

                $data['code']=$code;

                $data['c_t']=time();

                $data['type']=3;

                $res = M('mobleyzm')->save($data);

                if ($res) {

                   echo json_encode(array('code'=>2011,'msg'=>'验证码发送成功!','code'=>$data['code']));die;  

                }else{

                   echo json_encode(array('code'=>5009,'msg'=>'验证码发送失败!'));die;  

                }

            }else{

                $data['phone']=$mobile;

                $data['code']=$code;

                $data['c_t']=time();

                $data['type']=3;

                $res = M('mobleyzm')->add($data);

                //var_dump(M('mobleyzm')->_sql());exit;

                if ($res) {

                   echo json_encode(array('code'=>2011,'msg'=>'验证码发送成功!','code'=>$data['code']));die;  

                }else{

                   echo json_encode(array('code'=>5009,'msg'=>'验证码发送失败!'));die;  

                }

            }

            

        }

    }





    //银行卡列表

    public function myBankList(){

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }



        if(intval($_REQUEST['tytd']))

        {

            $array = array(

                'uid'=>$this->uid,

                'tytd' => intval($_REQUEST['tytd'])

            );

        }else{

            $array = array(

                'uid'=>$this->uid

            );

        }



        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        dump($msg);die;

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        $info =R('Func/Func/getMyBank',array($this->uid,1,2));



        if(intval($_REQUEST['tytd']))

        {

            $xyInfo = R('Func/Func/getMyBank', array($this->uid, 2, 2,intval($_REQUEST['tytd'])));

        }else{

            $xyInfo = R('Func/Func/getMyBank', array($this->uid, 2, 2));

        }

        $oneCard = M('mybank')->where(array('user_id'=>$this->uid,'type'=>1,'is_normal'=>1))->find();

        if (!$oneCard){

            $one=1;

        }else{

            $one=2;

        }



//        dump($xyInfo);die;

        echo json_encode(array(

            'code' => '2020',

            'msg' => '我的银行卡列表',

            'info' => $info,

            'xyInfo' => $xyInfo,

            'one'=>$one

        ));exit();

        

    }



    public function coupons(){

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }



        $array = array(

            'uid'=>$this->uid,

        );



        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        dump($msg);die;

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

         $list=array();

        $list = M('coupon_data')->where(array('user_id'=>$this->uid))->select();

        foreach ($list as $k => $v){

            $bid = $v['bid'];

            $bname = M('business')->where(array("business_id"=>$bid))->getField('name');

            $list[$k]['bname'] = $bname;

            $couponid = $v['coupon_id'];

            $info = M('coupon')->where('coupon_id = '.$couponid)->find();

            $list[$k]['money'] = $info['money'];

            $list[$k]['full_price'] = $info['full_price'];

            $list[$k]['start_time'] = $info['start_time'];

            $list[$k]['end_time'] = $info['end_time'];

        }

        echo json_encode(array(

            'code' => '2022',

            'msg' => '我的优惠券列表',

            'data'=>$list,

        ));exit();

    }







///////////////////////////////////////////2017年10月30号曾加涛///////////////////////////////////////

   //我的分销首页

    public function distribution()

    {

        $uid = $_REQUEST['uid'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }



        $array = array(

            'uid'=>$uid,

        );



        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断


        $p=$_REQUEST['page'];

        if(!$p){

            $p=0;

        }

        //兜客佣金记录(所有)

        $retailData = R('Func/User/indexcs',array($uid,$p));
        $count = R('Func/User/indexCount',array($uid));
        $sum=ceil($count/10);
//            dump($retailData);die;

        //结算记录

        $info = R('Func/User/retailEnd',array($uid));

        //成功结算总额

        $sumClose = R('Func/User/successClose',array($uid));

        //可收益

        $sumJsMoney=R('Func/User/sumJsMoney',array($uid));

        $canMoney=sprintf("%.4f",0);     //可结算的金额

        $successSum=sprintf("%.4f",0);   //成功已结算的金额

        $array =sprintf("%.4f",0);      //总收益

        $nowMoney=sprintf("%.4f",0);     //今日收益

        //分销总额

        foreach ($sumJsMoney as $k=>$v){

            $array+=$v['money'];    //分润金额

            if(date('Y-m-d',time()) == date('Y-m-d',$v['t'])){

                $nowMoney +=$v['money'];

            }

        }

        //成功结算的金额

        foreach ($sumClose as $k=>$v){

            $successSum+=$v['js_money'];

        }

//        $successSum = sprintf("%.4f",$successSum);//成功结算金额

        $money = $array;//总收益

//        $nowMoney = sprintf("%.4f",$nowMoney); // 今日分销额

        $canMoney = $money - $successSum;



        // 可结算金额 - 重新

        $canMoney = M('user')->where(array('user_id'=>$this->uid))->getField('money');



        $canMoney=sprintf("%.2f",$canMoney);

//        $canMoney = sprintf("%.4f",$canMoney); //可结算金额

        ///////////////////////////////////////////////////////////

    //   $retailData = R('Func/User/index',array($uid));

    //        //结算记录

    //        $info = R('Func/User/retailEnd',array($uid));

    //        //结算总额

    //        $sumClose = R('Func/User/successClose',array($uid));

    //        $canMoney=0;//可结算的金额

    //        $successSum=0;//成功已结算的金额

    //        $array = 0; //总分销额

    //        $nowMoney=0; //今日分销额

    //        //分销总额

    //        foreach ($retailData as $k=>$v){

    //          $array+=$v['price'];

    //          if(date('Y-m-d') == date('Y-m-d',$v['t'])){

    //              $nowMoney +=$v['price'];

    //          }

    //        }

    //        foreach ($sumClose as $k=>$v){

    //         $successSum+=$v['price'];

    //        }

    //

    //        $successSum = sprintf("%.2f",$successSum);//成功结算金额

    //        $money = sprintf("%.2f",$array);//总分销额

    //        $nowMoney = sprintf("%.2f",$nowMoney); // 今日分销额

    //        $canMoney = $money - $successSum;

    //     $canMoney = sprintf("%.2f",$canMoney); //可结算金额

//        echo $canMoney;die;

        $data = R('Func/User/orderRetailsValue',array($uid));

        $level1 = array();

        $level2 = array();

        $level3 = array();

        $levelEnd=array();

        $level=array();

        foreach ($data as $k => $v)

        {

            if($v['level'] == 0)

            {

                $level1[] = $v;

            }

            if($v['level'] == 1)

            {

                $level2[] = $v;

            }

            if($v['level'] == 2)

            {

                $level3[] = $v;

            }

            if($v['level'] >= 3)
            {

                $levelEnd[]=$v;

            }

        }

        $level['D1']=count($level1);

        $level['D2']=count($level2);

        $level['D3']=count($level3);

        $level['D4']=count($levelEnd);

//        $level=array('0'=>$level1,'1'=>$level2,'2'=>$level3);

        $data = array(

        	 'canMoney' => $canMoney,//可结算金额

        	 'successSum' => $successSum,//成功结算金额

        	 'money' => $money, //总收益

        	 'nowMoney' => $nowMoney, //今日收益

        	 'info' => $info, //兜客结算记录

        	 'retailData' => array('data'=>$retailData,'pagesum'=>$count,'pagesize'=>$sum), //所有兜客佣金记录

             'level' =>$level,

        	);



        // print_r($data);

        echo json_encode(array(

            'code'=>'2013',

            'msg'=>'分销首页',

            'data'=>$data,   

        )); exit();

    }

    //我的分销首页 分页测试

    public function distributioncs(){

        $uid = $_REQUEST['uid'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }



        $array = array(

            'uid'=>$uid,

        );



        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        $p=$_REQUEST['page'];

        if(!$p){

            $p=0;

        }

        //兜客佣金记录(所有)

        $retailData = R('Func/User/indexcs',array($uid,$p));

//            dump($retailData);die;

        //结算记录

        $info = R('Func/User/retailEnd',array($uid));

        //成功结算总额

        $sumClose = R('Func/User/successClose',array($uid));

        //可收益

        $sumJsMoney=R('Func/User/sumJsMoney',array($uid));

        $canMoney=sprintf("%.4f",0);     //可结算的金额

        $successSum=sprintf("%.4f",0);   //成功已结算的金额

        $array =sprintf("%.4f",0);      //总收益

        $nowMoney=sprintf("%.4f",0);     //今日收益

        //分销总额

        foreach ($sumJsMoney as $k=>$v){

            $array+=$v['money'];    //分润金额

            if(date('Y-m-d',time()) == date('Y-m-d',$v['t'])){

                $nowMoney +=$v['money'];

            }

        }

        //成功结算的金额

        foreach ($sumClose as $k=>$v){

            $successSum+=$v['js_money'];

        }

//        $successSum = sprintf("%.4f",$successSum);//成功结算金额

        $money = $array;//总收益

//        $nowMoney = sprintf("%.4f",$nowMoney); // 今日分销额

        $canMoney = $money - $successSum;



        // 可结算金额 - 重新

        $canMoney = M('user')->where(array('user_id'=>$this->uid))->getField('money');



        $canMoney=sprintf("%.2f",$canMoney);

        $data = R('Func/User/orderRetails',array($uid));

        $level1 = array();

        $level2 = array();

        $level3 = array();

        $levelEnd=array();

        $level=array();

        foreach ($data as $k => $v)

        {

            if($v['level'] == 0)

            {

                $level1[] = $v;

            }

            if($v['level'] == 1)

            {

                $level2[] = $v;

            }

            if($v['level'] == 2)

            {

                $level3[] = $v;

            }

            if($v['level'] >= 3){

                $levelEnd[]=$v;

            }

        }

        $level['D1']=count($level1);

        $level['D2']=count($level2);

        $level['D3']=count($level3);

        $level['D4']=count($levelEnd);

//        $level=array('0'=>$level1,'1'=>$level2,'2'=>$level3);

        $data = array(

            'canMoney' => $canMoney,//可结算金额

            'successSum' => $successSum,//成功结算金额

            'money' => $money, //总收益

            'nowMoney' => $nowMoney, //今日收益

            'info' => $info, //兜客结算记录

            'retailData' => $retailData, //所有兜客佣金记录

            'level' =>$level,

        );



        // print_r($data);

        echo json_encode(array(

            'code'=>'2013',

            'msg'=>'分销首页',

            'data'=>$data,

        )); exit();

    }

    //推客分销首页 (推客)

    public function tuikeFenXiaocs(){

        $uid = $this->uid;

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }



        $array = array(

            'uid'=>$uid,

        );



        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断







        //推客佣金记录(所有)

        $retailData = R('Func/User/index',array($uid));

//            dump($retailData);die;

        //推客结算记录

        $info = R('Func/User/retailEnd',array($uid));

        //成功结算总额

        $sumClose = R('Func/User/successClose',array($uid));

        //推客的分销利润

        $fenXiaoLi = R('Func/User/tuikeLi',array($this->uid));

//            dump($fenXiaoLi);die;

        //可收益

        $sumJsMoney=R('Func/User/sumJsMoney',array($uid));

        $canMoney=sprintf("%.4f",0);//可结算的金额

        $successSum=sprintf("%.4f",0);//成功已结算的金额

        $array = sprintf("%.4f",0); //总收益

        $nowMoney=sprintf("%.4f",0); //今日收益

        //分销总额

        foreach ($sumJsMoney as $k=>$v){

            $array+=$v['money'];

            if(date('Y-m-d',time()) == date('Y-m-d',$v['t'])){

                $nowMoney +=$v['money'];

            }

        }

        //成功结算的金额

        foreach ($sumClose as $k=>$v){

            $successSum+=$v['js_money'];

        }



//        $successSum = sprintf("%.2f",$successSum);//成功结算金额

        $money = $array;//总收益

//        $nowMoney = sprintf("%.2f",$nowMoney); // 今日分销额

        $canMoney = $money - $successSum;

        $canMoney=sprintf("%.2f",$canMoney);

//        $canMoney = sprintf("%.2f",$canMoney); //可结算金额

        $data = R('Func/User/orderRetailsValue',array($uid));

        $level1 = array();

        $level2 = array();

        $level3 = array();

        $levelEnd=array();

        $level=array();

        foreach ($data as $k => $v)

        {

            if($v['level'] == 0)

            {

                $level1[] = $v;

            }

            if($v['level'] == 1)

            {

                $level2[] = $v;

            }

            if($v['level'] == 2)

            {

                $level3[] = $v;

            }

            if($v['level'] >= 3){

                $levelEnd[]=$v;

            }

        }

        $level['D1']=count($level1);

        $level['D2']=count($level2);

        $level['D3']=count($level3);

        $level['D4']=count($levelEnd);

//        $levelInfo=array('0'=>$level1,'1'=>$level2,'2'=>$level3,'3'=>$level4);

        $data = array(

            'canMoney' => $canMoney,//可结算金额

            'successSum' => $successSum,//成功结算金额

            'money' => $money, //总收益

            'nowMoney' => $nowMoney, //今日收益

            'info' => $info, //兜客结算记录

            'retailData' => $retailData, //所有兜客佣金记录

            'fenXiaoLi'=>$fenXiaoLi, //推客的 分销利润记录

            'level'=>$level,

        );

        // print_r($data);

        echo json_encode(array(

            'code'=>'2013',

            'msg'=>'分销首页',

            'data'=>$data,

        )); exit();

    }

    //推客分销首页 分页测试

    public function tuikeFenXiao(){

        $uid = $this->uid;

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }



        $array = array(

            'uid'=>$uid,

        );



        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        $p1=$_REQUEST['page1'];

        if (!$p1){  //分润分页参数

            $p1=0;

        }
        $p2=$_REQUEST['page2'];
        if (!$p2){  //推客分润分页参数

            $p2=0;

        }
        $p3=$_REQUEST['page3'];
//        echo json_encode(array('page3'=>$p3));die;
        if (!$p3){   //结算分页参数
            $p3=0;
        }else{
            $p3=$_REQUEST['page3'];
        }

        //推客佣金记录(所有)

        $retailData = R('Func/User/indexcs',array($uid,$p1)); //分页
        $count = R('Func/User/indexCount',array($uid)); //统计
        $sum=ceil($count/2);

//            dump($retailData);die;

        //推客结算记录  (分页)

        $info = R('Func/User/retailEnd',array($uid,$p3)); //分页
        $jsCount = R('Func/User/retailEndCount',array($uid)); //统计一次
        $jsSum=ceil($jsCount/2);
        //总页数
        //成功结算总额

        $sumClose = R('Func/User/successClose',array($uid)); //查一次

        //推客的分销利润

        $fenXiaoLi = R('Func/User/tuikeLics',array($this->uid,$p2)); //分页
        $tkCoun = R('Func/User/tuikeCount',array($uid)); //查一次
        $tkSum=ceil($tkCoun/2);
//            dump($fenXiaoLi);die;

        //可收益

        $sumJsMoney=R('Func/User/sumJsMoney',array($uid)); //查一次

        $canMoney=sprintf("%.4f",0);//可结算的金额

        $successSum=sprintf("%.4f",0);//成功已结算的金额

        $array = sprintf("%.4f",0); //总收益

        $nowMoney=sprintf("%.4f",0); //今日收益

        //分销总额

        foreach ($sumJsMoney as $k=>$v){

            $array+=$v['money'];

            if(date('Y-m-d',time()) == date('Y-m-d',$v['t'])){

                $nowMoney +=$v['money'];

            }

        }

        //成功结算的金额

        foreach ($sumClose as $k=>$v){

            $successSum+=$v['js_money'];

        }



//        $successSum = sprintf("%.2f",$successSum);//成功结算金额

        $money = $array;//总收益

        $money = sprintf("%.4f",$array);

//        $nowMoney = sprintf("%.2f",$nowMoney); // 今日分销额

        $canMoney = $money - $successSum;

        $canMoney=sprintf("%.2f",$canMoney);

//        $canMoney = sprintf("%.2f",$canMoney); //可结算金额

        $data = R('Func/User/orderRetailsValue',array($uid));  //优化

        $level1 = array();

        $level2 = array();

        $level3 = array();

        $levelEnd=array();

        $level=array();

        foreach ($data as $k => $v)

        {

            if($v['level'] == 0)

            {

                $level1[] = $v;

            }

            if($v['level'] == 1)

            {

                $level2[] = $v;

            }

            if($v['level'] == 2)

            {

                $level3[] = $v;

            }

            if($v['level'] >= 3){

                $levelEnd[]=$v;

            }

        }

        $level['D1']=count($level1);

        $level['D2']=count($level2);

        $level['D3']=count($level3);

        $level['D4']=count($levelEnd);

//        $levelInfo=array('0'=>$level1,'1'=>$level2,'2'=>$level3,'3'=>$level4);

        $data = array(

            'canMoney' => $canMoney,//可结算金额

            'successSum' => $successSum,//成功结算金额

            'money' => $money, //总收益

            'nowMoney' => $nowMoney, //今日收益

            'info' => array('data'=>$info,'pagesum'=>$jsCount,'pagesize'=>$jsSum), //结算记录

            'retailData' => array('data'=>$retailData,'pagesum'=>$count,'pagesize'=>$sum), //所有兜客佣金记录

            'fenXiaoLi'=>array('data'=>$fenXiaoLi,'pagesum'=>$tkCoun,'pagesize'=>$tkSum), //推客的 分销利润记录

            'level'=>$level,

        );

        // print_r($data);

        echo json_encode(array(

            'code'=>'2013',

            'msg'=>'分销首页',

            'data'=>$data,

        )); exit();

    }

    //分销结算

    public function retailClose()

    {

        $uid = trim($_REQUEST['uid']);

        $mybank_id = trim($_REQUEST['mybank_id']);

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }



        if($mybank_id)

        {

            $array = array(

                'uid'=>$uid,

                'mybank_id' => $mybank_id # 可不传则默认

            );

        }else{

            $array = array(

                'uid'=>$uid

            );

        }



        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        //可结算金额

        $moneyData = R('Func/User/index',array($uid)); //总分销额

        $retailData = R('Func/User/successClose',array($uid));//已经结算的金额

        $money = 0;

        $sumClose = 0;

        foreach ($moneyData as $k=>$v){

         $money+=$v['price'];

        }

        foreach ($retailData as $k=>$v){

         $sumClose+=$v['price'];

        }

//      $money = sprintf("%.2f",$money);//总分销额

//        $sumClose = sprintf("%.2f",$sumClose);//成功结算功能

        $canMoney = $money - $sumClose;

//        $canMoney = sprintf("%.2f",$canMoney);



        $usertd = 1; # 目前固定合利宝通道ID，收益通道

        $sx_money = M("user_settlement")->where(array('user_settlement_id'=>$usertd))->getField('sx_money');

        $bank_id = M("user_settlement")->where(array('user_settlement_id'=>$usertd))->getField('bank_id');

        $mybankwhere['user_id'] = $uid;

        $mybankwhere['type'] = 1;



        $mybankwhere['bank_id'] = array('in',$bank_id); # 支持银行卡



        if(!$mybank_id)

        {

            $mybankwhere['is_normal'] = 1;

            // 获取默认的结算卡

            $mybank = M("mybank")->where($mybankwhere)->find();

        }else{

            // 获取结算卡

            $mybankwhere['mybank_id'] = $mybank_id;

            $mybank = M("mybank")->where($mybankwhere)->find();

        }





        # 保存日志

//        R("Payapi/Api/PaySetLog",array("./PayLog", "Api_Userceshi__", json_encode($mybankwhere)." ----- ".json_encode($mybank) . "\r\n"));



        $mybank['icon'] = M("bank")->where(array('bank_id'=>$mybank['bank_id']))->getField('icon');

        $mybank['bank_name'] = M("bank")->where(array('bank_id'=>$mybank['bank_id']))->getField('name');



        // 可结算金额

        $canMoney = M('user')->where(array('user_id'=>$this->uid))->getField('money');

        $canMoney=sprintf("%.2f",$canMoney);



        echo json_encode(array(

            'code'=>'2014',

            'msg'=>'分销结算',

            'data'=>$canMoney,

            'mybank'=> $mybank, // 我的银行卡信息

            'sx_money' => $sx_money

        )); exit();

    }
    //测试
    //分销上下级分页

    public function retailValue()

    {

        $uid = $_REQUEST['uid'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }



        $array = array(

            'uid'=>$_REQUEST['uid'],

            'type'=>$_REQUEST['type'],

        );



        $this->parem=array_merge($this->parem,$array);

//        echo json_encode(array('data'=>$this->parem));die;

//        $arg='';$url='';

//

//        foreach($this->parem as $key=>$val){

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

//        $one =strtoupper(md5($str));

//        $two= $sign=md5(strtoupper(md5($str)).'YINXUNTONG');

//        echo json_encode(array('data'=>$str,'one'=>$one,'two'=>$two));die;



        $msg = R('Func/Func/getKey',array($this->parem));//返回加密



        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        $data = R('Func/User/orderRetailsValue',array($uid));

        $level1 = array();

        $level2 = array();

        $level3 = array();

        $levelEnd=array();

        $num=20;

        $p1=$_REQUEST['page1'];

        $p2=$_REQUEST['page2'];

        $p3=$_REQUEST['page3'];

        $p4=$_REQUEST['page4'];

        if(!$p1){

            $p1=0;

        }

        if(!$p2){

            $p2=0;

        }

        if(!$p3){

            $p3=0;

        }

        if (!$p4){

            $p4=0;

        }

        $type =$_REQUEST['type'];

//        $startnum=($p-1)*$num;

//        $endnum=$num*$num;



        //一级

        if ($type == 'D1'){

            foreach ($data as $k => $v)

            {

                if($v['level'] == 0)

                {

                    $level1['son'][] = $v;

                    $level1['sum']=count($level1['son']);

                }

            }

                $level1['son']=array_splice($level1['son'],$p1*$num,$num);

            echo json_encode(array(

                'code'=>'2015',

                'msg'=>'分销结构详情',

                'data'=>$level1,

            )); exit();

        }

        //二级

        if ($type == 'D2'){

            foreach($data as $k=>$v){

                if($v['level'] == 1)

                {

                    $level2['son'][] = $v;

                    $level2['sum']=count($level2);

                }

            }

            $level2['son']=array_splice($level2['son'],$p2*$num,$num);

            echo json_encode(array(

                'code'=>'2015',

                'msg'=>'分销结构详情',

                'data'=>$level2,

            )); exit();

        }

        //三级

        if ($type == 'D3'){

            foreach ($data as $k=>$v){

                if($v['level'] == 2)

                {

                    $level3['son'][] = $v;

                    $level3['sum']=count($level3);

                }

            }

            $level3['son']=array_splice($level3['son'],$p3*$num,$num);

            echo json_encode(array(

                'code'=>'2015',

                'msg'=>'分销结构详情',

                'data'=>$level3,

            )); exit();

        }

        //四级或更低

        if ($type =='D4'){

            foreach ($data as $k=>$v){

                if($v['level'] >= 3){

                    $levelEnd['son'][]=$v;

                    $levelEnd['sum']=count($levelEnd);

                }

            }

            $levelEnd['son']=array_splice($levelEnd['son'],$p4*$num,$num);

            echo json_encode(array(

                'code'=>'2015',

                'msg'=>'分销结构详情',

                'data'=>$levelEnd,

            )); exit();

        }









//        $levelTwo=array_merge($level2,$p*$num,$num);

//        dump($levelTwo);die;





//        dump(count($data));die;

//        $data = array(

//        	 'level1' => $level1,

//        	 'level2' => $level2,

//        	 'level3' => $level3,

//             'levelEnd'=>$levelEnd,

//        	);

        echo json_encode(array('code'=>7771,'msg'=>'请传正确参数'));

        die;

        // print_r($data);

//        echo json_encode(array(

//            'code'=>'2015',

//            'msg'=>'分销结构详情',

//            'data'=>$data,

//        )); exit();

        

    }

    //绑定银行卡--储蓄卡

    //上传银行卡正面

    public function cardsc(){

    	$uid = $_REQUEST['uid'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$uid,

            'type'=>$_REQUEST['type'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        $w['user_id']=$uid;

        if(IS_POST){

            $data = I('post.');

            // input

            if(!$data['cart_img'])

            {

                if ($_FILES['cart_img']['error'] == 0){

                    $config = array(

                        'maxSize'    =>    10 * 1024 * 1024, //tp里面的单位单位是B(字节)8b  1Byte = 8bits  100Mbits / 8

                        'rootPath'   =>    './Uploads/', // 上传根目录（必须手工建立）

                        'savePath'   =>    'bankUp/', //上传的二级目录（不用自己建立）

                        'saveName'   =>    array('uniqid',''),

                        'exts'       =>    array('jpg', 'gif', 'png', 'jpeg'),

                    );

                    $upload = new \Think\Upload($config);// 实例化上传类

                    // 上传单个文件

                    $info   =   $upload->upload();

                    // var_dump($info);

                    if(!$info) {// 上传错误提示错误信息

                        echo json_encode(array('code'=>2015,'msg'=>$upload->getError()));die;

                        //                    $this->error($upload->getError());

                    }else{

                        $data['cart_img'] = $info['cart_img']['savepath'].$info['cart_img']['savename'];

                        $image = new \Think\Image();

                        if ($image->open('./Uploads/' . $data['cart_img'])) {

                            $image->thumb(100, 100)->save('./Uploads/' .$data['cart_img'] . '_thumb.jpg');

                        };

                        // $res = M('mybank')->where(array('uid'=>$uid))->save($data);

                        echo json_encode(array('code'=>2018,'msg'=>'银行卡正面上传成功','img'=>$data['cart_img']));exit;

                    }

                }

            }

        }

    }

     //绑定储蓄卡(第一次绑定结算卡的鉴权接口)(废弃接口,被替代了)

    public function cardSavingsBound(){
        echo json_encode(array('code'=>4303,'msg'=>"请升级新版本进行绑定银行卡"));die;
    }



    public function cardInfo(){

    	$uid = $_REQUEST['uid'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$uid,

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        $w['user_id']=$uid;

    	# 查询持卡人/证件号/发卡银行

        //发卡银行

        $res = M('mybank')->where($w)->find();

        $data['bank_id'] = $res['bank_id'];

//        $one = M('bank')->where(array('bank_id'=>$data['bank_id']))->find();//自己绑定的银行卡

//        $bankData = M('bank')->where(array('status'=>1))->select();

        $info = M('myrealname')->field('nickname,idcard')->where($w)->find();

        echo json_encode(array(

            'code'=>'2017',

            'msg'=>'银行卡信息',

            'data'=>array(

//                'bankData'=>$bankData,

//                'one'=>$one,

                'data'=>$data,

                'info'=>$info

                )

            )); exit();

    }



    //绑定银行卡发送验证码

    public function yzmfs(){

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$this->uid,

            'phone'=>$_REQUEST['phone'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        if ($_REQUEST){

          $phone = $_REQUEST['phone'];

            if ($phone == '') {

                echo json_encode(array('code'=>5013,'msg'=>'手机号码不能为空啊,同志'));die;

            }

            if (!preg_match("/^1[34578]{1}\d{9}$/",$phone)) {

                echo json_encode(array('errCode'=>4304,'msg'=>'手机号码格式不正确'));die;

            }



            $data['code'] = create_yzm();

            $data['type'] = 3;

            $one = M('mobleyzm')->where(array('phone'=>$phone,'type'=>3))->find();

            $msg =  "验证码：".$data['code']."(为了您的账户安全，请勿告知他人，请在页面尽快完成验证)客服4008-272-278";

            $infooo = R("Func/Func/send_mobile",array($phone,$msg));

          if($one){

              $res = M('mobleyzm')->where(array('phone'=>$phone,'type'=>3))->save($data);

              if ($res){

                  echo json_encode(array('code'=>2006,'msg'=>'验证码发送成功'));die;

              }else{

                  echo json_encode(array('code'=>5012,'msg'=>'系统繁忙'));die;

              }

          }else{

              $data['c_t'] = time();

              $data['phone'] = $phone;

              $res = M('mobleyzm')->add($data);

              if ($res){

                  echo json_encode(array('code'=>2006,'msg'=>'验证码发送成功'));die;

              }else{

                  echo json_encode(array('code'=>5012,'msg'=>'系统繁忙'));die;

              }

          }



        }else{

            echo json_encode(array('code'=>4006,'msg'=>'参数错误'));die;

        }

    }

    // 绑定银行卡 - 鉴权绑卡确认

    public function ymBankCard($validateCode="")

    {

        $param = C("KJPAY");  // 调用function.php文件中的配置参数 合利宝支付配置参数

        $param['userId'] = $this->getUserId($this->uid); //唯一标识

        if(IS_POST)

        {

            $phone = trim($_REQUEST['mobile']);

            $ordersn="ymbank__".date('Ymdhis',time());//订单号

            $timestamp=date('Ymdhis',time());//订单时间戳

            # 实名认证 - 审核成功状态

            $myinfo = R("Func/Func/getMyInfo",array('uid'=>$this->uid));

            if(empty($myinfo))

            {

                $arr['ret_code'] = 0;

                $arr['ret_msg'] = '实名认证信息错误';

                return $arr;

            }

            $useful = trim($_REQUEST['useful']);

            if(!$useful)

            {

                $yyear = "";

            }else{

                $yyear = substr($useful,2);

            }

            if(!$useful)

            {

                $ymonth = "";

            }else{

                $ymonth = substr($useful,0,2);

            }

            $order = array(

                'userId'=> $this->getUserId($this->uid),

                'orderId'=> $ordersn,

                'timestamp'=> $timestamp,

                'payerName'=> trim($myinfo['nickname']), # 姓名

                'idCardType'=> 'IDCARD', # 默认身份证

                'idCardNo'=> trim($myinfo['idcard']), # 身份证号码

                'cardNo' => trim($_REQUEST['cart']), # 银行卡号

                'year' => $yyear,

                'month' => $ymonth,

                'cvv2' => trim($_REQUEST['cw_two']),

                'phone' => $phone

            );

            $pdata['validateCode'] = trim($validateCode);

            $pdata['uid'] = $this->uid;

            $pdata['bankid'] = intval($_REQUEST['bankid']);

            $res = R("Payapi/Api/ajJqSubmit",array($param,$order,$pdata));

            return $res;

        }

    }

///////////////////////////// 解绑/////////////////////////////////////////////////

    #  解绑信用卡 - 单独用

    public function BankCardUnbind()

    {

        //---银行卡鉴权绑定成功/失败返回请求参数---------{"rt10_bindId":"58c9b190fd264938afc34da15b7737cf","rt2_retCode":"0000","rt5_userId":"13112157792",

        //"rt6_orderId":"ymbank__20171125055403","rt7_bindStatus":"SUCCESS","rt11_serialNumber":"AUTHENTICATION171125175404CEUV","sign":"4d4982d73ed34c40395af5e001eec05f","rt1_bizType":"QuickPayBindCard","rt4_customerNumber":"C1800001834",

        //"rt8_bankId":"CMBCHINA","rt3_retMsg":"认证成功","rt9_cardAfterFour":"3874"}-------

//        $uid = $_REQUEST['uid'];

        $param = C("KJPAY");

        import('Vendor.payPerson.HttpClient');

        $Client = new \HttpClient($param['ip']);

        $signkey_quickpay = $param['signkey_quickpay'];//密钥key



//----回调返回信息参数----czorder=&rt10_bindId=2fa63934ea204bc1b1760727baa175e0&sign=8f93b0de188e99caeebcb1659353d9d3&rt1_bizType=QuickPayConfirmPay&rt9_orderStatus=SUCCESS&rt6_serialNumber=QUICKPAY171115114631VWWY&rt14_userId=13112157790&rt2_retCode=0000&rt12_onlineCardType=CREDIT&rt11_bankId=CMBCHINA&rt13_cardAfterFour=3874&rt5_orderId=creadition__20171115114630&rt4_customerNumber=C1800001834&rt8_orderAmount=5.00&rt3_retMsg=%E6%88%90%E5%8A%9F&rt7_completeDate=2017-11-15+11%3A52%3A51



//        ----回调返回信息参数----czorder=&rt10_bindId=2792b8912b984f06b1aa5c7cfded27bb&sign=65030a3c117bee1c23db4498a6e52b33&rt1_bizType=QuickPayConfirmPay&rt9_orderStatus=SUCCESS&rt6_serialNumber=QUICKPAY171116102352UFGU&rt14_userId=13112157790&rt2_retCode=0000&rt12_onlineCardType=CREDIT&rt11_bankId=CMBCHINA&rt13_cardAfterFour=3874&rt5_orderId=creadition__20171116102351&rt4_customerNumber=C1800001834&rt8_orderAmount=5.00&rt3_retMsg=%E6%88%90%E5%8A%9F&rt7_completeDate=2017-11-16+10%3A24%3A40----回调返回信息参数----czorder=&rt10_bindId=2792b8912b984f06b1aa5c7cfded27bb&sign=65030a3c117bee1c23db4498a6e52b33&rt1_bizType=QuickPayConfirmPay&rt9_orderStatus=SUCCESS&rt6_serialNumber=QUICKPAY171116102352UFGU&rt14_userId=13112157790&rt2_retCode=0000&rt12_onlineCardType=CREDIT&rt11_bankId=CMBCHINA&rt13_cardAfterFour=3874&rt5_orderId=creadition__20171116102351&rt4_customerNumber=C1800001834&rt8_orderAmount=5.00&rt3_retMsg=%E6%88%90%E5%8A%9F&rt7_completeDate=2017-11-16+10%3A24%3A40----回调返回信息参数----czorder=&rt10_bindId=abe1de2f74504195bb11fa6882e9169d&sign=9b74510e0640c3b702d64dcb4506e221&rt1_bizType=QuickPayConfirmPay&rt9_orderStatus=SUCCESS&rt6_serialNumber=QUICKPAY171116101511Z9ZW&rt14_userId=13112157790&rt2_retCode=0000&rt12_onlineCardType=CREDIT&rt11_bankId=CMBCHINA&rt13_cardAfterFour=3874&rt5_orderId=creadition__20171116101511&rt4_customerNumber=C1800001834&rt8_orderAmount=5.00&rt3_retMsg=%E6%88%90%E5%8A%9F&rt7_completeDate=2017-11-16+10%3A17%3A03----回调返回信息参数----czorder=&rt10_bindId=2792b8912b984f06b1aa5c7cfded27bb&sign=65030a3c117bee1c23db4498a6e52b33&rt1_bizType=QuickPayConfirmPay&rt9_orderStatus=SUCCESS&rt6_serialNumber=QUICKPAY171116102352UFGU&rt14_userId=13112157790&rt2_retCode=0000&rt12_onlineCardType=CREDIT&rt11_bankId=CMBCHINA&rt13_cardAfterFour=3874&rt5_orderId=creadition__20171116102351&rt4_customerNumber=C1800001834&rt8_orderAmount=5.00&rt3_retMsg=%E6%88%90%E5%8A%9F&rt7_completeDate=2017-11-16+10%3A24%3A40

//        ----回调返回信息参数----czorder=&rt10_bindId=1900ad939007458396c64b06f4b3ab4d&sign=8a3cb60e03ec702747e8ee9d593cc1b0&rt1_bizType=QuickPayConfirmPay&rt9_orderStatus=SUCCESS&rt6_serialNumber=QUICKPAY1711161030456GFV&rt14_userId=13112157790&rt2_retCode=0000&rt12_onlineCardType=CREDIT&rt11_bankId=CMBCHINA&rt13_cardAfterFour=3874&rt5_orderId=creadition__20171116103045&rt4_customerNumber=C1800001834&rt8_orderAmount=5.00&rt3_retMsg=%E6%88%90%E5%8A%9F&rt7_completeDate=2017-11-16+10%3A33%3A04----回调返回信息参数----czorder=&rt10_bindId=1900ad939007458396c64b06f4b3ab4d&sign=8a3cb60e03ec702747e8ee9d593cc1b0&rt1_bizType=QuickPayConfirmPay&rt9_orderStatus=SUCCESS&rt6_serialNumber=QUICKPAY1711161030456GFV&rt14_userId=13112157790&rt2_retCode=0000&rt12_onlineCardType=CREDIT&rt11_bankId=CMBCHINA&rt13_cardAfterFour=3874&rt5_orderId=creadition__20171116103045&rt4_customerNumber=C1800001834&rt8_orderAmount=5.00&rt3_retMsg=%E6%88%90%E5%8A%9F&rt7_completeDate=2017-11-16+10%3A33%3A04

//        f37a6fb9537d470284f4472b29ed9d64   creadition__20171115120757

//          828363aad3f6475b86087616b8dd1b51   creadition__20171115124329



        #b7fc474712564be8b222726eccbf2067  58c9b190fd264938afc34da15b7737cf

//        $res = M('mybank_bind')->where(array('user_id'=>$uid,'card'=>$_REQUEST['cart']))->order('t desc')->find();

//        if (!$res){

//            echo json_encode(array('code'=>4500,'msg'=>'未查找到卡'));die;

//        }

        $order = array(

            'userId' =>'18978398484', // $res['userId']

            'bindId' => '6cfa5be0e5364c2a97b3dd43c23f8ee5',//$res['bindId']

            'orderId' => 'ymbank'.date('YmdHis',time()).rand(10000,99999),

            'timestamp' => date('YmdHis',time())

        );



        if ($param['ip'] <> '') {//检查必要参数

            $P1_bizType = "BankCardUnbind";

            $P2_customerNumber = $param["P2_customerNumber"];

            $P3_userId = $order["userId"];

            $P4_bindId = $order['bindId'];

            $P5_orderId = $order['orderId'];

            $P6_timestamp = $order['timestamp'];





            //构造签名串

            $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_userId&$P4_bindId&$P5_orderId&$P6_timestamp&$signkey_quickpay";



            $sign = md5($signFormString);//MD5签名



//            dump($sign);die;



            //构造请求参数   新增绑卡ID值(不参与签名)

            $params = array('P1_bizType' => $P1_bizType, 'P2_customerNumber' => $P2_customerNumber, 'P3_userId' => $P3_userId, 'P4_bindId' => $P4_bindId, 'P5_orderId' => $P5_orderId, 'P6_timestamp' => $P6_timestamp,'sign' => $sign);



//            $url = "http://transfer.trx.helipay.com/trx/transfer/interface.action";

            $url = "http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action";

            $pageContents = $Client->quickPost($url, $params);  //发送请求 send request



//            dump($params);

//            dump($pageContents);

//            die;

            //支付和绑卡等用：http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action

            //提现用：http://transfer.trx.helipay.com/trx/transfer/interface.action

            //http://transfer.trx.helipay.com/trx/transfer/interface.action





            # 保存日志

            $this->PaySetLog("./PayLog", "jiebank__", json_encode($params) . " -------  解绑请求参数 " . "---------" . $pageContents . "\r\n");



            $obj = json_decode($pageContents);



//            {"rt2_retCode":"0000","sign":"ef7cacdcd79ff6bad768542d51e3d556","rt1_bizType":"BankCardUnbind","rt4_customerNumber":"C1800001834","rt3_retMsg":"成功"}

            return $obj; //

        }

    }

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

    //////////////////////////////////////////////////////////////////////////////

     //绑定银行卡 - 鉴权发送短信

    public function yzmfsnew()

    {

        $param = C("KJPAY");  // 调用function.php文件中的配置参数 合利宝支付配置参数

        $param['userId'] = $this->getUserId($this->uid); //唯一标识

        if(IS_POST)

        {

            $phone = trim($_REQUEST['mobile']);

            $ordersn="yzmfsnew__".date('Ymdhis',time());//订单号

            $timestamp=date('Ymdhis',time());//订单时间戳

            $order = array(

                'userId'=> $this->getUserId($this->uid),

                'orderId'=> $ordersn,

                'timestamp'=> $timestamp,

                'cardNo'=> trim($_REQUEST['cardNo']), //银行卡号

                'phone' => $phone  //手机号码

            );

            $res = R("Payapi/Api/ajJqSend",array($param,$order));

            echo json_encode($res);exit;

        }

    }


    //绑定储蓄卡 (不是鉴权,除去第一张结算卡)(废弃接口,被替代了)

    public function cardTwoBound(){
        echo json_encode(array('code'=>4303,'msg'=>"请升级新版本进行绑定银行卡"));die;
    }
//
//    //绑定银行卡--信用卡(废弃接口,被替代了)

    public function cardCreditBound(){
        echo json_encode(array('code'=>4303,'msg'=>"请升级新版本进行绑定银行卡"));die;
    }

    //收支明细

    public function incomeDetailcs(){

        $uid = $_REQUEST['uid'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$this->uid,

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'网络错误,请重新提交'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        if (!$uid){

            echo json_encode(array('code'=>6502,'msg'=>'参数不全'));die;

        }

        //MONEY_TYPE_ID":"1

        $benyue=array();

        $shangyue=array();

        $where['user_id']=$this->uid;

        $where['_string']="a.money_type_id != 1 and a.money_type_id != 2 and a.money_type_id != 3 and a.money_type_id != 4 and a.money_type_id != 5 and a.money_type_id != 6 and a.money_type_id != 7 and a.money_type_id != 8 and a.money_type_id != 9 and a.money_type_id != 12 and a.money_type_id != 13 and a.money_type_id != 14 and a.money_type_id != 15 and a.money_type_id != 16 and a.money_type_id != 17 and a.money_type_id != 18 and a.money_type_id != 19 and a.money_type_id != 20 and a.money_type_id != 21 ";

//        $where['money_type_id'] = array(array('NEQ',9),array('NEQ',12),array('NEQ',17),array('NEQ',18),array('NEQ',19),'OR');  //没完成

//        $where['a.money_type_id'] = array(,'OR');

//        array('user_id'=>$this->uid)

        $details = M('money_detailed')->alias('a')->field('a.*,b.money_type_id as b_id,b.type as b_type')->join('left join y_money_type as b on a.money_type_id = b.money_type_id')->where($where)->order('t desc')->select();

//        dump(M('money_detailed')->_sql());

//        dump($details);die;

        foreach ($details as $k=>$v){

            if($v['money_type_id'] == 1){

                $v['symbol']='-';

            }elseif($v['money_type_id'] == 2){

                $v['symbol']='-';

            }elseif($v['money_type_id'] == 3){

                $v['symbol']='-';

            }elseif($v['money_type_id'] == 4){

                $v['symbol']='-';

            }elseif($v['money_type_id'] == 5){

                $v['symbol']='+';

            }elseif($v['money_type_id'] == 6){

                $v['symbol']='-';

            }elseif($v['money_type_id'] == 7){

                $v['symbol']='-';

            }elseif($v['money_type_id'] == 8){

                $v['symbol']='+';

            }elseif($v['money_type_id'] == 9){

                $v['symbol']='+';

            }elseif($v['money_type_id'] == 10){

                $v['symbol']='+';

            }elseif($v['money_type_id'] == 11){

                $v['symbol']='-';

            }elseif($v['money_type_id'] == 12){

                $v['symbol']='+';

            }elseif($v['money_type_id'] == 13){

                $v['symbol']='+';

            }elseif($v['money_type_id'] == 14){

                $v['symbol']='+';

            }elseif($v['money_type_id'] == 15){

                $v['symbol']='+';

            }elseif($v['money_type_id'] == 16){

                $v['symbol']='-';

            }elseif($v['money_type_id'] == 17){

                $v['symbol']='+';

            }elseif($v['money_type_id'] == 18){

                $v['symbol']='+';

            }elseif($v['money_type_id'] == 19){

                $v['symbol']='+';

            }elseif($v['money_type_id'] == 20){

                $v['symbol']='+';

            }elseif($v['money_type_id'] == 21){

                $v['symbol']='+';

            }

            $v['pay_money']=sprintf("%.2f",$v['pay_money']);

            if (date('Y-m',$v['t']) == date('Y-m',time())){

                $benyue[]=$v;

            }

            if (date('Y-m',$v['t']) == date('Y-m',time()-3600*24*30)){

                $shangyue[]=$v;

            }

        }



        if ($details){

            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>array('benyue'=>$benyue,'shangyue'=>$shangyue)));die;

        }else{

            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;

        }

    }

    //收支明细分页接口

    public function incomeDetail(){

        $uid = $_REQUEST['uid'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$this->uid,

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'网络错误,请重新提交'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        if (!$uid){

            echo json_encode(array('code'=>6502,'msg'=>'参数不全'));die;

        }

        $p=$_REQUEST['page'];

        $num=10;

        if(!$p){

          $p=0;

        }

        //MONEY_TYPE_ID":"1

        $benyue=array();

        $shangyue=array();

        $where['user_id']=$this->uid;

        $where['_string']="a.money_type_id != 1 and a.money_type_id != 2 and a.money_type_id != 3 and a.money_type_id != 4 and a.money_type_id != 5 and a.money_type_id != 6 and a.money_type_id != 7 and a.money_type_id != 8 and a.money_type_id != 9 and a.money_type_id != 12 and a.money_type_id != 13 and a.money_type_id != 14 and a.money_type_id != 15 and a.money_type_id != 16 and a.money_type_id != 17 and a.money_type_id != 18 and a.money_type_id != 19 and a.money_type_id != 20 and a.money_type_id != 21 ";

//        $where['money_type_id'] = array(array('NEQ',9),array('NEQ',12),array('NEQ',17),array('NEQ',18),array('NEQ',19),'OR');  //没完成

//        $where['a.money_type_id'] = array(,'OR');

//        array('user_id'=>$this->uid)

        $sum=M('money_detailed')->alias('a')->field('a.*,b.money_type_id as b_id,b.type as b_type')->join('left join y_money_type as b on a.money_type_id = b.money_type_id')->where($where)->order('t desc')->count();

        $details = M('money_detailed')->alias('a')->field('a.*,b.money_type_id as b_id,b.type as b_type')->join('left join y_money_type as b on a.money_type_id = b.money_type_id')->where($where)->order('t desc')->limit($p*$num,$num)->select();

//        dump($sum);die;

//        dump(M('money_detailed')->_sql());

        $pagesum=ceil($sum/$num);

        foreach ($details as $k=>$v){

            if($v['money_type_id'] == 1){

                $details[$k]['symbol']='-';

            }elseif($v['money_type_id'] == 2){

                $details[$k]['symbol']='-';

            }elseif($v['money_type_id'] == 3){

                $details[$k]['symbol']='-';

            }elseif($v['money_type_id'] == 4){

                $details[$k]['symbol']='-';

            }elseif($v['money_type_id'] == 5){

                $details[$k]['symbol']='+';

            }elseif($v['money_type_id'] == 6){

                $details[$k]['symbol']='-';

            }elseif($v['money_type_id'] == 7){

                $details[$k]['symbol']='-';

            }elseif($v['money_type_id'] == 8){

                $details[$k]['symbol']='+';

            }elseif($v['money_type_id'] == 9){

                $details[$k]['symbol']='+';

            }elseif($v['money_type_id'] == 10){

                $details[$k]['symbol']='+';

            }elseif($v['money_type_id'] == 11){

                $details[$k]['symbol']='-';

            }elseif($v['money_type_id'] == 12){

                $details[$k]['symbol']='+';

            }elseif($v['money_type_id'] == 13){

                $details[$k]['symbol']='+';

            }elseif($v['money_type_id'] == 14){

                $details[$k]['symbol']='+';

            }elseif($v['money_type_id'] == 15){

                $details[$k]['symbol']='+';

            }elseif($v['money_type_id'] == 16){

                $details[$k]['symbol']='-';

            }elseif($v['money_type_id'] == 17){

                $details[$k]['symbol']='+';

            }elseif($v['money_type_id'] == 18){

                $details[$k]['symbol']='+';

            }elseif($v['money_type_id'] == 19){

                $details[$k]['symbol']='+';

            }elseif($v['money_type_id'] == 20){

                $details[$k]['symbol']='+';

            }elseif($v['money_type_id'] == 21){

                $details[$k]['symbol']='+';

            }

            $details[$k]['pay_money']=sprintf("%.2f",$v['pay_money']);

//            if (date('Y-m',$v['t']) == date('Y-m',time())){

//                $benyue[]=$v;

//            }

//            if (date('Y-m',$v['t']) == date('Y-m',time()-3600*24*30)){

//                $shangyue[]=$v;

//            }

        }

             //'data'=>array('benyue'=>$benyue,'shangyue'=>$shangyue)

        if ($details){

            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>$details,'pagesize'=>$sum,'pagesum'=>$pagesum));die;

        }else{

            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;

        }

    }



    //收支明细(详细信息)

    public function szDetail(){



        $id = $_REQUEST['id'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'id'=>$id,

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        if (!$id){

            echo json_encode(array('code'=>6502,'msg'=>'参数不全'));die;

        }

        $info = M('money_detailed')->alias('a')->field('a.*,b.money_type_id as b_id,b.type as b_type')->join('left join y_money_type as b on a.money_type_id = b.money_type_id')->where(array('a.money_detailed_id'=>$id))->find();

        $info['pay_money']=sprintf("%.2f",$info['pay_money']);

         if ($info){

             if($info['money_type_id'] == 9){

               $user_id= M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$info['sh_ordersn']))->find();

               $name = M('user')->field('user_id,nick_name')->where(array('user_id'=>$user_id['user_id']))->find();

               $info['nick_name']=$name['nick_name'];

             }

             if ($info['money_type_id'] == 12){

                 $user_id= M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$info['sh_ordersn']))->find();

                 $name = M('user')->field('user_id,nick_name')->where(array('user_id'=>$user_id['user_id']))->find();

                 $info['nick_name']=$name['nick_name'];

             }

             if ($info['money_type_id'] == 13){

                 $user_id= M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$info['sh_ordersn']))->find();

                 $name = M('user')->field('user_id,nick_name')->where(array('user_id'=>$user_id['user_id']))->find();

                 $info['nick_name']=$name['nick_name'];

             }

             if ($info['money_type_id'] == 14){

                 $jsorderInfo=substr($info['sh_ordersn'],2);

                 $js_name = M('moneyjs_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$jsorderInfo))->find();

                 $name = M('user')->field('user_id,nick_name')->where(array('user_id'=>$js_name['user_id']))->find();

                 $info['nick_name']=$name['nick_name'];

             }

             if ($info['money_type_id'] == 15){

                 $name = M('user')->field('user_id,nick_name')->where(array('user_id'=>$info['user_id']))->find();

                 $info['nick_name']=$name['nick_name'];

             }

             if ($info['money_type_id'] == 18){

                 $doukeInfo=M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$info['sh_ordersn']))->find();

                 $name = M('user')->field('user_id,nick_name')->where(array('user_id'=>$doukeInfo['user_id']))->find();

                 $info['nick_name']=$name['nick_name'];

             }

             if ($info['money_type_id'] == 19){

                 $doukeInfo=M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$info['sh_ordersn']))->find();

                 $name = M('user')->field('user_id,nick_name')->where(array('user_id'=>$doukeInfo['user_id']))->find();

                 $info['nick_name']=$name['nick_name'];

             }

             if ($info['money_type_id'] == 20){   // 智能还款兜客佣金

                 $doukeInfo=M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$info['sh_ordersn']))->find();

                 $name = M('user')->field('user_id,nick_name')->where(array('user_id'=>$doukeInfo['user_id']))->find();

                 $info['nick_name']=$name['nick_name'];

             }

             if ($info['money_type_id'] == 21){   // 智能还款推客分销

                 $doukeInfo=M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$info['sh_ordersn']))->find();

                 $name = M('user')->field('user_id,nick_name')->where(array('user_id'=>$doukeInfo['user_id']))->find();

                 $info['nick_name']=$name['nick_name'];

             }

             echo json_encode(array(array('code'=>6501,'msg'=>'获取成功','data'=>$info)));die;

         }else{

             echo json_encode(array(array('code'=>6503,'msg'=>'获取信息为空','data'=>array())));die;

         }

    }

   //结算信息

    public function jieSuancs(){

    $uid =$_REQUEST['uid'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$this->uid,

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        $benyue=array();

        $shangyue=array();

        $info = M('moneyjs_detailed')->where(array('user_id'=>$uid,'type'=>2))->order('t desc')->select();

        foreach ($info as $k=>$v){

            $v['symbol']='+';

            if (date('Y-m',$v['t']) == date('Y-m',time())){

                $v['symbol']='+';

                $benyue[]=$v;

            }

            if (date('Y-m',$v['t']) == date('Y-m',time()-3600*24*30)){

                $v['symbol']='+';

                $shangyue[]=$v;

            }

        }

        if ($info){

            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>array('benyue'=>$benyue,'shangyue'=>$shangyue)));die;

        }else{

            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;

        }

    }

    //结算记录分页测试接口

    public function jieSuan(){

        $uid =$_REQUEST['uid'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$this->uid,

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        $p=$_REQUEST['page'];

        if(!$p){

            $p=0;

        }

        $num=10;

//    $benyue=array();

//    $shangyue=array();

        $sum=M('moneyjs_detailed')->where(array('user_id'=>$uid,'type'=>2))->order('t desc')->count();

        $info = M('moneyjs_detailed')->where(array('user_id'=>$uid,'type'=>2))->order('t desc')->limit($p*$num,$num)->select();

        $pagesum=ceil($sum/$num);

        foreach ($info as $k=>$v){

            $info[$k]['symbol']='+';

//        if (date('Y-m',$v['t']) == date('Y-m',time())){

//            $v['symbol']='+';

//            $benyue[]=$v;

//        }

//        if (date('Y-m',$v['t']) == date('Y-m',time()-3600*24*30)){

//            $v['symbol']='+';

//            $shangyue[]=$v;

//        }

        }

        if ($info){

            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>$info,'pagesize'=>$sum,'pagesum'=>$pagesum));die;

        }else{

            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;

        }

    }

    //结算信息详细信息

    /**

     * update time : 2017-12-4

     * @新增结算失败返回第一次(重新提交结算)/第二次(联系客服)

     */

    public function jieSuanDetail(){

        $id = $_REQUEST['id'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'id'=>$id,

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $info = M('moneyjs_detailed')->alias('a')->field('a.user_id,a.moneyjs_detailed_id,a.moneyjs_detailed_id,a.sx_money,a.tx_service,a.js_money,a.js_status,a.t,a.js_card,a.type,a.user_js_supply_id,a.rz_money,a.pt_ordersn,a.j_succee_time,a.js_type,b.pay_money')->join('left join y_money_detailed as b on a.pt_ordersn=b.pt_ordersn')->where(array('a.moneyjs_detailed_id'=>$id))->find();

        $info['symbol']='+';

            if (!$info['pay_money']){

                $info['pay_money']=ceil($info['js_money']);

            }

         $bankid = M('mybank')->where(array('user_id'=>$info['user_id'],'type'=>1,'is_normal'=>1))->getField('bank_id');

         $bankData = M('bank')->where(array('bank_id'=>$bankid))->getField('name');

//         dump($info);die;

         if ($info['type'] == 2){

          unset($info['js_duixiang']);

          unset($info['merchant_name']);

          unset($info['sh_ordersn']);

         }else{

             unset($info['sh_ordersn']);

         }

        $info['pay_money']=sprintf("%.2f",$info['pay_money']);

         foreach ($info as $k){

             trim($k);

         }

//        dump($info);die;





        # 新增第一次(重新提交结算)/第二次(联系客服)

        $info['sx_money'] = trim($info['sx_money']);

        $onetype = 0;

        if($info['js_status'] == 3) # 结算失败

        {

            # 查询是否有关联订单号

            $relation_order = M("moneyjs_detailed")->where(array('relation_order'=>$info['pt_ordersn']))->getField('relation_order');

            if(!empty($relation_order))

            {

                $onetype = 1;  // 已经有关联订单

            }else{

                $onetype = 2;  // 可重新提交结算

            }

        }
        $onetype = 1;
        $info['onetype'] = $onetype;


        //查所属机构的客服

        $res = M('user')->where(array('user_id'=>$info['user_id']))->find();

        $info['kf_tel'] = '4008-272-278';

        if($res)

        {

            $kefu = M('institution')->field('kf_tel')->where(array('institution_id'=>$res['institution_id']))->find();

            if($kefu)

            {

                $info['kf_tel'] = $kefu;

            }

        }

        if ($info){

            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>$info,'bank'=>$bankData));die;

        }else{

            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;

        }

    }

   //积分明细

    public function myIntegral(){

        $uid = $_REQUEST['uid'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$this->uid,

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        if (!$uid){

            echo json_encode(array('code'=>6502,'msg'=>'参数不全'));die;

        }

        $info = array();

        $data = array();

        $info = M('user')->field('integral')->where(array('user_id'=>$uid))->find(); //用户积分信息

        $data = M('jifen_jilu')->where(array('user_id'=>$uid))->select(); //记录

      echo json_encode(array('code'=>6801,'msg'=>'获取成功','data'=>array('yongyoujifen'=>$info,'jifenjilu'=>$data)));die;

    }

    //推广二维码

    public function myQrcode(){

        $uid = $_REQUEST['uid'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$this->uid,

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        if (!$uid){

            echo json_encode(array('code'=>6502,'msg'=>'参数不全'));die;

        }

        $info = M('user')->field('tg_ewcode,tg_code')->where(array('user_id'=>$uid))->find();

        $info['qrcode']="https://wallet.insoonto.com/index.php/Payapi/Wx/locationRegister?pid=".$uid;

       if ($info){

           echo json_encode(array('code'=>6801,'msg'=>'获取成功','data'=>$info));die;

       }else{

           echo json_encode(array('code'=>6503,'msg'=>'获取信息为空'));die;

       }

    }

    //查询指定月份的交易记录

    public function selectAssigncs(){

        $sj =$_REQUEST['time'];

        $uid = $_REQUEST['uid'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$this->uid,

            'time'=>$_REQUEST['time'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断





        if (!$uid || !$sj){

            echo json_encode(array('code'=>6502,'msg'=>'参数不全'));die;

        }

        $time= strtotime($sj);

       if (!$time){

           echo json_encode(array('code'=>6901,'msg'=>'日期参数不存在'));die;

       }

        $benyue=array();

        $shangyue=array();

        $where['user_id']=$this->uid;

        $where['_string']="money_type_id != 9 and money_type_id != 12 and money_type_id != 13 and money_type_id != 14 and money_type_id != 15 and money_type_id !=16 and money_type_id != 17 and money_type_id != 18 and money_type_id != 19 and money_type_id != 20 and money_type_id != 21";

//        $where['money_type_id'] = array(array('NEQ',9),array('NEQ',12),'OR');

        $details = M('money_detailed')->where($where)->order('t desc')->select();

        foreach ($details as $k=>$v){

            $v['symbol']='-';

            $v['pay_money']=sprintf("%.2f",$v['pay_money']);

            $v['money']=sprintf("%.2f",$v['money']);

            if (date('Y-m',$v['t']) == date('Y-m',$time)){

                $benyue[]=$v;

            }

            if (date('Y-m',$v['t']) == date('Y-m',$time-3600*24*30)){

                $shangyue[]=$v;

            }

        }

        if ($details){

            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>array('benyue'=>$benyue,'shangyue'=>$shangyue)));die;

        }else{

            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;

        }

    }

  //查询指定月份的交易记录分页测试接口

    public function selectAssign(){

        $sj =$_REQUEST['time'];

        $uid = $_REQUEST['uid'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$this->uid,

            'time'=>$_REQUEST['time'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断





        if (!$uid || !$sj){

            echo json_encode(array('code'=>6502,'msg'=>'参数不全'));die;

        }

        $time= strtotime($sj);

        if (!$time){

            echo json_encode(array('code'=>6901,'msg'=>'日期参数不存在'));die;

        }

        $p=$_REQUEST['page'];

        $num=10;

        if(!$p){

            $p=0;

        }

        $sjt=$_REQUEST['time'];



        $state=strtotime(date('Y-m',strtotime($sjt)));//本月初。

        $endsta=strtotime(date('Y-m-t',strtotime($sjt)));//本月末

//       dump($endsta);die;

//        $zhidingY=$sjt.'-01 00:00:00';   //月份第一天

//        date('Y-m-d', mktime(0,0,0,date('n'),date('t'),date('Y')));

//        $benyue=array();

//        $shangyue=array();

        $where['user_id']=$this->uid;

        $where['t']=array(array('egt',$state),array('elt',$endsta));

        $where['_string']="money_type_id != 1 and money_type_id != 2 and money_type_id != 3 and money_type_id != 4 and money_type_id != 5 and money_type_id != 6 and money_type_id != 7 and money_type_id != 8 and money_type_id != 9 and money_type_id != 12 and money_type_id != 13 and money_type_id != 14 and money_type_id != 15 and money_type_id !=16 and money_type_id != 17 and money_type_id != 18 and money_type_id != 19 and money_type_id != 20 and money_type_id != 21";

//        $where['money_type_id'] = array(array('NEQ',9),array('NEQ',12),'OR');

        $sum=M('money_detailed')->where($where)->order('t desc')->count();

        $details = M('money_detailed')->where($where)->order('t desc')->select();

        $pagesum=ceil($sum/$num);

        foreach ($details as $k=>$v){

            $details[$k]['symbol']='-';

            $details[$k]['pay_money']=sprintf("%.2f",$v['pay_money']);

            $details[$k]['money']=sprintf("%.2f",$v['money']);

        }

        $res = array_splice($details,$p*$num,$num); # 当前页码数据

        //'data'=>array('benyue'=>$benyue,'shangyue'=>$shangyue)

        if ($res){

            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>$res,'pagesize'=>$sum,'pagesum'=>$pagesum));die;

        }else{

            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;

        }

    }

     //查询指定日期的结算记录

    public function selectClosecs(){

        $uid = $_REQUEST['uid'];

        $sj =$_REQUEST['time'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$this->uid,

            'time'=>$_REQUEST['time'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        if (!$uid || !$sj){

            echo json_encode(array('code'=>6502,'msg'=>'参数不全'));die;

        }

        $time= strtotime($sj);

        if (!$time){

            echo json_encode(array('code'=>6901,'msg'=>'日期参数不存在'));die;

        }

        $benyue=array();

        $shangyue=array();

        $info = M('moneyjs_detailed')->where(array('user_id'=>$uid))->select();

        foreach ($info as $kk=>$vv){

            $vv['symbol']='+';

            if (date('Y-m',$time) == date('Y-m',$vv['t'])){

                $vv['symbol']='+';

                $benyue[] = $vv;

            }

            if (date('Y-m',$vv['t']) == date('Y-m',$time-3600*24*30)){

                $vv['symbol']='+';

                $shangyue[]=$vv;

            }

        }

        if ($info){

            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>array('benyue'=>$benyue,'shangyue'=>$shangyue)));die;

        }else{

            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;

        }

    }

    //查询指定日期的结算记录分页测试接口

    public function selectClose(){

        $uid = $_REQUEST['uid'];

        $sj =$_REQUEST['time'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$this->uid,

            'time'=>$_REQUEST['time'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        if (!$uid || !$sj){

            echo json_encode(array('code'=>6502,'msg'=>'参数不全'));die;

        }

        $time= strtotime($sj);

        if (!$time){

            echo json_encode(array('code'=>6901,'msg'=>'日期参数不存在'));die;

        }

        $p=$_REQUEST['page'];

        if(!$p){

            $p=0;

        }

        $num=10;

//        $benyue=array();

//        $shangyue=array();

        $sjt=$_REQUEST['time'];

        $state=strtotime(date('Y-m',strtotime($sjt)));//本月初。

        $endsta=strtotime(date('Y-m-t',strtotime($sjt)));//本月末

        $where['user_id']=$uid;

        $where['type'] = 2;

        $where['t']=array(array('egt',$state),array('elt',$endsta));

        $sum=M('moneyjs_detailed')->where($where)->order(' t desc ')->count();

        $info = M('moneyjs_detailed')->where($where)->order(' t desc ')->select();

        $pagesum=ceil($sum/$num);

        foreach ($info as $kk=>$vv){

            $info[$kk]['symbol']='+';

        }

        $res =array_splice($info,$p*$num,$num);

        if ($res){

            echo json_encode(array('code'=>6501,'msg'=>'获取成功','data'=>$res,'pagesize'=>$sum,'pagesum'=>$pagesum));die;

        }else{

            echo json_encode(array('code'=>6503,'msg'=>'获取的信息为空','data'=>array()));die;

        }

    }



    //客服联系方式

    public function serviceInterface(){

        $uid = $_REQUEST['uid'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$uid,

        );



        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        //查所属机构的客服

        $res = M('user')->where(array('user_id'=>$uid))->find();

        if (!$res['institution_id']){

            //机构id为空

         echo json_encode(array('code'=>4401,'msg'=>'当前用户的所属机构为空'));die;

        }

        $kefu = M('institution')->where(array('institution_id'=>$res['institution_id']))->find();

        if(!$kefu){

          echo json_encode(array('code'=>4402,'msg'=>'机构不存在!'));die;

        }else{

            echo json_encode(array('code'=>4403,'msg'=>'获取成功','data'=>array('phone'=>$kefu['kf_tel']) ) );die;

        }

    }

    //App升级接口

    public function upgradeApp(){

//       $re =  M('version_upgrade')->where(array('version_upgrade_id'=>1))->find();

//       $re['upgrade_point']=json_encode(array('测试升级1','测试升级2','测试升级3'));

//        M('version_upgrade')->where(array('version_upgrade_id'=>2))->save($re);

//        echo 'success';die;

        $type = $_REQUEST['type'];

        if (!$type){

            echo json_encode(array('code'=>4014,'msg'=>'参数不全'));die;

        }

       if ($type == 'android'){

           $res = M('version_upgrade')->where(array('app_id'=>'android','status'=>1))->find();

           if($res['upgrade_point'])
           {
//               dump($res);die;

               $res['upgrade_point'] = json_decode($res['upgrade_point']);

           }

           echo json_encode(array('code'=>4403,'msg'=>'获取成功','data'=>$res));die;
       }

       if ($type == 'ios'){

           $res = M('version_upgrade')->where(array('app_id'=>'ios','status'=>1))->find();

           $res['ios_url']=trim($res['ios_url']);

           echo json_encode(array('code'=>4403,'msg'=>'获取成功','data'=>$res));die;

       }

        if ($type == 'ceshi'){

            $res = M('version_upgrade')->where(array('version_upgrade_id'=>3,'status'=>1))->find();

            $res['upgrade_point']=json_decode($res['upgrade_point'],true);

            echo json_encode(array('code'=>4403,'msg'=>'获取成功','data'=>$res));die;

        }



    }

    //App升级接口

    public function upgradeAppcs(){

//       $re =  M('version_upgrade')->where(array('version_upgrade_id'=>1))->find();

//       $re['upgrade_point']=json_encode(array('测试升级1','测试升级2','测试升级3'));

//        M('version_upgrade')->where(array('version_upgrade_id'=>2))->save($re);

//        echo 'success';die;

        $type = $_REQUEST['type'];

        if (!$type){

            echo json_encode(array('code'=>4014,'msg'=>'参数不全'));die;

        }

        if ($type == 'android'){

            $res = M('version_upgrade')->where(array('app_id'=>'android','status'=>1))->find();
            if($res['upgrade_point'])
            {
                $res['upgrade_point'] = json_decode($res['upgrade_point']);
            }
            echo json_encode(array('code'=>4403,'msg'=>'获取成功','data'=>$res));die;

        }

        if ($type == 'ios'){

            $res = M('version_upgrade')->where(array('app_id'=>'ios','status'=>1))->find();

            $res['ios_url']=trim($res['ios_url']);

            echo json_encode(array('code'=>4403,'msg'=>'获取成功','data'=>$res));die;

        }

        if ($type == 'ceshi'){

            $res = M('version_upgrade')->where(array('version_upgrade_id'=>3,'status'=>1))->find();

            $res['upgrade_point']=json_decode($res['upgrade_point'],true);

            echo json_encode(array('code'=>4403,'msg'=>'获取成功','data'=>$res));die;

        }

    }

    //测试验证码发送

    public function yzmFsss(){

        $coco = create_yzm();

        $phone='13790957780';

        $msg =  "验证码：".$coco."(为了您的账户安全，请勿告知他人，请在页面尽快完成验证)客服4008-272-278";

        $infooo = R("Func/Func/send_mobile",array($phone,$msg));

        dump($infooo);die;

    }



     #对应银行的支行接口 (有分页)

     public function zhiHang(){

        $bankid=$_REQUEST['bank_id'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'bank_id'=>$bankid,

            'province'=>$_REQUEST['province'],

            'city'=>$_REQUEST['city'],

//            'page'=>$_REQUEST['page'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

       /*

        * */

//       $num=30;

       $p=$_REQUEST['page'];

       if(!$p){

           $p=0;

       }

       $tt['bank_id']=$bankid;

       $tt['province_id']=$_REQUEST['province'];

        $tt['city_id']=$_REQUEST['city'];

        $res = M('bank_children')->where($tt)->select();

       $count=M('bank_children')->where($tt)->count();

        echo json_encode(array('code'=>6005,'msg'=>'获取成功','data'=>$res,'count'=>$count));

        die;

    }

    #省份接口

    public function sheng(){

         $res = M('province')->field('provids,province')->select();

       echo json_encode(array('code'=>6005,'msg'=>'获取成功','data'=>$res));

     }

     #城市接口

    public function city(){

        $shengId=$_REQUEST['province'];

        $res = M('city')->field('cityids,city')->where(array('provid'=>$shengId))->select();

     echo json_encode(array('code'=>6005,'msg'=>'获取成功','data'=>$res));

    }

    #县区接口

    public function area(){

        $cityId=$_REQUEST['city_id'];

        $res = M('area')->field('areaids,area')->where(array('cityid'=>$cityId))->select();

//        if(!$res){

//            $res = M('street')->field('streetids,street')->where(array('cityid'=>$cityId))->select();

//            echo json_encode(array('code'=>6008,'msg'=>'获取成功','data'=>$res));die;

//        }

        echo json_encode(array('code'=>6005,'msg'=>'获取成功','data'=>$res));die;

    }



    //新鉴权通道(储蓄卡) - 第二次绑定(最新)

    public function newBindBank(){

        $uid = $_REQUEST['uid'];

        $signT = $_REQUEST['sign'];

        if($_REQUEST['children_id'] == ''){
            echo json_encode(array('code'=>4203,'msg'=>'支行ID为空'));die;
        }

        if(intval($_REQUEST['children_id']) == 0){
            echo json_encode(array('code'=>4203,'msg'=>'支行ID为空'));die;
        }

        if($_REQUEST['province_id'] == ''){
            echo json_encode(array('code'=>4203,'msg'=>'省ID为空'));die;
        }
        if($_REQUEST['city_id'] == ''){
            echo json_encode(array('code'=>4203,'msg'=>'城市ID为空'));die;
        }
        if($_REQUEST['line'] == ''){
            echo json_encode(array('code'=>4203,'msg'=>'联行号不存在'));die;
        }

        if (!$signT){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$uid,

            'bankCard'=>$_REQUEST['bankCard'],

            'name'=>urldecode($_REQUEST['name']),

            'idNumber'=>$_REQUEST['idNumber'],

            'mobile'=>$_REQUEST['mobile'],

            'code'=>$_REQUEST['code'],

            'cart_img'=>urldecode($_REQUEST['cart_img']),

            'bankid'=>intval($_REQUEST['bankid']),

            'children_id'=>intval($_REQUEST['children_id']),

            'province_id'=>intval($_REQUEST['province_id']),

            'city_id'=>intval($_REQUEST['city_id']),

            'line'=>$_REQUEST['line'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        dump($msg);die;

        if ($signT !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

            /*

             * 逻辑

             *

             * */

        $re = M('mobleyzm')->where(array('phone'=>$_REQUEST['mobile'],'type'=>3))->find();

        if ($re['code'] != $_REQUEST['code']){

            echo json_encode(array('code'=>4203,'msg'=>'验证码错误'));die;

        }



        $mywhere['cart'] = trim($_REQUEST['bankCard']);

        $mywhere['jq_status'] = 3;

        $mybankinfo = M("mybank")->where($mywhere)->getField('mybank_id');

//            dump($mybankinfo);die;

        if(!empty($mybankinfo))

        {

            echo json_encode(array('code'=>3,'msg'=>'此银行卡已绑定了!请勿重新绑定'));die;

        }



        $requsetId = 'SS'.date('Ymdhis',time());

        $timestamp = date('Ymdhis',time());

        $pdata=array(

            'name'=>urldecode($_REQUEST['name']),

            'idNumber'=>$_REQUEST['idNumber'],

            'bankCard'=>$_REQUEST['bankCard'],

            'mobile'=>$_REQUEST['mobile'],

            'requestId'=>$requsetId,

            'timestamp' => $timestamp,

        );

//                dump($pdata);die;

        $ppp = R("Payapi/AuthApi/AuthBank",array($pdata,$uid));

        if ($ppp['res_code'] == '5555'){

            echo json_encode(array('code'=>5555,'msg'=>$ppp['res_msg']));die;

        }elseif($ppp['res_code'] == '9999'){

            echo json_encode(array('code'=>9999,'msg'=>$ppp['res_msg']));die;

        }



        $ifjb = M("mybank")->where(array('cart'=>trim($_REQUEST['bankCard']),'jq_status'=>1))->find();

        if($ifjb)

        {

            if ($ifjb['cart_img']){
               $path='./Uploads/'.$ifjb['cart_img'];
               @unlink($path);
            }

            $ifjb['bank_children_id']=intval($_REQUEST['children_id']); //支行

            $ifjb['province_id']=intval($_REQUEST['province_id']);  //省

            $ifjb['city_id']=intval($_REQUEST['city_id']);//市

            $ifjb['area_id']=intval($_REQUEST['area_id']);//区

            $ifjb['lianhang']=$_REQUEST['line']; //联行号

            $ifjb['cart_img']=urldecode($_REQUEST['cart_img']);

            $ifjb['jq_status']=3;
//            $ifjb['is_normal']=2;
            M("mybank")->where(array('mybank_id'=>$ifjb['mybank_id']))->save($ifjb);

            echo json_encode(array('code'=>2016,'msg'=>'绑定成功'));die;

        }else{

            // 绑定银行卡 ---储蓄卡

            $data['user_id'] = $this->uid;  //1

            $data['nickname']=urldecode($_REQUEST['name']);  //1

            $data['cart_img'] = urldecode($_REQUEST['cart_img']);  //1

            $data['cart']=$_REQUEST['bankCard'];   // 1

            $data['idcard']=$_REQUEST['idNumber'];  //1

            $data['bank_id'] = intval($_REQUEST['bankid']);  //1

            $data['mobile']=$_REQUEST['mobile'];   //1

            $data['bank_children_id']=intval($_REQUEST['children_id']); //支行

            $data['province_id']=intval($_REQUEST['province_id']);  //省

            $data['city_id']=intval($_REQUEST['city_id']);//市

            $data['area_id']=intval($_REQUEST['area_id']);//区

            $data['lianhang']=$_REQUEST['line']; //联行号

            $data['jq_td_id']=2;

            $data['status'] = 1;

            $data['type'] = 1;

            $data['jq_status']=3;

            $data['nature']=2;

            $data['t'] = time();

            $data['type'] = 1;//储蓄卡

            unset($data['code']);

            //保存银行卡信息

            $res = M('mybank')->add($data);

            if ($res){

//            echo json_encode(array('code'=>3,'msg'=>'已提交绑定'));die;

                echo json_encode(array('code'=>2016,'msg'=>'储蓄卡绑定成功'));die;

            }else{

                echo json_encode(array('code'=>5104,'msg'=>'数据错误,再试一次'));die;

            }

        }

    }

    //新鉴权通道 (信用卡)(最新)
    public function newBindcredit(){

        $uid = $this->uid;

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        if($_REQUEST['cw_two'] == ''){
            echo json_encode(array('code'=>4203,'msg'=>'cw_two为空'));die;
        }
        if($_REQUEST['useful'] == ''){
            echo json_encode(array('code'=>4203,'msg'=>'有效期为空'));die;
        }
        if($_REQUEST['amount'] == ''){
            echo json_encode(array('code'=>4203,'msg'=>'额度为空'));die;
        }
        if($_REQUEST['bill_day'] == ''){
            echo json_encode(array('code'=>4203,'msg'=>'账单日为空'));die;
        }
        if($_REQUEST['refund_day'] == ''){
            echo json_encode(array('code'=>4203,'msg'=>'还款日为空'));die;
        }

        $array = array(

            'uid'=>$this->uid,

            'cart_img'=>urldecode($_REQUEST['cart_img']),

            'bankid'=>intval($_REQUEST['bankid']),

            'nickname'=>urldecode($_REQUEST['nickname']),

            'idcard'=>$_REQUEST['idcard'],

            'cart'=>$_REQUEST['cart'],

            'mobile'=>$_REQUEST['mobile'],

            'code'=>$_REQUEST['code'],

            'cw_two'=>$_REQUEST['cw_two'],

            'useful'=>$_REQUEST['useful'],

            'amount'=>$_REQUEST['amount'],

            'bill_day'=>$_REQUEST['bill_day'],

            'refund_day'=>$_REQUEST['refund_day'],

        );

        if($_REQUEST['bankid'] == ''){

            echo json_encode(array('code'=>5554,'msg'=>'请选择所属银行'));die;

        }

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        echo json_encode(array('data'=>$msg));die;

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        /*

         * 逻辑

         * */

        $re = M('mobleyzm')->where(array('phone'=>$_REQUEST['mobile'],'type'=>3))->find();

        if ($re['code'] != $_REQUEST['code']){

            echo json_encode(array('code'=>4203,'msg'=>'验证码错误'));die;

        }

        $mywhere['cart'] = trim($_REQUEST['cart']);

        $mywhere['jq_status'] = array('eq',3);

        $mybankinfo = M("mybank")->where($mywhere)->getField('mybank_id');

//            dump($mybankinfo);die;



        if(!empty($mybankinfo))

        {

            echo json_encode(array('code'=>3,'msg'=>'此银行卡已绑定了!请勿重新绑定'));die;

        }

        //调用认证

        $requsetId = 'SS'.date('Ymdhis',time());

        $timestamp = date('Ymdhis',time());

        $pdata=array(

            'name'=>urldecode($_REQUEST['nickname']),

            'idNumber'=>$_REQUEST['idcard'],

            'bankCard'=>$_REQUEST['cart'],

            'mobile'=>$_REQUEST['mobile'],

            'requestId'=>$requsetId,

            'timestamp' =>$timestamp,

        );

        $ppp = R("Payapi/AuthApi/AuthBank",array($pdata,$uid));

        if ($ppp['res_code'] == '5555'){

            echo json_encode(array('code'=>5555,'msg'=>$ppp['res_msg']));die;

        }elseif($ppp['res_code'] == '9999'){

            echo json_encode(array('code'=>9999,'msg'=>$ppp['res_msg']));die;

        }



        # 判断此银行卡是否解绑过，解绑过，则save，没有绑定过,则add

        $ifjb = M("mybank")->where(array('cart'=>trim($_REQUEST['cart']),'jq_status'=>1))->find();



        if($ifjb)

        {

            if ($ifjb['cart_img']){
                $path='./Uploads/'.$ifjb['cart_img'];
                @unlink($path);
            }

            $savew['bank_id']=intval($_REQUEST['bankid']);

            $savew['cw_two']=$_REQUEST['cw_two'];

            $savew['useful']=$_REQUEST['useful'];

            $savew['amount']=$_REQUEST['amount'];

            $savew['bill_day']=$_REQUEST['bill_day'];

            $savew['refund_day']=$_REQUEST['refund_day'];

            $savew['jq_status']=3;

            $savew['cart_img']=urldecode($_REQUEST['cart_img']);

            $savew['u_t']=time();

            $saves =M("mybank")->where(array('cart'=>trim($_REQUEST['cart'])))->data($savew)->save();
             if ($saves !== false){
                 echo json_encode(array('code'=>2016,'msg'=>'绑定成功'));die;
             }else{
                 echo json_encode(array('code'=>5104,'msg'=>'系统繁忙,请稍后再试.'));die;
             }

        }else{

            $data['nickname']=urldecode($_REQUEST['nickname']);

            $data['user_id']=$this->uid;

            $data['cart_img'] = urldecode($_REQUEST['cart_img']);

            $data['bank_id'] = intval($_REQUEST['bankid']);

            $data['cart'] = $_REQUEST['cart'];

            $data['mobile']=$_REQUEST['mobile'];

            $data['idcard']=$_REQUEST['idcard'];

            $data['jq_td_id']=2;

            $data['nature']=2;

            $data['cw_two']=$_REQUEST['cw_two'];

            $data['useful']=$_REQUEST['useful'];

            $data['amount']=$_REQUEST['amount'];

            $data['bill_day']=$_REQUEST['bill_day'];

            $data['refund_day']=$_REQUEST['refund_day'];

            $data['status']=1;

            $data['is_ztype']=intval($_REQUEST['is_ztype']);

            $data['type'] = 2;

            $data['jq_status']=3;

            $data['t']=time();

            //dump($data);die;

//            $Info['name']=$data['nickname'];//持卡人姓名

//            $Info['idNo']=$data['idcard'];//持卡人身份证

//            $Info['accountNo']=$data['cart'];//银行卡号

//            $Info['bankPreMobile']=$data['mobile'];//银行预留号码

//            unset($data['code']);

            $res = M('mybank')->add($data);

            if ($res){

                echo json_encode(array('code'=>2016,'msg'=>'绑定成功'));die;

            }else{

                echo json_encode(array('code'=>5104,'msg'=>'数据错误,再试一次'));die;

            }

        }

    }

   //测试
    public function newBindcreditTwo(){

        $uid = $this->uid;

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$this->uid,

            'cart_img'=>urldecode($_REQUEST['cart_img']),

            'bankid'=>$_REQUEST['bankid'],

            'nickname'=>urldecode($_REQUEST['nickname']),

            'idcard'=>$_REQUEST['idcard'],

            'cart'=>$_REQUEST['cart'],

            'mobile'=>$_REQUEST['mobile'],

            'code'=>$_REQUEST['code'],

            'cw_two'=>$_REQUEST['cw_two'],

            'useful'=>$_REQUEST['useful'],

            'amount'=>$_REQUEST['amount'],

            'bill_day'=>$_REQUEST['bill_day'],

            'refund_day'=>$_REQUEST['refund_day'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        echo json_encode(array('data'=>$msg));die;

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        /*

         * 逻辑

         * */

        $re = M('mobleyzm')->where(array('phone'=>$_REQUEST['mobile'],'type'=>3))->find();

        if ($re['code'] != $_REQUEST['code']){

            echo json_encode(array('code'=>4203,'msg'=>'验证码错误'));die;

        }

        $mywhere['cart'] = trim($_REQUEST['cart']);

        $mywhere['jq_status'] = array('eq',3);

        $mybankinfo = M("mybank")->where($mywhere)->getField('mybank_id');

//            dump($mybankinfo);die;



        if(!empty($mybankinfo))

        {

            echo json_encode(array('code'=>3,'msg'=>'此银行卡已绑定了!请勿重新绑定'));die;

        }

        //调用认证

        $requsetId = 'SS'.date('Ymdhis',time());

        $timestamp = date('Ymdhis',time());

        $pdata=array(

            'name'=>urldecode($_REQUEST['nickname']),

            'idNumber'=>$_REQUEST['idcard'],

            'bankCard'=>$_REQUEST['cart'],

            'mobile'=>$_REQUEST['mobile'],

            'requestId'=>$requsetId,

            'timestamp' =>$timestamp,

        );

        $ppp = R("Payapi/AuthApi/AuthBank",array($pdata,$uid));

        if ($ppp['res_code'] == '5555'){

            echo json_encode(array('code'=>5555,'msg'=>$ppp['res_msg']));die;

        }elseif($ppp['res_code'] == '9999'){

            echo json_encode(array('code'=>9999,'msg'=>$ppp['res_msg']));die;

        }



        # 判断此银行卡是否解绑过，解绑过，则save，没有绑定过,则add

        $ifjb = M("mybank")->where(array('cart'=>trim($_REQUEST['cart']),'jq_status'=>1))->getField('mybank_id');



        if($ifjb)

        {

            $arg='';$url='';



            foreach($this->parem as $key=>$val){



                //$arg.=$key."=".urlencode($val)."&amp;";



                $arg.=$key."=".urlencode($val)."&amp;";



            }



            $url.= $arg;





            $str=rtrim($url, "&amp;");



            $str=str_replace("&amp;","&",$str);

            $savew['cw_two']=$_REQUEST['cw_two'];

            $savew['useful']=$_REQUEST['useful'];

            $savew['amount']=$_REQUEST['amount'];

            $savew['bill_day']=$_REQUEST['bill_day'];

            $savew['refund_day']=$_REQUEST['refund_day'];

            $savew['jq_status']=3;

            $savew['u_t']=time();

//            echo json_encode(array('data'=>$str,'data2'=>$savew));die;

            M("mybank")->where(array('cart'=>trim($_REQUEST['cart'])))->save($savew);

            echo json_encode(array('code'=>2016,'msg'=>'绑定成功'));die;

        }else{

            $data['nickname']=urldecode($_REQUEST['nickname']);

            $data['user_id']=$this->uid;

            $data['cart_img'] = urldecode($_REQUEST['cart_img']);

            $data['bank_id'] = $_REQUEST['bankid'];

            $data['cart'] = $_REQUEST['cart'];

            $data['mobile']=$_REQUEST['mobile'];

            $data['idcard']=$_REQUEST['idcard'];

            $data['jq_td_id']=2;

            $data['nature']=2;

            $data['cw_two']=$_REQUEST['cw_two'];

            $data['useful']=$_REQUEST['useful'];

            $data['amount']=$_REQUEST['amount'];

            $data['bill_day']=$_REQUEST['bill_day'];

            $data['refund_day']=$_REQUEST['refund_day'];

            $data['status']=1;

            $data['is_ztype']=$_REQUEST['is_ztype'];

            $data['type'] = 2;

            $data['jq_status']=3;

            $data['t']=time();

            $data['u_t']=time();

            //dump($data);die;

//            $Info['name']=$data['nickname'];//持卡人姓名

//            $Info['idNo']=$data['idcard'];//持卡人身份证

//            $Info['accountNo']=$data['cart'];//银行卡号

//            $Info['bankPreMobile']=$data['mobile'];//银行预留号码

            unset($data['code']);

            $res = M('mybank')->add($data);

            if ($res){

                echo json_encode(array('code'=>2016,'msg'=>'绑定成功'));die;

            }else{

                echo json_encode(array('code'=>5104,'msg'=>'数据错误,再试一次'));die;

            }

        }

    }

    //新鉴权通道(储蓄卡) 第一次绑定的结算卡接口(最新)
    public function newBindBankTwo(){

        $uid = $_REQUEST['uid'];

        $signT = $_REQUEST['sign'];

        if($_REQUEST['children_id'] == ''){
            echo json_encode(array('code'=>4203,'msg'=>'支行ID为空'));die;
        }
        if($_REQUEST['province_id'] == ''){
            echo json_encode(array('code'=>4203,'msg'=>'省ID为空'));die;
        }
        if($_REQUEST['city_id'] == ''){
            echo json_encode(array('code'=>4203,'msg'=>'城市ID为空'));die;
        }
        if($_REQUEST['line'] == ''){
            echo json_encode(array('code'=>4203,'msg'=>'联行号不存在'));die;
        }

        if (!$signT){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$uid,

            'bankCard'=>$_REQUEST['bankCard'],

            'name'=>urldecode($_REQUEST['name']),

            'idNumber'=>$_REQUEST['idNumber'],

            'mobile'=>$_REQUEST['mobile'],

            'code'=>$_REQUEST['code'],

            'cart_img'=>urldecode($_REQUEST['cart_img']),

            'bankid'=>$_REQUEST['bankid'],

            'children_id'=>$_REQUEST['children_id'],

             'province_id'=>$_REQUEST['province_id'],

            'city_id'=>$_REQUEST['city_id'],

            'line'=>$_REQUEST['line'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        dump($msg);die;

        if ($signT !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        /*

         * 逻辑

         *

         * */

        $re = M('mobleyzm')->where(array('phone'=>$_REQUEST['mobile'],'type'=>3))->find();

        if ($re['code'] != $_REQUEST['code']){

            echo json_encode(array('code'=>4203,'msg'=>'验证码错误'));die;

        }

        $mywhere['cart'] = trim($_REQUEST['bankCard']);

        $mywhere['jq_status'] = 3; # 已绑定成功

        $mybankinfo = M("mybank")->where($mywhere)->getField('mybank_id');

//            dump($mybankinfo);die;

        if(!empty($mybankinfo))

        {

            echo json_encode(array('code'=>3,'msg'=>'此银行卡已绑定了!请勿重新绑定'));die;

        }



        $requsetId = 'SS'.date('Ymdhis',time());

        $timestamp = date('Ymdhis',time());

        $pdata=array(

            'name'=>urldecode($_REQUEST['name']),

            'idNumber'=>$_REQUEST['idNumber'],

            'bankCard'=>$_REQUEST['bankCard'],

            'mobile'=>$_REQUEST['mobile'],

            'requestId'=>$requsetId,

            'timestamp' => $timestamp,

        );

//                dump($pdata);die;

        $ppp = R("Payapi/AuthApi/AuthBank",array($pdata,$uid));

        if ($ppp['res_code'] == '5555'){

            echo json_encode(array('code'=>5555,'msg'=>$ppp['res_msg']));die;

        }elseif($ppp['res_code'] == '9999'){

            echo json_encode(array('code'=>9999,'msg'=>$ppp['res_msg']));die;

        }



        $mybankinfo = M("mybank")->where(array('cart'=>trim($_REQUEST['bankCard']),'jq_status'=>1))->find();

        if($mybankinfo)

        {

            $mybankinfo['bank_children_id']=$_REQUEST['children_id']; //支行

            $mybankinfo['province_id']=$_REQUEST['province_id'];  //省

            $mybankinfo['city_id']=$_REQUEST['city_id'];//市

            $mybankinfo['area_id']=$_REQUEST['area_id'];//区

            $mybankinfo['lianhang']=$_REQUEST['line']; //联行号

            $mybankinfo['jq_status']=3;

            $mybankinfo['is_normal']=1;


            $res = M("mybank")->where(array('mybank_id'=>$mybankinfo['mybank_id']))->save($mybankinfo);

            if ($res !== false){

                echo json_encode(array('code'=>2016,'msg'=>'绑定成功'));die;

            }else{

                echo json_encode(array('code'=>5012,'msg'=>'系统繁忙'));die;

            }

        }else{

            // 绑定银行卡 ---储蓄卡

            $data['user_id'] = $this->uid;  //1

            $data['nickname']=urldecode($_REQUEST['name']);  //1

            $data['cart_img'] = urldecode($_REQUEST['cart_img']);  //1

            $data['cart']=$_REQUEST['bankCard'];   // 1

            $data['idcard']=$_REQUEST['idNumber'];  //1

            $data['bank_id'] = intval($_REQUEST['bankid']);  //1

            $data['mobile']=$_REQUEST['mobile'];   //1

            $data['bank_children_id']=intval($_REQUEST['children_id']); //支行

            $data['province_id']=intval($_REQUEST['province_id']);  //省

            $data['city_id']=intval($_REQUEST['city_id']);//市

            $data['area_id']=intval($_REQUEST['area_id']);//区

            $data['lianhang']=$_REQUEST['line']; //联行号

            $data['jq_td_id']=2;

            $data['status'] = 1;

            $data['type'] = 1;

            $data['is_normal']=1;

            $data['jq_status']=3;

            $data['nature']=2;

            $data['t'] = time();

            $data['type'] = 1;//储蓄卡

            unset($data['code']);

            //保存银行卡信息

            $res = M('mybank')->add($data);

            if ($res !== false){  //成功
               $phone= M('user')->field('phone,utype')->where(array('user_id'=>$this->uid))->find();
//            echo json_encode(array('code'=>3,'msg'=>'已提交绑定'));die;
                if($phone['utype'] == '1'){ //兜客
                    $msg="尊敬的".$phone['phone']."，您已成功绑卡，兜客账户已开通！客服 4008-272-278";
                    $infooo = R("Func/Func/send_mobile",array($phone['phone'],$msg));
                }else{  //推客
                    $msg="尊敬的".$phone['phone']."，您已成功绑卡，请尽快缴费开通！客服 4008-272-278";
                    $infooo = R("Func/Func/send_mobile",array($phone['phone'],$msg));
                }
                echo json_encode(array('code'=>2016,'msg'=>'储蓄卡绑定成功'));die;
            }else{
                $phone= M('user')->field('phone,utype')->where(array('user_id'=>$this->uid))->find();
//            echo json_encode(array('code'=>3,'msg'=>'已提交绑定'));die;
                if($phone['utype'] == '1'){ //兜客
                    $msg="尊敬的".$phone['phone']."，您绑卡不成功，请核实再进行绑定！客服 4008-272-278";
                    $infooo = R("Func/Func/send_mobile",array($phone['phone'],$msg));
                }else{  //推客
                    $msg="尊敬的".$phone['phone']."，您绑卡不成功，请核实再进行绑定！客服 4008-272-278";
                    $infooo = R("Func/Func/send_mobile",array($phone['phone'],$msg));
                }
                echo json_encode(array('code'=>5104,'msg'=>'数据错误,再试一次'));die;

            }

        }

    }

    //实名认证图片上传接口

    public function idcartImage(){

        if (IS_POST){

            if($_FILES){

                $upload = new \Think\Upload();// 实例化上传类

                $upload->maxSize   =  6*1024*1024 ;// 设置附件上传大小

                $upload->exts      =  array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型

                $upload->savePath  =  ''; // 设置附件上传目录

                $upload->subName = array('date','Y/m/d'); // 采用date函数生成命名规则 传入Y-m-d参数

                // 上传单个文件

                $info   =   $upload->upload();

                // var_dump($info);exit;

                if(!$info) {// 上传错误提示错误信息

//                             $this->error($upload->getError());

                    echo json_encode(array('code' => 2015, 'msg' => $upload->getError())); die;

                }else{



                    $data['images'] = $info[0]['savepath'].$info[0]['savename'];

                    $image = new \Think\Image();

                    if ($image->open('./Uploads/' . $data['images'])) {

                        $image->thumb(100, 100)->save('./Uploads/' . $data['images'] . '_thumb.jpg');

                    };

                    echo json_encode(array('code'=>5020,'msg'=>'上传成功','image'=>$info[0]['savepath'].$info[0]['savename']));

                }

            }else{

                echo json_encode(array('code'=>5011,'msg'=>'没有上传的图片'));die;

            }

        }

    }

    //实名认证(人工)(测试)

    public function idcartcs(){

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }



        $array = array(

            'uid'=>$this->uid,

            'nick_name'=>urldecode($_REQUEST['nick_name']),

            'idcard'=>$_REQUEST['idcard'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        dump($msg);die;

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        if(IS_POST){

            $data = I('post.');

            if($_FILES['card_zm']['error'] == 0){

                if($_FILES['card_fm']['error'] == 0){

                    if($_FILES['card_sczm']['error'] == 0){

                        $upload = new \Think\Upload();// 实例化上传类

                        $upload->maxSize   =  6*1024*1024 ;// 设置附件上传大小

                        $upload->exts      =  array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型

                        $upload->savePath  =  ''; // 设置附件上传目录

                        $upload->subName = array('date','Y/m/d'); // 采用date函数生成命名规则 传入Y-m-d参数

                        // 上传单个文件

                        $info   =   $upload->upload();

                        // var_dump($info);exit;

                        if(!$info){    // 上传错误提示错误信息

//                             $this->error($upload->getError());

                            echo json_encode(array('code'=>2015,'msg'=>$upload->getError()));die;

                        }else{

                            $data['card_zm'] = $info['card_zm']['savepath'].$info['card_zm']['savename'];

                            $data['card_fm'] = $info['card_fm']['savepath'].$info['card_fm']['savename'];

                            $data['card_sczm'] = $info['card_sczm']['savepath'].$info['card_sczm']['savename'];

                            // var_dump($data);exit;

                            $image = new \Think\Image();

                            if ($image->open('./Uploads/' . $data['card_zm'])) {

                                $image->thumb(100, 100)->save('./Uploads/' . $data['card_zm'] . '_thumb.jpg');

                            };



                            if ($image->open('./Uploads/' . $data['card_fm']))

                            {

                                $image->thumb(100, 100)->save('./Uploads/' . $data['card_fm'] . '_thumb.jpg');

                            };

                            if($image->open('./Uploads/'.$data['card_sczm']))

                            {

                                $image->thumb(100, 100)->save('./Uploads/' . $data['card_sczm'] . '_thumb.jpg');

                            };



                            //调用认证

                            $requestId = 'SS'.date("YmdHis",time());

                            $timestamp = date("YmdHis",time());

                            $pdata = array(

                                'name' => urldecode($data['nick_name']),  // 姓名

                                'idNumber' => $_REQUEST['idcard'],  // 身份证

                                'requestId' => $requestId, // each : 'ab__'.date('Ymdhis',time()); // 订单号

                                'timestamp' => $timestamp //时间戳

                            );

                            $renZ = R("Payapi/AuthApi/AuthRealname",array($pdata,$this->uid));

//                            dump($res);

                            if ($renZ['res_code'] == '5555'){

                                echo json_encode(array('code'=>5555,'msg'=>$renZ['res_msg']));die;



                            }elseif($renZ['res_code'] == '9999'){

                                echo json_encode(array('code'=>9999,'msg'=>$renZ['res_msg']));die;

                            }



                            $resss = M('myrealname')->where(array('user_id'=>$this->uid))->find();

                            if ($resss){

                                $resss['nickname']=urldecode($data['nick_name']);

                                $resss['idcard']=$_REQUEST['idcard'];

                                $resss['status']=2;

                                $resss['user_id']=$this->uid;

                                $data['sh_type']=2;

                                $resss['t']=time();

                                $resss['card_zm'] = $info['card_zm']['savepath'].$info['card_zm']['savename'];

                                $resss['card_fm'] = $info['card_fm']['savepath'].$info['card_fm']['savename'];

                                $resss['card_sczm'] = $info['card_sczm']['savepath'].$info['card_sczm']['savename'];

                                $w = array(

                                    'card_zm'=>$data['card_zm'],

                                    'card_fm'=>$data['card_fm'],

                                    'card_sczm'=>$data['card_sczm']

                                );

                                $res =  M('myrealname')->where(array('user_id'=>$this->uid))->save($resss);

                                if($res){

                                    echo json_encode(array('code'=>2012,'msg'=>'提交实名认证成功','img'=>enThumb("./Uploads",$w))); exit();

                                }else{

                                    echo json_encode(array('code'=>5010,'msg'=>'提交认证失败')); exit();

                                }

                            }else{

                               $oneInfo= M('myrealname')->where(array('idcard'=>$data['idcard']))->find();

                                if($oneInfo){

                                     echo json_encode(array('code'=>5015,'msg'=>'该身份证已被认证,请重新提交身份证'));

                                     exit();

                                }

                               $data['t']= time();

                                $w = array(

                                    'card_zm'=>$data['card_zm'],

                                    'card_fm'=>$data['card_fm'],

                                    'card_sczm'=>$data['card_sczm']

                                );

                                $data['nickname']=urldecode($data['nick_name']);

                                $data['sh_type']=2;

                                $data['status']=2;

                                $data['user_id']=$this->uid;

                                $res = M('myrealname')->add($data);

                                if($res){

                                    echo json_encode(array('code'=>2012,'msg'=>'提交实名认证成功','img'=>enThumb("./Uploads",$w))); exit();

                                }else{

                                    echo json_encode(array('code'=>5010,'msg'=>'提交认证失败')); exit();

                                }

                            }

                        }

                    }else{

                        echo json_encode(array('code'=>5011,'msg'=>'请提交相关证件照片')); exit();

                    }

                }else{

                    echo json_encode(array('code'=>5011,'msg'=>'请提交相关证件照片')); exit();

                }

            }else{

                echo json_encode(array('code'=>5011,'msg'=>'请提交相关证件照片')); exit();

            }

        }

    }

    //实名认证活体接口

    public function idcart(){

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }



        $array = array(

            'uid'=>$this->uid,

            'nick_name'=>urldecode($_REQUEST['nick_name']),

            'idcard'=>$_REQUEST['idcard'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        dump($msg);die;

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        if(IS_POST){

            $data = I('post.');

            if(!$data['status']){

                echo json_encode(array('code'=>5018,'msg'=>'请先进行活体认证后提交'));die;

            }



            if($_FILES['card_zm']['error'] == 0){

                if($_FILES['card_fm']['error'] == 0){

                    if($_FILES['card_sczm']['error'] == 0){

                        $upload = new \Think\Upload();// 实例化上传类

                        $upload->maxSize   =  6*1024*1024 ;// 设置附件上传大小

                        $upload->exts      =  array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型

                        $upload->savePath  =  ''; // 设置附件上传目录

                        $upload->subName = array('date','Y/m/d'); // 采用date函数生成命名规则 传入Y-m-d参数

                        // 上传单个文件

                        $info   =   $upload->upload();

                        // var_dump($info);exit;

                        if(!$info){    // 上传错误提示错误信息

//                             $this->error($upload->getError());

                            echo json_encode(array('code'=>2015,'msg'=>$upload->getError()));die;

                        }else{

                            $data['card_zm'] = $info['card_zm']['savepath'].$info['card_zm']['savename'];

                            $data['card_fm'] = $info['card_fm']['savepath'].$info['card_fm']['savename'];

                            $data['card_sczm'] = $info['card_sczm']['savepath'].$info['card_sczm']['savename'];

                            // var_dump($data);exit;

                            $image = new \Think\Image();

                            if ($image->open('./Uploads/' . $data['card_zm'])) {

                                $image->thumb(100, 100)->save('./Uploads/' . $data['card_zm'] . '_thumb.jpg');

                            };



                            if ($image->open('./Uploads/' . $data['card_fm']))

                            {

                                $image->thumb(100, 100)->save('./Uploads/' . $data['card_fm'] . '_thumb.jpg');

                            };

                            if($image->open('./Uploads/'.$data['card_sczm']))

                            {

                                $image->thumb(100, 100)->save('./Uploads/' . $data['card_sczm'] . '_thumb.jpg');

                            };



                            //调用认证

                            $requestId = 'SS'.date("YmdHis",time());

                            $timestamp = date("YmdHis",time());

                            $pdata = array(

                                'name' => urldecode($data['nick_name']),  // 姓名

                                'idNumber' => $_REQUEST['idcard'],  // 身份证

                                'requestId' => $requestId, // each : 'ab__'.date('Ymdhis',time()); // 订单号

                                'timestamp' => $timestamp //时间戳

                            );

                            $renZ = R("Payapi/AuthApi/AuthRealname",array($pdata,$this->uid));

//                            dump($res);

                            if ($renZ['res_code'] == '5555'){

                                echo json_encode(array('code'=>5555,'msg'=>$renZ['res_msg']));die;

                            }elseif($renZ['res_code'] == '9999'){

                                echo json_encode(array('code'=>9999,'msg'=>$renZ['res_msg']));die;

                            }

                            $resss = M('myrealname')->where(array('user_id'=>$this->uid))->find();



                            if ($resss){

                                $resss['nickname']=urldecode($data['nick_name']);

                                $resss['idcard']=$_REQUEST['idcard'];

                                $resss['status']=trim($_REQUEST['status']);

                                if($_REQUEST['status'] == 1){

                                 $resss['success_time']=time();

                                }

                                $resss['user_id']=$this->uid;

                                $resss['sh_type']=1;

                                $resss['t']=time();

                                $resss['card_zm'] = $info['card_zm']['savepath'].$info['card_zm']['savename'];

                                $resss['card_fm'] = $info['card_fm']['savepath'].$info['card_fm']['savename'];

                                $resss['card_sczm'] = $info['card_sczm']['savepath'].$info['card_sczm']['savename'];

                                $w = array(

                                    'card_zm'=>$data['card_zm'],

                                    'card_fm'=>$data['card_fm'],

                                    'card_sczm'=>$data['card_sczm']

                                );

                                $res =  M('myrealname')->where(array('user_id'=>$this->uid))->save($resss);

                                if($res !== false){

                                    if ($_REQUEST['status'] == 1){
                                        $phone = M('user')->where(array('user_id'=>$this->uid))->getField('phone');
//                                        $phone='13548750126';
                                        $msg="尊敬的".$phone."，您的实名认证已通过，请继续完成操作！客服 4008-272-278";
                                        $infooo = R("Func/Func/send_mobile",array($phone,$msg));
                                    }else{
                                        $phone = M('user')->where(array('user_id'=>$this->uid))->getField('phone');
//                                        $phone='13548750126';
                                        $msg="尊敬的".$phone."，您的实名认证不通过，已转人工操作！客服 4008-272-278";
                                        $infooo = R("Func/Func/send_mobile",array($phone,$msg));
                                    }

                                    echo json_encode(array('code'=>2012,'msg'=>'提交实名认证成功','img'=>enThumb("./Uploads",$w))); exit();

                                }else{

                                    if($_REQUEST['status'] == 1){
                                        $phone = M('user')->where(array('user_id'=>$this->uid))->getField('phone');
//                                        $phone='13548750126';
                                        $msg="尊敬的".$phone."，您的实名认证已通过，请继续完成操作！客服 4008-272-278";
                                        $infooo = R("Func/Func/send_mobile",array($phone,$msg));
                                    }else{
                                        $phone = M('user')->where(array('user_id'=>$this->uid))->getField('phone');
//                                        $phone='13548750126';
                                        $msg="尊敬的".$phone."，您的实名认证不通过，已转人工操作！客服 4008-272-278";
                                        $infooo = R("Func/Func/send_mobile",array($phone,$msg));
                                    }

                                    echo json_encode(array('code'=>5010,'msg'=>'提交认证失败')); exit();

                                }

                            }else{

                                $oneInfo= M('myrealname')->where(array('idcard'=>$data['idcard']))->find();

                                if($oneInfo){

                                    echo json_encode(array('code'=>5015,'msg'=>'该身份证已被认证,请重新提交身份证'));

                                    exit();

                                }

                                $data['t']= time();

                                $w = array(

                                    'card_zm'=>$data['card_zm'],

                                    'card_fm'=>$data['card_fm'],

                                    'card_sczm'=>$data['card_sczm']

                                );

                                $data['nickname']=urldecode($data['nick_name']);

                                $data['user_id']=$this->uid;

                                if($data['status'] == 1){

                                    $data['success_time']=time();

                                }

                                $res = M('myrealname')->add($data);

                                if($res !== false){

                                    if($_REQUEST['status'] == 1){
                                        $phone = M('user')->where(array('user_id'=>$this->uid))->getField('phone');
//                                        $phone='13548750126';
                                        $msg="尊敬的".$phone."，您的实名认证已通过，请继续完成操作！客服 4008-272-278";
                                        $infooo = R("Func/Func/send_mobile",array($phone,$msg));
                                    }else{
                                        $phone = M('user')->where(array('user_id'=>$this->uid))->getField('phone');
//                                        $phone='13548750126';
                                        $msg="尊敬的".$phone."，您的实名认证不通过，已转人工操作！客服 4008-272-278";
                                        $infooo = R("Func/Func/send_mobile",array($phone,$msg));
                                    }

                                    echo json_encode(array('code'=>2012,'msg'=>'提交实名认证成功','img'=>enThumb("./Uploads",$w))); exit();

                                }else{

                                    if($_REQUEST['status'] == 1){
                                        $phone = M('user')->where(array('user_id'=>$this->uid))->getField('phone');
//                                        $phone='13548750126';
                                        $msg="尊敬的".$phone."，您的实名认证已通过，请继续完成操作！客服 4008-272-278";
                                        $infooo = R("Func/Func/send_mobile",array($phone,$msg));
                                    }else{
                                        $phone = M('user')->where(array('user_id'=>$this->uid))->getField('phone');
//                                        $phone='13548750126';
                                        $msg="尊敬的".$phone."，您的实名认证不通过，已转人工操作！客服 4008-272-278";
                                        $infooo = R("Func/Func/send_mobile",array($phone,$msg));
                                    }

                                    echo json_encode(array('code'=>5010,'msg'=>'提交认证失败')); exit();

                                }

                            }

                        }

                    }else{

                        echo json_encode(array('code'=>5011,'msg'=>'请提交相关证件照片')); exit();

                    }

                }else{

                    echo json_encode(array('code'=>5011,'msg'=>'请提交相关证件照片')); exit();

                }

            }else{

                echo json_encode(array('code'=>5011,'msg'=>'请提交相关证件照片')); exit();

            }

        }

    }

    //实名认证(推客活体)

    public function tkhuoidcart(){

        if (!$this->sign){

              echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        

        $array = array(

            'uid'=>$this->uid,

            'nick_name'=>urldecode($_REQUEST['nick_name']),

            'idcard'=>$_REQUEST['idcard'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        //        dump($msg);die;

        if ($this->sign !== $msg){

             echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

          R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        

        $whereuser['user_id']=array('eq',$this->uid);

        $userinfo=M('user')->where($whereuser)->find();

        if($userinfo['utype']!=20)

        {

            echo json_encode(array('code'=>10004,'msg'=>'该用户不是推客'));die;

        }

        

        if(IS_POST){

            $data = I('post.');

            $upstate= $data['status'];

            if($upstate==1)

            {

                $status=4;
                $agent_status=3;
            }else {
                $agent_status=4;
                $status=2;

            }

            

            if($_FILES['card_zm']['error'] == 0){

                if($_FILES['card_fm']['error'] == 0){

                    if($_FILES['card_sczm']['error'] == 0){

                        $upload = new \Think\Upload();// 实例化上传类

                        $upload->maxSize   =  6*1024*1024 ;// 设置附件上传大小

                        $upload->exts      =  array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型

                        $upload->savePath  =  ''; // 设置附件上传目录

                        $upload->subName = array('date','Y/m/d'); // 采用date函数生成命名规则 传入Y-m-d参数

                        // 上传单个文件

                        $info   =   $upload->upload();

                        // var_dump($info);exit;

                        if(!$info){    // 上传错误提示错误信息

                            //                             $this->error($upload->getError());

                            echo json_encode(array('code'=>2015,'msg'=>$upload->getError()));die;

                        }else{

                            $data['card_zm'] = $info['card_zm']['savepath'].$info['card_zm']['savename'];

                            $data['card_fm'] = $info['card_fm']['savepath'].$info['card_fm']['savename'];

                            $data['card_sczm'] = $info['card_sczm']['savepath'].$info['card_sczm']['savename'];


                            $myImg=M('myrealname')->where(array('user_id'=>$this->uid))->find();

                            if($myImg){
                                //删除原来的
                                $pzm='./Uploads/'.$myImg['card_zm'];
                                $pfm='./Uploads/'.$myImg['card_fm'];
                                $psczm='./Uploads/'.$myImg['card_sczm'];
                                @unlink($pzm);
                                @unlink($pfm);
                                @unlink($psczm);
                            }


                            // var_dump($data);exit;

                            $image = new \Think\Image();

                            if ($image->open('./Uploads/' . $data['card_zm'])) {

                                $image->thumb(100, 100)->save('./Uploads/' . $data['card_zm'] . '_thumb.jpg');

                            };

                            

                            if ($image->open('./Uploads/' . $data['card_fm']))

                            {

                                $image->thumb(100, 100)->save('./Uploads/' . $data['card_fm'] . '_thumb.jpg');

                            };

                            if($image->open('./Uploads/'.$data['card_sczm']))

                            {

                                $image->thumb(100, 100)->save('./Uploads/' . $data['card_sczm'] . '_thumb.jpg');

                            };

                            

                            //调用认证

                            $requestId = 'SS'.date("YmdHis",time());

                            $timestamp = date("YmdHis",time());

                            $pdata = array(

                                'name' => urldecode($data['nick_name']),  // 姓名

                                'idNumber' => $_REQUEST['idcard'],  // 身份证

                                'requestId' => $requestId, // each : 'ab__'.date('Ymdhis',time()); // 订单号

                                'timestamp' => $timestamp //时间戳

                            );

                            $renZ = R("Payapi/AuthApi/AuthRealname",array($pdata,$this->uid));

                            //                            dump($res);

                            if ($renZ['res_code'] == '5555'){

                                echo json_encode(array('code'=>5555,'msg'=>$renZ['res_msg']));die;

                            }elseif($renZ['res_code'] == '9999'){

                                echo json_encode(array('code'=>9999,'msg'=>$renZ['res_msg']));die;

                            }

                            

                            $resss = M('myrealname')->where(array('user_id'=>$this->uid))->find();

                            if ($resss){

                                

                                $resss['nickname']=urldecode($data['nick_name']);

                                $resss['idcard']=$_REQUEST['idcard'];

                                $resss['status']=$upstate;

                                $resss['user_id']=$this->uid;

                                $resss['t']=time();

                                $resss['sh_type']=1;

                                $op_status=3;

                                $mark="";

                                if($status==4) {

                                    $op_status=7;

                                    $mark="活体认证通过";

                                    $resss['success_time']=time();

                                    $userdate['mch_status']=1;

                                    $userress = M('user')->where(array('user_id'=>$this->uid))->save($userdate);

                                }else{

                                    $op_status=7;

                                    $mark="活体认证不通过,转人工";

                                }

                              

                                $resss['card_zm'] = $info['card_zm']['savepath'].$info['card_zm']['savename'];

                                $resss['card_fm'] = $info['card_fm']['savepath'].$info['card_fm']['savename'];

                                $resss['card_sczm'] = $info['card_sczm']['savepath'].$info['card_sczm']['savename'];

                                $w = array(

                                    'card_zm'=>$data['card_zm'],

                                    'card_fm'=>$data['card_fm'],

                                    'card_sczm'=>$data['card_sczm']

                                );

                                $res =  M('myrealname')->where(array('user_id'=>$this->uid))->save($resss);

                                if($res !== false){

                                    if ($_REQUEST['status'] == 1){
                                        $phone = M('user')->where(array('user_id'=>$this->uid))->getField('phone');
//                                        $phone='13548750126';
                                        $msg="尊敬的".$phone."，您的实名认证已通过，请继续完成操作！客服 4008-272-278";
                                        $infooo = R("Func/Func/send_mobile",array($phone,$msg));
                                    }else{
                                        $phone = M('user')->where(array('user_id'=>$this->uid))->getField('phone');
//                                        $phone='13548750126';
                                        $msg="尊敬的".$phone."，您的实名认证不通过，已转人工操作！客服 4008-272-278";
                                        $infooo = R("Func/Func/send_mobile",array($phone,$msg));
                                    }

                                    $tt['username'] = $resss['nickname'];

                                    $tt['idcard'] = $resss['idcard'];

                                    $tt['status'] = $agent_status;

                                    $tt['type'] = 1;

                                    $tt['t'] = time();

                                    $tt['tk_weixin'] = "";

                                    $tt['zmPic'] =  $resss['card_zm'];

                                    $tt['fmPic'] = $resss['card_fm'];

                                    $tt['sczmPic'] = $resss['card_sczm'];

                                    $tt['bankzmPic'] = "";

                                    $tt['beizhu'] = "推客活体实名认证";

                                    $tt['user_id'] = $this->uid;

                                    $tt['mold'] = 2;

                                    $whereagent['user_id'] = array('eq',$this->uid);

                                    $whereagent['mold'] = 2;

                                    $ishasagent=M('agent')->where($whereagent)->find();

                                    //插入推客审核

//                                    if($ishasagent)
//
//                                    {
//
//                                        $res22 = M('agent') ->where($whereagent) ->save($tt);
//
//                                    }else{

                                        $res22 = M('agent') ->add($tt);  

//                                    }

                                    if($res22)

                                    {

                                        
                                        //添加 推客 记录表
                                        $dateopt['op_status']=$op_status;

                                        $dateopt['user_id']=$this->uid;

                                        $dateopt['mark']=$mark;

                                        $dateopt['time']=time();

                                        $dateopt['send_mail']=1;

                                        $dateopt['send_phone']=1;

                                        $dateopt['op_name']=$tt['username'];

                                        $userinfo=M('user')->where($whereagent)->find();

                                        $jiInfo=M('institution')->where(array('institution_id'=>$userinfo['institution_id']))->find();

                                        $dateopt['op_level']=$jiInfo['username'];

                                        $dateopt['tk_level']=$userinfo['level'];

                                        $res23 = M('user_operation')->add($dateopt);

                                        if($res23 !== false){
                                            echo json_encode(array('code'=>2012,'msg'=>'提交实名认证成功','img'=>enThumb("./Uploads",$w))); exit();
                                        }else{
                                            echo json_encode(array('code'=>5010,'msg'=>'提交认证失败')); exit();
                                        }

                                    }

                                    

                                }else{

                                    echo json_encode(array('code'=>5010,'msg'=>'提交认证失败')); exit();

                                }

                            }else{

                                $oneInfo= M('myrealname')->where(array('idcard'=>$data['idcard']))->find();

                                if($oneInfo){

                                    echo json_encode(array('code'=>5015,'msg'=>'该身份证已被认证,请重新提交身份证'));

                                    exit();

                                }

                                $data['t']= time();

                                $w = array(

                                    'card_zm'=>$data['card_zm'],

                                    'card_fm'=>$data['card_fm'],

                                    'card_sczm'=>$data['card_sczm']

                                );

                                $data['nickname']=urldecode($data['nick_name']);

//                                $data['status']=2;   //不要写死

                                $data['sh_type']=1;

                                $op_status=3;

                                $mark="";

                                if($status==4){
                                    $op_status=7;
                                    $op_status=4;

                                    $mark="活体认证通过";

                                    $data['success_time']=time();

                                    $userdate['mch_status']=1;

                                    $userress = M('user')->where(array('user_id'=>$this->uid))->save($userdate);

                                }else{
                                    $op_status=7;

                                    $op_status=3;

                                    $mark="活体认证失败，转人工审核";

                                }

                               

                                $data['user_id']=$this->uid;

                                $res = M('myrealname')->add($data);

                                if($res){

                                    if ($_REQUEST['status'] == 1){
                                        $phone = M('user')->where(array('user_id'=>$this->uid))->getField('phone');
//                                        $phone='13548750126';
                                        $msg="尊敬的".$phone."，您的实名认证已通过，请继续完成操作！客服 4008-272-278";
                                        $infooo = R("Func/Func/send_mobile",array($phone,$msg));
                                    }else{
                                        $phone = M('user')->where(array('user_id'=>$this->uid))->getField('phone');
//                                        $phone='13548750126';
                                        $msg="尊敬的".$phone."，您的实名认证不通过，已转人工操作！客服 4008-272-278";
                                        $infooo = R("Func/Func/send_mobile",array($phone,$msg));
                                    }

                                    $tt['username'] = $data['nickname'];

                                    $tt['idcard'] = $data['idcard'];

                                    $tt['status'] = $agent_status;

                                    $tt['type'] = 1;

                                    $tt['t'] = time();

                                    $tt['tk_weixin'] = "";

                                    $tt['zmPic'] =  $data['card_zm'];

                                    $tt['fmPic'] = $data['card_fm'];

                                    $tt['sczmPic'] = $data['card_sczm'];

                                    $tt['bankzmPic'] = "";

                                    $tt['beizhu'] = "推客活体实名认证";

                                    $tt['user_id'] = $this->uid;

//                                    $tt['mold'] = 2;

                                    $whereagent['user_id']=array('eq',$this->uid);
//                                    $whereagent['mold']=2;

//                                    $ishasagent=M('agent')->where($whereagent)->find();

                                    //插入推客审核

//                                    if($ishasagent)
//
//                                    {
//
//                                        $res22 = M('agent') ->where($whereagent) ->save($tt);
//
//                                    }else{

                                        $res22 = M('agent') ->add($tt);

//                                    }

                                    if($res22 !== false)

                                    {

                                        $dateopt['op_status']=$op_status;

                                        $dateopt['user_id']=$this->uid;

                                        $dateopt['mark']=$mark;

                                        $dateopt['time']=time();

                                        $dateopt['send_mail']=2;

                                        $dateopt['send_phone']=2;

                                        $dateopt['op_name']=$tt['username'];

                                        $userinfo=M('user')->where($whereagent)->find();

                                        $jiInfo=M('institution')->where(array('institution_id'=>$userinfo['institution_id']))->find();

                                        $dateopt['op_level']=$jiInfo['username'];

                                        $dateopt['tk_level']=$userinfo['level'];

                                        $res23 = M('user_operation') ->add($dateopt);

                                        echo json_encode(array('code'=>2012,'msg'=>'提交实名认证成功','img'=>enThumb("./Uploads",$w))); exit();

                                    }

                                }else{

                                    echo json_encode(array('code'=>5010,'msg'=>'提交认证失败')); exit();

                                }

                            }

                            

                        }

                    }else{

                        echo json_encode(array('code'=>5011,'msg'=>'请提交相关证件照片')); exit();

                    }

                }else{

                    echo json_encode(array('code'=>5011,'msg'=>'请提交相关证件照片')); exit();

                }

            }else{

                echo json_encode(array('code'=>5011,'msg'=>'请提交相关证件照片')); exit();

            }

        }else {

            echo json_encode(array('code'=>5021,'msg'=>'请用post方式提交')); exit();

        }

    }

    //实名认证(兜客活体)

    public function dkhuoidcart(){

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$this->uid,

            'nick_name'=>urldecode($_REQUEST['nick_name']),

            'idcard'=>$_REQUEST['idcard'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        //        dump($msg);die;

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        $whereuser['user_id']=array('eq',$this->uid);

        $userinfo=M('user')->where($whereuser)->find(); //查找到此兜客

         //APP传参

        if(IS_POST){

            $data = I('post.');

            $upstate= $data['status'];

            if($upstate==1)

            {

                $status=4;

            }else{

                $status=2;

            }



            if($_FILES['card_zm']['error'] == 0){

                if($_FILES['card_fm']['error'] == 0){

                    if($_FILES['card_sczm']['error'] == 0){

                        $upload = new \Think\Upload();// 实例化上传类

                        $upload->maxSize   =  6*1024*1024 ;// 设置附件上传大小

                        $upload->exts      =  array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型

                        $upload->savePath  =  ''; // 设置附件上传目录

                        $upload->subName = array('date','Y/m/d'); // 采用date函数生成命名规则 传入Y-m-d参数

                        // 上传单个文件

                        $info   =   $upload->upload();

                        // var_dump($info);exit;

                        if(!$info){    // 上传错误提示错误信息

                            //                             $this->error($upload->getError());

                            echo json_encode(array('code'=>2015,'msg'=>$upload->getError()));die;

                        }else{

                            $data['card_zm'] = $info['card_zm']['savepath'].$info['card_zm']['savename'];

                            $data['card_fm'] = $info['card_fm']['savepath'].$info['card_fm']['savename'];

                            $data['card_sczm'] = $info['card_sczm']['savepath'].$info['card_sczm']['savename'];


                            $myImg=M('myrealname')->where(array('user_id'=>$this->uid))->find();

                            if($myImg){
                                //删除原来的
                                $pzm='./Uploads/'.$myImg['card_zm'];
                                $pfm='./Uploads/'.$myImg['card_fm'];
                                $psczm='./Uploads/'.$myImg['card_sczm'];
                                @unlink($pzm);
                                @unlink($pfm);
                                @unlink($psczm);
                            }

                            // var_dump($data);exit;

                            $image = new \Think\Image();

                            if ($image->open('./Uploads/' . $data['card_zm'])) {

                                $image->thumb(100, 100)->save('./Uploads/' . $data['card_zm'] . '_thumb.jpg');

                            };

                            if ($image->open('./Uploads/' . $data['card_fm']))

                            {

                                $image->thumb(100, 100)->save('./Uploads/' . $data['card_fm'] . '_thumb.jpg');

                            };

                            if($image->open('./Uploads/'.$data['card_sczm']))

                            {

                                $image->thumb(100, 100)->save('./Uploads/' . $data['card_sczm'] . '_thumb.jpg');

                            };



                            //调用认证

                            $requestId = 'SS'.date("YmdHis",time());

                            $timestamp = date("YmdHis",time());

                            $pdata = array(

                                'name' => urldecode($data['nick_name']),  // 姓名

                                'idNumber' => $_REQUEST['idcard'],  // 身份证

                                'requestId' => $requestId, // each : 'ab__'.date('Ymdhis',time()); // 订单号

                                'timestamp' => $timestamp //时间戳

                            );

                            $renZ = R("Payapi/AuthApi/AuthRealname",array($pdata,$this->uid));

                            //                            dump($res);

                            if ($renZ['res_code'] == '5555'){

                                echo json_encode(array('code'=>5555,'msg'=>$renZ['res_msg']));die;



                            }elseif($renZ['res_code'] == '9999'){

                                echo json_encode(array('code'=>9999,'msg'=>$renZ['res_msg']));die;

                            }



                            $resss = M('myrealname')->where(array('user_id'=>$this->uid))->find();

                            if ($resss){



                                $resss['nickname']=urldecode($data['nick_name']);

                                $resss['idcard']=$_REQUEST['idcard'];

                                $resss['status']=$upstate;

                                $resss['user_id']=$this->uid;

                                $resss['t']=time();

                                $resss['sh_type']=1;

                                $op_status=3;

                                $mark="";

                                if($status==4) {

                                    $op_status=4;

                                    $mark="推客活体审核成功";

                                    $resss['success_time']=time();

                                    $userdate['mch_status']=1;

                                    $userress = M('user')->where(array('user_id'=>$this->uid))->save($userdate);

                                }else {

                                    $op_status=3;

                                    $mark="推客活体审核失败，转人工审核";

                                }



                                $resss['card_zm'] = $info['card_zm']['savepath'].$info['card_zm']['savename'];

                                $resss['card_fm'] = $info['card_fm']['savepath'].$info['card_fm']['savename'];

                                $resss['card_sczm'] = $info['card_sczm']['savepath'].$info['card_sczm']['savename'];

                                $w = array(

                                    'card_zm'=>$data['card_zm'],

                                    'card_fm'=>$data['card_fm'],

                                    'card_sczm'=>$data['card_sczm']

                                );

                                $res =  M('myrealname')->where(array('user_id'=>$this->uid))->save($resss);

                                if($res){

                                    echo json_encode(array('code'=>2012,'msg'=>'提交实名认证成功','img'=>enThumb("./Uploads",$w))); exit();

                                }else{

                                    echo json_encode(array('code'=>5010,'msg'=>'提交认证失败')); exit();

                                }

                            }else{

                                $oneInfo= M('myrealname')->where(array('idcard'=>$data['idcard']))->find();

                                if($oneInfo){

                                    echo json_encode(array('code'=>5015,'msg'=>'该身份证已被认证,请重新提交身份证'));

                                    exit();

                                }

                                $data['t']= time();

                                $w = array(

                                    'card_zm'=>$data['card_zm'],

                                    'card_fm'=>$data['card_fm'],

                                    'card_sczm'=>$data['card_sczm']

                                );

                                $data['nickname']=urldecode($data['nick_name']);

                                $data['status']=2;

                                $data['sh_type']=1;

                                $op_status=3;

                                $mark="";

                                if($status==4) {

                                    $op_status=4;

                                    $mark="兜客活体审核成功";

                                    $data['success_time']=time();

                                    $userdate['mch_status']=1;

                                    $userress = M('user')->where(array('user_id'=>$this->uid))->save($userdate);

                                }else {

                                    $op_status=3;

                                    $mark="兜客活体审核失败，转人工审核";

                                }



                                $data['user_id']=$this->uid;

                                $res = M('myrealname')->add($data);

                                if($res){

                                    echo json_encode(array('code'=>2012,'msg'=>'提交实名认证成功','img'=>enThumb("./Uploads",$w))); exit();

                                }else{

                                    echo json_encode(array('code'=>5010,'msg'=>'提交认证失败')); exit();

                                }

                            }



                        }

                    }else{

                        echo json_encode(array('code'=>5011,'msg'=>'请提交相关证件照片')); exit();

                    }

                }else{

                    echo json_encode(array('code'=>5011,'msg'=>'请提交相关证件照片')); exit();

                }

            }else{

                echo json_encode(array('code'=>5011,'msg'=>'请提交相关证件照片')); exit();

            }

        }else {

            echo json_encode(array('code'=>5021,'msg'=>'请用post方式提交')); exit();

        }



    }

    /*

     * 个人中心中 我的账单接口

     * money_min  搜索金额区间(最小金额)

     * money_max  搜索金额区间(最大金额)

     * bill_cart  搜索的分类(固定几个值)

     * bill_month 搜索的账单月份

     *

     */

     public function billSelect(){

        $uid = $_REQUEST['uid'];

        if (!isset($uid)){

         echo json_encode(array('code'=>4006,'msg'=>'参数错误'));

         exit;

        }

         $signT = $_REQUEST['sign'];

         if (!$signT){

             echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

         }

         $array = array(

             'uid'=>$uid,

         );

         $this->parem=array_merge($this->parem,$array);

         $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        dump($msg);die;

         if ($signT !== $msg){

             echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

         }

         R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

         $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断





        // 需查找的表   money_detailed(交易表)  moneyjs_detailed(结算表)  chongzhi_order(话费流量充值订单表)

         $money_min=$_REQUEST['money_min'];  //最小金额

         $money_max=$_REQUEST['money_max'];  //最大金额

         $bill_cart=$_REQUEST['bill_cart'];  //分类

         $bill_month=$_REQUEST['bill_month']; //月份

         $p=$_REQUEST['page'];

         if(!$p){

             $p=0;

         }

         $num=10;

         $benyue=array();

         $shangyue=array();

         $data=array();

         $res=array();

         $by=date('Y-m',time());

         $sy=date('Y-m',strtotime('-1 month'));

         //指定金额

         if ($money_max && $money_min){

             $w['pay_money'] = array(array('egt',$money_min),array('elt',$money_max));

             $t['a.js_money'] = array(array('egt',$money_min),array('elt',$money_max));

             $tt['fee']=array(array('egt',$money_min),array('elt',$money_max));

         }

         //指定分类

         if($bill_cart){

            if ($bill_cart == '1'){  //O2O交易

                if ($bill_month){  //在分类中查询指定月份

                    $zhidingY=$bill_month.'-01 00:00:00';

                    $strY=substr($bill_month,0,4).'-';

                    $zhidingM=strtotime($zhidingY);

//                    dump($strY);

                    $endday = strtotime(date($strY.'m-d', mktime(23, 59, 59, date('m', strtotime($zhidingY))+1, 00)));
                    $endday=$endday+86399;
                    $w['t']=array(array('egt',$zhidingM),array('elt',$endday));

                    $t['a.t']=array(array('egt',$zhidingM),array('elt',$endday));

                }

                $w['user_id']=$uid;

                $w['jy_status']=1;

                $w['_string'] = "money_type_id != 1 and money_type_id != 2 and money_type_id != 3 and money_type_id != 4 and money_type_id != 5 and money_type_id != 6 and money_type_id != 7 and money_type_id != 8 and money_type_id != 9 and money_type_id != 12 and money_type_id != 13 and money_type_id != 14 and money_type_id != 15 and money_type_id !=16 and money_type_id != 17 and money_type_id != 18 and money_type_id != 19 and money_type_id != 20 and money_type_id != 21 and money_type_id != 22 and money_type_id != 23 and money_type_id != 24";

//                $w['money_type_id']=array(array('neq',9),array('neq',12),'OR');

                //当前用户的所有交易

                $jy_order = M('money_detailed')->field('jy_status,money_detailed_id,pay_money,user_id,money_type_id,t,money')->where($w)->order('t desc')->select();

                foreach($jy_order as $k=>$v){

                    if ($v['money_type_id'] == 9 || $v['money_type_id'] == 12 || $v['money_type_id'] == 13 || $v['money_type_id'] == 14 || $v['money_type_id'] == 15){

                        $v['pay_money']=sprintf("%.4f",$v['pay_money']);

                        $v['money']=sprintf("%.4f",$v['money']);

                    }else{

                        $v['pay_money']=sprintf("%.2f",$v['pay_money']);

                        $v['money']=sprintf("%.2f",$v['money']);

                    }

                    $type_name = M('money_type')->where(array('money_type_id'=>$v['money_type_id']))->getField('type');

                    $v['money_type_name']=$type_name;

                   if($v['money_type_id'] == 1){

                      $v['symbol']='-';

                   }elseif($v['money_type_id'] == 2){

                       $v['symbol']='-';

                   }elseif($v['money_type_id'] == 3){

                       $v['symbol']='-';

                   }elseif($v['money_type_id'] == 4){

                       $v['symbol']='-';

                   }elseif($v['money_type_id'] == 5){

                       $v['symbol']='+';

                   }elseif($v['money_type_id'] == 6){

                       $v['symbol']='-';

                   }elseif($v['money_type_id'] == 7){

                       $v['symbol']='-';

                   }elseif($v['money_type_id'] == 8){

                       $v['symbol']='+';

                   }elseif($v['money_type_id'] == 9){

                       $v['symbol']='+';

                   }elseif($v['money_type_id'] == 10){

                       $v['symbol']='+';

                   }elseif($v['money_type_id'] == 11){

                       $v['symbol']='-';

                   }elseif($v['money_type_id'] == 12){

                       $v['symbol']='+';

                   }elseif($v['money_type_id'] == 13){

                       $v['symbol']='+';

                   }elseif($v['money_type_id'] == 14){

                       $v['symbol']='+';

                   }elseif($v['money_type_id'] == 15){

                       $v['symbol']='+';

                   }elseif($v['money_type_id'] == 16){

                       $v['symbol']='-';

                   }elseif($v['money_type_id'] == 17){

                       $v['symbol']='+';

                   }elseif($v['money_type_id'] == 18){

                       $v['symbol']='+';

                   }elseif($v['money_type_id'] == 19){

                       $v['symbol']='+';

                   }elseif($v['money_type_id'] == 20){

                       $v['symbol']='+';

                   }elseif($v['money_type_id'] == 21){

                       $v['symbol']='+';

                   }elseif($v['money_type_id'] == 22){

                       $v['symbol']='-';

                   }elseif($v['money_type_id'] == 23){

                       $v['symbol']='-';

                   }elseif($v['money_type_id'] == 24){

                       $v['symbol']='+';

                   }

                    $data[]=$v;

                }

                //当前用户的所有结算

                $t['a.user_id']=$uid;

                $t['a.js_status']=2;

                $js_order = M('moneyjs_detailed')->alias('a')->field('a.moneyjs_detailed_id,a.rz_money,a.js_money,a.user_id,a.js_status,a.t,a.type,b.pay_money')->join('left join y_money_detailed as b on a.pt_ordersn=b.pt_ordersn')->where($t)->order('a.t desc')->select();

                foreach($js_order as  $k=>$v){

                    $v['pay_money']=sprintf("%.2f",$v['pay_money']);

                    if ($v['type'] == 1){

                        $v['type_name'] = '提现';

                    }elseif($v['type'] == 2){

                        $v['type_name'] = 'O2O结算';

                    }elseif($v['type'] == 3){

                        $v['type_name'] = '收益结算';

                    }

                    $v['symbol']='+';

                    $data[]=$v;

                }

                $bc = count($data);

                $flag=array();

                foreach ($data as $data2){

                    $flag[]=$data2['t'];

                }
//                arsort($data);

                array_multisort($flag,SORT_DESC,$data);

                $res = array_splice($data,$p*$num,$num); # 当前页码数据

//                $sc = count($shangyue);

                echo json_encode(array('code'=>6501,'msg'=>'获取成功', 'page'=>$p,'pagesize'=>$num,'data'=>array('shuju'=>$res),'sum'=>$bc));

                die;

            }elseif($bill_cart == '2'){   //我的收益

                if ($bill_month){  //在分类中查询指定月份

                    $zhidingY=$bill_month.'-01 00:00:00';

                    $strY=substr($bill_month,0,4).'-';

                    $zhidingM=strtotime($zhidingY);

//                    dump($strY);

                    $endday = strtotime(date($strY.'m-d', mktime(23, 59, 59, date('m', strtotime($zhidingY))+1, 00)));
                    $endday=$endday+86399;
                    $w['t']=array(array('egt',$zhidingM),array('elt',$endday));



//                    dump($zhidingM);

                }

                $utype = M('user')->where(array('user_id'=>$uid))->getField('utype');

                if($utype == '20'){   //推客

                    $w['user_id']=$uid;

                    $w['jy_status']=1;

                    $w['money_type_id']=12;

//                    $w['_string']='money_type_id != 9 and money_type_id != 12 and money_type_id != 13 and money_type_id != 14 and money_type_id != 15 and money_type_id !=16 and money_type_id != 17 and money_type_id != 18 and money_type_id != 19 and money_type_id != 20 and money_type_id != 21';

//                    $w['money_type_id']=array(array('eq',9),array('eq',12),'OR');

                    $jy_order =  M('money_detailed')->field('jy_status,money_detailed_id,pay_money,user_id,money_type_id,t,money,money_type_id')->where($w)->order('t desc')->select();

                    foreach($jy_order as $k=>$v){

                        if ($v['money_type_id'] == 9 || $v['money_type_id'] == 12 || $v['money_type_id'] == 13 || $v['money_type_id'] == 14 || $v['money_type_id'] == 15){

                            $v['pay_money']=sprintf("%.4f",$v['pay_money']);

                            $v['money']=sprintf("%.4f",$v['money']);

                        }else{

                            $v['pay_money']=sprintf("%.2f",$v['pay_money']);

                            $v['money']=sprintf("%.2f",$v['money']);

                        }

                        if($v['money_type_id'] == 1){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 2){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 3){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 4){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 5){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 6){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 7){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 8){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 9){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 10){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 11){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 12){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 13){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 14){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 15){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 16){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 17){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 18){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 19){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 20){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 21){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 22){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 23){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 24){

                            $v['symbol']='+';

                        }

                        $type_name = M('money_type')->where(array('money_type_id'=>$v['money_type_id']))->getField('type');

                        $v['money_type_name']=$type_name;

                        $data[]=$v;

                    }

                     $bc = count($data);

                    $flag=array();

                    foreach ($data as $data2){

                        $flag[]=$data2['t'];

                    }
//                arsort($data);

                    array_multisort($flag,SORT_DESC,$data);

                    $res = array_splice($data,$p*$num,$num); # 当前页码数据

                    echo json_encode(array('code'=>6501,'msg'=>'获取成功', 'page'=>$p,'pagesize'=>$num,'data'=>array('shuju'=>$res),'sum'=>$bc));

                    die;

                }else{     //兜客 商家

                    $w['user_id']=$uid;

                    $w['jy_status']=1;

                    $w['money_type_id']=9;

                    $jy_order =  M('money_detailed')->field('jy_status,money_detailed_id,pay_money,user_id,money_type_id,t,money,money_type_id')->where($w)->order('t desc')->select();

                    foreach($jy_order as $k=>$v){

                        if ($v['money_type_id'] == 9 || $v['money_type_id'] == 12 || $v['money_type_id'] == 13 || $v['money_type_id'] == 14 || $v['money_type_id'] == 15){

                            $v['pay_money']=sprintf("%.4f",$v['pay_money']);

                            $v['money']=sprintf("%.4f",$v['money']);

                        }else{

                            $v['pay_money']=sprintf("%.2f",$v['pay_money']);

                            $v['money']=sprintf("%.2f",$v['money']);

                        }

                        if($v['money_type_id'] == 1){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 2){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 3){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 4){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 5){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 6){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 7){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 8){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 9){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 10){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 11){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 12){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 13){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 14){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 15){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 16){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 17){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 18){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 19){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 20){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 21){

                            $v['symbol']='+';

                        }elseif($v['money_type_id'] == 22){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 23){

                            $v['symbol']='-';

                        }elseif($v['money_type_id'] == 24){

                            $v['symbol']='+';

                        }

//                        $v['pay_money']=sprintf("%.2f",$v['pay_money']);

                        $type_name = M('money_type')->where(array('money_type_id'=>$v['money_type_id']))->getField('type');

                        $v['money_type_name']=$type_name;

                        $data[]=$v;

                    }

                    $bc = count($data);
                    $flag=array();

                    foreach ($data as $data2){

                        $flag[]=$data2['t'];

                    }
//                arsort($data);

                    array_multisort($flag,SORT_DESC,$data);

                    $res = array_splice($data,$p*$num,$num); # 当前页码数据

//                    $sc = count($shangyue);

//                    $sumc=$bc +$sc;

                    echo json_encode(array('code'=>6501,'msg'=>'获取成功', 'page'=>$p,'pagesize'=>$num,'data'=>array('shuju'=>$res),'sum'=>$bc));

                    die;

                }

            }elseif($bill_cart == '3'){
                if ($bill_month){  //在分类中查询指定月份

                    $zhidingY=$bill_month.'-01 00:00:00';

                    $strY=substr($bill_month,0,4).'-';

                    $zhidingM=strtotime($zhidingY);

//                    dump($strY);

                    $endday = strtotime(date($strY.'m-d', mktime(23, 59, 59, date('m', strtotime($zhidingY))+1, 00)));
                    $endday=$endday+86399;
                    $tt['time']=array(array('egt',$zhidingM),array('elt',$endday));
//                    dump($zhidingM);
                }

                //当前用户的话费流量充值记录

                $tt['user_id']=$uid;

                $tt['pay_status']=1;
//                dump($tt);die;
                $huafei_order = M('chongzhi_order')->field('chongzhi_id,user_id,fee,time,status,goodsname,type')->where($tt)->order('time desc')->select();
                foreach($huafei_order as $k=>$v){
//                    if(date('Y-m',$v['time']) == $by){
//                        $benyue[]=$v;
//                    }
//                    if(date('Y-m',$v['time'] ) == $sy){
//                        $shangyue[]=$v;
//                    }
                    $data[]=$v;
//                    $bc = $data[]=$v;
                }
                $bc = count($data);

                $res = array_splice($data,$p*$num,$num); # 当前页码数据

//                $sc = count($shangyue);

//                $sumc=$bc +$sc;

//              dump($benyue);die;

                echo json_encode(array(

                        'page'=>$p,

                        'pagesize'=>$num,

                        'code'=>6501,

                        'msg'=>'获取成功',

                        'data'=>array('shuju'=>$res),

                        'sum'=>$bc
                    )
                );
                die;

            }elseif($bill_cart == '4'){   //智能还款分类查询

                if ($bill_month){  //在分类中查询指定月份

                    $zhidingY=$bill_month.'-01 00:00:00';

                    $strY=substr($bill_month,0,4).'-';

                    $zhidingM=strtotime($zhidingY);

                    $endday = strtotime(date($strY.'m-d', mktime(23, 59, 59, date('m', strtotime($zhidingY))+1, 00)));
                    $endday=$endday+86399;
                    $wt2['t']=array(array('egt',$zhidingM),array('elt',$endday));

                }

                $wt2['user_id']=$uid;

                $wt2['jy_status']=1;

                $wt2['money_type_id']=16;

                $zn_order =  M('money_detailed')->field('jy_status,money_detailed_id,pay_money,user_id,money_type_id,t,money,money_type_id')->where($wt2)->order('t desc')->select();

                foreach($zn_order as $k=>$v){

                    if ($v['money_type_id'] == 9 || $v['money_type_id'] == 12 || $v['money_type_id'] == 13 || $v['money_type_id'] == 14 || $v['money_type_id'] == 15){

                        $v['pay_money']=sprintf("%.4f",$v['pay_money']);

                        $v['money']=sprintf("%.4f",$v['money']);

                    }else{

                        $v['pay_money']=sprintf("%.2f",$v['pay_money']);

                        $v['money']=sprintf("%.2f",$v['money']);

                    }

                    if($v['money_type_id'] == 1){

                        $v['symbol']='-';

                    }elseif($v['money_type_id'] == 2){

                        $v['symbol']='-';

                    }elseif($v['money_type_id'] == 3){

                        $v['symbol']='-';

                    }elseif($v['money_type_id'] == 4){

                        $v['symbol']='-';

                    }elseif($v['money_type_id'] == 5){

                        $v['symbol']='+';

                    }elseif($v['money_type_id'] == 6){

                        $v['symbol']='-';

                    }elseif($v['money_type_id'] == 7){

                        $v['symbol']='-';

                    }elseif($v['money_type_id'] == 8){

                        $v['symbol']='+';

                    }elseif($v['money_type_id'] == 9){

                        $v['symbol']='+';

                    }elseif($v['money_type_id'] == 10){

                        $v['symbol']='+';

                    }elseif($v['money_type_id'] == 11){

                        $v['symbol']='-';

                    }elseif($v['money_type_id'] == 12){

                        $v['symbol']='+';

                    }elseif($v['money_type_id'] == 13){

                        $v['symbol']='+';

                    }elseif($v['money_type_id'] == 14){

                        $v['symbol']='+';

                    }elseif($v['money_type_id'] == 15){

                        $v['symbol']='+';

                    }elseif($v['money_type_id'] == 16){

                        $v['symbol']='-';

                    }elseif($v['money_type_id'] == 17){

                        $v['symbol']='+';

                    }elseif($v['money_type_id'] == 18){

                        $v['symbol']='+';

                    }elseif($v['money_type_id'] == 19){

                        $v['symbol']='+';

                    }elseif($v['money_type_id'] == 20){

                        $v['symbol']='+';

                    }elseif($v['money_type_id'] == 21){

                        $v['symbol']='+';

                    }elseif($v['money_type_id'] == 22){

                        $v['symbol']='-';

                    }elseif($v['money_type_id'] == 23){

                        $v['symbol']='-';

                    }

                    $type_name = M('money_type')->where(array('money_type_id'=>$v['money_type_id']))->getField('type');

                    $v['money_type_name']=$type_name;

                    $data[]=$v;

                }

                $bc = count($data);

                $res = array_splice($data,$p*$num,$num); # 当前页码数据

                echo json_encode(array('code'=>6501,'msg'=>'获取成功', 'page'=>$p,'pagesize'=>$num,'data'=>array('shuju'=>$res),'sum'=>$bc));

                die;

            }

         }

           //指定月份

         if($bill_month){

             $zhidingM=strtotime($bill_month);

             //我的收益(指定月份)

             $w['user_id']=$uid;

             $w['jy_status']=1;

             //当前用户的所有交易(指定月份)

             $jy_order = M('money_detailed')->field('jy_status,money_detailed_id,pay_money,user_id,money_type_id,t,money,money_type_id')->where($w)->order('t desc')->select();

             foreach($jy_order as $k=>$v){

                 if ($v['money_type_id'] == 9 || $v['money_type_id'] == 12 || $v['money_type_id'] == 13 || $v['money_type_id'] == 14 || $v['money_type_id'] == 15){

                     $v['pay_money']=sprintf("%.4f",$v['pay_money']);

                     $v['money']=sprintf("%.4f",$v['money']);

                 }else{

                     $v['pay_money']=sprintf("%.2f",$v['pay_money']);

                     $v['money']=sprintf("%.2f",$v['money']);

                 }

                 if($v['money_type_id'] == 1){

                     $v['symbol']='-';

                 }elseif($v['money_type_id'] == 2){

                     $v['symbol']='-';

                 }elseif($v['money_type_id'] == 3){

                     $v['symbol']='-';

                 }elseif($v['money_type_id'] == 4){

                     $v['symbol']='-';

                 }elseif($v['money_type_id'] == 5){

                     $v['symbol']='+';

                 }elseif($v['money_type_id'] == 6){

                     $v['symbol']='-';

                 }elseif($v['money_type_id'] == 7){

                     $v['symbol']='-';

                 }elseif($v['money_type_id'] == 8){

                     $v['symbol']='+';

                 }elseif($v['money_type_id'] == 9){

                     $v['symbol']='+';

                 }elseif($v['money_type_id'] == 10){

                     $v['symbol']='+';

                 }elseif($v['money_type_id'] == 11){

                     $v['symbol']='-';

                 }elseif($v['money_type_id'] == 12){

                     $v['symbol']='+';

                 }elseif($v['money_type_id'] == 13){

                     $v['symbol']='+';

                 }elseif($v['money_type_id'] == 14){

                     $v['symbol']='+';

                 }elseif($v['money_type_id'] == 15){

                     $v['symbol']='+';

                 }elseif($v['money_type_id'] == 16){

                     $v['symbol']='-';

                 }elseif($v['money_type_id'] == 17){

                     $v['symbol']='+';

                 }elseif($v['money_type_id'] == 18){

                     $v['symbol']='+';

                 }elseif($v['money_type_id'] == 19){

                     $v['symbol']='+';

                 }elseif($v['money_type_id'] == 20){

                     $v['symbol']='+';

                 }elseif($v['money_type_id'] == 21){

                     $v['symbol']='+';

                 }elseif($v['money_type_id'] == 22){

                     $v['symbol']='-';

                 }elseif($v['money_type_id'] == 23){

                     $v['symbol']='-';

                 }



                 $type_name = M('money_type')->where(array('money_type_id'=>$v['money_type_id']))->getField('type');

                 $v['money_type_name']=$type_name;

                 if(date('Y-m',$v['t']) == date('Y-m',$zhidingM)){

                     $data[]=$v;

                 }

             }

             //当前用户的话费流量充值记录(指定月份)

             $tt['user_id']=$uid;

             $tt['status']=1;

             $huafei_order = M('chongzhi_order')->field('chongzhi_id,user_id,fee,time,status,goodsname,type')->where($tt)->order('time desc')->select();

             foreach($huafei_order as $k=>$v){

                 if(date('Y-m',$v['time']) == date('Y-m',$zhidingM)){

                     $data[]=$v;

                 }

             }

             $bc = count($data);

             $res = array_splice($data,$p*$num,$num); # 当前页码数据

             echo json_encode(array('code'=>6501,'msg'=>'指定月份获取成功','page' =>$p,'pagesize'=>$num,'data'=>array('shuju'=>$res),'sum'=>$bc));

             die;

         }

          //我的收益

//         $utype = M('user')->where(array('user_id'=>$uid))->getField('utype');

             $w['user_id']=$uid;

             $w['jy_status']=1;

             //当前用户的所有交易

             $jy_order = M('money_detailed')->field('jy_status,money_detailed_id,pay_money,user_id,money_type_id,t,money,money_type_id')->where($w)->order('t desc')->select();

              foreach($jy_order as $k=>$v){

                  if ($v['money_type_id'] == 9 || $v['money_type_id'] == 12 || $v['money_type_id'] == 13 || $v['money_type_id'] == 14 || $v['money_type_id'] == 15){

                      $v['pay_money']=sprintf("%.4f",$v['pay_money']);

                      $v['money']=sprintf("%.4f",$v['money']);

                  }else{

                      $v['pay_money']=sprintf("%.2f",$v['pay_money']);

                      $v['money']=sprintf("%.2f",$v['money']);

                  }

                  if($v['money_type_id'] == 1){

                      $v['symbol']='-';

                  }elseif($v['money_type_id'] == 2){

                      $v['symbol']='-';

                  }elseif($v['money_type_id'] == 3){

                      $v['symbol']='-';

                  }elseif($v['money_type_id'] == 4){

                      $v['symbol']='-';

                  }elseif($v['money_type_id'] == 5){

                      $v['symbol']='+';

                  }elseif($v['money_type_id'] == 6){

                      $v['symbol']='-';

                  }elseif($v['money_type_id'] == 7){

                      $v['symbol']='-';

                  }elseif($v['money_type_id'] == 8){

                      $v['symbol']='+';

                  }elseif($v['money_type_id'] == 9){

                      $v['symbol']='+';

                  }elseif($v['money_type_id'] == 10){

                      $v['symbol']='+';

                  }elseif($v['money_type_id'] == 11){

                      $v['symbol']='-';

                  }elseif($v['money_type_id'] == 12){

                      $v['symbol']='+';

                  }elseif($v['money_type_id'] == 13){

                      $v['symbol']='+';

                  }elseif($v['money_type_id'] == 14){

                      $v['symbol']='+';

                  }elseif($v['money_type_id'] == 15){

                      $v['symbol']='+';

                  }elseif($v['money_type_id'] == 16){

                      $v['symbol']='-';

                  }elseif($v['money_type_id'] == 17){

                      $v['symbol']='+';

                  }elseif($v['money_type_id'] == 18){

                      $v['symbol']='+';

                  }elseif($v['money_type_id'] == 19){

                      $v['symbol']='+';

                  }elseif($v['money_type_id'] == 20){

                      $v['symbol']='+';

                  }elseif($v['money_type_id'] == 21){

                      $v['symbol']='+';

                  }elseif($v['money_type_id'] == 22){

                      $v['symbol']='-';

                  }elseif($v['money_type_id'] == 23){

                      $v['symbol']='-';

                  }

                  $type_name = M('money_type')->where(array('money_type_id'=>$v['money_type_id']))->getField('type');

                  $v['money_type_name']=$type_name;

                  $data[]=$v;

//                  if(date('Y-m',$v['t']) == $by){

//                    $benyue[]=$v;

//                  }

//                  if(date('Y-m',$v['t'] ) == $sy){

//                    $shangyue[]=$v;

//                  }

              }

               //当前用户的所有结算

               $t['a.user_id']=$uid;

               $t['a.js_status']=2;

         $js_order = M('moneyjs_detailed')->alias('a')->field('a.moneyjs_detailed_id,a.rz_money,a.js_money,a.user_id,a.js_status,a.t,a.type,b.pay_money')->join('left join y_money_detailed as b on a.pt_ordersn=b.pt_ordersn')->where($t)->order('a.t desc')->select();

              foreach($js_order as  $k=>$v){

                  if ($v['type'] == 1){

                      $v['type_name'] = '提现';

                  }elseif($v['type'] == 2){

                      $v['type_name'] = 'O2O结算';

                  }elseif($v['type'] == 3){

                      $v['type_name'] = '收益结算';

                  }

                  $v['symbol']='+';



                  $data[]=$v;

              }



              //当前用户的话费流量充值记录

              $tt['user_id']=$uid;

              $tt['status']=1;

              $huafei_order = M('chongzhi_order')->field('chongzhi_id,user_id,fee,time,status,goodsname,type')->where($tt)->order('time desc')->select();

              foreach($huafei_order as $k=>$v){

                  $data[]=$v;

//                  if(date('Y-m',$v['time']) == $by){

//                      $benyue[]=$v;

//                  }

//                  if(date('Y-m',$v['time'] ) == $sy){

//                      $shangyue[]=$v;

//                  }

              }

//              dump($benyue);die;

              $bc = count($data);

//              $sc = count($shangyue);

//              $sumc=$bc +$sc;

         //测试

//                 arsort($data,$data['t']);

              //按时间倒序

              $flag=array();

              foreach ($data as $data2){

                  $flag[]=$data2['t'];

              }
//                arsort($data);

              array_multisort($flag,SORT_DESC,$data);

//              dump($data);die;

              $res = array_splice($data,$p*$num,$num); # 当前页码数据

              echo json_encode(array(

                  'code'=>6501,

                  'msg'=>'获取成功',

                  'page' => $p,

                  'pagesize' => $num,

                  'sum'=>$bc,

                  'data'=>array('shuju'=>$res)

                  )

              );

               die;

//           dump($benyue);

     }

   //修改安卓升级提示

     public function dump2()

     {

         //1.商家收款申请 \n2.部分生活充值\n3.更新注册实名认证流程\n4.原见习推客升级\n5.兜客/推客注册选择\n6.信用卡申请流程优化\n7.增加全渠道有积分通道\n8.修复若干已知问题
//         +商家收款申请
//         +部分生活充值
//         +更新注册实名认证流程
//         +原见习推客升级
//         +兜客/推客注册选择
//         +信用卡申请流程优化
//         +增加全渠道有积分通道
//         +修复若干已知问题
//         $data['upgrade_point']="1.新增智能还款\n2.新增信用卡申请";

//         M("version_upgrade")->where(array('version_upgrade_id'=>1))->save($data);

//         $data['upgrade_point']="1.BUG修复\n\n2.活体认证BUG修复\n\n3.元旦大转盘活动上线\n\n4.我的优惠券上线";
          //and

         //1.图文推广新增微信分享
            //2.O2O收款开通新通道
            //3.部分bug修复
            //4.兼容android8.0通知栏信息

               //ios
//         1.图文推广新增微信分享
//        2.O2O收款开通新通道
//        3.部分bug修复
//        4.修复部分手机无法登陆问题

//         $data['upgrade_point']=json_encode(array(1=>'+商家收款申请',2=>'+部分生活充值',3=>'+更新注册实名认证流程',4=>'+原见习推客升级.0通知栏信息',5=>'+兜客/推客注册选择',6=>'+信用卡申请流程优化',7=>'+增加全渠道有积分通道',8=>'+修复若干已知问题'));
//1.+商家收款申请 \n
//         $data['upgrade_point']='1.+修复若干已知问题';
//         M("version_upgrade")->where(array('version_upgrade_id'=>3))->save($data);

            echo 'success';

//        dump(M("version_upgrade")->where(array('version_upgrade_id'=>2))->getField('upgrade_point'));

     }

     #返回银行卡信息接口

    public function returnBankInfo(){

        $uid = $_REQUEST['uid'];

//        if (!$this->sign){

//            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

//        }

//        $array = array(

//            'uid'=>$this->uid,

//            'bankcard'=>$_REQUEST['bankcard'],

//        );

//        $this->parem=array_merge($this->parem,$array);

//        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        if ($this->sign !== $msg){

//            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

//        }

//        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

//        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



    $headers=array();

    array_push($headers,"Authorization:APPCODE ".$this->AppCode);

    $querys="bankcard=6252490020102930";

        //

        //6228481531586126511

//        $querys="bankcard".$_REQUEST['bankcard'];

    $bodys="";

    $url=$this->host.$this->path."?".$querys;

    $method="GET";

    $curl=curl_init();

    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,$method);

    curl_setopt($curl,CURLOPT_URL,$url);

    curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);

    curl_setopt($curl,CURLOPT_FAILONERROR,false);

    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);

    curl_setopt($curl,CURLOPT_HEADER,true);

    if(1==strpos("$".$this->host,"https://"))

    {

        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);

        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,false);

    }

        $info = curl_exec($curl);

        curl_close($curl);

        dump($info);die;

        R("Payapi/Api/PaySetLog",array("./PayLog","User_returnBankInfo_",'---- 返回信息参数 ----'.$info));

        $info=json_decode($info,true);
//        dump($info);die;

        if($info['error_code'] == 0){

            echo json_encode(array('code'=>$info['error_code'],'msg'=>$info['reason'],'data'=>$info['result']));

            die;

        }else{

          echo json_encode(array('code'=>$info['error_code'],'msg'=>$info['reason']));

          die;

        }

//    return $info;

//        dump(json_decode($info,true));

    }



    #返回银行卡信息接口2

    public function returnBankInfoTwo(){

            $uid = $_REQUEST['uid'];

            if (!$this->sign){

                echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

            }

            $array = array(

                'uid'=>$this->uid,

                'bankcard'=>$_REQUEST['bankcard'],

            );

            $this->parem=array_merge($this->parem,$array);

            $msg = R('Func/Func/getKey',array($this->parem));//返回加密

            if ($this->sign !== $msg){

                echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

            }

            R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

            $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

             if(!$_REQUEST['bankcard']){

             echo json_encode(array('code'=>5554,'msg'=>'请输入卡号'));die;

             }

//         /*

//         * 此卡已存在数据库

//         * */

//           $aaa= M('mybank')->field('province_id,city_id,bank_id,cart,type,is_ztype')->where(array('cart'=>$_REQUEST['bankcard']))->find();

//           if($aaa['province_id']){

//               $bankS=M('province')->field('provids,province')->where(array('provids'=>$aaa['province_id']))->find();

//           }

//           if($aaa['city_id']){

//                $bankSS=M('city')->field('city,cityids')->where(array('cityids'=>$aaa['city_id']))->find();

//           }

//           if($aaa['bank_id']){

//               $bankI=M('bank')->where(array('bank_id'=>$aaa['bank_id']))->find();

//               $daa['bank']=$bankI['name'];

//                if(!$bankI){

//                    echo json_encode(array('code'=>5029,'msg'=>'暂不支持该银行'));die;

//                }

//           }

//           if($aaa['type']==1){

//               $daa['type']='借记卡';

//           }elseif($aaa['type']==2) {

//               if($aaa['is_ztype'] ==1 ){

//                  $daa['type']='准贷记卡';

//               }else{

//                   $daa['type'] = '贷记卡';

//               }

//           }

//        if($aaa){

//            echo json_encode(array('code'=>6005,'msg'=>'获取成功','data'=>$daa,'sheng'=>$bankS,'shi'=>$bankSS,'bank_id'=>$bankI));die;

//        }

       $host = "http://jisuyhkgsd.market.alicloudapi.com";

        $path = "/bankcard/query";

        $method = "GET";

//        $appcode = "";

        $headers = array();

//        http://jisuyhkgsd.market.alicloudapi.com/bankcard/query

        array_push($headers, "Authorization:APPCODE ".$this->AppCode);

//        $querys = "bankcard=6252490020102930";//      6259074445765738

        //  6228480606400544171   zx 6226890118005577  622439880016114875

        $querys = "bankcard=".$_REQUEST['bankcard'];

        $bodys = "";

        $url = $host.$path."?".$querys;

//       dump($url);die;

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($curl, CURLOPT_FAILONERROR, false);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_HEADER, false);

        if (1 == strpos("$".$host, "https://"))

        {

            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        }

        $info = curl_exec($curl);

//        dump($info);die;

        curl_close($curl);

        R("Payapi/Api/PaySetLog",array("./PayLog","User_returnBankInfo_",'---- 返回信息参数 ----'.$info));

        $info=json_decode($info,true);

        $re=array();

        $res=array();

        $bankInfo=array();

//        dump($info);die;

        if($info['status'] == '0'){

            $where['province']=array('like',"%{$info['result']['province']}%");

            $res = M('province')->field('provids,province')->where($where)->find();

            $w['city']=array('like',"%{$info['result']['city']}%");

            $re = M('city')->field('cityids,city')->where($w)->find();

            $ww['name'] = array("like","%{$info['result']['bank']}%");

            $bankInfo=M('bank')->where($ww)->find();

//            echo M('bank')->_sql();die;

            if(!$bankInfo){
                echo json_encode(array('code'=>5029,'msg'=>'暂不支持该银行'));die;
            }

            if ($bankInfo){
              $tt['bank_id']=$bankInfo['bank_id'];
            }

            echo json_encode(array('code'=>6005,'msg'=>$info['msg'],'data'=>$info['result'],'bank_id'=>$bankInfo,'sheng'=>$res,'shi'=>$re));

            die;

        }else{
            echo json_encode(array('code'=>201,'msg'=>$info['msg']));
            die;
        }
//      dump($info);
    }

   //测试
   //    #返回银行卡信息接口2

    public function returnBankInfoTwocs(){
       $host = "http://jisuyhkgsd.market.alicloudapi.com";

        $path = "/bankcard/query";

        $method = "GET";

//        $appcode = "";

        $headers = array();
//        http://jisuyhkgsd.market.alicloudapi.com/bankcard/query
        array_push($headers, "Authorization:APPCODE ".$this->AppCode);
//        $querys = "bankcard=6259691108700098";//      6259074445765738
        //  6228480606400544171   zx 6226890118005577  622439880016114875
        // $querys = "bankcard=".$_REQUEST['bankcard'];
        $querys = "bankcard=6222021001095097389";  //5105290029593217
        //6252490020102930
        $bodys = "";
        $url = $host.$path."?".$querys;
//       dump($url);die;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($curl, CURLOPT_FAILONERROR, false);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_HEADER, false);

        if (1 == strpos("$".$host, "https://"))

        {

            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        }

        $info = curl_exec($curl);

//        dump($info);die;

        curl_close($curl);

        R("Payapi/Api/PaySetLog",array("./PayLog","User_returnBankInfo_",'---- 返回信息参数 ----'.$info));

        $info=json_decode($info,true);

        $re=array();

        $res=array();

        $bankInfo=array();
       dump($info);die;

//        if($info['status'] == '0'){
//
//            $where['province']=array('like',"%{$info['result']['province']}%");
//
//            $res = M('province')->field('provids,province')->where($where)->find();
//
//            $w['city']=array('like',"%{$info['result']['city']}%");
//
//            $re = M('city')->field('cityids,city')->where($w)->find();
//
//            $ww['name'] = array("like","%{$info['result']['bank']}%");
//
//            $bankInfo=M('bank')->where($ww)->find();
//
////            echo M('bank')->_sql();die;
//
//            if(!$bankInfo){
//                echo json_encode(array('code'=>5029,'msg'=>'暂不支持该银行'));die;
//            }
//            echo $res['provids'].'----';
//            echo $re['cityids'];die;
//            if ($bankInfo){
//                $tt['bank_id']=$bankInfo['bank_id'];
//            }
//
//            echo json_encode(array('code'=>6005,'msg'=>$info['msg'],'data'=>$info['result'],'bank_id'=>$bankInfo,'sheng'=>$res,'shi'=>$re));
//
//            die;
//
//        }else{
//            echo json_encode(array('code'=>201,'msg'=>$info['msg']));
//            die;
//        }

    }
    #支行搜索接口 (有分页)
    public function selectZhiHang(){

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'bank_id'=>$_REQUEST['bank_id'],

            'keyname'=>$_REQUEST['keyname'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session为作判断



        $bank_id=$_REQUEST['bank_id'];

        $keyname=$_REQUEST['keyname'];

        $p=$_REQUEST['page'];

        if(!$p){

            $p=0;

        }

        $num=50;

            $where['bank_id']=$bank_id;

            $where['name']=array('like',"%{$keyname}%");

            $count=M('bank_children')->where($where)->count();

            $res = M('bank_children')->where($where)->limit($p*$num,$num)->select();

            if ($res){

                echo json_encode(array('code'=>200,'data'=>$res,'count'=>$count));die;

            }else{

                echo  json_encode(array('code'=>400,'msg'=>'未查找到所属支行'));die;

            }

    }
    #修改信用卡接口
    public function updBank(){

        $mybank_id = $_REQUEST['mybank_id'];

        $uid=$_REQUEST['uid'];

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$this->uid,

            'mybank_id'=>$_REQUEST['mybank_id'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        echo json_encode(array('data'=>$msg));die;

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断



        if (!$uid){

            echo json_encode(array('code'=>4006,'msg'=>'参数错误'));die;

        }

        if (!$mybank_id){

            echo json_encode(array('code'=>4006,'msg'=>'参数错误'));die;

        }

        $res = M('mybank')->where(array('mybank_id'=>$mybank_id,'user_id'=>$uid))->find();

         if($res['type'] == 2){

            $res['type'] ='贷记卡';

         }else{

             $res['type']='借记卡';

         }

         $bankName = M('bank')->field('name')->where(array('bank_id'=>$res['bank_id']))->find();

        $res['bank_name']=$bankName;

         if ($res){

         echo json_encode(array('code'=>4403,'msg'=>'获取成功','data'=>$res));

         die;

        }else{

            echo json_encode(array('code'=>4404,'msg'=>'系统繁忙'));

            die;

        }

    }
    #修改信用卡接口
    public function updBankInfo(){

        $uid = $this->uid;

        if (!$this->sign){

            echo json_encode(array('code'=>10005,'msg'=>'加密字符串不存在'));die;

        }

        $array = array(

            'uid'=>$this->uid,

            'mybank_id'=>$_REQUEST['mybank_id'],

            'amount'=>$_REQUEST['amount'], //额度

            'bill_day'=>$_REQUEST['bill_day'],//账单日

            'refund_day'=>$_REQUEST['refund_day'],//还款日

            'mobile'=>$_REQUEST['mobile'],

            'code'=>$_REQUEST['code'],

        );

        $this->parem=array_merge($this->parem,$array);

        $msg = R('Func/Func/getKey',array($this->parem));//返回加密

//        echo json_encode(array('data'=>$msg));die;

        if ($this->sign !== $msg){

            echo json_encode(array('code'=>10004,'msg'=>'禁止访问'));die;

        }

        R('Func/Func/getTwoSign',array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)

        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

         /*

         * 逻辑

         * */

         $yzm = M('mobleyzm')->where(array('phone'=>$_REQUEST['mobile'],'type'=>3))->find();

          if($yzm['code'] != $_REQUEST['code']){

              echo json_encode(array('code'=>4203,'msg'=>'验证码错误'));

              die;

          }

         $w['mybank_id']=$_REQUEST['mybank_id'];

        $res =M('mybank')->where($w)->data(array('amount'=>$_REQUEST['amount'],'bill_day'=>$_REQUEST['bill_day'],'refund_day'=>$_REQUEST['refund_day'],'u_t'=>time()))->save();

        if ($res){

        echo json_encode(array('code'=>5020,'msg'=>'修改成功'));

        die;

        }else{

         echo json_encode(array('code'=>5012,'msg'=>'系统繁忙'));

         die;

        }

    }
    #图文推广接口
    public function imageNewsTg(){

      $type = $_REQUEST['type'];

      if (!$type){

          echo json_encode(array('code'=>4444,'msg'=>'参数不存在'));die;

      }

      $res = M('image_news')->where(array('type'=>$type,'status'=>1))->order('id desc')->select();

      foreach ($res as $k=>$v){
         $res[$k]['imagelink']=enThumb('/Public/Payapi/images/news/',$v['imagelink']);
          $res[$k]['imagelink_thumb']=enThumb('/Public/Payapi/images/news/',$v['imagelink_thumb']);
      }

      if($res){

          echo json_encode(array('code'=>4445,'msg'=>'获取成功','data'=>$res));die;

      }else{

          echo json_encode(array('code'=>4446,'msg'=>'信息为空'));die;

      }

    }

}