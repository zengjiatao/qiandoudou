<?php
/**
 * Created by 银迅通支付网络公司.
 * User: Jeffery
 * Date: 2018/3/22
 * Time: 15:43
 */

# 全通用基本函数方法
namespace Func\Controller;

use Think\Controller;

class SbaseFuncController extends Controller
{

    # 测试
    public function ceshi()
    {
        $str = '{"success":true,"return_info":"下单成功","return_data":{"pay_code":"https://gateway.95516.com/gateway/api/frontTransReq.do","order_amt":"4653","pay_info":{"backUrl":"https%3A%2F%2Fnotify.allscore.com%2Fnotify%2FCHANNEL-B-CUP-001%2FNOTIFY%2FNOCARD","channelType":"07","txnAmt":"465300","certId":"74172609945","orderId":"2018042513184816491984","accNo":"caZLX0PSlMz4M9eRLS%2BSuqdf3HWCLFzry3zhnkMzL4PocT%2FG3TCnzy89gu4X8lYxqdxNmOVRez8NiIGE1CFvmAMWc6rxLohGybRCCnchm0hFpwVf%2FM%2FNnYk9QTgQV23sXvd6gCQsz%2BEGQd7bX%2FHefdrRX7zSOpcluXUkPIs6wmdF7DOlKY1hTROmCoxLN31aYN1h20jMojVZqZ36kplcg7I2xpm7t3TW%2B9saMn49ZXCydDfoEcHjxaRkMcgOawdhG15S0OG6BjKZQYwuLesuO%2B29KmytevZo19oTMXqSdMgwe2e4OimMZvFTflg%2BXUSmSBevJlwbMaIeDGDTC6vsiQ%3D%3D","signMethod":"01","accessType":"0","frontUrl":"https%3A%2F%2Fnotify.allscore.com%2Fnotify%2FCHANNEL-B-CUP-001%2FCALLBACK%2FNOCARD","signature":"DczKaYdli2hJLKHCQe94gepWH6zJpXrx8CRjv9Fs7xWCu0%2BAMB%2BWBydBMHpR9Zk4tilJUOQFgOcicR6eghiw5z4RW9qrdZUVndVbxJmeQXegzpL5chgN0F9Ujtm5qHUt54d660QABz4xZgfTZXM9e9RbPJanSPccH%2BQWTuosbuA9O9P1%2BxeFz%2BsVWGutWxcCRICiOp3Ieb9Rrf8uKfOFeGqGFFE0wIrdk0JUr4H5eHVin6rV9UTswPXDYhvSWVmm5Gw5YAyUgdarAayeebyEdLnPynejm1EzmsrCmaE222FOVvD1c9UWTUUUikaf%2Flj%2BQclKmJsTxnZRRPE%2FO33t3Q%3D%3D","bizType":"000301","txnTime":"20180425131848","txnType":"01","currencyCode":"156","accType":"01","merId":"883521080620303","encryptCertId":"69042905377","txnSubType":"01","encoding":"UTF-8","version":"5.0.0"},"fee_amt":"24.33","order_no":"18042513184800130310","merc_cd":"800000041313967"},"return_code":"00","token":""}';

        $str = json_decode($str);

        $payinfo = $str->return_data->pay_info;

        $payinfo = json_decode(json_encode($payinfo),true);
        $ss = "<form name='submit' action='https://gateway.95516.com/gateway/api/frontTransReq.do' accept-charset='utf-8' method='post'>";
        foreach ($payinfo as $k => $v)
        {
            $ss.="<input type='hidden' name='".$k."' value='".$v."'/>";
        }
        $ss.="</form>";
        echo $ss;
    }

    # 记录每次登录信息  -  活跃度
    public function SaveloginAction()
    {
        # wx
        $openid = trim($_SESSION['openid']);
        if (empty($openid)) {
            if (trim($_REQUEST['uid'])) {
                # APP
                $openid = M("user")->where(array('user_id' => trim($_REQUEST['uid'])))->getField('openid');
            } else {
                return;
            }
        }
        $ip = get_client_ip();
        $url = "https://apis.map.qq.com/ws/location/v1/ip/?ip=" . $ip . "&key=" . C("QQMAPAPI");
        $city = R("Func/Func/globalCurlGet", array($url));
        $city = json_decode($city, true);
        if ($openid) {
            $d['ip'] = $ip;
            $d['t'] = time();
            $d['openid'] = $openid;
            $d['lnt'] = trim($city['result']['location']['lnt']);
            $d['lat'] = trim($city['result']['location']['lat']);
            $d['province'] = trim($city['result']['ad_info']['province']);
            $d['city'] = trim($city['result']['ad_info']['city']);
            $d['adcode'] = trim($city['result']['ad_info']['adcode']);
            M("loginactive")->add($d);
        }
    }


