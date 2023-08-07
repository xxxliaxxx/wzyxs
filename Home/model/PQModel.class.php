<?php
//声明命名空间
namespace Home\model;
use Frame\libs\BaseModel;

final class PQModel extends BaseModel{
    //受保护的表名属性
    protected string $table = "paper_ques";

    public function fetchQuesesByPaperId($id) {
        $sql = "select ques_id from $this->table where paper_id = '$id'";

        return $this->pdo->dao_query($sql, true);
    }


}