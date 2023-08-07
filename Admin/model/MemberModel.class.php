<?php
namespace Admin\model;
use Frame\libs\BaseModel;

final class MemberModel extends BaseModel {

    protected string $table = "member";

    //分页查询
    public function fetchAllByPage($pageSize, $currentPage, $where = "2>1"){
        //limit参数一：开始索引
        $index = ($currentPage - 1) * $pageSize;
        //limit参数二：查询条目数=pageSize

        //构建分页查询sql
        $sql = "select * from $this->table ";
        $sql .= "where $where ";
        $sql .= "limit $index, $pageSize ";

        //执行sql，返回结果
        return $this->pdo->dao_query($sql, true);
    }

    //获取有几个年级
    public function getGrade() {
        //构建sql
        $sql = "select grade from $this->table group by grade";

        //执行sql，返回结果
        return $this->pdo->dao_query($sql, true);
    }

    //获取列数据
    public function fetchColumn($field) {
        $sql = "select $field from $this->table";
        return $this->pdo->dao_query($sql, true);
    }
}
