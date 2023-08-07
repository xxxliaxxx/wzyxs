<?php
namespace Home\model;
use Frame\libs\BaseModel;

final class ArticleModel extends BaseModel {

    protected string $table = "article";

    public function fetchAllWithJoin($pageSize, $currentPage, $where = "2>1") {

        //limit参数一：开始索引
        $index = ($currentPage - 1) * $pageSize;
        //limit参数二：查询条目数=pageSize

        //构建联合查询的sql语句
        $sql = "select article.*, category.name category_name, user.username user_name, user.pic user_pic from $this->table article ";
        $sql .= "left join category on article.category_id = category.id ";
        $sql .= "left join user on article.user_id = user.id ";
        $sql .= "where $where ";
        $sql .= "limit $index, $pageSize ";

        //执行sql语句，并返回结果集
        return $this->pdo->dao_query($sql, true);
    }

    //获取推荐阅读列表
    public function fetchRecommends() {

        $sql = "select id, title, praise, addate from $this->table ";
        $sql .= "order by praise desc limit 5";

        return $this->pdo->dao_query($sql, true);
    }

    //更新点赞数
    public function updatePraise($id) {
        $sql = "update $this->table set praise = praise + 1 where id = '$id'";
        return $this->pdo->dao_exec($sql);
    }

    //获取个人有多少文章
    public function getArticleRows($user_id) {
        $sql = "select count(*) rows from $this->table where user_id = '$user_id'";

        return $this->pdo->dao_query($sql, false)['rows'];
    }

}