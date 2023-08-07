<?php
//声明命名空间
namespace Admin\controller;
use Admin\model\ArticleModel;
use Admin\model\CategoryModel;
use Admin\model\FlinkModel;
use Admin\model\LoginModel;
use Admin\model\MemberModel;
use Admin\model\NoticeModel;
use Admin\model\PaperModel;
use Admin\model\PQModel;
use Admin\model\QuesModel;
use Admin\model\SRModel;
use Admin\model\UserModel;
use Frame\libs\BaseController;
use Frame\vendor\Popo\Page;

//创建后台index控制器
final class IndexController extends BaseController{

    public function __construct() {
        //调用父类的构造函数
        parent::__construct();

        //判断用户是否已经登录，若未登录，跳转到index登录页面
        $this->denyAccess();
        //获取通用头部展示数据
        $this->commonAssign();
    }

    //通用展示数据分配方法
    private function commonAssign() {
        //从登录日志模型获取展示的数据
        $login_logs = LoginModel::getObj()->fetchAllById($_SESSION['uid']);

        //数据分配
        $this->smartyObj->assign("login_logs", $login_logs);
    }

    //拦截游客用户
    private function denyVisitor() {
        $role = UserModel::getObj()->get("role", "id = {$_SESSION['uid']}");
        if($role == 2) {
            $this->smartyObj->display("deny.html");
            die();
        }
    }

    //批量增加用户
    public function addUsers() {
        //游客拦截
        $this->denyVisitor();

        //展示页面
        $this->smartyObj->display("user/add-users.html");
    }

    //个人主页
    public function personal() {

        //获取数据
        $user = UserModel::getObj()->fetchInfo("id='{$_SESSION['uid']}'");

        //计算时间差
        $diffSeconds = time() - $user['addate'];

        $minutes = floor($diffSeconds / 60) % 60;
        $hours = floor($diffSeconds / (60 * 60)) % 24;
        $days = floor($diffSeconds / (60 * 60 * 24)) % 30;
        $months = floor($diffSeconds / (60 * 60 * 24 * 30)) % 12;
        $years = floor($diffSeconds / (60 * 60 * 24 * 30 * 12));
        $timeStr = $years . '年' . $months . '月' . $days . '日' . $hours . '小时' . $minutes . '分';

        //计算个人文章数据
        $articleRows = ArticleModel::getObj()->getArticleRows($_SESSION['uid']);
        //计算参与考试的数目
        $examRows = SRModel::getObj()->getExamRows($user['username']);
        //最高排名
        $maxRank = SRModel::getObj()->getMaxRank($user['username']);

        //分配数据
        $this->smartyObj->assign(array(
            "user" => $user,
            "time" => $timeStr,
            "articleRows" => $articleRows,
            "examRows" => $examRows,
            "maxRank" => $maxRank
        ));

        //展示页面
        $this->smartyObj->display("personal/personal.html");
    }

    //主观题打分
    public function scoring() {
        //游客拦截
        $this->denyVisitor();

        $paper_id = htmlspecialchars($_GET['id'], ENT_QUOTES);

        //获取已保存的主观题
        $filename = ROOT_PATH. DS .'records' . DS . 'ans' . DS . 'ans' . $paper_id . '.json';

        $data = array(); // 用于存储json文件中读取的数据
        if(file_exists($filename)) {
            $content = file_get_contents($filename);
            $data = json_decode($content, true);

            //只保留简答题
            foreach ($data as &$ques) {
                foreach ($ques as $key => &$value) {
                    if(explode("_", $key)[1] != '4') {
                        unset($ques[$key]);
                    }
                    else {
                        $ques_id = explode("_", $key)[2];
                        $quesDetail = QuesModel::getObj()->fetchOne("id = '$ques_id'");
                        $ques[$key] = array(
                            'ans' => $value,
                            'content' => $quesDetail['content'],
                            'score' => $quesDetail['score']
                        );
                    }
                }
            }


        }
        else {
            $this->jump(5, "?c=Index&a=paper", "暂无提交记录");
        }

        //获取回显数据
        $scoring = $this->echoScoring($paper_id);

        //分配数据
        $this->smartyObj->assign("data", $data);
        $this->smartyObj->assign("paper_id", $paper_id);
        $this->smartyObj->assign("scoring", $scoring);

        //展示页面
        $this->smartyObj->display("race/scoring.html");
    }

