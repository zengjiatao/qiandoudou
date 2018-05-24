<?php
/**
 * Created by 银迅通支付网络公司.
 * User: Jeffery
 * Date: 2017/12/9
 * Time: 15:36
 * @author : Jeffery and jiatao
 * 信用卡申请
 */

namespace Payapi\Controller;

class CreditcardController extends BaseController{

    public $uid;
    public $parem;
    public $sign;
    public function _initialize()
    {
        parent::_initialize();
        $this->uid = $_REQUEST['uid'];
//        if (empty($this->uid)) {
//            $d = array(
//                'code' => 200,
//                'msg' => '用户ID不存在'
//            );
//            echo json_encode($d);
//            exit;
//        }

        $this->parem = array(
            'signType' => $_REQUEST['signType'],
            'timestamp' => $_REQUEST['timestamp'],
            'dataType' => $_REQUEST['dataType'],
            'inputCharset' => $_REQUEST['inputCharset'],
            'version' => $_REQUEST['version'],
        );
        $this->sign = $_REQUEST['sign'];
    }

    /**
     * 2.8.1 下一步跳转到引流链接
        2.8.2 添加信用卡申请记录
        2.8.3 添加信用卡申请人信息
        2.8.4 列出所有申请联系人
        2.8.5 删除常用联系人 -- 待修复多选删除功能，目前先单独删除
     */

