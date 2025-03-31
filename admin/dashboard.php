<?php
/**
 * 管理后台首页系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 展示商城系统的运营数据统计，包括商品总数、订单数量、
 * 销售额等关键指标的实时数据展示。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require_once '../db.php';

// 查询上架数据：商品总数
$stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

// 查询商品库存总量
$stmt = $pdo->query("SELECT SUM(stock) as total_stock FROM products");
$total_stock = $stmt->fetch(PDO::FETCH_ASSOC)['total_stock'];

// 查询购买数据：已支付订单总数
$stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders WHERE status = 'paid'");
$total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];

// 查询待支付订单总数
$stmt = $pdo->query("SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'pending'");
$pending_orders = $stmt->fetch(PDO::FETCH_ASSOC)['pending_orders'];

// 查询今日订单走势（按小时分组）
$stmt = $pdo->query("SELECT HOUR(created_at) as hour, COUNT(*) as order_count FROM orders WHERE DATE(created_at) = CURDATE() GROUP BY hour ORDER BY hour ASC");
$trend_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
// 构造一个24小时的数组（0点到23点），默认值为 0
$order_trend = array_fill(0, 24, 0);
foreach ($trend_data as $row) {
    $hour = intval($row['hour']);
    $order_trend[$hour] = intval($row['order_count']);
}
// 构造小时标签，如 "0点", "1点", ... "23点"
$labels = [];
for ($i = 0; $i < 24; $i++) {
    $labels[] = $i . "点";
}
?>
<?php
$page_title = '仪表盘';
$current_page = 'dashboard';
require_once 'includes/header.php';
?>
    <!-- 主内容区域 -->
    <main role="main" class="content">
      <h1>仪表盘</h1>
      
      <!-- 数据面板 -->
      <div class="data-panel row">
        <!-- 商品总数 -->
        <div class="col-md-3">
          <div class="card text-white bg-info">
            <div class="card-body">
              <h5 class="card-title">商品总数</h5>
              <p class="card-text" style="font-size: 1.5rem;"><?php echo $total_products; ?></p>
            </div>
          </div>
        </div>
        <!-- 商品库存 -->
        <div class="col-md-3">
          <div class="card text-white bg-success">
            <div class="card-body">
              <h5 class="card-title">库存总量</h5>
              <p class="card-text" style="font-size: 1.5rem;"><?php echo $total_stock; ?></p>
            </div>
          </div>
        </div>
        <!-- 已支付订单总数 -->
        <div class="col-md-3">
          <div class="card text-white bg-warning">
            <div class="card-body">
              <h5 class="card-title">已支付订单</h5>
              <p class="card-text" style="font-size: 1.5rem;"><?php echo $total_orders; ?></p>
            </div>
          </div>
        </div>
        <!-- 待支付订单总数 -->
        <div class="col-md-3">
          <div class="card text-white bg-danger">
            <div class="card-body">
              <h5 class="card-title">待支付订单</h5>
              <p class="card-text" style="font-size: 1.5rem;"><?php echo $pending_orders; ?></p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- 今日订单走势折线图 -->
      <div class="card">
        <div class="card-header">
          今日订单走势（小时统计）
        </div>
        <div class="card-body">
          <canvas id="orderTrendChart" width="400" height="150"></canvas>
        </div>
      </div>
      
      <!-- 管理员账户修改 -->
      <div class="card mt-4">
        <div class="card-header">
          管理员账户设置
        </div>
        <div class="card-body">
          <?php
          if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin'])) {
              $new_username = trim($_POST['new_username']);
              $new_password = trim($_POST['new_password']);
              $confirm_password = trim($_POST['confirm_password']);
              $current_password = trim($_POST['current_password']);
              
              // 验证当前管理员
              $stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
              $stmt->execute([$_SESSION['admin']]);
              $admin = $stmt->fetch(PDO::FETCH_ASSOC);
              
              if (!$admin || !password_verify($current_password, $admin['password'])) {
                  echo '<div class="alert alert-danger">当前密码验证失败</div>';
              } else if ($new_password !== $confirm_password) {
                  echo '<div class="alert alert-danger">新密码与确认密码不匹配</div>';
              } else {
                  // 更新管理员信息
                  $update_data = [];
                  $update_fields = [];
                  
                  if (!empty($new_username)) {
                      $update_fields[] = "username = ?";
                      $update_data[] = $new_username;
                  }
                  
                  if (!empty($new_password)) {
                      $update_fields[] = "password = ?";
                      $update_data[] = password_hash($new_password, PASSWORD_DEFAULT);
                  }
                  
                  if (!empty($update_fields)) {
                      $update_data[] = $_SESSION['admin'];
                      $sql = "UPDATE admin SET " . implode(", ", $update_fields) . " WHERE id = ?";
                      $stmt = $pdo->prepare($sql);
                      if ($stmt->execute($update_data)) {
                          echo '<div class="alert alert-success">管理员信息更新成功</div>';
                      } else {
                          echo '<div class="alert alert-danger">更新失败，请稍后重试</div>';
                      }
                  }
              }
          }
          ?>
          <form method="post" action="">
            <div class="form-group">
              <label for="new_username">新用户名</label>
              <input type="text" class="form-control" id="new_username" name="new_username" placeholder="留空表示不修改">
            </div>
            <div class="form-group">
              <label for="new_password">新密码</label>
              <input type="password" class="form-control" id="new_password" name="new_password" placeholder="留空表示不修改">
            </div>
            <div class="form-group">
              <label for="confirm_password">确认新密码</label>
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="再次输入新密码">
            </div>
            <div class="form-group">
              <label for="current_password">当前密码</label>
              <input type="password" class="form-control" id="current_password" name="current_password" required>
              <small class="form-text text-muted">请输入当前密码以验证身份</small>
            </div>
            <button type="submit" name="update_admin" class="btn btn-primary">更新信息</button>
          </form>
        </div>
      </div>
      
    </main>
  </div>
</div>
<!-- Chart.js 脚本生成订单走势折线图 -->
<script>
  var ctx = document.getElementById('orderTrendChart').getContext('2d');
  var orderTrendChart = new Chart(ctx, {
      type: 'line',
      data: {
          labels: <?php echo json_encode($labels); ?>,
          datasets: [{
              label: '订单数量',
              data: <?php echo json_encode($order_trend); ?>,
              backgroundColor: 'rgba(54, 162, 235, 0.2)',
              borderColor: 'rgba(54, 162, 235, 1)',
              borderWidth: 2,
              fill: true,
              lineTension: 0.1,
              pointRadius: 3
          }]
      },
      options: {
          scales: {
              y: {
                  beginAtZero: true,
                  ticks: {
                      stepSize: 1
                  }
              }
          },
          plugins: {
              legend: {
                  display: true,
                  position: 'top'
              }
          },
          responsive: true,
          maintainAspectRatio: false
      }
  });
</script>
<!-- 引入 jQuery 和 Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>