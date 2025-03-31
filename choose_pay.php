<?php
/**
 * 支付方式选择系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 提供支付方式选择界面，支持易支付和原生微信支付，
 * 处理订单参数，显示支付金额，并集成优惠码验证功能。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

require_once 'db.php';
require_once 'clean_orders.php';

// 获取支付配置
$stmt = $pdo->query("SELECT * FROM epay_config LIMIT 1");
$epay_config = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM wechat_config LIMIT 1");
$wechat_config = $stmt->fetch(PDO::FETCH_ASSOC);

// 获取系统配置 - 优惠码功能是否启用
try {
    $stmt = $pdo->query("SELECT * FROM system_config WHERE `key` = 'coupon_enabled' LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    $coupon_enabled = isset($config['value']) ? (bool)$config['value'] : false;
} catch (Exception $e) {
    // 如果表不存在或其他错误，默认禁用优惠码功能
    $coupon_enabled = false;
}

// 获取从 product.php 传递的订单信息
$id       = isset($_GET['id']) ? intval($_GET['id']) : 1;
$nickname = isset($_GET['nickname']) ? trim($_GET['nickname']) : '匿名';
$email    = isset($_GET['email']) ? trim($_GET['email']) : 'test@example.com';
$quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
$price    = isset($_GET['price']) ? floatval($_GET['price']) : 4.51;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>选择支付方式</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- 引入 Bootstrap 4 CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
  <style>
      body { padding-top: 70px; }
      .container { max-width: 600px; }
      .btn-pay { width: 100%; margin-bottom: 20px; font-size: 1.25rem; }
  </style>
</head>
<body>
<div class="container text-center">
    <h1 class="my-4">请选择支付方式</h1>
    <p>订单信息：<br>
       产品ID: <?php echo $id; ?>, 昵称: <?php echo htmlspecialchars($nickname); ?>, 邮箱: <?php echo htmlspecialchars($email); ?>, 数量: <?php echo $quantity; ?>, 单价: <?php echo $price; ?>
    </p>
    <div id="price_display">
        <p>应付金额: <span id="total_amount"><?php echo number_format($price * $quantity, 2); ?></span> 元</p>
    </div>
    <!-- 支付方式选择表单：将所有订单信息和支付方式提交给对应页面 -->
    <form method="get" id="payForm">
        <!-- 隐藏字段传递订单信息 -->
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="nickname" value="<?php echo htmlspecialchars($nickname); ?>">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <input type="hidden" name="quantity" value="<?php echo $quantity; ?>">
        <input type="hidden" name="price" value="<?php echo $price; ?>">
        
        <!-- 优惠码输入区域 -->
        <?php if ($coupon_enabled): ?>
        <div class="card mb-4">
            <div class="card-header bg-light">使用优惠码</div>
            <div class="card-body">
                <div class="form-group">
                    <div class="input-group">
                        <input type="text" class="form-control" id="coupon_code" placeholder="请输入优惠码">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-primary" id="check_coupon">验证</button>
                        </div>
                    </div>
                    <div id="coupon_message" class="mt-2"></div>
                </div>
                <!-- 优惠码隐藏字段 -->
                <input type="hidden" name="coupon_id" id="coupon_id" value="">
                <input type="hidden" name="coupon_code_hidden" id="coupon_code_hidden" value="">
                <input type="hidden" name="coupon_amount" id="coupon_amount" value="0">
            </div>
        </div>
        <?php endif; ?>
        <!-- 易支付支付方式选择部分 -->
        <?php if ($epay_config['alipay_enabled'] || $epay_config['wxpay_enabled'] || $epay_config['usdt_enabled']): ?>
        <div class="form-group">
            <label>易支付支付方式选择：</label><br>
            <?php if ($epay_config['alipay_enabled']): ?>
            <label><input type="radio" name="type" value="alipay" checked> 支付宝</label>&nbsp;
            <?php endif; ?>
            <?php if ($epay_config['wxpay_enabled']): ?>
            <label><input type="radio" name="type" value="wxpay" <?php echo !$epay_config['alipay_enabled'] ? 'checked' : ''; ?>> 微信支付</label>&nbsp;
            <?php endif; ?>
            <?php if ($epay_config['usdt_enabled']): ?>
            <label><input type="radio" name="type" value="usdt" <?php echo !$epay_config['alipay_enabled'] && !$epay_config['wxpay_enabled'] ? 'checked' : ''; ?>> USDT</label>&nbsp;
            <?php endif; ?>
        </div>
        <!-- 按钮区域 -->
        <button type="button" class="btn btn-primary btn-pay" onclick="submitPay('rainbow')">易支付接口</button>
        <?php endif; ?>
        <?php if ($wechat_config['enabled']): ?>
        <button type="button" class="btn btn-success btn-pay" onclick="submitPay('wechat')">微信官方支付</button>
        <?php endif; ?>
    </form>
    <p><a href="index.php" class="btn btn-secondary">返回首页</a></p>
</div>
<script>
    // 根据用户点击的按钮，跳转到对应的支付处理页面
    function submitPay(method) {
        var form = document.getElementById('payForm');
        if(method === 'rainbow'){
            // 易支付页面直接采用表单中选择的支付方式
            form.action = "rainbow_pay.php";
        } else if(method === 'wechat'){
            // 微信官方支付：强制覆盖支付方式为 "wxpay"
            form.action = "order.php";
            // 检查是否已有隐藏字段名为 type，若有则修改，否则添加一个新的隐藏字段
            var hiddenType = document.querySelector("input[name='type'][type='hidden']");
            if (hiddenType) {
                hiddenType.value = "wxpay";
            } else {
                // 如果没有找到隐藏字段，则创建一个
                var input = document.createElement("input");
                input.type = "hidden";
                input.name = "type";
                input.value = "wxpay";
                form.appendChild(input);
            }
        }
        // 提交表单
        form.submit();
    }
</script>

<?php if ($coupon_enabled): ?>
<!-- 优惠码验证脚本 -->
<script>
    document.getElementById('check_coupon').addEventListener('click', function() {
        var code = document.getElementById('coupon_code').value.trim();
        var price = <?php echo $price; ?>;
        var quantity = <?php echo $quantity; ?>;
        var totalAmount = price * quantity;
        var messageDiv = document.getElementById('coupon_message');
        
        if (!code) {
            messageDiv.innerHTML = '<div class="alert alert-warning">请输入优惠码</div>';
            return;
        }
        
        // 发送AJAX请求验证优惠码
        fetch('check_coupon.php?code=' + encodeURIComponent(code) + '&amount=' + totalAmount)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 优惠码有效
                    var discountAmount = data.data.discount_amount;
                    messageDiv.innerHTML = '<div class="alert alert-success">优惠码有效，可抵扣 ¥' + discountAmount.toFixed(2) + '</div>';
                    
                    // 设置隐藏字段的值
                    document.getElementById('coupon_id').value = data.data.id;
                    document.getElementById('coupon_code_hidden').value = data.data.code;
                    document.getElementById('coupon_amount').value = discountAmount;
                    
                    // 更新显示的应付金额
                    var finalAmount = totalAmount - discountAmount;
                    if (finalAmount < 0) finalAmount = 0;
                    document.getElementById('total_amount').innerText = finalAmount.toFixed(2);
                } else {
                    // 优惠码无效
                    messageDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                    
                    // 清空隐藏字段
                    document.getElementById('coupon_id').value = '';
                    document.getElementById('coupon_code_hidden').value = '';
                    document.getElementById('coupon_amount').value = '0';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="alert alert-danger">验证优惠码时出错</div>';
                console.error('Error:', error);
            });
    });
</script>
<?php endif; ?>

<!-- 引入 jQuery 和 Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>