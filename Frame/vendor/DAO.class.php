<?php
namespace Frame\vendor;
use PDO;        //在子命名空间中使用pdo应先导入pdo的命名空间
use PDOException;       //同理
class DAO{

    private $pdo;
    private $fetch_mode;

    //错误报告
    private function PDOExceptionError(PDOException $e){
        echo '数据库连接错误：' . '<br/>';
        echo '错误文件为：' . $e->getFile() . '<br/>';
        echo '错误行号为：' . $e->getLine() . '<br/>';
        echo '错误信息为：' . $e->getMessage() . '<br/>';
    }

    //初始化DAO对象
    public function __construct($drivers = array()){
        $type = $GLOBALS['config']['db_type'];
        $host = $GLOBALS['config']['db_host'];
        $port = $GLOBALS['config']['db_port'];
        $user = $GLOBALS['config']['db_user'];
        $pass = $GLOBALS['config']['db_pass'];
        $name = $GLOBALS['config']['db_name'];
        $charset = $GLOBALS['config']['db_charset'];
        $this->fetch_mode = $GLOBALS['config']['fetch_mode'];
        $drivers[PDO::ERRMODE_EXCEPTION] = $GLOBALS['config']['attr_errmode'];

        //实例化pdo对象
        try{
            $this->pdo = @new PDO($type . ':port=' . $port . ';host=' . $host . ';dbname=' . $name,$user,$pass,$drivers);
        }catch (PDOException $e){
            $this->PDOExceptionError($e);
            die();
        }
        try{
            @$this->pdo->exec("set names $charset");
        }catch(PDOException $e){
            $this->PDOExceptionError($e);
        }
    }

    //写操作
    public function dao_exec($sql){
        try{
            return $this->pdo->exec($sql);
        }catch (PDOException $e){
            $this->PDOExceptionError($e);
            die();
        }
    }

    //读操作
    public function dao_query($sql, bool $ifAll){
        try{
            $stmt=$this->pdo->query($sql);
            if($ifAll){
                return $stmt->fetchAll($this->fetch_mode);
            }
            return $stmt->fetch($this->fetch_mode);
        }catch(PDOException $e){
            $this->PDOExceptionError($e);
            die();
        }
    }
}