<?php
/**
 * 系统全局配置
 * 
 * 该文件主要用途是：
 * 提供系统全局配置，包括数据库连接、调试模式设置、
 * 微信支付配置和加密密钥等核心系统参数。
 * 
 * 使用说明：
 * 1. 数据库连接信息请写在项目根目录的 .env 里（参考 .env.example），
 *    或直接用系统环境变量注入；环境变量优先于 .env
 * 2. 配置微信支付相关参数
 * 3. 设置加密密钥用于API密钥加密
 * 
 * @license AGPL-3.0-or-later  https://github.com/jasonpan168/ajie-shop
 */

// 检查安装状态
$install_lock_file = __DIR__ . '/install.lock';
if (!file_exists($install_lock_file) && !defined('INSTALLING')) {
    header('Location: /install/');
    exit;
}

// 调试模式
define('DEBUG_MODE', false);

/**
 * 零依赖的 .env 加载器。
 *
 * - 真实的系统环境变量优先级最高，.env 不会覆盖它；
 * - 以 # 或 ; 开头的行视为注释，空行忽略；
 * - 值两端的单/双引号会被去掉；
 * - 文件不存在或解析失败都不致命，直接回落到系统环境变量 / 代码默认值。
 */
if (!function_exists('load_env_file')) {
    function load_env_file($path) {
        if (!is_file($path) || !is_readable($path)) {
            return false;
        }
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return false;
        }

        $pairs = array();
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue; // 注释行
            }
            if (strpos($line, '=') === false) {
                continue; // 无法识别的行，跳过而不报错
            }
            $parts = explode('=', $line, 2);
            $key = trim($parts[0]);
            $value = isset($parts[1]) ? trim($parts[1]) : '';

            // 兼容 "export FOO=bar" 写法
            if (strpos($key, 'export ') === 0) {
                $key = trim(substr($key, 7));
            }
            if ($key === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                continue;
            }

            // 去掉成对的引号
            $len = strlen($value);
            if ($len >= 2) {
                $first = $value[0];
                $last = $value[$len - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            $pairs[$key] = $value;
        }

        foreach ($pairs as $key => $value) {
            // 真实环境变量优先：已存在就不覆盖
            if (getenv($key) !== false) {
                continue;
            }
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            if (!isset($_SERVER[$key])) {
                $_SERVER[$key] = $value;
            }
        }
        return true;
    }
}

// 加载项目根目录下的 .env（若存在）
load_env_file(__DIR__ . '/.env');

// 标记安装状态
$config = array();
$config['installed'] = true; // 如果能执行到这里，说明系统已经安装

// 从环境变量或 .env 文件获取数据库配置
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_name = getenv('DB_NAME') ?: 'ajie_shop';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

// 只在系统已安装的情况下建立数据库连接
if (!defined('INSTALLING')) {
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("数据库连接失败：" . $e->getMessage());
    }
}

// 微信支付配置默认值（当数据库中没有配置记录时使用）
$default_appid = '在此填写微信支付AppID';
$default_mch_id = '在此填写微信支付商户号';
// 注意：这里默认密钥为加密后的字符串
$default_api_key_encrypted = '在此填写加密后的微信支付API密钥';
$default_notify_url = '在此填写支付通知回调URL';

// 加密密钥，用于解密微信支付 API 密钥（AES-128-ECB 模式要求 16 字节）
$encryption_key = '在此填写16字节的加密密钥';

// 解密函数（使用 AES-128-ECB，加密方式可自行选择）
function decrypt_data($data, $key) {
    return openssl_decrypt($data, 'AES-128-ECB', $key);
}

// 默认解密得到商户 API 密钥
$default_api_key = decrypt_data($default_api_key_encrypted, $encryption_key);

// 从数据库中读取微信支付配置（假设 wechat_config 表中只存一条记录）
$configData = false;
if (!defined('INSTALLING') && isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM wechat_config LIMIT 1");
        $configData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $configData = false;
    }
}

if ($configData) {
    // 从数据库中读取的配置（假设 api_key 存储的是加密后的字符串，需要解密）
    $merchant_appid = $configData['appid'];
    $merchant_mchid = $configData['mch_id'];
    $merchant_api_key_encrypted = $configData['api_key'];
    $merchant_api_key = decrypt_data($merchant_api_key_encrypted, $encryption_key);
    $notify_url = $configData['notify_url'];
} else {
    // 使用默认配置
    $merchant_appid = $default_appid;
    $merchant_mchid = $default_mch_id;
    $merchant_api_key = $default_api_key;
    $notify_url = $default_notify_url;
}

// 供其他文件使用的微信支付相关变量
// $merchant_appid, $merchant_mchid, $merchant_api_key, $notify_url
?>