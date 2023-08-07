<?php
namespace Home\controller;
use Frame\libs\BaseController;

final class VisitController extends BaseController {

    //更新访问量的静态方法
    public static function updateVis(): array{
        $file = ROOT_PATH. DIRECTORY_SEPARATOR .'vis.log';
        $fp=fopen($file,'r+');
        $content='';
        if (flock($fp,LOCK_EX)){
            while (($buffer=fgets($fp,1024))!=false){
                $content=$content.$buffer;
            }
            $data=unserialize($content);
            //设置记录键值
            $total = 'total';
            $month = date('Ym');
            $today = date('Ymd');
            $yesterday = date('Ymd',strtotime("-1 day"));
            $visLog = array();
            // 总访问增加
            $visLog[$total] = $data[$total] + 1;
            // 本月访问量增加
            $visLog[$month] = $data[$month] + 1;
            // 今日访问增加
            $visLog[$today] = $data[$today] + 1;
            //保持昨天访问
            $visLog[$yesterday] = $data[$yesterday];
            //保存统计数据
            ftruncate($fp,0); // 将文件截断到给定的长度
            rewind($fp); // 倒回文件指针的位置
            fwrite($fp, serialize($visLog));
            flock($fp,LOCK_UN);
            fclose($fp);
            //返回数据
            $total = $visLog[$total];
            $month = $visLog[$month];
            $today = $visLog[$today];
            $yesterday = $visLog[$yesterday]?$visLog[$yesterday]:0;
            return Array(
                'total' => $total,
                'month' => $month,
                'today' => $today,
                'yesterday' => $yesterday
            );
        }
        return Array();
    }

}