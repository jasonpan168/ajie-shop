<?php
/**
 * 支付结果返回处理
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 处理支付完成后用户同步返回页面，显示支付结果和订单信息，
 * 为用户提供清晰的支付状态反馈。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

// 从 GET 参数中获取订单号及其他信息（根据易支付回传的参数而定）
$order_no = isset($_GET['out_trade_no']) ? $_GET['out_trade_no'] : '';
$status = isset($_GET['trade_status']) ? $_GET['trade_status'] : '';
?>
<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <title>支付结果</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- 引入 Bootstrap 4 CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
</head>
<body>
  <div class="container" style="margin-top: 70px;">
    <h1>支付结果</h1>
    <?php if ($status == "TRADE_SUCCESS"): ?>
      <div class="alert alert-success">
        支付成功！订单号：<?php echo htmlspecialchars($order_no); ?>
      </div>
    <?php else: ?>
      <div class="alert alert-danger">
        支付失败或未完成。订单号：<?php echo htmlspecialchars($order_no); ?>
      </div>
    <?php endif; ?>
    <a href="index.php" class="btn btn-primary">返回首页</a>
  </div>
</body>
</html>