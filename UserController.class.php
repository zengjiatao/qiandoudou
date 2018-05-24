<?php  
namespace Func\Controller;
use Think\Controller;
class UserController extends Controller
 {

     //获得的分销利润总额
    public function sumJsMoney($uid=''){
        $w['user_id'] = $uid;
        $w['jy_status']=1;
        $w['_string'] = 'money_type_id = 9  or money_type_id=14 or money_type_id=18 or money_type_id=20  or money_type_id = 12 or money_type_id= 15 or money_type_id=19 or money_type_id =21 or money_type_id =25  or money_type_id =27 or money_type_id =30 or money_type_id=29';
        $res=M('money_detailed')->where($w)->order('t desc')->select();
        return $res;
    }
   //获取兜客加推客用户的佣金记录
    /*
     * 测试分页方法
     *  p 页码
     *  num 每页显示
     *  2.0  2.1 无缝对接
     * */
    public function indexcs($uid='',$p = 0,$num = 10){
        //echo '123';
        //测试
//        $w['a.user_id'] = $uid;
//        $w['_string'] = 'a.money_type_id = 9 or a.money_type_id =12 or a.money_type_id=14 or a.money_type_id=15 or a.money_type_id=18 or a.money_type_id=19 or a.money_type_id=20 or a.money_type_id=21';
//        $w['a.money_type_id']=array(array('EQ',9),array('EQ',12),"OR");
//        $w['jy_status']=1;
//        $res = M('money_detailed')->alias('a')->field('a.*,b.nick_name,head_img')->join('left join y_user as b on a.user_id=b.user_id')->where($w)->select();
//        return $res;
        //正式
//        if ($p=1){
//            $p=0;
//        }
        $w['user_id'] =$uid;
        $w['jy_status']=1;
        $w['_string'] = 'money_type_id = 9  or money_type_id=14 or money_type_id=18 or money_type_id=20 or money_type_id=29';
        $res=M('money_detailed')->where($w)->order('t desc')->limit($p*$num,$num)->select();
//        dump($res);die;
        //测试
        foreach ($res as $k=>$v){
            if ($v['money_type_id'] == 9){  //推客分润
                $SJSK=substr($v['sh_ordersn'],0,4);
                 if ($SJSK == 'SHSK'){  //商家产生的订单
                    $sj_order=M('business_order')->field('business_order_id,business_id,pt_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
                    $sjInfo=M('business')->field('business_id,name,thumb')->where(array('business_id'=>$sj_order['business_id']))->find();
                     $res[$k]['nick_name']=$sjInfo['name'];
                     $res[$k]['head_img']=enThumb('./Uploads/',$sjInfo['thumb']);
                 }else{
//                     dump($SJSK);
                     $user_id= M('money_detailed')->field('user_id,pt_ordersn,sh_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
                     $dkInfo=M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$user_id['user_id']))->find();
                     $res[$k]['nick_name']=$dkInfo['nick_name'];
                     $res[$k]['head_img']=enThumb('./Uploads/',$dkInfo['head_img']);
                 }
//                dump($v);
            }
            if ($v['money_type_id'] == 12){  //兜客分销
                $SJSK=substr($v['sh_ordersn'],0,4);
                if ($SJSK == 'SHSK'){  //商家产生的订单
                    $sj_order=M('business_order')->field('business_order_id,business_id,pt_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
                    $sjInfo=M('business')->field('business_id,name,thumb')->where(array('business_id'=>$sj_order['business_id']))->find();
                    $res[$k]['nick_name']=$sjInfo['name'];
                    $res[$k]['head_img']=enThumb('./Uploads/',$sjInfo['thumb']);
                }else{
                    $user_id= M('money_detailed')->field('user_id,pt_ordersn,sh_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
                    $dkInfo=M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$user_id['user_id']))->find();
                    $res[$k]['nick_name']=$dkInfo['nick_name'];
                    $res[$k]['head_img']=enThumb('./Uploads/',$dkInfo['head_img']);
                }
//                dump($v);
            }
            if ($v['money_type_id'] == 14){   //兜客结算
                $jsorderInfo=substr($v['sh_ordersn'],2);
                $js_name = M('moneyjs_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$jsorderInfo))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$js_name['user_id']))->find();
                $res[$k]['nick_name']=$name['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
//                dump($v);
            }
            if ($v['money_type_id'] == 15){  //推客结算
                $jsorderInfo=substr($v['sh_ordersn'],2);
                $js_name = M('moneyjs_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$jsorderInfo))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$js_name['user_id']))->find();
                $res[$k]['nick_name']=$name['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
            }
            //////////////////////////////////////////////////////
            if ($v['money_type_id'] == 18){  //信用卡申请佣金(暂未产生)
                if ($v['pay_money'] == 0){
                    $res[$k]['pay_money']=$v['money'];
                }
                $doukeInfo=M('credit_record')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$doukeInfo['user_id']))->find();
                $res[$k]['nick_name']=$name['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
            }
            if ($v['money_type_id'] == 19){  //信用卡申请分润
                if ($v['pay_money'] == 0){
                    $res[$k]['pay_money']=$v['money'];
                }
                $doukeInfo=M('credit_record')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$doukeInfo['user_id']))->find();
                $res[$k]['nick_name']=$name['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
            }
            /////////////////////////////////////////////////////
            if ($v['money_type_id'] == 20){   //智能还款兜客佣金
                $doukeInfo=M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$doukeInfo['user_id']))->find();
                $res[$k]['nick_name']=$name['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
            }
            if ($v['money_type_id'] == 21){  //智能还款推客分销
                $doukeInfo=M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
//                 $user_id= M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$info['sh_ordersn']))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$doukeInfo['user_id']))->find();
                $res[$k]['nick_name']=$name['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
            }
            if ($v['money_type_id'] == 29){  //信用卡固定返佣

                $doukeInfo=M('credit_record')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
//                 $user_id= M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$info['sh_ordersn']))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$doukeInfo['user_id']))->find();
                $res[$k]['nick_name']=$name['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
            }

        }
//        dump($res);die;
        return $res;
    }

    /*
     * 返回总数 兜客分销
     *  2.0  2.1 无缝对接
     * */
    public function indexCount($uid=''){
        $w['user_id'] =$uid;
        $w['jy_status']=1;
        $w['_string'] = 'money_type_id = 9  or money_type_id=14 or money_type_id=18 or money_type_id=20 or money_type_id=29';
        $res=M('money_detailed')->where($w)->order('t desc')->count();
        return $res;
    }

    /*
     * 测试分页方法
     *  p 页码
     *  num 每页显示
     *  2.0  2.1 无缝对接
     * */
    public function tuikeLics($uid='',$p = 0,$num = 10)
    {
        //        $w['a.user_id'] = $uid;
//        $w['a.money_type_id'] = 12;
//        $res = M('money_detailed')->alias('a')->field('a.*,b.nick_name,head_img')->join('left join y_user as b on a.user_id=b.user_id')->where($w)->select();
//        if ($p=1){
//            $p=0;
//        }
        $w['user_id'] = $uid;
        $w['_string'] = 'money_type_id = 12 or money_type_id=15 or money_type_id=19 or money_type_id =21 or money_type_id = 25 or money_type_id = 27 or money_type_id = 30';
        $res = M('money_detailed')->where($w)->order('t desc')->limit($p * $num, $num)->select();
        foreach ($res as $k => $v) {
            if ($v['money_type_id'] == 12) {
                $SJSK = substr($v['sh_ordersn'], 0, 4);
                if ($SJSK == 'SHSK') {  //商家产生的订单
                    $sj_order = M('business_order')->field('business_order_id,business_id,pt_ordersn')->where(array('pt_ordersn' => $v['sh_ordersn']))->find();
                    $sjInfo = M('business')->field('business_id,name,thumb')->where(array('business_id' => $sj_order['business_id']))->find();
                    $res[$k]['nick_name'] = $sjInfo['name'];
                    $res[$k]['head_img'] = enThumb('./Uploads/', $sjInfo['thumb']);
                } else {
                    $jsOrder = substr($v['sh_ordersn'], 0, 2);
                    if ($jsOrder == 'JS') {
                        $jsorderInfo = substr($v['sh_ordersn'], 2);
                        $js_name = M('moneyjs_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn' => $jsorderInfo))->find();
                        $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id' => $js_name['user_id']))->find();
                        $res[$k]['nick_name'] = $name['nick_name'];
                        $res[$k]['head_img'] = enThumb('./Uploads/', $name['head_img']);
                    }else{
                        $user_id = M('money_detailed')->field('user_id,pt_ordersn,sh_ordersn')->where(array('pt_ordersn' => $v['sh_ordersn']))->find();
                        $dkInfo = M('user')->field('user_id,nick_name,head_img')->where(array('user_id' => $user_id['user_id']))->find();
                        $res[$k]['nick_name'] = $dkInfo['nick_name'];
                        $res[$k]['head_img'] = enThumb('./Uploads/', $dkInfo['head_img']);
                    }
                }
            }
            if ($v['money_type_id'] == 15) {
                $jsorderInfo = substr($v['sh_ordersn'], 2);
                $js_name = M('moneyjs_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn' => $jsorderInfo))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id' => $js_name['user_id']))->find();
                $res[$k]['nick_name'] = $name['nick_name'];
                $res[$k]['head_img'] = enThumb('./Uploads/', $name['head_img']);
            }
            if ($v['money_type_id'] == 19) { #信用卡申请推客 分润
                if ($v['pay_money'] == 0){
                    $res[$k]['pay_money']=$v['money'];
                }
                $doukeInfo=M('credit_record')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$doukeInfo['user_id']))->find();
                $res[$k]['nick_name']=$name['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
            }
            if ($v['money_type_id'] == 21) {
                $doukeInfo = M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn' => $v['sh_ordersn']))->find();
//                 $user_id= M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$info['sh_ordersn']))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id' => $doukeInfo['user_id']))->find();
                $res[$k]['nick_name'] = $name['nick_name'];
                $res[$k]['head_img'] = enThumb('./Uploads/', $name['head_img']);
            }

            # 推客上级交易收益
            if ($v['money_type_id'] == 25) {
                if ($v['pay_money'] == 0){
                    $res[$k]['pay_money']=$v['money'];
                }
                $doukeInfo = M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn' => $v['sh_ordersn']))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id' => $doukeInfo['user_id']))->find();
                $res[$k]['nick_name'] = $name['nick_name'];
                $res[$k]['head_img'] = enThumb('./Uploads/', $name['head_img']);
            }
            # 推客上级结算收益
            if ($v['money_type_id'] == 27){
                if ($v['pay_money'] == 0){
                    $res[$k]['pay_money']=$v['money'];
                }
                $jsorderInfo = substr($v['sh_ordersn'], 2);
                $doukeInfo = M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn' => $jsorderInfo))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id' => $doukeInfo['user_id']))->find();
                $res[$k]['nick_name'] = $name['nick_name'];
                $res[$k]['head_img'] = enThumb('./Uploads/', $name['head_img']);
            }
            # 品牌服务费
            if ($v['money_type_id'] == 30){
                if ($v['pay_money'] == 0){
                    $res[$k]['pay_money']=$v['money'];
                }
                $jsorderInfo = substr($v['sh_ordersn'], 2);
                $level = M("jfpay_log")->where(array('pt_ordersn'=>$v['sh_ordersn'],'type'=>1))->find();
                $lyuser_id = $level['user_id'];
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id' => $lyuser_id))->find();
                $res[$k]['nick_name'] = $name['nick_name'];
                $res[$k]['head_img'] = enThumb('./Uploads/', $name['head_img']);
            }
        }
        return $res;
    }
    /*
     * 返回总数 推客分润
     *  2.0  2.1 无缝对接
     * */
    public function tuikeCount($uid=''){
        $w['user_id'] = $uid;
        $w['_string'] = 'money_type_id = 12 or money_type_id=15 or money_type_id=19 or money_type_id =21  or money_type_id =25  or money_type_id =27 or money_type_id =30';
        $res=M('money_detailed')->where($w)->order('t desc')->count();
        return $res;
    }

    /*
     * 正式线上的index方法
     * */
    public function index($uid=''){
//        $w['a.user_id'] = $uid;
//        $w['_string'] = 'a.money_type_id = 9 or a.money_type_id =12 or a.money_type_id=14 or a.money_type_id=15 or a.money_type_id=18 or a.money_type_id=19 or a.money_type_id=20 or a.money_type_id=21';
//        $w['a.money_type_id']=array(array('EQ',9),array('EQ',12),"OR");
//        $w['jy_status']=1;
//        $res = M('money_detailed')->alias('a')->field('a.*,b.nick_name,head_img')->join('left join y_user as b on a.user_id=b.user_id')->where($w)->select();
//        return $res;
        //正式
        $w['user_id'] =$uid;
        $w['jy_status']=1;
        $w['_string'] = 'money_type_id = 9  or money_type_id=14 or money_type_id=18 or money_type_id=20';
        $res=M('money_detailed')->where($w)->order('t desc')->select();
        //测试
        foreach ($res as $k=>$v){
            if ($v['money_type_id'] == 9){  //推客分润
                $user_id= M('money_detailed')->field('user_id,pt_ordersn,sh_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
                $dkInfo=M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$user_id['user_id']))->find();
                $res[$k]['nick_name']=$dkInfo['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$dkInfo['head_img']);
//                dump($v);
            }
            if ($v['money_type_id'] == 12){  //兜客分销
                $user_id= M('money_detailed')->field('user_id,pt_ordersn,sh_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
                $dkInfo=M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$user_id['user_id']))->find();
                $res[$k]['nick_name']=$dkInfo['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$dkInfo['head_img']);
//                dump($v);
            }
            if ($v['money_type_id'] == 14){   //兜客结算
                $jsorderInfo=substr($v['sh_ordersn'],2);
                $js_name = M('moneyjs_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$jsorderInfo))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$js_name['user_id']))->find();
                $res[$k]['nick_name']=$name['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
//                dump($v);
            }
            if ($v['money_type_id'] == 15){  //推客结算
                $jsorderInfo=substr($v['sh_ordersn'],2);
                $js_name = M('moneyjs_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$jsorderInfo))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$js_name['user_id']))->find();
                $res[$k]['nick_name']=$name['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
            }
            //////////////////////////////////////////////////////
            if ($v['money_type_id'] == 18){  //信用卡申请佣金(暂未产生)
                $jsorderInfo=substr($v['sh_ordersn'],2);
                $js_name = M('moneyjs_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$jsorderInfo))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$js_name['user_id']))->find();
                $res[$k]['nick_name']=$name['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
            }
            if ($v['money_type_id'] == 19){  //信用卡申请分销 (暂未产生)
                $jsorderInfo=substr($v['sh_ordersn'],2);
                $js_name = M('moneyjs_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$jsorderInfo))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$js_name['user_id']))->find();
                $res[$k]['nick_name']=$name['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
            }
            /////////////////////////////////////////////////////
            if ($v['money_type_id'] == 20){   //智能还款兜客佣金
                $doukeInfo=M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$doukeInfo['user_id']))->find();
                $res[$k]['nick_name']=$name['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
            }
            if ($v['money_type_id'] == 21){  //智能还款推客分销
                $doukeInfo=M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
//                 $user_id= M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$info['sh_ordersn']))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$doukeInfo['user_id']))->find();
                $res[$k]['nick_name']=$name['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
            }
        }
//        dump($res);die;
        return $res;
    }
    // 正式 推客的分润记录
    public function tuikeLi($uid=''){
        //        $w['a.user_id'] = $uid;
//        $w['a.money_type_id'] = 12;
//        $res = M('money_detailed')->alias('a')->field('a.*,b.nick_name,head_img')->join('left join y_user as b on a.user_id=b.user_id')->where($w)->select();
        $w['user_id'] = $uid;
        $w['_string'] = 'money_type_id = 12 or money_type_id=15 or money_type_id=19 or money_type_id =21 or money_type_id =30 ';
        $res=M('money_detailed')->where($w)->order('t desc')->select();
        foreach ($res as $k=>$v){
         if ($v['money_type_id'] == 12){
             $user_id= M('money_detailed')->field('user_id,pt_ordersn,sh_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
             $dkInfo=M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$user_id['user_id']))->find();
             $res[$k]['nick_name']=$dkInfo['nick_name'];
             $res[$k]['head_img']=enThumb('./Uploads/',$dkInfo['head_img']);
         }
         if ($v['money_type_id'] == 15){
             $jsorderInfo=substr($v['sh_ordersn'],2);
             $js_name = M('moneyjs_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$jsorderInfo))->find();
             $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$js_name['user_id']))->find();
             $res[$k]['nick_name']=$name['nick_name'];
             $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
         }
         if ($v['money_type_id'] == 19){
             $jsorderInfo=substr($v['sh_ordersn'],2);
             $js_name = M('moneyjs_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$jsorderInfo))->find();
             $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$js_name['user_id']))->find();
             $res[$k]['nick_name']=$name['nick_name'];
             $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
         }
         if ($v['money_type_id'] == 21){
             $doukeInfo=M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
//                 $user_id= M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$info['sh_ordersn']))->find();
             $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$doukeInfo['user_id']))->find();
             $res[$k]['nick_name']=$name['nick_name'];
             $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
         }
            if ($v['money_type_id'] == 30){
                $doukeInfo=M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$v['sh_ordersn']))->find();
//                 $user_id= M('money_detailed')->field('user_id,pt_ordersn')->where(array('pt_ordersn'=>$info['sh_ordersn']))->find();
                $name = M('user')->field('user_id,nick_name,head_img')->where(array('user_id'=>$doukeInfo['user_id']))->find();
                $res[$k]['nick_name']=$name['nick_name'];
                $res[$k]['head_img']=enThumb('./Uploads/',$name['head_img']);
            }
        }
        return $res;
    }
     //获得分销上下级
    public function orderRetails($uid='',$level=0){

        $data = M('user')->field('nick_name,pid,user_id,head_img,time,utype')->order('time desc')->select();

        static $lists=array();
        static $array=array();
      foreach($data as $k=>$v){
          if($v['pid'] == $uid){
              $v['level']=$level;
//              if ($v['level'] > 2){
//                  break;
//              }
              $lists[]=$v;
           $this->orderRetails($v['user_id'],$level+1);
          }
      }
        return $lists;
    }

//    //除三级之外的用户
//    public function getChildLevel($uid=''){
//        $data = M('user')->field('nick_name,pid,user_id,head_img,time,utype')->order('time desc')->select();
//        static $lists=array();
//        foreach($data as $k=>$v){
//            if($v['pid'] == $uid){
//                $v['level']=0;
//                $lists[]=$v;
//                foreach ($data as $kk=>$vv){
//                   if ($vv['pid'] == $v['user_id']){
//                       $v['level']=1;
//                       $lists[]=$vv;
//                       foreach ($data as $kkk=>$vvv){
//                            if ($vvv['pid'] == $vv['user_id']){
//                                $v['level']=1;
//                                $lists[]=$vvv;
//                            }
//                       }
//                   }
//                }
//            }
//        }
//        return $lists;
//    }
    //统计下级(正式) 2.0  2.1 无缝对接
    public function orderRetailsValue($uid=''){
        $Info = M('user')->field('user_id,utype')->where(array('user_id'=>$uid))->find();
        if($Info['utype'] == '20'){ //推客
            static $lists=array();
            static $array=array();
            $data = M('user')->field('nick_name,pid,user_id,head_img,time,utype')->order('time desc')->select();
            $ids = M('user')->field('pid,tk_pid,user_id,head_img,time,utype,nick_name')->where(array('tk_pid'=>$uid))->select();
            foreach ($data as $k=>$v){
                if (trim($v['pid']) == $uid){
                    $v['level']=0;//第一级
                    $lists[]=$v;
                }
            }
            $str='';
            $str1='';
            $str2='';

            foreach ($lists as $k=>$v){

                $str.=$v['user_id'].',';

            }

            $str=substr($str,0,-1);
            if ($str){
                $where['pid']=array('in',$str);

                $lists1 = M('user')->field('nick_name,pid,user_id,head_img,time,utype')->order('time desc')->where($where)->select();//查询第二级
                foreach ($lists1 as $k=>$v){
                    $v['level']=1;
                    $lists[]=$v;//拼接第二级

                    $str1.=$v['user_id'].',';//拼接第三级查询的id
                }
                $str1=substr($str1,0,-1);
                if ($str1){
                    $where['pid']=array('in',$str1);
                    $lists2 = M('user')->field('nick_name,pid,user_id,head_img,time,utype')->order('time desc')->where($where)->select();//查询第三级
                }
                foreach ($lists2 as $k=>$v){
                    $v['level']=2;
                    $lists[]=$v;//拼接第二级
                    $str2.=$v['user_id'].',';//拼接第四级查询的ID
                }

            }


//            $str2=substr($str2,0,-1);
//            $where['pid']=array('in',$str2);
//
//            $lists3 = M('user')->field('nick_name,pid,user_id,head_img,time,utype')->order('time desc')->where($where)->select();//查询第四级
//
//            foreach ($lists3 as $k=>$v){
//                $v['level']=3;
//                $lists[]=$v;//拼接第四季
//            }

            $disss = array();
            foreach ($lists as $k1 => $v1)
            {
                $disss[] = $v1['user_id'];
            }

            foreach ($ids as $kkkk=>$vvvv){
                if(in_array($vvvv['user_id'],$disss)){
                    unset($vvvv);
                }else{
                    $vvvv['level']=3;
                    $lists[]=$vvvv;
                }
//                $lists[$kkkk][]=$vvvv;
            }
            return $lists;
//            dump($lists);die;
        }else{   //兜客
            static $lists=array();
            $data = M('user')->field('nick_name,pid,user_id,head_img,time,utype')->where(array('pid'=>trim($uid)))->order('time desc')->select();
            foreach ($data as $k=>$v){
                $v['level']=0;//第一级
                $lists[]=$v;
            }
            $str='';
            $str1='';
            $str2='';
            foreach ($lists as $k=>$v){
                $str.=$v['user_id'].',';
            }
            $str=substr($str,0,-1);
            if ($str){
                $where['pid']=array('in',$str);

                $lists1 = M('user')->field('nick_name,pid,user_id,head_img,time,utype')->order('time desc')->where($where)->select();//查询第二级
                foreach ($lists1 as $k=>$v){
                    $v['level']=1;
                    $lists[]=$v;//拼接第二级

                    $str1.=$v['user_id'].',';//拼接第三级查询的id
                }
                $str1=substr($str1,0,-1);
                if ($str1){
                    $where['pid']=array('in',$str1);
                    $lists2 = M('user')->field('nick_name,pid,user_id,head_img,time,utype')->order('time desc')->where($where)->select();//查询第三级
                }
                foreach ($lists2 as $k=>$v){
                    $v['level']=2;
                    $lists[]=$v;//拼接第二级
                    $str2.=$v['user_id'].',';//拼接第四级查询的ID
                }
            }
//            dump($lists);die;
            return $lists;
        }

//        dump($lists);
    }
    //分销上下级改进方法(统计)
    public function orderRetailscs($uid='',$level=0){
        $Info = M('user')->field('user_id,utype')->where(array('user_id'=>$uid))->find();
        if($Info['utype'] == '20'){
//            static $lists=array();
//            static $array=array();
//            $data = M('user')->field('pid,user_id,time')->order('time desc')->select();
//            $ids = M('user')->field('pid,tk_pid,user_id')->where(array('tk_pid'=>$uid))->select();
//            foreach ($data as $k=>$v){
//                if ($v['pid'] == $uid){
//                    $v['level']=0;
//                    $lists[]=$v;
//                    foreach ($data as $kk=>$vv){
//                        if ($vv['pid'] == $v['user_id']){
//                            $vv['level']=1;
//                            $lists[]=$vv;
//                            foreach ($data as $kkk=>$vvv){
//                                if ($vvv['pid'] == $vv['user_id']){
//                                    $vvv['level']=2;
//                                    $lists[]=$vvv;
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//
//            $disss = array();
//            foreach ($lists as $k1 => $v1)
//            {
//                $disss[] = $v1['user_id'];
//            }
//            foreach ($ids as $kkkk=>$vvvv){
//                if(in_array($vvvv['user_id'],$disss)){
//                    unset($vvvv);
//                }else{
//                    $vvvv['level']=3;
//                    $lists[]=$vvvv;
//                }
////                $lists[$kkkk][]=$vvvv;
//            }
            static $lists=array();
            static $array=array();
            $data = M('user')->field('nick_name,pid,user_id,head_img,time,utype')->order('time desc')->select();
            $ids = M('user')->field('pid,tk_pid,user_id,head_img,time,utype,nick_name')->where(array('tk_pid'=>$uid))->select();
            foreach ($data as $k=>$v){
                if (trim($v['pid']) == $uid){
                    $v['level']=0;//第一级
                    $lists[]=$v;
                }
            }
            $str='';
            $str1='';
            $str2='';

            foreach ($lists as $k=>$v){

                $str.=$v['user_id'].',';

            }
            if ($str){
                $str=substr($str,0,-1);
                $where['pid']=array('in',$str);

                $lists1 = M('user')->field('nick_name,pid,user_id,head_img,time,utype')->order('time desc')->where($where)->select();//查询第二级

                foreach ($lists1 as $k=>$v){
                    $v['level']=1;
                    $lists[]=$v;//拼接第二级

                    $str1.=$v['user_id'].',';//拼接第三级查询的id
                }
                if ($str1){
                    $str1=substr($str1,0,-1);
                    $where['pid']=array('in',$str1);

                    $lists2 = M('user')->field('nick_name,pid,user_id,head_img,time,utype')->order('time desc')->where($where)->select();//查询第三级

                    foreach ($lists2 as $k=>$v){
                        $v['level']=2;
                        $lists[]=$v;//拼接第二级
                        $str2.=$v['user_id'].',';//拼接第四级查询的ID
                    }
                }

            }

//            $str2=substr($str2,0,-1);
//            $where['pid']=array('in',$str2);
//
//            $lists3 = M('user')->field('nick_name,pid,user_id,head_img,time,utype')->order('time desc')->where($where)->select();//查询第四级
//
//            foreach ($lists3 as $k=>$v){
//                $v['level']=3;
//                $lists[]=$v;//拼接第四季
//            }

            $disss = array();
            foreach ($lists as $k1 => $v1)
            {
                $disss[] = $v1['user_id'];
            }

            foreach ($ids as $kkkk=>$vvvv){
                if(in_array($vvvv['user_id'],$disss)){
                    unset($vvvv);
                }else{
                    $vvvv['level']=3;
                    $lists[]=$vvvv;
                }
//                $lists[$kkkk][]=$vvvv;
            }
//                        dump($lists);die;
            return $lists;
        }else{
            $data = M('user')->field('nick_name,pid,user_id,head_img,time,utype')->order('time desc')->select();
            static $lists=array();

            foreach($data as $k=>$v){
                if($v['pid'] == $uid){
                    $v['level']=0;
                    $lists[]=$v;
                    foreach ($data as $kk=>$vv){
                        if ($vv['pid'] == $v['user_id']){
                            $vv['level']=1;
                            $lists[]=$vv;
                            foreach ($data as $kkk=>$vvv){
                                if ($vvv['pid'] == $vv['user_id']){
                                    $vvv['level']=2;
                                    $lists[]=$vvv;
                                }
                            }
                        }
                    }
                }
            }
            return $lists;
        }
    }

   //兜客和推客的结算数据 2.0  2.1 无缝对接
    public function retailEnd($uid='',$p = 0,$num = 10){

        $w['user_id']=$uid;
        $w['type'] = 3;
     $data = M('moneyjs_detailed')->where($w)->order('t desc')->limit($p*$num,$num)->select();
//     dump($data);die;
     return $data;
    }
    //结算总数 2.0  2.1 无缝对接
    public function retailEndCount($uid=''){
        $w['user_id']=$uid;
        $w['type'] = 3;
        $data = M('moneyjs_detailed')->where($w)->count();
        return $data;
    }
    //兜推客成功结算的金额 2.0  2.1 无缝对接
    public function successClose($uid=''){
      $w['user_id']=$uid;
      $w['js_status']=2;
      $w['type']=3;
      $sumData = M('moneyjs_detailed')->where($w)->select();
      return $sumData;
    }
    //可结算金额和结算失败金额
    public function settledMoney($uid=''){
        $uid = $uid;
     $msg = M('moneyjs_detailed')->where("user_id = '{$uid}' and js_status != 2")->select();
     return $msg;
    }
    //获得会员上下级
    public function orderRe($uid='',$level=0){
      $data = M('user')->field('nick_name,pid,user_id,utype')->select();
        // static $lists=array();
        // static $array=array();
        static $arr=array();

      foreach($data as $k=>$v){
          //获取上级  
          if ($v['user_id'] == $uid) {
              $v['level'] = $level;
            $lists = M('user')->field('nick_name,pid,user_id,utype')->where(array('user_id'=>$v['pid']))->find();
            if($lists)
            {
              if($lists['utype'] ==  '20'){
                  $lists['level'] = $v['level'];
                  $arr["TWO"] = $lists;  # 所属推客
                  $li = M('institution')->field('username,password,institution_id')->where(array('institution_id'=>$arr["TWO"]['pid']))->find();
                  $arr["THREE"] = $li;
              }else if($lists['utype'] != '20'){
              }
              # 一级
//              $arr['ONE'] = $lists;
                if ($v['level'] >= 2){
                    break;
                }
            }
              $this->orderRe($lists['user_id'],$level+1);

          }

      }
        return $arr;

    }
    # 获取上级兜客、推客、机构  ---  暂时不需要用到
    # type = 1上级兜客   2推客  3机构
    public function levelY($type=1,$uid='')
    {
        $d = array();
        if($type == 1)
        {
            $d = $this->levelO($uid);
        }else if($type == 2)
        {
            $d = $this->levelS($uid);
        }else if($type == 3)
        {
            $d = $this->levelJ($uid);
        }else{
            // 默认
        }

        return $d;
    }

    # 获取上级兜客
    public function levelO($uid='')
    {
        $d = array();
        $pid = M('user')->where("user_id = '{$uid}' AND (utype = 1 OR utype = 10)")->getField('pid');
        if($pid!=0)
        {
            $d = M('user')->field('user_id,nick_name')->where(array('user_id'=>$pid))->find();
        }
        return $d;
    }

    # 获取上级推客
    public function levelS($uid='')
    {
        $data = M('user')->field('nick_name,pid,user_id,utype')->where(array('utype'=>'20'))->order('user_id desc')->select();
        static $d=array();

        foreach($data as $k=>$v){
            //获取上级
            //var_dump($v['user_id'].' --- '.$uid);
            if ($v['user_id'] == $uid) {
                $d = M('user')->field('nick_name,user_id')->where(array('pid'=>$v['user_id']))->find();
//                $this->levelS($v['id']);
            }
        }
        return $d;
    }

    # 获取上级机构
    public function levelJ($uid='')
    {
        $d = array();
        $pid = M('user')->where(array('user_id'=>$uid,'utype'=>'20'))->getField('pid');
        if($pid!=0)
        {
            $d = M('institution')->field('institution_id,username')->where(array('institution_id'=>$pid))->find();
        }
        return $d;
    }

 }

    
?>