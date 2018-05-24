<?php
/**
 * Created by 银迅通支付网络公司.
 * User: Jeffery
 * Date: 2018/1/22
 * Time: 9:06
 * 基本功能
 */

namespace Payapi\Controller;
use Think\Controller;

class BaseFuncController extends Controller
{
    public $uid;
    public $istest = 1;# 1测试2正式 (请勿修改)
    public function _initialize()
    {
        $this->uid = trim($_REQUEST['uid']);
        if($this->istest != 1)
        {
            $this->parem = array(
                'signType' => $_REQUEST['signType'],
                'timestamp' => $_REQUEST['timestamp'],
                'dataType' => $_REQUEST['dataType'],
                'inputCharset' => $_REQUEST['inputCharset'],
                'version' => $_REQUEST['version'],
            );
            $this->sign = $_REQUEST['sign'];
        }
    }

    # 文章协议 - 推客
    public function getXieyi()
    {
        if($this->istest != 1)
        {
            if (!$this->sign) {
                echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
                die;
            }
            $array = array(
                'uid' => $this->uid,
                'type' => intval($_REQUEST['type']),
                'level' => trim($_REQUEST['level'])
            );
            $this->parem = array_merge($this->parem, $array);
            $msg = R('Func/Func/getKey', array($this->parem));//返回加密
            if ($this->sign !== $msg) {
                echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
                die;
            }
            R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
            $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
        }
        /*
        $type = intval($_REQUEST['type']);
        $res = M("xieyi")->where(array('type'=>$type))->find();
        $this->assign("article",$res);
        */
        $level = trim($_REQUEST['level']);
        if (!trim($_REQUEST['level']))
        {
            $level = M("user")->where(array('user_id'=>$this->uid))->getField('level');
        }


        $this->assign("get_userinfo",$this->getInfo($level));
        $xinfo = M("xieyi_registerinfo")->where(array('user_id'=>$this->uid))->find();
//        var_dump($xinfo);
        if($xinfo)
        {
            $xinfo['qm_thumb'] = enThumb("./Uploads/",$xinfo['qm_thumb']);
        }
        $this->assign("xieyi",$xinfo);
        if(intval($_REQUEST['type']) == 1)
        {
            $this->display('xieyi'); # 推客
        }
    }

    # 文章协议 - 推客
    public function getXieyiCeshi()
    {
        if($this->istest != 1)
        {
            if (!$this->sign) {
                echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
                die;
            }
            $array = array(
                'uid' => $this->uid,
                'type' => intval($_REQUEST['type']),
                'level' => trim($_REQUEST['level'])
            );
            $this->parem = array_merge($this->parem, $array);
            $msg = R('Func/Func/getKey', array($this->parem));//返回加密
            if ($this->sign !== $msg) {
                echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
                die;
            }
            R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
            $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
        }
        /*
        $type = intval($_REQUEST['type']);
        $res = M("xieyi")->where(array('type'=>$type))->find();
        $this->assign("article",$res);
        */
        $level = trim($_REQUEST['level']);
        if (!trim($_REQUEST['level']))
        {
            $level = M("user")->where(array('user_id'=>$this->uid))->getField('level');
        }
        $this->assign("get_userinfo",$this->getInfo($level));
        $xinfo = M("xieyi_registerinfo")->where(array('user_id'=>$this->uid))->find();
//        var_dump($xinfo);
        if($xinfo)
        {
            $xinfo['qm_thumb'] = enThumb("./Uploads/",$xinfo['qm_thumb']);
        }
        $this->assign("xieyi",$xinfo);
        if(intval($_REQUEST['type']) == 1)
        {
            $this->display('xieyi'); # 推客
        }
    }

