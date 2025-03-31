<?php
// pay_success.php
$order_no = isset($_GET['order_no']) ? $_GET['order_no'] : '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <title>支付成功</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- 引入 Bootstrap 4 CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
  <!-- 引入 Font Awesome 用于显示图标 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <style>
    body {
      background: #f8f9fa;
      padding-top: 70px;
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    }
    .result-box {
      max-width: 500px;
      margin: 30px auto;
    }
    .result-card {
      border: none;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .result-card .card-body {
      padding: 30px;
    }
    .result-icon {
      font-size: 60px;
      color: #28a745;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-success fixed-top">
    <div class="container">
      <a class="navbar-brand" href="index.php">阿杰的站</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" 
              aria-controls="navbarNav" aria-expanded="false" aria-label="切换导航">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>
  </nav>

  <div class="container result-box text-center">
    <div class="card result-card">
      <div class="card-body">
        <div class="result-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <h2 class="card-title text-success">支付成功！</h2>
        <p class="card-text">订单号：<?php echo htmlspecialchars($order_no); ?></p>
        <a href="index.php" class="btn btn-primary btn-lg mt-3">返回首页</a>
      </div>
    </div>
  </div>

  <!-- 引入 jQuery 和 Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>