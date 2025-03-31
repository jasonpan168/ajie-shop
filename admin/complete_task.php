<?php
/**
 * 完成卡密生成任务处理脚本
 * 
 * 该文件用于处理管理员标记自动生成卡密任务为已完成的操作
 * 
 * @author   Trae
 * @contact  contact@yewu.laikr.com
 * @date     2024-03-29
 */

session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require_once '../db.php';

if (isset($_GET['id'])) {
    $task_id = intval($_GET['id']);
    
    // 更新任务状态为完成
    $stmt = $pdo->prepare("UPDATE auto_card_tasks SET status = 'completed' WHERE id = ?");
    $stmt->execute([$task_id]);

    header("Location: manage_card_tasks.php");
    exit;
}
?>