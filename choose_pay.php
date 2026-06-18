<?php
require_once 'db.php';
require_once 'clean_orders.php';
require_once 'lib/SafeOutput.php';

// 获取支付配置
$stmt = $pdo->query("SELECT * FROM epay_config LIMIT 1");
$epay_config = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM wechat_config LIMIT 1");
$wechat_config = $stmt->fetch(PDO::FETCH_ASSOC);

// 检查配置是否完整
$epay_ready = $epay_config &&
              isset($epay_config['enabled']) && $epay_config['enabled'] &&
              (!empty($epay_config['alipay_enabled']) || !empty($epay_config['wxpay_enabled']) || !empty($epay_config['usdt_enabled']));

$wechat_ready = $wechat_config &&
                isset($wechat_config['enabled']) && $wechat_config['enabled'] &&
                !empty($wechat_config['appid']) &&
                !empty($wechat_config['mch_id']) &&
                !empty($wechat_config['api_key']) &&
                strpos($wechat_config['appid'], '填写') === false &&
                strpos($wechat_config['mch_id'], '填写') === false &&
                strpos($wechat_config['api_key'], '填写') === false;

// 获取系统配置 - 优惠码功能是否启用
try {
    $stmt = $pdo->query("SELECT * FROM system_config WHERE `key` = 'coupon_enabled' LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    $coupon_enabled = isset($config['value']) ? (bool)$config['value'] : false;
} catch (Exception $e) {
    $coupon_enabled = false;
}

// 获取订单信息
$id       = isset($_GET['id']) ? intval($_GET['id']) : 1;
$nickname = isset($_GET['nickname']) ? trim($_GET['nickname']) : '';
$email    = isset($_GET['email']) ? trim($_GET['email']) : '';
$quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
$price    = isset($_GET['price']) ? floatval($_GET['price']) : 0;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Select Payment Method - AjieShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 60px 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .payment-container { max-width: 600px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 5px 30px rgba(0,0,0,0.1); padding: 50px; }
        .payment-title { font-size: 2rem; font-weight: 700; color: #2c3e50; margin-bottom: 10px; }
        .payment-subtitle { color: #7f8c8d; margin-bottom: 40px; font-size: 1.1rem; }
        .order-info { background: #f5f7fa; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
        .order-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .order-label { color: #7f8c8d; font-weight: 600; }
        .order-value { color: #2c3e50; font-weight: 700; }
        .amount-total { font-size: 1.5rem; color: #667eea; font-weight: 700; }
        .payment-methods { margin-bottom: 30px; }
        .payment-method { padding: 20px; border: 2px solid #ecf0f1; border-radius: 10px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s ease; }
        .payment-method:hover { border-color: #667eea; background: #f5f7fa; }
        .payment-method.active { border-color: #667eea; background: #f0f4ff; }
        .payment-method input { margin-right: 10px; cursor: pointer; }
        .payment-method-title { font-weight: 700; color: #2c3e50; margin-bottom: 5px; }
        .payment-method-desc { font-size: 0.9rem; color: #7f8c8d; }
        .payment-btn { width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease; }
        .payment-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3); }
        .payment-btn:disabled { background: #ccc; cursor: not-allowed; transform: none; }
        .alert-info { background: #e3f2fd; border-left: 4px solid #667eea; }
        .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .back-link { display: inline-block; margin-bottom: 30px; color: #667eea; text-decoration: none; font-weight: 600; }
        .back-link:hover { color: #764ba2; }
        .no-payment { text-align: center; color: #7f8c8d; }
        .config-btn { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="payment-container">
        <a href="/" class="back-link"><i class="fas fa-arrow-left"></i> Back to Home</a>
        
        <h1 class="payment-title"><i class="fas fa-credit-card"></i> Payment Method</h1>
        <p class="payment-subtitle">Select your preferred payment method</p>

        <!-- 订单信息 -->
        <div class="order-info">
            <div class="order-row">
                <span class="order-label">Product ID:</span>
                <span class="order-value"><?php echo SafeOutput::text($id); ?></span>
            </div>
            <div class="order-row">
                <span class="order-label">Quantity:</span>
                <span class="order-value"><?php echo SafeOutput::text($quantity); ?></span>
            </div>
            <div class="order-row">
                <span class="order-label">Unit Price:</span>
                <span class="order-value">¥<?php echo SafeOutput::text($price); ?></span>
            </div>
            <hr style="margin: 10px 0;">
            <div class="order-row">
                <span class="order-label" style="font-size: 1.1rem;">Total Amount:</span>
                <span class="amount-total">¥<?php echo number_format($price * $quantity, 2); ?></span>
            </div>
        </div>

        <!-- 支付方式选择 -->
        <div class="payment-methods">
            <form method="post" id="payForm">
                <!-- 隐藏字段 -->
                <input type="hidden" name="id" value="<?php echo SafeOutput::attr($id); ?>">
                <input type="hidden" name="nickname" value="<?php echo SafeOutput::attr($nickname); ?>">
                <input type="hidden" name="email" value="<?php echo SafeOutput::attr($email); ?>">
                <input type="hidden" name="quantity" value="<?php echo SafeOutput::attr($quantity); ?>">
                <input type="hidden" name="price" value="<?php echo SafeOutput::attr($price); ?>">
                <input type="hidden" name="payment_method" id="payment_method" value="">

                <?php if (!$epay_ready && !$wechat_ready): ?>
                    <!-- 没有任何支付方式配置 -->
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>No Payment Methods Available</strong><br>
                        The store hasn't configured any payment methods yet. Please contact the administrator.
                        <div class="config-btn">
                            <a href="admin/login.php" class="btn btn-sm btn-primary">Go to Admin Panel</a>
                        </div>
                    </div>

                <?php else: ?>

                    <!-- 易支付 -->
                    <?php if ($epay_ready): ?>
                    <div class="payment-method" onclick="selectPayment('epay', this)">
                        <div>
                            <input type="radio" name="method_choice" value="epay" id="method_epay">
                            <label for="method_epay" style="cursor: pointer; margin: 0;">
                                <div class="payment-method-title"><i class="fas fa-money-bill-wave"></i> Easy Payment (E-Pay)</div>
                                <div class="payment-method-desc">Support Alipay, WeChat Pay, USDT</div>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 微信支付 -->
                    <?php if ($wechat_ready): ?>
                    <div class="payment-method" onclick="selectPayment('wechat', this)">
                        <div>
                            <input type="radio" name="method_choice" value="wechat" id="method_wechat">
                            <label for="method_wechat" style="cursor: pointer; margin: 0;">
                                <div class="payment-method-title"><i class="fab fa-weixin"></i> WeChat Official Payment</div>
                                <div class="payment-method-desc">Direct WeChat payment integration</div>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 提示信息 -->
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle"></i>
                        Your information is secure. We never store your payment details.
                    </div>

                    <!-- 提交按钮 -->
                    <button type="submit" class="payment-btn" id="submitBtn" disabled>
                        <i class="fas fa-lock"></i> Proceed to Payment
                    </button>

                <?php endif; ?>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPayment(method, element) {
            // 取消其他选择
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('active');
            });
            
            // 选中当前方法
            element.classList.add('active');
            document.getElementById('payment_method').value = method;
            document.getElementById('submitBtn').disabled = false;
            
            // 选中对应的 radio
            document.getElementById('method_' + method).checked = true;
        }

        // 设置默认选项
        document.addEventListener('DOMContentLoaded', function() {
            const radioButtons = document.querySelectorAll('input[name="method_choice"]');
            if (radioButtons.length > 0) {
                radioButtons[0].closest('.payment-method').click();
            }
        });

        // 表单提交
        document.getElementById('payForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const method = document.getElementById('payment_method').value;
            const form = this;
            
            if (method === 'wechat') {
                form.action = 'order.php';
            } else if (method === 'epay') {
                form.action = 'rainbow_pay.php';
            }
            
            form.submit();
        });
    </script>
</body>
</html>
