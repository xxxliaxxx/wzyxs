<?php
//声明命名空间
namespace Admin\controller;
use Admin\model\ArticleModel;
use Admin\model\UserModel;
use Frame\libs\BaseController;

final class ArticleController extends BaseController {
    public function __construct() {
        //调用父类的构造函数
        parent::__construct();

        //判断用户是否已经登录，若未登录，跳转到index登录页面
        $this->denyAccess();
    }

    //多选操作
    public function checkAll() {
        //获取post数据（checkbox）
        if(!isset($_POST['checkbox'])) {
            $this->jump(5, "?c=Index&a=article", "请选择删除的项目");
        }

        //拦截非本文章用户
        $checkbox = $_POST['checkbox'];
        foreach ($checkbox as $id) {
            $this->denyUser($id);
        }

        //获取模型，批量删除数据
        if (ArticleModel::getObj()->deleteByIds($checkbox)) {
            $this->jump(5, "?c=Index&a=article", "删除成功");
        }
        else {
            $this->jump(5, "?c=Index&a=article", "删除失败");
        }
    }

    //更新文章
    public function updateArticle() {

        //获取get id
        $id = $_GET['id'];

        //拦截非本文章用户
        $this->denyUser($id);

        //获取post数据
        $data = $this->getPost();
        if($data['category_id'] == "0"){
            $data['category_id'] = "";
        }

        //获取模型，修改数据
        if (ArticleModel::getObj()->update($data, $id)) {
            $this->jump(5, "?c=Index&a=article", "修改成功");
        }
        else{
            $this->jump(5, "?c=Index&a=article", "修改失败");
        }

    }

    //删除文章
    public function deleteArticle() {
        //获取id
        $id = $_GET['id'];


        //拦截非本文章用户
        $this->denyUser($id);

        //获取模型，删除数据
        if (ArticleModel::getObj()->delete($id)) {
            $this->jump(5, "?c=Index&a=article", "删除成功");
        }
        else{
            $this->jump(5, "?c=Index&a=article", "删除失败");
        }
    }

    //增加文章
    public function addArticle() {
        //获取post数据
        $data = $this->getPost();
        if($data['category_id'] == "0"){
            unset($data['category_id']);
        }

        //计算所需数据
        $data['user_id'] = $_SESSION['uid'];
        $data['addate'] = time();

        //获取模型存入数据
        if (ArticleModel::getObj()->add($data)) {
            $this->jump(5, "?c=Index&a=article", "添加成功");
        }
        else{
            $this->jump(5, "?c=Index&a=article", "添加失败");
        }

    }

    /**
     * @return array
     */
    private function getPost(): array
    {
        $data['title'] = htmlspecialchars($_POST['title'], ENT_QUOTES);
        $data['content'] = htmlspecialchars($_POST['content'], ENT_QUOTES);
        $data['orderby'] = htmlspecialchars($_POST['orderby'], ENT_QUOTES);
        $data['description'] = htmlspecialchars($_POST['description'], ENT_QUOTES);
        $data['category_id'] = htmlspecialchars($_POST['category'], ENT_QUOTES);
        $data['tag'] = htmlspecialchars($_POST['tag'], ENT_QUOTES);
        $data['top'] = htmlspecialchars($_POST['top'], ENT_QUOTES);
        $data['visibility'] = htmlspecialchars($_POST['visibility'], ENT_QUOTES);
        return $data;
    }

    //拦截非本文章用户
    private function denyUser($id) {
        $role = UserModel::getObj()->get("role", "id = {$_SESSION['uid']}");
        $user_id = ArticleModel::getObj()->get("user_id", "id = $id");
        if($role != 1 && $user_id != $_SESSION['uid']) {
            $this->jump(5, "?c=Index&a=article", "您没有权限操作其他人的文章");
        }
    }
}