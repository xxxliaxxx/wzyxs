<?php
//声明命名空间
namespace Admin\model;
use Frame\libs\BaseModel;

final class PaperModel extends BaseModel{
    //受保护的表名属性
    protected string $table = "paper";

    //更新总分
    public function updateMaxScore($score, $id) {
        $sql = "update $this->table set maxScore = maxScore + $score where id = '$id'";

        $this->pdo->dao_exec($sql);
    }

}