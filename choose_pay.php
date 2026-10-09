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
// 字段以 database.sql 的 epay_config 表为准（该表没有 enabled 列）
$epay_ready = $epay_config &&
              !empty($epay_config['apiurl']) && !empty($epay_config['pid']) && !empty($epay_config['key']) &&
              (!empty($epay_config['alipay_enabled']) || !empty($epay_config['wxpay_enabled']) || !empty($epay_config['usdt_enabled']));
$epay_types = $epay_ready ? array_keys(array_filter([
    'alipay' => !empty($epay_config['alipay_enabled']),
    'wxpay'  => !empty($epay_config['wxpay_enabled']),
    'usdt'   => !empty($epay_config['usdt_enabled']),
])) : [];
$epay_type_names = ['alipay' => '支付宝', 'wxpay' => '微信', 'usdt' => 'USDT'];

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
$id       = isset($_GET['id']) ? intval($_GET['id']) : 0;
$nickname = isset($_GET['nickname']) && is_string($_GET['nickname']) ? trim($_GET['nickname']) : '';
$email    = isset($_GET['email']) && is_string($_GET['email']) ? trim($_GET['email']) : '';
$quantity = isset($_GET['quantity']) ? max(1, intval($_GET['quantity'])) : 1;

// 单价以数据库为准，只用于展示；真正扣款金额在下单时由服务端重新计算
$stmt = $pdo->prepare("SELECT price FROM products WHERE id = ? AND status = 1");
$stmt->execute([$id]);
$db_price = $stmt->fetchColumn();
if ($db_price === false) {
    die('商品不存在或已下架。<a href="index.php">返回首页</a>');
}
$price = (float) $db_price;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>选择支付方式 - AjieShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" integrity="sha384-iw3OoTErCYJJB9mCa8LNS2hbsQ7M3C0EpIsO/H5+EGAkPGc6rk+V8i04oW/K5xq0" crossorigin="anonymous" referrerpolicy="no-referrer">
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
        <a href="/" class="back-link"><i class="fas fa-arrow-left"></i> 返回首页</a>
        
        <h1 class="payment-title"><i class="fas fa-credit-card"></i> 选择支付方式</h1>
        <p class="payment-subtitle">选择您喜欢的支付方式</p>

        <!-- 订单信息 -->
        <div class="order-info">
            <div class="order-row">
                <span class="order-label">产品ID：</span>
                <span class="order-value"><?php echo SafeOutput::text($id); ?></span>
            </div>
            <div class="order-row">
                <span class="order-label">购买数量：</span>
                <span class="order-value"><?php echo SafeOutput::text($quantity); ?></span>
            </div>
            <div class="order-row">
                <span class="order-label">单价：</span>
                <span class="order-value">¥<?php echo SafeOutput::text($price); ?></span>
            </div>
            <hr style="margin: 10px 0;">
            <div class="order-row">
                <span class="order-label" style="font-size: 1.1rem;">应付金额：</span>
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
                <input type="hidden" name="payment_method" id="payment_method" value="">

                <?php if (!$epay_ready && !$wechat_ready): ?>
                    <!-- 没有任何支付方式配置 -->
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>暂无支付方式</strong><br>
                        商店管理员还未配置任何支付方式。请联系管理员。
                        <div class="config-btn">
                            <a href="admin/login.php" class="btn btn-sm btn-primary">进入后台</a>
                        </div>
                    </div>

                <?php else: ?>

                    <!-- 易支付 -->
                    <?php if ($epay_ready): ?>
                    <div class="payment-method" onclick="selectPayment('epay', this)">
                        <div>
                            <input type="radio" name="method_choice" value="epay" id="method_epay">
                            <label for="method_epay" style="cursor: pointer; margin: 0;">
                                <div class="payment-method-title"><i class="fas fa-money-bill-wave"></i> 易支付</div>
                                <div class="payment-method-desc">
                                    <?php foreach ($epay_types as $i => $t): ?>
                                    <label style="margin-right: 12px; cursor: pointer;">
                                        <input type="radio" name="type" value="<?php echo SafeOutput::attr($t); ?>" <?php echo $i === 0 ? 'checked' : ''; ?>>
                                        <?php echo SafeOutput::text($epay_type_names[$t]); ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
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
                                <div class="payment-method-title"><i class="fab fa-weixin"></i> 微信官方支付</div>
                                <div class="payment-method-desc">安全快捷的微信支付</div>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($coupon_enabled): ?>
                    <!-- 优惠码（选填）：只提交优惠码本身，抵扣金额由服务端按数据库计算 -->
                    <div class="form-group mt-3">
                        <label for="coupon_code">优惠码（选填）</label>
                        <input type="text" class="form-control" id="coupon_code" name="coupon_code_hidden" maxlength="50" placeholder="有优惠码请填写，下单时自动抵扣">
                    </div>
                    <?php endif; ?>

                    <!-- 提示信息 -->
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle"></i>
                        您的信息是安全的。我们不会保存任何支付详情。
                    </div>

                    <!-- 提交按钮 -->
                    <button type="submit" class="payment-btn" id="submitBtn" disabled>
                        <i class="fas fa-lock"></i> 进行支付
                    </button>

                <?php endif; ?>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        function selectPayment(method, element) {
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('active');
            });
            
            element.classList.add('active');
            document.getElementById('payment_method').value = method;
            document.getElementById('submitBtn').disabled = false;
            
            document.getElementById('method_' + method).checked = true;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const radioButtons = document.querySelectorAll('input[name="method_choice"]');
            if (radioButtons.length > 0) {
                radioButtons[0].closest('.payment-method').click();
            }
        });

        document.getElementById('payForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const method = document.getElementById('payment_method').value;
            const form = this;
            
            if (method === 'wechat') {
                form.action = 'order.php';
                form.method = 'GET';
                form.querySelectorAll('input[name="type"]').forEach(el => el.disabled = true);
            } else if (method === 'epay') {
                form.action = 'rainbow_pay.php';
                form.method = 'GET';
            }
            
            form.submit();
        });
    </script>
</body>
</html>
