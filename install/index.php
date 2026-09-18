<?php
session_start();

// 生成表单令牌
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

// 标记正在安装，防止config.php建立数据库连接
define('INSTALLING', true);

// 刚刚在本次会话里装完 —— 允许停留在第 3 步看完成页。
// 否则第 2 步写完 install.lock 后跳到 ?step=3，会被下面的锁文件检查立刻踢回首页，
// 「请立即删除 install/ 目录」这句最关键的提示用户根本看不到。
$install_completed = !empty($_SESSION['install_completed']);

// 检查是否已安装（第一道防线：安装锁文件）
if (file_exists(__DIR__ . '/../install.lock') && !$install_completed) {
    header('Location: ../');
    exit;
}

/**
 * 第二道防线：admin 表里已经有账号，就一定不是全新安装。
 *
 * 只靠 install.lock 是不够的 —— 迁移、rsync、备份还原都可能把锁文件弄丢，
 * 而第 2 步会 TRUNCATE TABLE admin 再写入新管理员。锁文件一丢，任何人访问
 * /install/ 就能清空管理员表、设置自己的账号，完整接管整个商城。
 */
function installer_existing_admin_count($dbHost, $dbUser, $dbPass, $dbName) {
    if ($dbName === '' || $dbHost === '') {
        return 0;
    }
    try {
        $probe = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
        $probe->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $count = $probe->query("SELECT COUNT(*) FROM `admin`")->fetchColumn();
        return (int)$count;
    } catch (Exception $e) {
        // 库或表还不存在 => 确实是全新安装
        return 0;
    }
}

/** 已装好的站点不允许再跑向导 */
function installer_refuse_if_installed($dbHost, $dbUser, $dbPass, $dbName) {
    if (installer_existing_admin_count($dbHost, $dbUser, $dbPass, $dbName) > 0) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="zh"><head><meta charset="UTF-8">'
           . '<title>安装已被拒绝</title></head><body style="font:16px/1.7 system-ui;max-width:640px;margin:80px auto;padding:0 20px">'
           . '<h1>安装已被拒绝</h1>'
           . '<p>目标数据库中<strong>已存在管理员账号</strong>，说明本站已经安装过。'
           . '继续安装会清空管理员表并让执行者接管整个后台，因此向导已停止。</p>'
           . '<p>如果你确实要重装，请先手动备份并清空数据库；'
           . '如果不是你发起的这次安装，请<strong>立即删除服务器上的 install/ 目录</strong>，'
           . '并检查后台是否有异常登录。</p>'
           . '</body></html>';
        exit;
    }
}

// 环境变量 / .env 里若已有可用的数据库配置，进向导前就先查一遍
// （本次会话刚装完的完成页除外，否则会被自己刚建的管理员挡住）
if (!$install_completed) {
    installer_refuse_if_installed(
        (string)(getenv('DB_HOST') ?: ''),
        (string)(getenv('DB_USER') ?: ''),
        (string)(getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''),
        (string)(getenv('DB_NAME') ?: '')
    );
}

