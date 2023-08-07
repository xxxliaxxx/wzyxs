<?php
namespace Home\controller;
use Frame\libs\BaseController;
use Frame\vendor\Popo\Page;
use Home\model\ArticleModel;
use Home\model\CategoryModel;
use Home\model\FlinkModel;
use Home\model\MemberModel;
use Home\model\NoticeModel;
use Home\model\PaperModel;
use Home\model\PQModel;
use Home\model\QuesModel;
use Home\model\SRModel;
use Home\model\UserModel;

final class IndexController extends BaseController {

    public function __construct(){
        //调用父类的构造函数
        parent::__construct();

        //统一分配数据
        $this->commonAssign();
    }

    //统一分配数据
    private function commonAssign() {
        //访问次数统计
        $visLog = VisitController::updateVis();
        $this->smartyObj->assign("visLog", $visLog);

        //是否登录判断
        //若已经登录，则传递true
        $this->smartyObj->assign("isLogin", $this->isLogin());
    }

    //展示任何人的个人页面
    public function personal() {
        $user_id = htmlspecialchars($_GET['user_id'], ENT_QUOTES);

        $user = UserModel::getObj()->fetchOne("id = '$user_id'");

        if (empty($user)) {
            $this->jump(5, "?c=Index&a=rank", "没有找到该用户");
        }

        //计算个人文章数据
        $articleRows = ArticleModel::getObj()->getArticleRows($user_id);
        //计算参与考试的数目
        $examRows = SRModel::getObj()->getExamRows($user['username']);
        //最高排名
        $maxRank = SRModel::getObj()->getMaxRank($user['username']);

        //分配数据
        $this->smartyObj->assign(array(
            "user" => $user,
            "articleRows" => $articleRows,
            "examRows" => $examRows,
            "maxRank" => $maxRank
        ));

        //展示页面
        $this->smartyObj->display("personal.html");
    }

    //展示动态排行页面
    public function rank_dynamic() {
        $paper_id = htmlspecialchars($_GET['id'], ENT_QUOTES);

        //获取排名信息
        $ranks = SRModel::getObj()->getRank($paper_id);

        //获取id
        foreach ($ranks as &$person) {
            $person['user_id'] = UserModel::getObj()->get("id", "username = '{$person['username']}'");
        }

        //生成排名
        $rank = 0;
        $flag = -1;
        foreach ($ranks as &$person) {
            if ($flag != $person['score']) {
                $rank += 1;
            }
            $person['rank'] = $rank;
            $flag = $person['score'];
        }


        //获取试卷信息
        $paper = PaperModel::getObj()->fetchOne("id = '$paper_id'");

        //分配数据
        $this->smartyObj->assign("ranks", $ranks);
        $this->smartyObj->assign("paper", $paper);


        //展示动态排行页面
        $this->smartyObj->display("race/rank_dynamic.html");
    }

    //展示排行页面
    public function rank() {
        //获取有报告的考试id
        $paperIds = SRModel::getObj()->fetchPaper();


        //获取有报告的考试名
        $papers = [];
        foreach ($paperIds as $paperId) {
            $papers[] = array(
                "id" => $paperId["paper_id"],
                "title" => PaperModel::getObj()->get("title", "id = '{$paperId["paper_id"]}'")
            );
        }

        //分配数据
        $this->smartyObj->assign("papers", $papers);

        //展示页面
        $this->smartyObj->display("race/rank.html");
    }

