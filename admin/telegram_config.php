<?php
/**
 * Telegram通知配置系统
 * 
 * 该文件主要用途是：
 * 管理Telegram机器人的配置信息，包括Bot Token和Chat ID的设置，
 * 以及启用/禁用Telegram通知功能。
 * 
 * 使用说明：
 * 1. 配置Telegram Bot Token
 * 2. 设置目标Chat ID
 * 3. 启用/禁用通知功能
 * 
 * 开源协议：MIT License
 */

session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once '../db.php';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bot_token = trim($_POST['bot_token']);
    $chat_id = trim($_POST['chat_id']);
    $enabled = isset($_POST['enabled']) ? 1 : 0;

    try {
        // 检查配置表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'telegram_config'");
        if ($stmt->rowCount() == 0) {
            // 创建配置表
            $pdo->exec("CREATE TABLE telegram_config (
                id INT PRIMARY KEY AUTO_INCREMENT,
                bot_token VARCHAR(100) NOT NULL,
                chat_id VARCHAR(20) NOT NULL,
                enabled TINYINT(1) DEFAULT 1,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
        }

        // 更新或插入配置
        $stmt = $pdo->query("SELECT id FROM telegram_config LIMIT 1");
        if ($stmt->rowCount() > 0) {
            $sql = "UPDATE telegram_config SET bot_token = ?, chat_id = ?, enabled = ?"; 
        } else {
            $sql = "INSERT INTO telegram_config (bot_token, chat_id, enabled) VALUES (?, ?, ?)"; 
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bot_token, $chat_id, $enabled]);
        $success_message = '配置已保存';
    } catch (PDOException $e) {
        $error_message = '保存失败：' . $e->getMessage();
    }
}

// 获取当前配置
try {
    $stmt = $pdo->query("SELECT * FROM telegram_config LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $config = null;
}
?>

<?php
$page_title = '电报机器人配置 - 管理后台';
$current_page = 'telegram_config';
require_once 'includes/header.php';
?>
    <!-- 主内容区域 -->
    <main role="main" class="content">
      <div class="card mt-4">
        <div class="card-header">
          <h3 class="mb-0">TG机器人配置</h3>
        </div>
        <div class="card-body">
          <?php if (isset($success_message)): ?>
          <div class="alert alert-success"><?php echo $success_message; ?></div>
          <?php endif; ?>
          
          <?php if (isset($error_message)): ?>
          <div class="alert alert-danger"><?php echo $error_message; ?></div>
          <?php endif; ?>

          <form method="POST" action="">
            <div class="form-group row mb-4">
                <label for="bot_token" class="col-sm-3 col-form-label text-right">机器人Token</label>
                <div class="col-sm-7">
                    <input type="text" class="form-control" id="bot_token" name="bot_token" 
                           value="<?php echo htmlspecialchars($config['bot_token'] ?? ''); ?>" required>
                    <small class="form-text text-muted">从BotFather获取的机器人Token</small>
                </div>
            </div>

            <div class="form-group row mb-4">
                <label for="chat_id" class="col-sm-3 col-form-label text-right">通知ID</label>
                <div class="col-sm-7">
                    <input type="text" class="form-control" id="chat_id" name="chat_id" 
                           value="<?php echo htmlspecialchars($config['chat_id'] ?? ''); ?>" required>
                    <small class="form-text text-muted">接收通知的Telegram用户ID或群组ID</small>
                </div>
            </div>

            <div class="form-group row mb-4">
                <div class="col-sm-7 offset-sm-3">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" 
                               <?php echo (!isset($config['enabled']) || $config['enabled']) ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="enabled">启用通知</label>
                    </div>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-sm-7 offset-sm-3">
                    <button type="submit" class="btn btn-primary px-4">保存配置</button>
                </div>
            </div>
            </form>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- 引入 Bootstrap 4 JS 和依赖 -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
</body>
</html>