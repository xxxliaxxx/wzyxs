<?php
//声明命名空间
namespace Frame;

//定义最终框架类
final class frame{

    public static function run(){
        self::initCharset();//初始化字符集设置
        self::initConfig();//初始化配置文件
        self::initRoute();//初始化路由参数
        self::initConst();//初始化常量目录设置
        self::initAutoLoad();//初始化类的自动加载
        self::initDispatch();//初始化请求分发
    }

    //私有的静态的字符集设置
    private static function initCharset(){
        header("Content-type:text/html;charset=utf-8");
        //开启SESSION会话
        session_start();
    }

    //私有的静态的配置文件设置
    private static function initConfig(){
        $GLOBALS['config'] = require_once(APP_PATH . DS . "conf" . DS . "config.php");
    }

    //私有的静态的路由参数
    private static function initRoute(){
        $p = $GLOBALS['config']['default_platform'];                    //平台参数
        $c = $_GET['c'] ?? $GLOBALS['config']['default_controller'];    //控制器参数
        $a = $_GET['a'] ?? $GLOBALS['config']['default_action'];        //动作参数
        define('PLAT',$p);
        define('CONTROLLER',$c);
        define('ACTION',$a);
    }

    //私有的静态的目录常量设置
    private static function initConst(){
        //例如：./Home/view/student/add.html
        define('VIEW_PATH', APP_PATH . DS . 'view');    //视图目录
        define('FRAME_PATH',ROOT_PATH . DS . 'Frame');  //Frame目录
    }

    //私有的静态的类的自动加载
    private static function initAutoLoad(){
        //自动加载空间中的类，例如：$obj=new \Home\controller\studentController();
        //应转换为：./Home/controller/studentController.class.php()
        spl_autoload_register(function ($className){
            $filepath = ROOT_PATH . DS . str_replace("\\",DS,$className) . '.class.php';
            if(file_exists($filepath)) require_once($filepath);
        });
    }

    //私有的静态的请求分发
    private static function initDispatch(){
        //防止用户进入不存在的控制器或方法
//        try {
//            //构建控制器类名
//            $className = "\\" . PLAT . "\\" . 'controller' . "\\" . CONTROLLER . 'Controller';
//            //创建控制器对象
//            $controllerObj = new $className();
//            //使用控制器对象方法
//            $actionName = ACTION;
//
//            $controllerObj->$actionName();
//        }catch (\Error $e) {
//            echo "你要去哪？？";
//        }
        //构建控制器类名
        $className = "\\" . PLAT . "\\" . 'controller' . "\\" . CONTROLLER . 'Controller';

        //创建控制器对象
        $controllerObj = new $className();

        //使用控制器对象方法
        $actionName = ACTION;
        $controllerObj->$actionName();


    }
}