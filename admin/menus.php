<?php
/**
 * 菜单管理脚本
 * 
 * 该文件用于管理员配置系统菜单，包括添加、编辑和删除菜单项等功能
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

// 处理删除请求
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM menus WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: menus.php");
    exit;
}

// 处理新增或编辑表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $url = trim($_POST['url']);
    $sort_order = intval($_POST['sort_order']);
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // 编辑菜单项
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("UPDATE menus SET name = ?, url = ?, sort_order = ? WHERE id = ?");
        $stmt->execute([$name, $url, $sort_order, $id]);
    } else {
        // 新增菜单项
        $stmt = $pdo->prepare("INSERT INTO menus (name, url, sort_order) VALUES (?, ?, ?)");
        $stmt->execute([$name, $url, $sort_order]);
    }
    header("Location: menus.php");
    exit;
}

// 查询所有菜单项
$stmt = $pdo->query("SELECT * FROM menus ORDER BY sort_order ASC");
$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 如果有传入 id，则查询对应菜单项用于编辑
$editing = false;
if (isset($_GET['id'])) {
    $editId = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM menus WHERE id = ?");
    $stmt->execute([$editId]);
    $editMenu = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editMenu) {
        $editing = true;
    }
}
?>
<?php
$page_title = '菜单管理';
$current_page = 'menus';
require_once 'includes/header.php';
?>
    
    <!-- 主内容区域 -->
    <main role="main" class="content">
      <h1 class="my-4">菜单管理</h1>
      <!-- 菜单管理表格 -->
      <table class="table table-bordered table-striped">
        <thead class="thead-dark">
          <tr>
            <th>ID</th>
            <th>名称</th>
            <th>跳转链接</th>
            <th>排序</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($menus): ?>
            <?php foreach ($menus as $menu): ?>
              <tr>
                <td><?php echo htmlspecialchars($menu['id']); ?></td>
                <td><?php echo htmlspecialchars($menu['name']); ?></td>
                <td><?php echo htmlspecialchars($menu['url']); ?></td>
                <td><?php echo htmlspecialchars($menu['sort_order']); ?></td>
                <td>
                  <a href="menus.php?id=<?php echo $menu['id']; ?>" class="btn btn-primary btn-sm">编辑</a>
                  <a href="menus.php?delete=<?php echo $menu['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('确定删除吗？');">删除</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
              <tr><td colspan="5" class="text-center">暂无菜单项</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
      
      <!-- 新增/编辑菜单项表单 -->
      <h3><?php echo $editing ? '编辑菜单项' : '新增菜单项'; ?></h3>
      <form method="post" action="menus.php">
        <?php if ($editing): ?>
          <input type="hidden" name="id" value="<?php echo htmlspecialchars($editMenu['id']); ?>">
        <?php endif; ?>
        <div class="form-group">
          <label for="name">名称</label>
          <input type="text" name="name" id="name" class="form-control" required value="<?php echo $editing ? htmlspecialchars($editMenu['name']) : ''; ?>">
        </div>
        <div class="form-group">
          <label for="url">跳转链接</label>
          <input type="text" name="url" id="url" class="form-control" required value="<?php echo $editing ? htmlspecialchars($editMenu['url']) : ''; ?>">
        </div>
        <div class="form-group">
          <label for="sort_order">排序</label>
          <input type="number" name="sort_order" id="sort_order" class="form-control" value="<?php echo $editing ? htmlspecialchars($editMenu['sort_order']) : 0; ?>">
        </div>
        <button type="submit" class="btn btn-success"><?php echo $editing ? '更新菜单项' : '新增菜单项'; ?></button>
        <a href="dashboard.php" class="btn btn-secondary">取消</a>
      </form>
    </main>
  </div>
</div>
<!-- 引入 jQuery 和 Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>