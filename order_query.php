<?php
/**
 * 订单查询系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 提供订单查询接口，返回订单详细信息，
 * 包括订单状态、金额、商品信息等数据。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

require_once 'db.php';
header('Content-Type: application/json');

$order_no = isset($_GET['order_no']) ? trim($_GET['order_no']) : '';
if (!$order_no) {
    echo json_encode(['error' => '订单号不能为空']);
    exit;
}

// 直接从 orders 表中查询订单数据，包括 nickname、email、quantity、product_title 等字段
$stmt = $pdo->prepare("
    SELECT order_no, amount, status, created_at, nickname, email, quantity, product_title 
    FROM orders 
    WHERE order_no = ?
");
$stmt->execute([$order_no]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['error' => '未找到该订单']);
    exit;
}

// 将订单状态从英文转换为中文显示
$statusMap = [
    'pending'   => '待支付',
    'paid'      => '已支付',
    'cancelled' => '已取消'
];
$order['status'] = isset($statusMap[$order['status']]) ? $statusMap[$order['status']] : $order['status'];

echo json_encode($order);
?>