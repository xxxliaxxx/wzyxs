<?php
//声明命名空间
namespace Admin\controller;

use Admin\model\UserModel;
use Frame\libs\BaseController;

class PersonalController extends BaseController{
    public function __construct() {
        //调用父类的构造函数
        parent::__construct();

        //只判断是否登录
        $this->denyAccessIndex();
    }

    //修改签名
    public function updateSignature() {
        $id = $_SESSION['uid']; // 当前用户id
        $data['signature'] = htmlspecialchars($_POST['signature'], ENT_QUOTES) ; // 签名

        //更新数据库
        if (UserModel::getObj()->update($data, $id)) {
            $this->jump(5, "?c=Index&a=personal", "更新成功");
        }
        else {
            $this->jump(5, "?c=Index&a=personal", "更新失败");
        }
    }

    //修改头像
    public function updateAvatar() {

        $id = $_SESSION['uid']; // 当前用户id
        $data['pic'] = htmlspecialchars($_POST['url'], ENT_QUOTES) ; // 图片url

        //更新数据库
        if (UserModel::getObj()->update($data, $id)) {
            $this->jump(5, "?c=Index&a=personal", "更新成功");
        }
        else {
            $this->jump(5, "?c=Index&a=personal", "更新失败");
        }
    }

}