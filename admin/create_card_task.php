<?php
/**
 * 创建卡密生成任务脚本
 * 
 * 该文件用于管理员创建新的自动卡密生成任务，包含任务名称、商品选择和卡密内容的处理
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

// 获取商品列表
$stmt = $pdo->query("SELECT id, title FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $product_id = intval($_POST['product_id']);
    $task_name = trim($_POST['task_name']);
    $card_content = trim($_POST['card_content']);

    // 创建发卡任务
    $stmt = $pdo->prepare("INSERT INTO auto_card_tasks (product_id, task_name) VALUES (?, ?)");
    $stmt->execute([$product_id, $task_name]);
    $task_id = $pdo->lastInsertId();

    // 分行保存卡密，每条记录 email 字段默认保存空字符串
    $lines = explode("\n", $card_content);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            $stmt = $pdo->prepare("INSERT INTO auto_cards (task_id, card_content, status, email) VALUES (?, ?, 'unused', '')");
            $stmt->execute([$task_id, $line]);
        }
    }

    header("Location: manage_card_tasks.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <title>创建发卡任务</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- 引入 Bootstrap 4 CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>创建发卡任务</h2>
    <form method="post" action="create_card_task.php">
        <div class="form-group">
            <label for="product_id">选择商品</label>
            <select name="product_id" id="product_id" class="form-control" required>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="task_name">任务名称</label>
            <input type="text" name="task_name" class="form-control" id="task_name" placeholder="任务名称" required>
        </div>
        <div class="form-group">
            <label for="card_content">卡密或发卡内容（每行一个）</label>
            <textarea name="card_content" class="form-control" id="card_content" rows="6" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">创建发卡任务</button>
    </form>
</div>
</body>
</html>