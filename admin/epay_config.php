<?php
/**
 * 易支付接口配置系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 管理易支付接口的配置信息，包括API地址、商户ID、密钥等设置，
 * 以及支付接口的相关参数配置。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// 确保能找到 db.php（本文件位于 admin/ 目录）
require_once __DIR__ . '/../db.php';

// 从 epay_config 表读取配置（假设只存一条记录）
$stmt = $pdo->query("SELECT * FROM epay_config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单参数
    $apiurl         = trim($_POST['apiurl']);
    $pid            = trim($_POST['pid']);
    $key            = trim($_POST['key']);  // 商户密钥
    $notify_url     = trim($_POST['notify_url']);
    $return_url     = trim($_POST['return_url']);
    $alipay_enabled = isset($_POST['alipay_enabled']) ? 1 : 0;
    $wxpay_enabled  = isset($_POST['wxpay_enabled']) ? 1 : 0;
    $usdt_enabled   = isset($_POST['usdt_enabled']) ? 1 : 0;

    // 检查必填项
    if (empty($apiurl) || empty($pid) || empty($key) || empty($notify_url) || empty($return_url)) {
        $error = "所有字段均为必填！";
    } else {
        // 如果已有记录则 UPDATE，否则 INSERT
        if ($config) {
            $stmt = $pdo->prepare("
                UPDATE epay_config
                SET 
                    apiurl = ?,
                    pid = ?,
                    `key` = ?,
                    notify_url = ?,
                    return_url = ?,
                    alipay_enabled = ?,
                    wxpay_enabled = ?,
                    usdt_enabled = ?
                WHERE id = ?
            ");
            $stmt->execute([$apiurl, $pid, $key, $notify_url, $return_url, $alipay_enabled, $wxpay_enabled, $usdt_enabled, $config['id']]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO epay_config
                    (apiurl, pid, `key`, notify_url, return_url, alipay_enabled, wxpay_enabled, usdt_enabled)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$apiurl, $pid, $key, $notify_url, $return_url, $alipay_enabled, $wxpay_enabled, $usdt_enabled]);
        }
        // 保存成功后跳转并显示提示
        header("Location: epay_config.php?success=1");
        exit;
    }
}
?>
<?php
$page_title = '易支付配置 - 管理后台';
$current_page = 'epay_config';
require_once 'includes/header.php';
?>
    <!-- 主内容区域 -->
    <main role="main" class="content">
      <div class="card mt-4">
        <div class="card-header">
          <h3 class="mb-0">配置易支付接口</h3>
        </div>
        <div class="card-body">
          <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success">配置已更新成功！</div>
          <?php endif; ?>
          <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>
          <form method="post" action="epay_config.php">
            <div class="mb-3 row">
              <label for="apiurl" class="col-sm-2 col-form-label">支付接口地址</label>
              <div class="col-sm-6">
                <input type="text" class="form-control" id="apiurl" name="apiurl" required
                       value="<?php echo htmlspecialchars($config['apiurl'] ?? 'https://pay.a6.cm/'); ?>">
                <div class="form-text">例如：https://pay.a6.cm/（末尾带斜杠）</div>
              </div>
            </div>
            <div class="mb-3 row">
              <label for="pid" class="col-sm-2 col-form-label">商户ID</label>
              <div class="col-sm-6">
                <input type="text" class="form-control" id="pid" name="pid" required
                       value="<?php echo htmlspecialchars($config['pid'] ?? ''); ?>">
              </div>
            </div>
            <div class="mb-3 row">
              <label for="key" class="col-sm-2 col-form-label">商户密钥</label>
              <div class="col-sm-6">
                <input type="text" class="form-control" id="key" name="key" required
                       value="<?php echo htmlspecialchars($config['key'] ?? ''); ?>">
              </div>
            </div>
            <div class="mb-3 row">
              <label for="notify_url" class="col-sm-2 col-form-label">异步通知地址</label>
              <div class="col-sm-6">
                <input type="text" class="form-control" id="notify_url" name="notify_url" required
                       value="<?php echo htmlspecialchars($config['notify_url'] ?? 'https://yewu.laikr.com/notify_url.php'); ?>">
              </div>
            </div>
            <div class="mb-3 row">
              <label for="return_url" class="col-sm-2 col-form-label">同步返回地址</label>
              <div class="col-sm-6">
                <input type="text" class="form-control" id="return_url" name="return_url" required
                       value="<?php echo htmlspecialchars($config['return_url'] ?? 'https://yewu.laikr.com/return_url.php'); ?>">
              </div>
            </div>
            <div class="mb-3 row">
              <label class="col-sm-2 col-form-label">支付方式开关</label>
              <div class="col-sm-6">
                <div class="mb-2">
                  <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="alipay_enabled" name="alipay_enabled" <?php echo ($config['alipay_enabled'] ?? 1) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="alipay_enabled">支付宝支付</label>
                  </div>
                </div>
                <div class="mb-2">
                  <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="wxpay_enabled" name="wxpay_enabled" <?php echo ($config['wxpay_enabled'] ?? 1) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="wxpay_enabled">微信支付</label>
                  </div>
                </div>
                <div class="mb-2">
                  <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="usdt_enabled" name="usdt_enabled" <?php echo ($config['usdt_enabled'] ?? 1) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="usdt_enabled">USDT支付</label>
                  </div>
                </div>
              </div>
            </div>
            <div class="mb-3 row">
              <div class="col-sm-6 offset-sm-2">
                <button type="submit" class="btn btn-primary">保存配置</button>
              </div>
            </div>
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