$step = isset($_GET['step']) ? $_GET['step'] : 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$install_completed) {
    // 验证表单令牌
    if (!isset($_POST['form_token']) || !isset($_SESSION['form_token']) || 
        $_POST['form_token'] !== $_SESSION['form_token']) {
        $error = '表单已过期，请重新提交';
    } else if ($step == 1) {
        $dbHost = $_POST['db_host'];
        $dbUser = $_POST['db_user'];
        $dbPass = $_POST['db_pass'];
        $dbName = $_POST['db_name'];

        try {
            // 测试数据库连接
            $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 检查创建数据库权限
            $stmt = $pdo->query("SHOW GRANTS FOR CURRENT_USER");
            $hasCreateDbPermission = false;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $grant = array_values($row)[0];
                if (strpos($grant, 'ALL PRIVILEGES') !== false || 
                    strpos($grant, 'CREATE') !== false) {
                    $hasCreateDbPermission = true;
                    break;
                }
            }
            
            if (!$hasCreateDbPermission) {
                throw new PDOException('数据库用户缺少创建数据库的权限。请确保用户具有 CREATE 权限，或联系数据库管理员授予相应权限。');
            }

            // 目标库里已有管理员 => 拒绝重装
            installer_refuse_if_installed($dbHost, $dbUser, $dbPass, $dbName);

            // 保存数据库配置到会话
            $_SESSION['db_host'] = $dbHost;
            $_SESSION['db_user'] = $dbUser;
            $_SESSION['db_pass'] = $dbPass;
            $_SESSION['db_name'] = $dbName;
            $_SESSION['db_configured'] = true;
            
            // 重新生成表单令牌
            $_SESSION['form_token'] = bin2hex(random_bytes(32));
            header('Location: ?step=2');
            exit;
        } catch (PDOException $e) {
            // 详细原因只进服务器错误日志：
            // $e->getMessage() 里含主机名、库名和 MySQL 用户名，不能回显给前端。
            error_log('[install] 数据库连接失败：' . $e->getMessage());
            $error = '数据库连接失败。请检查：1. 数据库地址与端口；2. 用户名与密码；3. 该用户是否有 CREATE 权限。详细错误已记入服务器错误日志。';
        }
    } elseif ($step == 2 && isset($_SESSION['db_configured'])) {
        $adminUser = $_POST['admin_user'];
        $adminPass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
        $dbHost = $_SESSION['db_host'];
        $dbUser = $_SESSION['db_user'];
        $dbPass = $_SESSION['db_pass'];
        $dbName = $_SESSION['db_name'];

        // TRUNCATE TABLE admin 之前的最后一道检查
        installer_refuse_if_installed($dbHost, $dbUser, $dbPass, $dbName);

        try {
            // 连接数据库服务器
            $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 创建数据库
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $pdo->exec("USE `$dbName`");
            
            // 导入数据库结构
            $sql = file_get_contents(__DIR__ . '/../database.sql');
            $pdo->exec($sql);
            
            // 清空已存在的管理员账户
            $pdo->exec("TRUNCATE TABLE admin");
            
            // 创建管理员账户
            $stmt = $pdo->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
            $stmt->execute([$adminUser, $adminPass]);
            
            // 保存数据库配置到 .env
            //
            // 注意：这里只写 .env，不再重写 config.php。
            // 以前的做法是用一个内联模板把 config.php 整个覆盖掉，
            // 结果是仓库里对 config.php 的任何修复（.env 加载器、安装锁检查等）
            // 装完一次就被抹掉，而且数据库口令会被明文写进一个被版本控制跟踪的文件。
            $envPath = __DIR__ . '/../.env';
            $envLines = array(
                '# 由安装向导生成于 ' . date('Y-m-d H:i:s'),
                '# 该文件含数据库口令，已被 .gitignore 排除，请勿提交、勿对外暴露。',
                'DB_HOST=' . $dbHost,
                'DB_NAME=' . $dbName,
                'DB_USER=' . $dbUser,
                'DB_PASS=' . $dbPass,
                '',
            );
            if (file_put_contents($envPath, implode("\n", $envLines)) === false) {
                throw new RuntimeException('ENV_WRITE_FAILED');
            }
            @chmod($envPath, 0600);
            
            // 创建安装锁定文件
            file_put_contents(__DIR__ . '/../install.lock', date('Y-m-d H:i:s'));
            
            $success = '安装完成！';
            $_SESSION['install_completed'] = true;
            // 重新生成表单令牌
            $_SESSION['form_token'] = bin2hex(random_bytes(32));
            header('Location: ?step=3');
            exit;
        } catch (PDOException $e) {
            error_log('[install] 创建管理员账户失败：' . $e->getMessage());
            $error = '创建管理员账户失败，详细错误已记入服务器错误日志。';
        } catch (Exception $e) {
            if ($e->getMessage() === 'ENV_WRITE_FAILED') {
                $error = '无法写入 .env 文件，请检查网站根目录的写入权限后重试。';
            } else {
                $error = '安装失败，请查看服务器错误日志。';
            }
        }
    }
}

// 计算进度条百分比
$progress = ($step / 3) * 100;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装向导</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.2.3/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="css/install.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="install-container">
        <div class="install-header">
            <h1>系统安装向导</h1>
            <p>欢迎使用安装向导，请按照步骤完成系统安装</p>
        </div>

        <div class="progress-wrapper">
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="step-indicators">
                <div class="step-indicator <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">数据库配置</div>
                <div class="step-indicator <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">管理员设置</div>
                <div class="step-indicator <?php echo $step == 3 ? 'active' : ''; ?>">安装完成</div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
        <div class="install-form">
            <h2>数据库配置</h2>
            <form method="post">
                <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                <div class="form-group">
                    <label>数据库主机：</label>
                    <input type="text" class="form-control" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>数据库用户名：</label>
                    <input type="text" class="form-control" name="db_user" required>
                </div>
                <div class="form-group">
                    <label>数据库密码：</label>
                    <input type="password" class="form-control" name="db_pass">
                </div>
                <div class="form-group">
                    <label>数据库名：</label>
                    <input type="text" class="form-control" name="db_name" required>
                </div>
                <button type="submit" class="btn btn-primary">下一步</button>
            </form>
        </div>
        
        <?php elseif ($step == 2): ?>
        <div class="install-form">
            <h2>创建管理员账户</h2>
            <form method="post">
                <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                <div class="form-group">
                    <label>管理员用户名：</label>
                    <input type="text" class="form-control" name="admin_user" required>
                </div>
                <div class="form-group">
                    <label>管理员密码：</label>
                    <input type="password" class="form-control" name="admin_pass" required>
                </div>
                <button type="submit" class="btn btn-primary">完成安装</button>
            </form>
        </div>
        
        <?php elseif ($step == 3 && isset($_SESSION['install_completed'])): ?>
        <div class="completion-message">
            <h2>恭喜，系统安装完成！</h2>
            <p>您现在可以开始使用系统了。请使用以下链接访问：</p>
            <div class="completion-links">
                <a href="../" class="link-home" target="_blank">前台首页</a>
                <a href="../admin/" class="link-admin" target="_blank">后台管理</a>
            </div>
            <div class="security-notice">
                <strong>必做：立即删除 install/ 目录</strong>
                <p>安装向导会重建管理员账户。只要 install/ 目录还在服务器上，
                它就是一个接管入口。请在服务器上执行：</p>
                <pre style="background:#f5f5f5;padding:10px;overflow-x:auto">rm -rf install/</pre>
                <p>另请确认：<code>.env</code> 权限为 600，<code>logs/</code> 不可通过 Web 访问，
                并已为站点配置 HTTPS。</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.2.3/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>
</html>