<?php
/**
 * return_url.php
 * 彩虹易支付同步返回页面
 *
 * 功能：
 * 1. 通过 SDK 验证返回参数签名。
 * 2. 根据返回的交易状态显示支付成功或失败信息。
 * 3. 显示高端、简洁的页面，包含图标、提示信息及返回首页按钮。
 */
require_once("lib/epay.config.php");
require_once("lib/EpayCore.class.php");

$epay = new EpayCore($epay_config);
$verify_result = $epay->verifyReturn();

$resultMessage = "";
$status = "";
if ($verify_result) {
    $out_trade_no = $_GET['out_trade_no'] ?? '';
    $trade_no     = $_GET['trade_no'] ?? '';
    $trade_status = $_GET['trade_status'] ?? '';
    $type         = $_GET['type'] ?? '';

    if ($trade_status == 'TRADE_SUCCESS') {
        // 此处调用你的订单处理逻辑，例如 updateOrderStatus($out_trade_no, 'paid');
        $resultMessage = "支付成功！订单号：" . htmlspecialchars($out_trade_no);
        $status = "success";
    } else {
        $resultMessage = "交易状态：" . htmlspecialchars($trade_status);
        $status = "failed";
    }
    $resultMessage .= "<br>验证成功";
} else {
    $resultMessage = "验证失败";
    $status = "failed";
}

require_once __DIR__.'/storefront/theme.php';
$theme=sf_theme($pdo);
sf_header($theme,'支付返回');
?>
<main id="main" class="page-main"><section class="panel result-panel"><div class="result-icon"><?= $status==='success'?'✓':'!' ?></div><h1><?= $status==='success'?'支付已返回':'暂未确认支付' ?></h1><p><?= $resultMessage ?></p><p class="muted">最终订单状态请以订单查询结果为准。</p><div class="result-actions"><a class="button" href="orders.php?order_no=<?= rawurlencode(is_string($out_trade_no??null)?$out_trade_no:'') ?>">查看订单</a><a class="button secondary" href="index.php">返回商城</a></div></section></main><?php sf_footer(); ?>
