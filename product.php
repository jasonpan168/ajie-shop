<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/storefront/theme.php';
$theme=sf_theme($pdo);
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT) ?: 0;
$stmt=$pdo->prepare('SELECT * FROM products WHERE id = ? AND status = 1');
$stmt->execute([$id]); $product=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$product){ http_response_code(404); sf_header($theme,'商品不存在'); echo '<main id="main" class="page-main"><div class="panel result-panel"><h1>商品不存在或已下架</h1><a class="button" href="index.php">查看其他会员</a></div></main>'; sf_footer(); exit; }
$sf_pending=sf_price_pending($product); $sf_buyable=!$sf_pending && (int)$product['stock']>0;
sf_header($theme,$product['title']);
?>
<main id="main" class="page-main"><div class="breadcrumbs"><a href="index.php">会员商店</a> / <?= sf_e($product['title']) ?></div><div class="steps"><strong>01 选择商品</strong><span>02 确认与支付</span><span>03 查询订单</span></div><div class="purchase-grid"><section class="product-showcase"><?php sf_art($product,in_array($theme,['gold','gallery'],true),$theme); ?><h1><?= sf_e($product['title']) ?></h1><p><?= sf_e($product['description']??'') ?></p><span class="stock-label"><?= $sf_pending?'即将开售 · 价格待定':((int)$product['stock']>0?'有货 · 可选购':'暂时售罄') ?></span></section><section class="panel"><h2>确认你的会员</h2><div class="summary-line"><span>商品单价</span><strong><?= $sf_pending?'价格待定':'¥'.number_format((float)$product['price'],2) ?></strong></div><?php if($sf_buyable): ?><form method="get" action="choose_pay.php"><input type="hidden" name="id" value="<?= (int)$product['id'] ?>"><label class="form-field"><span>姓名 / 昵称</span><input type="text" name="nickname" autocomplete="nickname" placeholder="填写称呼，方便核对订单" maxlength="50" required></label><label class="form-field"><span>接收邮箱</span><input type="email" name="email" autocomplete="email" placeholder="name@example.com" maxlength="100" required><small class="form-hint">请仔细核对邮箱，订单通知将发送至此。</small></label><label class="form-field"><span>购买数量 · 库存 <?= (int)$product['stock'] ?></span><input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= max(1,min(100,(int)$product['stock'])) ?>" data-price="<?= sf_e($product['price']) ?>" required <?= $sf_buyable?'':'disabled' ?>></label><div class="summary-line summary-total"><span>商品合计</span><strong><?php if($sf_pending): ?>价格待定<?php else: ?>¥<span id="product-total"><?= number_format((float)$product['price'],2) ?></span><?php endif; ?></strong></div><button class="button wide" type="submit" <?= $sf_buyable?'':'disabled' ?>><?= $sf_pending?'即将开售':((int)$product['stock']>0?'下一步，选择支付方式 →':'暂时售罄') ?></button></form><?php else: ?><div class="notice unavailable-notice"><strong><?= $sf_pending?'即将开售':'暂时售罄' ?></strong><span><?= $sf_pending?'价格公布后即可在这里下单购买。':'补货后即可在这里下单购买。' ?></span></div><a class="button wide secondary" href="index.php">查看其他会员</a><?php endif; ?></section></div><section class="panel product-detail"><h2>商品说明</h2><?php
$detail=trim((string)($product['detail']??''));
if($detail==='') echo '<p class="muted">暂无补充说明，请购买前确认商品名称与规格。</p>';
elseif($detail===strip_tags($detail)) echo nl2br(preg_replace('~(https?://[^\s"\'<>]+?\.(?:png|jpe?g|gif|webp))(?=\s|$)~im','<img src="$1" alt="商品说明图片" loading="lazy">',sf_e($detail)));
else echo sf_rich_html($detail); // 商家富文本：白名单净化后输出
?></section></main><?php sf_footer(); ?>
