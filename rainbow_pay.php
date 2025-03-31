<?php
/**
 * 彩虹易支付处理系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 处理彩虹易支付的订单创建流程，生成订单记录，
 * 调用支付SDK获取支付链接，并进行页面跳转。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/epay.config.php';
require_once __DIR__ . '/lib/EpayCore.class.php';
require_once __DIR__ . '/clean_orders.php';



// 获取客户端IP地址
$ip = $_SERVER['REMOTE_ADDR'];
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $forwarded_ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
    $ip = $forwarded_ips[0];
} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
}

// 检查IP限制
if (!checkIpLimit($ip)) {
    die("提交订单过于频繁，请稍后再试。");
}

// 1. 获取订单相关参数
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$nickname   = isset($_GET['nickname']) ? trim($_GET['nickname']) : "匿名";
$email      = isset($_GET['email']) ? trim($_GET['email']) : "test@example.com";
$quantity   = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
$price      = isset($_GET['price']) ? floatval($_GET['price']) : 4.51;
$type = isset($_GET['type']) ? trim($_GET['type']) : "alipay"; // 用户选择的支付方式

// 处理优惠码
$coupon_id = isset($_GET['coupon_id']) ? intval($_GET['coupon_id']) : 0;
$coupon_code = isset($_GET['coupon_code_hidden']) ? trim($_GET['coupon_code_hidden']) : '';
$coupon_amount = isset($_GET['coupon_amount']) ? floatval($_GET['coupon_amount']) : 0;

// 验证支付类型
if (!in_array($type, ['alipay', 'wxpay', 'usdt'])) {
    die("不支持的支付方式");
}

// 2. 查询商品信息（如果存在）获取商品名称和价格
$stmt = $pdo->prepare("SELECT title, price FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$productData = $stmt->fetch(PDO::FETCH_ASSOC);
if ($productData) {
    $product_title = $productData['title'];
    if ($price == 0) {
        $price = floatval($productData['price']);
    }
} else {
    $product_title = "未知产品";
}

// 3. 定义订单名称（直接使用商品名称）
$order_name = $product_title;

// 4. 计算总金额（单位：元）
$money = $price * $quantity;

// 应用优惠码抵扣
if ($coupon_amount > 0) {
    // 确保优惠金额不超过订单总额
    if ($coupon_amount > $money) {
        $coupon_amount = $money;
    }
    // 计算优惠后的实际支付金额
    $money = $money - $coupon_amount;
    // 确保金额不小于0
    if ($money < 0) {
        $money = 0;
    }
}

// 格式化金额
$money = number_format($money, 2, ".", "");

// 5. 生成唯一订单号，并插入订单记录（状态初始设为 pending）
$order_no = time() . rand(100, 999);
try {
    $stmt = $pdo->prepare("
        INSERT INTO orders 
            (order_no, product_id, product_title, nickname, email, quantity, amount, status, created_at, pay_type, coupon_id, coupon_code, coupon_amount, ip)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $order_no, 
        $product_id, 
        $product_title, 
        $nickname, 
        $email, 
        $quantity, 
        $money, 
        $type, 
        $coupon_id > 0 ? $coupon_id : null,
        !empty($coupon_code) ? $coupon_code : null,
        $coupon_amount > 0 ? $coupon_amount : 0,
        $ip
    ]);
    
    // 发送Telegram和WxPusher下单通知
    try {
        require_once 'lib/TelegramNotifier.php';
        require_once 'lib/WxPusherNotifier.php';
        $telegramStmt = $pdo->query("SELECT * FROM telegram_config WHERE enabled = 1 LIMIT 1");
        $telegramConfig = $telegramStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($telegramConfig) {
            error_log("准备发送易支付下单Telegram通知，订单号：$order_no");
            $orderData = [
                'order_no' => $order_no,
                'product_title' => $product_title,
                'quantity' => $quantity,
                'amount' => $money,
                'email' => $email,
                'pay_type' => $type,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $notifier = new TelegramNotifier($telegramConfig['bot_token'], $telegramConfig['chat_id']);
            $notifier->sendOrderNotification($orderData);
            error_log("易支付下单Telegram通知发送成功，订单号：$order_no");
        } else {
            error_log("未找到启用的Telegram配置，无法发送易支付下单通知");
        }

        // 发送WxPusher通知
        $wxPusher = new WxPusherNotifier();
        if ($wxPusher->sendOrderNotification($orderData)) {
            error_log("易支付下单WxPusher通知发送成功，订单号：$order_no");
        } else {
            error_log("易支付下单WxPusher通知发送失败或未启用，订单号：$order_no");
        }
    } catch (Exception $e) {
        error_log("易支付下单Telegram通知发送失败：" . $e->getMessage());
    }
} catch (Exception $e) {
    die("订单创建失败：" . $e->getMessage());
}

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
    die("支付链接生成失败。");
}

header("Location: $pay_link");
exit;
?>