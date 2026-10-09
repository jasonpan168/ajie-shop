<?php
/**
 * 订单服务：下单定价、优惠码占用、支付入账、发卡，全部在服务端完成。
 *
 * 规则：
 * - 价格、优惠金额一律以数据库为准，不信任浏览器传来的任何金额字段；
 * - 金额统一用「分」(int) 计算和比较，避免浮点误差；
 * - 支付回调必须金额一致才入账，同一订单只入账一次（行锁 + 状态判断）；
 * - 日志写在 logs/ 目录（nginx 已禁止外网访问），不记录卡密明文。
 */

require_once __DIR__ . '/../db.php';

const ORDER_MIN_CENTS = 1; // 微信/易支付最小支付 0.01 元

// 邮件主题、管理员提醒里显示的站点名，可在 config.php 里 define('SITE_NAME', '你的站名') 覆盖
if (!defined('SITE_NAME')) {
    define('SITE_NAME', '商城');
}

function app_log($name, $message) {
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    @file_put_contents($dir . '/' . $name . '.log', date('Y-m-d H:i:s') . ' ' . $message . "\n", FILE_APPEND);
}

/** 客户端 IP：站点由 nginx 直连，只认 REMOTE_ADDR，不信任可伪造的 X-Forwarded-For */
/** 回滚当前事务（MySQL 死锁时事务已被自动回滚，再 rollBack 会抛异常） */
function safe_rollback(PDO $pdo) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

/** 取请求参数里的字符串，传数组等非字符串一律当空串，避免 trim() 抛 TypeError */
function str_param(array $src, $key) {
    return isset($src[$key]) && is_string($src[$key]) ? trim($src[$key]) : '';
}

function client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function yuan_to_cents($yuan) {
    return (int) round(((float) $yuan) * 100);
}

function cents_to_yuan($cents) {
    return number_format($cents / 100, 2, '.', '');
}

/**
 * 同一 IP 的「频率检查 + 建单」串行执行，避免并发请求同时通过限流检查。
 * 用 MySQL 命名锁，锁在连接断开时自动释放。
 */
function with_ip_lock(PDO $pdo, $ip, callable $fn) {
    $name = 'shop_order_' . md5($ip);
    $stmt = $pdo->prepare("SELECT GET_LOCK(?, 5)");
    $stmt->execute([$name]);
    if ((int) $stmt->fetchColumn() !== 1) {
        throw new RuntimeException('当前下单人数较多，请稍后再试。');
    }
    try {
        return $fn();
    } finally {
        $pdo->prepare("SELECT RELEASE_LOCK(?)")->execute([$name]);
    }
}

/** 发信，失败自动重试（共 3 次，间隔 1s/3s） */
function send_mail_with_retry($to, $subject, $body) {
    $last = null;
    foreach ([0, 1, 3] as $wait) {
        if ($wait) {
            sleep($wait);
        }
        $last = sendMail($to, $subject, $body);
        if ($last === true) {
            return true;
        }
    }
    app_log('mail', "发信失败(已重试3次) $to: " . (is_string($last) ? $last : json_encode($last)));
    return $last;
}

/** 不可预测的订单号：时间 + 6 位安全随机数 */
function new_order_no() {
    return date('YmdHis') . random_int(100000, 999999);
}

