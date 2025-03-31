<?php
/**
 * 商品状态更新系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 处理商品上架和下架状态的更新请求，提供Ajax接口
 * 用于实时更新商品的销售状态。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

session_start();
if (!isset($_SESSION['admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => '未登录']);
    exit;
}

require_once '../db.php';

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
    
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $response['success'] = true;
        } catch (PDOException $e) {
            $response['error'] = '更新失败';
        }
    } else {
        $response['error'] = '无效的商品ID';
    }
}

header('Content-Type: application/json');
echo json_encode($response);