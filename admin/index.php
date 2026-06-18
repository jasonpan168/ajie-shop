<?php
// 自动跳转到登录页面或仪表板
session_start();
if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
