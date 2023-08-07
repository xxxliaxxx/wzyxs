<?php
//声明命名空间
namespace Admin\model;
use Frame\libs\BaseModel;

//创建最终用户类
final class UserModel extends BaseModel{

    //受保护的数据表名称（可以让继承父类来的方法使用）
    protected string $table = "user";

    //获取用户id和用户名
    public function fetchIdAndName() {
        $sql = "select id, username from $this->table";
        return $this->pdo->dao_query($sql, true);
    }

    //获取前台用户必要展示信息
    public function fetchInfo($where = "2 > 1") {
        $sql = "select username, name, tel, last_login_ip, last_login_time, login_times, status, role, addate, pic, signature from $this->table where $where";
        return $this->pdo->dao_query($sql, false);
    }

}