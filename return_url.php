<?php
/**
 * return_url.php
 * 彩虹易支付同步返回页面
 *
 * 功能：
 * 1. 通过 SDK 验证返回参数签名。
 * 2. 根据返回的交易状态显示支付成功或失败信息。
 * 3. 显示高端、简洁的页面，包含图标、提示信息及返回首页按钮。
 */
require_once("lib/epay.config.php");
require_once("lib/EpayCore.class.php");

$epay = new EpayCore($epay_config);
$verify_result = $epay->verifyReturn();

$resultMessage = "";
$status = "";
if ($verify_result) {
    $out_trade_no = $_GET['out_trade_no'] ?? '';
    $trade_no     = $_GET['trade_no'] ?? '';
    $trade_status = $_GET['trade_status'] ?? '';
    $type         = $_GET['type'] ?? '';

    if ($trade_status == 'TRADE_SUCCESS') {
        // 此处调用你的订单处理逻辑，例如 updateOrderStatus($out_trade_no, 'paid');
        $resultMessage = "支付成功！订单号：" . htmlspecialchars($out_trade_no);
        $status = "success";
    } else {
        $resultMessage = "交易状态：" . htmlspecialchars($trade_status);
        $status = "failed";
    }
    $resultMessage .= "<br>验证成功";
} else {
    $resultMessage = "验证失败";
    $status = "failed";
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>支付返回页面</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- 引入 Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <!-- 引入 Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            padding-top: 50px;
        }
        .result-container {
            max-width: 600px;
            margin: auto;
        }
        .result-card {
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .result-icon {
            font-size: 80px;
            color: #28a745;
        }
        .result-icon.failed {
            color: #dc3545;
        }
        .result-message {
            font-size: 1.5rem;
            margin-top: 20px;
        }
        .btn-return {
            margin-top: 30px;
        }
    </style>
</head>
<body>
<div class="container result-container">
    <div class="card result-card">
        <div class="card-body text-center">
            <?php if ($status == "success"): ?>
                <i class="fas fa-check-circle result-icon"></i>
            <?php else: ?>
                <i class="fas fa-times-circle result-icon failed"></i>
            <?php endif; ?>
            <div class="result-message">
                <?php echo $resultMessage; ?>
            </div>
            <a href="index.php" class="btn btn-primary btn-return">返回首页</a>
        </div>
    </div>
</div>
<!-- 引入 jQuery 和 Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>