    # 根据现有IP地址获取其地理位置（省份,城市等）的方法
    public function GetIpLookup($ip = '')
    {
        if (empty($ip)) {
            return '请输入IP地址';
        }
        $res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=' . $ip);
        if (empty($res)) {
            return false;
        }
        $jsonMatches = array();
        preg_match('#\{.+?\}#', $res, $jsonMatches);
        if (!isset($jsonMatches[0])) {
            return false;
        }
        $json = json_decode($jsonMatches[0], true);
        if (isset($json['ret']) && $json['ret'] == 1) {
            $json['ip'] = $ip;
            unset($json['ret']);
        } else {
            return false;
        }
        return $json;
    }

    # 判断当前参数总数
    public function getParam()
    {
        $get = ($_REQUEST);
        if (count($get) > 2) {
            return 1;
        } else {
            return 0;
        }
    }


    ## 活动奖励制度
    # 奖励说明
    public function statisticsSm()
    {
        $newarray = array();
        $level = M('distribution_level')->field('distribution_level_id,levelname,register_info,system_gz,system_member,system_bz')->where(array('type' => 20, 'is_use' => 1))->select();

        $bz = array();
        foreach ($level as $k3 => $v3) {
            $wx = explode('-', $v3['system_gz']);
            $tpinfo = M('distribution_level')->field('system_gz,system_member,system_ms')->where(array('type' => 20, 'is_use' => 1, 'distribution_level_id' => 4))->find();
            $ypinfo = M('distribution_level')->field('system_gz,system_member,system_ms')->where(array('type' => 20, 'is_use' => 1, 'distribution_level_id' => 5))->find();
            $wxmoney = explode(',', $tpinfo['system_member']);
            $wxmoney1 = explode(',', $ypinfo['system_member']);
            if (empty($wx[1])) {
                $level[$k3]['zb'] = $wx[0] . '≤拓展数';
            } else {
                $level[$k3]['zb'] = $wx[0] . '≤拓展数<' . $wx[1];
            }
            $level[$k3]['tpmoney'] = $wxmoney[$k3];
            $level[$k3]['wx_sum'] = $wx;
            $level[$k3]['ypmoney'] = $wxmoney1[$k3];


            $level[$k3]['wxsystem_member'] = explode(',',$v3['system_member']);

            $level[$k3]['moneybz'] = $v3['system_bz'];

            $bz[] = $v3['levelname'] . " ： " . $v3['system_bz'] . "%";

            $sj = $tpinfo['system_ms'];
            $zh = $ypinfo['system_ms'];
        }

        # $sj = "每天早上9:00发放奖励";
        # $sm = "钱兜兜平台【我的】 - 【我的零钱】";

        # 达标制度
        $newarray['zd'] = $level;
        # 标准
        $newarray['bz'] = $bz;
        # 说明
        $newarray['sj'] = $sj;
        $newarray['zh'] = $zh;

        return $newarray;
    }


