<?php
/**
 * 邮件服务配置系统
 * 
 * 该文件主要用途是：
 * 管理SMTP邮件服务器的配置信息，包括服务器地址、端口、账号等设置，
 * 以及邮件发送的相关参数配置。
 * 
 * 使用说明：
 * 1. 配置SMTP服务器信息
 * 2. 设置发件人信息
 * 3. 选择邮件通知类型
 * 
 * 开源协议：MIT License
 */

session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/../db.php';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $smtp_host   = trim($_POST['smtp_host']);
    $smtp_port   = trim($_POST['smtp_port']);
    $smtp_user   = trim($_POST['smtp_user']);
    $smtp_pass   = trim($_POST['smtp_pass']);
    $sender_name = trim($_POST['sender_name']);
    $sender_email= trim($_POST['sender_email']);
    $channel_type= trim($_POST['channel_type']);

    // 检查是否存在记录
    $stmt = $pdo->query("SELECT COUNT(*) FROM email_settings");
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        // 更新配置
        $stmt = $pdo->prepare("UPDATE email_settings SET smtp_host=?, smtp_port=?, smtp_user=?, smtp_pass=?, sender_name=?, sender_email=? WHERE id=1");
        $stmt->execute([$smtp_host, $smtp_port, $smtp_user, $smtp_pass, $sender_name, $sender_email]);
        $message = "邮箱配置信息更新成功！";
    } else {
        // 插入新配置
        $stmt = $pdo->prepare("INSERT INTO email_settings (smtp_host, smtp_port, smtp_user, smtp_pass, sender_name, sender_email) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$smtp_host, $smtp_port, $smtp_user, $smtp_pass, $sender_name, $sender_email]);
        $message = "邮箱配置信息已保存！";
    }
}

// 获取当前配置信息
$stmt = $pdo->query("SELECT * FROM email_settings LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<?php
$page_title = '邮箱配置管理 - 管理后台';
$current_page = 'email_settings';
require_once 'includes/header.php';
?>
<div class="container-fluid">
  <div class="row">
    <!-- 左侧侧边栏 -->
    
    <!-- 右侧主内容区域 -->
    <main role="main" class="content">
      <h1>邮箱配置管理</h1>
      <div class="card">
        <div class="card-header">
          <h3>📧 邮箱配置管理</h3>
        </div>
        <div class="card-body">
          <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
          <?php endif; ?>
          <form method="post" action="email_settings.php">
            <div class="form-group">
              <label for="smtp_host">SMTP 服务器地址</label>
              <input type="text" name="smtp_host" id="smtp_host" class="form-control" value="<?php echo htmlspecialchars($config['smtp_host'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
              <label for="smtp_port">SMTP 端口</label>
              <input type="number" name="smtp_port" id="smtp_port" class="form-control" value="<?php echo htmlspecialchars($config['smtp_port'] ?? '465'); ?>" required>
            </div>
            <div class="form-group">
              <label for="smtp_user">SMTP 用户名</label>
              <input type="email" name="smtp_user" id="smtp_user" class="form-control" value="<?php echo htmlspecialchars($config['smtp_user'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
              <label for="smtp_pass">SMTP 密码/授权码</label>
              <input type="password" name="smtp_pass" id="smtp_pass" class="form-control" value="<?php echo htmlspecialchars($config['smtp_pass'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
              <label for="sender_name">发件人名称</label>
              <input type="text" name="sender_name" id="sender_name" class="form-control" value="<?php echo htmlspecialchars($config['sender_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
              <label for="sender_email">发件人邮箱</label>
              <input type="email" name="sender_email" id="sender_email" class="form-control" value="<?php echo htmlspecialchars($config['sender_email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
              <label for="channel_type">发信通道</label>
              <select name="channel_type" id="channel_type" class="form-control" required>
                <option value="smtp" <?php echo ($config['channel_type'] ?? '') === 'smtp' ? 'selected' : ''; ?>>普通SMTP</option>
                <option value="mailgun" <?php echo ($config['channel_type'] ?? '') === 'mailgun' ? 'selected' : ''; ?>>Mailgun</option>
              </select>
            </div>
            <div class="form-group">
              <label for="test_email">测试邮箱</label>
              <div class="input-group">
                <input type="email" name="test_email" id="test_email" class="form-control" placeholder="输入测试邮箱地址">
                <div class="input-group-append">
                  <button type="button" class="btn btn-info" onclick="testEmail()">发送测试邮件</button>
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-primary">💾 保存设置</button>
            
            <script>
            function testEmail() {
                var testEmail = document.getElementById('test_email').value;
                if (!testEmail) {
                    alert('请输入测试邮箱地址');
                    return;
                }
                
                // 收集当前表单数据
                var formData = new FormData(document.querySelector('form'));
                formData.append('action', 'test');
                formData.append('test_email', testEmail);
                
                // 发送测试请求
                fetch('test_email.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('测试邮件发送成功！');
                    } else {
                        alert('测试邮件发送失败：' + data.message);
                    }
                })
                .catch(error => {
                    alert('发送请求失败：' + error);
                });
            }
            </script>
          </form>
        </div>
      </div>
    </main>
  </div>
</div>
<!-- 引入 jQuery 和 Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>