    //展示个人报告页面
    public function report() {
        //访问拦截
        $this->denyAccessIndex();

        //获取数据
        $id = htmlspecialchars($_GET['id'], ENT_QUOTES); //paper id
        if($id == "") {
            $this->jump(5, "?c=Index&a=race", "请选择试卷"); // 不选id
        }
        $paper = PaperModel::getObj()->fetchOne("id = '$id'");
        if(empty($paper) || !$paper['isDisplay']) {
            $this->jump(5, "?c=Index&a=race", "没有找到该试卷"); // 乱选id 或 考试没上线
        }
        //考试中不允许查看
        if (time() >= $paper['startTime'] && time() <= $paper['startTime'] + $paper['deadline'] * 60 * 60) {
            $this->jump(5, "?c=Index&a=race", "考试ing，无法查看报告");
        }

        //获取paper对应的考题
        $ques_column = PQModel::getObj()->fetchQuesesByPaperId($id);
        $ques_ids = [];
        foreach ($ques_column as $row) {
            $ques_ids[] = $row['ques_id'];
        }
        $queses = QuesModel::getObj()->fetchByIds($ques_ids);

        //将选项格式化 x 配置题目总体信息
        $quesInfo = [];
        for ($i = 1; $i <=4; $i++) {
            $quesInfo[$i]['count'] = $quesInfo[$i]['score'] = 0;
        }
        foreach ($queses as &$ques) {
            if($ques['type'] == 1 || $ques['type'] == 2) {
                $ques['answer'] = explode("*", $ques['answer']);
            }

            $quesInfo[$ques['type']]['count']++;
            $quesInfo[$ques['type']]['score'] += $ques['score'];
        }

        //获取用户报告数据
        $filename = ROOT_PATH. DS .'records' . DS . 'report' . DS . 'report' . $id . '.json'; // 指定要操作的json文件路径
        $userInfo = array(); // 用于存储json文件中读取的数据
        if(file_exists($filename)) {
            // 如果json文件存在，则读取其内容并解析为数组
            $content = file_get_contents($filename);
            $userInfo = json_decode($content, true)[$_SESSION['username']]; // 只取当前用户的数据

            //换键
            $keys =  array_keys($userInfo['ques']);
            $newArr = [];
            foreach ($keys as $key) {
                $newKey = explode("_", $key)[2];
                $newArr[$newKey] = $userInfo['ques'][$key];
            }
            $userInfo['ques'] = $newArr;
        }
        else {
            $this->jump(5, "", "报告暂未生成...");
        }


        //分配数据
        $this->smartyObj->assign("userInfo", $userInfo);
        $this->smartyObj->assign("paper", $paper);
        $this->smartyObj->assign("queses", $queses);
        $this->smartyObj->assign("quesInfo", $quesInfo);

        //展示页面
        $this->smartyObj->display("race/report.html");
    }

    //展示试卷页面
    public function paper() {

        //访问拦截
        $this->denyAccessIndex();

        //获取数据
        $id = htmlspecialchars($_GET['id'], ENT_QUOTES); //paper id
        if($id == "") {
            $this->jump(5, "?c=Index&a=race", "请选择试卷"); // 不选id
        }
        $paper = PaperModel::getObj()->fetchOne("id = '$id'");
        if(empty($paper) || !$paper['isDisplay']) {
            $this->jump(5, "?c=Index&a=race", "没有找到该试卷"); // 乱选id 或 考试没上线
        }

        $this->smartyObj->assign("paper", $paper);

        //判断考试时间是否开始
        $startTime = $paper['startTime'];
        $deadline = $paper['deadline'];

        if(time() < $startTime) { // 未到考试时间
            $this->smartyObj->assign(array(
                "status" => 1,
                "msg" => "未到考试时间"
            ));
            $this->smartyObj->display("race/paper-intro.html");
        }
        else if (time() > $startTime + $deadline * 60 * 60) { // 超过考试时间
            $this->smartyObj->assign(array(
                "status" => 2,
                "msg"=> "考试已结束"
            ));
            $this->smartyObj->display("race/paper-intro.html");
        }
        else { // 考试ing
            //获取paper对应的考题
            $ques_column = PQModel::getObj()->fetchQuesesByPaperId($id);
            $ques_ids = [];
            foreach ($ques_column as $row) {
                $ques_ids[] = $row['ques_id'];
            }
            $queses = QuesModel::getObj()->fetchByIds($ques_ids);

            //将选项格式化 x 配置题目总体信息
            $quesInfo = [];
            for ($i = 1; $i <=4; $i++) {
                $quesInfo[$i]['count'] = $quesInfo[$i]['score'] = 0;
            }
            foreach ($queses as &$ques) {
                if($ques['type'] == 1 || $ques['type'] == 2) {
                    $ques['answer'] = explode("*", $ques['answer']);
                }

                $quesInfo[$ques['type']]['count']++;
                $quesInfo[$ques['type']]['score'] += $ques['score'];
            }

            //将用户保存的数据回显
            $userAns = $this->echoPaper($paper['id']);

            //分配数据
            $this->smartyObj->assign("queses", $queses);
            $this->smartyObj->assign("quesInfo", $quesInfo);
            $this->smartyObj->assign("userAns", $userAns);

            //展示页面
            $this->smartyObj->display("race/paper.html");
        }
    }

    //回显用户试卷
    private function echoPaper($paper_id): array{
        $filename = ROOT_PATH. DS .'records' . DS . 'ans' . DS . 'ans' . $paper_id . '.json'; // 指定要操作的json文件路径

        $data = array(); // 用于存储json文件中读取的数据
        if(file_exists($filename)) {
            // 如果json文件存在，则读取其内容并解析为数组
            $content = file_get_contents($filename);
            $data = json_decode($content, true);
        }
        $data = $data[$_SESSION['username']];
        $newData = [];
        if($data) {
            foreach ($data as $key => &$d) {
                $newData[explode("_", $key)[2]] = $d;
            }
        }

        return $newData;
    }

