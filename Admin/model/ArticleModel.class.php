<?php
namespace Admin\model;
use Frame\libs\BaseModel;

final class ArticleModel extends BaseModel {

    protected string $table = "article";

    public function fetchAllWithJoin($pageSize, $currentPage, $where = "2>1") {

        //limit参数一：开始索引
        $index = ($currentPage - 1) * $pageSize;
        //limit参数二：查询条目数=pageSize

        //构建联合查询的sql语句
        $sql = "select article.*, category.name category_name, user.username user_name from $this->table article ";
        $sql .= "left join category on article.category_id = category.id ";
        $sql .= "left join user on article.user_id = user.id ";
        $sql .= "where $where ";
        $sql .= "limit $index, $pageSize ";

        //执行sql语句，并返回结果集
        return $this->pdo->dao_query($sql, true);
    }

    //获取指定分类下的所有文章
    public function fetchAllByCId($cid) {
        //构建sql
        $sql = "select a.*, c.name category_name from $this->table a ";
        $sql .= "left join category c on a.category_id = c.id ";
        $sql .= "where category_id = $cid";

        //执行sql，返回数据
        return $this->pdo->dao_query($sql, true);
    }

    //获取个人有多少文章
    public function getArticleRows($user_id) {
        $sql = "select count(*) rows from $this->table where user_id = '$user_id'";

        return $this->pdo->dao_query($sql, false)['rows'];
    }


}