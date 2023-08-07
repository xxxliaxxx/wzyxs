<?php
namespace Admin\controller;
use Admin\model\MemberModel;
use Frame\libs\BaseController;

final class MemberController extends BaseController {

    public function __construct() {
        //调用父类的构造函数
        parent::__construct();

        //判断用户是否已经登录，若未登录，跳转到index登录页面
        $this->denyAccess();
    }

    //清除数据库中不存在的照片，在更新或添加文件后执行
    private function clearFile($path) {
        $existFiles = array_diff(scandir($path), array('.', '..'));
        $picColumn = MemberModel::getObj()->fetchColumn("pic");
        $file = array();
        foreach ($picColumn as $pic) {
            $file[] = basename($pic['pic']);
        }

        foreach ($existFiles as $f) {
            if(!in_array($f, $file)) {
                unlink($path . '/' . $f);
            }
        }
    }

    //上传文件方法（如果有，覆盖，如果没有，则删除用户上一张传的照片）
    public function uploadFile() {
        //获取上传配置
        $maxUploadSize = $GLOBALS['config']['maxUploadSize'];
        $filepath = $GLOBALS['config']['memberFilepath'];
        $filename = time() . '_' . $_FILES["imgFile"]["name"];

        $fileUrl = $filepath . $filename;

        //响应数组
        $resp = [];
        $resp['path'] = $filepath;
        $resp['name'] = $filename;


        if ((($_FILES["imgFile"]["type"] == "image/jpg")
                || ($_FILES["imgFile"]["type"] == "image/jpeg")
                || ($_FILES["imgFile"]["type"] == "image/pjpeg")
                || ($_FILES["imgFile"]["type"] == "image/png"))
            && ($_FILES["imgFile"]["size"] < $maxUploadSize))
        {
            if ($_FILES["imgFile"]["error"] > 0)
            {
                $resp['returnCode'] = $_FILES["imgFile"]["error"];
                $resp['msg'] = "error";
            }
            else
            {

                $resp['upload'] = $_FILES["imgFile"]["name"];
                $resp['type'] = $_FILES["imgFile"]["type"];
                $resp['size'] = ($_FILES["imgFile"]["size"] / 1024) . " Kb";

                //同名文件保留最新
                if (file_exists($fileUrl))
                {
                    unlink($fileUrl);
                }

                move_uploaded_file($_FILES["imgFile"]["tmp_name"], $fileUrl);
                $resp['msg'] = "Stored in: " . $fileUrl;
            }
        }
        else
        {
            $resp['msg'] = "Invalid file";
        }

        //返回json字符串
        echo json_encode($resp);

    }

    //删除成员
    public function deleteMember() {
        $id = $_GET['id'];
        if (MemberModel::getObj()->delete($id)) {
            $this->jump(5, "?c=Index&a=member", "删除成功");
        }
        else {
            $this->jump(5, "?c=Index&a=member", "删除失败");
        }
    }

    //更新成员
    public function updateMember() {
        //获取id
        $id = $_GET['id'];
        //获取数据
        $data = $this->getPost();

        //获取模型，修改成员
        if (MemberModel::getObj()->update($data, $id)) {
            //清除数据库中没有的图片
            $this->clearFile(dirname($data['pic']));
            $this->jump(5, "?c=Index&a=member", "更新成功");
        }
        else {
            //清除数据库中没有的图片
            $this->clearFile(dirname($data['pic']));
            $this->jump(5, "?c=Index&a=member", "更新失败");
        }

    }

    //添加成员方法
    public function addMember() {
        //获取post
        $data = $this->getPost();

        //存入数据
        if (MemberModel::getObj()->add($data)) {
            //清除数据库中没有的图片
            $this->clearFile(dirname($data['pic']));
            $this->jump(5, "?c=Index&a=member", "添加成功");
        }
        else {
            //清除数据库中没有的图片
            $this->clearFile(dirname($data['pic']));
            $this->jump(5, "?c=Index&a=member", "添加失败");
        }

    }

    /**
     * @return array
     */
    public function getPost(): array{
        $data['name'] = $_POST['name'];
        $data['grade'] = $_POST['grade'];
        $data['class'] = $_POST['class'];
        $data['skill'] = $_POST['skill'];
        $data['description'] = $_POST['description'];
        $data['role'] = $_POST['role'];
        $data['pic'] = $_POST['picUrl'];
        return $data;

    }
}