function coupon_enabled(PDO $pdo) {
    try {
        $row = $pdo->query("SELECT value FROM system_config WHERE `key` = 'coupon_enabled' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        return $row && (bool) $row['value'];
    } catch (Exception $e) {
        return false;
    }
}

/** 读取上架商品，不存在或已下架返回 null */
function find_on_sale_product(PDO $pdo, $product_id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 1");
    $stmt->execute([(int) $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    return $product ?: null;
}

/**
 * 创建待支付订单。价格取数据库，优惠码在事务内校验并占用。
 * 失败抛出 RuntimeException（消息可直接展示给用户）。
 *
 * @return array 订单行（含 amount_cents）
 */
function create_pending_order(PDO $pdo, array $input, $pay_type) {
    $product_id  = (int) str_param($input, 'id');
    $quantity    = (int) str_param($input, 'quantity');
    $nickname    = str_param($input, 'nickname');
    $email       = str_param($input, 'email');
    $coupon_code = str_param($input, 'coupon_code_hidden');

    if ($nickname === '' || mb_strlen($nickname) > 50) {
        throw new RuntimeException('请填写 50 字以内的姓名/昵称。');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        throw new RuntimeException('请填写正确的邮箱。');
    }
    if ($quantity < 1 || $quantity > 100) {
        throw new RuntimeException('购买数量不正确。');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 1 FOR UPDATE");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            throw new RuntimeException('商品不存在或已下架。');
        }
        if ($quantity > (int) $product['stock']) {
            throw new RuntimeException('库存不足。');
        }

        $total_cents = yuan_to_cents($product['price']) * $quantity;
        if ($total_cents < ORDER_MIN_CENTS) {
            throw new RuntimeException('商品价格配置错误，请联系客服。');
        }

        $order_no = new_order_no();
        $coupon_id = null;
        $discount_cents = 0;

        if ($coupon_code !== '') {
            if (!coupon_enabled($pdo)) {
                throw new RuntimeException('优惠码功能未启用。');
            }
            $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' FOR UPDATE");
            $stmt->execute([$coupon_code]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$coupon) {
                throw new RuntimeException('优惠码无效或已被使用。');
            }
            // 至少保留 0.01 元实付，支付渠道不接受 0 元订单
            $discount_cents = min(yuan_to_cents($coupon['discount_amount']), $total_cents - ORDER_MIN_CENTS);
            $discount_cents = max(0, $discount_cents);
            $coupon_id = (int) $coupon['id'];
            $pdo->prepare("UPDATE coupons SET status = 'used', used_at = NOW(), used_order_no = ? WHERE id = ? AND status = 'active'")
                ->execute([$order_no, $coupon_id]);
        }

        $amount_cents = $total_cents - $discount_cents;

        // 下单即预扣库存，防止多笔待付订单超卖；超时取消时退回
        $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$quantity, $product_id]);

        $pdo->prepare("
            INSERT INTO orders
                (order_no, product_id, product_title, nickname, email, quantity, amount, status, created_at, pay_type, coupon_id, coupon_code, coupon_amount, ip)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?, ?, ?, ?, ?)
        ")->execute([
            $order_no,
            $product_id,
            $product['title'],
            $nickname,
            $email,
            $quantity,
            cents_to_yuan($amount_cents),
            $pay_type,
            $coupon_id,
            $coupon_id ? $coupon_code : null,
            cents_to_yuan($discount_cents),
            client_ip(),
        ]);

        $pdo->commit();
    } catch (Exception $e) {
        safe_rollback($pdo);
        throw $e;
    }

    return [
        'order_no'      => $order_no,
        'product_id'    => $product_id,
        'product_title' => $product['title'],
        'nickname'      => $nickname,
        'email'         => $email,
        'quantity'      => $quantity,
        'amount'        => cents_to_yuan($amount_cents),
        'amount_cents'  => $amount_cents,
        'pay_type'      => $pay_type,
        'created_at'    => date('Y-m-d H:i:s'),
    ];
}

/**
 * 拉起支付失败时立即取消：退回预扣库存并释放优惠码。
 * 仅用于用户还没拿到支付二维码/链接的场景；超时取消见 clean_orders.php（不释放优惠码）。
 */
function cancel_pending_order(PDO $pdo, $order_no) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT id, product_id, quantity FROM orders WHERE order_no = ? AND status = 'pending' FOR UPDATE");
        $stmt->execute([$order_no]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($order) {
            $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$order['id']]);
            $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")->execute([(int) $order['quantity'], (int) $order['product_id']]);
            $pdo->prepare("UPDATE coupons SET status = 'active', used_at = NULL, used_order_no = NULL WHERE used_order_no = ? AND status = 'used'")
                ->execute([$order_no]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        safe_rollback($pdo);
        app_log('order', "取消订单失败 $order_no: " . $e->getMessage());
    }
}

/**
 * 超时未支付：置为已取消并退回预扣库存。优惠码不释放——
 * 迟到的付款仍会入账，若释放了优惠码，同一张码就能被两笔订单各用一次。
 * 需要让顾客重新用码，由管理员在后台手动恢复。
 */
function cancel_expired_orders(PDO $pdo, $minutes = 30) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT id, product_id, quantity FROM orders WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL ? MINUTE) FOR UPDATE");
        $stmt->execute([(int) $minutes]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $restock = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $cancel = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        foreach ($rows as $r) {
            $restock->execute([(int) $r['quantity'], (int) $r['product_id']]);
            $cancel->execute([$r['id']]);
        }
        $pdo->commit();
        return count($rows);
    } catch (Exception $e) {
        safe_rollback($pdo);
        throw $e;
    }
}

/**
 * 支付成功入账：校验金额、置为已支付、扣库存、发卡，只会成功执行一次。
 *
 * @return array ['result' => paid|duplicate|not_found|amount_mismatch, 'order' => 行, 'card' => 卡密|null]
 */
