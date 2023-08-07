<?php
//声明命名空间
namespace Home\model;
use Frame\libs\BaseModel;

final class LoginModel extends BaseModel{
    //受保护的表名属性
    protected string $table = "login_logs";

}