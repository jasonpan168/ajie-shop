<?php require_once __DIR__.'/db.php'; require_once __DIR__.'/storefront/theme.php'; $theme=sf_theme($pdo); sf_header($theme,'订单查询'); ?>
<main id="main" class="page-main orders-main">
  <div class="orders-heading"><div><p class="orders-eyebrow"><?= sf_e(sf_brand()['latin']) ?> / MY ORDERS</p><h1>你的订单，进度尽在掌握。</h1><p class="muted">查看支付状态，找回会员订单。</p></div><a class="orders-back" href="index.php">继续选会员 ↗</a></div>
  <div class="orders-workspace">
    <section class="order-search-panel" aria-labelledby="lookup-title">
      <span class="order-section-number">01 / 查询订单</span><h2 id="lookup-title">找到你的 VIP</h2><p class="muted">输入下单时保存的订单号。</p>
      <form id="order-query" class="query-form"><label for="order_no">订单号</label><div class="order-input-wrap"><?= sf_icon('search') ?><input class="form-input" id="order_no" name="order_no" placeholder="粘贴你的订单号" maxlength="128" autocomplete="off" spellcheck="false" aria-describedby="order-input-hint" required value="<?= sf_e(is_string($_GET['order_no']??null)?$_GET['order_no']:'') ?>"></div><p id="order-input-hint" class="order-input-hint">可在下单后的付款页面找到订单号</p><button class="button order-search-button" type="submit"><span>查询订单</span><span aria-hidden="true">↗</span></button></form>
      <div class="order-help"><span><?= sf_icon('help') ?></span><div><h3>找不到订单号？</h3><p>查看购买帮助，了解订单查询方式。</p><a href="help.php">获取帮助 →</a></div></div>
    </section>
    <section class="order-result-panel" aria-label="订单详情"><div class="order-result-toolbar"><span class="order-section-number">02 / 订单详情</span><span class="order-private">个人信息脱敏展示</span></div><div id="query-result" class="query-result" aria-live="polite" aria-atomic="true"><div class="order-placeholder"><div class="order-placeholder-icon" aria-hidden="true"><?= sf_icon('order') ?></div><h2>每一次升级，都有迹可循</h2><p>输入订单号后，订单信息将显示在这里</p><div class="order-preview-fields"><span>会员商品</span><span>支付状态</span><span>订单金额</span></div></div></div></section>
  </div>
</main><?php sf_footer(); ?>