    //回显打分
    private function echoScoring($paper_id) {
        $filename = ROOT_PATH. DS .'records' . DS . 'ans' . DS . 'scoring' . $paper_id . '.json'; // 指定要操作的json文件路径

        $data = array(); // 用于存储json文件中读取的数据
        if(file_exists($filename)) {
            // 如果json文件存在，则读取其内容并解析为数组
            $content = file_get_contents($filename);
            $data = json_decode($content, true);
        }
        return $data;
    }

    //展示设置考题页面
    public function setPaper() {
        //拦截游客
        $this->denyVisitor();

        //获取paper id
        $id = htmlspecialchars($_GET['id'], ENT_QUOTES);
        if($id == "")
            $this->jump(5, "?c=Index&a=paper", "请选择试卷"); //如果没填id

        //获取paper数据
        $paper = PaperModel::getObj()->fetchOne("id = '$id'");
        if(empty($paper))
            $this->jump(5, "?c=Index&a=paper", "没有找到该试卷"); //如过乱填id

        //获取该paper对应哪些ques
        $ques_column = PQModel::getObj()->fetchQuesesByPaperId($id);
        $ques_ids = [];
        foreach ($ques_column as $row) {
            $ques_ids[] = $row['ques_id'];
        }
        $queses = QuesModel::getObj()->fetchByIds($ques_ids); // 属于试卷的题
        $quesesNotSelected = QuesModel::getObj()->fetchByNotInIds($ques_ids); //不属于试卷的题

        //分配数据
        $this->smartyObj->assign(array(
            "paper" => $paper,
            "queses" => $queses,
            "quesesNotSelected" => $quesesNotSelected
        ));

        //展示页面
        $this->smartyObj->display("race/set-paper.html");
    }

    //展示修改题目页面
    public function echoQues() {
        //拦截游客
        $this->denyVisitor();

        //获取数据
        $id = htmlspecialchars($_GET['id'], ENT_QUOTES);
        $papers = PaperModel::getObj()->fetchAll();
        $ques = QuesModel::getObj()->fetchOne("id = '$id'");

        //分配数据
        $this->smartyObj->assign("papers", $papers);
        $this->smartyObj->assign("ques", $ques);

        //展示页面
        $this->smartyObj->display("race/update-ques.html");
    }

    //展示添加题目页面
    public function addQues() {

        //拦截游客
        $this->denyVisitor();

        //获取试卷数据
        $papers = PaperModel::getObj()->fetchAll();

        //分配数据
        $this->smartyObj->assign("papers", $papers);

        //展示页面
        $this->smartyObj->display("race/add-ques.html");
    }

