<?php
//声明命名空间
namespace Frame\vendor;

//手动包含原始smarty类
require_once (FRAME_PATH . DS . 'vendor' . DS . 'Smarty' . DS . 'Smarty.class.php');

//创建自己的最终smarty类，并继承原始smarty类
final class smarty extends \Smarty {
    //该类中什么都不用写
}