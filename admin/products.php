<?php
/**
 * 商品管理系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 管理商城系统的商品，包括添加、编辑、删除商品信息，
 * 以及管理商品的库存、价格等信息。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require_once '../db.php';

// 如果提交了添加商品表单，则处理添加
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $cover = trim($_POST['cover']);
    $sort_order = intval($_POST['sort_order']);
    
    $stmt = $pdo->prepare("INSERT INTO products (title, description, price, stock, cover, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $price, $stock, $cover, $sort_order]);
    
    // 重定向避免重复提交
    header("Location: products.php");
    exit;
}

// 如果有删除操作，则处理删除
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: products.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM products ORDER BY sort_order ASC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$page_title = '商品管理';
$current_page = 'products';
require_once 'includes/header.php';
?>
    <!-- 主内容区域 -->
    <main role="main" class="content">
      <h2 class="mt-4">商品管理</h2>
      <!-- 添加商品按钮，点击后展开内嵌表单 -->
      <button class="btn btn-success mb-3" type="button" data-toggle="collapse" data-target="#addProductForm" aria-expanded="false" aria-controls="addProductForm">
        添加商品
      </button>
      <!-- 内嵌添加商品表单 -->
      <div class="collapse collapse-form" id="addProductForm">
        <div class="card card-body">
          <form method="post" action="products.php">
            <input type="hidden" name="add_product" value="1">
            <div class="form-group">
              <label>商品标题</label>
              <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group">
              <label>商品介绍</label>
              <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
              <label>价格</label>
              <input type="number" step="0.01" name="price" class="form-control" required>
            </div>
            <div class="form-group">
              <label>库存</label>
              <input type="number" name="stock" class="form-control" required>
            </div>
            <div class="form-group">
              <label>封面链接</label>
              <input type="text" name="cover" class="form-control" required>
            </div>
            <div class="form-group">
              <label>显示排序（数字越小越靠前）</label>
              <input type="number" name="sort_order" class="form-control" value="0">
            </div>
            <button type="submit" class="btn btn-primary">保存商品</button>
          </form>
        </div>
      </div>
      <!-- 商品列表表格 -->
      <table class="table table-bordered table-striped">
        <thead class="thead-dark">
          <tr>
            <th>排序</th>
            <th>标题</th>
            <th>价格</th>
            <th>库存</th>
            <th>封面</th>
            <th>状态</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): ?>
          <tr>
            <td><?php echo htmlspecialchars($p['sort_order']); ?></td>
            <td><?php echo htmlspecialchars($p['title']); ?></td>
            <td><?php echo htmlspecialchars($p['price']); ?></td>
            <td><?php echo htmlspecialchars($p['stock']); ?></td>
            <td><img src="<?php echo htmlspecialchars($p['cover']); ?>" class="table-img" alt="封面"></td>
            <td>
              <?php if ($p['status'] == 1): ?>
                <span class="badge badge-success">已上架</span>
              <?php else: ?>
                <span class="badge badge-secondary">已下架</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="product_edit.php?id=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm">编辑</a>
              <?php if ($p['status'] == 1): ?>
                <button onclick="updateProductStatus(<?php echo $p['id']; ?>, 0)" class="btn btn-warning btn-sm">下架</button>
              <?php else: ?>
                <button onclick="updateProductStatus(<?php echo $p['id']; ?>, 1)" class="btn btn-success btn-sm">上架</button>
              <?php endif; ?>
              <a href="products.php?delete=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('确认删除吗？');">删除</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </main>
  </div>
</div>
<!-- 引入 jQuery 和 Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
<script>
function updateProductStatus(id, status) {
  $.post('update_product_status.php', {
    id: id,
    status: status
  }, function(response) {
    if (response.success) {
      location.reload();
    } else {
      alert(response.error || '操作失败');
    }
  });
}
</script>
</body>
</html>