    //展示题库页面
    public function ques() {

        //拦截游客
        $this->denyVisitor();

        //获取数据

        //构建查询参数
        $where = "2>1";

        //搜索查询参数
        $_REQUEST['search'] = htmlspecialchars($_REQUEST['search'], ENT_QUOTES); // 防注入转义
        $_REQUEST['selectType'] = htmlspecialchars($_REQUEST['selectType'], ENT_QUOTES); // 防注入转义

        if(!empty($_REQUEST['search'])) // 搜索参数
            $where .= " and content like " . "'%" . $_REQUEST['search'] . "%'";

        if(!empty($_REQUEST['selectType']) && $_REQUEST['selectType'] !== '0') // 分类参数
            $where .= " and type = " . $_REQUEST['selectType'];


        //构建分页参数
        $rows = QuesModel::getObj()->rowCount($where); // 得出总记录条数
        $currentPage = $_GET['currentPage'] ?? 1; // 当前页，默认为1
        $pageSize = $_GET['pageSize'] ?? 5; // 当前页展示条目数
        $params = array(
            'c' => CONTROLLER,
            'a' => ACTION,
        );

        if(!empty($_REQUEST['search']))
            $params['search'] = $_REQUEST['search'];
        if(!empty($_REQUEST['selectType']))
            $params['selectType'] = $_REQUEST['selectType'];

        $pageObj = new Page($rows, $pageSize, $currentPage, $params);
        $page = $pageObj->getAttr();

        //根据分页获取题目数据
        $data = QuesModel::getObj()->fetchByPageAndWhere($page['pageSize'], $page['currentPage'], $where);

            //将题目所属的试卷变为以逗号分隔的字符串
        foreach ($data as &$ques) {
            $tmp = [];
            foreach (PQModel::getObj()->fetchPapersByQuesId($ques['id']) as $row) {
                $tmp[] = $row['paper_title'];
            }
            $ques['paper_title'] = implode(",", $tmp);
        }

        //分配数据
        @$this->smartyObj->assign(Array(
            "page" => $page,
            "rows" => $rows,
            "data" => $data,
            "search" => $_REQUEST['search'],
            "selectType" => $_REQUEST['selectType'],
            "selectPaper" => $_REQUEST['selectPaper']
        ));

        //展示页面
        $this->smartyObj->display("race/ques.html");
    }

    //展示修改试卷页面
    public function echoPaper() {
        //拦截游客
        $this->denyVisitor();

        //获取数据
        $id = htmlspecialchars($_GET['id'], ENT_QUOTES);
        $paper = PaperModel::getObj()->fetchOne("id = '$id'");

        $timestamp = $paper['startTime'];
        $paper['year'] = date('Y', $timestamp + 0);
        $paper['month'] = date('m', $timestamp + 0); // 获取月份
        $paper['day'] = date('d', $timestamp + 0); // 获取日期
        $paper['hour'] = date('H', $timestamp + 0); // 获取小时
        $paper['min'] = date('i', $timestamp + 0); // 获取分钟

        //分配数据
        $this->smartyObj->assign("paper", $paper);

        //展示页面
        $this->smartyObj->display("race/update-paper.html");
    }

    //展示增加试卷页面
    public function addPaper() {
        //拦截游客
        $this->denyVisitor();

        //展示页面
        $this->smartyObj->display("race/add-paper.html");
    }

    //展示新生赛页面
    public function paper() {
        //拦截游客
        $this->denyVisitor();

        //获取数据
        $papers = PaperModel::getObj()->fetchAll();
        foreach ($papers as &$paper) {
            $paper['startTime'] = date("Y-m-d H:i:s", $paper['startTime'] + 0);
        }

        //分配数据
        $this->smartyObj->assign("papers", $papers);

        //展示页面
        $this->smartyObj->display("race/paper.html");
    }


    //展示增加成员页面
    public function addMember() {
        //展示页面
        $this->smartyObj->display("member/add-member.html");
    }

    //展示修改成员页面
    public function echoMember() {
        //获取数据
        $id = $_GET['id'];
        $member = MemberModel::getObj()->fetchOne("id = $id");

        //分配数据
        $this->smartyObj->assign("member", $member);

        //展示页面
        $this->smartyObj->display("member/update-member.html");
    }

