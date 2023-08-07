<?php
namespace Home\model;
use Frame\libs\BaseModel;

final class NoticeModel extends BaseModel {

    protected string $table = "notice";

    public function fetchFirst() {
        $sql = "select * from $this->table order by orderby";
        return $this->pdo->dao_query($sql, false);
    }

}