<?php
/**
 * Telegram通知系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 项目地址：https://github.com/jasonpan168/ajie-shop
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 提供Telegram机器人通知功能，用于发送订单通知、
 * 系统状态通知等信息到指定的Telegram群组或频道。
 * 
 * @license AGPL-3.0-or-later  https://github.com/jasonpan168/ajie-shop
 */

class TelegramNotifier {
    private $botToken;
    private $chatId;
    private $apiUrl = 'https://api.telegram.org/bot';

    public function __construct($botToken, $chatId) {
        $this->botToken = $botToken;
        $this->chatId = $chatId;
    }

    public function sendMessage($message) {
        $url = $this->apiUrl . $this->botToken . '/sendMessage';
        $data = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];
        error_log("Telegram API请求URL: " . $url);
        error_log("Telegram API请求参数: " . json_encode($data, JSON_UNESCAPED_UNICODE));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);
        
        error_log("Telegram API响应状态码: " . $httpCode);
        error_log("Telegram API响应内容: " . $result);
        if ($curlInfo) {
            error_log("Telegram API请求信息: " . json_encode($curlInfo, JSON_UNESCAPED_UNICODE));
        }

        if ($result === false) {
            $errorMessage = $curlError ?: 'Unknown error';
            error_log('Telegram通知发送失败 - CURL错误: ' . $errorMessage);
            throw new Exception('Telegram notification failed: ' . $errorMessage);
        }

        if ($httpCode !== 200) {
            error_log('Telegram API HTTP错误 - 状态码: ' . $httpCode . '\n响应内容: ' . $result);
            throw new Exception('Telegram API HTTP error: ' . $httpCode);
        }

        $response = json_decode($result, true);
        if (!$response['ok']) {
            $errorMessage = $response['description'] ?? 'Unknown error';
            error_log('Telegram API错误 - 错误描述: ' . $errorMessage);
            if (isset($response['error_code'])) {
                error_log('Telegram API错误代码: ' . $response['error_code']);
            }
            throw new Exception('Telegram API error: ' . $errorMessage);
        }

        return true;
    }

    public function sendOrderNotification($orderData) {
        $message = "【下单通知】您的店铺有人正在下单!\n";
        $message .= "-----------------------------\n";
        $message .= "商品名称：{$orderData['product_title']} * {$orderData['quantity']}\n";
        $message .= "订单号：{$orderData['order_no']}\n";
        $message .= "下单时间：{$orderData['created_at']}\n";
        $message .= "联系方式：{$orderData['email']}\n";
        $message .= "支付方式：{$orderData['pay_type']}\n";
        $message .= "支付金额：{$orderData['amount']}";

        return $this->sendMessage($message);
    }

    public function sendPaymentNotification($orderData) {
        $message = "🔥【支付通知】您的店铺刚刚支付了订单!\n";
        $message .= "-----------------------------\n";
        $message .= "商品名称：{$orderData['product_title']} * {$orderData['quantity']}\n";
        $message .= "订单号：{$orderData['order_no']}\n";
        $message .= "下单时间：{$orderData['created_at']}\n";
        $message .= "联系方式：{$orderData['email']}\n";
        $message .= "支付方式：{$orderData['pay_type']}\n";
        $message .= "支付金额：{$orderData['amount']}";

        return $this->sendMessage($message);
    }
}