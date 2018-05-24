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

class EachController extends Controller
{
    /**
     * 上福进件商户 - 重新进件
     */
    public function mercherSf()
    {
        $user_id = "Q00003961";
        $tdid = "25";
        $wherebank['user_id']=array('eq',$user_id);
        $wherebank['is_normal']=array('eq',1);

        $mybany=M('mybank')->JOIN('LEFT JOIN y_bank on y_bank.bank_id=y_mybank.bank_id')->field('y_mybank.*,y_bank.name as bname,y_bank.lianhanghao')->where($wherebank)->find();

        if(!$mybany)
        {
           echo("请先绑定默认结算卡！");
        }

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

    # 商户入驻
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
//        if($tdid == 25) # 有积分有短信 - 调用
//        {
//            $mydata['leg_phone'] = substr($mydata['leg_phone'],0,3).mt_rand(10000000,99999999);
//        }
        $data['leg_phone']=$mydata['leg_phone']; //法人电话

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


        $result=cURLSSLHttp($url,$data);

        $mydata['sp_id']= $data['sp_id'];

        $where['user_id']=$mydata['user_id'];
        $where['sp_id']=$data['sp_id'];  # 新增多个服务商号判断

        $wherebank['user_id']=array('eq',$mydata['user_id']);
        $wherebank['is_normal']=array('eq',1);
        $mybanyTrue=M('mybank')->JOIN('LEFT JOIN y_bank on y_bank.bank_id=y_mybank.bank_id')->field('y_mybank.*,y_bank.name as bname,y_bank.lianhanghao')->where($wherebank)->find();
        $where['leg_phone']=$mybanyTrue['mobile'];
        dump($where);die;


        $nowtime= date("Y-m-d h:i:s", time());
        # 写入日志
        R("Payapi/Api/PaySetLog",array("./PayLog","GatePayController_register_Return__",$nowtime.'请求参数'.implode(',',$data).'----回调返回信息参数----'.$result));

        $msg=json_decode($result);

        echo '---返回信息';
        dump($msg);
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

        $mydata['sp_id'] = $data['sp_id'];

        $where['user_id']=$mydata['user_id'];
        $where['sp_id']=$data['sp_id'];  # 新增多个服务商号判断

        $wherebank['user_id']=array('eq',$mydata['user_id']);
        $wherebank['is_normal']=array('eq',1);
        $mybanyTrue=M('mybank')->JOIN('LEFT JOIN y_bank on y_bank.bank_id=y_mybank.bank_id')->field('y_mybank.*,y_bank.name as bname,y_bank.lianhanghao')->where($wherebank)->find();
        $where['leg_phone']=$mybanyTrue['mobile'];
        dump($where);

        $gatepay_user=M('gatepay_user')->where($where)->find();
        dump($gatepay_user);
        dump($mydata);

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
     * 智能付 - 进件测试
     */
    public function znfceshi()
    {
        $url = "https://www.silverpay.cn/public/yrt_api/Pay/merchUpload";
        $signData = array(
            'merchId' => '298149969401',
            'signKey' => 'f928b87ca0878b3e5791e2f703039310',
            'mcht_name' => '林良振',
            'mcht_short_name' => '林良振',
            'province' => '130000',
            'city' => '130500',
            'area' => '130503',
            'address' => '河北省邢台市桥西区',
            'leg_name' => '林良振',
            'leg_phone' => '13790957780',
            'leg_email' => '13790957780@qq.com',
            'id_no' => '440881199402215552',
            'acc_name' => '林良振',
            'acc_no' => '6212263602074800616',
            'acc_bank_name' => '中国工商银行',
            'acc_bank_no' => '102133208249',
            'acc_bank_mobile' => '13790957780',
            'nonce_str' => mt_rand(1000000,9999999),
            'bus_type' => 'WKPAY',
        );
        ksort($signData);
        $keys = array_keys($signData); //获取key数组
        $values=array_values($signData); //获取values数组
        $signstr="";
        for ($i = 0; $i < count($values); $i++) {
            $signstr=$signstr.$keys[$i]."=".$values[$i]."&";
        }
        $signstr=$signstr."key=f928b87ca0878b3e5791e2f703039310";
        $sign= strtoupper(Md5($signstr)); //转大写
        $data = array(
            'merchId' => '298149969401',
            'signKey' => 'f928b87ca0878b3e5791e2f703039310',
            'mcht_name' => '林良振',
            'mcht_short_name' => '林良振',
            'province' => '130000',
            'city' => '130500',
            'area' => '130503',
            'address' => '河北省邢台市桥西区',
            'leg_name' => '林良振',
            'leg_phone' => '13790957780',
            'leg_email' => '13790957780@qq.com',
            'id_no' => '440881199402215552',
            'acc_name' => '林良振',
            'acc_no' => '6212263602074800616',
            'acc_bank_name' => '中国工商银行',
            'acc_bank_no' => '102133208249',
            'acc_bank_mobile' => '13790957780',
            'nonce_str' => mt_rand(1000000,9999999),
            'cardZmImg' => '',
            'cardFmImg' => '',
            'jsCardImg' => '',
            'xinycard_zm' => '',
            'bus_type' => 'WKPAY',
            'sign' => $sign
        );
        $result=cURLSSLHttp($url,$data);
        dump($result);die;
    }

    /**
     * 智能付 - 进件测试
     */
    public function znfPayceshi()
    {
        $url = "https://www.silverpay.cn/public/yrt_api/Pay/merchSend";
        $signData = array(
            'merchId' => '298149969401',
            'signKey' => 'f928b87ca0878b3e5791e2f703039310',
            'merch_id' => '800000041276456',
            'out_trade_no' => 'znf'.date('Ymndhis').get_timeHm(),
            'total_fee' => '50000',
            'return_url' => 'http://baidu.com',
            'notify_url' => 'xxxx',
            'bank_code' => 'ECITIC',
            'bank_name' => '邹东琪',
            'card_name' => '18219247391',
            'card_no' => '6258101664659739',
            'id_no' => '450802199212100910',
            'acc_name' => '邹东琪',
            'bank_mobile' => '18219247391',
            'card_valid_date' => '0323',
            'cvv2' => '603',
            'nonce_str' => mt_rand(1000000,9999999),
            'settle_rate' => '0.48',
            'extra_rate' => '2',
            'bus_type' => 'WKPAY',
        );
        ksort($signData);
        $keys = array_keys($signData); //获取key数组
        $values=array_values($signData); //获取values数组
        $signstr="";
        for ($i = 0; $i < count($values); $i++) {
            $signstr=$signstr.$keys[$i]."=".$values[$i]."&";
        }
        $signstr=$signstr."key=f928b87ca0878b3e5791e2f703039310";
        $sign= strtoupper(Md5($signstr)); //转大写
        $data = array(
            'merchId' => '298149969401',
            'signKey' => 'f928b87ca0878b3e5791e2f703039310',
            'merch_id' => '800000041276456',
            'out_trade_no' => 'znf'.date('Ymndhis').get_timeHm(),
            'total_fee' => '50000',
            'return_url' => 'http://baidu.com',
            'notify_url' => 'xxxx',
            'bank_code' => 'ECITIC',
            'bank_name' => '邹东琪',
            'card_name' => '18219247391',
            'card_no' => '6258101664659739',
            'id_no' => '450802199212100910',
            'acc_name' => '邹东琪',
            'bank_mobile' => '18219247391',
            'card_valid_date' => '0323',
            'cvv2' => '603',
            'nonce_str' => mt_rand(1000000,9999999),
            'settle_rate' => '0.48',
            'extra_rate' => '2',
            'bus_type' => 'WKPAY',
            'sign' => $sign
        );
        $result=cURLSSLHttp($url,$data);
        dump($result);die;
    }
}
