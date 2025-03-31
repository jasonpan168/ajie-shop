<?php
// order_display.php
session_start();
if (!isset($_SESSION['order_data'])) {
    die("没有订单数据，请重新下单。");
}
$order_no = $_SESSION['order_data']['order_no'];
$code_url = $_SESSION['order_data']['code_url'];
// 清除订单数据，避免刷新重复使用
unset($_SESSION['order_data']);

// 引入 phpqrcode 库（确保 phpqrcode 文件夹位于网站根目录下）
require_once 'phpqrcode/qrlib.php';

// 利用输出缓冲区生成二维码图片，并进行 Base64 编码
ob_start();
QRcode::png($code_url, null, QR_ECLEVEL_L, 6);
$imageData = base64_encode(ob_get_contents());
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>微信扫码支付</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- 引入 Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <!-- 引入 Font Awesome 用于显示微信图标 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background: #f2f2f2;
        }
        .card {
            max-width: 450px;
            margin: 30px auto;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card-header {
            background: #09BB07; /* 微信绿色 */
            color: #fff;
            font-size: 20px;
            text-align: center;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            padding: 15px;
        }
        .card-body {
            text-align: center;
            padding: 20px;
        }
        .qr-code {
            margin: 20px 0;
        }
        .qr-code img {
            width: 230px;
            height: 230px;
        }
        .order-no {
            font-size: 16px;
            margin-top: 10px;
            color: #555;
        }
        .instruction {
            margin-top: 20px;
            font-size: 14px;
            color: #777;
        }
        .btn-copy {
            margin-top: 15px;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <i class="fab fa-weixin"></i> 微信扫码支付
    </div>
    <div class="card-body">
        <div class="qr-code">
            <img src="data:image/png;base64,<?php echo $imageData; ?>" alt="微信支付二维码" class="img-fluid">
        </div>
        <div class="order-no">
            订单号：<?php echo htmlspecialchars($order_no); ?>
        </div>
        <div class="instruction">
            <p>请使用微信扫描二维码完成支付</p>
            <p>若无法识别二维码，请点击下方按钮复制支付链接，并在微信中打开付款</p>
        </div>
        <button id="copyBtn" class="btn btn-success btn-lg btn-copy">复制支付链接</button>
    </div>
</div>

<script>
// 订单状态轮询，每 5 秒检测一次支付结果
var orderNo = "<?php echo $order_no; ?>";
var checkInterval = setInterval(function() {
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "check_order.php?order_no=" + orderNo, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var data = JSON.parse(xhr.responseText);
            if (data.status === "paid") {
                clearInterval(checkInterval);
                alert("支付成功！");
                window.location.href = "pay_success.php?order_no=" + orderNo;
            }
        }
    };
    xhr.send();
}, 5000);

// 复制支付链接功能
document.getElementById('copyBtn').addEventListener('click', function() {
    var link = "<?php echo $code_url; ?>";
    if (navigator.clipboard) {
        navigator.clipboard.writeText(link).then(function() {
            alert("支付链接已复制，请打开微信并粘贴进行支付。");
        }, function(err) {
            alert("复制失败，请手动复制支付链接：" + link);
        });
    } else {
        // 如果浏览器不支持 Clipboard API，则提示手动复制
        prompt("复制支付链接：", link);
    }
});
</script>

<!-- 引入 jQuery 和 Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>