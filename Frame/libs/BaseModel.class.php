<?php
namespace Frame\libs;
use Frame\vendor\DAO;
abstract class baseModel{
    protected DAO $pdo;


    //得到一个pdo类
    public function __construct(){
        $this->pdo = new DAO();
    }

    //静态的私有的保存模型类对象的数组
    private static array $modelObjArr = array();
    //单例模式得到对象
    public static function getObj(){
        //获取静态化方式调用的类名
        $modelClassName = get_called_class();

        //判断是否已经有对象
        if(!isset(self::$modelObjArr[$modelClassName])){
            self::$modelObjArr[$modelClassName] = new $modelClassName();
        }
        //返回当前模型类
        return self::$modelObjArr[$modelClassName];
    }

    //根据id删除多个
    public function deleteByIds($ids) {
        //构建id列
        $str = "";
        foreach ($ids as $id) {
            $str .= $id . ',';
        }
        $str = rtrim($str, ',');

        if(count($ids) > 1) {
            $str = 'in (' . $str . ')';
        }
        else {
            $str = '= ' . $str;
        }

        //构建删除多条数据的sql语句
        $sql = "delete from $this->table where id $str";

        //执行sql，返回受影响的数据行数
        return $this->pdo->dao_exec($sql);
    }

    //根据id删除模型数据
    public function delete($id) {
        //构建删除模型数据的sql语句
        $sql = "delete from $this->table where id = '$id'";

        //执行并返回结果
        return $this->pdo->dao_exec($sql);
    }

    //添加一行模型数据
    public function add($data) {

        //构建字段列表和值列表
        $fields = "";
        $values = "";
        foreach ($data as $k => $v) {
            $fields .= $k . ',';
            if($v === "") {
                $values .= "null" . ',';
            }
            else {
                $values .= "'" . $v . "'" . ',';
            }

        }
        $fields = rtrim($fields, ',');
        $values = rtrim($values, ',');

        //构建添加一行数据的sql语句
        $sql = "insert into $this->table($fields) values($values)";

        //执行并返回结果
        return $this->pdo->dao_exec($sql);

    }

    //更新模型数据
    public function update($data, $id) {
        //组set键值对
        $str = "";

        foreach ($data as $key => $value) {
            if($value === ""){
                $str .= "$key=null,";
            }
            else {
                $str .= "$key='$value',";
            }

        }

        //去掉末尾逗号
        $str = rtrim($str, ",");

        //构建更新的sql语句
        $sql = "update $this->table set $str where id = '$id'";

        //执行并返回影响行数
        return $this->pdo->dao_exec($sql);
    }

    //获取单一行的一字段数据（值）
    public function get($field, $where = "2>1") {
        //构建获取值的sql语句
        $sql = "select $field from $this->table where $where";

        //执行并返回一个值
        return $this->pdo->dao_query($sql, false)[$field];
    }

    //获取一行用户数据
    public function fetchOne($where = "2>1") { //缺省值恒真
        //构建获取一行数据的sql语句
        $sql = "select * from $this->table where $where";

        //执行并返回一行结果集
        return @$this->pdo->dao_query($sql, true)[0]; // 返回的是二维数组
    }

    //获取所有用户数据（二维数组）
    public function fetchAll() {
        //构建获取所有用户数据的sql语句
        $sql = "select * from $this->table";

        //执行并返回所有用户集
        return $this->pdo->dao_query($sql, true);
    }

    //根据条件获取行数
    public function rowCount($where = "2>1") {
        //构建条件查询的sql语句
        $sql = "select count(*) as rows from $this->table where $where";

        //执行并返回符合条件的行数
        return $this->pdo->dao_query($sql, false)['rows'];

    }
}