<?php
/**
 * 输入验证类
 *
 * 提供安全的输入验证和清理函数
 */

class InputValidator {
    /**
     * 验证邮箱地址
     */
    public static function validateEmail($email) {
        // 使用PHP内置过滤
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // 额外的长度检查
        if (strlen($email) > 254) {
            return false;
        }

        return true;
    }

    /**
     * 验证和清理邮箱
     */
    public static function sanitizeEmail($email) {
        $email = trim(strtolower($email));

        // 基础验证
        if (!self::validateEmail($email)) {
            return false;
        }

        return $email;
    }

    /**
     * 验证 URL
     */
    public static function validateUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * 验证 IP 地址
     */
    public static function validateIp($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * 验证整数范围
     */
    public static function validateIntRange($value, $min = null, $max = null) {
        $value = intval($value);

        if ($min !== null && $value < $min) {
            return false;
        }

        if ($max !== null && $value > $max) {
            return false;
        }

        return true;
    }

    /**
     * 验证浮点数范围
     */
    public static function validateFloatRange($value, $min = null, $max = null) {
        $value = floatval($value);

        if ($min !== null && $value < $min) {
            return false;
        }

        if ($max !== null && $value > $max) {
            return false;
        }

        return true;
    }

    /**
     * 验证字符串长度
     */
    public static function validateStringLength($str, $min = 1, $max = null) {
        $len = mb_strlen($str, 'UTF-8');

        if ($len < $min) {
            return false;
        }

        if ($max !== null && $len > $max) {
            return false;
        }

        return true;
    }

    /**
     * 清理用户名（仅允许字母、数字、下划线、中文）
     */
    public static function sanitizeUsername($username) {
        $username = trim($username);

        // 允许字母、数字、下划线、中文
        if (!preg_match('/^[\p{L}\p{N}_]+$/u', $username)) {
            return false;
        }

        // 长度限制
        if (!self::validateStringLength($username, 3, 32)) {
            return false;
        }

        return $username;
    }

    /**
     * 清理电话号码
     */
    public static function sanitizePhone($phone) {
        // 移除所有非数字字符
        $phone = preg_replace('/[^0-9+\-\s()]/', '', $phone);
        $phone = trim($phone);

        // 检查长度
        if (strlen($phone) < 8 || strlen($phone) > 20) {
            return false;
        }

        return $phone;
    }

    /**
     * 验证中文手机号
     */
    public static function validateChinesePhone($phone) {
        return preg_match('/^1[3-9]\d{9}$/', $phone) === 1;
    }

    /**
     * 清理文件名
     */
    public static function sanitizeFilename($filename) {
        // 移除路径遍历字符
        $filename = str_replace(['/', '\\', '..', "\0"], '', $filename);

        // 只允许字母、数字、下划线、点
        if (!preg_match('/^[\w\.\-]+$/', $filename)) {
            return false;
        }

        return $filename;
    }

    /**
     * 检查是否为有效的 JSON
     */
    public static function isValidJson($json) {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
