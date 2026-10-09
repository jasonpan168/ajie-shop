<?php
/**
 * 订单处理系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 项目地址：https://github.com/jasonpan168/ajie-shop
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 处理商城系统的订单创建和支付流程，支持原生微信支付（非易支付）。
 * 包含IP限制检查、订单参数验证、优惠码处理等功能。
 * 
 * @license AGPL-3.0-or-later  https://github.com/jasonpan168/ajie-shop
 */

require_once 'db.php';
require_once 'config.php';
require_once 'lib/InputValidator.php';
require_once 'lib/Logger.php';
session_start();

/**
 * 校验支付回调地址是否已由管理员正确填写。
 *
 * 回调地址必须来自后台「微信支付配置」，绝不允许写死任何固定域名：
 * 写死会导致别人部署后的支付回调（含订单号、金额、买家昵称与邮箱）
 * 被发到第三方服务器，且他们自己的订单永远不会变成已支付。
 */
function is_valid_notify_url($url) {
    $url = trim((string)$url);
    if ($url === '') {
        return false;
    }
    // 未替换的占位符一律视为未配置
    foreach (array('填写', '你的域名', 'example.com', '127.0.0.1', 'localhost') as $placeholder) {
        if (strpos($url, $placeholder) !== false) {
            return false;
        }
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, array('http', 'https'), true);
}

// 引入清理脚本
require_once 'clean_orders.php';

// 检查微信支付配置
$stmt = $pdo->query("SELECT * FROM wechat_config LIMIT 1");
$wechat_config = $stmt->fetch(PDO::FETCH_ASSOC);

$wechat_ready = $wechat_config && $wechat_config['enabled'] &&
                !empty($wechat_config['appid']) &&
                !empty($wechat_config['mch_id']) &&
                !empty($wechat_config['api_key']) &&
                strpos($wechat_config['appid'], '填写') === false &&
                strpos($wechat_config['mch_id'], '填写') === false &&
                strpos($wechat_config['api_key'], '填写') === false &&
                is_valid_notify_url($wechat_config['notify_url'] ?? '');

if (!$wechat_ready) {
    header('Location: payment-setup-guide.php?type=wechat');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_once 'lib/order_service.php';

    // 价格、优惠金额全部由服务端按数据库计算，浏览器传来的 price/coupon_amount 一律忽略；
    // 只认 REMOTE_ADDR，同一 IP 的限流检查与建单串行执行
    $ip = client_ip();
    $type = 'wxpay';
    try {
        $order = with_ip_lock($pdo, $ip, function () use ($pdo, $ip, $type) {
            if (!checkIpLimit($ip)) {
                throw new RuntimeException("提交订单过于频繁：每次下单需间隔至少 60 秒，10 分钟内最多 3 单，请稍后再试。");
            }
            return create_pending_order($pdo, $_GET, $type);
        });
    } catch (RuntimeException $e) {
        die(htmlspecialchars($e->getMessage()));
    } catch (Exception $e) {
        app_log('order', '微信下单失败: ' . $e->getMessage());
        die('订单创建失败，请稍后重试。');
    }
    $order_no   = $order['order_no'];
    $product_id = $order['product_id'];
    $quantity   = $order['quantity'];
    $nickname   = $order['nickname'];
    $email      = $order['email'];
    $product    = ['title' => $order['product_title']];
    send_order_created_notifications($pdo, $order);

    // 构造附加数据（用于微信统一下单回调时关联订单）
    // attach 只放订单号，回调一律以数据库订单为准
    $attach = $order_no;

    // 微信统一下单接口参数（请确保 config.php 中定义了以下变量）
    $appid      = $merchant_appid;
    $mch_id     = $merchant_mchid;
    $merchant_key_local = $merchant_api_key; // 从 config.php 获取微信支付 API 密钥
    // 回调地址只能来自后台配置（wechat_config 表），config.php 已将其读入 $notify_url。
    // 这里优先用本文件开头已查出的 $wechat_config，并做一次兜底校验；
    // 任何情况下都不允许静默回落到作者自己的域名。
    $notify_url = trim((string)($wechat_config['notify_url'] ?? ($configData['notify_url'] ?? '')));
    if (!is_valid_notify_url($notify_url)) {
        cancel_pending_order($pdo, $order_no);
        Logger::logSecurityEvent('Invalid notify_url', 'ERROR', array('order' => $order_no));
        die('支付回调地址（notify_url）未配置或格式错误。请登录后台「微信支付配置」，'
            . '填写你自己的回调地址（例如 https://你的域名/notify.php）后重试。');
    }
    $unifiedorder_url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    // 辅助函数：生成随机字符串
    function createNonceStr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++){
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    // 辅助函数：生成签名
    function getSign($params, $key) {
        $params_filter = [];
        foreach ($params as $k => $v) {
            if ($v !== '' && $k != 'sign' && !is_array($v)) {
                $params_filter[$k] = $v;
            }
        }
        ksort($params_filter);
        $stringA = "";
        foreach ($params_filter as $k => $v) {
            $stringA .= $k . '=' . $v . '&';
        }
        $stringSignTemp = $stringA . "key=" . $key;
        return strtoupper(md5($stringSignTemp));
    }

    // 辅助函数：将数组转换为 XML 格式
    function arrayToXml($arr) {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<{$key}>{$val}</{$key}>";
            } else {
                $xml .= "<{$key}><![CDATA[{$val}]]></{$key}>";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    // 辅助函数：将 XML 转换为数组
    function xmlToArray($xml) {
        $result = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $result;
    }

    $params = [
        'appid'            => $appid,
        'mch_id'           => $mch_id,
        'nonce_str'        => createNonceStr(),
        'body'             => mb_substr($product['title'], 0, 40),
        'out_trade_no'     => $order_no,
        'total_fee'        => $order['amount_cents'], // 单位为分，服务端计算
        'spbill_create_ip' => $ip,
        'notify_url'       => $notify_url,
        'trade_type'       => 'NATIVE',
        'attach'           => $attach
    ];
    $params['sign'] = getSign($params, $merchant_key_local);
    $xmlData = arrayToXml($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $unifiedorder_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);

    if ($response) {
        $result = xmlToArray($response);
        if (isset($result["return_code"]) && $result["return_code"] == "SUCCESS" &&
            isset($result["result_code"]) && $result["result_code"] == "SUCCESS") {
            $code_url = $result["code_url"];
        } else {
            cancel_pending_order($pdo, $order_no);
            app_log('order', "微信统一下单失败 $order_no: " . ($result["return_msg"] ?? '') . ' ' . ($result["err_code_des"] ?? ''));
            die("微信支付暂时不可用，请稍后重试或换一种支付方式。");
        }
    } else {
        cancel_pending_order($pdo, $order_no);
        die("无法连接微信支付接口，请稍后重试。");
    }

    $_SESSION['order_data'] = [
        'order_no' => $order_no,
        'code_url' => $code_url
    ];
    header("Location: order_display.php");
    exit;
} else {
    die("非法访问");
}
?>