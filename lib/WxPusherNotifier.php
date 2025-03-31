<?php
/**
 * WxPusher消息通知类
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 通过WxPusher平台实现微信消息推送功能，用于发送订单通知和支付成功通知等系统消息。
 * 支持从数据库配置或直接传参两种方式初始化，可灵活控制不同类型通知的开启状态。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

class WxPusherNotifier {
    private $appToken;
    private $adminUid;
    private $apiUrl = 'http://wxpusher.zjiecode.com/api/send/message';
    
    private $enabled;
    private $orderNotifyEnabled;
    private $paymentNotifyEnabled;
    
    public function __construct($appToken = null, $adminUid = null) {
        global $pdo;
        
        // 如果没有传入参数，从数据库获取配置
        if ($appToken === null || $adminUid === null) {
            $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN (
                'wxpusher_app_token', 'wxpusher_admin_uid', 'wxpusher_enabled',
                'wxpusher_order_notify', 'wxpusher_payment_notify'
            )");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $this->appToken = $settings['wxpusher_app_token'] ?? '';
            $this->adminUid = $settings['wxpusher_admin_uid'] ?? '';
            $this->enabled = ($settings['wxpusher_enabled'] ?? '0') === '1';
            $this->orderNotifyEnabled = ($settings['wxpusher_order_notify'] ?? '0') === '1';
            $this->paymentNotifyEnabled = ($settings['wxpusher_payment_notify'] ?? '0') === '1';
        } else {
            $this->appToken = $appToken;
            $this->adminUid = $adminUid;
            $this->enabled = true;
            $this->orderNotifyEnabled = true;
            $this->paymentNotifyEnabled = true;
        }
    }
    
    public function sendMessage($content, $summary = '') {
        if (empty($this->appToken) || empty($this->adminUid)) {
            return false;
        }
        
        $data = [
            'appToken' => $this->appToken,
            'content' => $content,
            'summary' => $summary,
            'contentType' => 1,
            'uids' => [$this->adminUid],
        ];
        
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    public function sendOrderNotification($orderInfo) {
        if (!$this->enabled || !$this->orderNotifyEnabled) {
            return false;
        }
        
        $content = "💰 新订单通知\n";
        $content .= "订单号：{$orderInfo['order_no']}\n";
        $content .= "商品：{$orderInfo['product_title']}\n";
        $content .= "金额：¥{$orderInfo['amount']}\n";
        $content .= "昵称：{$orderInfo['nickname']}\n";
        $content .= "邮箱：{$orderInfo['email']}\n";
        $content .= "时间：" . date('Y-m-d H:i:s');
        
        return $this->sendMessage($content, "新订单：{$orderInfo['product_title']}");
    }
    
    public function sendPaymentNotification($orderInfo) {
        if (!$this->enabled || !$this->paymentNotifyEnabled) {
            return false;
        }
        
        $content = "✅ 支付成功通知\n";
        $content .= "订单号：{$orderInfo['order_no']}\n";
        $content .= "商品：{$orderInfo['product_title']}\n";
        $content .= "金额：¥{$orderInfo['amount']}\n";
        $content .= "昵称：{$orderInfo['nickname']}\n";
        $content .= "邮箱：{$orderInfo['email']}\n";
        $content .= "时间：" . date('Y-m-d H:i:s');
        
        return $this->sendMessage($content, "支付成功：{$orderInfo['product_title']}");
    }
}

/**
 * 文件结束
 * WxPusher消息通知类结束
 */