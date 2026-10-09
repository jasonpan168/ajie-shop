<?php
// order_display.php
session_start();
require_once __DIR__.'/db.php';
require_once __DIR__.'/storefront/theme.php';
$theme=sf_theme($pdo);
if (!isset($_SESSION['order_data'])) {
    sf_header($theme,'查看支付订单'); echo '<main id="main" class="page-main"><div class="panel result-panel"><h2>没有待支付的二维码</h2><p>如果已经付款，请先查询订单，避免重复支付。</p><a href="orders.php" class="button">查询订单</a></div></main>'; sf_footer(); exit;
}
$order_no = $_SESSION['order_data']['order_no'];
$code_url = $_SESSION['order_data']['code_url'];
// 清除订单数据，避免刷新重复使用
unset($_SESSION['order_data']);

// 引入 phpqrcode 库（确保 phpqrcode 文件夹位于网站根目录下）
require_once 'phpqrcode/qrlib.php';

// 利用输出缓冲区生成二维码图片，并进行 Base64 编码
ob_start();
QRcode::png($code_url, null, QR_ECLEVEL_L, 6);
$imageData = base64_encode(ob_get_contents());
ob_end_clean();
sf_header($theme,'微信扫码支付');
?>
<main id="main" class="page-main"><section class="panel result-panel"><div class="result-icon">▦</div><h1>微信扫码支付</h1><p class="muted">打开微信扫一扫，完成本次支付。</p><div class="qr-code"><img src="data:image/png;base64,<?= $imageData ?>" alt="本次订单微信支付二维码" width="230" height="230"></div><p class="order-number">订单号：<?= sf_e($order_no) ?></p><p id="payment-status" aria-live="polite">等待支付结果…</p><div class="result-actions"><button id="copyBtn" class="button secondary" type="button">复制支付链接</button><a class="button" href="orders.php?order_no=<?= rawurlencode($order_no) ?>">查询订单</a></div></section></main>
<script>
const orderNo=<?= json_encode($order_no,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const paymentLink=<?= json_encode($code_url,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
let stopped=false;
async function pollPayment(){
 if(stopped)return;
 try{const response=await fetch('check_order.php?order_no='+encodeURIComponent(orderNo)); if(!response.ok)throw new Error(); const data=await response.json();
 if(data.status==='paid'){stopped=true;location.href='pay_success.php?order_no='+encodeURIComponent(orderNo);return;}
 if(data.status==='cancelled'){stopped=true;document.getElementById('payment-status').textContent='订单已取消，请查询订单详情。';return;}
 document.getElementById('payment-status').textContent='等待支付结果…';
 }catch{document.getElementById('payment-status').textContent='正在重新连接，可使用订单查询核对状态。';}
 if(!stopped)setTimeout(pollPayment,5000);
}
setTimeout(pollPayment,3000);
window.addEventListener('pagehide',()=>stopped=true);
document.getElementById('copyBtn').addEventListener('click',async()=>{try{await navigator.clipboard.writeText(paymentLink);document.getElementById('copyBtn').textContent='已复制';}catch{document.getElementById('payment-status').textContent='请使用微信扫描上方二维码。';}});
</script>
<?php sf_footer(); ?>