    //展示成员页面
    public function member() {
        //构建查询参数
        $where = "2>1";

        if(!empty($_REQUEST['select'])){
            $where .= " and grade = '{$_REQUEST['select']}'";
        }


        //构建分页参数
        $rows = MemberModel::getObj()->rowCount($where); // 得出总记录条数

        $currentPage = $_GET['currentPage'] ?? 1; // 当前页，默认为1
        $pageSize = $_GET['pageSize'] ?? 5; // 当前页展示条目数

        $params = array(
            'c' => CONTROLLER,
            'a' => ACTION,
        );

        if(!empty($_REQUEST['select']))
            $params['select'] = $_REQUEST['select'];

        $pageObj = new Page($rows, $pageSize, $currentPage, $params);
        $page = $pageObj->getAttr();

        //根据参数获取日志数据
        $members = MemberModel::getObj()->fetchAllByPage($page['pageSize'], $page['currentPage'], $where);

        //获取有几种年级
        $grades = MemberModel::getObj()->getGrade();

        //分配数据
        $this->smartyObj->assign("members", $members);
        $this->smartyObj->assign("grades", $grades);
        $this->smartyObj->assign("page", $page);
        $this->smartyObj->assign("rows", $rows);
        $this->smartyObj->assign("select", $_REQUEST['select']);

        //展示页面
        $this->smartyObj->display("member/member.html");
    }

    //展示修改公告页面
    public function echoNotice() {
        //获取数据
        $id = $_GET['id'];
        $notice = NoticeModel::getObj()->fetchOne("id = $id");

        //分配数据
        $this->smartyObj->assign("notice", $notice);

        //展示页面
        $this->smartyObj->display("notice/update-notice.html");
    }

    //展示添加公告页面
    public function addNotice() {
        //展示页面
        $this->smartyObj->display("notice/add-notice.html");
    }

    //展示公告页面
    public function notice() {
        //获取数据
        $notices = NoticeModel::getObj()->fetchAll();
        //分配数据
        $this->smartyObj->assign("notices", $notices);
        //展示页面
        $this->smartyObj->display("notice/notice.html");
    }

    //展示修改文章页面，并回显数据
    public function echoArticle() {
        //获取数据
        $id = $_GET['id'];
        $article = ArticleModel::getObj()->fetchOne("id = $id");
        $categoryModelObj = CategoryModel::getObj(); // 获取分类数据
        $categories = $categoryModelObj->fetchAll(); // 获取原始分类数据
        $categories = $categoryModelObj->categoryList($categories); // 加工原始数据为无限级分类

        //分配数据
        $this->smartyObj->assign("article", $article);
        $this->smartyObj->assign("categories", $categories);

        //展示页面
        $this->smartyObj->display("article/update-article.html");
    }


    //展示增加文章页面
    public function addArticle() {
        //获取数据
        $categoryModelObj = CategoryModel::getObj();
        $categories = $categoryModelObj->fetchAll(); // 获取原始分类数据
        $categories = $categoryModelObj->categoryList($categories); //加工原始数据为无限级分类

        //分配数据
        $this->smartyObj->assign("categories", $categories);


        //展示页面
        $this->smartyObj->display("article/add-article.html");
    }

