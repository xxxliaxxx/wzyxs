<?php
//定义常量
define("DS",DIRECTORY_SEPARATOR);
define("ROOT_PATH",getcwd());
define("APP_PATH",ROOT_PATH . DS . 'Admin');

//包含框架初始类文件
include_once(ROOT_PATH . DS . "Frame" . DS . "frame.class.php");
//框架初始化
\Frame\frame::run();