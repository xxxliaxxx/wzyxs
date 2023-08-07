<?php
//声明命名空间
namespace Home\model;
use Frame\libs\BaseModel;

final class QuesModel extends BaseModel {

    protected string $table = "ques";

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

}