    # 排行榜
    ## all -> 配合$w['user_id'] = "Q000000xxxxX"; 一起使用
    ### 从4月15号开始统计起
    ### 例子：R("Func/SbaseFunc/statisticsFunc",array($w,'2016-01-27,2018-04-17'));
    public function statisticsFunc($w = array(), $all = '', $yesterday = 0, $today = 0)
    {
        $field = 'user_id,nick_name,phone,level';
        $w['utype'] = 20;
        $w['isopen'] = 1;
        # $w['mch_status'] = 1;
        $ulist = M('user')->field($field)->where($w)->select();
        foreach ($ulist as $k => $v) {
            # $w1['pid|tk_pid'] = $v['user_id'];  # 所有分级
            $w1['pid'] = $v['user_id'];
            $w1['utype'] = 20;
            $w1['isopen'] = 1;
            # $w1['mch_status'] = 1;
            $array = M('user')->field('user_id,level,nick_name')->where($w1)->select();
            $tpcount = array();
            $ypcount = array();
            $jpcount = array();
            $zpcount = array();
            $tpcountSum = 0;
            $ypcountSum = 0;
            $jpcountSum = 0;
            $zpcountSum = 0;
            foreach ($array as $k1 => $v1) {
                $time = M('agent')->field('t,username,user_id')->where(array('user_id' => $v1['user_id'], 'status' => 1))->order('agent_id desc')->find(); # 财审通过的推客最后时间==注册时间
                $v1['time'] = date("Y-m-d", $time['t']);
                if ($v1['level'] == 4)  # 铜牌
                {
                    $tpcount[] = $v1;
                    $tpcountSum += 1;
                }
                if ($v1['level'] == 5)  # 银牌
                {
                    $ypcount[] = $v1;
                    $ypcountSum += 1;
                }
                if ($v1['level'] == 6)  # 金牌
                {
                    $jpcount[] = $v1;
                    $jpcountSum += 1;
                }
                if ($v1['level'] == 9)  # 钻石
                {
                    $zpcount[] = $v1;
                    $zpcountSum += 1;
                }
            }

            # 筛选时间
            if ($yesterday == 1)  # 昨天
            {
                $day = date("Y-m-d", strtotime(date("Y-m-d")) - 86400);
            } elseif ($today == 1)                  # 今天
            {
                $day = date("Y-m-d");
            } elseif ($all) {
                $day = $all; // 开始时间算起 - 结束时间
            }

            $newtpcount = $this->searchArray($tpcount, 'time', $day);

            $newypcount = $this->searchArray($ypcount, 'time', $day);
            $newjpcount = $this->searchArray($jpcount, 'time', $day);
            $newzpcount = $this->searchArray($zpcount, 'time', $day);


            $ulist[$k]['tpcount'] = $newtpcount;
            $ulist[$k]['tpcountSum'] = count($newtpcount);

            $ulist[$k]['ypcount'] = $newypcount;
            $ulist[$k]['ypcountSum'] = count($newypcount);

            $ulist[$k]['jpcount'] = $newjpcount;
            $ulist[$k]['jpcountSum'] = count($newjpcount);

            $ulist[$k]['zpcount'] = $newzpcount;
            $ulist[$k]['zpcountSum'] = count($newzpcount);

            $ulist[$k]['nick_name'] = $v['nick_name'];
            $ulist[$k]['user_id'] = $v['user_id'];
            $levelname = M('distribution_level')->where(array('distribution_level_id' => $v['level']))->getField('levelname');
            $ulist[$k]['levelname'] = str_replace("推客", "", $levelname);
            $is_moni = 0;
            $status = $this->statisticsSendMiData($v['user_id'],$day);
            # $status = 0;

            #  $keymoney = $this->statisticsJs($v['level'],count($newtpcount),count($newypcount));
            $ulist[$k]['is_moni'] = $is_moni;  # 是否是模拟数据
            $ulist[$k]['status'] = $status;  # 状态，是否发放
            #  $ulist[$k]['keymoney'] = $keymoney;  # 奖励
            $ulist[$k]['count'] = count($array);  # 总推的推客总数
            $ulist[$k]['time'] = $day;  #

        }
        return $ulist;
    }


    # 奖励计算方式
    # $offedeLevel = 该推客原等级   $tpcountSum = 所推的铜牌推客人数    $ypcountSum = 所推的银牌推客人数
    public function statisticsJs($offedeLevel = 0, $tpcountSum = 0, $ypcountSum = 0)
    {
        $sm = $this->statisticsSm();
        $zd = $sm['zd'];

        $keymoney = 0;
        $tpkeymoney = 0;
        $ypkeymoney = 0;

        if ($offedeLevel == 4) {
            $offedeLevel = $zd[0]['moneybz'];
        } elseif ($offedeLevel == 5) {
            $offedeLevel = $zd[1]['moneybz'];
        } elseif ($offedeLevel == 6) {
            $offedeLevel = $zd[2]['moneybz'];
        } elseif ($offedeLevel == 9) {
            $offedeLevel = $zd[3]['moneybz'];
        }

        foreach ($zd as $k => $v) {
            $gz = explode('-', $v['system_gz']);

            if ($tpcountSum >= $gz[0] && $tpcountSum <= $gz[1]) {
                $tpkeymoney = ($v['tpmoney'] * $tpcountSum);
            }
            if ($ypcountSum >= $gz[0] && $ypcountSum <= $gz[1]) {
                $ypkeymoney = ($v['ypmoney'] * $ypcountSum);
            }
        }
        $keymoney = ($tpkeymoney + $ypkeymoney) * ($offedeLevel / 100);
        # echo "铜牌推客人数==：".$tpcountSum."，银牌推客人数==：".$ypcountSum."(".$tpkeymoney.")+"."(".$ypkeymoney.")*".$offedeLevel."%"."=====".$keymoney."==<br/>";
        return $keymoney;
    }

