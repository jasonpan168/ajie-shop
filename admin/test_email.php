<?php
/**
 * 邮件测试系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 提供邮件服务器配置的测试功能，用于验证邮件发送功能是否正常，
 * 通过发送测试邮件来确认邮件服务器配置的正确性。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

session_start();
if (!isset($_SESSION['admin'])) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => '未登录或会话已过期']));
}

require_once __DIR__ . '/../send_mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['test_email'])) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => '无效的请求']));
}

// 获取测试邮箱地址
$test_email = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
if (!$test_email) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => '无效的邮箱地址']));
}

// 发送测试邮件
$subject = '邮件发送测试';
$body = '<h2>邮件发送测试</h2>'
      . '<p>这是一封测试邮件，用于验证邮件发送功能是否正常。</p>'
      . '<p>发送时间：' . date('Y-m-d H:i:s') . '</p>';

$result = sendMail($test_email, $subject, $body);

header('Content-Type: application/json');
if ($result === true) {
    echo json_encode(['success' => true, 'message' => '测试邮件发送成功']);
} else {
    echo json_encode(['success' => false, 'message' => $result]);
}