    # 信息
    public function getInfo($level)
    {
        $myrealName = $this->getMyrealName();
        $mybankJs = $this->getMybankJs();
        $userInfo = $this->getUserInfo($level);
        $getConfig = $this->getSystymConfig();
        $d = array(
            'getConfig' => $getConfig, # 配置信息
            "myrealName" => $myrealName, # 实名认证
            "mybankJs" => $mybankJs, # 默认结算卡
            "userInfo" => $userInfo, # 用户信息
        );
        return $d;
    }
    # 添加协议信息
    public function xieyiAdd()
    {
        $qm_thumb = trim($_REQUEST['qm_thumb']);
        $type = intval($_REQUEST['type']); // 默认为1
        $level = trim($_REQUEST['level']);//等级
        if($this->istest != 1)
        {
            if (!$this->sign) {
                echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
                die;
            }
            $array = array(
                'uid' => $this->uid,
                'qm_thumb' => $qm_thumb,
                'type' => $type,
                'level' => $level
            );
            $this->parem = array_merge($this->parem, $array);
            $msg = R('Func/Func/getKey', array($this->parem));//返回加密
            if ($this->sign !== $msg) {
                echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
                die;
            }
            R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
            $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
        }
        $ifInfo = M("xieyi_registerinfo")->where(array('user_id'=>$this->uid))->find();
        /**/
        if(!$ifInfo)
        {
            if($type=1)
            {
                $xieyi_id = 4;
            }
            $add['user_id'] = $this->uid;
            $add['xieyi_id'] = $xieyi_id;
            $add['qm_thumb'] = $qm_thumb;
            $add['level'] = $level;
            $add['t'] = time();
            M("xieyi_registerinfo")->add($add);
        }else{
            if($type=1)
            {
                $xieyi_id = 4;
            }
            $add['xieyi_id'] = $xieyi_id;
            $add['qm_thumb'] = $qm_thumb;
            $add['t'] = time();
            $add['level'] = $level;
            M("xieyi_registerinfo")->where(array('user_id'=>$this->uid))->save($add);
        }
        echo json_encode(array("code"=>400,"msg"=>"success"));exit;
    }

    public function levelInfo($level)
    {
        $dinfo = M("distribution_level")->field('levelname,ordermoney')->where(array('distribution_level_id'=>$level,'is_use'=>1))->find(); # ordermoney
        return $dinfo;
    }

    public function getUserInfo($level)
    {
        $userInfo = M("user")->field('level,phone,lxphone,isopen')->where(array('user_id'=>$this->uid))->find();

        /*
        if($userInfo['isopen'] != 1)
        {
            return array("code"=>200,"msg"=>"您的账号已冻结");
        }
        */
        /*自己
        $dinfo = M("distribution_level")->field('levelname,ordermoney')->where(array('distribution_level_id'=>$userInfo['level'],'is_use'=>1))->find(); # ordermoney
        */
        # 等级
        $userInfo['level'] = $this->levelInfo($level);


//        dump($userInfo);
        return $userInfo;
    }

    public function getMyrealName()
    {
        $myrealName = M("myrealname")->where(array('user_id'=>$this->uid,'status'=>1))->find();
        if(!$myrealName)
        {
            return array("code"=>200,"msg"=>"请实名认证");
        }
        return $myrealName;
    }

    public function getMybankJs()
    {
        $mybankJs = M("mybank")->where(array('user_id'=>$this->uid,'type'=>1,'jq_status'=>3,'status'=>1,'is_normal'=>1))->find();
        if(!$mybankJs)
        {
            return array("code"=>200,"msg"=>"请绑定默认结算卡");
        }
        return $mybankJs;
    }

    # 系统配置
    public function getSystymConfig($config_id)
    {
        return M("site_config")->where(array('config_id'=>$config_id))->find();
    }

