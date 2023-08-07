<?php
//声明命名空间
namespace Admin\model;
use Frame\libs\BaseModel;

final class LoginModel extends BaseModel{
    //受保护的表名属性
    protected string $table = "login_logs";

    //获取对应id用户的所有登陆日志数据（二维数组）
    public function fetchAllById($id) {
        //倒序排的条件
        $descStr = "order by id desc ";
        //限制5条记录的条件
        $limitStr = "limit 5 ";
        //构建获取所有登陆日志数据的sql语句
        $sql = "select * from $this->table where user_id='$id' " . $descStr . $limitStr;

        //执行并返回所有日志集
        return $this->pdo->dao_query($sql, true);
    }

    //获取拥有登录记录的用户数据
    public function getUser() {
        //构建sql
        $sql = "select username from $this->table group by username";

        //执行sql，返回结果
        return $this->pdo->dao_query($sql, true);
    }

    //分页查询
    public function fetchAllByPage($pageSize, $currentPage, $where = "2>1"){
        //limit参数一：开始索引
        $index = ($currentPage - 1) * $pageSize;
        //limit参数二：查询条目数=pageSize

        //构建分页查询sql
        $sql = "select * from $this->table ";
        $sql .= "where $where ";
        $sql .= "order by login_time desc ";
        $sql .= "limit $index, $pageSize ";

        //执行sql，返回结果
        return $this->pdo->dao_query($sql, true);
    }
}