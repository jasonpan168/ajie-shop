<?php
/**
 * 订单管理脚本
 * 
 * 该文件用于管理员查看和管理订单信息，包括订单列表、订单详情和批量删除等功能
 * 
 * @author   Trae
 * @contact  contact@yewu.laikr.com
 * @date     2024-03-29
 */

session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require_once '../db.php';

// 处理批量删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order_ids']) && is_array($_POST['delete_order_ids'])) {
    $ids = $_POST['delete_order_ids'];
    // 为避免 SQL 注入，使用预处理
    $in  = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $pdo->prepare("DELETE FROM orders WHERE order_no IN ($in)");
    $stmt->execute($ids);
    header("Location: order_details.php?success=1");
    exit;
}

// 每页显示订单数
$pageSize = 10;
// 当前页码
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

// 获取日期筛选参数，默认显示今日订单
$defaultDate = date("Y-m-d");
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : $defaultDate;
$end_date   = isset($_GET['end_date'])   ? trim($_GET['end_date'])   : $defaultDate;

// 拼接查询条件：订单创建时间在 start_date 至 end_date 范围内
$where = "WHERE DATE(created_at) BETWEEN :start_date AND :end_date";

// 查询订单总数
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders $where");
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalOrders / $pageSize);
$offset = ($page - 1) * $pageSize;

// 查询订单数据，按创建时间降序排列
// 注意此处新增 pay_type 字段
$stmt = $pdo->prepare("
    SELECT order_no, product_title, nickname, email, quantity, amount, status, pay_type, created_at
    FROM orders
    $where
    ORDER BY created_at DESC
    LIMIT :offset, :pageSize
");
$stmt->bindValue(':start_date', $start_date);
$stmt->bindValue(':end_date', $end_date);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$page_title = '订单明细管理';
$current_page = 'order_details';
require_once 'includes/header.php';
?>
    
    <!-- 主内容区域 -->
    <main role="main" class="content">
      <h1 class="my-4">订单明细管理</h1>
      
      <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">删除成功！</div>
      <?php endif; ?>
      
      <!-- 筛选表单 -->
      <form method="get" class="form-inline mb-4">
        <div class="form-group mr-2">
          <label for="start_date" class="mr-2">开始日期</label>
          <input type="date" name="start_date" id="start_date" class="form-control"
                 value="<?php echo htmlspecialchars($start_date); ?>">
        </div>
        <div class="form-group mr-2">
          <label for="end_date" class="mr-2">结束日期</label>
          <input type="date" name="end_date" id="end_date" class="form-control"
                 value="<?php echo htmlspecialchars($end_date); ?>">
        </div>
        <button type="submit" class="btn btn-primary">筛选</button>
      </form>
      
      <!-- 批量删除表单：包含订单列表复选框 -->
      <form method="post" action="order_details.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&page=<?php echo $page; ?>">
        <table class="table table-bordered table-striped">
          <thead class="thead-dark">
            <tr>
              <th><input type="checkbox" id="checkAll"></th>
              <th>订单号</th>
              <th>商品名称</th>
              <th>下单人</th>
              <th>邮箱</th>
              <th>数量</th>
              <th>金额 (元)</th>
              <th>支付方式</th> <!-- 新增支付方式列 -->
              <th>状态</th>
              <th>下单时间</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($orders): ?>
              <?php
                // 如果你的 pay_type 字段存的值是 alipay, wxpay, qqpay 等
                // 可在这里做一个映射
                $payTypeMap = [
                  'alipay' => '支付宝',
                  'wxpay'  => '微信支付',
                  'qqpay'  => 'QQ钱包',
                  'usdt'   => 'USDT',
                  'bank'   => '云闪付'
                ];

                $statusMap = [
                  'pending'   => '待支付',
                  'paid'      => '已支付',
                  'cancelled' => '已取消'
                ];
              ?>
              <?php foreach ($orders as $order): ?>
                <tr>
                  <td><input type="checkbox" name="delete_order_ids[]" value="<?php echo htmlspecialchars($order['order_no']); ?>"></td>
                  <td><?php echo htmlspecialchars($order['order_no']); ?></td>
                  <td><?php echo htmlspecialchars($order['product_title']); ?></td>
                  <td><?php echo htmlspecialchars($order['nickname']); ?></td>
                  <td><?php echo htmlspecialchars($order['email']); ?></td>
                  <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                  <td><?php echo htmlspecialchars($order['amount']); ?></td>
                  <!-- 显示支付方式 -->
                  <td>
                    <?php
                      $typeVal = $order['pay_type'];
                      echo isset($payTypeMap[$typeVal]) ? $payTypeMap[$typeVal] : htmlspecialchars($typeVal);
                    ?>
                  </td>
                  <!-- 显示订单状态 -->
                  <td>
                    <?php
                      $stVal = $order['status'];
                      echo isset($statusMap[$stVal]) ? $statusMap[$stVal] : htmlspecialchars($stVal);
                    ?>
                  </td>
                  <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="10" class="text-center">暂无订单数据</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        <button type="submit" class="btn btn-danger"
                onclick="return confirm('确定删除所选订单吗？');">删除所选订单</button>
      </form>
      
      <!-- 分页导航 -->
      <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation">
          <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&page=<?php echo $page-1; ?>">上一页</a>
              </li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                <a class="page-link" href="?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
              <li class="page-item">
                <a class="page-link" href="?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&page=<?php echo $page+1; ?>">下一页</a>
              </li>
            <?php endif; ?>
          </ul>
        </nav>
      <?php endif; ?>
    </main>
  </div>
</div>
<!-- 引入 jQuery 和 Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
<script>
  // 全选/取消全选
  document.getElementById('checkAll').addEventListener('click', function() {
    var checkboxes = document.querySelectorAll('input[name="delete_order_ids[]"]');
    for (var i = 0; i < checkboxes.length; i++) {
      checkboxes[i].checked = this.checked;
    }
  });
</script>
</body>
</html>