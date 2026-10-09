<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/storefront/theme.php';
$theme=sf_theme($pdo);
$order_no=is_string($_GET['order_no']??null)?trim($_GET['order_no']):'';
$stmt=$pdo->prepare('SELECT status FROM orders WHERE order_no = ?');
$stmt->execute([$order_no]); $status=$stmt->fetchColumn();
$paid=$status==='paid';
sf_header($theme,$paid?'支付成功':'订单状态');
?>
<main id="main" class="page-main"><section class="panel result-panel"><div class="result-icon"><?= $paid?'✓':'◷' ?></div><h1><?= $paid?'支付成功':($status==='cancelled'?'订单已取消':'暂未确认支付') ?></h1><p class="order-number">订单号：<?= sf_e($order_no?:'未提供') ?></p><p class="muted"><?= $paid?'付款已确认，可前往订单查询查看订单信息。':'如果已经付款，请稍后查询订单状态，避免重复支付。' ?></p><div class="result-actions"><a class="button" href="orders.php?order_no=<?= rawurlencode($order_no) ?>">查看订单 →</a><a class="button secondary" href="index.php">继续选购</a></div></section></main><?php sf_footer(); ?>
