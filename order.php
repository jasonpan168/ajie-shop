<?php
/**
 * 订单处理系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 处理商城系统的订单创建和支付流程，支持原生微信支付（非易支付）。
 * 包含IP限制检查、订单参数验证、优惠码处理等功能。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

require_once 'db.php';
require_once 'config.php';
session_start();

// 引入清理脚本
require_once 'clean_orders.php';

// 检查必填参数：产品 id, 买家昵称, 邮箱, 数量, 价格
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 获取真实IP地址
    $ip = $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded_ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        $ip = $forwarded_ips[0];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // 检查IP限制
    if (!checkIpLimit($ip)) {
        $error_msg = "提交订单过于频繁。为了保证服务质量，每个IP地址：\n";
        $error_msg .= "1. 每次提交订单需间隔至少60秒\n";
        $error_msg .= "2. 10分钟内最多可提交3个订单\n";
        $error_msg .= "请稍后再试。";
        die($error_msg);
    }
    if (!isset($_GET['id']) || !isset($_GET['nickname']) || !isset($_GET['email']) ||
        !isset($_GET['quantity']) || !isset($_GET['price'])) {
        die("缺少必要参数，请返回重试。");
    }
    
    $product_id = intval($_GET['id']);
    $nickname   = trim($_GET['nickname']);
    $email      = trim($_GET['email']);
    $quantity   = intval($_GET['quantity']);
    $price      = floatval($_GET['price']);
    $amount     = $price * $quantity; // 总金额（单位元）
    
    // 处理优惠码
    $coupon_id = isset($_GET['coupon_id']) ? intval($_GET['coupon_id']) : 0;
    $coupon_code = isset($_GET['coupon_code_hidden']) ? trim($_GET['coupon_code_hidden']) : '';
    $coupon_amount = isset($_GET['coupon_amount']) ? floatval($_GET['coupon_amount']) : 0;
    
    // 应用优惠码抵扣
    if ($coupon_amount > 0) {
        // 确保优惠金额不超过订单总额
        if ($coupon_amount > $amount) {
            $coupon_amount = $amount;
        }
        // 计算优惠后的实际支付金额
        $amount = $amount - $coupon_amount;
        // 确保金额不小于0
        if ($amount < 0) {
            $amount = 0;
        }
    }

    if (empty($nickname) || empty($email)) {
        die("姓名/昵称和邮箱为必填项。");
    }

    // 检查产品是否存在及库存
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        die("产品不存在");
    }
    if ($quantity > $product['stock']) {
        die("库存不足");
    }

    // 生成订单号（确保唯一）
    $order_no = date("YmdHis") . rand(1000, 9999);

    // 支付方式：确保原生微信支付使用 "wxpay"
    $type = isset($_GET['type']) ? trim($_GET['type']) : "wxpay";  // 默认值为 wxpay

    // 将订单记录插入数据库，并写入支付方式（pay_type）
    try {
        $stmt = $pdo->prepare("
            INSERT INTO orders 
                (order_no, product_id, product_title, nickname, email, quantity, amount, status, created_at, pay_type, coupon_id, coupon_code, coupon_amount, ip)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?, ?, ?, ?, ?)
        ");
        // 这里使用产品的 title 字段作为产品名称
        $stmt->execute([
            $order_no,
            $product_id,
            $product['title'],
            $nickname,
            $email,
            $quantity,
            $amount,
            $type,
            $coupon_id,
            $coupon_code,
            $coupon_amount,
            $ip
        ]);

        // 发送Telegram下单通知
        // 发送Telegram下单通知
        try {
            require_once 'lib/TelegramNotifier.php';
            require_once 'lib/WxPusherNotifier.php';
            $telegramStmt = $pdo->query("SELECT * FROM telegram_config WHERE enabled = 1 LIMIT 1");
            $telegramConfig = $telegramStmt->fetch(PDO::FETCH_ASSOC);
            
            $orderData = [
                'order_no' => $order_no,
                'product_title' => $product['title'],
                'quantity' => $quantity,
                'amount' => $amount,
                'email' => $email,
                'pay_type' => $type,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // 发送Telegram通知
            if ($telegramConfig) {
                error_log("Telegram配置信息：bot_token=" . substr($telegramConfig['bot_token'], 0, 10) . "..., chat_id=" . $telegramConfig['chat_id']);
                error_log("准备发送Telegram通知，订单数据：" . json_encode($orderData, JSON_UNESCAPED_UNICODE));
                $notifier = new TelegramNotifier($telegramConfig['bot_token'], $telegramConfig['chat_id']);
                $notifier->sendOrderNotification($orderData);
                error_log("Telegram通知发送成功：订单号 " . $order_no);
            } else {
                error_log("未找到启用的Telegram配置");
            }
            
            // 发送WxPusher通知
            $wxPusher = new WxPusherNotifier();
            if ($wxPusher->sendOrderNotification($orderData)) {
                error_log("WxPusher下单通知发送成功：订单号 " . $order_no);
            } else {
                error_log("WxPusher下单通知发送失败或未启用");
            }
        } catch (Exception $e) {
            error_log("通知发送失败：" . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    } catch (Exception $e) {
        die("订单创建失败：" . $e->getMessage());
    }

    // 构造附加数据（用于微信统一下单回调时关联订单）
    $attachData = [
        'product_id' => $product_id,
        'quantity'   => $quantity,
        'nickname'   => $nickname,
        'email'      => $email
    ];
    $attach = json_encode($attachData);

    // 微信统一下单接口参数（请确保 config.php 中定义了以下变量）
    $appid      = $merchant_appid;
    $mch_id     = $merchant_mchid;
    $merchant_key_local = $merchant_api_key; // 从 config.php 获取微信支付 API 密钥
    $notify_url = 'https://yewu.laikr.com/notify.php';
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
        'body' => $product['title'],
        'out_trade_no'     => $order_no,
        'total_fee'        => intval($amount * 100), // 单位为分
        'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $result = xmlToArray($response);
        if (isset($result["return_code"]) && $result["return_code"] == "SUCCESS" &&
            isset($result["result_code"]) && $result["result_code"] == "SUCCESS") {
            $code_url = $result["code_url"];
        } else {
            die("微信支付错误: " . ($result["return_msg"] ?? "未知错误"));
        }
    } else {
        die("无法连接微信支付接口，请检查网络。");
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