    # 模拟数据
    public function statisticsMiData()
    {
        # $w['c'] = 1;
        $w['is_moni'] = array('eq',1);
        $list = M("midata")->where($w)->select();
        foreach ($list as $k => $v) {
            # $list[$k]['user_id'] = "EEEEEE" . mt_rand(1111, 9999);
            $list[$k]['phone'] = "1" . mt_rand(11111111111, 99999999999);
            $list[$k]['tpcount'] = array();
            $list[$k]['ypcount'] = array();
            $list[$k]['jpcount'] = array();
            $list[$k]['zpcount'] = array();
            $list[$k]['count'] = mt_rand(1, 9);

            unset($list[$k]['midata_id']);

        }
        return $list;
    }

    # 是否已发放 - 零钱(判断)
    public function statisticsSendMiData($user_id="",$time="")
    {
        $ifinfo = M("midata")->where(array('user_id'=>$user_id,'is_moni'=>array('neq',1),'time'=>$time,'status'=>1))->find();
        if($ifinfo)
        {
            return 1;
        }else{
            return 0;
        }
    }

    # 点击发放
    public function statisticsSend($is_moni=1)
    {
        $d['user_id'] = trim($_REQUEST['user_id']);
        $d['nick_name'] = trim($_REQUEST['nick_name']);
        $d['tpcountSum'] = trim($_REQUEST['tpcountSum']);
        $d['ypcountSum'] = trim($_REQUEST['ypcountSum']);
        $d['jpcountSum'] = trim($_REQUEST['jpcountSum']);
        $d['zpcountSum'] = trim($_REQUEST['zpcountSum']);

        $d['levelname'] = trim($_REQUEST['levelname']);
        $d['level'] = trim($_REQUEST['level']);
        $d['is_moni'] = $is_moni; # 否模拟数据
        $d['status'] = trim($_REQUEST['status']);
        $d['keymoney'] = trim($_REQUEST['keymoney']);
        $d['time'] = trim($_REQUEST['time']);
        $ifmd = M("midata")->where(array('time'=>$d['time'],'nick_name'=>$d['nick_name']))->find();
        if(!$ifmd)
        {
            M("midata")->add($d);
        }else{
            M("midata")->where(array('time'=>$d['time'],'nick_name'=>$d['nick_name']))->save(array('status'=>trim($_REQUEST['status'])));
        }
//        if($ids)
//        {
            if($is_moni!=1) # 发放到零钱
            {
                $wallet_money = M("user")->where(array('user_id'=>$d['user_id']))->getField('wallet_money');
                M("user")->where(array('user_id'=>$d['user_id']))->save(array('wallet_money' => $wallet_money + $d['keymoney']));
            }
            return 1; # 发放成功
//        }else{
//            return 3; # 发放失败
//        }
    }

    # 系统配置信息
    public function site_config($id = 1)
    {
        return M("site_config")->where(array('site_config_id'=>$id))->find();
    }

    # 验证发放密码  -  拓客奖励制度
    public function system_kk_code()
    {
        $system = $this->site_config();
        if($_REQUEST['system_kk_send_pass'] == $system['system_kk_send_pass'])
        {
            # $_COOKIE['system_kk_send_pass'] = $_REQUEST['system_kk_send_pass'];
            setcookie("system_kk_send_pass",$_REQUEST['system_kk_send_pass'],2*24*3600);
            echo json_encode(1);
            # return 1;
        }else{
            echo json_encode(0);
            # return 0;
        }
    }

