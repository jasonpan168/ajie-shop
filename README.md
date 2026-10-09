# AjieShop - 数字产品商城系统

一个自建的数字产品在线商城：上架商品、下单、支付、自动发卡、订单查询，支持**微信官方支付**和**易支付**。PHP + MySQL，没有框架，没有 Composer 依赖，丢到任何一台装了 PHP 的机器上就能跑。

- 许可证：**AGPL-3.0**（商业用途允许，见 [许可证](#-许可证)）
- 安全问题：请按 [SECURITY.md](SECURITY.md) **私密报告**，不要公开提 Issue
- 接手 / 想贡献：先读 [技术债](#-技术债) —— 这个项目欠着什么，写得很清楚

> ⚠️ **这套程序会处理真钱和买家个人信息。** 上线前请完整读一遍 [安全性](#-安全性) 一节。

---

## 🌟 功能

| | |
|---|---|
| 商品 | 上架 / 编辑 / 上下架 / 库存 / 排序 |
| 订单 | 下单、支付、订单号查询、超时自动清理 |
| 支付 | 微信官方支付（NATIVE 扫码）、易支付（支付宝 / 微信 / USDT） |
| 自动发卡 | 支付成功后按「固定内容」或「依次发放」自动发卡密到买家邮箱 |
| 优惠码 | 创建、启用 / 停用、按订单核销 |
| 通知 | 邮件（SMTP）、Telegram Bot、WxPusher |
| 风控 | 下单 IP 频率限制、后台登录失败锁定 |
| 后台 | 仪表板、商品 / 订单 / 优惠码 / 支付 / 邮件 / 菜单管理 |

---

## 📋 系统要求

- PHP **8.0+**，需要 `pdo_mysql`、`openssl`、`curl`、`mbstring`
- MySQL **5.7+** / 8.0+
- Nginx 或 Apache（本地体验也可以用 PHP 内置服务器）
- 生产环境**必须**配 HTTPS —— 微信支付回调只走 HTTPS

---

## 🚀 怎么用

### 第 1 步：拿到代码

```bash
git clone https://github.com/jasonpan168/ajie-shop.git
cd ajie-shop
```

### 第 2 步：准备数据库

只要准备一个**能创建数据库的 MySQL 账号**就行，库本身由安装向导创建。

本地想快速试一下，可以用 Docker 起一个：

```bash
docker run -d --name ajieshop-mysql \
  -e MYSQL_ALLOW_EMPTY_PASSWORD=yes \
  -p 3306:3306 \
  mysql:8.0
```

### 第 3 步：起服务

本地体验（PHP 内置服务器）：

```bash
php -S localhost:8888 -t .
```

生产部署（Nginx + PHP-FPM）：请照 [docs/INSTALLATION.md](docs/INSTALLATION.md) 配置站点，**其中的 `deny` 规则不能省**（见 [安全性](#-安全性)）。

### 第 4 步：跑安装向导

浏览器打开 <http://localhost:8888/>。因为还没装，会自动跳到 `/install/`。

1. **数据库配置**：填主机、用户名、密码、要创建的库名 → 下一步
2. **创建管理员**：设置你自己的管理员用户名和密码 → 完成安装

向导会做三件事：建库并导入 `database.sql`、写入 `.env`（权限 600）、生成 `install.lock`。

> **没有默认账号密码。** 管理员账号完全由你在第 2 步自己设置，`database.sql` 里不预置任何账号。
> 请用强口令：后台是整个商城的钥匙，登录失败 5 次会锁该 IP 15 分钟，但挡不住一个弱口令。

### 第 5 步：装完立刻做这三件事

```bash
rm -rf install/                      # ① 删掉安装向导，否则它是一个接管入口
chmod 600 .env                       # ② 数据库口令只给属主看
chmod -R 750 logs admin/uploads      # ③ 不要用 777
```

再确认一次：`https://你的域名/.env`、`/logs/`、`/config.php` 都访问不到（应该是 403 或 404）。

### 第 6 步：上架第一个商品

后台地址：`https://你的域名/admin/`（用第 4 步设的账号登录）

左侧 **商品管理** → 填写：

| 字段 | 说明 |
|---|---|
| 商品名称 | 前台展示的标题 |
| 商品描述 | 支持少量安全 HTML 标签（`<p> <br> <img> <strong> <em> <u>`） |
| 封面图 | 图片 URL（外链或你自己的 CDN） |
| 价格 | 单位元，例如 `9.90` |
| 库存 | 下单时预扣，订单超时取消或在后台删除待付订单时退回 |
| 排序 | 数字越小越靠前 |

保存后回到前台首页就能看到，点进去就是 `product.php?id=<商品ID>`。

自动发卡商品还要额外做两步：**发卡任务管理**里创建任务并导入卡密，再在里面把该商品勾选为「自动发卡」。

---

## 💳 配置支付

**不配支付，前台点「购买」会被引导到设置页，下不了单** —— 这是故意的，避免把订单发到一个没配好的通道里。

### 微信官方支付

去哪申请：<https://pay.weixin.qq.com/> 商户平台（需要企业资质，个人主体申请不了）。

需要三样东西：

| 填什么 | 去哪拿 |
|---|---|
| **AppID** | 微信公众平台 / 开放平台的应用 AppID，并在商户平台完成与商户号的绑定 |
| **商户号 MchID** | 微信支付商户平台首页，10 位数字 |
| **API 密钥（APIv2）** | 商户平台 → 账户中心 → API 安全 → 设置 APIv2 密钥（32 位） |

填写位置：后台 → **微信支付配置** → 勾选「启用」→ 保存。

### 易支付

去哪申请：你所使用的易支付（彩虹易支付等）平台，通常提供 PID 和商户密钥。

| 填什么 | 说明 |
|---|---|
| **API 地址** | 易支付站点地址，**末尾带斜杠**，例如 `https://pay.example.com/` |
| **PID** | 商户 ID |
| **API 密钥** | 商户密钥 |
| **异步通知地址** | `https://你的域名/notify_url.php` |
| **同步跳转地址** | `https://你的域名/return_url.php` |

填写位置：后台 → **易支付配置** → 勾选要启用的支付方式（支付宝 / 微信 / USDT）→ 保存。

### ⚠️ 回调地址（notify_url）怎么配 —— 最容易出事的一步

**回调地址必须是你自己域名下的地址。** 微信支付成功后，会把「订单号、金额」等交易信息 POST 到这个地址（`attach` 里只放订单号，不再带买家昵称和邮箱）。填错了会发生两件事：

1. 你的订单**永远不会变成已支付**（回调没到你这儿）；
2. 你买家的个人信息被发到了别人的服务器上。

> 历史教训：本项目早期版本在 `order.php` 里把 `notify_url` 写死成了作者自己的域名，覆盖了后台里管理员填的值。所有部署方的回调都发到了作者服务器。该问题已修复，现在回调地址**只从后台配置读取**，没填或填成占位符会直接拦住下单，不会静默回落到任何固定域名。

各通道该填什么：

| 通道 | 回调地址 |
|---|---|
| 微信官方支付 | `https://你的域名/notify.php` |
| 易支付（异步） | `https://你的域名/notify_url.php` |
| 易支付（同步跳转） | `https://你的域名/return_url.php` |

要求：

- 必须是 **`https://`**（微信支付不接受 http，也不接受自签证书）；
- 必须是**公网可达**的地址，不能是 `127.0.0.1`、`localhost` 或内网 IP；
- **不能带查询参数**（不能写成 `notify.php?x=1`）；
- 不要放在被 nginx deny 掉的路径下。

### 怎么验证回调真的通了

**① 先确认回调地址在公网上能打开**（不要只在本机测）：

```bash
curl -I https://你的域名/notify.php
# 期望：HTTP/1.1 200（返回内容为空或 "No data received" 都正常，
#       它本来就只接受 POST。若是 403/404，说明 nginx 规则或路径不对）
```

**② 走一笔真实小额订单**（例如 0.01 元），**③ 支付完成后看日志和订单状态**：

```bash
tail -n 50 logs/payment.log
# 期望看到：[wechat] 入账成功 <订单号> 金额1分   （易支付是 [epay]）

mysql -e "SELECT order_no,status,amount,card_sent FROM orders ORDER BY id DESC LIMIT 1" 你的库名
# 期望：status = paid；自动发卡商品 card_sent = 1
```

`logs/payment.log` 只记订单号、金额和处理结果，不记卡密和回调原文。需要人工处理的情况（卡密不足、发货邮件发送失败）会写进 `logs/alert.log`，并在开启了 Telegram 通知时推送给管理员。

常见现象对照：

| 现象 | 原因 |
|---|---|
| 日志里一条回调都没有 | 回调地址填错 / 不是 https / 公网访问不到 / 被 nginx deny 了 |
| 日志有 `签名校验失败` | 微信：后台填的 API 密钥与商户平台的 APIv2 密钥不一致；易支付：商户密钥与网关后台不一致 |
| 日志有 `appid/商户号不匹配` / `商户号或交易状态不符` | 回调来自别的商户号，或后台填错了 appid / 商户号 / pid |
| 日志有 `金额不符，拒绝入账` | 实付金额与订单金额不一致，订单不会发货，请排查是否有人篡改 |
| 日志有 `订单不存在` | 回调打到了另一套部署上 |

---

## 🔒 安全性

这个项目**处理支付和买家个人信息**，所以这一节只写事实，不写宣传。

### 它存了哪些数据

| 表 | 内容 | 是否个人信息 |
|---|---|---|
| `orders` | 订单号、商品、数量、金额、状态、**买家昵称**、**买家邮箱** | ✅ 是 |
| `ip_limits` | 下单者 **IP 地址**、时间戳 | ✅ 是（多数司法辖区视 IP 为个人信息） |
| `auto_cards` | 卡密内容、发放给哪个**邮箱**、关联订单号 | ✅ 是 |
| `admin_login_attempts` | 后台登录失败的来源 **IP** | ✅ 是 |
| `admin` | 管理员用户名、口令哈希 | — |
| `wechat_config` / `epay_config` / `email_settings` | 商户密钥、SMTP 口令 | 🔑 凭据 |
| `logs/*.log` | 运行日志：订单号、金额、处理结果；`alert.log` 含需人工补发订单的买家邮箱 | ✅ 是 |

**部署者就是数据控制者。** 你向谁收集、存多久、怎么删除、是否需要隐私政策，由你按你所在地的法律（中国《个人信息保护法》、GDPR 等）自行负责。本项目不提供任何合规承诺。

### 支付回调怎么验的

微信（`notify.php`）和易支付（`notify_url.php`，旧地址 `rainbow_notify.php` 也转到它）走同一套入账逻辑 `lib/order_service.php`：

- **验签**：按各自规则重算 MD5 签名，用 `hash_equals()` 做定长时间比较。签名不过直接拒绝，不动数据库。
- **验商户**：微信校验 `appid` / `mch_id`，易支付校验 `pid` 和 `trade_status=TRADE_SUCCESS`，防止拿别的商户的合法通知来套。
- **金额校验**：回调金额（统一换算成「分」比较）必须与数据库订单金额完全一致，否则拒绝入账、不发货。
- **幂等 / 防并发**：入账在事务里先 `SELECT ... FOR UPDATE` 锁订单行，已支付的订单直接忽略；并发、重复回调只会入账一次、扣一次库存、发一次卡。回调里查不到的订单不会凭空建单。
- **自动发卡**：微信和易支付订单都会自动发卡；只有卡密**发齐**才标记 `card_sent = 1`，没发齐会提醒管理员补发。

### 从旧版本升级

1. 升级前确认**没有待支付订单**：`SELECT COUNT(*) FROM orders WHERE status = 'pending';` 为 0 再升级（新版下单时预扣库存、超时取消时退回库存；旧版的待付订单当初没有预扣，升级后被取消会多退库存）。
2. 执行 `db_updates/add_unique_order_no.sql`，给订单号加唯一索引（执行前先按文件里的语句确认没有重复订单号）。
3. Nginx 部署按 [docs/INSTALLATION.md](docs/INSTALLATION.md) 补上新增的 `lib/`、`db_updates/` 等 deny 规则。
4. 易支付「异步通知地址」建议改成 `notify_url.php`（旧的 `rainbow_notify.php` 仍可用，会转到同一套实现）。

### 下单怎么防刷单

- **价格、优惠金额只认数据库**：浏览器传来的 `price`、`coupon_amount` 一律忽略，金额全部在服务端计算（单位「分」，没有浮点误差）。下架商品、负数量、超库存、后台没开启的支付方式都会被拒绝。
- **优惠码一码一用**：下单时在事务里校验并占用；订单超时取消也**不会**释放（否则迟到的付款会让同一张码被用两次），需要让顾客重新用码时，在数据库里把该码的 `status` 改回 `active` 即可。
- **库存预占**：下单即预扣库存，超时取消或后台删除待付订单时退回，避免多笔待付订单超卖。
- **超时订单不删除**：30 分钟未支付的订单置为「已取消」，迟到的付款仍会正常入账发货。
- **下单限流**：只认 `REMOTE_ADDR`（不信任可伪造的 `X-Forwarded-For`），同一 IP 的限流检查与建单串行执行。如果你的站点在 CDN / 反向代理后面，需要在 Web 服务器层把真实 IP 还原到 `REMOTE_ADDR`（如 nginx `real_ip_header`）。
- **订单查询脱敏**：`order_query.php` 只凭订单号就能查，所以昵称和邮箱返回打码后的值；订单号使用安全随机数，无法顺序遍历。
- **密钥**：微信 API 密钥在数据库中以 AES-128-ECB 加密存放，解密密钥在 `config.php` 的 `$encryption_key`。**这不是强保护**——能读到数据库的人通常也能读到 `config.php`。它的作用是防止数据库导出文件被随手翻到，不要当成密钥托管。

### 口令怎么存的

- 管理员口令用 `password_hash()`（当前默认 bcrypt，cost 12）存哈希，**不可逆**。
- 旧版本接受的 `md5()` 口令兼容分支**已删除**。登录成功时会跑 `password_needs_rehash()`，把旧算法/旧 cost 的哈希平滑升级。
- 如果你的库里还是 MD5 口令，现在登不上了，请这样重置：

  ```bash
  php -r "echo password_hash('你的新强口令', PASSWORD_DEFAULT), PHP_EOL;"
  ```
  ```sql
  UPDATE admin SET password = '<上面输出的哈希>' WHERE username = '你的用户名';
  ```

### 防护的真实状态

| 项目 | 状态 | 说明 |
|---|---|---|
| SQL 注入 | ✅ 好 | 全站用 PDO 预处理；唯一的字符串拼接在 `admin/manage_card_tasks.php`，但先过了 `array_map('intval', …)` |
| XSS（前台） | ✅ 好 | `index.php` / `product.php` / `choose_pay.php` 经 `lib/SafeOutput.php` 转义；商品描述走白名单富文本 |
| XSS（后台） | ⚠️ 部分 | 多数字段有 `htmlspecialchars`，但未逐页审计过 |
| CSRF（后台登录） | ✅ 有 | 本次已加 |
| CSRF（其他后台页） | ⚠️ **不完整** | 目前只有 `login.php`、`product_edit.php`、`order_details.php`、`coupons.php` 校验 token；**其余 14 个带 POST 的后台页面还没有**。`lib/CsrfProtection.php` 已就绪，补齐是欢迎的 PR |
| 后台登录爆破 | ✅ 有 | 同一 IP 失败 5 次锁定 15 分钟 |
| 会话安全 | ✅ 有 | 登录成功 `session_regenerate_id(true)`；30 分钟超时；会话绑定登录时的 IP |
| 下单频率限制 | ✅ 有 | `admin/ip_limits.php` 按 IP 限制下单，**只管下单，不管登录** |
| 支付验签 / 防重放 | ✅ 有 | 见上一节 |
| 敏感文件保护 | ⚠️ 看你部署 | 仓库自带 `.htaccess`；**用 Nginx 的必须自己加 deny 规则**，`.htaccess` 对 Nginx 完全无效 |
| 错误信息 | ✅ 好 | 数据库异常只进 `error_log()`，前台显示通用文案 |
| CDN 资源完整性 | ✅ 有 | 全部 65 处 CDN 引用都带 `integrity` + `crossorigin`（SRI），CDN 被篡改时浏览器会拒绝加载 |
| 依赖版本 | ⚠️ 旧 | 前端 CDN 用的 Bootstrap 4.5.0 / jQuery 3.5.1 已停止维护 |

### 生产加固清单

上线前逐条打勾：

- [ ] **HTTPS**：全站强制 HTTPS，微信支付回调只走 HTTPS
- [ ] **删除 `install/` 目录** —— 最重要的一条。它的第 2 步会 `TRUNCATE TABLE admin` 并重建管理员
- [ ] **禁止公网访问敏感文件**：`*.log`、`*.sql`、`.env`、`config.php`、`logs/` 目录
  - Apache：仓库自带的 `.htaccess` 已经处理（需 `AllowOverride All`）
  - Nginx：照抄 [docs/INSTALLATION.md](docs/INSTALLATION.md) 里的 `deny` 段，**并且必须写在 `location ~ \.php$` 之前**，否则永不生效
- [ ] **权限**：`.env` 为 `600`，`logs/` 和 `admin/uploads/` 为 `750` 且属主是 Web 用户，**不要用 777**
- [ ] **关闭错误回显**：`php.ini` 里 `display_errors = Off`、`log_errors = On`
- [ ] **管理员强口令**，并考虑给 `/admin/` 再加一层 HTTP Basic Auth 或 IP 白名单
- [ ] **数据库账号最小权限**：安装完成后可以把 `CREATE DATABASE` 权限收回
- [ ] **备份**：定期备份数据库；备份文件不要放在网站目录里
- [ ] **日志轮转与清理**：`logs/` 会一直增长，且含个人信息，按你的留存策略定期清理

自查命令：

```bash
for p in /.env /config.php /logs/ /database.sql /notify.log /lib/order_service.php /db_updates/update_db.php /clean_orders.php; do
  echo "$p -> $(curl -s -o /dev/null -w '%{http_code}' https://你的域名$p)"
done
# 期望：全部 403 或 404。出现 200 就是漏了。
```

### 已知限制

这个项目欠的账都写在下面一节 [技术债](#-技术债) 里，逐条说明了「是什么 / 影响什么场景 / 为什么现在不做 / 想做的人从哪下手」。安全相关的几条按优先级依次是：

1. [后台 CSRF 只覆盖 18 个 POST 页面里的 5 个](#1-后台-csrf-只覆盖-18-个-post-页面里的-5-个--优先级最高)
2. [后台 XSS 未逐页审计](#2-后台-xss-未逐页审计)
3. [`admin/uploads/` 没有上传类型白名单审计](#6-adminuploads-没有上传类型白名单审计)
4. [微信 API 密钥的加密不是强保护](#8-其他已知项不影响安全但接手前该知道)

发现问题请按 [SECURITY.md](SECURITY.md) **私密报告**。

---

## 🧱 技术债

这个项目欠着以下东西。**每一条都是已知的、有意留下的**，不是没注意到。接手或想贡献的人请先读完这一节，再决定从哪里动手。

格式：**是什么 → 影响谁 / 什么场景 → 为什么现在不做 → 想做的人从哪下手**。

---

### 1. 后台 CSRF 只覆盖 18 个 POST 页面里的 5 个 🔴 优先级最高

**是什么**
`lib/CsrfProtection.php` 早就写好了，但后台只有 5 个页面在用：`admin/login.php`、`admin/product_edit.php`、`admin/order_details.php`、`admin/coupons.php`、`admin/unlock_ip.php`。另外 **13 个会处理 POST 的后台页面没有任何 token 校验**：

```
admin/create_card_task.php      admin/products.php
admin/dashboard.php             admin/telegram_config.php
admin/email_settings.php        admin/test_email.php
admin/epay_config.php           admin/ip_limits.php
admin/update_product_status.php
admin/manage_card_tasks.php     admin/wechat_config.php
admin/menus.php                 admin/wxpusher_config.php
```

**影响谁 / 什么场景**
只在**管理员已经登录**的浏览器里才成立：管理员带着有效 session 的情况下，被诱导打开一个第三方恶意页面（钓鱼邮件、论坛帖、聊天链接），那个页面就能向上述任意端点自动提交表单。攻击者看不到响应，但**写操作会真的执行**。最值钱的目标是 `admin/wechat_config.php` 和 `admin/epay_config.php` —— 把回调地址改成攻击者的域名，就等于把后续所有支付回调（含买家昵称、邮箱）劫走，而且管理员很可能几天都发现不了。其次是 `admin/products.php`（改价改库存）、`admin/menus.php`（往前台插链接）、`admin/ip_limits.php`（解除风控）。

**为什么现在不做**
本轮修复的范围是登录入口本身（未认证攻击面）。补齐这 14 个页面要逐页改表单 + 改处理分支，每个页面都得单独回归验证一遍「保存还能不能正常工作」；一次性混在安全修复里提交，出了回归很难定位是哪一改动引起的。这是一轮独立的工作。

**想做的人从哪下手**
不需要新写任何基础设施，照抄 `admin/coupons.php` 的用法即可：

1. 文件顶部 `require_once '../lib/CsrfProtection.php';`（该页必须已 `session_start()`）；
2. 表单里加 `<?php echo CsrfProtection::getTokenField(); ?>`；
3. 处理 POST 的分支最前面加
   ```php
   if (!CsrfProtection::validateToken()) {
       Logger::logCsrfAttempt(basename(__FILE__));
       $error = '会话已过期或请求无效，请刷新页面后重试。';
   } else {
       // 原有处理逻辑
   }
   ```
4. **注意 `admin/test_email.php`**：它不是普通表单提交，而是被 `admin/email_settings.php:132` 用 `fetch()` 调用的，补 token 要改成把 token 一并放进 fetch 的 body（或请求头），只在页面里塞一个 hidden input 是不够的。
5. 改完必须**实际点一遍每个按钮**确认保存还正常，光看代码不算验证。

建议一个页面一个 commit，方便出问题时单独回滚。

---

### 2. 后台 XSS 未逐页审计

**是什么**
前台（`index.php`、`product.php`、`choose_pay.php`、`payment-setup-guide.php`）统一走 `lib/SafeOutput.php` 转义，商品描述走白名单富文本。**后台没有做过同样的系统性审计**：多数字段确实套了 `htmlspecialchars`，但这是逐处人工写的，没有统一出口，也没人把 18 个后台页面的每个回显点过一遍。

**影响谁 / 什么场景**
后台会回显买家可控的数据 —— 订单里的**昵称**和**邮箱**是买家在下单时自己填的。如果某个后台页面把它们未转义地打印出来，一个下单时把昵称写成 `<script>…</script>` 的人，就能在管理员打开订单列表时在管理员浏览器里执行脚本（存储型 XSS）。结合第 1 条的 CSRF 缺口，杀伤力会显著放大。

**为什么现在不做**
这是「逐页读一遍每一个回显点」的体力活，没有捷径也没有可靠的自动化手段（本项目没有模板引擎，输出散落在 PHP 内联 HTML 里）。做一半比不做更危险，因为会给人「已经审过了」的错觉。

**想做的人从哪下手**
先用 `grep -rn 'echo \$\|<?= *\$\|<?php echo \$' admin/` 把所有直接回显变量的位置列出来，逐个判断数据来源；凡是来自 `orders`、`auto_cards`、`ip_limits` 这些含用户输入的表，一律改成 `SafeOutput::text()` / `SafeOutput::attr()`。验收方式：下一笔昵称为 `<img src=x onerror=alert(1)>` 的测试订单，然后把后台每个页面都打开一遍。

---

### 3. 旧 MD5 口令的管理员会被锁在外面

**是什么**
`admin/login.php` 原先同时接受 `password_hash()` 哈希和 `md5($password)`。MD5 分支已被删除（无盐、可被彩虹表秒查）。安装向导从来都是用 `password_hash()` 建号，所以正常安装的站点不受影响；但如果你的 `admin` 表是很久以前手工建的、口令存的是 MD5，**升级到这个版本之后你会登不上后台**，界面只会显示「用户名或密码错误」。

**影响谁**
只影响从早期版本升级上来、且口令仍是 MD5 的部署。全新安装不受影响。

**为什么现在不做**（指为什么不保留兼容分支）
保留 MD5 分支就等于保留一条弱口令后门，而登录端在此之前连爆破保护都没有。「登录时顺便升级成 bcrypt」听起来两全，但那要求先验证 MD5 —— 也就是那条后门必须一直开着，直到最后一个用户登录过为止，实际上等于永远开着。

**怎么自救（一次性操作）**
生成新哈希：

```bash
php -r "echo password_hash('你的新强口令', PASSWORD_DEFAULT), PHP_EOL;"
```

写回数据库：

```sql
UPDATE admin SET password = '<上面输出的哈希>' WHERE username = '你的用户名';
```

之后登录成功时 `password_needs_rehash()` 会自动把旧算法 / 旧 cost 的哈希平滑升级，不需要再手动操作。

---

### 4. 前端依赖已停止维护，且全部走第三方 CDN

**是什么**
后台大量页面用 Bootstrap **4.5.0** + jQuery **3.5.1**（两者均已停止维护），前台和部分后台页面又用 Bootstrap **5.1.3 / 5.2.3 / 5.3.0 / 5.3.1** —— 同一个项目里并存 **5 个 Bootstrap 版本**。所有资源都是 CDN 外链（stackpath / jsdelivr / cdnjs / bootcdn），本仓库不自带任何前端静态资源。

**影响谁 / 什么场景**
① 停止维护意味着后续出的漏洞不会再有补丁；② 外链意味着**可用性和隐私都依赖第三方**——CDN 挂了后台就变成裸 HTML，而且每个访客的 IP 都会暴露给 CDN 厂商（对需要满足 GDPR 的部署是个实际问题）；③ bootcdn 在部分地区可达性不稳定。

**已经做了的部分**
全部 65 处 CDN 引用已加 `integrity`（SRI）+ `crossorigin` + `referrerpolicy`，CDN 内容被篡改时浏览器会拒绝加载；原先无版本号的 `cdn.jsdelivr.net/npm/chart.js` 也已钉到 `chart.js@4.4.3`（无版本号的 URL 根本没法用 SRI 钉住）。**SRI 保护的是完整性，不解决版本老旧和可用性。**

**为什么现在不做**
Bootstrap 4 → 5 是破坏性升级：class 名大改（`form-group` / `btn-block` / `ml-*` `mr-*` 等全部变了）、jQuery 依赖被移除、JS 组件 API 变更。本项目所有页面都是手写内联 HTML，没有组件复用，升级等于把 20 多个页面的模板逐个重排一遍再逐个人工回归。这是一次 UI 重构，不是一次依赖升级。

**想做的人从哪下手**
不要一次全升。推荐顺序：
1. **先自托管**（收益大、风险小）：把这几个文件下载到 `assets/` 目录，改成相对路径引用，顺手去掉 `integrity`/`crossorigin`。这一步立刻解决可用性和隐私问题，且不改任何 class 名。
2. **再统一版本**：先把所有页面统一到 Bootstrap 5.3.x，用 [官方 v4→v5 迁移文档](https://getbootstrap.com/docs/5.3/migration/) 逐页改 class。
3. **最后去 jQuery**：Bootstrap 5 不再依赖 jQuery，`admin/js/form-submit.js` 里的少量用法换成原生 DOM API 即可。

---

### 5. 没有 Dockerfile，也没有一键启动脚本

**是什么**
README 曾经写着 `bash start.sh` / `bash stop.sh` 和 `docker build -t ajieshop .`，但这三样东西从来没有提交进仓库（`git ls-files` 里一个都没有）。这些段落已从 README 删除，**现在没有容器化方案，也没有一键启动**。

**影响谁**
想快速试一下、或者想在 CI 里跑起来的人。目前只能手动起 MySQL + `php -S`，README 的[「怎么用」](#-怎么用)一节有完整步骤。

**为什么现在不做**
这个应用的安装向导会往 document root 写 `.env` 和 `install.lock`，容器化要一并决定：这两个文件放哪个 volume、镜像要不要预置一个已安装状态、MySQL 是同镜像还是 compose 起。随手扔一个跑不通或跑得半通的 Dockerfile，比没有更糟 —— 它会变成 issue 的主要来源。而删掉 README 里指向不存在文件的段落是立刻能做且必须做的，所以先做了那一步。

**想做的人从哪下手**
做 `docker-compose.yml`（php-fpm + nginx + mysql）比做单个 Dockerfile 更合适。关键点：把 `.env`、`install.lock`、`logs/`、`admin/uploads/` 声明成 volume；nginx 配置直接照抄 [docs/INSTALLATION.md](docs/INSTALLATION.md)（**注意里面的 `deny` 规则必须写在 `location ~ \.php$` 之前，否则不生效**）；镜像里**不要**预置 `install.lock`。验收标准：`docker compose up` 之后浏览器能走完安装向导、能上架商品、`https://…/.env` 访问不到。

---

### 6. `admin/uploads/` 没有上传类型白名单审计

**是什么**
仓库约定了 `admin/uploads/` 作为上传目录（`docs/INSTALLATION.md` 里会给它加写权限），但**没有人系统审计过上传入口的类型校验**：有没有扩展名白名单、有没有校验 MIME、有没有重命名、会不会保留 `.php` 后缀。

**影响谁 / 什么场景**
只在管理员账号被攻破、或结合第 1 条的 CSRF 之后才谈得上利用。但一旦成立，且 Web 服务器把该目录当 PHP 执行，就是从「后台被入侵」直接升级成「服务器被拿下」。

**为什么现在不做**
本轮范围是数据泄露和未认证攻击面。这条需要先把上传链路读一遍、再实际传几个恶意样本验证，属于独立的一轮。

**眼下怎么兜底（部署方现在就该做）**
在 Web 服务器层面禁止该目录执行 PHP：

```nginx
location ^~ /admin/uploads/ {
    location ~ \.php$ { deny all; }
}
```

**想做的人从哪下手**
先 `grep -rn '\$_FILES' admin/` 找出所有上传入口，逐个补：扩展名白名单（只允许图片）、`finfo` 校验真实 MIME、强制服务端重命名（不要用用户提交的文件名）、拒绝任何双扩展名。

---

### 7. git 历史里残留着已被移除的凭据

**是什么**
早期版本的 `rainbow_notify.php` 把一条易支付商户密钥**明文写在源码里**，另外 `logs/` 下曾提交过含真实邮箱的订单日志，`database.sql` 里曾有作者的 SMTP 账号口令。**当前版本这些都已经清除**（密钥改为从配置读取，日志和演示数据已删），但它们**仍然留在 git 提交历史里**，任何人 `git log -p` 都能翻出来。

**影响谁**
主要影响原作者本人的那几个账号；对下游部署者没有直接影响 —— 你部署的是当前版本，里面没有任何硬编码凭据。

**为什么现在不做**
彻底清除要用 `git filter-repo` 重写全部历史再强推，这会打断所有已存在的 fork 和 clone，是一个需要仓库所有者拍板的破坏性操作，不应该由一次常规修复顺手做掉。

**正确的处置顺序**
1. **先在服务商那边作废并轮换**这些凭据 —— 这一步最重要，而且做完之后历史里留着的就只是一串废字符串了。**只从最新版本删掉是不够的**，参见 [SECURITY.md](SECURITY.md) 的「密钥泄露」一节。
2. 再评估是否值得为此重写历史。多数情况下，轮换之后不重写是可以接受的。

---

### 8. 其他已知项（不影响安全，但接手前该知道）

| 项 | 说明 |
|---|---|
| **没有自动化测试，也没有 CI** | 仓库里没有测试套件，每次改动只能人工验证。改支付链路（`order.php` / `notify.php` / `notify_url.php`）时务必实际走一笔小额真实订单，光看代码不算验证。 |
| **后台没有多用户和权限分级** | 只有一个管理员角色，`admin` 表里所有账号权限完全相同，没有操作审计到人。 |
| **微信 API 密钥的 AES-128-ECB 加密不是强保护** | 解密密钥 `$encryption_key` 就写在 `config.php` 里 —— 能读到数据库的人通常也能读到 `config.php`。它的作用仅限于防止数据库导出文件被随手翻到，**不要当成密钥托管方案**。想做得更好：把密钥改从环境变量读取，并换用带认证的模式（如 AES-256-GCM）。 |
| **`admin/manage_card_tasks.php:32` 有字符串拼接 SQL** | ```$pdo->exec("UPDATE products SET is_autocard = 1 WHERE id IN ($ids_str)")```。**这不是注入**：`$ids_str` 来自 `implode(',', array_map('intval', $autocard_ids))`，`intval()` 保证每个元素都是整数，拼出来的只可能是 `1,2,3` 这种形式。写在这里是为了省掉后人反复怀疑、反复重新验证一遍。真要改的话，用 `IN` 的占位符展开（`str_repeat('?,', count($ids))`）会更让人放心。 |
| **前台是内联 HTML，没有模板层** | 20 多个 PHP 文件里 HTML 和逻辑混写，改 UI 要逐文件改。这也是第 4 条升级 Bootstrap 成本高的根本原因。 |
| **`admin/js/form-submit.js` 是死代码** | 仓库里带着这个文件，但没有任何 PHP 页面引用它（`grep -rln form-submit.js --include='*.php' .` 为空）。要么接上，要么删掉。 |

---

## 🌐 访问地址

| 页面 | 地址 |
|------|------|
| 前台首页 | `/` |
| 商品详情 | `/product.php?id=1` |
| 订单查询 | `/order_query.php` |
| 后台登录 | `/admin/login.php` |
| 后台仪表板 | `/admin/dashboard.php` |
| 使用文档 | `/docs/` |

## 📖 更多文档

- [docs/INSTALLATION.md](docs/INSTALLATION.md) —— 生产环境部署（Nginx / 宝塔 / HTTPS）
- [docs/CONFIGURATION.md](docs/CONFIGURATION.md) —— 各项配置详解
- [docs/user_manual.md](docs/user_manual.md) —— 面向站点管理员的使用手册

## 📞 支持

- 项目主页：<https://github.com/jasonpan168/ajie-shop>
- 问题反馈：<https://github.com/jasonpan168/ajie-shop/issues>
- 安全漏洞：[SECURITY.md](SECURITY.md)（私密渠道，请勿公开提 Issue，请勿发邮件）


---

## 📄 许可证

本项目以 **GNU AGPL-3.0** 开源，许可证全文见 [LICENSE](LICENSE)。

**任何人可以自由使用本项目，包括商业用途。** 你不需要征得作者同意，也不需要付费，就可以：

- 用它搭商城、对外经营、向你的客户收费；
- 修改、二次开发、集成进你自己的系统；
- 分发它，无论收费与否。

**唯一的条件**写在许可证第 13 条：如果你**修改**了本项目，并且让用户通过网络与之交互（SaaS、在线服务、对外开放的平台），你必须向这些用户提供你修改后的完整源代码。自建自用、内部部署、不改代码直接用，都不触发这个条件。

如果你需要把本项目改造后作为**闭源**产品或 SaaS 对外提供，或集成进无法以 AGPL 发布的专有软件，可以购买**商业许可**作为一条可选的替代许可，从而豁免第 13 条的源码公开义务：

👉 详见 [COMMERCIAL-LICENSE.md](COMMERCIAL-LICENSE.md)

商业许可不是对 AGPL 用户的额外限制，而是一条可选的替代路径。不购买商业许可的人，依然完整享有 AGPL-3.0 赋予的全部权利，包括商用。

### 第三方组件

仓库内置以下第三方组件，它们由各自作者授权，许可条款不受本项目许可证影响：

| 组件 | 许可证 |
| --- | --- |
| [PHPMailer](PHPMailer/) | LGPL-2.1 |
| [phpqrcode](phpqrcode/) | LGPL-3.0 |
| Bootstrap / jQuery（CDN 引入） | MIT |

---

**版本**: 1.1.0  
**维护者**: Ajie
