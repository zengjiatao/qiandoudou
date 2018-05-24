<?php
/**
 * Created by 银迅通支付网络公司.
 * User: Jeffery
 * Date: 2017/12/8
 * Time: 21:48
 * 相关记录资金变动日志/等其他资金问题函数
 */

namespace Func\Controller;
use Think\Controller;

class MoneyController extends Controller
{
    /**
     * 资金变动
     */
    public function userMoneyLog($pdata=array())
    {
        $pdata = array(
            'user_id' => trim($pdata['user_id']),
            'msg' => trim($pdata['msg']),
            'money' => trim($pdata['money']),
            'pn' => trim($pdata['pn']),  // + -
            'ordersn' => trim($pdata['ordersn']),
            'type' => trim($pdata['type']),  // 1收益结算3余额结算
            'is_type' => trim($pdata['is_type']),  // 1收益余额结算2钱包余额结算3等其他
            't' => time(),
        );
        $res = M("usermoney_log")->add($pdata);
        if($res)
        {
            return true;
        }else{
            return false;
        }
    }
}