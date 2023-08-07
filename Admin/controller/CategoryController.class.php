<?php
//声明命名空间
namespace Admin\controller;
use Admin\model\ArticleModel;
use Admin\model\CategoryModel;
use Frame\libs\BaseController;

final class CategoryController extends BaseController {
    public function __construct() {
        //调用父类的构造函数
        parent::__construct();

        //判断用户是否已经登录，若未登录，跳转到index登录页面
        $this->denyAccess();
    }

    //删除文章
    public function deleteArticle() {
        //获取id
        $cid = $_GET['cid'];
        $aid = $_GET['aid'];
        //获取模型，删除数据
        if (ArticleModel::getObj()->delete($aid)) {
            $this->jump(5, "?c=Index&a=viewCategory&id=$cid", "删除成功");
        }
        else{
            $this->jump(5, "?c=Index&a=viewCategory&id=$cid", "删除失败");
        }
    }

    //递归删除分类
    public function deleteCategory() {
        //获取get id
        $id = $_GET['id'];

        //获取全部分类数据
        $categories = CategoryModel::getObj()->fetchAll();

        //获取模型，删除数据
        if (CategoryModel::getObj()->recursiveDelete($id, $categories)) {
            $this->jump(5, "?c=Index&a=category", "删除成功");
        }
        else {
            $this->jump(5, "?c=Index&a=category", "删除失败");
        }
    }

    //修改分类
    public function updateCategory() {
        //获取get id
        $id = $_GET['id'];

        //获取post数据(不改pid)
        $data['name'] = $_POST['name'];
        $data['alias'] = $_POST['alias'];
        $data['orderby'] = $_POST['orderby'];
        $data['keywords'] = $_POST['keywords'];
        $data['description'] = $_POST['description'];

        //获取模型，修改数据
        if (CategoryModel::getObj()->update($data, $id)) {
            $this->jump(5, "?c=Index&a=category", "修改成功");
        }
        else {
            $this->jump(5, "?c=Index&a=category", "修改失败");
        }
    }

    //添加分类
    public function addCategory() {

        //获取post数据
        $data['name'] = $_POST['name'];
        $data['alias'] = $_POST['alias'];
        $data['orderby'] = $_POST['orderby'];
        $data['pid'] = $_POST['pid'];
        $data['keywords'] = $_POST['keywords'];
        $data['description'] = $_POST['description'];

        //获取模型，添加数据
        if (CategoryModel::getObj()->add($data)) {
            $this->jump(5, "?c=Index&a=category", "添加成功");
        }
        else {
            $this->jump(5, "?c=Index&a=category", "添加失败");
        }
    }
}
