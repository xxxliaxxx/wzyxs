<?php
//声明命名空间
namespace Admin\model;
use Frame\libs\BaseModel;

final class SRModel extends BaseModel{
    //受保护的表名属性
    protected string $table = "score_records";

    //重写添加方法
    public function add($data) {
        //构建字段列表和值列表
        $fields = "";
        $values = "";
        foreach ($data as $k => $v) {
            $fields .= $k . ',';
            if($v === "") {
                $values .= "null" . ',';
            }
            else {
                $values .= "'" . $v . "'" . ',';
            }

        }
        $fields = rtrim($fields, ',');
        $values = rtrim($values, ',');

        //构建添加一行数据的sql语句
        $sql = "insert into $this->table($fields) values($values) ";
        $sql .= "on duplicate key update ";
        $sql .= "username = values(username), ";
        $sql .= "paper_id = values(paper_id), ";
        $sql .= "score = values(score) ";

        //执行并返回结果
        return $this->pdo->dao_exec($sql);
    }

    //获取参与的考试数目
    public function getExamRows($username) {
        $sql = "select count(*) rows from $this->table where username='$username' group by username";

        return $this->pdo->dao_query($sql, false)['rows'];
    }

    //获取个人的最高排名
    public function getMaxRank($username) {
        $sql = "select paper_id from $this->table group by paper_id";

        $tmp = $this->pdo->dao_query($sql, true);
        $minr = 10000000;
        foreach ($tmp as $item) {
            $paper_id = $item['paper_id'];

            $sql2 = "select * from $this->table where paper_id = $paper_id order by score desc";

            $rank = $this->pdo->dao_query($sql2, true);
            $r = 0;
            foreach ($rank as $person) {
                $r += 1;
                if ($person['username'] == $username) {
                    break;
                }
            }

            $minr = min($minr, $r);
        }
        return $minr;
    }
}