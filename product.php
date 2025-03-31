<?php
/**
 * 商品详情展示系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 展示商品详情页面，包括商品标题、价格、描述等信息，
 * 并提供下单入口，支持商品预览和购买功能。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

require_once 'db.php';

if (!isset($_GET['id'])) {
    die("产品不存在");
}
$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    die("产品不存在");
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($product['title']); ?> - 虚拟商品商城</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- 引入 Bootstrap 4 CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
  <style>
    /* 保持原有标题和副标题样式 */
    .product-header {
      margin-top: 20px;
      font-size: 2rem;
      font-weight: bold;
      margin-bottom: 15px;
    }
    .product-subtitle {
      color: #888;
      font-size: 1.2rem;
      margin-bottom: 25px;
    }
    .product-detail {
      margin-top: 40px;
      padding-top: 10px;
      border-top: 1px solid #ddd;
    }
    .detail-img {
      max-width: 100%;
      height: auto;
      display: block;
      margin: 15px 0;
    }
    /* 左侧图片容器 */
    .cover-container {
      flex: 1;
      min-height: 280px;
      overflow: hidden;
      border-radius: 4px;
      margin-bottom: 15px;
    }
    .cover-container img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    /* 右侧下单区域 */
    .info-section {
      flex: 1;
    }
    /* 桌面端左右等高布局 */
    @media (min-width: 768px) {
      .equal-height-row {
        display: flex;
        align-items: stretch;
      }
      .equal-height-row > .col-md-6 {
        display: flex;
        flex-direction: column;
      }
      .card-body {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 20px;
      }
      .buyer-form {
        margin-top: 20px;
      }
    }
    /* 手机端：堆叠显示，信息区域带边框 */
    @media (max-width: 767.98px) {
      .info-section {
         border: 1px solid #ddd;
         border-radius: 4px;
         background-color: #f9f9f9;
         padding: 20px;
         margin-top: 20px;
      }
    }
  </style>
</head>
<body>
<div class="container">
  <!-- 商品标题 -->
  <h1 class="product-header"><?php echo htmlspecialchars($product['title']); ?></h1>
  <!-- 副标题：简短介绍 -->
  <?php if (!empty($product['description'])): ?>
    <h4 class="product-subtitle"><?php echo htmlspecialchars($product['description']); ?></h4>
  <?php endif; ?>
  
  <!-- 主区域：左右两栏（桌面端左右对齐，手机端自动堆叠） -->
  <div class="row my-4 equal-height-row">
    <!-- 左侧图片区域 -->
    <div class="col-md-6 mb-3 mb-md-0">
      <div class="cover-container flex-fill">
        <img src="<?php echo htmlspecialchars($product['cover']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
      </div>
    </div>
    <!-- 右侧下单区域 -->
    <div class="col-md-6">
      <div class="card info-section flex-fill">
        <div class="card-body">
          <!-- 上部：显示价格与库存 -->
          <div class="product-info">
            <p class="lead">价格：￥<?php echo htmlspecialchars($product['price']); ?></p>
            <p>库存：<?php echo htmlspecialchars($product['stock']); ?></p>
          </div>
          <hr>
          <!-- 下部：下单表单 -->
          <!-- 修改表单的 action 为 choose_pay.php 以便用户选择支付方式 -->
          <div class="buyer-form">
            <form method="get" action="choose_pay.php">
              <!-- 传递必要参数 -->
              <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
              <input type="hidden" name="price" value="<?php echo htmlspecialchars($product['price']); ?>">
              <div class="form-group">
                <label for="nickname">姓名/昵称 <span class="text-danger">*</span></label>
                <input type="text" name="nickname" id="nickname" class="form-control" placeholder="请输入您的姓名或昵称" required>
              </div>
              <div class="form-group">
                <label for="email">邮箱 <span class="text-danger">*</span></label>
                <input type="email" name="email" id="email" class="form-control" placeholder="请输入您的邮箱" required>
              </div>
              <div class="form-group">
                <label for="quantity">数量</label>
                <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1" max="<?php echo htmlspecialchars($product['stock']); ?>" required>
              </div>
              <button type="submit" class="btn btn-success btn-lg btn-block">立即购买</button>
            </form>
          </div>
        </div><!-- card-body -->
      </div><!-- card -->
    </div>
  </div><!-- row -->
  
  <!-- 详细介绍 -->
  <div class="product-detail">
    <h3>详细介绍</h3>
    <?php
      if (isset($product['detail']) && !empty(trim($product['detail']))) {
          $detail = trim($product['detail']);
          // 如果内容中不包含 HTML 标签，则自动将图片链接转换为 <img> 标签
          if ($detail === strip_tags($detail)) {
              $pattern = '/(https?:\/\/[^\s]+?\.(jpg|jpeg|png|gif))/i';
              $detail = preg_replace($pattern, '<img src="$1" alt="详细介绍图片" class="detail-img">', $detail);
              echo nl2br($detail);
          } else {
              echo $detail;
          }
      } else {
          echo "<p>暂无详细介绍。</p>";
      }
    ?>
  </div>
</div>
<!-- 引入 jQuery 和 Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>