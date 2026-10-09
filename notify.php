<?php
/**
 * 支付通知处理系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 项目地址：https://github.com/jasonpan168/ajie-shop
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 处理支付平台的异步通知，验证支付状态，
 * 更新订单状态，并发送相关通知。
 * 这是整个支付流程中的关键组件。
 * 
 * @license AGPL-3.0-or-later  https://github.com/jasonpan168/ajie-shop
 */

require_once 'db.php';
require_once 'config.php';
require_once 'lib/order_service.php';

function wx_reply($ok, $msg = 'OK') {
    echo '<xml><return_code><![CDATA[' . ($ok ? 'SUCCESS' : 'FAIL') . ']]></return_code><return_msg><![CDATA[' . $msg . ']]></return_msg></xml>';
    exit;
}

// 微信 APIv2 签名：过滤空值和 sign，按 key 排序拼接后追加 &key=
function getLocalSign($params, $key) {
    ksort($params);
    $stringA = '';
    foreach ($params as $k => $v) {
        if ($k !== 'sign' && $v !== '' && !is_array($v)) {
            $stringA .= $k . '=' . $v . '&';
        }
    }
    return strtoupper(md5($stringA . 'key=' . $key));
}

$rawData = file_get_contents('php://input');
if (!$rawData) {
    wx_reply(false, 'No data');
}

libxml_use_internal_errors(true);
$xml = simplexml_load_string($rawData, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
$result = $xml ? json_decode(json_encode($xml), true) : null;
if (!is_array($result) || empty($result['sign'])) {
    app_log('payment', '[wechat] 回调报文无法解析');
    wx_reply(false, 'Bad request');
}

if (empty($merchant_api_key) || !hash_equals(getLocalSign($result, $merchant_api_key), (string) $result['sign'])) {
    app_log('payment', '[wechat] 签名校验失败 ' . ($result['out_trade_no'] ?? ''));
    wx_reply(false, 'Signature verification failed');
}

if (($result['return_code'] ?? '') !== 'SUCCESS' || ($result['result_code'] ?? '') !== 'SUCCESS') {
    wx_reply(true);
}

// 必须是本商户的单，防止拿别的商户号的合法通知来套
if (($result['appid'] ?? '') !== (string) $merchant_appid || ($result['mch_id'] ?? '') !== (string) $merchant_mchid) {
    app_log('payment', '[wechat] appid/商户号不匹配 ' . ($result['out_trade_no'] ?? ''));
    wx_reply(false, 'Merchant mismatch');
}

$order_no = (string) ($result['out_trade_no'] ?? '');
$paid_cents = (int) ($result['total_fee'] ?? -1);

try {
    $r = fulfill_paid_order($pdo, $order_no, $paid_cents, 'wechat');
} catch (Exception $e) {
    wx_reply(false, 'Server error'); // 让微信稍后重试
}

if ($r['result'] === 'not_found') {
    wx_reply(false, 'Order not found');
}
if ($r['result'] === 'amount_mismatch') {
    wx_reply(false, 'Amount mismatch');
}

// paid / duplicate 都告诉微信处理成功；只有首次入账才发通知
if ($r['result'] === 'paid') {
    echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    send_paid_notifications($pdo, $r['order'], $r['card']);
    exit;
}
wx_reply(true);
