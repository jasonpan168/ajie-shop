<?php
/**
 * 安全输出函数库
 *
 * 提供防XSS的输出函数，同时保留emoji等Unicode字符
 */

class SafeOutput {
    /**
     * 安全HTML输出（用于属性值）
     * 防止XSS同时保留emoji
     */
    public static function attr($value) {
        if (!is_string($value)) {
            $value = (string)$value;
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * 安全文本输出（用于普通文本）
     * 防止XSS同时保留emoji
     */
    public static function text($value) {
        if (!is_string($value)) {
            $value = (string)$value;
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * 安全HTML输出（允许特定标签）
     * 用于商品描述等富文本
     * 只允许安全的标签：<p>, <br>, <img>, <strong>, <em>, <u>
     */
    public static function richText($value) {
        if (!is_string($value)) {
            $value = (string)$value;
        }

        // 使用 strip_tags 只保留安全标签
        $allowed_tags = '<p><br><img><strong><em><u><h1><h2><h3><h4><h5><h6>';
        $value = strip_tags($value, $allowed_tags);

        return $value;
    }

    /**
     * JSON安全输出
     * 用于JavaScript中
     */
    public static function json($value) {
        if (!is_string($value)) {
            $value = (string)$value;
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * URL安全输出
     */
    public static function url($value) {
        if (!is_string($value)) {
            $value = (string)$value;
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
