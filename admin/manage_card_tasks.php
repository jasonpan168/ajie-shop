<?php
/**
 * 卡密任务管理脚本
 * 
 * 该文件用于管理员管理自动发卡任务，包括任务列表查看、自动发卡商品设置等功能
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

// --------------------------
// A. 更新商品自动发卡设置
// --------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_autocard'])) {
    // 获取勾选的商品ID数组
    $autocard_ids = isset($_POST['autocard_ids']) ? $_POST['autocard_ids'] : array();
    
    // 将所有商品的 is_autocard 重置为 0
    $pdo->exec("UPDATE products SET is_autocard = 0");
    
    // 将选中的商品设为自动发卡
    if (!empty($autocard_ids)) {
        $ids_str = implode(',', array_map('intval', $autocard_ids));
        $pdo->exec("UPDATE products SET is_autocard = 1 WHERE id IN ($ids_str)");
    }
    $update_message = "商品自动发卡设置已更新！";
}

// --------------------------
// B. 创建发卡任务
// --------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    $product_id  = intval($_POST['product_id']);
    $task_name   = trim($_POST['task_name']);
    // 固定内容模式：repeat_flag=1；依次发卡模式：repeat_flag=0
    $repeat_flag = isset($_POST['repeat_flag']) ? 1 : 0;
    $card_content = trim($_POST['card_content']);
    
    if ($product_id && $task_name) {
        // 插入发卡任务记录，状态默认为 active
        $stmt = $pdo->prepare("INSERT INTO auto_card_tasks (product_id, task_name, repeat_flag, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
        $stmt->execute([$product_id, $task_name, $repeat_flag]);
        $task_id = $pdo->lastInsertId();
        
        // 使用 preg_split 兼容各种换行符
        if (!empty($card_content)) {
            $lines = preg_split('/\r\n|\r|\n/', $card_content);
            $stmtCard = $pdo->prepare("INSERT INTO auto_cards (task_id, card_content, status, email, created_at) VALUES (?, ?, 'unused', '', NOW())");
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $stmtCard->execute([$task_id, $line]);
                }
            }
        }
        $create_message = "发卡任务创建成功！";
    }
}

// --------------------------
// C. 获取数据
// --------------------------
// 获取所有商品数据，用于自动发卡设置和任务创建
$stmtProd = $pdo->query("SELECT id, title, is_autocard FROM products ORDER BY id ASC");
$products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

// 获取所有发卡任务（关联商品信息）
$stmtTasks = $pdo->query("SELECT t.*, p.title AS product_title FROM auto_card_tasks t LEFT JOIN products p ON t.product_id = p.id ORDER BY t.created_at DESC");
$tasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$page_title = '自动发卡任务管理 - 管理后台';
$current_page = 'manage_card_tasks';
require_once 'includes/header.php';
?>
    <!-- 主内容区域 -->
    <main role="main" class="content">
      <h1>自动发卡任务管理</h1>
      
      <?php if (!empty($update_message)): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($update_message); ?></div>
      <?php endif; ?>
      <?php if (!empty($create_message)): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($create_message); ?></div>
      <?php endif; ?>
      
      <!-- A. 商品自动发卡设置 -->
      <div class="card mb-4">
        <div class="card-header">
          <h3>商品自动发卡设置</h3>
        </div>
        <div class="card-body">
          <form method="post" action="manage_card_tasks.php">
            <input type="hidden" name="update_autocard" value="1">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>商品名称</th>
                  <th>自动发卡</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($products as $prod): ?>
                <tr>
                  <td><?php echo $prod['id']; ?></td>
                  <td><?php echo htmlspecialchars($prod['title']); ?></td>
                  <td>
                    <input type="checkbox" name="autocard_ids[]" value="<?php echo $prod['id']; ?>"
                           <?php if($prod['is_autocard']) echo 'checked'; ?>>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <button type="submit" class="btn btn-primary">更新自动发卡设置</button>
          </form>
        </div>
      </div>
      
      <!-- B. 创建发卡任务 -->
      <div class="card mb-4">
        <div class="card-header">
          <h3>创建发卡任务</h3>
        </div>
        <div class="card-body">
          <form method="post" action="manage_card_tasks.php">
            <input type="hidden" name="create_task" value="1">
            <div class="form-group">
              <label for="product_id">选择商品（仅显示启用自动发卡的商品）</label>
              <select name="product_id" id="product_id" class="form-control" required>
                <?php foreach ($products as $prod): ?>
                  <?php if ($prod['is_autocard']) : ?>
                    <option value="<?php echo $prod['id']; ?>"><?php echo htmlspecialchars($prod['title']); ?></option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="task_name">任务名称</label>
              <input type="text" name="task_name" id="task_name" class="form-control" required>
            </div>
            <div class="form-group form-check">
              <input type="checkbox" class="form-check-input" id="repeat_flag" name="repeat_flag" value="1">
              <label class="form-check-label" for="repeat_flag">固定内容，可重复发卡</label>
            </div>
            <div class="form-group">
              <label for="card_content">卡密或发卡内容（每行一条）</label>
              <textarea name="card_content" id="card_content" class="form-control" rows="5"></textarea>
            </div>
            <button type="submit" class="btn btn-success">创建发卡任务</button>
          </form>
        </div>
      </div>
      
      <!-- C. 发卡任务列表 -->
      <div class="card mb-4">
        <div class="card-header">
          <h3>发卡任务列表</h3>
        </div>
        <div class="card-body">
          <table class="table table-bordered table-hover">
            <thead class="thead-light">
              <tr>
                <th>任务ID</th>
                <th>商品</th>
                <th>任务名称</th>
                <th>重复发卡</th>
                <th>状态</th>
                <th>创建时间</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($tasks): ?>
                <?php foreach ($tasks as $task): ?>
                  <tr>
                    <td><?php echo $task['id']; ?></td>
                    <td><?php echo htmlspecialchars($task['product_title'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($task['task_name'] ?? ''); ?></td>
                    <td><?php echo ($task['repeat_flag'] ? '固定内容' : '依次发卡'); ?></td>
                    <td><?php echo htmlspecialchars($task['status'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($task['created_at'] ?? ''); ?></td>
                    <td>
                      <a href="complete_task.php?id=<?php echo $task['id']; ?>" class="btn btn-success btn-sm">完成任务</a>
                      <a href="delete_task.php?id=<?php echo $task['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('确定删除该任务吗？');">删除任务</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center">暂无发卡任务</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      
    </main>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>