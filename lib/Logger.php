<?php
/**
 * 日志和监控类
 *
 * 记录应用事件、安全事件、错误等信息
 */

class Logger {
    // 日志级别
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_SECURITY = 'SECURITY';

    // 日志目录
    private static $log_dir = null;

    // 初始化日志目录
    public static function init() {
        if (!self::$log_dir) {
            self::$log_dir = __DIR__ . '/../logs';
            if (!is_dir(self::$log_dir)) {
                mkdir(self::$log_dir, 0755, true);
            }
        }
    }

    /**
     * 写入日志
     */
    public static function log($level, $message, $context = []) {
        self::init();

        $timestamp = date('Y-m-d H:i:s');
        $ip = self::getClientIp();
        $user_id = isset($_SESSION['admin']) ? $_SESSION['admin'] : 'GUEST';

        // 构建日志行
        $log_entry = sprintf(
            "[%s] [%s] User:%s IP:%s | %s",
            $timestamp,
            $level,
            $user_id,
            $ip,
            $message
        );

        // 如果有额外的上下文信息，添加到日志
        if (!empty($context)) {
            $log_entry .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        // 按日期分组日志文件
        $log_file = self::$log_dir . '/' . date('Y-m-d') . '.log';

        // 写入文件
        file_put_contents($log_file, $log_entry . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * 记录用户操作
     */
    public static function logAction($action, $target, $details = []) {
        $message = "Action: $action | Target: $target";
        self::log(self::LEVEL_INFO, $message, $details);
    }

    /**
     * 记录安全事件
     */
    public static function logSecurityEvent($event, $severity = 'WARNING', $details = []) {
        $message = "Security Event: $event (Severity: $severity)";
        self::log(self::LEVEL_SECURITY, $message, $details);
    }

    /**
     * 记录 CSRF 攻击尝试
     */
    public static function logCsrfAttempt($page) {
        self::logSecurityEvent(
            'CSRF Token Mismatch',
            'HIGH',
            ['page' => $page, 'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown']
        );
    }

    /**
     * 记录登录尝试
     */
    public static function logLoginAttempt($username, $success = false) {
        $level = $success ? self::LEVEL_INFO : self::LEVEL_WARNING;
        $message = $success ? "Login successful for user: $username" : "Failed login attempt for user: $username";
        self::log($level, $message);
    }

    /**
     * 记录数据库错误
     */
    public static function logDatabaseError($error_message, $query = null) {
        $context = [];
        if ($query) {
            $context['query'] = substr($query, 0, 255); // 限制查询长度
        }
        self::log(self::LEVEL_ERROR, "Database Error: $error_message", $context);
    }

    /**
     * 记录 API 调用
     */
    public static function logApiCall($endpoint, $method, $status_code, $response_time = null) {
        $context = [
            'endpoint' => $endpoint,
            'method' => $method,
            'status' => $status_code
        ];
        if ($response_time) {
            $context['response_time_ms'] = $response_time;
        }
        self::log(self::LEVEL_INFO, "API Call", $context);
    }

    /**
     * 记录支付事件
     */
    public static function logPaymentEvent($event, $order_no, $amount, $details = []) {
        $context = array_merge([
            'order_no' => $order_no,
            'amount' => $amount
        ], $details);
        self::logSecurityEvent("Payment: $event", 'INFO', $context);
    }

    /**
     * 获取客户端 IP
     */
    private static function getClientIp() {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
            $ip = $ips[0];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }

        return $ip;
    }

    /**
     * 获取最近的日志
     */
    public static function getRecentLogs($days = 7, $level = null) {
        self::init();

        $logs = [];
        $start_date = strtotime("-$days days");

        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', $start_date + ($i * 86400));
            $log_file = self::$log_dir . '/' . $date . '.log';

            if (file_exists($log_file)) {
                $lines = file($log_file, FILE_IGNORE_NEW_LINES);
                foreach ($lines as $line) {
                    if ($level === null || strpos($line, "[$level]") !== false) {
                        $logs[] = $line;
                    }
                }
            }
        }

        return $logs;
    }

    /**
     * 清理旧日志（保留指定天数）
     */
    public static function cleanOldLogs($keep_days = 90) {
        self::init();

        $cutoff_time = time() - ($keep_days * 86400);
        $files = glob(self::$log_dir . '/*.log');

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
            }
        }
    }
}