    //展示文章页面
    public function article() {
        //获取数据

        //获取分类数据
        $categories = CategoryModel::getObj()->fetchAll();
        $categories = CategoryModel::getObj()->categoryList($categories); //加工原始数据为无限极分类

        //获取用户数据
        $users = UserModel::getObj()->fetchIdAndName();


        //构建查询参数
        $where = "2>1";
            //除了超级管理员（1）可以查看所有文章，其他用户只能看到自己的文章
        $role = UserModel::getObj()->get("role", "id = {$_SESSION['uid']}");
        if($role != 1) {
            $where .= " and user_id=" . $_SESSION['uid'];
        }
            //搜索查询参数
        $_REQUEST['search'] = htmlspecialchars($_REQUEST['search'], ENT_QUOTES); // 防注入转义
        $_REQUEST['selectCategory'] = htmlspecialchars($_REQUEST['selectCategory'], ENT_QUOTES); // 防注入转义

        if(!empty($_REQUEST['search'])) // 搜索参数
            $where .= " and article.title like " . "'%" . $_REQUEST['search'] . "%'";

        if(!empty($_REQUEST['selectCategory'])) // 分类参数
            $where .= " and category_id = " . $_REQUEST['selectCategory'];
        else if($_REQUEST['selectCategory'] == '0')
            $where .= " and category_id is null";

        if(!empty($_REQUEST['selectUser'])) // 用户参数
            $where .= " and user_id = " . $_REQUEST['selectUser'];


        //构建分页参数
        $rows = ArticleModel::getObj()->rowCount($where); // 得出总记录条数
        $currentPage = $_GET['currentPage'] ?? 1; // 当前页，默认为1
        $pageSize = $_GET['pageSize'] ?? 5; // 当前页展示条目数
        $params = array(
            'c' => CONTROLLER,
            'a' => ACTION,
        );

        if(!empty($_REQUEST['search']))
            $params['search'] = $_REQUEST['search'];
        if(!empty($_REQUEST['selectCategory']))
            $params['selectCategory'] = $_REQUEST['selectCategory'];
        if(!empty($_REQUEST['selectUser']))
            $params['selectUser'] = $_REQUEST['selectUser'];

        $pageObj = new Page($rows, $pageSize, $currentPage, $params);
        $page = $pageObj->getAttr();

        //根据分页获取文章数据
        $data = ArticleModel::getObj()->fetchAllWithJoin($page['pageSize'], $page['currentPage'], $where);

        //分配数据

        @$this->smartyObj->assign(Array(
            "page" => $page,
            "rows" => $rows,
            "data" => $data,
            "categories" => $categories,
            "users"  => $users,
            "search" => $_REQUEST['search'],
            "selectCategory" => $_REQUEST['selectCategory'],
            "selectUser" => $_REQUEST['selectUser'],
            "role" => $role
        ));

        //展示页面
        $this->smartyObj->display("article/article.html");
    }

    //展示查看分类页面
    public function viewCategory() {

        //获取分类数据：当前分类、当前分类的父分类，所有分类（无限级）
        $id = $_GET['id'];
        $categoryModelObj =CategoryModel::getObj();

        $category = $categoryModelObj->fetchOne("id = $id"); //当前分类

        $pcategory = $categoryModelObj->fetchOne("id = " . $category['pid']); //当前分类的父分类

        $categories = $categoryModelObj->fetchAll(); //所有分类
        $categories = $categoryModelObj->categoryList($categories, 0, $id); //加工原始数据为以当前节点为根节点的无限级分类

        $cRows = count($categories);

        $categories = $categoryModelObj->getArticleCount($categories); //添加每个分类的文章数


        //获取当前分类下的文章数据
        $articles = ArticleModel::getObj()->fetchAllByCId($id);
        $aRows = count($articles);

        //分配数据
        $this->smartyObj->assign("category", $category);
        if(!empty($pcategory)) {
            $this->smartyObj->assign("pcategory", $pcategory);
        }
        $this->smartyObj->assign("categories", $categories);
        $this->smartyObj->assign("cRows", $cRows);
        $this->smartyObj->assign("articles", $articles);
        $this->smartyObj->assign("aRows", $aRows);

        //展示页面
        $this->smartyObj->display("category/view-category.html");
    }

    //展示修改分类页面，回显数据
    public function echoCategory() {
        //获取要更新的id
        $id = $_GET['id'];
        //取得所有已有分类数据
        $category = CategoryModel::getObj()->fetchOne("id = $id");

        $categoryModelObj = CategoryModel::getObj();
        $categories = $categoryModelObj->fetchAll();
        $categories = $categoryModelObj->categoryList($categories); //加工原始数据为无限级分类

        //分配数据
        $this->smartyObj->assign("category", $category);
        $this->smartyObj->assign("categories", $categories);
        $this->smartyObj->assign("id", $id);

        //展示页面
        $this->smartyObj->display("category/update-category.html");
    }

    //展示分类页面
    public function category() {

        //数据获取
        //获取原始分类数据
        $CategoryModelObj = CategoryModel::getObj();
        $categories = $CategoryModelObj->fetchAll();
        //加工原始数据为无限极分类
        $categories = $CategoryModelObj->categoryList($categories);
        //获取文章数
        $categories = $CategoryModelObj->getArticleCount($categories);

        $rows = CategoryModel::getObj()->rowCount();

        //分配数据
        $this->smartyObj->assign("rows", $rows);
        $this->smartyObj->assign("categories", $categories);

        //展示页面
        $this->smartyObj->display("category/category.html");
    }

