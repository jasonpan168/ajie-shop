<?php
/**
 * 店铺品牌配置示例。复制为 storefront/brand.local.php 后修改即可，模板会自动使用。
 * 不创建这个文件时，模板显示通用文字标志（店名默认取 config.php 里的 SITE_NAME，再没有就是「AI 会员商店」）。
 * 图片路径相对于网站根目录，建议放在 assets/ 下。
 */
return [
    'name' => '你的店名',            // 页头、页脚、浏览器标题
    'latin' => 'YOUR SHOP',          // 店名下方的英文小字
    'tagline' => '选好会员，开启更多可能。', // 页脚标语
    'logo' => '',                    // 方形 logo，例如 assets/brand/logo.png；留空用店名首字
    'wordmark' => '',                // 深色模板页头用的横版字标（透明底 PNG），留空用 logo + 店名
    'buy_button' => '',              // 「深色科技」模板的图片购买按钮，留空用普通文字按钮
];
