<?php
namespace Home\model;
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

    //获取社长
    public function fetchLeaders() {
        $sql = "select * from $this->table where role = 2 order by grade desc";
        return $this->pdo->dao_query($sql, true);
    }

}
