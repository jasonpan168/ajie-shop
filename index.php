<?php
/**
 * 商城系统首页
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 展示商城系统的首页，包含商品列表展示、订单查询等功能。
 * 使用Bootstrap 4实现响应式布局，提供良好的移动端支持。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

// 检查是否已安装
if (!file_exists(__DIR__ . '/install.lock')) {
    header('Location: install/');
    exit;
}

require_once 'config.php';
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <title>阿杰付款业务</title>
  <!-- 引入 Bootstrap 4 CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    /* 自定义样式 */
    body {
      padding-top: 70px;
    }
    .navbar-brand {
      font-weight: bold;
    }
    /* 将订单查询表单设为 inline 布局 */
    .order-query-form {
      display: flex;
      align-items: center;
    }
    .order-query-form input {
      margin-right: 5px;
    }
    /* 商品卡片样式 */
    .card {
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    .card-img-top {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }
    .card-body {
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    .card-text:last-of-type {
      margin-top: auto;
    }
    .btn-success {
      width: 100%;
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <!-- 导航栏 -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container">
      <a class="navbar-brand" href="index.php">阿杰离岸</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" 
              aria-controls="navbarNav" aria-expanded="false" aria-label="切换导航">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <!-- 左侧菜单项：动态加载菜单项 -->
        <ul class="navbar-nav mr-auto">
          <?php
            // 从 menus 表中读取菜单项，按 sort_order 排序
            $stmt = $pdo->query("SELECT * FROM menus ORDER BY sort_order ASC");
            $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($menus) {
              foreach ($menus as $menu) {
                echo '<li class="nav-item"><a class="nav-link" href="'.htmlspecialchars($menu['url']).'">' . htmlspecialchars($menu['name']) . '</a></li>';
              }
            } else {
                // 如果没有菜单项，则显示默认菜单
                echo '<li class="nav-item active"><a class="nav-link" href="index.php">首页 <span class="sr-only">(当前)</span></a></li>';
            }
          ?>
        </ul>
        <!-- 右侧内嵌订单查询表单和管理后台链接 -->
        <ul class="navbar-nav">
          <li class="nav-item">
            <form id="orderQueryForm" class="form-inline order-query-form">
              <input type="text" class="form-control" id="orderNo" placeholder="订单号" aria-label="订单号">
              <button type="submit" class="btn btn-outline-light">查询订单</button>
            </form>
          </li>
          <li class="nav-item ml-3">
            <a class="nav-link" href="admin/login.php" target="_blank">管理后台</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- 主内容区域：商品展示 -->
  <div class="container">
    <h1 class="my-4 text-center">注意观看说明</h1>
    <div class="row">
      <?php
      $stmt = $pdo->query("SELECT * FROM products WHERE status = 1 ORDER BY sort_order ASC");
      while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
      ?>
      <div class="col-md-4 col-sm-6 mb-4">
        <div class="card h-100">
          <img src="<?php echo htmlspecialchars($product['cover']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['title']); ?>">
          <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h5>
            <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
            <p class="card-text font-weight-bold">价格: ￥<?php echo htmlspecialchars($product['price']); ?></p>
            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-success">购买</a>
          </div>
        </div>
      </div>
      <?php } ?>
    </div>
  </div>

  <!-- 引入 jQuery 和 Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
  <!-- 订单查询 AJAX 脚本 -->
  <script>
    document.getElementById('orderQueryForm').addEventListener('submit', function(e) {
      e.preventDefault();
      var orderNo = document.getElementById('orderNo').value.trim();
      if (!orderNo) {
        alert("请输入订单号！");
        return;
      }
      fetch('order_query.php?order_no=' + encodeURIComponent(orderNo))
        .then(response => response.json())
        .then(data => {
          if(data.error){
            alert(data.error);
          } else {
            // 拼接显示订单信息：订单号、商品名称、下单人、邮箱、数量、状态、金额和下单时间
            var msg = "订单号：" + data.order_no + "\n" +
                      "商品名称：" + data.product_title + "\n" +
                      "下单人：" + data.nickname + "\n" +
                      "邮箱：" + data.email + "\n" +
                      "数量：" + data.quantity + "\n" +
                      "状态：" + data.status + "\n" +
                      "金额：￥" + data.amount + "\n" +
                      "下单时间：" + data.created_at;
            alert(msg);
          }
        })
        .catch(err => {
          console.error(err);
          alert("查询失败，请稍后重试！");
        });
    });
  </script>
</body>
</html>