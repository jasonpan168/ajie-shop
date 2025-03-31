<?php
/**
 * 易支付配置文件
 * 
 * 该文件主要用途是：
 * 管理易支付接口的配置信息，包括接口地址、商户ID、密钥等参数
 * 支持从环境变量或数据库获取配置
 * 
 * 开源协议：MIT License
 */

require_once __DIR__ . '/../db.php';

try {
    // 使用数据库单例
    $db = Database::getInstance();
    $stmt = $db->prepare('SELECT * FROM epay_config LIMIT 1');
    $stmt->execute();
    $cfg = $stmt->fetch();

    if (!$cfg) {
        // 从环境变量获取默认配置
        $cfg = [
            'apiurl' => getenv('EPAY_API_URL') ?: '在此填写易支付接口地址',
            'pid'    => getenv('EPAY_PID') ?: '在此填写商户ID',
            'key'    => getenv('EPAY_KEY') ?: '在此填写商户密钥',
            'notify_url' => getenv('EPAY_NOTIFY_URL') ?: '在此填写异步通知地址',
            'return_url' => getenv('EPAY_RETURN_URL') ?: '在此填写同步跳转地址',
        ];
    }

    // 验证配置有效性
    foreach (['apiurl', 'pid', 'key', 'notify_url', 'return_url'] as $required) {
        if (empty($cfg[$required])) {
            throw new Exception("支付配置错误：{$required} 不能为空");
        }
    }

    // 验证URL格式
    if (!filter_var($cfg['apiurl'], FILTER_VALIDATE_URL) ||
        !filter_var($cfg['notify_url'], FILTER_VALIDATE_URL) ||
        !filter_var($cfg['return_url'], FILTER_VALIDATE_URL)) {
        throw new Exception('支付配置错误：URL格式无效');
    }

    $epay_config = [
        'apiurl' => $cfg['apiurl'],
        'pid'    => $cfg['pid'],
        'key'    => $cfg['key'],
        'notify_url'  => $cfg['notify_url'],
        'return_url'  => $cfg['return_url']
    ];

} catch (Exception $e) {
    error_log('支付配置加载错误: ' . $e->getMessage());
    die('支付服务暂时不可用，请稍后再试');
}