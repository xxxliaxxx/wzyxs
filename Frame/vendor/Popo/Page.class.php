<?php
namespace Frame\vendor\Popo;


final class Page {

    private $rows;          //总记录数
    private $pageCount;     //总页数
    private $pageSize;      //每页显示条数
    private $currentPage;   //当前页
    private $url;           //链接地址


    public function __construct($rows, $pageSize, $currentPage, $params = array()){

        $this->rows = $rows;
        $this->pageSize = $pageSize;
        $this->pageCount = $this->setPageCount();
        $this->currentPage = $this->setCurrentPage($currentPage);
        $this->url = $this->setUrl($params);


    }

    private function setPageCount(){
        //页数不小于1
        return max(ceil($this->rows/$this->pageSize), 1);
    }

    private function setCurrentPage($currentPage){
        $currentPage = max($currentPage, 1); // 当前页不小于1
        return min($currentPage, $this->pageCount); // 当前页不大于最大页数
    }

    private function setUrl($params = array()): string{
        $arr = array();
        foreach ($params as $key=>$value) {
            $arr[] = "$key=$value";
        }
        return "?" . implode("&", $arr);
    }

    public function getAttr(): array{

        return array(
            'rows' => $this->rows,
            'pageSize' => $this->pageSize,
            'pageCount' => $this->pageCount,
            'currentPage' => $this->currentPage,
            'url' => $this->url
        );

    }
}
