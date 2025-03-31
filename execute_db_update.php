<?php
// 执行数据库更新脚本
require_once 'config.php';

try {
    // 建立数据库连接
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 读取SQL文件内容
    $sql = file_get_contents('db_update_coupon.sql');
    
    // 执行SQL语句
    $pdo->exec($sql);
    
    echo "数据库更新成功！优惠码相关表已创建。\n";
    echo "<a href='admin/coupons.php'>返回优惠码管理页面</a>";
    
} catch (PDOException $e) {
    die("数据库更新失败：" . $e->getMessage());
}
?>