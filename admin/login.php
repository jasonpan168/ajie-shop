<?php
/**
 * 管理员登录系统
 *
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 *
 * 该文件主要用途是：
 * 提供管理员登录功能，包含安全验证和会话管理，
 * 是后台管理系统的安全入口。
 *
 * @license AGPL-3.0-or-later  https://github.com/jasonpan168/ajie-shop
 */

session_start();
require_once '../db.php';
require_once '../lib/CsrfProtection.php';
require_once '../lib/Logger.php';

// 登录失败锁定策略：同一 IP 连续失败 5 次，锁定 15 分钟
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCK_SECONDS', 900);

/**
 * 取客户端真实 IP。
 * 只有在确实存在反向代理时 X-Forwarded-For 才可信，这里取第一段并做格式校验，
 * 校验不过就退回 REMOTE_ADDR。
 */
function login_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        if (filter_var($parts[0], FILTER_VALIDATE_IP)) {
            $ip = $parts[0];
        }
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

// 登录失败计数表：老版本升级上来的库里没有这张表，这里惰性创建，
// 与 telegram_config 的做法一致，不需要手动跑迁移。
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_login_attempts` (
        `ip` varchar(45) NOT NULL,
        `attempts` int(11) NOT NULL DEFAULT '0',
        `last_attempt` datetime NOT NULL,
        `locked_until` datetime DEFAULT NULL,
        PRIMARY KEY (`ip`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $throttle_ready = true;
} catch (Exception $e) {
    // 建表失败不应该让管理员彻底进不去后台，降级为「不限流但记日志」
    error_log('admin_login_attempts 表创建失败：' . $e->getMessage());
    $throttle_ready = false;
}

$client_ip = login_client_ip();
$error = '';
$locked_remaining = 0;

// 读取当前 IP 的失败记录
$attempt_row = null;
if ($throttle_ready) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM admin_login_attempts WHERE ip = ?");
        $stmt->execute([$client_ip]);
        $attempt_row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $attempt_row = null;
    }
}

if ($attempt_row && !empty($attempt_row['locked_until'])) {
    $locked_until_ts = strtotime($attempt_row['locked_until']);
    if ($locked_until_ts > time()) {
        $locked_remaining = (int)ceil(($locked_until_ts - time()) / 60);
        $error = "登录尝试过于频繁，请在 {$locked_remaining} 分钟后再试。";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $locked_remaining === 0) {
    // 1) CSRF 校验
    if (!CsrfProtection::validateToken()) {
        Logger::logCsrfAttempt('admin/login.php');
        $error = '会话已过期或请求无效，请刷新页面后重试。';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2) 只接受 password_hash 产生的哈希。
        //    旧的 md5($password) === $admin['password'] 兼容分支已删除：
        //    MD5 无盐、可被彩虹表秒查，保留它等于给弱口令留一条后门。
        //    如果你的库里还是 MD5 口令，用下面这条 SQL 直接重置（PHP 8 环境执行）：
        //      php -r "echo password_hash('新的强口令', PASSWORD_DEFAULT);"
        //      UPDATE admin SET password = '<上面输出的哈希>' WHERE username = 'xxx';
        $password_match = $admin && password_verify($password, $admin['password']);

        if ($password_match) {
            // 3) 登录成功：清空失败计数
            if ($throttle_ready) {
                try {
                    $pdo->prepare("DELETE FROM admin_login_attempts WHERE ip = ?")->execute([$client_ip]);
                } catch (Exception $e) {
                    // 清计数失败不影响登录
                }
            }

            // 4) 平滑升级哈希算法（例如 bcrypt cost 调整、未来默认算法变更）
            if (password_needs_rehash($admin['password'], PASSWORD_DEFAULT)) {
                try {
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?")
                        ->execute([$new_hash, $admin['id']]);
                } catch (Exception $e) {
                    error_log('管理员口令哈希升级失败：' . $e->getMessage());
                }
            }

            // 防止会话固定攻击
            session_regenerate_id(true);
            $_SESSION['admin'] = $admin['id'];
            $_SESSION['login_time'] = time();
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            CsrfProtection::refreshToken();
            Logger::logLoginAttempt($username, true);
            header("Location: dashboard.php");
            exit;
        }

        // 5) 登录失败：累加计数，达到阈值就锁定该 IP
        Logger::logLoginAttempt($username, false);
        if ($throttle_ready) {
            try {
                $attempts = ($attempt_row ? (int)$attempt_row['attempts'] : 0) + 1;
                if ($attempts >= LOGIN_MAX_ATTEMPTS) {
                    $locked_until = date('Y-m-d H:i:s', time() + LOGIN_LOCK_SECONDS);
                    $attempts = 0; // 锁定期满后重新计数
                    $locked_remaining = (int)ceil(LOGIN_LOCK_SECONDS / 60);
                } else {
                    $locked_until = null;
                }
                $stmt = $pdo->prepare("
                    INSERT INTO admin_login_attempts (ip, attempts, last_attempt, locked_until)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE attempts = VALUES(attempts),
                                            last_attempt = VALUES(last_attempt),
                                            locked_until = VALUES(locked_until)
                ");
                // last_attempt 与 locked_until 都用 PHP 时间生成，
                // 避免 PHP 时区与 MySQL 时区不一致导致两列口径不同。
                $stmt->execute([$client_ip, $attempts, date('Y-m-d H:i:s'), $locked_until]);
                if ($locked_until !== null) {
                    Logger::logSecurityEvent('Admin login locked', 'WARNING', ['ip' => $client_ip]);
                }
            } catch (Exception $e) {
                error_log('登录失败计数写入失败：' . $e->getMessage());
            }
        }

        // 统一的错误文案，不透露用户名是否存在
        $error = $locked_remaining > 0
            ? "登录失败次数过多，该 IP 已被锁定 {$locked_remaining} 分钟。"
            : "用户名或密码错误";
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <title>管理员登录</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous" referrerpolicy="no-referrer">
  <style>
    body { background-color: #f8f9fa; }
    .login-container { max-width: 400px; margin: 100px auto; }
  </style>
</head>
<body>
<div class="login-container">
  <div class="card">
    <div class="card-body">
      <h3 class="card-title text-center mb-4">管理员登录</h3>
      <?php if ($error !== '') { echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>'; } ?>
      <form method="post" action="">
        <?php echo CsrfProtection::getTokenField(); ?>
        <div class="form-group">
          <label for="username">用户名</label>
          <input type="text" class="form-control" id="username" name="username" required <?php echo $locked_remaining > 0 ? 'disabled' : ''; ?>>
        </div>
        <div class="form-group">
          <label for="password">密码</label>
          <input type="password" class="form-control" id="password" name="password" required <?php echo $locked_remaining > 0 ? 'disabled' : ''; ?>>
        </div>
        <button type="submit" class="btn btn-primary btn-block" <?php echo $locked_remaining > 0 ? 'disabled' : ''; ?>>登录</button>
      </form>
    </div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" integrity="sha384-1CmrxMRARb6aLqgBO7yyAxTOQE2AKb9GfXnEo760AUcUmFx3ibVJJAzGytlQcNXd" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>
</html>
