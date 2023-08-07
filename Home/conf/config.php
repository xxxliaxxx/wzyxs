<?php
//前端配置数组
return array(
    //数据库配置
    'db_type'       => 'mysql',       //数据库
    'db_host'       => 'localhost',   //主机
    'db_port'       => '3306',        //端口
    'db_user'       => 'lia',        //用户
    'db_pass'       => 'lia20031103', //密码
    'db_name'       => 'blog',    //数据库名
    'db_charset'    => 'utf8',        //字符集
    'fetch_mode'    => PDO::FETCH_ASSOC,            //设置数据库返回数据形式
    'attr_errmode'  => PDO::ERRMODE_EXCEPTION,      //设置数据库报错格式
    'ifAll'         => true,                        //读取数据库表是否读取全部

    //前端默认URL路由数据
    'default_platform'      => 'Home',
    'default_controller'    => 'Index',
    'default_action'        => 'index'
);