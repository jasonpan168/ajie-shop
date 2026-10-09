<?php
// 检查是否已安装
if (!file_exists(__DIR__ . '/install.lock')) {
    header('Location: install/');
    exit;
}

require_once __DIR__.'/db.php';
require_once __DIR__.'/storefront/theme.php';
$theme=sf_theme($pdo);
$products=$pdo->query('SELECT * FROM products WHERE status = 1 ORDER BY sort_order ASC')->fetchAll(PDO::FETCH_ASSOC);
$menus=$pdo->query('SELECT * FROM menus ORDER BY sort_order ASC')->fetchAll(PDO::FETCH_ASSOC);
require __DIR__.'/storefront/home.php';
