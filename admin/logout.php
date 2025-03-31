<?php
/**
 * 管理员退出登录脚本
 * 
 * 该文件用于处理管理员退出登录操作，清除会话数据并重定向到登录页面
 * 
 * @author   Trae
 * @contact  contact@yewu.laikr.com
 * @date     2024-03-29
 */
session_start();
session_destroy();
header("Location: login.php");
exit;
?>