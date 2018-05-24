<?php
# 银钱包对接接口 - 公共功能模块接口

namespace Payapi\Controller;
use Think\Controller;
class BaseController extends Controller{

    public function _initialize()
    {
        # 网站公钥
//        $token = trim($_REQUEST['token']);
//        if($token!=C('TOKEN'))
//        {
////            $rd = array(
////              'code' => '200001', 'msg' => '小伙伴，你的权限可不够哦'
////            );
////            echoAJson($rd);
//        }
    }

    public function index(){
        echo '不允许访问该文件';
    }
}