    //展示新生赛页面
    public function race() {

        //获取数据
        $papers = PaperModel::getObj()->fetchAll();

        foreach ($papers as $key => $paper) {
            if(!$paper['isDisplay']) {
                unset($papers[$key]);
            }
        }


        //分配数据
        $this->smartyObj->assign("papers", $papers);

        //展示页面
        $this->smartyObj->display("race/race.html");
    }

    //展示成员页面
    public function member() {
        //获取数据
        $members = MemberModel::getObj()->fetchAll();
        $leaders = MemberModel::getObj()->fetchLeaders();

        //分配数据
        $this->smartyObj->assign("members", $members);
        $this->smartyObj->assign("leaders", $leaders);

        //展示页面
        $this->smartyObj->display("member.html");
    }

    //展示成员页面
    public function medal() {

        //展示页面
        $this->smartyObj->display("medal.html");
    }

    //展示成员页面
    public function contact() {

        //展示页面
        $this->smartyObj->display("contact.html");
    }

    //展示文章页面
    public function article() {

        //获取数据
        $id = $_GET['id'];
        if(!$id) {
            $this->jump(5, "?c=Index&a=blog", "请选择文章"); //不给id参数
        }

        //获取文章
        $article = ArticleModel::getObj()->fetchOne("id = $id");
        if(empty($article)) {
            $this->jump(5, "?c=Index&a=blog", "没有找到该文章奥"); //乱给id参数
        }
        //获取作者
        $user = UserModel::getObj()->getUser("id = {$article['user_id']}");
        //获取推荐阅读列表
        $recommends = ArticleModel::getObj()->fetchRecommends();


        //分配数据
        $this->smartyObj->assign("article", $article);
        $this->smartyObj->assign("user", $user);
        $this->smartyObj->assign("recommends", $recommends);
        if(!empty($article['category_id'])) {
            $this->smartyObj->assign("category", CategoryModel::getObj()->getCategory("id = {$article['category_id']}"));
        }

        //展示页面
        $this->smartyObj->display("blog/article.html");

    }

    //展示博客页面
    public function blog() {
        //获取数据

        //获取友链
        $flinks = FlinkModel::getObj()->fetchAll();

        //获取分类
        //获取原始分类数据
        $CategoryModelObj = CategoryModel::getObj();
        $categories = $CategoryModelObj->fetchAll();
        //加工原始数据为无限极分类
        $categories = $CategoryModelObj->categoryList($categories);

        //获取文章
        //构建查询参数
        $where = "2>1";
        $_REQUEST['search'] = htmlspecialchars($_REQUEST['search'], ENT_QUOTES); // 防注入转义
        $_REQUEST['category_id'] = htmlspecialchars($_REQUEST['category_id'], ENT_QUOTES); // 防注入转义
        if(!empty($_REQUEST['search']))
            $where .= " and article.title like " . "'%" . $_REQUEST['search'] . "%'";
        if(!empty($_REQUEST['category_id']))
            $where .= " and article.category_id = " . $_REQUEST['category_id'];

        //构建分页参数
        $articleRows = ArticleModel::getObj()->rowCount($where); // 得出总记录条数
        $currentPage = $_GET['currentPage'] ?? 1; // 当前页，默认为1
        $pageSize = $_GET['pageSize'] ?? 3; // 当前页展示条目数
        $params = array(
            'c' => CONTROLLER,
            'a' => ACTION,
        );

        if(!empty($_REQUEST['search']))
            $params['search'] = $_REQUEST['search'];
        if(!empty($_REQUEST['category_id']))
            $params['category_id'] = $_REQUEST['category_id'];

        $pageObj = new Page($articleRows, $pageSize, $currentPage, $params);
        $page = $pageObj->getAttr();

        //根据分页获取文章数据
        $articles = ArticleModel::getObj()->fetchAllWithJoin($page['pageSize'], $page['currentPage'], $where);

        //分配数据
        $this->smartyObj->assign(array(
            "flinks" => $flinks,
            "articles" => $articles,
            "search" => $_REQUEST['search'],
            "category_id" => $_REQUEST['category_id'],
            "articleRows" => $articleRows,
            "categories" => $categories,
            "page" => $page
        ));

        //展示页面
        $this->smartyObj->display("blog/blog.html");
    }

    //展示首页
    public function index() {

        //获取数据
        $notice = $this->decodeData(NoticeModel::getObj()->fetchFirst());

        //分配数据
        $this->smartyObj->assign("notice", $notice);
        $ip = $_SERVER['SERVER_NAME'];
        $this->smartyObj->assign("ip", $ip);

        //展示页面
        $this->smartyObj->display("index.html");

    }

}
