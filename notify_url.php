<?php
/**
 * 彩虹易支付通知处理系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 处理彩虹易支付平台的异步通知，支持普通订单和USDT订单，
 * 验证通知数据的真实性，更新订单状态。
 * 特别说明：URL参数plugin=usdt时按USDT订单处理。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

// 引入数据库连接文件（确保 db.php 路径正确）
require_once("db.php");
// 引入配置和 SDK
require_once("lib/epay.config.php");
require_once("lib/EpayCore.class.php");

$epay = new EpayCore($epay_config);
$verify_result = $epay->verifyNotify();

if ($verify_result) {
    // 获取回调参数
    $out_trade_no = $_GET['out_trade_no'] ?? '';
    $trade_no     = $_GET['trade_no'] ?? '';
    $trade_status = $_GET['trade_status'] ?? '';
    $type         = $_GET['type'] ?? '';
    $money        = $_GET['money'] ?? '0.00';
    
    // 记录所有回调参数，便于调试
    error_log("易支付回调参数: " . json_encode($_GET, JSON_UNESCAPED_UNICODE));

    // 根据是否有 plugin=usdt 参数来区分 USDT 订单
    if (isset($_GET['plugin']) && $_GET['plugin'] === 'usdt') {
        // USDT订单处理逻辑：更新订单状态为 'paid'
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = 'paid' WHERE order_no = ?");
            $stmt->execute([$out_trade_no]);
            error_log("USDT订单：$out_trade_no 更新为 paid");
            
            // 发送Telegram支付通知
            try {
                require_once 'lib/TelegramNotifier.php';
                $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE order_no = ?");
                $orderStmt->execute([$out_trade_no]);
                $orderData = $orderStmt->fetch(PDO::FETCH_ASSOC);

                $telegramStmt = $pdo->query("SELECT * FROM telegram_config WHERE enabled = 1 LIMIT 1");
                $telegramConfig = $telegramStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($telegramConfig && $orderData) {
                    error_log("准备发送USDT支付通知，订单号：$out_trade_no");
                }
                
                // 发送WxPusher通知
                require_once 'lib/WxPusherNotifier.php';
                $wxPusher = new WxPusherNotifier();
                if ($orderData) {
                    $wxPusher->sendPaymentNotification($orderData);
                    $notifier = new TelegramNotifier($telegramConfig['bot_token'], $telegramConfig['chat_id']);
                    $notifier->sendPaymentNotification($orderData);
                    error_log("USDT支付通知发送成功，订单号：$out_trade_no");
                }
            } catch (Exception $e) {
                error_log("USDT支付通知发送失败：" . $e->getMessage());
            }
        } catch (Exception $e) {
            error_log("USDT订单更新失败：".$e->getMessage());
            echo "fail";
            exit;
        }
    } else {
        // 普通订单处理逻辑
        if ($trade_status == 'TRADE_SUCCESS') {
            try {
                $stmt = $pdo->prepare("UPDATE orders SET status = 'paid' WHERE order_no = ?");
                $stmt->execute([$out_trade_no]);
                error_log("普通订单：$out_trade_no 更新为 paid");

                // 发送Telegram支付通知
                try {
                    require_once 'lib/TelegramNotifier.php';
                    require_once 'lib/WxPusherNotifier.php';
                    $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE order_no = ?");
                    $orderStmt->execute([$out_trade_no]);
                    $orderData = $orderStmt->fetch(PDO::FETCH_ASSOC);

                    $telegramStmt = $pdo->query("SELECT * FROM telegram_config WHERE enabled = 1 LIMIT 1");
                    $telegramConfig = $telegramStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($telegramConfig && $orderData) {
                        $notifier = new TelegramNotifier($telegramConfig['bot_token'], $telegramConfig['chat_id']);
                        $notifier->sendPaymentNotification($orderData);
                        error_log("Telegram支付通知发送成功：订单号 " . $out_trade_no);
                    }

                    // 发送WxPusher通知
                    if ($orderData) {
                        $wxPusher = new WxPusherNotifier();
                        if ($wxPusher->sendPaymentNotification($orderData)) {
                            error_log("WxPusher支付通知发送成功：订单号 " . $out_trade_no);
                        } else {
                            error_log("WxPusher支付通知发送失败或未启用：订单号 " . $out_trade_no);
                        }
                    }
                } catch (Exception $e) {
                    error_log("通知发送失败：" . $e->getMessage());
                }
            } catch (Exception $e) {
                error_log("普通订单更新失败：".$e->getMessage());
                echo "fail";
                exit;
            }
        }
    }
    echo "success"; // 通知易支付服务器处理成功
} else {
    echo "fail";
}
?>