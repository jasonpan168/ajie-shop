<?php
session_start();
require_once __DIR__.'/session_check.php';
require_once __DIR__.'/../db.php';
require_once __DIR__.'/../storefront/theme.php';
require_once __DIR__.'/../storefront/catalog.php';
header('Cache-Control: private, no-store');
if(empty($_SESSION['storefront_csrf'])) $_SESSION['storefront_csrf']=bin2hex(random_bytes(32));
$error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
    if(!is_string($_POST['csrf']??null) || !hash_equals($_SESSION['storefront_csrf'],$_POST['csrf'])) {
        http_response_code(403); $error='请求已过期，请刷新页面后再试。';
    } elseif(($_POST['action']??'')==='import_samples') {
        try {
            $r=sf_import_sample_catalog($pdo, empty($_POST['price_pending']));
            $_SESSION['storefront_import']=$r; header('Location: storefront_themes.php#sample-products'); exit;
        } catch(Throwable $e) { http_response_code(500); error_log('Sample catalog import failed: '.$e->getMessage()); $error='示例商品导入失败，请检查数据库连接后重试。'; }
    } elseif(!sf_valid_theme($_POST['theme']??null)) {
        http_response_code(422); $error='请选择有效模板。';
    } else {
        try { sf_save_theme($pdo,$_POST['theme']); unset($_SESSION['storefront_preview']); $_SESSION['storefront_saved']=true; header('Location: storefront_themes.php'); exit; }
        catch(PDOException $e) { http_response_code(500); error_log('Storefront theme save failed: '.$e->getCode()); $error='模板保存失败，请检查数据库写入及建表权限后重试。'; }
    }
}
$saved=!empty($_SESSION['storefront_saved']); unset($_SESSION['storefront_saved']);
$import=$_SESSION['storefront_import']??null; unset($_SESSION['storefront_import']);
$product_count=(int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$active=sf_saved_theme($pdo); $page_title='商城模板'; $current_page='storefront_themes';
require __DIR__.'/includes/header.php';
?>
<style>.theme-settings{width:100%}.theme-settings-head{display:flex;justify-content:space-between;gap:20px;align-items:center;margin-bottom:24px}.theme-options{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:22px}.theme-option{border:1px solid var(--a-line);border-radius:var(--a-radius);overflow:hidden;background:var(--a-surface)}.theme-option.is-active{border-color:var(--a-ink);box-shadow:0 0 0 1px var(--a-ink)}.theme-thumb{display:block;aspect-ratio:1.4;background:var(--a-hover);overflow:hidden;border-bottom:1px solid var(--a-line)}.theme-thumb img{width:100%;height:100%;object-fit:cover;object-position:top}.theme-description{padding:18px}.theme-description h2{font-family:var(--a-serif);font-size:18px;margin-bottom:6px}.theme-description p{font-size:13px;color:var(--a-muted)}.theme-actions{display:flex;gap:12px;align-items:center}.theme-actions form{margin:0}.theme-label{font-family:var(--a-sans);font-size:12px;font-weight:500;color:var(--a-accent);margin-left:8px}.sample-box{display:flex;gap:24px;align-items:center;justify-content:space-between;border:1px solid var(--a-line);border-radius:var(--a-radius);background:var(--a-surface);padding:22px 24px;margin-bottom:26px}.sample-box.is-empty{border-color:var(--a-ink);box-shadow:0 0 0 1px var(--a-ink)}.sample-box h2{font-family:var(--a-serif);font-size:18px;margin:0 0 6px}.sample-box p{margin:0;color:var(--a-muted);font-size:13px;max-width:760px}.sample-icons{display:flex;gap:6px;margin-top:12px;flex-wrap:wrap}.sample-icons img{width:28px;height:28px;border-radius:6px;background:var(--a-hover);padding:4px}.sample-form{display:flex;flex-direction:column;align-items:flex-end;gap:10px;flex-shrink:0}.sample-form label{font-size:13px;font-weight:400;color:var(--a-muted);margin:0}@media(max-width:650px){.theme-settings-head{display:block}.sample-box{display:block}.sample-form{align-items:stretch;margin-top:16px}}</style>
<div class="theme-settings"><div class="theme-settings-head"><div><h1>商城模板</h1><p class="text-muted">当前全站模板：<?= sf_e(sf_themes()[$active]['name']) ?>。预览仅自己可见，启用后所有访客生效。</p></div><a class="btn btn-outline-primary" href="../index.php?end_preview=1" target="_blank" rel="noopener">查看商城 ↗</a></div>
<?php if($saved): ?><div class="alert alert-success" role="status">模板已启用。首页与购买、支付、订单页面将使用新的主题。</div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger" role="alert"><?= sf_e($error) ?></div><?php endif; ?>
<section id="sample-products" class="sample-box <?= $product_count===0?'is-empty':'' ?>"><div><h2>一键导入 AI 会员示例商品</h2><p><?= $product_count===0?'你的商城还没有商品。':'' ?>导入 ChatGPT Plus、Claude Pro、Gemini、Midjourney、Cursor、Perplexity、Notion AI 等 <?= count(sf_sample_catalog()) ?> 个商品，自带各模板的商品图、建议价格和商品说明，导入后在「商品管理」里改成你自己的价格、库存和交付方式即可。已有同名商品会自动跳过，不会覆盖。</p><div class="sample-icons" aria-hidden="true"><?php foreach(sf_sample_catalog() as $item): ?><img src="../assets/storefront/icons/<?= sf_e($item['icon']) ?>.svg" alt=""><?php endforeach; ?></div></div><form method="post" class="sample-form"><input type="hidden" name="csrf" value="<?= sf_e($_SESSION['storefront_csrf']) ?>"><input type="hidden" name="action" value="import_samples"><label><input type="checkbox" name="price_pending" value="1"> 价格先空着（前台显示「价格待定」，暂不可购买）</label><button type="submit" class="btn btn-primary">导入示例商品</button></form></section>
<?php if($import): ?><div class="alert <?= $import['created']?'alert-success':'alert-info' ?>" role="status"><?php if($import['created']): ?>已导入 <?= count($import['created']) ?> 个商品：<?= sf_e(implode('、',$import['created'])) ?>。<?php endif; ?><?php if($import['skipped']): ?> 已存在、已跳过：<?= sf_e(implode('、',$import['skipped'])) ?>。<?php endif; ?> <a href="products.php">去商品管理修改价格与库存 →</a></div><?php endif; ?>
<div class="theme-options"><?php foreach(sf_themes() as $key=>$info): ?><article class="theme-option <?= $active===$key?'is-active':'' ?>"><a class="theme-thumb" href="../index.php?preview_theme=<?= $key ?>" target="_blank" rel="noopener"><img src="../assets/storefront/previews/<?= sf_e($key) ?>.webp" alt="<?= sf_e($info['name']) ?>设计参考" loading="lazy"></a><div class="theme-description"><h2><?= sf_e($info['name']) ?><?php if($active===$key): ?><span class="theme-label">正在使用</span><?php endif; ?></h2><p><?= sf_e($info['description']) ?></p><div class="theme-actions"><a class="btn btn-outline-secondary btn-sm" href="../index.php?preview_theme=<?= $key ?>" target="_blank" rel="noopener">预览实页 ↗</a><form method="post"><input type="hidden" name="csrf" value="<?= sf_e($_SESSION['storefront_csrf']) ?>"><input type="hidden" name="theme" value="<?= $key ?>"><button type="submit" class="btn btn-primary btn-sm" <?= $active===$key?'disabled':'' ?>><?= $active===$key?'已启用':'启用模板' ?></button></form></div></div></article><?php endforeach; ?></div><p class="text-muted mt-4">缩略图为设计参考，实际页面展示你的商品、价格与库存。模板切换不会改动商品或订单。</p></div>
<?php require __DIR__.'/includes/footer.php'; ?>
