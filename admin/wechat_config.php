<?php
/**
 * 微信支付配置系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 项目地址：https://github.com/jasonpan168/ajie-shop
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 管理微信支付接口的配置信息，包括AppID、商户号、API密钥等设置，
 * 以及启用/禁用微信支付功能。
 * 
 * @license AGPL-3.0-or-later  https://github.com/jasonpan168/ajie-shop
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
    // 商户 API 密钥：留空表示不修改；填写的是明文，在服务端加密后保存（加密密钥只在 config.php，绝不下发到浏览器）
    $api_key_input = is_string($_POST['api_key'] ?? null) ? trim($_POST['api_key']) : '';
    if ($api_key_input === '') {
        $api_key = $config['api_key'] ?? '';
    } else {
        $api_key = openssl_encrypt($api_key_input, 'AES-128-ECB', $encryption_key);
    }
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
          <input type="password" name="api_key" id="api_key" class="form-control" autocomplete="new-password" placeholder="<?php echo !empty($config['api_key']) ? '已设置，留空表示不修改' : '填写商户平台的 APIv2 密钥'; ?>" <?php echo empty($config['api_key']) ? 'required' : ''; ?>>
          <small class="form-text text-muted">保存时在服务器端自动加密，页面不会显示已保存的密钥。</small>
        </div>
        <div class="form-group">
          <label for="notify_url">回调通知地址 (Notify URL)</label>
          <input type="text" name="notify_url" id="notify_url" class="form-control" required value="<?php echo htmlspecialchars($config['notify_url'] ?? 'https://你的域名/notify.php'); ?>">
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
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" integrity="sha384-1CmrxMRARb6aLqgBO7yyAxTOQE2AKb9GfXnEo760AUcUmFx3ibVJJAzGytlQcNXd" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

</body>
</html>