<?php
//声明命名空间
namespace Home\model;
use Frame\libs\BaseModel;

final class PaperModel extends BaseModel{
    //受保护的表名属性
    protected string $table = "paper";

    //获取所有id列
    public function fetchId() {
        $sql = "select id from $this->table";
        return $this->pdo->dao_query($sql, true);
    }

}