    # 下一步跳转到引流链接 / 是否有申请人
    public function nextUrl()
    {
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $creditcard_id = intval($_REQUEST['creditcard_id']); # 信用卡ID
        $array = array(
            'uid' => $this->uid,
            'creditcard_id' => $creditcard_id
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
        # 引流链接 (申请链接)
        $creditcard_td_id = M("creditcard")->where(array('creditcard_id'=>$creditcard_id))->getField('creditcard_td_id');
        if(!$creditcard_td_id)
        {
            echo json_encode(array('code' => 400, 'msg' => '申请通道未开放'));
            die;
        }
        # 旧方式
        $yl_url = M("creditcard_td")->where(array('creditcard_td_id'=>$creditcard_td_id,'status'=>1))->getField('yl_url');
//        $yl_url = M("pay_supply")->where(array('pay_supply_id'=>$pay_supply_id,'status'=>1))->getField('yl_url');
        if(!$yl_url)
        {
            echo json_encode(array('code' => 400, 'msg' =>'申请通道未开放'));
            die;
        }
        $creditcard_user_id = M("creditcard_user")->where(array('user_id'=>$this->uid,'is_cy'=>1))->getField('creditcard_user_id');
        if($creditcard_user_id)
        {
            $is_sqr = 1;  # 有申请人信息
        }else{
            $is_sqr = 0;  # 木有
        }
        if($yl_url)
        {
            # 新增引流链接
            echo json_encode(array('code' => 200, 'msg' => '优惠办卡','data' => array('yl_url'=>$yl_url,'is_sqr'=>$is_sqr)));
            die;
        }
    }

    # 添加信用卡申请人信息
    public function addUser()
    {
        $idcard = trim($_REQUEST['idcard']);
        $name = trim($_REQUEST['name']);
        $phone = trim($_REQUEST['phone']);
        /**/
            if (!$this->sign) {
                echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
                die;
            }
            $array = array(
                'uid' => $this->uid,
                'name' => urldecode($name),
                'phone' => $phone,
                'idcard' => $idcard
            );
            $this->parem = array_merge($this->parem, $array);
            $msg = R('Func/Func/getKey', array($this->parem));//返回加密
            if ($this->sign !== $msg){
                echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
                die;
            }
            R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
            $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断




        $ifinfo = M("creditcard_user")->where(array('name'=>urldecode($name),'user_id'=>$this->uid,'idcard'=>$idcard))->find();
        if(!$name)
        {
            echo json_encode(array('code' => 400, 'msg' => '请填写姓名'));
            die;
        }
        if(!$phone)
        {
            echo json_encode(array('code' => 400, 'msg' => '请填写手机号码'));
            die;
        }
        if(!$idcard)
        {
            echo json_encode(array('code' => 400, 'msg' => '请填写身份证号码'));
            die;
        }

        # 鉴权是否成功
        $requestId = 'SS'.date("YmdHis",time());
        $timestamp = date("YmdHis",time());
        $pdataauth = array(
            'name' => urldecode($name),  // 姓名
            'idNumber' => $idcard,  // 身份证
            'requestId' => $requestId, // each : 'ab__'.date('Ymdhis',time()); // 订单号
            'timestamp' => $timestamp
        );
        $jqres = R("Payapi/AuthApi/AuthRealname",array($pdataauth,$this->uid));
        if($jqres['res_code']!='0000')
        {
            echo json_encode(array('code' => 400, 'msg' => '姓名和身份证号匹配不正确'));
            die;
        }

        $res = 1;
        if(!$ifinfo)
        {
            # 用户名
            $userId = M("user")->where(array('user_id'=>$this->uid))->getField('phone');
            $pdata = array(
                'userId' => $userId,
                'user_id'=>$this->uid,
                'name' => urldecode($name),
                'idcard' => $idcard,
                'phone' => $phone
            );
            $res = M("creditcard_user")->add($pdata);
        }else{
            M("creditcard_user")->where(array('name'=>urldecode($name),'user_id'=>$this->uid,'idcard'=>$idcard))->save(array('is_cy'=>1));
            $res = 1;
        }
        if($res)
        {
            echo json_encode(array('code' => 200, 'msg' => '添加成功'));
            die;
        }else{
            echo json_encode(array('code' => 400, 'msg' => '操作失败,请重试'));
            die;
        }
    }

    # 列出所有申请联系人
    public function checkUser()
    {
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $info = M("creditcard_user")->where(array('user_id'=>$this->uid,'is_cy'=>1))->order('creditcard_user_id desc')->select();

        if($info)
        {
            foreach ($info as $k => $v)
            {
//                $info[$k]['idcard'] = substr($v['idcard'],0,5).'*****'.substr($v['idcard'],6);
//                $info[$k]['phone'] = substr($v['phone'],0,3).'*****'.substr($v['phone'],8,strlen($str));
            }
            echo json_encode(array('code' => 200, 'msg' => '选择联系人','data'=>$info));
            die;
        }else{
            echo json_encode(array('code' => 400, 'msg' => '请添加联系人'));
            die;
        }
    }

    # 删除常用联系人
    public function delUser()
    {
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $creditcard_user_id = trim($_REQUEST['creditcard_user_id']);
        $array = array(
            'uid' => $this->uid,
            'creditcard_user_id' => $creditcard_user_id
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密

        # 截取,号
        $excid = str_replace("-",",",$creditcard_user_id);

        /*
            $arg='';$url='';
            foreach($this->parem as $key=>$val){
                //$arg.=$key."=".urlencode($val)."&amp;";
                $arg.=$key."=".urlencode($val)."&amp;";
            }
            $url.= $arg;
            $str=rtrim($url);
            var_dump($url);
            echo '<br>';
            var_dump($str);
            echo '<br>';
            var_dump($msg);
            die;
        */
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $del = 0;
        if($excid)
        {
            $w['user_id'] = $this->uid;
            $w['creditcard_user_id'] = array('in',$excid);
            $del = M("creditcard_user")->where($w)->save(array('is_cy'=>2));
        }

        if($del)
        {
            echo json_encode(array('code' => 200, 'msg' => '删除成功'));
            die;
        }else{
            echo json_encode(array('code' => 400, 'msg' => '操作失败'));
            die;
        }
    }

    # 首页信用卡中心
    public function index()
    {
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(

        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        // 轮播图
        $data['lunbo'] = R("Func/Func/getLunbo",array(3));
        $updatebao = array(
            '**凤刚刚申请上海银行信用卡',
            '**涛刚刚申请上海银行信用卡',
            '**麦刚刚申请上海银行信用卡',
            '**振刚刚申请上海银行信用卡',
            '**明刚刚申请上海银行信用卡',
            '**志刚刚申请上海银行信用卡',
            '**卫刚刚申请上海银行信用卡',
            '**琪刚刚申请上海银行信用卡',
            '**红刚刚申请上海银行信用卡',
            '**锋刚刚申请上海银行信用卡',
        );
        // 实时播报
        $data['updatebao'] = $updatebao;
        $w['is_index'] = 1;
        // 热门推荐 - 首页显示
        $data['hotList'] = R("Func/Creditcard/getList",array($w));
//        dump($data);die;
         $data['xieyi'] = M('xieyi')->where(array('type'=>1))->find();
        // 列出银行
        $data['bankList'] = R("Func/Creditcard/getBankList",array($w));
        echo json_encode(array('code' => 200, 'msg' => '首页数据','data'=>$data));
        die;
    }

    # 申请进度 , ceshi
    public function sqjdceshi()
    {
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $info = M("creditcard_jilu")->where(array('user_id'=>$this->uid))->order('creditcard_jilu_id desc')->select();
        $res = array();
        if($info)
        {
            foreach ($info as $k => $v)
            {
                $creinfo = M("creditcard")->field('name,bank_id')->where(array('creditcard_id'=>$v['creditcard_id']))->find();
                $res[$k]['kname'] = $creinfo['name']; // 卡名称
                $res[$k]['bankname'] = M('bank')->where(array('bank_id'=>$creinfo['bank_id']))->getField('name');
                # 申请提交
                if($v['status'] == 1)
                {
                    $jdarr[] = array(
                        '申请已提交',
                        date("Y-m-d H:i",$v['t'])
                    );
                    $newstatus = '申请已提交';

                }else if($v['status'] == 2)
                {
                    $jdarr[] = array(
                        '申请已提交',
                        date("Y-m-d H:i",$v['t'])
                    );
                    $jdarr[] = array(
                        '正在审核中',
                        date("Y-m-d H:i",$v['t'])
                    );
                    $newstatus = '正在审核中';

                }else if($v['status'] == 4)
                {
                    $jdarr[] = array(
                        '申请已提交',
                        date("Y-m-d H:i",$v['t'])
                    );
                    $jdarr[] = array(
                        '正在审核中',
                        date("Y-m-d H:i",$v['t'])
                    );
                    $jdarr[] = array(
                        '审核通过',
                        date("Y-m-d H:i",$v['success_time'])
                    );
                    $newstatus = '审核通过';

                }else if($v['status'] == 3)
                {
                    $jdarr[] = array(
                        '申请已提交',
                        date("Y-m-d H:i",$v['t'])
                    );
                    $jdarr[] = array(
                        '正在审核中',
                        date("Y-m-d H:i",$v['t'])
                    );
                    $jdarr[] = array(
                        '审核未通过',
                        date("Y-m-d H:i",$v['success_time'])
                    );
                    $newstatus = '审核未通过';
                }
                # 进度状态
                $res[$k]['jd'] = $jdarr;

                $res[$k]['newstatus'] = $newstatus;
            }

            echo json_encode(array('code' => 200, 'msg' => '申请进度列表','data'=>$res));
            die;
        }else{
            echo json_encode(array('code' => 400, 'msg' => '没有申卡记录'));
            die;
        }
    }

    # 申请进度
    public function sqjd()
    {
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $info = M("creditcard_jilu")->where(array('user_id'=>$this->uid,'is_cy'=>2))->order('creditcard_jilu_id desc')->select();
        $res = array();
        if($info)
        {
            foreach ($info as $k => $v)
            {
                $creinfo = M("creditcard")->field('name,bank_id')->where(array('creditcard_id'=>$v['creditcard_id']))->find();
                $res[$k]['kname'] = $creinfo['name']; // 卡名称
                $res[$k]['bankname'] = M('bank')->where(array('bank_id'=>$creinfo['bank_id']))->getField('name');
                # 申请提交
                $newtime1 = '...';
                $newtime2 = '...';
                $newtime3 = '...';

                if($v['status'] == 1)
                {
                    $newstatus = '申请已提交';
                    $newtime1 = date("Y-m-d H:i",$v['t']);

                }else if($v['status'] == 2)
                {
                    $newstatus = '正在审核中';
                    $newtime1 = date("Y-m-d H:i",$v['t']);
                    $newtime2 = date("Y-m-d H:i",$v['t1']);

                }else if($v['status'] == 4)
                {
                    $newstatus = '审核通过等待制卡';
                    $newtime1 = date("Y-m-d H:i",$v['t']);
                    $newtime2 = date("Y-m-d H:i",$v['t1']);
                    $newtime3 = date("Y-m-d H:i",$v['success_time']);

                }else if($v['status'] == 3)
                {
                    $newstatus = '审核未通过';
                    $newtime1 = date("Y-m-d H:i",$v['t']);
                    $newtime2 = date("Y-m-d H:i",$v['t1']);
                    $newtime3 = date("Y-m-d H:i",$v['error_time']);
                }
                $res[$k]['state'] = $v['status'];

                $res[$k]['newtime1'] = $newtime1;
                $res[$k]['newtime2'] = $newtime2;
                $res[$k]['newtime3'] = $newtime3;


                # 进度状态
                $res[$k]['newstatus'] = $newstatus;
                $res[$k]['time'] = $newstatus;
            }

            echo json_encode(array('code' => 200, 'msg' => '申请进度列表','data'=>$res));
            die;
        }else{
            echo json_encode(array('code' => 400, 'msg' => '没有申卡记录'));
            die;
        }
    }

    # 测试分销订单 ( 后台审核成功 )
    public function setFxOrder()
    {
        $order = trim($_REQUEST['order']);
        $res = R("Func/Fenxiao/fenxiaoCCOrder",array($order));
        dump($res);die;
    }

    # 实时播报
    public function ssbb()
    {
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(

        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断


        $jl = M("creditcard_jilu")->field('creditcard_id,name')->order('creditcard_jilu_id desc')->limit(0,10)->select();
        $arr = array();
        if($jl)
        {
            foreach ($jl as $k => $v)
            {
                $crname = M("creditcard")->where(array('creditcard_id'=>$v['creditcard_id']))->getField('name');
                $arr[] = $this->substr_cut($v['name'])."刚刚申请了".$crname;
            }
        }
        echo json_encode(array('code' => 200, 'msg' => '实时播报','data'=>$arr));
        die;
    }

    public function substr_cut($user_name)
    {
        $strlen     = mb_strlen($user_name, 'utf-8');
        $firstStr     = mb_substr($user_name, 0, 1, 'utf-8');
        $lastStr     = mb_substr($user_name, -1, 1, 'utf-8');
        return $strlen == 2 ? $firstStr . str_repeat('*', mb_strlen($user_name, 'utf-8') - 1) : $firstStr . str_repeat("*", $strlen - 2) . $lastStr;
    }

    # 身份证鉴权
    public function authRealname()
    {
        $requestId = 'SS'.date("YmdHis",time());
        $timestamp = date("YmdHis",time());
        $pdata = array(
            'name' => '蔡俊锋',  // 姓名
            'idNumber' => '445221199702234558',  // 身份证
            'requestId' => $requestId, // each : 'ab__'.date('Ymdhis',time()); // 订单号
            'timestamp' => $timestamp
        );
        $res = R("Payapi/AuthApi/AuthRealname",array($pdata,$this->uid));
        dump($res);
    }
    /*
     *
     * */
   #新版 信用卡首页
    public function indexcs(){
//        if (!$this->sign) {
//            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
//            die;
//        }
        $array = array(
        );
//        $this->parem = array_merge($this->parem, $array);
//        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
//        if ($this->sign !== $msg) {
//            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
//            die;
//        }
//        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
//        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        // 轮播图
        $data['lunbo'] = R("Func/Func/getLunbo",array(3));

        $updatebao = array(
            '**凤刚刚申请上海银行信用卡',
            '**涛刚刚申请上海银行信用卡',
            '**麦刚刚申请上海银行信用卡',
            '**振刚刚申请上海银行信用卡',
            '**明刚刚申请上海银行信用卡',
            '**志刚刚申请上海银行信用卡',
            '**卫刚刚申请上海银行信用卡',
            '**琪刚刚申请上海银行信用卡',
            '**红刚刚申请上海银行信用卡',
            '**锋刚刚申请上海银行信用卡',
        );
        // 实时播报
        $data['updatebao'] = $updatebao;
        // 热门推荐 - 首页显示
         $data['cateList']=M('creditcard_cate')->where(array('is_index'=>1,'status'=>1))->limit('0,8')->select();
         foreach ($data['cateList'] as $k=>$v){
             $data['cateList'][$k]['icon']= enThumb('./Uploads/',$v['icon']);
         }
         //        $data['hotList'] = R("Func/Creditcard/getList",array($w));
        //通道银行
        $w['a.status']=1;
        $data['bankList']=M('Creditcard_td')->alias('a')->field('a.*,b.name,b.icon')->join(' left join y_bank as b on a.bank_id=b.bank_id')->where($w)->group('bank_id ')->select();
        foreach ($data['bankList'] as $k=>$v){
            $data['bankList'][$k]['creditcard_id']=M('creditcard')->field('creditcard_id,creditcard_td_id')->where(array('creditcard_td_id'=>$v['creditcard_td_id']))->find();
        }
//                dump($data);die;
        echo json_encode(array('code' => 200, 'msg' => '首页数据','data'=>$data));
    }
    #对应分类的信用卡
    public function creditcardCateList(){
        $creditcard_cate_id=trim($_REQUEST['creditcard_cate_id']);  //分类id
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'creditcard_cate_id'=>$creditcard_cate_id,
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $data =M('creditcard')->field('creditcard_id,creditcard_cate_id,type,name,pic,pic_thumb,tagids,is_index,status,creditcard_td_id,bank_id,t,ms')->where(array('creditcard_cate_id'=>$creditcard_cate_id))->select();
        foreach ($data as $k=>$v){
             $data[$k]['pic']=enThumb('./Uploads/',$v['pic']);
             $data[$k]['pic_thumb']=enThumb('./Uploads/',$v['pic_thumb']);
             $data[$k]['ms']=htmlspecialchars_decode($v['ms']);
        }
//        dump($data);die;
        echo json_encode(array('code'=>200,'msg'=>'获取成功','data'=>$data));die;
    }
    #信用卡详细信息
    public function cardDetails(){
        $creditcard_id=trim($_REQUEST['creditcard_id']);
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'creditcard_id'=>$creditcard_id,
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

        $data = M('creditcard')->where(array('creditcard_id'=>$creditcard_id))->find();
            $data['pic']=enThumb('./Uploads/',$data['pic']);
            $data['pic_thumb']=enThumb('./Uploads/',$data['pic_thumb']);
        $ids=explode(',',$data['tagids']);
        $where['creditcard_tag_id']=array('in',$ids);
        $data['tagInfo'][]=M('creditcard_tag')->where($where)->select();
        $data['quanyi']=htmlspecialchars_decode($data['quanyi']);
        $data['fy_info']=htmlspecialchars_decode($data['fy_info']);
        $data['yl_url']=M('creditcard_td')->where(array('creditcard_td_id'=>$data['creditcard_td_id']))->getField('yl_url');
        if ($data['yl_url']){
            echo json_encode(array('code'=>2048,'msg'=>'获取成功','data'=>$data));die;
        }else{
            echo json_encode(array('code'=>2048,'msg'=>'获取失败'));die;
        }
    }
    #核对申请人信息
    public function selectInfo(){
        $uid=trim($_REQUEST['uid']);
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid'=>$uid,
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密
        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断

       $data = M('myrealname')->field('nickname,idcard,user_id')->where(array('user_id'=>$uid))->find();
        $data['phone']=M('user')->where(array('user_id'=>$uid))->getField('phone');
       if ($data){
           echo json_encode(array('code'=>2048,'msg'=>'获取成功','data'=>$data));die;
       }else{
           echo json_encode(array('code'=>2059,'msg'=>'信息为空,请绑定身份证信息'));die;
       }
    }
    # 添加信用卡申请记录
    public function addJl()
    {
        $idcard = trim($_REQUEST['idcard']);
        $name = trim($_REQUEST['name']);
        $phone = trim($_REQUEST['phone']);
        $creditcard_id = intval($_REQUEST['creditcard_id']); # 信用卡ID


        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'creditcard_id' => $creditcard_id,
            'name' => urldecode($name),
            'phone' => $phone,
            'idcard' => $idcard
        );
        $this->parem = array_merge($this->parem, $array);

        $msg = R('Func/Func/getKey', array($this->parem));//返回加密

        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断


        $ifinfo = M("creditcard_jilu")->where(array('user_id'=>$this->uid,'idcard'=>$idcard,'creditcard_id'=>$creditcard_id))->find();
        if($ifinfo)
        {
            echo json_encode(array('code' => 400, 'msg' => '请不要第二次申请该信用卡'));die;
            $is_one=2;
        }else{
            $is_one=1;
        }

        if(!$creditcard_id)
        {
            echo json_encode(array('code' => 400, 'msg' => '请选择银行'));
            die;
        }
        if(!$name)
        {
            echo json_encode(array('code' => 400, 'msg' => '请填写姓名'));
            die;
        }
        if(!$phone)
        {
            echo json_encode(array('code' => 400, 'msg' => '请填写手机号码'));
            die;
        }
        if(!$idcard)
        {
            echo json_encode(array('code' => 400, 'msg' => '请填写身份证号码'));
            die;
        }

        $res = 1;
//        if(!$ifinfo)
//        {
        # 用户名
        $userId = M("user")->where(array('user_id'=>$this->uid))->getField('phone');
        $order_sn = 'CS'.date("YmdHis",time());

        # 对应信用卡申请通道
        $pay_supply_id = M("creditcard")->where(array('creditcard_id'=>$creditcard_id))->find();

        $creditcard_td_id=M("creditcard_td")->where(array('creditcard_td_id'=>$pay_supply_id['creditcard_td_id'],'status'=>1))->getField('creditcard_td_id');

//            $user_pay_supply_id = M("user_pay_supply")->where(array('pay_supply_id'=>$pay_supply_id['pay_supply_id'],'status'=>1))->getField('user_pay_supply_id');
        $pdata = array(
            'userId' => $userId,
            'name' => urldecode($name),
            'idcard' => $idcard,
            'phone' => $phone,
            'user_id' => $this->uid,
            't' => time(),
            'order_sn' => $order_sn,
            'creditcard_td_id' => $creditcard_td_id,
            'sh_ordersn' => 'QUCS'.mt_rand(100000,999999), // 自动生成可分销的订单类型
            'money_type_id' => 17, //信用卡申请 /类型
            'creditcard_id' => $creditcard_id,
            'is_one'=>$is_one
        );
        # 申请记录表
        $res = M("creditcard_jilu")->add($pdata);
//        }


        if($res)
        {
            echo json_encode(array('code' => 200, 'msg' => '申请成功'));
            die;
        }else{
            echo json_encode(array('code' => 400, 'msg' => '操作失败,请重试'));
            die;
        }
    }

    # 添加信用卡申请记录
    public function addJlcs()
    {
        $idcard = trim($_REQUEST['idcard']);
        $name = trim($_REQUEST['name']);
        $phone = trim($_REQUEST['phone']);
        $uid = $_REQUEST['uid'];
        $orderId = $_REQUEST['orderId'];

        $credit_product_id = $_REQUEST['credit_product_id'];

        if (!$this->sign){
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'name' => urldecode($name),
            'phone' => $phone,
            'idcard' => $idcard,
            'orderId'=> $orderId
        );
        $this->parem = array_merge($this->parem, $array);

        $msg = R('Func/Func/getKey', array($this->parem));//返回加密

        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断





        $ifinfo = M("creditcard_jilu")->where(array('name'=>urldecode($name),'phone'=>$phone,'idcard'=>$idcard))->find();
        if($ifinfo)
        {
            $is_one=2;
        }else{
            $is_one=1;
        }

        $cfInfo=M('credit_record')->where(array('name'=>urldecode($name),'mobile'=>$phone,'idcard'=>$idcard))->find();
        if ($cfInfo)
        {
            $is_two=2;
        }else{
            $is_two=1;
        }

        if(!$name)
        {
            echo json_encode(array('code' => 400, 'msg' => '请填写姓名'));
            die;
        }
        if(!$phone)
        {
            echo json_encode(array('code' => 400, 'msg' => '请填写手机号码'));
            die;
        }
        if(!$idcard)
        {
            echo json_encode(array('code' => 400, 'msg' => '请填写身份证号码'));
            die;
        }

//        if(!$credit_product_id)
//        {
//            echo json_encode(array('code' => 400, 'msg' => '请传申请银行'));
//            die;
//        }

        $res = 1;

        # 用户名
        $userId = M("user")->where(array('user_id'=>$this->uid))->getField('phone');
//        $order_sn = 'CS'.date("YmdHis",time()).mt_rand(1000,9999);
        $order_sn=$_REQUEST['orderId'];

        if(!$order_sn)
        {
            echo json_encode(array('code' => 400, 'msg' => '请传订单号!'));
            die;
        }
        if (strlen($order_sn) > 32 ){
            echo json_encode(array('code' => 400, 'msg' => '订单号长度不能超过32位!'));
            die;
        }

        $pdata['userId']=$userId;
        $pdata['name']=urldecode($name);
        $pdata['idcard']=$idcard;
        $pdata['phone']=$phone;
        $pdata['user_id']=$this->uid;
        $pdata['t']=time();
        $pdata['order_sn']=$order_sn;
        $pdata['sh_ordersn']='QUCS'.mt_rand(100000,999999);
        $pdata['money_type_id']=17;
        $pdata['is_one']=$is_one;
        $pdata['credit_product_id']=$credit_product_id;

        # 申请记录表
        $res = M("creditcard_jilu")->add($pdata);
        $insertData['name']=urldecode($name);
        $insertData['idcard']=$idcard;
        $insertData['mobile']=$phone;
        $insertData['userId']=$userId;
        $insertData['idcard']=$idcard;
        $insertData['user_id']=$this->uid;
        $insertData['t']=time();
        $insertData['pt_ordersn']=$order_sn;
        $insertData['order_sn']='QUCS'.mt_rand(100000,999999);
        $insertData['money_type_id']=17;
        $insertData['is_one']=$is_two;
        $insertData['credit_product_id']=$credit_product_id;

        M("credit_record")->add($insertData);
        if($res)
        {
            echo json_encode(array('code' => 200, 'msg' => '申请成功'));
            die;
        }else{
            echo json_encode(array('code' => 400, 'msg' => '操作失败,请重试'));
            die;
        }
    }

    #添加 网贷记录
    public function loansRecord(){
        $idcard = trim($_REQUEST['idcard']);
        $name = trim($_REQUEST['name']);
        $phone = trim($_REQUEST['phone']);
        $uid = $_REQUEST['uid'];
        $order_sn=$_REQUEST['orderId'];
        $userId = M("user")->where(array('user_id'=>$uid))->getField('phone');
        if (!$this->sign) {
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'uid' => $this->uid,
            'name' => urldecode($name),
            'phone' => $phone,
            'idcard' => $idcard,
            'orderId'=>$order_sn
        );
        $this->parem = array_merge($this->parem, $array);
        $msg = R('Func/Func/getKey', array($this->parem));//返回加密

        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
        if(!$name)
        {
            echo json_encode(array('code' => 400, 'msg' => '请填写姓名'));
            die;
        }
        if(!$order_sn)
        {
            echo json_encode(array('code' => 400, 'msg' => '请传订单号!'));
            die;
        }
        if (strlen($order_sn) > 32 ){
            echo json_encode(array('code' => 400, 'msg' => '订单号长度不能超过32位!'));
            die;
        }
        if(!$phone)
        {
            echo json_encode(array('code' => 400, 'msg' => '请填写手机号码'));
            die;
        }
        if(!$idcard)
        {
            echo json_encode(array('code' => 400, 'msg' => '请填写身份证号码'));
            die;
        }
//        $order_sn = 'Jd'.date("YmdHis",time()).mt_rand(1000,9999);
        $pdata['name']=urldecode($name);
        $pdata['idcard']=$idcard;
        $pdata['mobile']=$phone;
        $pdata['user_id']=$uid;
        $pdata['userId']=$userId;

        $pdata['pt_ordersn']=$order_sn;
        $pdata['t']=time();
        $res = M('loans_record')->add($pdata);
        if($res!==false)
        {
            echo json_encode(array('code' => 200, 'msg' => '添加记录成功'));
            die;
        }else{
            echo json_encode(array('code' => 400, 'msg' => '操作失败,请重试'));
            die;
        }
    }

    #查询进度 接口
    public function selectJinDu(){
       $data =  M('credit_product')->where(array('status'=>1))->select();
        echo json_encode(array('code'=>1000,'msg'=>'获取成功','data'=>$data));die;
    }
    #新信用卡首页
    public function newCreditSq(){
        if (!$this->sign){
            echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
            die;
        }
        $array = array(
            'user_id' => $_REQUEST['user_id'],
        );
        $this->parem = array_merge($this->parem, $array);

        $msg = R('Func/Func/getKey', array($this->parem));//返回加密

        if ($this->sign !== $msg) {
            echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
            die;
        }
        R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
        $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断




        $data=array();
        $arr=array();

        #轮播图
        $data['lunbo'] = R("Func/Func/getLunbo",array(3));

        #申请记录
        $updatebao = M('credit_record')->field('credit_record_id,name,credit_product_id')->order('credit_record_id desc')->limit(0,10)->select();
        foreach($updatebao as $k=>$v){
            if ($v['credit_product_id']){
                $bName = M('credit_product')->where(array('credit_product_id'=>$v['credit_product_id']))->getField('name');
            }else{
                $bName = '上海银行';
            }
            $arr[] = $this->substr_cut($v['name']).'申请了'.$bName.'信用卡';
        }
        if (!$arr){
            $arr = array(
                '龚*凤刚刚申请上海银行信用卡',
                '曾*涛刚刚申请广州银行信用卡',
                '麦*麦刚刚申请招商银行信用卡',
                '林*振刚刚申请华夏银行信用卡',
                '李*明刚刚申请兴业银行信用卡',
                '周*志刚刚申请交通银行信用卡',
                '周*卫刚刚申请民生银行信用卡',
                '左*琪刚刚申请建设银行信用卡',
                '李*红刚刚申请中国银行信用卡',
                '肖*锋刚刚申请上海银行信用卡',
            );
        }
        $data['updatebao'] = $arr;
        $sqInfo = M('credit_product')->where(array('is_sq'=>1))->order('credit_product_id desc')->select();
        foreach($sqInfo as $k=>$v){
           if ($v['credit_product_id'] == 1){ #交通
                $add1 = M('credit_record')->where(array('credit_product_id'=>$v['credit_product_id']))->count();
               $sqInfo[$k]['sq_number'] = 1102 + $add1;
                $sqInfo[$k]['sq_rate'] = '90%';
               $sqInfo[$k]['is_gf'] = 0;

           }elseif($v['credit_product_id'] == 2){ #建设银行
               $add2 = M('credit_record')->where(array('credit_product_id'=>$v['credit_product_id']))->count();
               $sqInfo[$k]['sq_number'] = 1505 + $add2;
               $sqInfo[$k]['sq_rate'] = '92%';
               $sqInfo[$k]['is_gf'] = 0;

           }elseif($v['credit_product_id'] == 3){ #兴业银行
               $add3 = M('credit_record')->where(array('credit_product_id'=>$v['credit_product_id']))->count();
               $sqInfo[$k]['sq_number'] = 1801 + $add3;
               $sqInfo[$k]['sq_rate'] = '93%';
               $sqInfo[$k]['is_gf'] = 0;

           }elseif($v['credit_product_id'] == 5){ #上海银行
               $add4 = M('credit_record')->where(array('credit_product_id'=>$v['credit_product_id']))->count();
               $sqInfo[$k]['sq_number'] = 1443 + $add4;
               $sqInfo[$k]['sq_rate'] = '94%';
               $sqInfo[$k]['is_gf'] = 0;

           }elseif($v['credit_product_id'] == 11){ #华夏银行
               $add5 = M('credit_record')->where(array('credit_product_id'=>$v['credit_product_id']))->count();
               $sqInfo[$k]['sq_number'] = 1903 + $add5;
               $sqInfo[$k]['sq_rate'] = '94%';
               $sqInfo[$k]['is_gf'] = 0;

           }elseif($v['credit_product_id'] == 12){ #民生银行
               $add6 = M('credit_record')->where(array('credit_product_id'=>$v['credit_product_id']))->count();
               $sqInfo[$k]['sq_number'] = 2011 + $add6;
               $sqInfo[$k]['sq_rate'] = '93%';
               $sqInfo[$k]['is_gf'] = 0;

           }elseif($v['credit_product_id'] == 14){ #招商银行
               $add7 = M('credit_record')->where(array('credit_product_id'=>$v['credit_product_id']))->count();
               $sqInfo[$k]['sq_number'] = 2159 + $add7;
               $sqInfo[$k]['sq_rate'] = '91%';
               $sqInfo[$k]['is_gf'] = 0;

           }elseif($v['credit_product_id'] == 15){ #工商银行
               $add8 = M('credit_record')->where(array('credit_product_id'=>$v['credit_product_id']))->count();
               $sqInfo[$k]['sq_number'] = 2036 + $add8;
               $sqInfo[$k]['sq_rate'] = '95%';
               $sqInfo[$k]['is_gf'] = 0;

           }elseif($v['credit_product_id'] == 16){ #中国银行
               $add9 = M('credit_record')->where(array('credit_product_id'=>$v['credit_product_id']))->count();
               $sqInfo[$k]['sq_number'] = 985 + $add9;
               $sqInfo[$k]['sq_rate'] = '94%';
               $sqInfo[$k]['is_gf'] = 0;

           }elseif($v['credit_product_id'] == 17){ #上海银行 官方链接
               $add10 = M('credit_record')->where(array('credit_product_id'=>$v['credit_product_id']))->count();
               $sqInfo[$k]['sq_number'] = 1899 + $add10;
               $sqInfo[$k]['sq_rate'] = '94%';
               $sqInfo[$k]['is_gf'] = 1;
           }
        }
        $data['bankInfo'] = $sqInfo;
        echo json_encode(array('code'=>1010,'msg'=>'获取成功','data'=>$data));die;
    }
}