<?php# 银钱包对接接口 - 所有支付回调地址模块接口# 2017.11.2namespace Payapi\Controller;use Think\Controller;class ChongzhiReturnController extends Controller{    public function _initialize()    {    }    #19e 话费充值异步通知接口    public function e19Return(){        $input = file_get_contents('php://input');        //验签        $input=urldecode(urldecode($input));//        M('Cs')->add(array('text'=>json_encode($input)));        $farr = explode('&', $input);        file_put_contents('./Public/log/19e.txt',json_encode($input), FILE_APPEND);        R("Payapi/Api/PaySetLog",array("./Public/log","19eReturn_","返回参数".json_encode($farr)."\r\n"));        foreach( $farr as $val ) {            $valArr = explode('=', $val);            $newArr[$valArr[0]]=$valArr[1];        }        $epay = new \Com\E19\Chongzhi;        $res=$epay->checkSign($newArr);        if($res==$newArr['sign']){            if($input['status']=='SUCCESS'){                $this->orderjiesuan($input['eOrderId'],$input['order_no'],$input['finishTime'],'NineteenE');            }            if($input['status']=='FAIL'){                $this->faildjiesuan($input['eOrderId'],$input['order_no'],$input['finishTime'],'NineteenE');            }        }      echo'resultCode=SUCCESS&resultDesc=DESC'; exit;    }    #banma话费充值接口    public function banma(){        $input=$_REQUEST;//        M('Cs')->add(array('text'=>json_encode($input)));        file_put_contents('./Public/log/banmes.txt',json_encode($input), FILE_APPEND);        R("Payapi/Api/PaySetLog",array("./Public/log","banmesReturn_","返回参数".json_encode($input)."\r\n"));        if($input['recharge_state']==1){            $this->orderjiesuan($input['tid'],$input['outer_tid'],$input['timestamp'],'Banma');        }        if($input['recharge_state']==9){            $this->faildjiesuan($input['tid'],$input['outer_tid'],$input['timestamp'],'Banma');        }        echo'success'; exit;    }    #qianyi流量充值接口    public function qianyi(){        $input=$_REQUEST;//        M('Cs')->add(array('text'=>json_encode($input)));        file_put_contents('./Public/log/qianyi.txt',json_encode($input), FILE_APPEND);        R("Payapi/Api/PaySetLog",array("./Public/log","qianyiReturn_","返回参数".json_encode($input)."\r\n"));        if($input['recharge_state']==1){            $this->orderjiesuan($input['tid'],$input['outer_tid'],$input['timestamp'],'Qianyi');        }        if($input['recharge_state']==9){            $this->faildjiesuan($input['tid'],$input['outer_tid'],$input['timestamp'],'Qianyi');        }        echo'success'; exit;    }////{//"ext_data": "{\"name\":\"value\",\"name1\":\"value1\"}",//"outer_tid": "TID2017051245874512",//"recharge_state": "1",//"sign": "444F4A793F22D7483C240FC489D8DB8710D1F45A",//"tid": "S1705051216911",//"timestamp": "2017-04-13 15:58:41",//"user_id": "A891718"//}    #开心1充值接口 qbi 加油卡    public function kaixinReturn(){        $xml=$_REQUEST;//        M('Cs')->add(array('text'=>json_encode($xml)));        file_put_contents("./Public/log/kaixin1.txt",json_encode($xml), FILE_APPEND);        R("Payapi/Api/PaySetLog",array("./Public/log","kaixin1_Return_","返回参数".json_encode($xml)."\r\n"));        if($xml['status']=='SUCCESS'){            $this->orderjiesuan($xml['stream_id'],$xml['order_no'],$xml['order_time'],'KaixinOne');        }        if($xml['status']=='FAILED'){            $this->faildjiesuan($xml['stream_id'],$xml['order_no'],$xml['order_time'],'KaixinOne');        }        echo'SUCCESS'; exit;    }    #开心1充值接口    public function kaixin1Return(){        $xml=$_REQUEST;//        M('Cs')->add(array('text'=>json_encode($xml)));        file_put_contents("./Public/log/kaixin2.txt",json_encode($xml), FILE_APPEND);        R("Payapi/Api/PaySetLog",array("./Public/log","kaixin2_Return_","返回参数".json_encode($xml)."\r\n"));        if($xml['status']=='SUCCESS'){                $this->orderjiesuan($xml['stream_id'],$xml['order_no'],$xml['order_time'],'KaixinOne');            }        if($xml['status']=='FAILED'){            $this->faildjiesuan($xml['stream_id'],$xml['order_no'],$xml['order_time'],'KaixinOne');        }        echo'SUCCESS'; exit;    }    #开心2话费充值接口    public function kaixin2Return(){        $xml=$_REQUEST;//        M('Cs')->add(array('text'=>json_encode($xml)));        file_put_contents("./Public/log/kaixin3.txt",json_encode($xml));        R("Payapi/Api/PaySetLog",array("./Public/log","kaixin3_Return_","返回参数".json_encode($xml)."\r\n"));        if($xml['status']=='SUCCESS'){            $this->orderjiesuan($xml['stream_id'],$xml['order_no'],$xml['order_time'],'KaixinTwo');        }        if($xml['status']=='FAILED'){            $this->faildjiesuan($xml['stream_id'],$xml['order_no'],$xml['order_time'],'KaixinTwo');        }        echo'SUCCESS'; exit;    }    #开心2话费充值接口    public function kaixinNew(){        $input = file_get_contents('php://input');        //验签        $input=urldecode(urldecode($input));        $input=json_decode($input,true);//转化为数组        file_put_contents("./Public/log/kaixinnews.txt",json_encode($input), FILE_APPEND);        R("Payapi/Api/PaySetLog",array("./Public/log","kaixinnews_Return","返回参数".json_encode($input)."\r\n"));//        M('Cs')->add(array('text'=>json_encode($input)));        if($input['status']=='SUCCESS'){            $this->orderjiesuan($input['stream_id'],$input['order_no'],$input['order_time'],'KaixinNew');        }        if($input['status']=='FAILED'){            $this->faildjiesuan($input['stream_id'],$input['order_no'],$input['order_time'],'KaixinNew');        }        echo'SUCCESS'; exit;    }//充值成功 结算    public  function orderjiesuan($oid,$ono,$time,$tongdao){        if($oid && $ono){            $where['order_number']=$ono;            $info=M('chongzhi_order')->where($where)->find();//查询该订单记录            if($info['status']==1) die; //如果状态已改变 不在修改该订单            $order_info['ischongzhi']='已充值';            $order_info['status']='1';            $order_info['finishtime']=time();            $order_info['other_order_number']=$oid;            $order_info['is_refund'] = 5;            M('chongzhi_order')->where($where)->save($order_info);            $map['pt_ordersn']=$ono;            $tt['jy_status'] = 1;//结算状态 1结算中2结算成功3失败4未结算            M('money_detailed')->where($map)->save($tt);            $order_infos=M('chongzhi_order')->where($where)->save($order_info);//查询该订单记录            if($order_infos['fee']>=100){//计算是否添加积分                $jifen['num']=intval($order_info['fee']/100);                $jifen['t']=time();                $jifen['price']=100;                $jifen['user_id']=$order_info['user_id'];                if($order_infos['type']=='money'){                    $jifen['type']=1;                }                elseif ($order_infos['type']=='flow'){                    $jifen['type']=1;                }                elseif ($order_infos['type']=='Qbi'){                    $jifen['type']=3;                }                elseif ($order_infos['type']=='refuel'){                    $jifen['type']=4;                }                M('jifen_jilu')->data($jifen)->add();            }        }    }    //充值失败 写入状态    public  function faildjiesuan($oid,$ono,$time,$tongdao){        if($oid && $ono){            $where['order_number']=$ono;            $info=M('chongzhi_order')->where($where)->find();//查询该订单记录            if($info['status']==2) die; //如果订单已修改为充值失败 不在进行下面修改            $order_info['ischongzhi']='充值失败';            $order_info['status']='2';            $order_info['is_refund']=1;//        $order_info['tongdao']=$tongdao;//        $order_info['finishtime']=$time;            $order_info['other_order_number']=$oid;//            $where['order_number']=$ono;            M('chongzhi_order')->where($where)->save($order_info);            $map['pt_ordersn']=$ono;            $tt['jy_status'] = 3;//结算状态 1结算中2结算成功3失败4未结算            M('money_detailed')->where($map)->save($tt);        }    }}