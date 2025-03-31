<?php
/**
 * IP限制管理脚本
 * 
 * 该文件用于管理员查看和管理系统的IP访问限制记录，包括解除IP封禁等操作
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

// 处理解除IP限制请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unblock_ips']) && is_array($_POST['unblock_ips'])) {
    $ips = $_POST['unblock_ips'];
    // 为避免SQL注入，使用预处理
    $in = str_repeat('?,', count($ips) - 1) . '?';
    $stmt = $pdo->prepare("UPDATE ip_limits SET is_blocked = 0, attempt_count = 0, last_attempt = DATE_SUB(NOW(), INTERVAL 24 HOUR) WHERE ip IN ($in)");
    $stmt->execute($ips);
    header("Location: ip_limits.php?success=1");
    exit;
}

// 每页显示数量
$pageSize = 10;
// 当前页码
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

// 查询被限制的IP总数（包括60秒内被限制的IP）
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM ip_limits WHERE is_blocked = 1 OR last_attempt > DATE_SUB(NOW(), INTERVAL 60 SECOND)");
$stmt->execute();
$totalBlocked = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalBlocked / $pageSize);
$offset = ($page - 1) * $pageSize;

// 查询被限制的IP列表（包括60秒内被限制的IP）
$stmt = $pdo->prepare("
    SELECT ip, last_attempt, attempt_count, created_at, updated_at
    FROM ip_limits
    WHERE is_blocked = 1 OR last_attempt > DATE_SUB(NOW(), INTERVAL 60 SECOND)
    ORDER BY updated_at DESC
    LIMIT :offset, :pageSize
");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
$stmt->execute();
$blockedIps = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$page_title = 'IP限制管理 - 管理后台';
$current_page = 'ip_limits';
require_once 'includes/header.php';
?>

        <!-- 主内容区域 -->
        <main role="main" class="content">
            <h1 class="my-4">IP限制管理</h1>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">操作成功！</div>
            <?php endif; ?>

            <!-- IP限制列表 -->
            <form method="post" action="ip_limits.php?page=<?php echo $page; ?>">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th><input type="checkbox" id="checkAll"></th>
                                <th>IP地址</th>
                                <th>尝试次数</th>
                                <th>最后尝试时间</th>
                                <th>首次记录时间</th>
                                <th>更新时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($blockedIps): ?>
                                <?php foreach ($blockedIps as $ip): ?>
                                    <tr>
                                        <td><input type="checkbox" name="unblock_ips[]" value="<?php echo htmlspecialchars($ip['ip']); ?>"></td>
                                        <td><?php echo htmlspecialchars($ip['ip']); ?></td>
                                        <td><?php echo htmlspecialchars($ip['attempt_count']); ?></td>
                                        <td><?php echo htmlspecialchars($ip['last_attempt']); ?></td>
                                        <td><?php echo htmlspecialchars($ip['created_at']); ?></td>
                                        <td><?php echo htmlspecialchars($ip['updated_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">暂无被限制的IP</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($blockedIps): ?>
                    <button type="submit" class="btn btn-danger mb-3">解除选中IP限制</button>
                <?php endif; ?>

                <!-- 分页 -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </form>
        </main>
    </div>
</div>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
<script>
    // 全选/取消全选
    document.getElementById('checkAll').addEventListener('change', function() {
        var checkboxes = document.getElementsByName('unblock_ips[]');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    });
</script>
</body>
</html>