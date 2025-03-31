<?php
/**
 * 商品编辑脚本
 * 
 * 该文件用于管理员添加和编辑商品信息，包括商品基本信息、价格和状态等设置
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

$page_title = isset($_GET['id']) ? '编辑商品' : '添加商品';
$current_page = 'products';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $detail = trim($_POST['detail']);  // 详细介绍字段
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $cover = trim($_POST['cover']);
    $sort_order = intval($_POST['sort_order']);
    
    if ($id > 0) {
        // 更新商品
        $stmt = $pdo->prepare("UPDATE products SET title=?, description=?, detail=?, price=?, stock=?, cover=?, sort_order=? WHERE id=?");
        $stmt->execute([$title, $description, $detail, $price, $stock, $cover, $sort_order, $id]);
    } else {
        // 添加新商品
        $stmt = $pdo->prepare("INSERT INTO products (title, description, detail, price, stock, cover, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $detail, $price, $stock, $cover, $sort_order]);
    }
    header("Location: products.php");
    exit;
}
?>
<?php include 'includes/header.php'; ?>
    <!-- 主内容区域 -->
    <main role="main" class="content">
      <div class="card">
        <div class="card-header">
          <?php echo $id > 0 ? '编辑商品' : '添加商品'; ?>
        </div>
        <div class="card-body">
          <form method="post" action="product_edit.php<?php echo $id > 0 ? '?id=' . $id : ''; ?>">
            <div class="form-group">
              <label>商品标题</label>
              <input type="text" name="title" class="form-control" required value="<?php echo $product ? htmlspecialchars($product['title']) : ''; ?>">
            </div>
            <div class="form-group">
              <label>商品介绍（副标题）</label>
              <textarea name="description" class="form-control" rows="2"><?php echo $product ? htmlspecialchars($product['description']) : ''; ?></textarea>
            </div>
            <div class="form-group">
              <label>详细介绍（支持 HTML，可插入图片链接）</label>
              <textarea name="detail" class="form-control" rows="6"><?php echo $product ? htmlspecialchars($product['detail'] ?? '') : ''; ?></textarea>
              <small class="form-text text-muted">
                您可以在此输入详细介绍信息，支持 HTML 标签，例如：<code>&lt;img src="http://example.com/image.jpg"&gt;</code>
              </small>
            </div>
            <div class="form-group">
              <label>价格</label>
              <input type="number" step="0.01" name="price" class="form-control" required value="<?php echo $product ? htmlspecialchars($product['price']) : ''; ?>">
            </div>
            <div class="form-group">
              <label>库存</label>
              <input type="number" name="stock" class="form-control" required value="<?php echo $product ? htmlspecialchars($product['stock']) : ''; ?>">
            </div>
            <div class="form-group">
              <label>封面链接</label>
              <input type="text" name="cover" class="form-control" required value="<?php echo $product ? htmlspecialchars($product['cover']) : ''; ?>">
            </div>
            <div class="form-group">
              <label>显示排序（数字越小越靠前）</label>
              <input type="number" name="sort_order" class="form-control" value="<?php echo $product ? htmlspecialchars($product['sort_order']) : 0; ?>">
            </div>
            <button type="submit" class="btn btn-success">
              <?php echo $id > 0 ? '更新商品' : '添加商品'; ?>
            </button>
            <a href="products.php" class="btn btn-secondary">返回</a>
          </form>
        </div>
      </div>
    </main>
  </div>
</div>
  </div>
</div>
</body>
</html>