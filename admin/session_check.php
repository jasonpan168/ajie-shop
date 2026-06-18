<?php
/**
 * 管理员会话安全检查
 *
 * 检查会话有效性、IP地址匹配、超时等
 */

// 会话超时设置（30分钟）
$session_timeout = 1800;

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// 检查会话超时
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $session_timeout) {
    session_destroy();
    header("Location: login.php?error=session_expired");
    exit;
}

// 检查 IP 地址一致性（防止会话劫持）
if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
    session_destroy();
    header("Location: login.php?error=ip_mismatch");
    exit;
}

// 更新最后活动时间
$_SESSION['last_activity'] = time();
