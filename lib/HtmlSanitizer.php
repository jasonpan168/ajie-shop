<?php
/**
 * HTML 内容安全过滤类
 *
 * 允许安全的富文本标签，防止XSS攻击
 */

class HtmlSanitizer {
    // 允许的安全标签
    private static $allowed_tags = [
        'p', 'br', 'strong', 'em', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'blockquote', 'img', 'a', 'div', 'span'
    ];

    /**
     * 清理用户输入的HTML内容
     * 只保留安全的标签和属性
     */
    public static function sanitize($html) {
        if (!is_string($html)) {
            return '';
        }

        // 使用 HTML Purifier 如果可用，否则使用基础过滤
        if (class_exists('HTMLPurifier')) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', implode(',', self::$allowed_tags) . '[img|src|alt|title];[a|href|title|target]');
            $purifier = new HTMLPurifier($config);
            return $purifier->purify($html);
        }

        // 备选方案：基础标签过滤
        $allowed_tags_str = '<' . implode('><', self::$allowed_tags) . '>';
        $html = strip_tags($html, $allowed_tags_str);

        // 移除危险属性（onclick, onerror, onload 等）
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\s*on\w+\s*=\s*[^>\s]*/i', '', $html);

        return $html;
    }

    /**
     * 验证 URL 是否安全
     */
    public static function isSafeUrl($url) {
        if (empty($url)) {
            return false;
        }

        // 允许相对URL和特定协议
        $allowed_protocols = ['http://', 'https://', '/'];

        foreach ($allowed_protocols as $protocol) {
            if (strpos($url, $protocol) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * 清理 img 标签中的 src
     */
    public static function sanitizeImageSrc($src) {
        // 只允许 http, https 和相对 URL
        if (self::isSafeUrl($src)) {
            return $src;
        }
        return '';
    }
}
