<?php
/**
 * rainbow_notify.php
 * 彩虹易支付异步回调通知示例
 * 
 * 注意：
 * - 本示例假设易支付回调参数通过 GET 方式传递；
 *   如为 POST，请将 $_GET 修改为 $_POST。
 * - 请确保你的数据库连接文件 db.php 可用，并已建立订单表 orders，其中 order_no 为订单号字段。
 */

// 引入数据库连接文件
require_once 'db.php';

// ----------------------
// 配置参数（请替换为你的实际信息）
// ----------------------
$merchant_id = "1000";  // 彩虹易支付商户号（示例）
$merchant_key = "4CPvm60127WPjiXMp7j9ZV72U9X9zZ6W";  // 彩虹易支付密钥（明文）

// ----------------------
// 获取回调参数（假设为 GET 方式，如为 POST 则替换 $_GET 为 $_POST）
// ----------------------
$data = $_GET;

// 写入日志（可选），便于调试（确保有写权限）
$logFile = 'rainbow_notify.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Received data:\n" . print_r($data, true) . "\n", FILE_APPEND);

// 检查必要参数
if (empty($data['sign']) || empty($data['out_trade_no']) || empty($data['trade_status'])) {
    file_put_contents($logFile, "缺少必要参数\n", FILE_APPEND);
    exit("fail");
}

// 提取收到的签名，并从参数中移除
$received_sign = $data['sign'];
unset($data['sign']);

// ----------------------
// 生成本地签名
// 签名规则：
// 1. 将所有参数按字母排序后拼接为字符串，格式：key=value，用 & 连接；
// 2. 去掉末尾 & 后，在字符串末尾追加商户密钥；
// 3. 对整个字符串进行 MD5 加密，生成小写签名。
ksort($data);
$signStr = "";
foreach ($data as $key => $value) {
    if ($value !== "") {
        $signStr .= "$key=$value&";
    }
}
$signStr = rtrim($signStr, "&");
$signStr .= $merchant_key;
$calculated_sign = md5($signStr);

// 将签名对比信息写入日志
file_put_contents($logFile, "签名字符串: $signStr\n计算签名: $calculated_sign\n接收到的签名: $received_sign\n", FILE_APPEND);

// ----------------------
// 验证签名和交易状态
// ----------------------
if ($calculated_sign == $received_sign && $data['trade_status'] == 'TRADE_SUCCESS') {
    $order_no = $data['out_trade_no'];
    // 更新订单状态为 'paid'，假设订单表 orders 中 order_no 为唯一标识
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'paid' WHERE order_no = ?");
        $stmt->execute([$order_no]);
        file_put_contents($logFile, "订单 $order_no 更新为 paid\n", FILE_APPEND);
    } catch (Exception $ex) {
        file_put_contents($logFile, "数据库更新错误: " . $ex->getMessage() . "\n", FILE_APPEND);
        exit("fail");
    }
    echo "success";  // 返回 success 通知易支付回调成功
} else {
    file_put_contents($logFile, "签名验证失败或交易状态不正确\n", FILE_APPEND);
    echo "fail";
}
exit;
?>