<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 引入 PHPMailer 类文件（假设 PHPMailer 文件夹位于网站根目录）
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once 'db.php';  // 确保 db.php 正确设置了 $pdo 对象

/**
 * 从数据库中读取邮箱配置（假设配置存储在 email_settings 表中）
 * 返回配置数组，如果未设置则返回 false
 */
function getEmailConfig($pdo) {
    $stmt = $pdo->query("SELECT * FROM email_settings LIMIT 1");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * sendMail 发送邮件函数
 *
 * @param string $to      收件人邮箱
 * @param string $subject 邮件主题（请使用UTF-8编码）
 * @param string $body    邮件正文（支持HTML格式，使用UTF-8编码）
 * @return bool|string    成功返回 true，失败返回错误信息
 */
function sendMail($to, $subject, $body) {
    global $pdo;
    $config = getEmailConfig($pdo);
    
    if (!$config) {
        return json_encode(['success' => false, 'message' => '邮箱配置未设置！']);
    }
    
    $mail = new PHPMailer(true);
    try {
        // 如需调试，可启用以下行：
//         $mail->SMTPDebug = 2;
        
        // 使用 SMTP 方式发送邮件
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        
        // 根据不同的发信通道配置SMTP参数
        switch($config['channel_type']) {
            case 'mailgun':
                $mail->Host = 'smtp.mailgun.org';
                $mail->SMTPSecure = 'ssl';
                $mail->Port = 465;
                break;
            case 'smtp':
            default:
                $mail->Host = $config['smtp_host'];
                $mail->SMTPSecure = $config['smtp_port'] == 587 ? 'tls' : 'ssl';
                $mail->Port = $config['smtp_port'];
                break;
        }
        
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_user'];
        $mail->Password = $config['smtp_pass'];
        
        // 发件人设置：使用数据库中存储的发件人邮箱和名称
        $mail->setFrom($config['sender_email'], $config['sender_name']);
        // 收件人设置
        $mail->addAddress($to);
        
        // 邮件内容设置
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        return true;

    } catch (Exception $e) {
        return '邮件发送失败: ' . $mail->ErrorInfo;
    }
}
?>