<?php
/**
 * IP解封管理系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 提供IP地址解封功能，用于管理员手动解除被系统自动封禁的IP地址，
 * 支持永久解封和临时解封操作。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

require_once '../db.php';
require_once 'includes/header.php';

// 检查管理员登录状态
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

// 处理永久解除IP限制的请求
if (isset($_POST['unlock_ip']) && !empty($_POST['ip'])) {
    try {
        $ip = trim($_POST['ip']);
        
        // 更新IP限制记录，设置为永久解除限制
        $stmt = $pdo->prepare("UPDATE ip_limits SET is_blocked = 0, permanently_unlocked = 1 WHERE ip = ?");
        $stmt->execute([$ip]);
        
        if ($stmt->rowCount() > 0) {
            $success = "成功永久解除IP {$ip} 的限制";
            error_log("[" . date('Y-m-d H:i:s') . "] 管理员永久解除IP限制: {$ip}");
        } else {
            // 如果IP不在限制列表中，则插入一条新记录
            $stmt = $pdo->prepare("INSERT INTO ip_limits (ip, is_blocked, permanently_unlocked, attempt_count) VALUES (?, 0, 1, 0)");
            $stmt->execute([$ip]);
            $success = "成功将IP {$ip} 添加到永久解除限制列表";
        }
    } catch (PDOException $e) {
        $error = "操作失败: " . $e->getMessage();
        error_log("[" . date('Y-m-d H:i:s') . "] 永久解除IP限制失败: " . $e->getMessage());
    }
}

// 获取所有IP限制记录
$stmt = $pdo->query("SELECT * FROM ip_limits ORDER BY last_attempt DESC");
$ip_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>IP限制管理</h2>
    
    <?php if (isset($success)): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <!-- 永久解除IP限制表单 -->
    <div class="card mb-4">
        <div class="card-header">永久解除IP限制</div>
        <div class="card-body">
            <form method="post" class="form-inline">
                <div class="form-group mx-sm-3 mb-2">
                    <label for="ip" class="sr-only">IP地址</label>
                    <input type="text" class="form-control" id="ip" name="ip" placeholder="输入IP地址" required>
                </div>
                <button type="submit" name="unlock_ip" class="btn btn-primary mb-2">永久解除限制</button>
            </form>
        </div>
    </div>
    
    <!-- IP限制记录列表 -->
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>IP地址</th>
                    <th>最后尝试时间</th>
                    <th>尝试次数</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ip_records as $record): ?>
                <tr>
                    <td><?php echo htmlspecialchars($record['ip']); ?></td>
                    <td><?php echo $record['last_attempt']; ?></td>
                    <td><?php echo $record['attempt_count']; ?></td>
                    <td>
                        <?php if ($record['permanently_unlocked']): ?>
                            <span class="badge badge-success">永久解除限制</span>
                        <?php elseif ($record['is_blocked']): ?>
                            <span class="badge badge-danger">已封禁</span>
                        <?php else: ?>
                            <span class="badge badge-info">正常</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$record['permanently_unlocked']): ?>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="ip" value="<?php echo htmlspecialchars($record['ip']); ?>">
                            <button type="submit" name="unlock_ip" class="btn btn-sm btn-success">永久解除限制</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>