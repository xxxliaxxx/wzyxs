<?php
//声明命名空间
namespace Home\model;
use Frame\libs\BaseModel;

//创建最终用户类
final class UserModel extends BaseModel{

    //受保护的数据表名称（可以让继承父类来的方法使用）
    protected string $table = "user";

    //获取指定用户
    public function getUser($where) {
        $sql = "select * from $this->table where $where";
        return $this->pdo->dao_query($sql, false);
    }

}