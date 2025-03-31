<?php
/**
 * WxPusher消息推送配置系统
 * 
 * 该文件主要用途是：
 * 管理WxPusher消息推送服务的配置信息，包括应用Token和管理员UID的设置，
 * 以及启用/禁用各类消息通知功能。
 * 
 * 使用说明：
 * 1. 配置WxPusher应用Token
 * 2. 设置管理员UID
 * 3. 选择需要启用的通知类型
 * 
 * 开源协议：MIT License
 */

session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require_once '../db.php';

$page_title = 'WxPusher配置';
$current_page = 'wxpusher_config';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_token = trim($_POST['app_token']);
    $admin_uid = trim($_POST['admin_uid']);
    $enable_wxpusher = isset($_POST['enable_wxpusher']) ? 1 : 0;
    $enable_order_notify = isset($_POST['enable_order_notify']) ? 1 : 0;
    $enable_payment_notify = isset($_POST['enable_payment_notify']) ? 1 : 0;
    
    // 更新配置
    $stmt = $pdo->prepare("REPLACE INTO system_settings (setting_key, setting_value) VALUES 
        ('wxpusher_app_token', ?),
        ('wxpusher_admin_uid', ?),
        ('wxpusher_enabled', ?),
        ('wxpusher_order_notify', ?),
        ('wxpusher_payment_notify', ?)");
    $stmt->execute([$app_token, $admin_uid, $enable_wxpusher, $enable_order_notify, $enable_payment_notify]);
    
    $success_message = '配置已更新';
}

// 获取当前配置
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN 
    ('wxpusher_app_token', 'wxpusher_admin_uid', 'wxpusher_enabled', 'wxpusher_order_notify', 'wxpusher_payment_notify')");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$app_token = $settings['wxpusher_app_token'] ?? '';
$admin_uid = $settings['wxpusher_admin_uid'] ?? '';
$enable_wxpusher = $settings['wxpusher_enabled'] ?? '0';
$enable_order_notify = $settings['wxpusher_order_notify'] ?? '0';
$enable_payment_notify = $settings['wxpusher_payment_notify'] ?? '0';

require_once 'includes/header.php';
?>

<main role="main" class="content">
    <div class="container-fluid">
        <h2 class="mt-4 mb-4">WxPusher配置</h2>
        
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post">
                    <div class="form-group">
                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="enable_wxpusher" name="enable_wxpusher" <?php echo $enable_wxpusher ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="enable_wxpusher">启用WxPusher通知</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>应用Token (appToken)</label>
                        <input type="text" name="app_token" class="form-control" value="<?php echo htmlspecialchars($app_token); ?>" required>
                        <small class="form-text text-muted">在 <a href="https://wxpusher.zjiecode.com/admin/main/app/appToken" target="_blank">WxPusher后台</a> 创建应用并获取appToken</small>
                    </div>
                    
                    <div class="form-group">
                        <label>管理员UID</label>
                        <input type="text" name="admin_uid" class="form-control" value="<?php echo htmlspecialchars($admin_uid); ?>" required>
                        <small class="form-text text-muted">在 <a href="https://wxpusher.zjiecode.com/admin/main/app/uid" target="_blank">WxPusher后台</a> 扫码关注应用获取UID</small>
                    </div>

                    <div class="form-group">
                        <label class="d-block">通知事件设置</label>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="enable_order_notify" name="enable_order_notify" <?php echo $enable_order_notify ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="enable_order_notify">新订单通知</label>
                        </div>
                        <div class="custom-control custom-checkbox mt-2">
                            <input type="checkbox" class="custom-control-input" id="enable_payment_notify" name="enable_payment_notify" <?php echo $enable_payment_notify ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="enable_payment_notify">支付成功通知</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">保存配置</button>
            </form>
        </div>
    </div>
</main>

</div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>