function fulfill_paid_order(PDO $pdo, $order_no, $paid_cents, $channel) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_no = ? FOR UPDATE");
        $stmt->execute([$order_no]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            safe_rollback($pdo);
            app_log('payment', "[$channel] 订单不存在 $order_no");
            return ['result' => 'not_found', 'order' => null, 'card' => null];
        }
        if ($order['status'] === 'paid') {
            safe_rollback($pdo);
            app_log('payment', "[$channel] 重复通知，已忽略 $order_no");
            return ['result' => 'duplicate', 'order' => $order, 'card' => null];
        }
        $expected = yuan_to_cents($order['amount']);
        if ((int) $paid_cents !== $expected) {
            safe_rollback($pdo);
            app_log('payment', "[$channel] 金额不符，拒绝入账 $order_no 应付{$expected}分 实付{$paid_cents}分");
            return ['result' => 'amount_mismatch', 'order' => $order, 'card' => null];
        }

        // pending 订单下单时已预扣库存；超时取消后才到账的订单库存已退回，需重新扣
        $was_cancelled = $order['status'] === 'cancelled';
        $pdo->prepare("UPDATE orders SET status = 'paid' WHERE id = ?")->execute([$order['id']]);
        if ($was_cancelled) {
            // 不截断到 0：库存可能被其他订单预占，负数如实记录欠账，负库存期间无法再下单
            $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
                ->execute([(int) $order['quantity'], (int) $order['product_id']]);
        }

        $card = allocate_card($pdo, $order);
        // 只有卡密发齐才标记已发卡；发不齐保持 0，方便管理员补发
        if ($card !== null && $card['complete']) {
            $pdo->prepare("UPDATE orders SET card_sent = 1 WHERE id = ?")->execute([$order['id']]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        safe_rollback($pdo);
        app_log('payment', "[$channel] 入账异常 $order_no: " . $e->getMessage());
        throw $e;
    }

    if ($was_cancelled) {
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([(int) $order['product_id']]);
        $left = (int) $stmt->fetchColumn();
        if ($left < 0) {
            alert_admin($pdo, "订单 {$order_no} 超时取消后才付款，商品库存已被其他订单占用，现库存为 {$left}，请补货或联系买家处理");
        }
    }

    $order['status'] = 'paid';
    app_log('payment', "[$channel] 入账成功 $order_no 金额{$paid_cents}分" . ($card !== null ? ($card['complete'] ? ' 已发卡' : ' 卡密未发齐') : ''));
    return ['result' => 'paid', 'order' => $order, 'card' => $card];
}

/**
 * 在入账事务内分配卡密。
 * @return array|null ['content' => 发给买家的内容, 'complete' => 是否发齐]；非自动发卡商品返回 null
 */
function allocate_card(PDO $pdo, array $order) {
    $stmt = $pdo->prepare("SELECT is_autocard FROM products WHERE id = ?");
    $stmt->execute([(int) $order['product_id']]);
    if ((int) $stmt->fetchColumn() !== 1) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT * FROM auto_card_tasks WHERE product_id = ? AND status = 'active' ORDER BY created_at ASC LIMIT 1");
    $stmt->execute([(int) $order['product_id']]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        app_log('payment', "订单 {$order['order_no']} 商品未配置发卡任务");
        return ['content' => '发货内容准备中，客服会尽快通过邮件补发。', 'complete' => false];
    }

    if (isset($task['repeat_flag']) && (int) $task['repeat_flag'] === 1) {
        // 固定发卡：每单发同一条内容
        $stmt = $pdo->prepare("SELECT card_content FROM auto_cards WHERE task_id = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$task['id']]);
        $content = $stmt->fetchColumn();
        return $content !== false
            ? ['content' => $content, 'complete' => true]
            : ['content' => '发货内容准备中，客服会尽快通过邮件补发。', 'complete' => false];
    }

    // 依次发卡：每件商品占用一条未使用卡密
    $quantity = max(1, (int) $order['quantity']);
    $stmt = $pdo->prepare("SELECT id, card_content FROM auto_cards WHERE task_id = ? AND status = 'unused' ORDER BY id ASC LIMIT $quantity FOR UPDATE");
    $stmt->execute([$task['id']]);
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$cards) {
        app_log('payment', "订单 {$order['order_no']} 卡密库存不足");
        return ['content' => '发货内容准备中，客服会尽快通过邮件补发。', 'complete' => false];
    }
    $mark = $pdo->prepare("UPDATE auto_cards SET status = 'used', email = ?, order_no = ?, created_at = NOW() WHERE id = ?");
    $contents = [];
    foreach ($cards as $c) {
        $mark->execute([$order['email'], $order['order_no'], $c['id']]);
        $contents[] = $c['card_content'];
    }
    $complete = count($cards) >= $quantity;
    if (!$complete) {
        app_log('payment', "订单 {$order['order_no']} 卡密不足，应发{$quantity} 实发" . count($cards));
        $contents[] = '（其余卡密客服会尽快通过邮件补发）';
    }
    return ['content' => implode('<br>', $contents), 'complete' => $complete];
}

