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
// 易支付配置（后台「易支付配置」→ epay_config 表，或 EPAY_* 环境变量）
require_once 'lib/epay.config.php';

// ----------------------
// 配置参数
// ----------------------
// 商户号与密钥一律从配置读取，绝不能写死在源码里：
// 写死的密钥会随仓库公开，任何人都能伪造「支付成功」回调把订单刷成已支付。
$merchant_id  = $epay_config['pid'];
$merchant_key = $epay_config['key'];

// ----------------------
// 获取回调参数（假设为 GET 方式，如为 POST 则替换 $_GET 为 $_POST）
// ----------------------
$data = $_GET;

// 写入日志（可选），便于调试
// 日志必须落在 logs/ 目录，不能写在网站根目录（否则可被公网直接下载）。
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}
$logFile = $logDir . '/rainbow_notify.log';
// 原始回调参数含买家信息与签名，生产默认不记录，排错时设 PAY_LOG_RAW=1 打开
$logRawCallback = (getenv('PAY_LOG_RAW') === '1');
if ($logRawCallback) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Received data:\n" . print_r($data, true) . "\n", FILE_APPEND);
} else {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Received callback\n", FILE_APPEND);
}

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

// 将签名对比结果写入日志
// 注意：$signStr 末尾拼接了商户密钥，绝不能整串写进日志，否则等于把密钥写到磁盘上。
file_put_contents($logFile, "签名校验: " . ($calculated_sign === $received_sign ? "通过" : "不通过") . "\n", FILE_APPEND);

// ----------------------
// 验证签名和交易状态
// ----------------------
if (hash_equals($calculated_sign, (string)$received_sign) && $data['trade_status'] == 'TRADE_SUCCESS') {
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