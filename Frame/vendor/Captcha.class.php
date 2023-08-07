<?php
namespace Frame\vendor;

final class Captcha {
    private string $code;      // 验证码字符串
    private $codelen;   // 验证码长度
    private $width;     // 图像宽度
    private $height;    // 图像高度
    private $img;       // 图像资源
    private $fontSize;  // 字号大小
    private string $fontfile;  // 字号大小

    public function __construct($codelen = 4, $width = 120, $height = 50, $fontSize = 20) {

        $this->codelen = $codelen;
        $this->width = $width;
        $this->height = $height;
        $this->fontSize = $fontSize;
        $this->fontfile = ROOT_PATH. DS . "Public" . DS ."admin" . DS ."fonts" . DS ."Elephant.ttf";
        $this->code = $this->createCode();
        $_SESSION['captcha'] = $this->code;
        $this->img = $this->createImg();
        $this->createBg(); // 给画布添加背景颜色
        $this->createLine(); // 生成线条、雪花
        $this->createText(); // 写入字符串
        $this->output(); // 输出图像

    }

    private function createCode(): string {
        //产生随机字符串数组
        $arr_str = array_merge(range('a', 'z'), range(0, 9), range('A', 'Z'));
        //打乱数组
        shuffle($arr_str);
        shuffle($arr_str);
        //从数组中随机取下标
        $arr_index = array_rand($arr_str, $this->codelen);
        //循环下标数组，构建随机字符串
        $str = "";
        foreach ($arr_index as $i) {
            $str .= $arr_str[$i];
        }

        return $str;

    }

    private function createImg() {
        return imagecreatetruecolor($this->width, $this->height);
    }

    private function createBg() {
        //给矩形分配颜色
        $color = imagecolorallocate($this->img, mt_rand(157,255), mt_rand(157,255), mt_rand(157,255));
        //绘制带背景的矩形
        imagefilledrectangle($this->img, 0, 0, $this->width, $this->height, $color);
    }

    private function createText() {
        $_x = $this->width / $this->codelen;
        for ($i=0;$i<$this->codelen;$i++) {
            $color = imagecolorallocate($this->img,mt_rand(0,156),mt_rand(0,156),mt_rand(0,156));
            imagettftext($this->img,$this->fontSize,mt_rand(-30,30),$_x*$i+mt_rand(1,5),$this->height / 1.4,$color,$this->fontfile,$this->code[$i]);
        }
    }

    private function createLine() {
        //线条
        for ($i=0;$i<6;$i++) {
            $color = imagecolorallocate($this->img,mt_rand(0,156),mt_rand(0,156),mt_rand(0,156));
            imageline($this->img,mt_rand(0,$this->width),mt_rand(0,$this->height),mt_rand(0,$this->width),mt_rand(0,$this->height),$color);
        }
        //雪花
        for ($i=0;$i<100;$i++) {
            $color = imagecolorallocate($this->img,mt_rand(200,255),mt_rand(200,255),mt_rand(200,255));
            imagestring($this->img,mt_rand(1,5),mt_rand(0,$this->width),mt_rand(0,$this->height),'*',$color);
        }
    }

    private function output() {
        header("content-type: image/png");
        //输出图像资源
        imagepng($this->img);
        //销毁图像资源
        imagedestroy($this->img);
    }


}