    # 查询该订单存在的分销/分润信息 - 此该订单
    # $pt_ordersn = 生成的分销/分润订单号，不与商户订单号一样
    public function orderCheckFenxiao($pt_ordersn)
    {
        $d = M("money_detailed")->where(array('pt_ordersn'=>$pt_ordersn))->find();
        if($d)
        {
            # if 佣金
            $orderYj = 0;
            $orderYjBl = 0;
            $orderYjBenefit = $d['benefit'];

            $orders = M("money_detailed")->join('left JOIN y_money_type ON y_money_type.money_type_id = y_money_detailed.money_type_id' )->order('money_detailed_id desc')->find();
            $wherepay['user_pay_supply_id']=array('eq',$orders['user_pay_supply_id']);
            $paytype = M('user_pay_supply');

            $paytypearr=$paytype->where($wherepay)->select();
            $bl=$paytypearr[0]['yy_rate']-$paytypearr[0]['tk_rate'];

            if($d['money_type_id']==12  || $d['money_type_id'] == 5)
            {
                $bl=$paytypearr[0]['tk_rate']-$paytypearr[0]['pt_rate'];
            }

            if($d['commission_bl']!=0.0000)
            {
                $orderYjBl = $d['commission_bl'];
            }else{
                $orderYjBl = $bl;
            }

            if($d['commission_summoney']!=0.0000)
            {
                $orderYj = $d['commission_summoney'];
            }else{
                // 订单总佣金
                if($d['money_type_id'] == 9 || $d['money_type_id'] == 5)
                {
                    $yongjin =$orderYjBenefit/$orderYjBl; //算出的费率千分一  // ( o2o交易 )
                }else if($d['money_type_id'] == 18){
                    $yongjin = $orderYjBl; // ( 信用卡申请 )
                }

                if($d['money_type_id']==14)
                {
                    //分销结算收益
                    $wherejs['sid'] = array('eq',$d['user_pay_supply_id']);
                    $distribution=M('user_js_supply')->where($wherejs)->select();
                    $yy_rate=$distribution[0]['yy_rate']; //运营费率
                    $pt_rate=$distribution[0]['tk_rate']; //推客费率
                    $yongjin=$yy_rate-$pt_rate;
                }
                $orderYj = $yongjin;
            }


            # if 分润
            $frorderYj = 0;
            $frorderYjBl = 0;
            $frorderYjBenefit = $d['benefit'];

            # o2o交易
            $levelMsg = M('user')->alias('a')->field('a.user_id,a.level,b.levelname,b.commission1')->join('left join y_distribution_level as b on a.level = b.distribution_level_id')->where(array('a.user_id'=>$d['user_id']))->find();

            $where['pt_ordersn'] = array('eq',$d['sh_ordersn']);
            $wherepay['user_pay_supply_id']=array('eq',$d['user_pay_supply_id']);
            $paytype = M('user_pay_supply');
            $paytypearr=$paytype->where($wherepay)->find();
            $bl=$paytypearr['tk_rate']-$paytypearr['pt_rate'];


            if($d['commission_fr_bl']!=0.0000)
            {
                $frorderYjBl = $d['commission_fr_bl'];
            }else{
                if($d['money_type_id'] == "12" || $d['money_type_id'] == "15")
                {
                    $yongjinbl=$levelMsg['commission1']/100;
                }else if($d['money_type_id'] == "25" || $d['money_type_id'] == "27" )  # 判断推客上级收款收益 = 交易/结算比例
                {
                    if($d['money_type_id'] == "25")
                    {
                        $sh_ordersn = trim($d['sh_ordersn']);
                    }else if($d['money_type_id'] == "27")
                    {
                        $sh_ordersn = "JS".trim($d['sh_ordersn']);
                    }
                    # 下级推客等级
                    $lyorder = M('money_detailed')->where(array('sh_ordersn'=>$sh_ordersn,'money_type_id'=>array('in','12,15,25,27')))->order('money_detailed_id desc')->select();
                    # dump($lyorder);
                    $upuserid = "";
                    foreach ($lyorder as $k => $v)
                    {
                        $userinfo = M('user')->where(array('user_id'=>$v['user_id']))->find();
                        if($userinfo['utype']==20 && $userinfo['level']<$levelMsg['level'])
                        {
                            $upuserid[] = $userinfo['user_id'];
                            $upuserlevel = $userinfo['level'];
                        }
                    }
                    $yongjinbl = $this->tklevelorder($levelMsg['level'],$upuserlevel)/100;
                }
                $frorderYjBl = $yongjinbl;
            }

            if($d['commissioncommission_fr_summoney']!=0.0000)
            {
                $frorderYj = $d['commissioncommission_fr_summoney'];
            }else{
                $frorderYj = $frorderYjBenefit/$frorderYjBl;
            }
            $of['yj'] = array(
                $orderYj,
                $orderYjBl,
                $orderYjBenefit
            );
            $of['fr'] = array(
                $frorderYj,
                $frorderYjBl,
                $frorderYjBenefit
            );
            return $of;
        }
    }

