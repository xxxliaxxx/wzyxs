<?php
namespace Admin\controller;
use Admin\model\PaperModel;
use Admin\model\PQModel;
use Admin\model\QuesModel;
use Admin\model\SRModel;
use Admin\model\UserModel;
use Frame\libs\BaseController;

final class RaceController extends BaseController {
    public function __construct() {
        //调用父类的构造函数
        parent::__construct();

        //判断用户是否已经登录，若未登录，跳转到index登录页面
        $this->denyAccess();
    }

    //生成排名
    public function rank() {
        $id = htmlspecialchars($_GET['id'], ENT_QUOTES);

        //生成排名数据

        $reportFilename = ROOT_PATH. DS .'records' . DS . 'report' . DS . 'report' . $id . '.json';

        $data = [];
        if(file_exists($reportFilename)) {
            // 如果json文件存在，则读取其内容并解析为数组
            $data = json_decode(file_get_contents($reportFilename), true); // 用户答案
        }
        else {
            $this->jump(5, "?c=Index&a=paper", "未找到相关报告");
        }

        //转存报告
        $insertDatas = [];
        foreach ($data as $username => $record) {
            $insertDatas[] = array(
                "username" => $username,
                "paper_id" => $id,
                "score" => $record["score"]
            );
        }

        //将数组存入成绩记录表，若已存在该条数据则更新，若不存在则删除

        foreach ($insertDatas as $insertData) {
            SRModel::getObj()->add($insertData);
        }

        $this->jump(5, "?c=Index&a=paper", "生成成功");
    }

    //重置考试
    public function resetPaper() {
        $id = htmlspecialchars($_GET['id'], ENT_QUOTES);
        $files['scoringFilename'] = ROOT_PATH. DS .'records' . DS . 'ans' . DS . 'scoring' . $id . '.json';
        $files['ansFilename'] = ROOT_PATH. DS .'records' . DS . 'ans' . DS . 'ans' . $id . '.json';
        $files['reportFilename'] = ROOT_PATH. DS .'records' . DS . 'report' . DS . 'report' . $id . '.json';

        try {
            foreach ($files as $file) {
                if(file_exists($file)) {
                    unlink($file);
                }
            }
        }catch (\Error $e) {
            $this->jump(5, "?c=Index&a=paper", "重置失败");
        }
        $this->jump(5, "?c=Index&a=paper", "重置成功");
    }

    //主观题打分结算
    public function scoring() {
        $paper_id = htmlspecialchars($_POST['id'], ENT_QUOTES);

        $filename = ROOT_PATH. DS .'records' . DS . 'ans' . DS . 'scoring' . $paper_id . '.json';
        $lockfile = ROOT_PATH. DS .'records' . DS . 'ans' . DS . 'scoring' . $paper_id . '.lock';

        try {
            $fp = fopen($lockfile, "w+");
            if(flock($fp, LOCK_EX)) {
                // 独占式锁定文件，开始读取json文件内容
                $data = array(); // 用于存储json文件中读取的数据
                if(file_exists($filename)) {
                    // 如果json文件存在，则读取其内容并解析为数组
                    $content = file_get_contents($filename);
                    $data = json_decode($content, true); // 用户答案
                }
                else {
                    file_put_contents($filename, "{}");
                }

                // 修改读取到的数据
                $username = $_POST['username'];
                $type = $_POST['type'];
                $score = $_POST['score'];
                $data[$username][$type] = $score;

                // 将修改后的数据重新编码为json格式，并写回文件中
                $content = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); // JSON_PRETTY_PRINT参数表示格式化输出
                file_put_contents($filename, $content, LOCK_EX);

                // 释放文件锁
                flock($fp, LOCK_UN);
            }
            fclose($fp);

        }
        catch (\Error|\Exception $e) {
            $this->jump(5, "?c=Index&a=scoring&id=$paper_id", "保存失败");
        }
        finally {
            unlink($lockfile);
        }

