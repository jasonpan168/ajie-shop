<?php
require_once 'config.php';
require_once 'lib/TelegramNotifier.php';

// 如果是POST请求，则发送测试通知
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 创建模拟订单数据
        $orderData = [
            'product_title' => '测试商品',
            'quantity' => 1,
            'order_no' => 'TEST' . date('YmdHis'),
            'created_at' => date('Y-m-d H:i:s'),
            'email' => 'test@example.com',
            'pay_type' => '测试支付',
            'amount' => '99.99'
        ];

        // 获取Telegram配置
        $stmt = $pdo->query("SELECT * FROM telegram_config WHERE enabled = 1 LIMIT 1");
        $telegramConfig = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$telegramConfig) {
            throw new Exception('未找到有效的Telegram配置，请先在管理后台配置Telegram机器人');
        }
        
        // 初始化Telegram通知器
        $notifier = new TelegramNotifier($telegramConfig['bot_token'], $telegramConfig['chat_id']);
        
        // 发送下单通知
        $result = $notifier->sendOrderNotification($orderData);
        $message = '通知发送成功！请检查您的Telegram是否收到消息。';
        $status = 'success';
    } catch (Exception $e) {
        $message = '发送失败：' . $e->getMessage();
        $status = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram通知测试</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.1/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Telegram通知测试</h2>
        
        <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $status; ?>" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">模拟下单通知</h5>
                <p class="card-text">点击下面的按钮将发送一个测试订单通知到您配置的Telegram账号。</p>
                <form method="post" action="">
                    <button type="submit" class="btn btn-primary">发送测试通知</button>
                </form>
            </div>
        </div>

        <div class="mt-3">
            <p><strong>提示：</strong></p>
            <ul>
                <li>确保已在管理后台正确配置了Telegram机器人Token和Chat ID</li>
                <li>确保已启用了Telegram通知功能</li>
                <li>如果发送失败，请检查错误信息并确认配置是否正确</li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.1/js/bootstrap.bundle.min.js"></script>
</body>
</html>