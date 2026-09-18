<?php
session_start();

// 生成表单令牌
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

// 标记正在安装，防止config.php建立数据库连接
define('INSTALLING', true);

// 检查是否已安装
if (file_exists(__DIR__ . '/../install.lock')) {
    header('Location: ../');
    exit;
}

$step = isset($_GET['step']) ? $_GET['step'] : 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $error = '数据库连接失败：' . $e->getMessage() . '\n请检查：\n1. 数据库服务器地址是否正确\n2. 用户名和密码是否正确\n3. 数据库用户是否具有足够的权限';
        }
    } elseif ($step == 2 && isset($_SESSION['db_configured'])) {
        $adminUser = $_POST['admin_user'];
        $adminPass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
        $dbHost = $_SESSION['db_host'];
        $dbUser = $_SESSION['db_user'];
        $dbPass = $_SESSION['db_pass'];
        $dbName = $_SESSION['db_name'];

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
            $error = '创建管理员账户失败：' . $e->getMessage();
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
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.2.3/css/bootstrap.min.css" rel="stylesheet">
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
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
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
                <strong>安全提示：</strong> 请立即删除install目录以确保系统安全！
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.2.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>