        header("refresh:0;url=?c=Index&a=scoring&id=$paper_id");
    }

    //结算考试，生成报告
    public function checkAns() {
        $paper_id = htmlspecialchars($_GET['id'], ENT_QUOTES);

        $ansFilename = ROOT_PATH. DS .'records' . DS . 'ans' . DS . 'ans' . $paper_id . '.json';
        $reportFilename = ROOT_PATH. DS .'records' . DS . 'report' . DS . 'report' . $paper_id . '.json';
        $scoringFilename = ROOT_PATH. DS .'records' . DS . 'ans' . DS . 'scoring' . $paper_id . '.json';

        $data = array(); // 用于存储json文件中读取的数据
        if(file_exists($ansFilename)) {
            // 如果json文件存在，则读取其内容并解析为数组
            $content = file_get_contents($ansFilename);
            $data = json_decode($content, true); // 用户答案

            //获取试卷题目信息（内含正确答案）
            $ques_column = PQModel::getObj()->fetchQuesesByPaperId($paper_id);
            $ques_ids = [];
            foreach ($ques_column as $row) {
                $ques_ids[] = $row['ques_id'];
            }
            $queses = QuesModel::getObj()->fetchByIds($ques_ids);

            //checkAns
            $alphabet = ['A', 'B', 'C', 'D', 'E', 'F', 'G',
                        'H', 'I', 'J', 'K', 'L', 'M', 'N',
                        'O', 'P', 'Q', 'R', 'S', 'T', 'U',
                        'V', 'W', 'X', 'Y', 'Z'];
            $correctAns = [];
            foreach ($queses as $ques) {
                $key = "ques_" . $ques['type'] . "_" . $ques['id'];
                if($ques['type'] == 2 || $ques['type'] == 3) {
                    $ques['correctAnswer'] = explode("*", $ques['correctAnswer']);
                }
                $correctAns[$key]['ans'] = $ques['correctAnswer'];
                $correctAns[$key]['score'] = $ques['score'];
            }

            $report = [];
            foreach ($data as $username => $d) {
                $report[$username]['score'] = 0;
                foreach ($d as $key => $ans) {
                    $type = explode("_", $key)[1];
                    $isCorrect = 0;

                    //判断正误
                    if($type == 1) { // 单选/判断
                        $ans = $alphabet[(int)$ans - 1];
                        if($ans === $correctAns[$key]['ans']) {
                            $isCorrect = 1;
                        }
                    }
                    else if ($type == 2) { // 多选
                        foreach ($ans as &$a) {
                           $a =  $alphabet[(int)$a - 1];
                        }
                        if($ans === $correctAns[$key]['ans']) {
                            $isCorrect = 1;
                        }
                    }
                    else if($type == 3) { // 填空
                        if(in_array($ans, $correctAns[$key]['ans'])) {
                            $isCorrect = 1;
                        }
                    }
                    else { // 简答
                        $isCorrect = 1;
                        //读取打分文件
                        if(file_exists($scoringFilename)) {
                            // 如果json文件存在，则读取其内容并解析为数组
                            $scoringContent = file_get_contents($scoringFilename);
                            $scoringData = json_decode($scoringContent, true);
                            $correctAns[$key]['score'] = (int)$scoringData[$username][$key] ?? 0;
                        }
                        else {
                            $correctAns[$key]['score'] = 0;
                        }

                    }

                    //统计报告
                    $report[$username]['ques'][$key]['score'] = 0;
                    if($isCorrect) {
                        $report[$username]['score'] += $correctAns[$key]['score'];
                        $report[$username]['ques'][$key]['score'] = $correctAns[$key]['score'];
                    }
                    $report[$username]['ques'][$key]['isCorrect'] = $isCorrect;
                    $report[$username]['ques'][$key]['ans'] = $ans;
                    $report[$username]['ques'][$key]['correctAns'] = $correctAns[$key]['ans'];

                }
            }

            // 将修改后的数据重新编码为json格式，并写回文件中
            $content = json_encode($report, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); // JSON_PRETTY_PRINT参数表示格式化输出
            file_put_contents($reportFilename, $content, LOCK_EX);
            $this->jump(5, "?c=Index&a=paper", "生成成功");

        }
        else {
            $this->jump(5, "?c=Index&a=paper", "生成失败");
        }


    }

    //返回题目(ajax)
    public function returnQues() {
        $id = htmlspecialchars($_POST['id'], ENT_QUOTES);
        $ques =QuesModel::getObj()->fetchOne("id = '$id'");
        echo json_encode($ques);
    }

    //题目批量移除卷子
    public function outPaperMul() {
        $paper_id = htmlspecialchars($_GET['paper_id'], ENT_QUOTES);

        //获取post数据（checkbox）
        if(!isset($_POST['checkbox'])) {
            $this->jump(5, "?c=Index&a=setPaper&id=$paper_id", "请选择项目");
        }

        $checkbox = $_POST['checkbox'];

        //逐一添加
        foreach ($checkbox as $ques_id) {
            if (PQModel::getObj()->deleteByWhere("ques_id = '$ques_id' and paper_id = '$paper_id'") == 0) {

                $this->jump(5, "?c=Index&a=setPaper&id=$paper_id", "移除失败");
            }
            //更新总分
            $score = QuesModel::getObj()->get("score", "id = '$ques_id'");
            PaperModel::getObj()->updateMaxScore(-$score, $paper_id);
        }
        $this->jump(5, "?c=Index&a=setPaper&id=$paper_id", "移除成功");
    }

    //题目批量放入卷子
    public function inPaperMul() {
        $paper_id = htmlspecialchars($_GET['paper_id'], ENT_QUOTES);

        //获取post数据（checkbox）
        if(!isset($_POST['checkbox'])) {
            $this->jump(5, "?c=Index&a=setPaper&id=$paper_id", "请选择项目");
        }

        $checkbox = $_POST['checkbox'];

        //逐一添加
        foreach ($checkbox as $ques_id) {
            if (PQModel::getObj()->add(array(
                "ques_id" => $ques_id,
                "paper_id" => $paper_id,
                "paper_title" => PaperModel::getObj()->get("title", "id='$paper_id'")
            )) == 0) {

                $this->jump(5, "?c=Index&a=setPaper&id=$paper_id", "添加失败");
            }
            //更新总分
            $score = QuesModel::getObj()->get("score", "id = '$ques_id'");
            PaperModel::getObj()->updateMaxScore($score, $paper_id);

        }
        $this->jump(5, "?c=Index&a=setPaper&id=$paper_id", "添加成功");
    }

    //题目移出卷子
    public function outPaper() {
        $ques_id = htmlspecialchars($_GET['ques_id'], ENT_QUOTES);
        $paper_id = htmlspecialchars($_GET['paper_id'], ENT_QUOTES);
        if (PQModel::getObj()->deleteByWhere("ques_id = '$ques_id' and paper_id = '$paper_id'")) {
            header("refresh:0;url=?c=Index&a=setPaper&id=$paper_id");

            //更新总分
            $score = QuesModel::getObj()->get("score", "id = '$ques_id'");
            PaperModel::getObj()->updateMaxScore(-$score, $paper_id);
        }
        else {
            $this->jump(5, "?c=Index&a=setPaper&id=$paper_id", "移除失败");
        }
    }

    //题目放入卷子
    public function inPaper() {
        $ques_id = htmlspecialchars($_GET['ques_id'], ENT_QUOTES);
        $paper_id = htmlspecialchars($_GET['paper_id'], ENT_QUOTES);
        if (PQModel::getObj()->add(array(
            "ques_id" => $ques_id,
            "paper_id" => $paper_id,
            "paper_title" => PaperModel::getObj()->get("title", "id='$paper_id'")
        ))) {
            header("refresh:0;url=?c=Index&a=setPaper&id=$paper_id");

            //更新总分
            $score = QuesModel::getObj()->get("score", "id = '$ques_id'");
            PaperModel::getObj()->updateMaxScore($score, $paper_id);
        }
        else {
            $this->jump(5, "?c=Index&a=setPaper&id=$paper_id", "添加失败");
        }
    }

    //修改题目
    public function updateQues() {

        $id = htmlspecialchars($_GET['id'], ENT_QUOTES);
        $data = $this->getQuesPost();
        if (QuesModel::getObj()->update($data, $id)) {
            $this->jump(5, "?c=Index&a=ques", "修改成功");
        }
        else {
            $this->jump(5, "?c=Index&a=ques", "修改失败");
        }
    }

    //添加题目
    public function addQues() {
        //获取ques表数据
        $data = $this->getQuesPost();

        if (QuesModel::getObj()->add($data)) {
            $this->jump(5, "?c=Index&a=ques", "添加成功");
        }
        else {
            $this->jump(5, "?c=Index&a=ques", "添加失败");
        }



    }

    //删除题目
    public function deleteQues() {
        $id = htmlspecialchars($_GET['id'], ENT_QUOTES);
        if (QuesModel::getObj()->delete($id)) {
            $this->jump(5, "?c=Index&a=ques", "删除成功");
        }
        else {
            $this->jump(5, "?c=Index&a=ques", "删除失败");
        }
    }

    //多选操作
    public function checkAll() {
        //获取post数据（checkbox）
        if(!isset($_POST['checkbox'])) {
            $this->jump(5, "?c=Index&a=ques", "请选择删除的项目");
        }

        $checkbox = $_POST['checkbox'];

        //获取模型，批量删除数据
        if (QuesModel::getObj()->deleteByIds($checkbox)) {
            $this->jump(5, "?c=Index&a=ques", "删除成功");
        }
        else {
            $this->jump(5, "?c=Index&a=ques", "删除失败");
        }
    }

    //删除试卷
    public function deletePaper() {
        $id = htmlspecialchars($_GET['id'], ENT_QUOTES);
        if (PaperModel::getObj()->delete($id)) {
            $this->jump(5, "?c=Index&a=paper", "删除成功");
        }
        else {
            $this->jump(5, "?c=Index&a=paper", "删除失败");
        }

    }

    //修改试卷
    public function updatePaper() {

        $id = htmlspecialchars($_GET['id'], ENT_QUOTES);
        $data = $this->getPaperPost();
        if (PaperModel::getObj()->update($data, $id)) {
            $this->jump(5, "?c=Index&a=paper", "修改成功");
        }
        else {
            $this->jump(5, "?c=Index&a=paper", "修改失败");
        }
    }

    //添加试卷
    public function addPaper() {
        $data = $this->getPaperPost();

        if (PaperModel::getObj()->add($data)) {
            $this->jump(5, "?c=Index&a=paper", "添加成功");
        }
        else {
            $this->jump(5, "?c=Index&a=paper", "添加失败");
        }
    }

    private function getPaperPost():array {
        $data['title'] = htmlspecialchars($_POST['title'], ENT_QUOTES);
        $data['author'] = htmlspecialchars($_POST['author'], ENT_QUOTES);
        $data['deadline'] = htmlspecialchars($_POST['deadline'], ENT_QUOTES);

        $year = htmlspecialchars($_POST['year'], ENT_QUOTES);
        $month = htmlspecialchars($_POST['month'], ENT_QUOTES);
        $day = htmlspecialchars($_POST['day'], ENT_QUOTES);
        $hour = htmlspecialchars($_POST['hour'], ENT_QUOTES);
        $min = htmlspecialchars($_POST['min'], ENT_QUOTES);

        $data['startTime'] = strtotime("$year-$month-$day $hour:$min:00");

        $data['notice'] = htmlspecialchars($_POST['notice'], ENT_QUOTES);
        $data['isDisplay'] = htmlspecialchars($_POST['isDisplay'], ENT_QUOTES);

        return $data;
    }
    private function getQuesPost():array {
        $data['type'] = htmlspecialchars($_POST['type'], ENT_QUOTES);
        $data['content'] = htmlspecialchars($_POST['content'], ENT_QUOTES);
        $data['answer'] = htmlspecialchars($_POST['answer'], ENT_QUOTES);
        $data['correctAnswer'] = htmlspecialchars($_POST['correctAnswer'], ENT_QUOTES);
        if(!empty($_POST['analysis'])) {
            $data['analysis'] = htmlspecialchars($_POST['analysis'], ENT_QUOTES);
        }
        $data['score'] = htmlspecialchars($_POST['score'], ENT_QUOTES);

        return $data;
    }

}