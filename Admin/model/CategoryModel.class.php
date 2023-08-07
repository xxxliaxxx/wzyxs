<?php
namespace Admin\model;
use Frame\libs\BaseModel;
use Admin\model\ArticleModel;
final class CategoryModel extends BaseModel {

    //受保护的表名属性
    protected string $table = "category";

    //获取分类文章数
    public function getArticleCount($arrs): array {

        //静态数组，用来存储结果
        static $categories = array();

        foreach ($arrs as $arr) {
            $arr['article'] = ArticleModel::getObj()->rowCount("category_id = {$arr['id']}");
            $categories[] = $arr;

        }

        return $categories;

    }

    //无限级分类
    public function categoryList($arrs, $level = 0, $pid = 0): array{

        //静态数组，用来存储结果
        static $categories = array();

        //遍历原始数组中参数pid与原始相等的数据，进行深度搜索
        foreach ($arrs as $arr) {
            //判断是否是属于该层级的分类
            if($arr['pid'] == $pid) {

                //如果是，就将数据纳入结果集
                $arr['level'] = $level; //更新level
                $categories[] = $arr; //加入结果集


                //深度优先搜索该父节点
                $this->categoryList($arrs, $level + 1, $arr['id']); //level+1，父节点的id作为子节点的pid
            }
        }

        //返回结果集
        return $categories;
    }

    public function delete($id) {
        //禁用父函数
    }

    //递归删除
    public function recursiveDelete($id, $arrs) {

        //按id进行深度优先遍历，到达边界，回溯时进行删除
        foreach ($arrs as $arr) {
            if($arr['pid'] == $id) {
                $this->recursiveDelete($arr['id'], $arrs);
            }
        }

        //将自己删除掉
        return parent::delete($id);

    }

}
