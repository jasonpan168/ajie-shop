<?php
/**
 * 支付通知测试系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 提供支付通知功能的测试环境，包括：
 * 1. 模拟订单创建
 * 2. 模拟支付完成
 * 3. 测试通知发送功能
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */


require_once 'db.php';
require_once 'lib/WxPusherNotifier.php';

// 记录日志函数
function writeLog($message) {
    $logFile = 'logs/test_notify.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];

    try {
        if ($action === 'create_order') {
            // 生成订单号
            $order_no = 'TEST' . date('YmdHis') . rand(1000, 9999);
            
            // 创建测试订单
            $stmt = $pdo->prepare("INSERT INTO orders (order_no, product_id, product_title, quantity, amount, status, created_at, email, nickname) VALUES (?, 1, '测试商品', 1, 99.00, 'pending', NOW(), 'test@example.com', '测试用户')");
            $stmt->execute([$order_no]);
            
            // 获取订单信息用于发送通知
            $orderData = [
                'order_no' => $order_no,
                'product_title' => '测试商品',
                'amount' => 99.00
            ];
            
            // 发送WxPusher通知
            $wxPusher = new WxPusherNotifier();
            if ($wxPusher->sendOrderNotification($orderData)) {
                writeLog("WxPusher下单通知发送成功：$order_no");
            } else {
                writeLog("WxPusher下单通知发送失败或未启用");
            }
            
            $response = ['success' => true, 'message' => "测试订单创建成功：$order_no"];
            writeLog("创建测试订单：$order_no");

        } elseif ($action === 'simulate_payment') {
            // 获取最新的待支付测试订单
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_no LIKE 'TEST%' AND status = 'pending' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                // 更新订单状态
                $stmt = $pdo->prepare("UPDATE orders SET status = 'paid' WHERE order_no = ?");
                $stmt->execute([$order['order_no']]);

                // 发送WxPusher通知
                $wxPusher = new WxPusherNotifier();
                if ($wxPusher->sendPaymentNotification($order)) {
                    writeLog("已发送WxPusher通知：{$order['order_no']}");
                }

                $response = ['success' => true, 'message' => "支付成功通知已发送：{$order['order_no']}"];
            } else {
                $response = ['success' => false, 'message' => '没有找到测试订单'];
            }
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => '操作失败：' . $e->getMessage()];
        writeLog("错误：" . $e->getMessage());
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>通知功能测试</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">WxPusher通知功能测试面板</h4>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <button id="createOrderBtn" class="btn btn-primary me-2">模拟下单</button>
                    <button id="simulatePaymentBtn" class="btn btn-success">模拟支付成功</button>
                </div>
                <div id="resultArea" class="alert d-none"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('createOrderBtn').addEventListener('click', async () => {
            try {
                const response = await fetch('test_notify.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=create_order'
                });
                const result = await response.json();
                showResult(result.success, result.message);
            } catch (error) {
                showResult(false, '请求失败：' + error.message);
            }
        });

        document.getElementById('simulatePaymentBtn').addEventListener('click', async () => {
            try {
                const response = await fetch('test_notify.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=simulate_payment'
                });
                const result = await response.json();
                showResult(result.success, result.message);
            } catch (error) {
                showResult(false, '请求失败：' + error.message);
            }
        });

        function showResult(success, message) {
            const resultArea = document.getElementById('resultArea');
            resultArea.className = `alert ${success ? 'alert-success' : 'alert-danger'}`;
            resultArea.textContent = message;
            resultArea.classList.remove('d-none');
        }
    </script>
</body>
</html>