<?php
/** Shared presentation only. Payment amount and fulfilment remain in the order services. */

/**
 * 店铺品牌：默认是通用文字标志；在 storefront/brand.local.php 返回数组即可覆盖
 * （name 店名、latin 英文副标、tagline 页脚标语、logo / wordmark / buy_button 图片路径）。
 */
function sf_brand(): array {
    static $brand = null;
    if ($brand !== null) return $brand;
    $brand = [
        'name' => defined('SITE_NAME') ? SITE_NAME : 'AI 会员商店',
        'latin' => 'AI MEMBERSHIPS',
        'tagline' => '选好会员，开启更多可能。',
        'logo' => '',
        'wordmark' => '',
        'buy_button' => '',
    ];
    $local = __DIR__ . '/brand.local.php';
    if (is_file($local)) {
        $override = include $local;
        if (is_array($override)) $brand = array_merge($brand, array_intersect_key($override, $brand));
    }
    return $brand;
}
function sf_themes(): array {
    return [
        'blue' => ['name'=>'清爽蓝白', 'description'=>'居中竖卡 · 清晰选购'],
        'minimal' => ['name'=>'极简横卡', 'description'=>'双列横卡 · 价格优先'],
        'gold' => ['name'=>'琥珀黑金', 'description'=>'主推展台 · 金属质感'],
        'gallery' => ['name'=>'曜石展廊', 'description'=>'三列展廊 · 立体会员卡'],
        'neon' => ['name'=>'深色科技', 'description'=>'深蓝玻璃 · 青色微光'],
        'classic' => ['name'=>'经典商城', 'description'=>'封面网格 · 直观购买'],
    ];
}
function sf_e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function sf_valid_theme($value): bool { return is_string($value) && isset(sf_themes()[$value]); }
function sf_saved_theme($pdo): string {
    try {
        $q=$pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
        $q->execute(['storefront_theme']); $key=$q->fetchColumn();
        return sf_valid_theme($key) ? $key : 'neon';
    } catch (PDOException $e) { return 'neon'; }
}
function sf_save_theme($pdo, string $key): void {
    if (!sf_valid_theme($key)) { throw new InvalidArgumentException('无效模板'); }
    $sqlite=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite';
    $pdo->exec($sqlite
        ? 'CREATE TABLE IF NOT EXISTS system_settings (setting_key VARCHAR(255) NOT NULL PRIMARY KEY, setting_value TEXT)'
        : 'CREATE TABLE IF NOT EXISTS system_settings (setting_key VARCHAR(255) NOT NULL PRIMARY KEY, setting_value TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $sql=$sqlite
        ? 'INSERT INTO system_settings (setting_key,setting_value) VALUES (?,?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value'
        : 'INSERT INTO system_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)';
    $pdo->prepare($sql)->execute(['storefront_theme',$key]);
}
function sf_theme($pdo): string {
    if (session_status()===PHP_SESSION_NONE && isset($_COOKIE[session_name()])) { session_start(); }
    if (!empty($_SESSION['admin'])) {
        header('Cache-Control: private, no-store');
        if (isset($_GET['end_preview'])) { unset($_SESSION['storefront_preview']); }
        if (sf_valid_theme($_GET['preview_theme'] ?? null)) { $_SESSION['storefront_preview']=$_GET['preview_theme']; }
        if (sf_valid_theme($_SESSION['storefront_preview'] ?? null)) { return $_SESSION['storefront_preview']; }
    }
    return sf_saved_theme($pdo);
}
function sf_url($value): string {
    $value=trim((string)$value);
    if ($value==='' || preg_match('/[\x00-\x20\\\\]/', $value) || strncmp($value,'//',2)===0) { return ''; }
    if (preg_match('~^[a-z][a-z0-9+.-]*:~i',$value) && !preg_match('~^https?://~i',$value)) { return ''; }
    return $value;
}
/**
 * 商品详情富文本：白名单净化（DOM 解析），只保留排版标签；
 * 链接、图片地址只允许 http(s) 与站内相对路径，其余属性（含 on*、style）全部移除。
 */
function sf_rich_html(string $html): string {
    if (!class_exists('DOMDocument')) return nl2br(sf_e(strip_tags($html)));
    $allowed = ['p'=>[],'br'=>[],'strong'=>[],'b'=>[],'em'=>[],'i'=>[],'u'=>[],'h2'=>[],'h3'=>[],'h4'=>[],
        'ul'=>[],'ol'=>[],'li'=>[],'blockquote'=>[],'div'=>[],'span'=>[],'a'=>['href','title'],'img'=>['src','alt','title']];
    $doc = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="sf-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
    libxml_clear_errors(); libxml_use_internal_errors($prev);
    $root = $doc->getElementById('sf-root');
    if (!$root) return nl2br(sf_e(strip_tags($html)));
    $walk = function (DOMNode $node) use (&$walk, $allowed) {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (in_array($tag, ['script','style','iframe','object','embed','form','input','button','textarea','select','svg','math','template','link','meta'], true)) {
                    $node->removeChild($child); continue;
                }
                if (!isset($allowed[$tag])) {           // 不认识的标签：去壳留内容
                    $walk($child);
                    while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
                    $node->removeChild($child); continue;
                }
                foreach (iterator_to_array($child->attributes) as $attr) {
                    $name = strtolower($attr->name);
                    if (!in_array($name, $allowed[$tag], true)) { $child->removeAttribute($attr->name); continue; }
                    if (in_array($name, ['href','src'], true) && sf_url($attr->value) === '') $child->removeAttribute($attr->name);
                }
                if ($tag === 'a') { $child->setAttribute('rel', 'nofollow noopener noreferrer'); $child->setAttribute('target', '_blank'); }
                if ($tag === 'img') { $child->setAttribute('loading', 'lazy'); if (!$child->hasAttribute('src')) { $node->removeChild($child); continue; } }
                $walk($child);
            } elseif ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
            }
        }
    };
    $walk($root);
    $out = '';
    foreach ($root->childNodes as $child) $out .= $doc->saveHTML($child);
    return $out;
}
function sf_category(array $p): string {
    $s=mb_strtolower($p['title'].' '.($p['description']??''));
    if (preg_match('/chatgpt|claude|gemini|grok/iu',$p['title'])) return 'chat';
    if (preg_match('/cursor|copilot|编程|代码/iu',$s)) return 'code';
    if (preg_match('/midjourney|绘画|图像|绘图|视频/iu',$s)) return 'create';
    if (preg_match('/chatgpt|claude|gemini|grok|对话/iu',$s)) return 'chat';
    return 'tools';
}
function sf_icon(string $name): string {
    $paths=[
        'search'=>'<circle cx="10.5" cy="10.5" r="6.8"/><path d="m16 16 4.5 4.5"/>',
        'headphones'=>'<path d="M4 14v-2a8 8 0 0 1 16 0v6a3 3 0 0 1-3 3h-3"/><rect x="2" y="11" width="4" height="8" rx="2"/><rect x="18" y="11" width="4" height="8" rx="2"/>',
        'order'=>'<path d="M15 21H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v9M8 7h6M8 11h6M8 15h3"/><circle cx="18" cy="18" r="3"/><path d="m20 20 2 2"/>',
        'help'=>'<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 4.2 1.8c-.9.6-1.7 1-1.7 2.7M12 17h.01"/>',
    ];
    return '<svg class="ui-icon" aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">'.($paths[$name]??'').'</svg>';
}
function sf_header(string $theme, string $title, array $menus=[], bool $home=false): void {
    $dark=in_array($theme,['gold','gallery','neon'],true);
    ?><!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="<?= $dark?'dark':'light' ?>"><title><?= sf_e($title===sf_brand()['name'] ? $title : $title.' · '.sf_brand()['name']) ?></title><link rel="icon" href="assets/storefront/favicon.ico"><link rel="stylesheet" href="assets/storefront/storefront.css?v=20261009-12"><script src="assets/storefront/storefront.js?v=20261009-12" defer></script></head><body class="sf theme-<?= sf_e($theme) ?>"><a class="skip" href="#main">跳转到内容</a>
    <?php if (!empty($_SESSION['admin']) && sf_valid_theme($_SESSION['storefront_preview']??null)): ?><aside class="preview-bar">正在预览：<?= sf_e(sf_themes()[$theme]['name']) ?>（仅自己可见） <a href="admin/storefront_themes.php">返回模板设置</a><a href="index.php?end_preview=1">结束预览</a></aside><?php endif; ?>
    <header class="site-header"><div class="header-inner"><?php $b=sf_brand(); ?><a class="brand" href="index.php" aria-label="<?= sf_e($b['name']) ?> 首页"><?php if($dark && $b['wordmark']): ?><img class="brand-wordmark" src="<?= sf_e($b['wordmark']) ?>" alt="<?= sf_e($b['name'].' · '.$b['latin']) ?>" width="176" height="56"><?php else: ?><?php if($b['logo']): ?><img src="<?= sf_e($b['logo']) ?>" alt="" width="54" height="54"><?php else: ?><span class="brand-mark" aria-hidden="true"><?= sf_e(mb_substr($b['name'],0,1)) ?></span><?php endif; ?><span><?= sf_e($b['name']) ?><small><?= sf_e($b['latin']) ?></small></span><?php endif; ?></a><nav aria-label="主导航"><a href="index.php">会员商店</a><a href="orders.php">订单查询</a><a href="help.php">购买帮助</a><?php foreach ($menus as $menu): $url=sf_url($menu['url']??''); if($url): ?><a href="<?= sf_e($url) ?>"><?= sf_e($menu['name']??'') ?></a><?php endif; endforeach; ?></nav><?php if($home && ($dark || $theme==='classic')): ?><label class="search-box header-search"><?= sf_icon('search') ?><input id="product-search" type="search" placeholder="搜索 AI 会员" aria-label="搜索商品"></label><?php endif; ?><a class="header-help" href="help.php"><?= sf_icon('headphones') ?> <span>服务与帮助</span></a></div></header>
    <?php
}
function sf_footer(): void { ?>
    <footer class="site-footer"><div class="footer-bottom"><span><?= sf_e(sf_brand()['name']) ?> <small>· <?= sf_e(sf_brand()['latin']) ?></small></span><span><?= sf_e(sf_brand()['tagline']) ?></span></div></footer></body></html><?php }
