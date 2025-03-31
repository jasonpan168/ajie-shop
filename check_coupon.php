<?php
/**
 * 优惠码验证系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 提供优惠码验证接口，处理AJAX请求验证优惠码的有效性，
 * 包括优惠码功能开关检查、优惠码存在性验证和使用限制检查。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

require_once 'db.php';

// 设置响应头为JSON
header('Content-Type: application/json');

// 获取请求参数
$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;

// 验证参数
if (empty($code)) {
    echo json_encode(['success' => false, 'message' => '请提供优惠码']);
    exit;
}

// 检查优惠码功能是否启用
try {
    $stmt = $pdo->query("SELECT * FROM system_config WHERE `key` = 'coupon_enabled' LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    $coupon_enabled = isset($config['value']) ? (bool)$config['value'] : false;
    
    if (!$coupon_enabled) {
        echo json_encode(['success' => false, 'message' => '优惠码功能未启用']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '系统错误']);
    exit;
}

// 查询优惠码
try {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coupon) {
        echo json_encode(['success' => false, 'message' => '优惠码无效或已使用']);
        exit;
    }
    
    // 优惠码有效，返回优惠信息
    $discount_amount = floatval($coupon['discount_amount']);
    
    // 如果优惠金额大于订单金额，则最多优惠到订单金额
    if ($discount_amount > $amount) {
        $discount_amount = $amount;
    }
    
    echo json_encode([
        'success' => true, 
        'data' => [
            'id' => $coupon['id'],
            'code' => $coupon['code'],
            'discount_amount' => $discount_amount
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '验证优惠码时出错：' . $e->getMessage()]);
}