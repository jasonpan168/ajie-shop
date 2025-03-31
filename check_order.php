<?php
/**
 * 订单状态查询系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 提供订单状态查询接口，通过订单号查询订单的当前状态，
 * 返回JSON格式的订单状态信息，用于前端实时更新订单状态。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

require_once 'db.php';
header('Content-Type: application/json');
$order_no = isset($_GET['order_no']) ? $_GET['order_no'] : '';
$status = 'pending';
if ($order_no) {
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE order_no = ?");
    $stmt->execute([$order_no]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($order) {
        $status = $order['status'];
    }
}
echo json_encode(['status' => $status]);
?>