<?php
namespace Home\controller;
use Frame\libs\BaseController;

final class RaceController extends BaseController {

    public function __construct() {
        //调用父类的构造函数
        parent::__construct();

        //访问拦截
        $this->denyAccessIndex();
    }

    //对数组进行转义
    private function chars(&$arr) {

        foreach ($arr as &$item) {
            //将数组递归
            if(is_array($item)) {
                $this->chars($item);
            }
            else {
                $item = htmlspecialchars($item);
            }

        }

    }


    //保存用户答案
    public function saveAns() {

        $paper_id = htmlspecialchars($_POST['paper_id'], ENT_QUOTES);
        unset($_POST['paper_id']);

        //对数据进行转义
        $this->chars($_POST);

        $filename = ROOT_PATH. DS .'records' . DS . 'ans' . DS . 'ans' . $paper_id . '.json'; // 指定要操作的json文件路径
        $lockfile = ROOT_PATH. DS .'records' . DS . 'ans' . DS . 'ans' . $paper_id . '.lock'; // 指定文件锁文件路径
        $data = array(); // 用于存储json文件中读取的数据


        // 获取文件锁，避免多进程写入时产生冲突
        try {
            $fp = fopen($lockfile, "w+");
            if(flock($fp, LOCK_EX)) {
                // 独占式锁定文件，开始读取json文件内容
                if(file_exists($filename)) {
                    // 如果json文件存在，则读取其内容并解析为数组
                    $content = file_get_contents($filename);
                    $data = json_decode($content, true);
                } else {
                    // 如果json文件不存在，则先创建一个空文件
                    file_put_contents($filename, "{}");
                }

                // 修改读取到的数据
                $data[$_SESSION['username']] = $_POST;

                // 将修改后的数据重新编码为json格式，并写回文件中
                $content = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); // JSON_PRETTY_PRINT参数表示格式化输出
                file_put_contents($filename, $content, LOCK_EX);

                // 释放文件锁
                flock($fp, LOCK_UN);
            }
            fclose($fp);

        }
        catch (\Error|\Exception $e) {
            $this->jump(5, "?c=Index&a=race", "保存失败");
        }
        finally {
            unlink($lockfile);
        }

        $this->jump(5, "?c=Index&a=race", "保存成功");
    }

}
