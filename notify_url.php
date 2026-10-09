<?php
/**
 * 彩虹易支付通知处理系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 项目地址：https://github.com/jasonpan168/ajie-shop
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 处理彩虹易支付平台的异步通知，支持普通订单和USDT订单，
 * 验证通知数据的真实性，更新订单状态。
 * 特别说明：URL参数plugin=usdt时按USDT订单处理。
 * 
 * @license AGPL-3.0-or-later  https://github.com/jasonpan168/ajie-shop
 */

require_once("db.php");
require_once("lib/epay.config.php");
require_once("lib/EpayCore.class.php");
require_once("lib/order_service.php");

$epay = new EpayCore($epay_config);
if (!$epay->verifyNotify()) {
    app_log('payment', '[epay] 签名校验失败 ' . ($_GET['out_trade_no'] ?? ''));
    echo "fail";
    exit;
}

// 必须是本商户、且交易成功（网关回调固定带 trade_status=TRADE_SUCCESS）
if ((string) ($_GET['pid'] ?? '') !== (string) $epay_config['pid'] || ($_GET['trade_status'] ?? '') !== 'TRADE_SUCCESS') {
    app_log('payment', '[epay] 商户号或交易状态不符 ' . ($_GET['out_trade_no'] ?? '') . ' pid=' . ($_GET['pid'] ?? '') . ' status=' . ($_GET['trade_status'] ?? ''));
    echo "fail";
    exit;
}

$order_no = (string) ($_GET['out_trade_no'] ?? '');
$paid_cents = yuan_to_cents($_GET['money'] ?? -1);

try {
    $r = fulfill_paid_order($pdo, $order_no, $paid_cents, 'epay');
} catch (Exception $e) {
    echo "fail"; // 让网关稍后重试
    exit;
}

if ($r['result'] === 'paid') {
    echo "success";
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    send_paid_notifications($pdo, $r['order'], $r['card']);
    exit;
}
echo $r['result'] === 'duplicate' ? "success" : "fail";