    # 图片上传
    public function imgUpload()
    {
        if($this->istest != 1)
        {
            $is_post = intval($_REQUEST['is_post']); // 默认为1
            if (!$this->sign) {
                echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
                die;
            }
            $array = array(
                'is_post' => $is_post,
            );
            $this->parem = array_merge($this->parem, $array);
            $msg = R('Func/Func/getKey', array($this->parem));//返回加密
            if ($this->sign !== $msg) {
                echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
                die;
            }
            R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
            $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
        }

        if(IS_POST){

            $data = I('post.');

            // input

            if(!$data['cart_img'])

            {

                if ($_FILES['cart_img']['error'] == 0){

                    $config = array(

                        'maxSize'    =>    10 * 1024 * 1024, //tp里面的单位单位是B(字节)8b  1Byte = 8bits  100Mbits / 8

                        'rootPath'   =>    './Uploads/', // 上传根目录（必须手工建立）

                        'savePath'   =>    'xieyi/', //上传的二级目录（不用自己建立）

                        'saveName'   =>    array('uniqid',''),

                        'exts'       =>    array('jpg', 'gif', 'png', 'jpeg'),

                    );

                    $upload = new \Think\Upload($config);// 实例化上传类

                    // 上传单个文件

                    $info   =   $upload->upload();

                    // var_dump($info);

                    if(!$info) {// 上传错误提示错误信息
                        echo json_encode(array('code'=>2015,'msg'=>$upload->getError()));die;
                    }else{

                        $data['cart_img'] = $info['cart_img']['savepath'].$info['cart_img']['savename'];
                        $image = new \Think\Image();
                        if ($image->open('./Uploads/' . $data['cart_img'])) {
                            $image->thumb(100, 100)->save('./Uploads/' .$data['cart_img'] . '_thumb.jpg');
                        };
                        // $res = M('mybank')->where(array('uid'=>$uid))->save($data);
                        echo json_encode(array('code'=>2018,'msg'=>'上传成功','img'=>$data['cart_img']));exit;
                    }
                }
            }
        }
    }

    # 文件下载显示
    public function fileUpload()
    {
        if($this->istest != 1)
        {
            if (!$this->sign) {
                echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
                die;
            }
            $array = array(
                'uid' => $this->uid,
            );
            $this->parem = array_merge($this->parem, $array);
            $msg = R('Func/Func/getKey', array($this->parem));//返回加密
            if ($this->sign !== $msg) {
                echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
                die;
            }
            R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
            $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
        }
        $w = array();
        # 包含电子发票 和 其他协议
        $xieyi_id = M("xieyi_registerinfo")->where(array('user_id'=>$this->uid))->getField('xieyi_id',true);
        if(!$xieyi_id)
        {
            echo json_encode(array("code"=>200,"msg"=>"无数据"));exit;
        }

        $infileid = 0;
        if($xieyi_id)
        {
            foreach ($xieyi_id as $k => $v)
            {
                $xieinfo = M("xieyi")->where(array('xieyi_id'=>$v))->find();
                if($xieinfo['xieyi_id'] == 4)
                {
                    $infileid = "2";
                }
                if($xieinfo['xieyi_id'] == 5){
                    $infileid .= ",1";
                }
            }
        }

        $w['file_upload_id'] = array('in',$infileid);
        $res = M("file_upload")->where($w)->order('file_upload_id desc')->select();

        foreach ($res as $k => $v)
        {
            if($v['file_url']) {
                $res[$k]['file_url'] = $v['file_url'].$this->uid;
            }else{
                $res[$k]['file_url'] = "";
            }
            $xieyi = M("xieyi_registerinfo")->where(array('user_id'=>$this->uid))->find();
            $res[$k]['is_download'] = $xieyi['is_download'];
        }
        echo json_encode(array("code"=>400,"msg"=>"文件下载","data"=>$res));exit;
    }

    # 文件下载次数
    public function fileDown()
    {
        $xieyi_id = trim($_REQUEST['xieyi_id']);
        if($this->istest != 1)
        {
            if (!$this->sign) {
                echo json_encode(array('code' => 10005, 'msg' => '加密字符串不存在'));
                die;
            }
            $array = array(
                'uid' => $this->uid,
                'xieyi_id' => $xieyi_id,
            );
            $this->parem = array_merge($this->parem, $array);
            $msg = R('Func/Func/getKey', array($this->parem));//返回加密
            if ($this->sign !== $msg) {
                echo json_encode(array('code' => 10004, 'msg' => '禁止访问'));
                die;
            }
            R('Func/Func/getTwoSign', array($this->sign)); //判断是否相同参数传第二次(如果是正常调用接口,参数不可能和sign相同)
            $_SESSION['last_sign'] = $this->sign;//把sign存入session 为作判断
        }
        M("xieyi_registerinfo")->where(array('xieyi_id'=>$xieyi_id,'user_id'=>$this->uid))->save(array('is_download'=>1));
        echo json_encode(array("code"=>400,"msg"=>"文件已下载"));exit;
    }


    ### 调试
    # 通道ID
    public function td()
    {
        $tylist = $this->getSkTds();
        dump($tylist);
    }