    //展示修改友链的页面，并回显(echo)数据
    public function echoFlink() {

        //获取要更新的id
        $id = $_GET['id'];

        //通过id获取原始数据
        $flink = FlinkModel::getObj()->fetchOne("id = $id");

        //分配数据
        $this->smartyObj->assign("flink", $flink);
        $this->smartyObj->assign("id", $id);

        //展示页面
        $this->smartyObj->display("flink/update-flink.html");

    }

    //展示添加友情链接页面
    public function addFlink() {

        //展示页面
        $this->smartyObj->display("flink/add-flink.html");

    }

    //展示友情链接页面
    public function flink() {

        //从友链模型获取友链数据
        $flinks = FlinkModel::getObj()->fetchAll();
        $rows = FlinkModel::getObj()->rowCount();

        //分配数据
        $this->smartyObj->assign("flinks", $flinks);
        $this->smartyObj->assign("rows", $rows);

        //展示页面
        $this->smartyObj->display("flink/flink.html");

    }

    //展示用户登录日志页面
    public function loginlog() {
        //拦截游客
        $this->denyVisitor();

        //构建查询参数
        $where = "2>1";

        if(!empty($_REQUEST['select'])){
            $where .= " and username = '{$_REQUEST['select']}'";
        }


        //构建分页参数
        $rows = LoginModel::getObj()->rowCount($where); // 得出总记录条数

        $currentPage = $_GET['currentPage'] ?? 1; // 当前页，默认为1
        $pageSize = $_GET['pageSize'] ?? 5; // 当前页展示条目数

        $params = array(
            'c' => CONTROLLER,
            'a' => ACTION,
        );

        if(!empty($_REQUEST['select']))
            $params['select'] = $_REQUEST['select'];

        $pageObj = new Page($rows, $pageSize, $currentPage, $params);
        $page = $pageObj->getAttr();

        //根据参数获取日志数据
        $loginlogs = LoginModel::getObj()->fetchAllByPage($page['pageSize'], $page['currentPage'], $where);

        //获取用户数据
        $users = LoginModel::getObj()->getUser();

        //分配数据
        $this->smartyObj->assign("loginlogs", $loginlogs);
        $this->smartyObj->assign("page", $page);
        $this->smartyObj->assign("users", $users);
        $this->smartyObj->assign("rows", $rows);
        $this->smartyObj->assign("select", $_REQUEST['select']);

        //展示页面
        $this->smartyObj->display("user/loginlog.html");
    }

    //展示用户列表页面
    public function user(){
        //拦截游客
        $this->denyVisitor();

        //从用户模型获取展示的数据
        $userModelObj = UserModel::getObj();
        $users = $userModelObj->fetchAll(); //获取所有用户信息
        $rows = $userModelObj->rowCount(); //获取用户数

        //数据分配
        $this->smartyObj->assign("users", $users);
        $this->smartyObj->assign("rows", $rows);

        //展示manage-user页面
        $this->smartyObj->display('user/manage-user.html');
    }



    //定义展示主页面的控制器
    public function main(){
        //拦截游客
        $this->denyVisitor();

        //获取文章数
        $articleRows = ArticleModel::getObj()->rowCount();
        //获取友链数
        $flinkRows = FlinkModel::getObj()->rowCount();
        //获取用户人数
        $userRows = UserModel::getObj()->rowCount();

        //取得后台页面所需信息，除了使用smarty保留字的
        $data['php_version'] = PHP_VERSION;
        $data['php_os'] = PHP_OS;

        //分配后台信息
        $this->smartyObj->assign('data', $data);
        $this->smartyObj->assign('articleRows', $articleRows);
        $this->smartyObj->assign('flinkRows', $flinkRows);
        $this->smartyObj->assign('userRows', $userRows);

        //展示后台页面
        $this->smartyObj->display('user/main.html');
    }

}