    # 获取推客上级与下级的分润比例
    # $pay_type = 1交易 2结算
    public function tklevelorder($leve,$dnlevel)
    {
        # 第一等级
        $where['distribution_level_id'] = array('eq',$leve);
        $distribution=M('distribution_level')->where($where)->select();
        # 第二等级
        $wherejx['distribution_level_id'] = array('eq',$dnlevel);
        $distributionjx=M('distribution_level')->where($wherejx)->select();
        $commission=$distribution[0]['commission1'] - $distributionjx[0]['commission1'];
        return $commission;
    }

    # 搜索二维数组中的值
    # $value = 如果有区间选择性，则用,号隔开
    public function searchArray($array, $key, $value)
    {
        $new = array();
        foreach ($array as $keyp => $valuep) {

            if(strpos($value,',')===false)
            {

                if ($valuep[$key] == $value) {
                    # unset($array[$keyp]);  // 删除
                    $new[] = $array[$keyp];         # 寻找查询
                }
            }else{
                $exvalue = explode(',',$value);


//echo strtotime($exvalue[0]." 00:00:00")."<=";
//echo $valuep[$key]." 00:00:00"."====";
//echo $valuep[$key]." 23:59:59"."<=";
//echo strtotime($exvalue[1]." 23:59:59")."<br>";

                if (strtotime($exvalue[0]." 00:00:00")<= strtotime($valuep[$key]." 00:00:00") && strtotime($valuep[$key]." 23:59:59") <= strtotime($exvalue[1]." 23:59:59")) {
                    $new[] = $array[$keyp];         # 寻找查询
                }
            }
        }
        return $new;
    }

    # 二维数组单字段排序
    public function my_sort($arrays,$sort_key,$sort_order=SORT_DESC,$sort_type=SORT_NUMERIC ){
        if(is_array($arrays)){
            foreach ($arrays as $array){
                if(is_array($array)){
                    $key_arrays[] = $array[$sort_key];
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
        return $arrays;
    }

    # 二维数组多字段排序
    public function sortArrByManyField(){
        $args = func_get_args();
        if(empty($args)){
            return null;
        }
        $arr = array_shift($args);
        if(!is_array($arr)){
            throw new Exception("第一个参数不为数组");
        }
        foreach($args as $key => $field){
            if(is_string($field)){
                $temp = array();
                foreach($arr as $index=> $val){
                    $temp[$index] = $val[$field];
                }
                $args[$key] = $temp;
            }
        }
        $args[] = &$arr;//引用值
        call_user_func_array('array_multisort',$args);
        return array_pop($args);
    }

    /**
     * 求两个日期之间相差的天数
     * (针对1970年1月1日之后，求之前可以采用泰勒公式)
     * @param string $day1
     * @param string $day2
     * @return number
     */
    public function diffBetweenTwoDays ($day1, $day2)
    {
        $second1 = strtotime($day1);
        $second2 = strtotime($day2);

        if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        return ($second1 - $second2) / 86400;
    }

    # 昵称随机
    public function offers_sortname()
    {
        $n = array(
            '伱的爱我全权代理',
            '旳仦緈鍢',
            '北辰羽墨',
            '坏脾滊',
            '萌面超人',
            '柠檬不萌只是酸',
            '天会亮、心会暖',
            '梦终有一天会醒',
            '唯有时光和爱经久不衰',
            '沩囻菔務',
            '傷芣起',
            '女人不花,何来貌美如花',
            '筁終仄潵',
            '瞪谁谁怀孕',
            '再见、再也不见',
            '除了死亡所有离开都是背叛',
            '自然卷',
            '深爱不腻',
            '风吹柳絮飞',
            '心似荒城囚我终生',
            '小不正经',
            '情不知所起而一往情深',
            '人最怕就是动了情',
            '文字很轻,思念很重',
        );
        $tkids = rand(0,count($n)-1);
        return $n[$tkids];
    }


}