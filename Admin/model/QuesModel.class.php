<?php
//声明命名空间
namespace Admin\model;
use Frame\libs\BaseModel;

final class QuesModel extends BaseModel {

    protected string $table = "ques";

    public function fetchByPageAndWhere($pageSize, $currentPage, $where = "2>1") {

        //limit参数一：开始索引
        $index = ($currentPage - 1) * $pageSize;
        //limit参数二：查询条目数=pageSize

        $sql = "select ques.* from $this->table ";
        $sql .= "where $where ";
        $sql .= "limit $index, $pageSize ";

        //执行sql语句，并返回结果集
        return $this->pdo->dao_query($sql, true);
    }

    //获取id在ids中的ques数据
    public function fetchByIds($ids) {

        if(empty($ids)) {
            return array();
        }

        //构建id列
        $str = "";
        foreach ($ids as $id) {
            $str .= $id . ',';
        }
        $str = rtrim($str, ',');

        if(count($ids) > 1) {
            $str = 'in (' . $str . ')';
        }
        else {
            $str = '= ' . $str;
        }

        $sql = "select * from $this->table where id $str";

        return $this->pdo->dao_query($sql, true);
    }

    //获取id不在ids中的ques数据
    public function fetchByNotInIds($ids) {

        if(empty($ids)) {
            return $this->pdo->dao_query("select * from $this->table", true);
        }

        //构建id列
        $str = "";
        foreach ($ids as $id) {
            $str .= $id . ',';
        }
        $str = rtrim($str, ',');

        if(count($ids) > 1) {
            $str = 'not in (' . $str . ')';
        }
        else {
            $str = '!= ' . $str;
        }

        $sql = "select * from $this->table where id $str";

        return $this->pdo->dao_query($sql, true);
    }

}
