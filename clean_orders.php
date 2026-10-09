<?php
/**
 * 订单清理和防刷系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 项目地址：https://github.com/jasonpan168/ajie-shop
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 定期清理未支付订单，实现订单防刷限制功能，
 * 包含错误日志记录和系统维护功能。
 * 
 * @license AGPL-3.0-or-later  https://github.com/jasonpan168/ajie-shop
 */

// 确保错误显示和日志记录已开启
// 只允许被其他页面 include，禁止外网直接访问执行
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/clean_orders.log');

// 记录脚本开始执行

// 检查logs目录是否存在并可写
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0777, true);
    error_log("[" . date('Y-m-d H:i:s') . "] 创建logs目录成功");
}

if (!is_writable(__DIR__ . '/logs')) {
    error_log("[" . date('Y-m-d H:i:s') . "] 警告：logs目录不可写，请检查权限设置");
    die("系统维护中，请稍后再试");
}

// 加载数据库配置
require_once 'db.php';

// 启动session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 使用全局PDO对象
global $pdo;

// 验证数据库连接
try {
    $pdo->query('SELECT 1');
} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] 数据库连接失败: " . $e->getMessage());
    die("系统繁忙，请稍后再试");
}

// 30分钟未支付的订单置为「已取消」并退回预扣库存（逻辑见 lib/order_service.php）。
// 不再删除：支付平台的回调可能晚于 30 分钟到达，订单删了就会「钱收了单没了」。
function cleanUnpaidOrders() {
    global $pdo;
    require_once __DIR__ . '/lib/order_service.php';
    try {
        $count = cancel_expired_orders($pdo, 30);
        if ($count > 0) {
            error_log("[" . date('Y-m-d H:i:s') . "] 已将{$count}个超时未支付订单置为已取消");
        }
    } catch (Exception $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] 清理未支付订单时出错: " . $e->getMessage());
    }
}

// IP限制检查（10分钟内最多允许3个订单，每次提交间隔至少60秒）
function checkIpLimit($ip) {
    global $pdo;
    try {
        // 检查是否为管理员账户 - 不再需要在这里启动session，因为已经在脚本开始时启动
        if (!empty($_SESSION['admin'])) {
            return true; // 管理员账户跳过IP限制检查
        }

        // 检查IP是否被永久解除限制
        $stmt = $pdo->prepare("SELECT permanently_unlocked FROM ip_limits WHERE ip = ? AND permanently_unlocked = 1");
        $stmt->execute([$ip]);
        if ($stmt->fetch()) {
            return true;
        }
        // 站点由 nginx 直连，只认 REMOTE_ADDR，不信任可伪造的 X-Forwarded-For / Client-IP
        $realIP = $_SERVER['REMOTE_ADDR'] ?? $ip;

        // 清理超过24小时的IP限制记录
        $stmt = $pdo->prepare("DELETE FROM ip_limits WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 24 HOUR) AND permanently_unlocked = 0");
        $stmt->execute();

        // 检查IP是否已被封禁
        $stmt = $pdo->prepare("SELECT * FROM ip_limits WHERE ip = ? AND is_blocked = 1 AND last_attempt > DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
        $stmt->execute([$realIP]);
        if ($stmt->fetch()) {
            error_log("[" . date('Y-m-d H:i:s') . "] IP: {$realIP} 已被封禁");
            return false;
        }

        // 检查60秒内是否有订单创建
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)");
        $stmt->execute([$realIP]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['count'] > 0) {
            error_log("[" . date('Y-m-d H:i:s') . "] IP: {$realIP} 提交过于频繁（60秒内）");
            return false;
        }
        
        // 检查10分钟内的订单数量（包括所有状态的订单）
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
        $stmt->execute([$realIP]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] >= 3) {
            // 更新IP限制记录
            updateIpLimitRecord($realIP, true);
            error_log("[" . date('Y-m-d H:i:s') . "] IP: {$realIP} 已达到订单限制（10分钟内{$result['count']}个已支付订单）");
            return false;
        }

        // 更新IP限制记录（正常访问）
        updateIpLimitRecord($realIP, false);
        return true;
    } catch (PDOException $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] 检查IP限制时出错: " . $e->getMessage());
        return false;
    }
}

// 更新IP限制记录
function updateIpLimitRecord($ip, $isViolation = false) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO ip_limits (ip, attempt_count, is_blocked, last_attempt) 
            VALUES (?, 1, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            attempt_count = CASE 
                WHEN last_attempt < DATE_SUB(NOW(), INTERVAL 10 MINUTE) THEN 1
                ELSE attempt_count + 1
            END,
            is_blocked = CASE 
                WHEN attempt_count >= 5 OR ? = 1 THEN 1 
                ELSE 0
            END,
            last_attempt = NOW()");
        $stmt->execute([$ip, $isViolation ? 1 : 0, $isViolation]);
    } catch (PDOException $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] 更新IP限制记录时出错: " . $e->getMessage());
    }
}

// 执行清理
try {
    cleanUnpaidOrders();
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] 执行订单清理时发生未知错误: " . $e->getMessage());
}
?>