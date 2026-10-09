<?php require_once __DIR__ . '/../../storefront/theme.php'; $admin_brand = sf_brand(); ?>
<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars(($page_title ?? '管理后台') . ' · ' . $admin_brand['name']); ?></title>
  <meta name="robots" content="noindex">
  <!-- Bootstrap 4 CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous" referrerpolicy="no-referrer">
  <!-- Font Awesome 图标 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous" referrerpolicy="no-referrer">
  <!-- 自定义样式 -->
  <link rel="stylesheet" href="/admin/css/admin-style.css?v=20261010-8">
  <!-- Chart.js 库 -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" integrity="sha384-JUh163oCRItcbPme8pYnROHQMC6fNKTBWtRG3I3I0erJkzNgL7uxKlNwcrcFKeqF" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- 侧边栏 -->
    <nav class="sidebar" aria-label="后台导航">
      <div class="sidebar-sticky">
        <div class="admin-brand"><strong><?php echo htmlspecialchars($admin_brand['name']); ?></strong><span>管理后台 <a href="../index.php" target="_blank" rel="noopener">查看商城 ↗</a></span></div>
        <ul class="nav flex-column">
          <!-- 概览 -->
          <li class="nav-item">
            <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">概览</h6>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
              <i class="fas fa-tachometer-alt"></i>仪表盘
            </a>
          </li>

          <!-- 商品管理 -->
          <li class="nav-item">
            <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">商品管理</h6>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'products' ? 'active' : ''; ?>" href="products.php">
              <i class="fas fa-box"></i>商品管理
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'menus' ? 'active' : ''; ?>" href="menus.php">
              <i class="fas fa-list"></i>编辑菜单
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'manage_card_tasks' ? 'active' : ''; ?>" href="manage_card_tasks.php">
              <i class="fas fa-tasks"></i>自动发卡任务管理
            </a>
          </li>

          <!-- 订单管理 -->
          <li class="nav-item">
            <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">订单管理</h6>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'order_details' ? 'active' : ''; ?>" href="order_details.php">
              <i class="fas fa-shopping-cart"></i>订单数据
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'coupons' ? 'active' : ''; ?>" href="coupons.php">
              <i class="fas fa-ticket-alt"></i>优惠码管理
            </a>
          </li>

          <!-- 支付配置 -->
          <li class="nav-item">
            <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">支付配置</h6>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'wechat_config' ? 'active' : ''; ?>" href="wechat_config.php">
              <i class="fab fa-weixin"></i>微信支付配置
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'epay_config' ? 'active' : ''; ?>" href="epay_config.php">
              <i class="fas fa-credit-card"></i>易支付配置
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'storefront_themes' ? 'active' : ''; ?>" href="storefront_themes.php">
              <i class="fas fa-palette"></i>商城模板
            </a>
          </li>

          <!-- 系统设置 -->
          <li class="nav-item">
            <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">系统设置</h6>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'email_settings' ? 'active' : ''; ?>" href="email_settings.php">
              <i class="fas fa-envelope"></i>邮箱配置
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'telegram_config' ? 'active' : ''; ?>" href="telegram_config.php">
              <i class="fab fa-telegram"></i>TG机器人配置
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'wxpusher_config' ? 'active' : ''; ?>" href="wxpusher_config.php">
              <i class="fas fa-bell"></i>WxPusher配置
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'ip_limits' ? 'active' : ''; ?>" href="ip_limits.php">
              <i class="fas fa-shield-alt"></i>IP限制管理
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">
              <i class="fas fa-sign-out-alt"></i>退出登录
            </a>
          </li>
        </ul>
      </div>
    </nav>
    <!-- 主内容区域 -->
    <main role="main" class="content">