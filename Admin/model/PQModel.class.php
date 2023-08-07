<?php
//声明命名空间
namespace Admin\model;
use Frame\libs\BaseModel;

final class PQModel extends BaseModel{
    //受保护的表名属性
    protected string $table = "paper_ques";

    public function fetchPapersByQuesId($id) {
        $sql = "select paper_title from $this->table where ques_id = '$id'";

        return $this->pdo->dao_query($sql, true);
    }

    public function fetchQuesesByPaperId($id) {
        $sql = "select ques_id from $this->table where paper_id = '$id'";

        return $this->pdo->dao_query($sql, true);
    }


    public function deleteByWhere($where = 2>1) {
        $sql = "delete from $this->table where $where";

        return $this->pdo->dao_exec($sql);
    }

}