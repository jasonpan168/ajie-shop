<?php
/**
 * 删除卡密生成任务脚本
 * 
 * 该文件用于处理管理员删除自动卡密生成任务的操作，包括删除任务记录及相关卡密数据
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

    // 先删除与该任务关联的卡密记录
    $stmt = $pdo->prepare("DELETE FROM auto_cards WHERE task_id = ?");
    $stmt->execute([$task_id]);

    // 再删除任务记录
    $stmt = $pdo->prepare("DELETE FROM auto_card_tasks WHERE id = ?");
    $stmt->execute([$task_id]);

    header("Location: manage_card_tasks.php");
    exit;
}
?>