<?php
/**
 * AI 会员示例商品：在后台「商城模板」页一键导入，帮卖 AI 会员的店主快速上架。
 *
 * - 商品图：模板按商品名自动识别（ChatGPT / Claude / Gemini / Midjourney / Cursor /
 *   Perplexity / Notion / 效率）并使用各模板自带的卡面与图标；cover 填的是通用图标兜底。
 * - 价格：建议零售价（人民币 / 月），仅供参考，导入后请按你的进货成本修改。
 * - 已存在同名商品时跳过，不覆盖你改过的数据；重复导入是安全的。
 */

function sf_sample_catalog(): array {
    $detail = function (string $what, string $fit): string {
        return "【适合谁】{$fit}\n"
            . "【商品内容】{$what}，有效期以下单页标注为准。\n"
            . "【交付方式】付款成功后，开通信息会发送到你填写的邮箱；也可以在「订单查询」里用订单号查看进度。\n"
            . "【注意事项】请确认邮箱填写正确。虚拟商品开通后不支持退换，遇到问题请联系客服。";
    };
    return [
        ['title' => 'ChatGPT Plus', 'description' => '对话 · 写作 · 灵感', 'price' => 99,
         'icon' => 'chatgpt', 'detail' => $detail('ChatGPT Plus 会员 1 个月', '日常写作、学习、办公提效，想用上更强模型的人')],
        ['title' => 'Claude Pro', 'description' => '长文写作 · 编程 · 分析', 'price' => 129,
         'icon' => 'claude', 'detail' => $detail('Claude Pro 会员 1 个月', '需要处理长文档、写代码、做深度分析的人')],
        ['title' => 'Gemini Advanced', 'description' => '多模态理解 · 创作', 'price' => 89,
         'icon' => 'gemini', 'detail' => $detail('Gemini Advanced 会员 1 个月', '图片、文档、表格混合处理，常用 Google 全家桶的人')],
        ['title' => 'Midjourney', 'description' => '专业 AI 绘图 · 设计', 'price' => 109,
         'icon' => 'midjourney', 'detail' => $detail('Midjourney 订阅 1 个月', '设计师、自媒体配图、电商出图')],
        ['title' => 'Cursor Pro', 'description' => 'AI 编程 · 代码补全', 'price' => 129,
         'icon' => 'cursor', 'detail' => $detail('Cursor Pro 会员 1 个月', '程序员、独立开发者，想让 AI 帮你写代码的人')],
        ['title' => 'Perplexity Pro', 'description' => 'AI 搜索 · 知识问答', 'price' => 79,
         'icon' => 'perplexity', 'detail' => $detail('Perplexity Pro 会员 1 个月', '查资料、做调研、需要带来源引用的人')],
        ['title' => 'Notion AI', 'description' => '笔记 · 知识管理', 'price' => 69,
         'icon' => 'notion', 'detail' => $detail('Notion AI 附加功能 1 个月', '用 Notion 记笔记、管项目、写文档的人')],
        ['title' => 'AI 效率会员', 'description' => '多款 AI 工具 · 一站开通', 'price' => 59,
         'icon' => 'tools', 'detail' => $detail('精选 AI 效率工具组合 1 个月', '想一次体验多款 AI 工具的人')],
    ];
}

/**
 * 导入示例商品。
 *
 * @param bool $with_price false 时价格写 0，前台显示「价格待定」且不可购买
 * @return array ['created' => [标题...], 'skipped' => [标题...]]
 */
function sf_import_sample_catalog(PDO $pdo, bool $with_price = true, int $stock = 10): array {
    $created = [];
    $skipped = [];
    $exists = $pdo->prepare('SELECT COUNT(*) FROM products WHERE LOWER(title) = LOWER(?)');
    $insert = $pdo->prepare('INSERT INTO products (title, description, detail, price, stock, cover, sort_order, is_autocard, status)
                             VALUES (?, ?, ?, ?, ?, ?, ?, 0, 1)');
    $base = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM products')->fetchColumn();

    // MySQL 命名锁：两个管理员同时点导入时串行执行，避免各自查重通过后重复插入
    $mysql = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
    if ($mysql && (int) $pdo->query("SELECT GET_LOCK('storefront_sample_import', 10)")->fetchColumn() !== 1) {
        throw new RuntimeException('另一个导入正在进行，请稍后再试');
    }
    $pdo->beginTransaction();
    try {
        foreach (sf_sample_catalog() as $i => $item) {
            $exists->execute([$item['title']]);
            if ((int) $exists->fetchColumn() > 0) {
                $skipped[] = $item['title'];
                continue;
            }
            $insert->execute([
                $item['title'],
                $item['description'],
                $item['detail'],
                $with_price ? $item['price'] : 0,
                max(0, $stock),
                'assets/storefront/icons/' . $item['icon'] . '.svg',
                $base + $i + 1,
            ]);
            $created[] = $item['title'];
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    } finally {
        if ($mysql) $pdo->query("SELECT RELEASE_LOCK('storefront_sample_import')");
    }
    return ['created' => $created, 'skipped' => $skipped];
}
