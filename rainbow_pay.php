<?php
/**
 * 彩虹易支付处理系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 项目地址：https://github.com/jasonpan168/ajie-shop
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 处理彩虹易支付的订单创建流程，生成订单记录，
 * 调用支付SDK获取支付链接，并进行页面跳转。
 * 
 * @license AGPL-3.0-or-later  https://github.com/jasonpan168/ajie-shop
 */

require_once __DIR__ . '/db.php';

// 检查易支付配置是否已填写（字段以 database.sql 的 epay_config 表为准）
$epay_row = $pdo->query("SELECT * FROM epay_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$epay_ready = $epay_row &&
              (!empty($epay_row['alipay_enabled']) || !empty($epay_row['wxpay_enabled']) || !empty($epay_row['usdt_enabled'])) &&
              !empty($epay_row['apiurl']) && !empty($epay_row['pid']) && !empty($epay_row['key']);
if (!$epay_ready) {
    header('Location: payment-setup-guide.php?type=epay');
    exit;
}

require_once __DIR__ . '/lib/epay.config.php';
require_once __DIR__ . '/lib/EpayCore.class.php';
require_once __DIR__ . '/clean_orders.php';

require_once __DIR__ . '/lib/order_service.php';

$ip = client_ip();


// 只允许后台已开启的支付方式
$type = isset($_GET['type']) ? (is_string($_GET['type']) ? trim($_GET['type']) : '') : '';
$enabled = $pdo->query("SELECT alipay_enabled, wxpay_enabled, usdt_enabled FROM epay_config LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
$allowed = array_keys(array_filter([
    'alipay' => !empty($enabled['alipay_enabled']),
    'wxpay'  => !empty($enabled['wxpay_enabled']),
    'usdt'   => !empty($enabled['usdt_enabled']),
]));
if (!in_array($type, $allowed, true)) {
    die("不支持的支付方式");
}

// 价格、优惠金额全部由服务端按数据库计算，浏览器传来的 price/coupon_amount 一律忽略
try {
    $order = with_ip_lock($pdo, $ip, function () use ($pdo, $ip, $type) {
        if (!checkIpLimit($ip)) {
            throw new RuntimeException("提交订单过于频繁，请稍后再试。");
        }
        return create_pending_order($pdo, $_GET, $type);
    });
} catch (RuntimeException $e) {
    die(htmlspecialchars($e->getMessage()));
} catch (Exception $e) {
    app_log('order', '易支付下单失败: ' . $e->getMessage());
    die('订单创建失败，请稍后重试。');
}
$order_no   = $order['order_no'];
$order_name = $order['product_title'];
$money      = $order['amount'];
send_order_created_notifications($pdo, $order);

// 6. 构造彩虹易支付请求参数（配置文件中的地址末尾必须有斜杠）
$params = [
    "pid"         => $epay_config['pid'],
    "out_trade_no"=> $order_no,
    "name"        => $order_name,
    "money"       => $money,
    "type"        => $type,
    "notify_url"  => $epay_config['notify_url'],
    "return_url"  => $epay_config['return_url']
];

// 7. 使用 EpayCore 获取支付链接
$epay = new EpayCore($epay_config);
$pay_link = $epay->getPayLink($params);

// 8. 检查支付链接有效性，并跳转到支付页面
if (!$pay_link || !filter_var($pay_link, FILTER_VALIDATE_URL)) {
    cancel_pending_order($pdo, $order_no);
    die("支付链接生成失败。");
}

header("Location: $pay_link");
exit;
?>