<?php
//声明命名空间
namespace Frame\libs;
use Admin\model\UserModel;
use Frame\vendor\smarty;

abstract class BaseController{
    protected $smartyObj = null;

    //构造函数
    public function __construct(){
        $this->initSmarty();//smarty对象初始化
    }

    //smarty初始化
    private function initSmarty(){
        //创建smarty对象
        $this->smartyObj = new smarty();
        //配置smarty
        $this->smartyObj->left_delimiter = '<{';                                        //左界定符
        $this->smartyObj->right_delimiter = '}>';                                       //右界定符
        $this->smartyObj->setTemplateDir(VIEW_PATH);                         //设置视图文件路径
        $this->smartyObj->setCompileDir(sys_get_temp_dir() . DS .'View');     //设置编译文件路径
    }

    //定义公用跳转页面方法
    protected function jump($time, $page, $str) {
        $this->smartyObj->assign(array(
            "time" => $time,
            "page" => $page, //page若为空，则返回上一页
            "str" => $str
        ));
        $this->smartyObj->display("jump.html");
        die();
    }

    //判断用户是否登录
    protected function isLogin(): bool {
        if(isset($_SESSION['username']) && isset($_SESSION['uid'])){
            //若有session，要看session是否来自于数据库
            $username = htmlspecialchars($_SESSION['username']); // 转义防注入
            $id = htmlspecialchars($_SESSION['uid']); // 转义防注入

            @$user = UserModel::getObj()->fetchOne("id = '$id' and username = '$username'");
            if(!empty($user)) {
                return true;
            }
        }
        return false;
    }

    //前台访问拦截
    protected function denyAccessIndex() {
        //判断是否登录
        if(!$this->isLogin()) {
            $this->jump(5, "?c=Login&a=index", "请先登录");
        }
    }

    //后台访问拦截
    protected function denyAccess() {
        if(!$this->isLogin()) {
            $this->jump(5, "?c=Login&a=index", "请先登录");
        }

        //后端权限过滤
        @$role = UserModel::getObj()->get("role", "id = {$_SESSION['uid']}"); // 获取角色

        if($role == 0) {
            if(in_array(strtolower(CONTROLLER), $GLOBALS['config']['bannedPage'])){
                $this->jump(5, "?c=Index&a=main", "您的权限不足");
            }
        }
        else if($role == 2) {
            if(in_array(strtolower(CONTROLLER), $GLOBALS['config']['visitorBannedPage'])){
                $this->jump(5, "?c=Index&a=main", "您的权限不足");
            }
        }
        else if($role != 1) {
            echo "你是谁？";
            die();
        }


    }

    //对htmlspecialchars编码过的内容进行解码
    protected function decodeData($data) {
        if($data == null) return null;
        foreach ($data as $k => $v) {
            $data[$k] = htmlspecialchars_decode($v);
        }
        return $data;
    }

}