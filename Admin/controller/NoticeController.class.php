<?php
//声明命名空间
namespace Admin\controller;
use Frame\libs\BaseController;
use Admin\model\NoticeModel;


final class NoticeController extends BaseController{
    public function __construct() {
        //调用父类的构造函数
        parent::__construct();

        //判断用户是否已经登录，若未登录，跳转到index登录页面
        $this->denyAccess();
    }

    public function checkAll() {
        //获取post数据（checkbox）
        if(!isset($_POST['checkbox'])) {
            $this->jump(1, "?c=Index&a=notice", "请选择删除的项目");
        }

        $checkbox = $_POST['checkbox'];

        //获取模型，批量删除数据
        if (NoticeModel::getObj()->deleteByIds($checkbox)) {
            $this->jump(5, "?c=Index&a=notice", "删除成功");
        }
        else {
            $this->jump(5, "?c=Index&a=notice", "删除失败");
        }
    }

    //删除公告
    public function deleteNotice() {
        $id = $_GET['id'];
        if (NoticeModel::getObj()->delete($id)) {
            $this->jump(5, "?c=Index&a=notice", "删除成功");
        }
        else {
            $this->jump(5, "?c=Index&a=notice", "删除失败");
        }
    }

    //更新公告
    public function updateNotice() {
        //获取id
        $id = $_GET['id'];
        //获取数据
        $data = $this->getPost();

        //获取模型，修改
        if (NoticeModel::getObj()->update($data, $id)) {
            $this->jump(5, "?c=Index&a=notice", "更新成功");
        }
        else {
            $this->jump(5, "?c=Index&a=notice", "更新失败");
        }
    }

    //添加公告
    public function addNotice() {
        //获取post数据
        $data = $this->getPost();

        //计算数据
        $data['addate'] = time();

        //获取模型，存入数据
        if (NoticeModel::getObj()->add($data)) {
            $this->jump(5, "?c=Index&a=notice", "添加成功");
        }
        else {
            $this->jump(5, "?c=Index&a=notice", "添加失败");
        }
    }

    /**
     * @return array
     */
    public function getPost(): array{
        $data['title'] = htmlspecialchars($_POST['title'], ENT_QUOTES);
        $data['content'] = htmlspecialchars($_POST['content'], ENT_QUOTES);
        $data['keywords'] = htmlspecialchars($_POST['keywords'], ENT_QUOTES);
        $data['orderby'] = htmlspecialchars($_POST['orderby'], ENT_QUOTES);
        $data['description'] = htmlspecialchars($_POST['description'], ENT_QUOTES);
        $data['visibility'] = htmlspecialchars($_POST['visibility'], ENT_QUOTES);
        return $data;
    }
}