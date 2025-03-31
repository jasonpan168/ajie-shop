<?php
/**
 * 支付通知处理系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 处理支付平台的异步通知，验证支付状态，
 * 更新订单状态，并发送相关通知。
 * 这是整个支付流程中的关键组件。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

require_once 'db.php';
require_once 'config.php';
require_once 'send_mail.php';

$debugLogFile = __DIR__ . '/notify_debug.log';
$logFile = __DIR__ . '/notify.log';

// 记录原始 POST 数据
$rawData = file_get_contents('php://input');
file_put_contents($debugLogFile, date('Y-m-d H:i:s') . " Raw POST data:\n" . $rawData . "\n", FILE_APPEND);
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Received XML:\n" . $rawData . "\n", FILE_APPEND);

if (!$rawData) {
    exit('No data received');
}

// 解析 XML 数据为数组
$result = json_decode(json_encode(simplexml_load_string($rawData, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
file_put_contents($logFile, "Parsed result:\n" . print_r($result, true) . "\n", FILE_APPEND);

// 获取微信传来的签名，并移除
$wechatSign = $result['sign'];
unset($result['sign']);

// 签名函数：过滤空值及 sign, sign_type 后追加 &key=KEY
function getLocalSign($params, $key) {
    ksort($params);
    $stringA = '';
    foreach ($params as $k => $v) {
        if ($v !== '' && !is_array($v)) {
            $stringA .= $k . '=' . $v . '&';
        }
    }
    $stringSignTemp = rtrim($stringA, '&') . '&key=' . $key;
    return strtoupper(md5($stringSignTemp));
}

// 从 config.php 获取解密后的商户密钥
$merchant_key_local = $merchant_api_key;
$localSign = getLocalSign($result, $merchant_key_local);
file_put_contents($logFile, "Local sign: $localSign, WeChat sign: $wechatSign\n", FILE_APPEND);

// 验证签名和支付状态
if ($localSign == $wechatSign && $result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
    $order_no = $result['out_trade_no'];
    $total_fee = $result['total_fee']; // 单位：分

    // 从 attach 参数解析 product_id, quantity, nickname, email
    $attach = isset($result['attach']) ? json_decode($result['attach'], true) : [];
    
    // 发送WxPusher通知
    require_once 'lib/WxPusherNotifier.php';
    $wxPusher = new WxPusherNotifier();
    
    // 获取订单信息
    $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE order_no = ?");
    $orderStmt->execute([$order_no]);
    $orderData = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($orderData) {
        $wxPusher->sendPaymentNotification($orderData);
    }
    file_put_contents($logFile, "Attach data:\n" . print_r($attach, true) . "\n", FILE_APPEND);
    
    $product_id = isset($attach['product_id']) ? intval($attach['product_id']) : 0;
    $quantity   = isset($attach['quantity']) ? intval($attach['quantity']) : 1;
    $nickname   = isset($attach['nickname']) ? $attach['nickname'] : '';
    $email      = isset($attach['email']) ? $attach['email'] : '';

    if (!$order_no) {
        file_put_contents($logFile, "Missing order_no in callback.\n", FILE_APPEND);
        exit('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[Missing order_no]]></return_msg></xml>');
    }
    
    // 查询产品信息，获取 title & is_autocard
    try {
        $stmtProd = $pdo->prepare("SELECT title, is_autocard FROM products WHERE id = ?");
        $stmtProd->execute([$product_id]);
        $productData = $stmtProd->fetch(PDO::FETCH_ASSOC);
        $product_title = $productData ? $productData['title'] : '';
        $is_autocard = $productData ? $productData['is_autocard'] : 0;
    } catch (Exception $e) {
        // 如果出错或没有 is_autocard 字段，则默认 is_autocard=0
        $stmtProd = $pdo->prepare("SELECT title FROM products WHERE id = ?");
        $stmtProd->execute([$product_id]);
        $productData = $stmtProd->fetch(PDO::FETCH_ASSOC);
        $product_title = $productData ? $productData['title'] : '';
        $is_autocard = 0;
    }
    if (empty($product_title) && isset($attach['title'])) {
        $product_title = $attach['title'];
    }
    if (empty($product_title)) {
        $product_title = '未知产品';
    }
    file_put_contents($logFile, "Queried product title: $product_title, is_autocard: $is_autocard\n", FILE_APPEND);
    
    // 更新或插入订单记录
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_no = ?");
    $stmt->execute([$order_no]);
    $orderRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($orderRecord) {
        if ($orderRecord['status'] !== 'paid') {
            $stmt = $pdo->prepare("UPDATE orders SET status = 'paid', amount = ? WHERE order_no = ?");
            $stmt->execute([$total_fee / 100, $order_no]);
            file_put_contents($logFile, "Order $order_no updated to paid.\n", FILE_APPEND);
        } else {
            file_put_contents($logFile, "Order $order_no already marked as paid.\n", FILE_APPEND);
        }
    } else {
        // 新订单
        $stmt = $pdo->prepare("
            INSERT INTO orders 
            (order_no, product_id, product_title, nickname, email, quantity, amount, status, created_at, pay_type)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, 'paid', NOW(), ?)
        ");
        $stmt->execute([
            $order_no,
            $product_id,
            $product_title,
            $nickname,
            $email,
            $quantity,
            $total_fee / 100,
            'wxpay'
        ]);
        file_put_contents($logFile, "Order $order_no inserted successfully.\n", FILE_APPEND);
    }
    
    // 扣减库存
    try {
        $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$quantity, $product_id]);
    } catch (Exception $e) {
        file_put_contents($logFile, "库存更新失败: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    
    // 发送Telegram支付通知
    try {
        require_once 'lib/TelegramNotifier.php';
        $orderData = [
            'order_no' => $order_no,
            'product_title' => $product_title,
            'quantity' => $quantity,
            'amount' => $total_fee / 100,
            'email' => $email,
            'pay_type' => 'wxpay',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $telegramStmt = $pdo->query("SELECT * FROM telegram_config WHERE enabled = 1 LIMIT 1");
        $telegramConfig = $telegramStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($telegramConfig) {
            file_put_contents($logFile, "准备发送微信支付Telegram通知，订单号：$order_no\n", FILE_APPEND);
            $notifier = new TelegramNotifier($telegramConfig['bot_token'], $telegramConfig['chat_id']);
            $notifier->sendPaymentNotification($orderData);
            file_put_contents($logFile, "微信支付Telegram通知发送成功，订单号：$order_no\n", FILE_APPEND);
        } else {
            file_put_contents($logFile, "未找到启用的Telegram配置，无法发送通知\n", FILE_APPEND);
        }
    } catch (Exception $e) {
        file_put_contents($logFile, "微信支付Telegram通知发送失败：" . $e->getMessage() . "\n", FILE_APPEND);
    }
    
    // 返回成功给微信，避免超时
    echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    
    // 1. 发送支付成功邮件（无发卡内容）
    $subject_payment = "阿杰平台：您的订单 {$order_no} 已支付成功";
    $body_payment = "<p>尊敬的网友，</p>
                     <p>您的订单 <strong>{$order_no}</strong> 已支付成功！</p>
                     <p>产品：" . htmlspecialchars($product_title) . "</p>
                     <p>数量：{$quantity}</p>
                     <p>总金额：￥" . ($total_fee / 100) . "</p>
                     <p>感谢您的购买！</p>
                     <p>—— 祝您使用愉快</p>";
    $mailResult1 = sendMail($email, $subject_payment, $body_payment);
    file_put_contents($logFile, "Payment email send result: " . print_r($mailResult1, true) . "\n", FILE_APPEND);
    
    // 2. 自动发卡逻辑
    if ($is_autocard == 1) {
        // 检查是否已经发卡
        $stmt = $pdo->prepare("SELECT card_sent FROM orders WHERE order_no = ?");
        $stmt->execute([$order_no]);
        $orderInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (isset($orderInfo['card_sent']) && $orderInfo['card_sent'] == 1) {
            file_put_contents($logFile, "Auto card already sent for order $order_no.\n", FILE_APPEND);
        } else {
            $cardContent = '';
            // 查询发卡任务
            $stmtTask = $pdo->prepare("SELECT * FROM auto_card_tasks WHERE product_id = ? AND status = 'active' ORDER BY created_at ASC LIMIT 1");
            $stmtTask->execute([$product_id]);
            $task = $stmtTask->fetch(PDO::FETCH_ASSOC);
            if ($task) {
                file_put_contents($logFile, "Found auto card task: " . print_r($task, true) . "\n", FILE_APPEND);
                if (isset($task['repeat_flag']) && $task['repeat_flag'] == 1) {
                    // 固定发卡模式
                    $stmtCard = $pdo->prepare("SELECT * FROM auto_cards WHERE task_id = ? LIMIT 1");
                    $stmtCard->execute([$task['id']]);
                    $card = $stmtCard->fetch(PDO::FETCH_ASSOC);
                    if ($card) {
                        $cardContent = $card['card_content'];
                        file_put_contents($logFile, "Fixed card mode: using card id " . $card['id'] . "\n", FILE_APPEND);
                    } else {
                        $cardContent = "暂无可用发卡内容，请联系客服。";
                        file_put_contents($logFile, "Fixed card mode: no card found.\n", FILE_APPEND);
                    }
                } else {
                    // 依次发卡模式：使用事务锁定一条未使用卡密
                    try {
                        $pdo->beginTransaction();
                        $stmtCard = $pdo->prepare("
                            SELECT * 
                            FROM auto_cards 
                            WHERE task_id = ? AND status = 'unused' 
                            ORDER BY id ASC 
                            LIMIT 1 
                            FOR UPDATE
                        ");
                        $stmtCard->execute([$task['id']]);
                        $card = $stmtCard->fetch(PDO::FETCH_ASSOC);
                        if ($card) {
                            $cardContent = $card['card_content'];
                            // 注意这里要更新 order_no 字段
                            $stmtUpdateCard = $pdo->prepare("
                                UPDATE auto_cards 
                                SET status = 'used', email = ?, order_no = ?, created_at = NOW() 
                                WHERE id = ?
                            ");
                            $stmtUpdateCard->execute([$email, $order_no, $card['id']]);
                            file_put_contents($logFile, "Sequential mode: card id " . $card['id'] . " marked as used.\n", FILE_APPEND);
                        } else {
                            $cardContent = "暂无可用卡密，请联系客服。";
                            file_put_contents($logFile, "Sequential mode: no unused card found.\n", FILE_APPEND);
                        }
                        $pdo->commit();
                    } catch (Exception $ex) {
                        $pdo->rollBack();
                        file_put_contents($logFile, "Sequential mode transaction failed: " . $ex->getMessage() . "\n", FILE_APPEND);
                        $cardContent = "发卡失败，请联系客服。";
                    }
                }
                file_put_contents($logFile, "Card issued for order $order_no: " . $cardContent . "\n", FILE_APPEND);
                
                // 发送自动发卡邮件
                $subject_card = "阿杰平台：您的自动发卡内容已发放";
                $body_card = "<p>尊敬的网友，</p>
                              <p>您的订单 <strong>{$order_no}</strong> 已支付成功，以下是您的自动发卡内容：</p>
                              <p>产品：" . htmlspecialchars($product_title) . "</p>
                              <p>卡密/内容：" . $cardContent . "</p>
                              <p>感谢您的购买，祝您使用愉快！</p>";
                $mailResult2 = sendMail($email, $subject_card, $body_card);
                file_put_contents($logFile, "Auto card email send result: " . print_r($mailResult2, true) . "\n", FILE_APPEND);
                
                // 标记订单已发卡
                $stmt = $pdo->prepare("UPDATE orders SET card_sent = 1 WHERE order_no = ?");
                $stmt->execute([$order_no]);
            } else {
                file_put_contents($logFile, "未找到与商品关联的发卡任务。\n", FILE_APPEND);
            }
        }
    }
    exit;
} else {
    file_put_contents($logFile, "Signature verification failed or error in callback.\n", FILE_APPEND);
    echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[Signature verification failed]]></return_msg></xml>';
    exit;
}
?>