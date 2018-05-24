<?php
# 银钱包 - 商家功能模块

namespace Payapi\Controller;
use Think\Controller;
class BusinessController extends BaseController {
    public $cityinfo;
    public function _initialize()
    {
        parent::_initialize();
        $ipd = R("Func/Func/getIpAdr"); // ip地址
        $url = "https://apis.map.qq.com/ws/location/v1/ip/?ip=".$ipd."&key=".C("QQMAPAPI");
        $city = R("Func/Func/globalCurlGet",array($url));
        $city = json_decode($city,true);
        $this->cityinfo = $city['result'];
    }

    # 列表
    public function getList(){
        $cw['is_index'] = 1;
        $cate = R('Func/Business/getCate',array($cw));

        $jxw['is_jx'] = 1;

        $cityinfo = $this->cityinfo['ad_info'];
        $jxw['cityid'] = M("city")->where(array('name'=>$cityinfo['city']))->getField('id');

        $loc = $this->cityinfo['location'];
        $jl['lat1'] = $loc['lat'];
        $jl['lng1'] = $loc['lng'];


        $jxlist = R('Func/Business/getList',array($jxw,$jl));

        $lw['is_tuijian'] = 1;
        $cityinfo = $this->cityinfo['ad_info'];
        $lw['cityid'] = M("city")->where(array('name'=>$cityinfo['city']))->getField('id');


        $p = intval($_REQUEST['page']);
        $psize = intval($_REQUEST['psize']);
        $list = R('Func/Business/getList',array($lw,$jl,$p,$psize));

        # 轮播图
        $lbarr = R('Func/Func/getLunbo',array(2));

        $d = array(
            'code' => 2001,
            'msg' => '商家首页',
            'data' => array(
                'city' => $this->cityinfo,
                'lbarr' => $lbarr,
                'cate' => $cate,
                'jxlist' => $jxlist,
                'list' => $list
            )
        );
        echo json_encode($d);exit;
    }

    # 详情
    public function getDetail()
    {
            $w['id'] = intval($_REQUEST['id']);
            $loc = $this->cityinfo['location'];
            $jl['lat1'] = $loc['lat'];
            $jl['lng1'] = $loc['lng'];

            $d = R('Func/Business/getList',array($w,$jl));

            if(!$d)
            {
                $d = array(
                    'code' => 400,
                    'msg' => '没有这个参数值'
                );
                echo json_encode($d);exit;
            }

            # 猜你喜欢
            $tj['cate'] = $d[0]['cid'];
            $tj['id'] = array('neq',$d[0]['id']);
            $cityinfo = $this->cityinfo['ad_info'];
            $tj['cityid'] = M("city")->where(array('name'=>$cityinfo['city']))->getField('id');

            $tjdata = R('Func/Business/getList',array($tj,$jl,1,5));
            $tjsjdata = shuffle_assoc($tjdata);

            $this->assign('d',$d[0]);

            $this->assign('tjsjdata',$tjsjdata);

        $d = array(
            'code' => 2002,
            'msg' => '商家详情',
            'data' => array(
                'detail' => $d[0],
                'tjsjdata' => $tjsjdata  // 相关推荐
            )
        );
        echo json_encode($d);exit;

    }

    # 领取优惠券
    public function ajaxLqCoupon()
    {
        $w['couponid'] = intval($_REQUEST['couponid']);
        $w['bid'] = intval($_REQUEST['bid']);
        if(empty($w['couponid']) && empty($w['bid']))
        {
            $d = array(
                'code'=>200,
                'msg'=>'参数不全'
            );
            echoAJson($d);exit;
        }

        $w['uid'] = $this->uid;
        $yhqarr['id'] = intval($_REQUEST['couponid']);
        $yhqcount = R("Func/Business/getCoupon",array($w['bid'],$yhqarr));
        $count = R("Func/Business/getLqCoupon",array($w));
        $lq_num = $yhqcount[0]['lq_num'];

        # 判断每个人针对优惠券是否可以领取多张
        if($count<=0)
        {
            if($count>$lq_num)
            {
                $d = array(
                    'code'=>200,
                    'msg'=>'已超出领取次数'
                );
            }else{

                # 添加领取数据
                $data['openid'] = trim($this->openid);
                $data['couponid'] = intval($_REQUEST['couponid']);
                $data['gettime'] = time();
                $data['used'] = 0;
                $data['bid'] = intval($_REQUEST['bid']);

                M('coupon_data')->add($data);
                $d = array(
                    'code'=>400,
                    'msg'=>'领取成功'
                );
            }
        }else{
            $d = array(
                'code'=>700,
                'msg'=>'已领取'
            );
        }
        echoAJson($d);exit;
    }
}