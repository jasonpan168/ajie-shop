<?php
/**
 * 微信支付配置系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 管理微信支付接口的配置信息，包括AppID、商户号、API密钥等设置，
 * 以及启用/禁用微信支付功能。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require_once '../db.php';

// 如果配置记录不存在，则尝试读取一条记录（默认只有一行配置）
$stmt = $pdo->query("SELECT * FROM wechat_config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appid      = trim($_POST['appid']);
    $mch_id     = trim($_POST['mch_id']);
    $api_key    = trim($_POST['api_key']);
    $notify_url = trim($_POST['notify_url']);
    $enabled    = isset($_POST['enabled']) ? 1 : 0;
    
    if ($config) {
        // 更新已有记录
        $stmt = $pdo->prepare("UPDATE wechat_config SET appid = ?, mch_id = ?, api_key = ?, notify_url = ?, enabled = ? WHERE id = ?");
        $stmt->execute([$appid, $mch_id, $api_key, $notify_url, $enabled, $config['id']]);
    } else {
        // 插入新记录
        $stmt = $pdo->prepare("INSERT INTO wechat_config (appid, mch_id, api_key, notify_url, enabled) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$appid, $mch_id, $api_key, $notify_url, $enabled]);
    }
    header("Location: wechat_config.php?success=1");
    exit;
}
?>
<?php
$page_title = '微信支付配置管理';
$current_page = 'wechat_config';
require_once 'includes/header.php';
?>
    <!-- 主内容区域 -->
    <main role="main" class="content">
      <h1 class="my-4">微信支付配置管理</h1>
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">配置已更新成功！</div>
      <?php endif; ?>
      <form method="post" action="wechat_config.php">
        <div class="form-group">
          <label for="appid">AppID</label>
          <input type="text" name="appid" id="appid" class="form-control" required value="<?php echo htmlspecialchars($config['appid'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label for="mch_id">商户号 (MchID)</label>
          <input type="text" name="mch_id" id="mch_id" class="form-control" required value="<?php echo htmlspecialchars($config['mch_id'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label for="api_key">支付API密钥</label>
          <div class="input-group">
            <input type="text" name="api_key" id="api_key" class="form-control" required value="<?php echo htmlspecialchars($config['api_key'] ?? ''); ?>">
            <div class="input-group-append">
              <button type="button" id="encryptBtn" class="btn btn-info">加密密钥</button>
            </div>
          </div>
          <small class="form-text text-muted">请先输入原始密钥，再点击“加密密钥”按钮生成加密后的密钥。</small>
        </div>
        <div class="form-group">
          <label for="notify_url">回调通知地址 (Notify URL)</label>
          <input type="text" name="notify_url" id="notify_url" class="form-control" required value="<?php echo htmlspecialchars($config['notify_url'] ?? 'https://yewu.laikr.com/notify.php'); ?>">
        </div>
        <div class="form-group">
          <label>支付通道开关：</label>
          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" <?php echo ($config['enabled'] ?? 1) ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="enabled">微信支付通道</label>
          </div>
        </div>
        <button type="submit" class="btn btn-success">保存配置</button>
        <a href="dashboard.php" class="btn btn-secondary">返回后台</a>
      </form>
    </main>
  </div>
</div>
<!-- 引入 jQuery 和 Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
<!-- 引入 CryptoJS 库（用于 AES 加密） -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
<script>
document.getElementById('encryptBtn').addEventListener('click', function() {
    var rawKey = document.getElementById('api_key').value.trim();
    if (!rawKey) {
       alert("请输入需要加密的API密钥");
       return;
    }
    // 定义加密密钥，与 PHP 中使用的加密密钥一致（16个字符）
    var encryptionKey = CryptoJS.enc.Utf8.parse('abcdefghijklmno1sdw33kco2');
    // 使用 AES-128-ECB 模式进行加密
    var encrypted = CryptoJS.AES.encrypt(rawKey, encryptionKey, {
         mode: CryptoJS.mode.ECB,
         padding: CryptoJS.pad.Pkcs7
    });
    // 将加密结果转换为字符串，并更新到输入框
    document.getElementById('api_key').value = encrypted.toString();
    alert("密钥已加密");
});
</script>
</body>
</html>