    public function getSkTds($td=1)

    {

        $tdid = M('pay_supply_cate')->where(array('pay_supply_cate_id'=>intval($td)))->getField('pay_supply_cate_id');
        $res = array();
//        $tyname = $this->getPayType($td); //快捷支付
        $tyname = '快捷支付';
        if($tdid)

        {

            $sid = M('pay_supply')->field('pay_supply_id,englistname')->where(array('pay_supply_cate_id'=>$tdid,'status'=>1))->order('pay_supply_id desc')->select();
            if($sid)

            {

                $exsid = "";

                foreach ($sid as $k => $v){

                    $exsid .= ",".$v['pay_supply_id'];

                }
                $exsid = ltrim($exsid,',');

//                dump($exsid);



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





    # 拓客奖排行榜
    public function statistics()
    {
        $ispage = 1;
        $p = intval($_REQUEST['p']);
        $size = intval($_REQUEST['size']);
        $today = intval($_REQUEST['today']);
        if($today==2){
            $yesterday = 1;
            $today = 0;
        }else{
            $yesterday = 0;
            $today = 1;
        }
        /*
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
        */

        $w = array();
        $list = R("Func/SbaseFunc/statisticsFunc",array($w,'2016-04-15,2030-04-17',$yesterday,$today));  # 全部


        # 筛选时间
        if ($yesterday == 1)  # 昨天
        {
            $day = date("Y-m-d", strtotime(date("Y-m-d")) - 86400);
        } elseif ($today == 1)                  # 今天
        {
            $day = date("Y-m-d");
        }

        # 合并模拟数据
        $mnlist = R("Func/SbaseFunc/statisticsMiData");  #
        $mnlist = R("Func/SbaseFunc/searchArray",array($mnlist,'time',$day));
        $list = array_merge($list,$mnlist);


        # dump($list);

        # 重新计算金额
        foreach ($list as $k6 => $v6)
        {
            $newtpcount = count($v6['tpcount']);
            $newypcount = count($v6['ypcount']);

            if(empty($v6['tpcount']))
            {
                $newtpcount = $v6['tpcountSum'];
            }
            if(empty($v6['ypcount']))
            {
                $newypcount = $v6['ypcountSum'];
            }

            $keymoney = R("Func/SbaseFunc/statisticsJs",array($v6['level'],$newtpcount,$newypcount));
            # echo $keymoney."===<br/>";
            $list[$k6]['keymoney'] = $keymoney;
        }
        # dump($list);

        $newlist = R("Func/SbaseFunc/sortArrByManyField",array($list,'keymoney',SORT_DESC,'tpcountSum',SORT_DESC,'ypcountSum',SORT_DESC));
        $pkk = 1;
        foreach ($newlist as $k => $v)
        {
            $newlist[$k]['pkk'] = $pkk;
            $pkk++;
        }
        $mylive = R("Func/SbaseFunc/searchArray",array($newlist,'user_id',$this->uid));
        # 我的战绩
        # dump($newlist);
        # dump($mylive);
        # 分页
        if($ispage==1)
        {
            $offset = ($p - 1) * $size;
            if($newlist && $size > 0){
                $ulist = array_slice($newlist,$offset,$size);
            }else{
                $ulist = array();
            }
        }else{
            $ulist = $newlist;
        }

        $news = array();
        foreach ($ulist as $k => $v)
        {
            if($v['tpcountSum'] != 0 || $v['ypcountSum'] != 0 )
            {
                $news[] = $v;
            }
        }


        $newsMylive = array();
        foreach ($mylive as $k => $v)
        {
            if($v['tpcountSum'] != 0 || $v['ypcountSum'] != 0 )
            {
                $newsMylive[] = $v;
            }
        }

        # 奖励说明
        $statisticsSm = R("Func/SbaseFunc/statisticsSm");

        echo json_encode(array("code"=>400,"msg"=>"排行榜首页","statisticsSm"=>$statisticsSm,"newlist"=>$news,"mylive"=>$newsMylive));exit;
    }

    # 我的战绩
    public function statisticsMy()
    {
        $w = array();
        $w['user_id'] = trim($_REQUEST['uid']);
        $newarray = array();
        $today = date("Y-m-d");
        $yy = array();
        $startDay = "2018-04-15";
        $offers = R("Func/SbaseFunc/diffBetweenTwoDays",array($startDay,$today));  # 全部
        for ($y = 0; $y<= $offers; $y++)
        {
            $yy[] = date('Y-m-d',strtotime("{$startDay} + {$y} day"));
        }
        for ($i=0;$i<=count($yy)-1;$i++)
        {
            $ulist = R("Func/SbaseFunc/statisticsFunc",array($w,$yy[$i]));  # 全部
            $ulist = $ulist[0];
            $newarray[] = $ulist;
        }
        # $list = R("Func/SbaseFunc/statisticsMYLive",array($w,'2018-04-17'));  # 全部
        # $newlist = R("Func/SbaseFunc/sortArrByManyField",array($list,'keymoney',SORT_DESC,'tpcountSum',SORT_DESC,'ypcountSum',SORT_DESC));
        # 重新计算金额
        foreach ($newarray as $k6 => $v6)
        {
            $newtpcount = count($v6['tpcount']);
            $newypcount = count($v6['ypcount']);

            if(empty($v6['tpcount']))
            {
                $newtpcount = $v6['tpcountSum'];
            }
            if(empty($v6['ypcount']))
            {
                $newypcount = $v6['ypcountSum'];
            }

            $keymoney = R("Func/SbaseFunc/statisticsJs",array($v6['level'],$newtpcount,$newypcount));
            # echo $keymoney."===<br/>";
            $newarray[$k6]['keymoney'] = $keymoney;
        }
        foreach ($newarray as $k2 => $v2)
        {
            $newarray[$k2]['strtotime'] = strtotime($v2['time']." 00:00:00");
//            $newarray[$k2]['strtotime'] = strtotime($v2['time']." 23:59:59");
        }
        $newlist = R("Func/SbaseFunc/sortArrByManyField",array($newarray,'strtotime',SORT_DESC));

        # dump($newlist);

        foreach ($newlist as $k => $v) {
            if($v['time'] == date("Y-m-d"))
            {
                $timename = "今天";
            }elseif ($v['time'] == date("Y-m-d",strtotime("-1 day")))
            {
                $timename = "昨天";
            }else{
                $timename = $v['time'];
            }
            $newlist[$k]['time'] = $timename;

            # 对比之前的数据，上升或者下降
            if($v['keymoney'] > $newlist[$k+1]['keymoney'])
            {
                $upup = 1;  # "↑"
            }elseif($v['keymoney'] < $newlist[$k+1]['keymoney'])
            {
                $upup = 2;  # "↓"
            }elseif($v['keymoney'] == $newlist[$k+1]['keymoney']){
                $upup = 3;  # '--'
            }else{
                $upup = 2;  # "↓"
            }
            $newlist[$k]['upup'] = $upup;

        }

        $newsMylive = array();
        foreach ($newlist as $k => $v)
        {
            if($v['tpcountSum'] != 0 || $v['ypcountSum'] != 0 )
            {
                $newsMylive[] = $v;
            }
        }

        echo json_encode(array("code"=>400,"msg"=>"我的战绩","mylive"=>$newsMylive));exit;
    }


    # 检测分销三级 == 单独
    public function threeYj()
    {
        # $pt_ordersn = "1523184159051";
        $pt_ordersn = trim($_REQUEST['pt_ordersn']);
        $users = M("money_detailed")->where(array('pt_ordersn'=>$pt_ordersn))->find();

        $txuser = $users['user_id']."->";

        $users = R("Func/Fenxiao/getUpUser",array($users['user_id']));  # 此方法只针对兜客有效/获取三级关系
        $txuser .= $users['user_id']."->";

        for ($i = 0; $i < 3; $i++) {
            $users = R("Func/Fenxiao/getUpUser",array($users['user_id']));  # 此方法只针对兜客有效/获取三级关系
            $txuser.=$users['user_id']."->";
        }
        echo substr($txuser,0,-2);
    }


    # 检测分销三级 - 全部(交易)
    public function threeYjFunc($pt_ordersn)
    {
        # $pt_ordersn = "1523184159051";
        # $pt_ordersn = trim($_REQUEST['pt_ordersn']);
        $orderarr = M("money_detailed")->where(array('pt_ordersn'=>$pt_ordersn))->find();
        $txuser = $orderarr['user_id']."->";
        $users = R("Func/Fenxiao/getUpUser",array($orderarr['user_id']));
        $txuser .= $users['user_id']."->";

        for ($i = 0; $i < 3; $i++) {

            $infof = M("money_detailed")->where(array('sh_ordersn'=>$pt_ordersn,'user_id'=>$users['user_id'],'money_type_id'=>9))->find(); # 没有分的其他用户上级 - 交易
            # echo $users['user_id'];
            if(empty($infof))
            {
                if(!empty($users['user_id']))
                {
                    R("Func/Fenxiao/addUserOrder",array($users['user_id'], $pt_ordersn, $i + 1, $users['utype'], $orderarr['pay_money'], $orderarr['user_pay_supply_id']));
                }
            }
            $users = R("Func/Fenxiao/getUpUser",array($users['user_id']));
            $txuser.=$users['user_id']."->";
        }

        # echo substr($txuser,0,-2);
    }


    # 检测分销三级 - 全部(结算)
    public function threeYjJsFunc($pt_ordersn)
    {
        # $pt_ordersn = "1523184159051";
        # $pt_ordersn = trim($_REQUEST['pt_ordersn']);
        $oldorder = $pt_ordersn;
        $order = "JS" . $pt_ordersn;
        $orderarr = M("moneyjs_detailed")->where(array('pt_ordersn'=>$oldorder))->find();

        $txuser = $orderarr['user_id']."->";
        $users = R("Func/Fenxiao/getUpUser",array($orderarr['user_id']));
        $txuser .= $users['user_id']."->";
        for ($i = 0; $i < 3; $i++) {
            $where['pt_ordersn'] = array('eq', $oldorder);
            $infof = M("money_detailed")->where(array('sh_ordersn'=>$order,'user_id'=>$users['user_id'],'money_type_id'=>14))->find(); # 没有分的其他用户上级 - 交易
            if(empty($infof))
            {
                if(!empty($users['user_id']))
                {
                    R("Func/Fenxiao/jiesuanaddUserOrder",array($users['user_id'], $order, $i + 1, $users['utype'], $orderarr['js_money'], $orderarr['user_js_supply_id']));
                }
            }
            $users = R("Func/Fenxiao/getUpUser",array($users['user_id']));
            $txuser.=$users['user_id']."->";
        }
        ## echo substr($txuser,0,-2);
    }

    # 重新分配
    public function threeFunc()
    {
        $pt_ordersn = $_REQUEST['pt_ordersn'];
        R("Func/Fenxiao/fenxiaoByOrder",array($pt_ordersn));
        R("Func/Fenxiao/jiesuanfenxiaoByOrder",array($pt_ordersn));
    }


    # 调试
    public function cts()
    {
        $retailData = R('Func/User/orderRetailscs',array("Q00000011"));
        dump($retailData);
    }

    # 24 - 26号重新分销/分润
    public function betweenFenxiao()
    {
        $start_time = 1516723200;
        $end_time = 1516982399;
        $w['t'] = array('between',array(($start_time),($end_time)));
        $w['jy_status'] = array("eq",1);
        $w['money_type_id'] = array('in','11');
        $res = M("money_detailed")->field('jy_status,js_status,pt_ordersn')->where($w)->select();
        foreach ($res as $k => $v)
        {
            if($v['jy_status'] == 1)
            {
                R("Func/Fenxiao/fenxiaoTkLevelOrder",array($v['pt_ordersn'])); # 交易分
                if($v['js_status'] == 2)
                {
                    R("Func/Fenxiao/fenxiaoTkJsLevelOrder",array($v['pt_ordersn'])); # 结算分
                }

                echo '---------------------------==========================---------------------------<br/>';

            }
        }
    }





    # 查找分配全部木有的
    public function fenxiaoCheck($user_id)
    {
//        $w['money_type_id'] = array('in','9,12,14,15,25,27');
        $w['t'] = array('between',array(1516723200,1517068799));
        $w['user_id'] = $user_id;
        $res = M("money_detailed")->where($w)->order('sh_ordersn desc')->order('t asc')->select();
        $str = "查找匹配".$user_id."全部所有收益情况<br/>";
        foreach ($res as $k => $v)
        {
            if(in_array($v['money_type_id'],explode(',','9,12,14,15,25,27')))
            {
                $type = M('money_type')->where(array('money_type_id'=>$v['money_type_id']))->getField('type');
                $str.="有分配到=====》订单号：".$v['pt_ordersn']."，来源订单".$v['sh_ordersn']."，收益名称：".$type."，时间：".date('Y-m-d H:i:s',$v['t'])."<br/>";
            }else{
                // 匹配没有得到的收益
                $type = M('money_type')->where(array('money_type_id'=>$v['money_type_id']))->getField('type');
                $str.="没有分配到=====》订单号：".$v['pt_ordersn']."，收益名称：".$type."<br/>";
            }
        }
        echo $str;
        echo "<hr>";
    }


    # 重新分配
    public function cxFenx()
    {
        $pt_ordersn = "XJF2018012620232381142,XJF2018012620000254016,XJF2018012619583751375,XJF2018012619101586995,XJF2018012619173662154,XJF2018012618521939748,XJF2018012616540314849,XJF2018012616024652728,XJF2018012615370241921,XJF2018012615352920236,XJF2018012615333122365,XJF2018012615271727334,XJF2018012614442256590,XJF2018012614084317283,XJF2018012613224235643,XJF2018012613131828428,XJF2018012613075951396,XJF2018012611471930678,XJF2018012611094136504,XJF2018012610474820821,XJF2018012610471666067,XJF2018012610195317887,XJF2018012610185496818,XJF2018012515154714776,XJF2018012609584460262,XJF2018012609015422043,XJF2018012608162196104,XJF2018012521160968944,XJF2018012521124788952,XJF2018012521044580659,XJF2018012521005239521,XJF2018012519462771104,XJF2018012519401394703,XJF2018012519394174424,XJF2018012519341541452,XJF2018012519162382867,XJF2018012518374792431,XJF2018012518090248012,XJF2018012517341195782,XJF2018012517091833878,XJF2018012517070839937,XJF2018012613494881025,XJF2018012610330421528,XJF2018012610053279430";
        echo '总订单数量：'.count(explode(',',$pt_ordersn));


        /*
        $exarr = explode(',',$pt_ordersn);
        $resarr = array();
        foreach ($exarr as $k => $v)
        {
            $w['pt_ordersn'] = $v;
            $w['jy_status'] = 1;
            $res = M("money_detailed")->field('money_detailed_id,pt_ordersn')->where($w)->order('money_detailed_id desc')->find();

            M("money_detailed")->where($w)->save(array('sh_ordersn'=>''));
            $resarr[] = $res;
        }

        dump($resarr);die;
*/

        $w['pt_ordersn'] = array('in',$pt_ordersn);
        $w['jy_status'] = 1;
        $w['money_type_id'] = array('in','11');
        $res = M("money_detailed")->field('money_detailed_id,pt_ordersn')->where($w)->order('money_detailed_id desc')->select();
        foreach ($res as $k => $v)
        {
            # 产生分销/分润订单 - 交易
            R("Func/Fenxiao/fenxiaoByOrderCeshi",array($v['pt_ordersn']));
//            R("Func/Fenxiao/fenxiaoTkLevelOrder",array($v['pt_ordersn'])); # 交易分 - 固定收益
        }
    }


    /**
     * 授权测试
     */
    public function autho()
    {
        header('Content-Type:text/html;charset=utf-8');
        $weObj = R('Func/Wxapi/getConfig');

        //获得access_token和openid
        $cuts = session('cutsDown2333');
        if(!$cuts)
        {
            // 重新自动授权登录
            $url='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; //1代表微信
            //         echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
            $res = R('Func/Wxapi/getConfig');
            $souquanurl = $res->getOauthRedirect($url,'STATE');
            $cuts = "cutt";
            session('cutsDown2333',$cuts);
            header("location:".$souquanurl);exit;
        }else{
            $data = $weObj->getOauthAccessToken();
            dump($data);
            $info = $weObj->getOauthUserinfo($data['access_token'],$data['openid']);
            dump($info);
        }
    }

    /**
     * 通道统一关闭
     */
    public function tdSet()
    {
        $res = M("module_set")->select();
        echo json_encode(array("code"=>400,"msg"=>"产品开光","data"=>$res));exit;
    }




    /**
     * 重新
     */

    public function poses()
    {

    }


    # 测试
    /*
    public function cxAddFenx()
    {
        $tdid = 25;
        $pt_ordersn = M('money_detailed')->where(array('user_pay_supply_id'=>$tdid))->select();

        $w['user_pay_supply_id'] = $tdid;
        $w['jy_status'] = 1; # 交易成功
        $w['js_status'] = 1; # 结算中
        $w['money_type_id'] = array('in','11');
        $res = M("money_detailed")->field('money_detailed_id,pt_ordersn,pay_money,money')->where($w)->order('money_detailed_id desc')->select();
        # dump($res);die;

        foreach ($res as $k => $v)
        {
            M("money_detailed")->where(array("pt_ordersn"=>$v['pt_ordersn']))->save(array('js_status'=>2));
            M("moneyjs_detailed")->where(array("pt_ordersn"=>$v['pt_ordersn']))->save(array('js_status'=>2));
            # 结算成功订单分销
            R("Func/Fenxiao/jiesuanfenxiaoByOrder",array($v['pt_ordersn']));
            # 产生分销/分润订单 - 交易
            # R("Func/Fenxiao/fenxiaoByOrderCeshi",array($v['pt_ordersn']));
            # R("Func/Fenxiao/fenxiaoTkLevelOrder",array($v['pt_ordersn'])); # 交易分 - 固定收益
        }
    }
    */


    ################

    public function tklevel()
    {
        $uid = trim($_REQUEST['uid']);
        $level = trim($_REQUEST['level']);
        $pt_ordersn = trim($_REQUEST['pt_ordersn']);
        if($_REQUEST['is_del'] == 1)
        {
            M("money_detailed")->where(array("sh_ordersn"=>array("like","%$pt_ordersn%")))->delete();
            echo '删除成功';
            exit;
        }

        # M("user")->where(array('user_id'=>$uid))->save(array('level'=>$level));


        # 佣金 / 分润
        R("Func/Fenxiao/fenxiaoByOrder",array('pt_ordersn'=>$pt_ordersn));  # 交易

        /**/
//        R("Func/Fenxiao/jiesuanfenxiaoByOrder",array('pt_ordersn'=>$pt_ordersn));  # 结算


        /**/
        # 上级固定收益
//        R("Func/Fenxiao/fenxiaoTkLevelOrder",array('pt_ordersn'=>$pt_ordersn));  # 交易
//        R("Func/Fenxiao/fenxiaoTkJsLevelOrder",array('pt_ordersn'=>$pt_ordersn));  # 结算


        # 品牌服务费xxx
    }

    # 重新分配有问题的
    public function resRU()
    {
        $pt_ordersn = trim($_REQUEST['pt_ordersn']);
        R("Func/Fenxiao/fenxiaoTkLevelOrder",array('pt_ordersn'=>$pt_ordersn));  # 交易
        R("Func/Fenxiao/fenxiaoTkJsLevelOrder",array('pt_ordersn'=>$pt_ordersn));  # 结算
    }



    #
    # 测试
    public function sendToUser()
    {
        $userid = $_REQUEST['user_id'];
        $typename = $_REQUEST['typename'];
        sendToUser("{\"fromid\":\"1\",\"fromname\":\"钱兜兜\",\"user_id\":\"".$userid."\",\"name\":\"\",\"token\":\"cebced47c9d6c22c9ed9c6df528caace83f04edb1a52780b49f53e975001bed4\",\"msg\":\"".$typename."\",\"operation\":\"text\",\"sound\":\"default\",\"content-available\":\"1\"}");

        // sendToUser("{\"fromid\":\"1\",\"fromname\":\"钱兜兜\",\"user_id\":\"".$userid."\",\"name\":\"\",\"token\":\"cebced47c9d6c22c9ed9c6df528caace83f04edb1a52780b49f53e975001bed4\",\"msg\":\"您所得".$typename."元\",\"operation\":\"text\",\"sound\":\"default\",\"content-available\":\"1\"}");
    }



}