<?php
/**
 * 系统全局配置
 * 
 * 该文件主要用途是：
 * 提供系统全局配置，包括数据库连接、调试模式设置、
 * 微信支付配置和加密密钥等核心系统参数。
 * 
 * 使用说明：
 * 1. 请根据实际环境修改数据库连接信息
 * 2. 配置微信支付相关参数
 * 3. 设置加密密钥用于API密钥加密
 * 
 * 开源协议：MIT License
 */

// 检查安装状态
$install_lock_file = __DIR__ . '/install.lock';
if (!file_exists($install_lock_file) && !defined('INSTALLING')) {
    header('Location: /install/');
    exit;
}

// 调试模式
define('DEBUG_MODE', false);

// 标记安装状态
$config = array();
$config['installed'] = true; // 如果能执行到这里，说明系统已经安装

// 从环境变量获取数据库配置，如果没有则使用默认值
$db_host = getenv('DB_HOST') ?: '在此填写数据库主机地址';
$db_name = getenv('DB_NAME') ?: '在此填写数据库名称';
$db_user = getenv('DB_USER') ?: '在此填写数据库用户名';
$db_pass = getenv('DB_PASS') ?: '在此填写数据库密码';

// 只在系统已安装的情况下建立数据库连接
if (!defined('INSTALLING')) {
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
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