<?php
require_once '../db.php';

try {
    // 读取SQL文件内容
    $sql = file_get_contents(__DIR__ . '/create_system_settings.sql');
    
    // 执行SQL语句
    $pdo->exec($sql);
    
    echo "数据库更新成功：system_settings表已创建。\n";
} catch (PDOException $e) {
    echo "数据库更新失败：" . $e->getMessage() . "\n";
    exit(1);
}