function sf_product_key(array $p): string {
    foreach (['chatgpt'=>'chatgpt','claude'=>'claude','gemini'=>'gemini','cursor'=>'cursor','midjourney'=>'midjourney','perplexity'=>'perplexity','notion'=>'notion','效率'=>'tools'] as $match=>$icon) {
        if (mb_stripos($p['title'],$match)!==false) return $icon;
    }
    return '';
}
function sf_art(array $p, bool $physical=false, string $theme=''): void {
    $key=sf_product_key($p); $url=sf_url($p['cover']??'');
    $passName=['chatgpt'=>'ChatGPT','claude'=>'Claude','gemini'=>'Gemini','midjourney'=>'Midjourney','cursor'=>'Cursor','perplexity'=>'Perplexity','notion'=>'Notion','tools'=>'AI Tools'][$key]??$p['title'];
    $passTier=preg_match('/\b(Plus|Pro|Advanced)\b/i',$p['title'],$tier)?strtoupper($tier[1]):'VIP';
    $custom=$key==='';
    if ($key && !$custom) $url='assets/storefront/'.(in_array($theme,['blue','minimal'],true)?'light':'emblems').'/'.$key.'.webp';
    if ($theme==='classic' && in_array($key,['chatgpt','gemini'],true)) $url='assets/storefront/classic/'.$key.'.webp';
    if ($physical && $key && !$custom) {
        ?><div class="product-art physical-art generated-art art-<?= sf_e($key) ?>" aria-hidden="true"><div class="generated-pass"><img class="card-render" src="assets/storefront/cards/<?= sf_e($theme==='gold' && $key==='chatgpt' && is_file(__DIR__.'/../assets/storefront/cards/chatgpt-gold.webp')?'chatgpt-gold':$key) ?>.webp" alt="" loading="lazy"><span class="generated-pass-name"><?= sf_e($passName) ?></span><span class="generated-pass-meta"><?= sf_e($passTier) ?><br>MEMBERSHIP</span></div></div><?php
        return;
    }
    ?><div class="product-art <?= $physical?'physical-art':'' ?> art-<?= sf_e($key?:'custom') ?>" aria-hidden="true"><div class="membership-pass"><div class="product-emblem"><?php if($url): ?><img src="<?= sf_e($url) ?>" alt="" loading="lazy"><?php else: ?><span><?= sf_e(mb_substr($p['title'],0,1)) ?></span><?php endif; ?></div><span class="pass-name"><?= sf_e($p['title']) ?></span><span class="pass-meta">VIP<br>MEMBERSHIP</span></div></div><?php
}
/** 价格未设置（≤0）的商品只展示，不可购买 */
function sf_price_pending(array $p): bool { return (float)($p['price']??0) <= 0; }
function sf_product_card(array $p,string $theme): void {
    $category=sf_category($p); $physical=in_array($theme,['gold','gallery'],true); $pending=sf_price_pending($p); $stock=$pending?0:(int)($p['stock']??0);
    ?><article class="product-card" data-category="<?= $category ?>" data-search="<?= sf_e(mb_strtolower($p['title'].' '.($p['description']??''))) ?>" data-price="<?= sf_e($p['price']) ?>" data-stock="<?= $stock ?>">
      <?php sf_art($p,$physical,$theme); ?><div class="product-copy"><h2><?= sf_e($p['title']) ?></h2><p class="description"><?= sf_e($p['description']??'') ?></p><?php if(!$pending): ?><span class="stock-label"><?= $stock>0?'有货 · 可选购':'暂时售罄' ?></span><?php endif; ?></div>
      <div class="product-buy"><div class="price"><?php if($pending): ?><strong class="price-pending">价格待定</strong><?php else: ?><span>¥</span><strong><?= sf_e($theme==='classic'?number_format((float)$p['price'],2,'.',''):rtrim(rtrim(number_format((float)$p['price'],2,'.',''),'0'),'.')) ?></strong><?php endif; ?></div><ul class="product-benefits"><?php $benefits=preg_split('/[·\n]/u',trim((string)($p['description']??''))); foreach(array_slice(array_filter(array_map('trim',$benefits)),0,3) as $benefit): ?><li><?= sf_e($benefit) ?></li><?php endforeach; ?></ul><?php if($stock>0): ?><a class="button buy-button<?= $theme==='neon' && sf_brand()['buy_button']?' vip-art-button':'' ?>" href="product.php?id=<?= (int)$p['id'] ?>"><?php if($theme==='neon' && sf_brand()['buy_button']): ?><img src="<?= sf_e(sf_brand()['buy_button']) ?>" alt="选购" width="128" height="54"><?php else: ?><?= $theme==='classic'?'立即购买':($theme==='blue'?'选购会员':'选购') ?><span aria-hidden="true"> ↗</span><?php endif; ?><span class="sr-only"><?= sf_e($p['title']) ?></span></a><?php else: ?><button class="button" disabled><?= $pending?'即将开售':'暂时售罄' ?></button><?php endif; ?></div>
    </article><?php
}
