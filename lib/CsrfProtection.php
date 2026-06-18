<?php
/**
 * CSRF 保护类
 *
 * 为所有表单生成和验证 CSRF 令牌
 */

class CsrfProtection {
    private static $token_name = 'csrf_token';
    private static $token_length = 32;

    /**
     * 生成 CSRF 令牌
     */
    public static function generateToken() {
        if (!isset($_SESSION[self::$token_name])) {
            $_SESSION[self::$token_name] = bin2hex(random_bytes(self::$token_length));
        }
        return $_SESSION[self::$token_name];
    }

    /**
     * 获取 CSRF 令牌（用于表单）
     */
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="' . self::$token_name . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * 验证 CSRF 令牌
     */
    public static function validateToken() {
        $submitted_token = $_POST[self::$token_name] ?? '';
        $session_token = $_SESSION[self::$token_name] ?? '';

        if (empty($submitted_token) || empty($session_token)) {
            return false;
        }

        // 使用哈希比较防止时序攻击
        return hash_equals($session_token, $submitted_token);
    }

    /**
     * 刷新令牌
     */
    public static function refreshToken() {
        $_SESSION[self::$token_name] = bin2hex(random_bytes(self::$token_length));
        return $_SESSION[self::$token_name];
    }
}
