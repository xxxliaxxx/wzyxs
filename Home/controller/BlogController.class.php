<?php
namespace Home\controller;
use Frame\libs\BaseController;
use Home\model\ArticleModel;

final class BlogController extends BaseController {

    //文章点赞方法（ajax）
    public function praise() {
        $id = htmlspecialchars($_POST['id'], ENT_QUOTES);  // 转义防注入
        //判断用户是否已经登录
        if($this->isLogin()) {
            //判断是否已经被点赞
            if(!isset($_SESSION['praise'][$id])) {
                //更改当前id状态
                $_SESSION['praise'][$id] = 1;
                //更新点赞数
                ArticleModel::getObj()->updatePraise($id);
            }
            //如果点赞不能再次点赞
        }
        else {
            echo "请先登录";
        }
        echo ArticleModel::getObj()->get("praise", "id = $id");
    }

}
