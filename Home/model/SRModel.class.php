<?php
//声明命名空间
namespace Home\model;
use Frame\libs\BaseModel;

final class SRModel extends BaseModel {
    //受保护的表名属性
    protected string $table = "score_records";

    //获取已生成排行的试卷id
    public function fetchPaper() {
        $sql = "select paper_id from $this->table group by paper_id";

        return $this->pdo->dao_query($sql, true);
    }

    //获取排行信息
    public function getRank($paper_id) {
        $sql = "select r.username, r.score, u.pic, u.signature from $this->table r, user u ";
        $sql .= "where 
            r.username = u.username and 
            r.paper_id = '$paper_id' order by r.score desc";

        return $this->pdo->dao_query($sql, true);
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