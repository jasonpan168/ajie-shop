# AjieShop - 数字产品商城系统

一个自建的数字产品在线商城：上架商品、下单、支付、自动发卡、订单查询，支持**微信官方支付**和**易支付**。PHP + MySQL，没有框架，没有 Composer 依赖，丢到任何一台装了 PHP 的机器上就能跑。

- 许可证：**AGPL-3.0**（商业用途允许，见 [许可证](#-许可证)）
- 安全问题：请按 [SECURITY.md](SECURITY.md) **私密报告**，不要公开提 Issue

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
| 库存 | 支付成功后在事务里扣减，扣不动则回滚 |
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

**回调地址必须是你自己域名下的地址。** 微信支付成功后，会把「订单号、金额、以及你在下单时塞进 `attach` 的买家昵称和邮箱」POST 到这个地址。填错了会发生两件事：

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

**② 打开原始报文日志，走一笔真实小额订单**（例如 0.01 元）：

```bash
# 只在排错时临时打开；它会把回调原文和买家邮箱写进日志
PAY_LOG_RAW=1 systemctl restart php8.1-fpm    # 或在 .env 里加 PAY_LOG_RAW=1 后重启
```

**③ 支付完成后看日志和订单状态**：

```bash
tail -n 50 logs/notify.log
# 期望看到：Order <订单号> updated to paid.

mysql -e "SELECT order_no,status,amount FROM orders ORDER BY id DESC LIMIT 1" 你的库名
# 期望：status = paid
```

**④ 排错完立刻把 `PAY_LOG_RAW` 关掉并清理日志**，它记录的是买家个人信息。

常见现象对照：

| 现象 | 原因 |
|---|---|
| 日志里一条回调都没有 | 回调地址填错 / 不是 https / 公网访问不到 / 被 nginx deny 了 |
| 日志有 `Signature verification failed` | 后台填的 API 密钥与商户平台的 APIv2 密钥不一致 |
| 日志有 `Amount mismatch` | 订单金额与实付金额不符（正常情况下不该出现，请排查） |
| 日志有 `Order xxx not found` | 回调打到了另一套部署上，或订单已被清理脚本删除 |

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
| `logs/*.log` | 运行日志；开启 `PAY_LOG_RAW` 后含回调原文与买家邮箱 | ✅ 是 |

**部署者就是数据控制者。** 你向谁收集、存多久、怎么删除、是否需要隐私政策，由你按你所在地的法律（中国《个人信息保护法》、GDPR 等）自行负责。本项目不提供任何合规承诺。

### 支付回调怎么验的

- **验签**：`notify.php` 按微信 APIv2 规则重算 MD5 签名，用 `hash_equals()` 做定长时间比较（防止按响应时间逐字节爆破）。签名不过直接返回 `FAIL`，不动数据库。
- **金额校验**：把回调的 `total_fee`（分）与数据库里的订单金额比对，不一致就拒绝并记日志，防止支付金额被篡改。
- **防重放 / 幂等**：订单若已是 `paid` / `shipped` / `completed`，重复回调直接忽略；状态更新与库存扣减在**同一个事务**里，库存不足则整体回滚，不会出现「扣了钱没扣库存」。
- **自动发卡幂等**：靠 `orders.card_sent` 标记，同一订单不会重复发卡；依次发卡模式用 `SELECT ... FOR UPDATE` 锁定卡密行，避免并发发出同一张卡。
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
| 依赖 | ⚠️ 旧 | 前端 CDN 用的 Bootstrap 4.5.0 / jQuery 3.5.1 已停止维护 |

### 生产加固清单

上线前逐条打勾：

- [ ] **HTTPS**：全站强制 HTTPS，微信支付回调只走 HTTPS
- [ ] **删除 `install/` 目录** —— 最重要的一条。它的第 2 步会 `TRUNCATE TABLE admin` 并重建管理员
- [ ] **禁止公网访问敏感文件**：`*.log`、`*.sql`、`.env`、`config.php`、`logs/` 目录
  - Apache：仓库自带的 `.htaccess` 已经处理（需 `AllowOverride All`）
  - Nginx：照抄 [docs/INSTALLATION.md](docs/INSTALLATION.md) 里的 `deny` 段，**并且必须写在 `location ~ \.php$` 之前**，否则永不生效
- [ ] **权限**：`.env` 为 `600`，`logs/` 和 `admin/uploads/` 为 `750` 且属主是 Web 用户，**不要用 777**
- [ ] **关闭错误回显**：`php.ini` 里 `display_errors = Off`、`log_errors = On`
- [ ] **`PAY_LOG_RAW` 保持关闭**（默认就是关的），只在排错时临时开，用完清日志
- [ ] **管理员强口令**，并考虑给 `/admin/` 再加一层 HTTP Basic Auth 或 IP 白名单
- [ ] **数据库账号最小权限**：安装完成后可以把 `CREATE DATABASE` 权限收回
- [ ] **备份**：定期备份数据库；备份文件不要放在网站目录里
- [ ] **日志轮转与清理**：`logs/` 会一直增长，且含个人信息，按你的留存策略定期清理

自查命令：

```bash
for p in /.env /config.php /logs/ /database.sql /notify.log; do
  echo "$p -> $(curl -s -o /dev/null -w '%{http_code}' https://你的域名$p)"
done
# 期望：全部 403 或 404。出现 200 就是漏了。
```

### 已知限制

- **后台 CSRF 覆盖不全**（见上表），在管理员已登录的浏览器里被诱导访问恶意页面，可能触发未受保护的后台操作。
- **后台没有多用户和权限分级**，只有一个管理员角色。
- **`admin/uploads/` 没有上传类型白名单审计**，请不要把该目录配成可执行 PHP。
- **微信 API 密钥的 AES-128-ECB 加密不是强保护**（同上文说明）。
- **没有自动化测试**，也没有 CI。
- **前端依赖是 CDN 外链且无 SRI**（Bootstrap 4.5.0 / jQuery 3.5.1，均已停止维护）。对完整性要求高的部署，建议把这些静态资源下载到本地自托管。
- `rainbow_notify.php` 与 `notify_url.php` 功能重叠，前者是易支付回调的简化实现，仅在你手动把它配成回调地址时才会被用到。

发现问题请按 [SECURITY.md](SECURITY.md) **私密报告**。

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
