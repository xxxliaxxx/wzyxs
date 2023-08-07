<?php
//声明命名空间
namespace Admin\controller;
use Admin\model\FlinkModel;
use Frame\libs\BaseController;

final class FlinkController extends BaseController {

    public function __construct() {
        //调用父类的构造函数
        parent::__construct();

        //判断用户是否已经登录，若未登录，跳转到index登录页面
        $this->denyAccess();
    }

    //多选操作方法
    public function checkAll() {
        //获取post数据（checkbox）
        if(!isset($_POST['checkbox'])) {
            $this->jump(1, "?c=Index&a=flink", "请选择删除的项目");
        }

        $checkbox = $_POST['checkbox'];


        //获取模型，批量删除数据
        if (FlinkModel::getObj()->deleteByIds($checkbox)) {
            $this->jump(5, "?c=Index&a=flink", "删除成功");
        }
        else {
            $this->jump(5, "?c=Index&a=flink", "删除失败");
        }


    }

    //更新友链方法
    public function updateFlink() {
        //获取post数据
        $data['name'] = $_POST['name'];
        $data['domain'] = $_POST['domain'];
        $data['url'] = $_POST['url'];
        $data['orderby'] = $_POST['orderby'];
        $data['description'] = $_POST['description'];
        $data['target'] = $_POST['target'];
        $data['rel'] = $_POST['rel'];

        //获取get id
        $id = $_GET['id'];

        //获取模型，修改
        if (FlinkModel::getObj()->update($data, $id)) {
            $this->jump(5, "?c=Index&a=flink", "修改成功");
        }
        else {
            $this->jump(5, "?c=Index&a=flink", "修改失败");
        }

    }


    //删除友链方法
    public function deleteFlink() {

        //获取要删除的友链的id
        $id = $_GET['id'];

        //删除并判断是否删除成功
        if(FlinkModel::getObj()->delete($id)) {
            $this->jump(1, "?c=Index&a=flink", "删除成功");
        }
        else {
            $this->jump(1, "?c=Index&a=flink", "删除失败");
        }
    }

    //增添数据方法
    public function addFlink() {

        //接收post数据
        $data['name'] = $_POST['name'];
        $data['domain'] = $_POST['domain'];
        $data['url'] = $_POST['url'];
        $data['orderby'] = $_POST['orderby'];
        $data['description'] = $_POST['description'];
        $data['target'] = $_POST['target'];
        $data['rel'] = $_POST['rel'];

        //获取模型，添加数据
        if (FlinkModel::getObj()->add($data)) {
            $this->jump(5, "?c=Index&a=flink", "添加成功");
        }
        else {
            $this->jump(5, "?c=Index&a=flink", "添加失败");
        }

    }


}