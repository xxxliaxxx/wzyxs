<?php
//声明命名空间
namespace Admin\controller;
use Admin\model\LoginModel;
use Admin\model\UserModel;
use Frame\libs\BaseController;

//创建用户控制器类
class UserController extends BaseController{
    public function __construct() {
        //调用父类的构造函数
        parent::__construct();

        //判断用户是否已经登录，若未登录，跳转到index登录页面
        $this->denyAccess();
    }

    //批量添加用户
    public function addUsers() {
        //获取csv的每一行
        $csvArr = preg_split('/\r\n/', htmlspecialchars($_POST['user_csv'], ENT_QUOTES));

        $users = [];
        foreach ($csvArr as &$str) {
            if ($str == "") continue; // 忽略空行

            $fields = explode(",", $str);

            //必须有前三个字段
            if (empty($fields[0]) || empty($fields[1]) || empty($fields[2])) {
                $this->jump(5, "?c=Index&a=addUsers", "格式错误");
            }

            $user['name'] = $fields[0];
            $user['username'] = $fields[1];
            $user['password'] = $fields[2];
            $user['tel'] = $fields[3];
            $user['pic'] = $fields[4];

            $user['addate'] = time();
            $user['role'] = 2;

            $users[] = $user;
        }

        if (empty($users)) {
            $this->jump(5, "?c=Index&a=addUsers", "格式错误");
        }

        //获取模型类
        $userModel = UserModel::getObj();

        $fail = []; //记录注册失败的注册

        foreach ($users as $user) {
            //判断用户是否已经注册
            if($userModel->rowCount("username = '{$user['username']}'")) {
                $fail[] = array(
                    "name" => $user['name'],
                    "username" => $user['username'],
                    "reasonMsg" => "用户名已存在"
                );
                continue;
            }
            elseif (strlen($user['password']) < 5 || strlen($user['password']) > 15 || !preg_match('/^[a-zA-Z0-9]+$/', $user['password'])) {
                $fail[] = array(
                    "name" => $user['name'],
                    "username" => $user['username'],
                    "reasonMsg" => "密码格式错误，长度应在5-15之间，且为字母或数字，不能为特殊字符"
                );
                continue;
            }
            elseif (strlen($user['username']) < 5 || strlen($user['username']) > 15 || !preg_match('/^[a-zA-Z0-9]+$/', $user['username'])) {
                $fail[] = array(
                    "name" => $user['name'],
                    "username" => $user['username'],
                    "reasonMsg" => "用户名格式错误，长度应在5-15之间，且为字母或数字，不能为特殊字符"
                );
                continue;
            }


            //添加用户，失败记录原因
            if (!$userModel->add($user)) {
                $fail[] = array(
                    "name" => $user['name'],
                    "username" => $user['username'],
                    "reasonMsg" => "字段错误"
                );
            }
        }
        if(empty($fail)) {
            $this->jump(5, "?c=Index&a=addUsers", "添加成功");
        }
        else {
            $this->smartyObj->assign(array(
                "page" => "?c=Index&a=addUsers", //page若为空，则返回上一页
                "fail" => $fail
            ));
            $this->smartyObj->display("user/add-users-return.html");
        }

    }

    //添加用户方法
    public function addUser() {

        //获取post数据
        $data = $this->getPost();


        $data['addate'] = time();

        //获取模型类
        $userModelObj = UserModel::getObj();

        //判断用户是否已经注册
        if($userModelObj->rowCount("username = '{$data['username']}'")) {
            $this->jump(5, "?c=Index&a=user", "该用户名已经被注册");
        }

        //插入用户，并返回插入是否成功
        if($userModelObj->add($data)) {
            $this->jump(5, "?c=Index&a=user", "添加成功");
        }
        else {
            $this->jump(5, "?c=Index&a=user", "添加失败");
        }

    }

    //禁用用户的方法
    public function banUser() {

        //获取id
        $id = $_GET['id'];

        //根据id查询该用户目前状态
        $status = UserModel::getObj()->get("status", "id = $id");

        //将当前用户状态更改为“反”
        $status ^= 1;

        //根据id更新用户状态，并判断是否更新成功
        $data['status'] = $status;

        if(UserModel::getObj()->update($data, $id)) {
            $this->jump(5, "?c=Index&a=user", "修改成功");
        }
        else {
            $this->jump(5, "?c=Index&a=user", "修改失败");
        }

    }

    //修改用户方法
    public function updateUser() {

        //获取get的id数据
        $id = $_GET['id'];

        //获取post数据
        $data = $this->getPost();

        //修改数据并判断是否成功
        if(UserModel::getObj()->update($data, $id)) {
            $this->jump(1, "?c=Index&a=user", "修改成功");
        }
        else {
            $this->jump(1, "?c=Index&a=user", "修改失败");
        }


    }

    //修改用户时异步回显数据的方法
    public function updateEcho() {

        //获取id
        $id = $_POST['id'];

        //根据id查询整行用户数据
        echo json_encode(UserModel::getObj()->fetchOne("id = $id"));
    }

    //删除用户方法
    public function deleteUser() {

        //获取要删除的用户的id
        $id = $_GET['id'];

        //删除并判断是否删除成功
        if(UserModel::getObj()->delete($id)) {
            $this->jump(1, "?c=Index&a=user", "删除成功");
        }
        else {
            $this->jump(1, "?c=Index&a=user", "删除失败");
        }
    }

    //删除用户登录记录
    public function deleteLog() {
        $id = $_GET['id'];
        if(LoginModel::getObj()->delete($id)) {
            $this->jump(5, "?c=Index&a=Loginlog", "删除成功");
        }
        else {
            $this->jump(5, "?c=Index&a=Loginlog", "删除失败");
        }
    }

    private function getPost(): array {
        $data['name'] = $_POST['truename'];
        $data['username'] = $_POST['username'];
        if(!empty($_POST['password'])) {
            $data['password'] = md5(htmlspecialchars($_POST['password'], ENT_QUOTES));
        }
        $data['tel'] = $_POST['tel'];
        $data['status'] = $_POST['status'];
        $data['role'] = $_POST['role'];
        $data['pic'] = $_POST['pic'];
        return $data;
    }
}