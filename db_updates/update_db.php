<?php
// 数据库升级脚本只允许在服务器命令行执行：php db_updates/xxx.php
// 放在网站目录里时，任何人访问 URL 就会执行并改库（例如把 WxPusher 配置重置为空）。
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../db.php';

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