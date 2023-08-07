<?php
//声明命名空间
namespace Home\controller;
use Home\model\LoginModel;
use Home\model\UserModel;
use Frame\libs\BaseController;
use Frame\vendor\Captcha;

final class LoginController extends BaseController{

    //获取验证码
    public function captcha() {
        //创建验证码对象
        new Captcha();

    }

    //定义展示首页控制器
    public function index(){

        //若已经登录，则跳转到首页
        if(isset($_SESSION['username']) && isset($_SESSION['uid'])){
            //若有session，要看session是否来自于数据库
            $username = htmlspecialchars($_SESSION['username']); // 转义防注入
            $id = htmlspecialchars($_SESSION['uid']); // 转义防注入

            @$user = UserModel::getObj()->fetchOne("id = '$id' and username = '$username'");
            if(!empty($user)) {
                header("refresh:0;url=?c=Index&a=main");
            }
        }

        //展示登录页面
        $this->smartyObj->display('login.html');
    }

    //处理登录表单
    public function loginCheck() {


        //判断是否提交按钮
        if(!isset($_POST['submitButton'])) {
            $this->jump(5, "?c=Login&a=index", "你从哪来？");
        }

        //获取表单
        $username = htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8');
        $password = md5(htmlspecialchars($_POST['userpwd'], ENT_QUOTES, 'UTF-8'));
        $captcha = htmlspecialchars($_POST['captcha']);

        //判断验证码是否正确
        if(strtolower($captcha) != strtolower($_SESSION['captcha']) ) {
            $this->jump(5, "?c=Login&a=index", "验证码错误");
        }

        //获取对应用户数据
        $user = UserModel::getObj()->fetchOne("username = '$username' and password = '$password'");

        //判断账号密码是否正确
        if(empty($user)) {
            $this->jump(5, "?c=Login&a=index", "用户名或密码不正确");
        }

        //更新用户信息：最后登录的ip，最后登录的时间，总登录次数
        $data['last_login_ip'] = $_SERVER['REMOTE_ADDR'];
        $data['last_login_time'] = time();
        $data['login_times'] = $user['login_times'] + 1;
        //如果更新失败——
        if(!UserModel::getObj()->update($data, $user['id'])){
            $this->jump(5, "?c=Login&a=index", "用户更新失败");
        }

        //将用户信息写入session
        $_SESSION['uid'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['login_times'] = $user['login_times'];
        $_SESSION['last_login_time'] = time();

        //同时将登录信息记录到登陆日志
        $login_data['user_id'] = $user['id'];
        $login_data['username'] = $username;
        $login_data['login_ip'] = $_SERVER['REMOTE_ADDR'];
        $login_data['login_time'] = time();
        LoginModel::getObj()->add($login_data);

        //跳转到网站前台页面
        $this->jump(5, "?c=Index&a=index", "登陆成功，正在跳转……");
    }

    //定义登出方法
    public function logout() {

        //删除session数据
        unset($_SESSION['uid']);
        unset($_SESSION['username']);
        //删除session文件
        session_destroy();
        //删除session对应的cookie数据
        setcookie(session_name(), false);
        //跳转到前台登录页面
        $this->jump(5, "?c=Index&a=index", "用户退出成功");

    }
}