<?php
/**
 * rainbow_notify.php —— 兼容旧回调地址
 *
 * 早期版本的易支付回调入口。为避免两套回调逻辑不一致（旧实现不校验金额、不防重复），
 * 现在直接复用 notify_url.php 的正式实现：验签、校验商户号与金额、幂等入账、自动发卡。
 * 新部署请把易支付「异步通知地址」配置为 notify_url.php。
 *
 * @license AGPL-3.0-or-later  https://github.com/jasonpan168/ajie-shop
 */
require __DIR__ . '/notify_url.php';