/** 给管理员发电报提醒（需要人工处理的情况），失败只记日志 */
function alert_admin(PDO $pdo, $text) {
    app_log('alert', $text);
    try {
        require_once __DIR__ . '/TelegramNotifier.php';
        $tg = $pdo->query("SELECT * FROM telegram_config WHERE enabled = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($tg) {
            (new TelegramNotifier($tg['bot_token'], $tg['chat_id']))->sendMessage('⚠️ ' . SITE_NAME . ' 需人工处理：' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        }
    } catch (Throwable $e) {
        app_log('alert', '电报提醒发送失败: ' . $e->getMessage());
    }
}

/** 邮件发不出去时给管理员的处理提示：附上已分配的发货内容，方便直接转发给买家 */
function paid_manual_hint($card) {
    if ($card === null) {
        return '该商品不自动发货，请手动联系买家。';
    }
    return '请把以下发货内容手动发给买家：' . str_replace('<br>', ' / ', $card['content']);
}

/** 入账成功后的通知：买家邮件 + 电报 + WxPusher。失败只记日志并提醒管理员，不影响入账。 */
function send_paid_notifications(PDO $pdo, array $order, $card) {
    require_once __DIR__ . '/../send_mail.php';
    $title = htmlspecialchars($order['product_title'] ?? '', ENT_QUOTES, 'UTF-8');
    $no = htmlspecialchars($order['order_no'], ENT_QUOTES, 'UTF-8');

    if ($card !== null && !$card['complete']) {
        alert_admin($pdo, "订单 {$order['order_no']} 已付款但卡密没有发齐（卡密库存不足或未配置发卡任务），请补卡后手动发给 {$order['email']}");
    }

    try {
        $r1 = send_mail_with_retry($order['email'], SITE_NAME . "：您的订单 {$order['order_no']} 已支付成功",
            "<p>您好，</p><p>您的订单 <strong>{$no}</strong> 已支付成功！</p>"
            . "<p>产品：{$title}</p><p>数量：" . (int) $order['quantity'] . "</p>"
            . "<p>实付金额：￥" . htmlspecialchars($order['amount']) . "</p><p>感谢您的购买！</p>");
        $r2 = true;
        if ($card !== null) {
            $r2 = send_mail_with_retry($order['email'], SITE_NAME . "：您的订单 {$order['order_no']} 发货内容",
                "<p>您好，</p><p>您的订单 <strong>{$no}</strong> 已支付成功，以下是发货内容：</p>"
                . "<p>产品：{$title}</p><p>卡密/内容：<br>{$card['content']}</p><p>感谢您的购买！</p>");
        }
        if ($r1 !== true || $r2 !== true) {
            alert_admin($pdo, "订单 {$order['order_no']} 已付款，但发给买家 {$order['email']} 的邮件发送失败（已重试3次）。" . paid_manual_hint($card));
        }
    } catch (Throwable $e) {
        alert_admin($pdo, "订单 {$order['order_no']} 已付款，但邮件发送异常（" . $e->getMessage() . "）。买家 {$order['email']}。" . paid_manual_hint($card));
    }

    try {
        require_once __DIR__ . '/TelegramNotifier.php';
        $tg = $pdo->query("SELECT * FROM telegram_config WHERE enabled = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($tg) {
            (new TelegramNotifier($tg['bot_token'], $tg['chat_id']))->sendPaymentNotification($order);
        }
    } catch (Throwable $e) {
        app_log('payment', "订单 {$order['order_no']} 电报通知失败: " . $e->getMessage());
    }

    try {
        require_once __DIR__ . '/WxPusherNotifier.php';
        (new WxPusherNotifier())->sendPaymentNotification($order);
    } catch (Throwable $e) {
        app_log('payment', "订单 {$order['order_no']} WxPusher 通知失败: " . $e->getMessage());
    }
}

/** 下单通知（电报 + WxPusher），失败只记日志 */
function send_order_created_notifications(PDO $pdo, array $order) {
    try {
        require_once __DIR__ . '/TelegramNotifier.php';
        $tg = $pdo->query("SELECT * FROM telegram_config WHERE enabled = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($tg) {
            (new TelegramNotifier($tg['bot_token'], $tg['chat_id']))->sendOrderNotification($order);
        }
    } catch (Throwable $e) {
        app_log('order', "订单 {$order['order_no']} 电报下单通知失败: " . $e->getMessage());
    }
    try {
        require_once __DIR__ . '/WxPusherNotifier.php';
        (new WxPusherNotifier())->sendOrderNotification($order);
    } catch (Throwable $e) {
        app_log('order', "订单 {$order['order_no']} WxPusher 下单通知失败: " . $e->getMessage());
    }
}
