<?php
require_once 'db.php';
require_once 'clean_orders.php';
require_once 'lib/SafeOutput.php';

// 获取支付配置
$stmt = $pdo->query("SELECT * FROM epay_config LIMIT 1");
$epay_config = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM wechat_config LIMIT 1");
$wechat_config = $stmt->fetch(PDO::FETCH_ASSOC);

// 检查配置是否完整
// 字段以 database.sql 的 epay_config 表为准（该表没有 enabled 列）
// 回调地址必须是有效的 http(s) 地址，否则付款后订单不会入账，不能展示给买家
$valid_url = function ($u) { return is_string($u) && filter_var($u, FILTER_VALIDATE_URL) && preg_match('~^https?://~i', $u); };
$epay_ready = $epay_config &&
              !empty($epay_config['apiurl']) && !empty($epay_config['pid']) && !empty($epay_config['key']) &&
              $valid_url($epay_config['notify_url'] ?? '') && $valid_url($epay_config['return_url'] ?? '') &&
              (!empty($epay_config['alipay_enabled']) || !empty($epay_config['wxpay_enabled']) || !empty($epay_config['usdt_enabled']));
$epay_types = $epay_ready ? array_keys(array_filter([
    'alipay' => !empty($epay_config['alipay_enabled']),
    'wxpay'  => !empty($epay_config['wxpay_enabled']),
    'usdt'   => !empty($epay_config['usdt_enabled']),
])) : [];
$epay_type_names = ['alipay' => '支付宝', 'wxpay' => '微信', 'usdt' => 'USDT'];

$wechat_ready = $wechat_config &&
                isset($wechat_config['enabled']) && $wechat_config['enabled'] &&
                !empty($wechat_config['appid']) &&
                !empty($wechat_config['mch_id']) &&
                !empty($wechat_config['api_key']) &&
                strpos($wechat_config['appid'], '填写') === false &&
                strpos($wechat_config['mch_id'], '填写') === false &&
                strpos($wechat_config['api_key'], '填写') === false &&
                $valid_url($wechat_config['notify_url'] ?? '');

// 获取系统配置 - 优惠码功能是否启用
try {
    $stmt = $pdo->query("SELECT * FROM system_config WHERE `key` = 'coupon_enabled' LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    $coupon_enabled = isset($config['value']) ? (bool)$config['value'] : false;
} catch (Exception $e) {
    $coupon_enabled = false;
}

// 获取订单信息
$id       = isset($_GET['id']) ? intval($_GET['id']) : 0;
$nickname = isset($_GET['nickname']) && is_string($_GET['nickname']) ? trim($_GET['nickname']) : '';
$email    = isset($_GET['email']) && is_string($_GET['email']) ? trim($_GET['email']) : '';
$quantity = isset($_GET['quantity']) ? max(1, intval($_GET['quantity'])) : 1;

// 单价以数据库为准，只用于展示；真正扣款金额在下单时由服务端重新计算
$stmt = $pdo->prepare("SELECT price FROM products WHERE id = ? AND status = 1");
$stmt->execute([$id]);
$db_price = $stmt->fetchColumn();

/** 用站点主题展示无法下单的提示页 */
function checkout_notice($pdo, $title, $message, $id) {
    require_once __DIR__ . '/storefront/theme.php';
    $theme = sf_theme($pdo);
    sf_header($theme, $title);
    echo '<main id="main" class="page-main"><div class="panel result-panel"><h1>' . sf_e($title) . '</h1><p>' . sf_e($message) . '</p>'
        . '<a class="button" href="' . ($id > 0 ? 'product.php?id=' . (int) $id : 'index.php') . '">' . ($id > 0 ? '返回商品页' : '查看其他会员') . '</a></div></main>';
    sf_footer();
    exit;
}
if ($db_price === false) {
    http_response_code(404);
    checkout_notice($pdo, '商品不存在或已下架', '这件商品目前无法购买，去看看其他会员吧。', 0);
}
$price = (float) $db_price;
if ($price <= 0) {
    checkout_notice($pdo, '即将开售', '这件商品的价格还未公布，公布后即可下单购买。', $id);
}

require_once __DIR__.'/storefront/theme.php';
$theme=sf_theme($pdo);
$stmt=$pdo->prepare('SELECT title, stock FROM products WHERE id = ? AND status = 1');
$stmt->execute([$id]); $checkout_product=$stmt->fetch(PDO::FETCH_ASSOC);
if (!$checkout_product || $quantity > (int)$checkout_product['stock'] || !$nickname || !filter_var($email,FILTER_VALIDATE_EMAIL)) {
    http_response_code(422); sf_header($theme,'请核对订单信息');
    echo '<main id="main" class="page-main"><div class="panel result-panel"><h2>请重新确认商品与接收信息</h2><p>请填写有效邮箱、昵称，并确认购买数量未超出库存。</p><a class="button" href="product.php?id='.(int)$id.'">返回商品详情</a></div></main>';
    sf_footer(); exit;
}
// 只把「配置完整」的支付方式交给收银页展示
if (!$epay_ready) {
    $epay_config = ['alipay_enabled' => 0, 'wxpay_enabled' => 0, 'usdt_enabled' => 0];
}
$wechat_config = ['enabled' => $wechat_ready ? 1 : 0];
require __DIR__.'/storefront/checkout.php';
