# 🛍️ Ajie Shop - 轻量级商城系统

<p align="center">
  <img src="https://img.shields.io/badge/php-%3E%3D8.0-blue.svg" alt="PHP 8.0+">
  <img src="https://img.shields.io/badge/mysql-%3E%3D5.6-brightgreen.svg" alt="MySQL 5.6+">
  <img src="https://img.shields.io/badge/license-AGPL%203.0-brightgreen.svg" alt="AGPL 3.0">
  <img src="https://img.shields.io/badge/version-1.0-orange.svg" alt="Version 1.0">
</p>

> 一款基于原生 PHP + MySQL 的轻量级商城系统，无需框架依赖，功能清晰易懂。支持商品售卖、订单管理、支付集成（微信原生支付 + 彩虹易支付）以及消息推送（Telegram、企业微信）。

## ✨ 项目特色

- 🎯 **开箱即用** - 原生 PHP，无框架重依赖，部署快速
- 💳 **多支付方案** - 支持微信原生支付（Native）+ 彩虹易支付
- 📱 **消息推送** - Telegram、企业微信、WxPusher 订单通知
- 🔐 **安全支付** - TLS 证书验证，防止中间人攻击
- 📊 **完整后台** - 商品、订单、优惠码、用户管理
- 💰 **灵活定价** - 支持多商品、优惠码、库存管理
- 🛡️ **IP 限制** - 防止频繁下单，保护服务质量

## 📋 系统要求

| 项目 | 最低版本 | 推荐版本 |
|------|--------|--------|
| **PHP** | 8.0 | 8.1+ |
| **MySQL** | 5.6 | 8.0+ |
| **Nginx** | 1.15+ | 1.20+ |
| **内存** | 256MB | 512MB+ |
| **磁盘** | 100MB | 500MB+ |

## 🚀 快速安装

### 1️⃣ 前置准备

确保你的服务器已安装：
- PHP 8.0+ (需要 PDO、cURL、JSON 扩展)
- MySQL 5.6+ 或 MariaDB
- Nginx 或 Apache (推荐 Nginx)

```bash
# 验证 PHP 版本
php -v

# 验证 MySQL 连接
mysql -u root -p
```

### 2️⃣ 克隆项目

```bash
# 进入网站根目录
cd /home/wwwroot

# 克隆仓库
git clone https://github.com/jasonpan168/ajie-shop.git
cd ajie-shop

# 设置目录权限
chmod -R 755 ./
chmod -R 777 ./logs
chmod -R 777 ./admin/uploads
```

### 3️⃣ 数据库配置

#### 方式一：环境变量（推荐用于生产环境）

```bash
# 在 .env 或 Nginx/Apache 配置中设置
export DB_HOST=localhost
export DB_NAME=ajie_shop
export DB_USER=ajie_user
export DB_PASS=your_secure_password
```

#### 方式二：直接修改 config.php（开发环境）

编辑 `config.php`，修改以下部分：

```php
// 从环境变量获取数据库配置
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'ajie_shop';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
```

### 4️⃣ 数据库初始化

```bash
# 登录 MySQL
mysql -u root -p

# 创建数据库
CREATE DATABASE ajie_shop DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE USER 'ajie_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON ajie_shop.* TO 'ajie_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# 导入初始数据
mysql -u ajie_user -p ajie_shop < database.sql
```

### 5️⃣ 访问安装向导

打开浏览器访问：
```
http://your-domain.com/install/
```

按照安装向导完成以下配置：
1. ✅ 检查系统环境
2. ✅ 配置数据库连接
3. ✅ 创建管理员账号
4. ✅ 完成安装

> 安装完成后会自动生成 `install.lock` 文件锁定安装入口。

### 6️⃣ Nginx 配置示例

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /home/wwwroot/ajie-shop;
    index index.php;

    # 安全头
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # 路由重写
    location / {
        try_files $uri $uri/ =404;
    }

    # PHP-FPM 配置
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 日志
    access_log /var/log/nginx/ajie-shop-access.log;
    error_log /var/log/nginx/ajie-shop-error.log;
}
```

## ⚙️ 配置说明

### 微信支付配置

编辑 `config.php`：

```php
// 微信支付商户参数
$merchant_appid = '你的AppID';
$merchant_mchid = '你的商户ID';
$merchant_api_key = '你的API密钥';
```

获取方式：
1. 登录 [微信商户平台](https://pay.weixin.qq.com/)
2. 账户中心 → API安全 → 查看密钥
3. 将对应参数复制到 `config.php`

### 彩虹易支付配置

编辑 `config.php`：

```php
// 彩虹易支付参数
$epay_config = array(
    'pid' => '你的商户ID',
    'key' => '你的商户密钥',
    'apiurl' => 'https://vip.123863.com/api/'  // 接口地址
);
```

获取方式：
1. 登录 [彩虹易支付后台](https://vip.123863.com)
2. 系统设置 → 商户信息 → 获取PID和密钥

### Telegram 推送配置

编辑 `config.php`：

```php
// Telegram 机器人配置
$telegram_config = array(
    'bot_token' => '你的BotToken',
    'chat_id' => '你的ChatID'
);
```

获取方式：
1. 向 [@BotFather](https://t.me/botfather) 发送 `/start`
2. 创建新机器人并获取 Token
3. 向机器人发送消息，再向 [@userinfobot](https://t.me/userinfobot) 查询 Chat ID

### 企业微信消息推送

在后台 → 系统配置 → 消息推送 中配置：
- 企业 ID
- 应用 Agent ID
- 应用密钥

## 📊 功能清单

### 前台功能
- ✅ 商品浏览与搜索
- ✅ 购物车管理
- ✅ 订单创建
- ✅ 优惠码应用
- ✅ 支付调用
- ✅ 订单查询

### 后台功能
- ✅ 商品管理（增删改查、库存管理）
- ✅ 订单管理（查看、标记、发货）
- ✅ 用户管理
- ✅ 优惠码管理（创建、编辑、统计）
- ✅ 支付方式配置
- ✅ 消息推送配置
- ✅ 统计报表

### 支付集成
- ✅ 微信支付（Native 二维码）
- ✅ 彩虹易支付（多种方式）
- ✅ 订单状态自动更新
- ✅ 支付回调处理

### 消息推送
- ✅ Telegram 订单通知
- ✅ 企业微信消息
- ✅ WxPusher 微信推送

## 🔐 安全特性

- 🛡️ **TLS 证书验证** - 支付通信使用 SSL/TLS，防止中间人攻击
- 🚫 **IP 频率限制** - 防止恶意频繁下单
  - 同一 IP 60 秒内只能下单一次
  - 10 分钟内最多下单 3 次
- 🔒 **签名验证** - 所有支付回调都验证商户签名
- 💳 **敏感信息隐藏** - 日志不保存完整的支付密钥
- 📋 **SQL 注入防护** - 使用 PDO 参数绑定

## 📁 项目结构

```
ajie-shop/
├── admin/                  # 后台管理系统
│   ├── index.php          # 后台首页
│   ├── orders.php         # 订单管理
│   ├── products.php       # 商品管理
│   └── ...
├── lib/                   # 核心库文件
│   ├── TelegramNotifier.php
│   ├── WxPusherNotifier.php
│   ├── EpayCore.class.php
│   └── ...
├── install/               # 安装向导
├── logs/                  # 应用日志
├── config.php             # 全局配置文件
├── database.sql           # 数据库初始化脚本
├── order.php              # 订单处理
├── notify.php             # 支付回调
├── index.php              # 前台首页
└── README.md              # 本文件
```

## 🎯 使用示例

### 创建商品

1. 登录后台：`http://your-domain.com/admin/`
2. 进入 商品管理 → 添加商品
3. 填写：
   - 商品名称
   - 商品价格
   - 库存数量
   - 商品描述
4. 保存

### 创建优惠码

1. 后台 → 优惠码管理 → 新建
2. 设置：
   - 优惠码
   - 折扣金额 / 百分比
   - 有效期
   - 使用次数上限
3. 保存并复制分享链接

### 处理订单

1. 后台 → 订单列表
2. 订单状态说明：
   - `pending` - 等待支付
   - `paid` - 已支付
   - `shipped` - 已发货
   - `completed` - 已完成

## 🐛 常见问题

### Q: 安装后打开全是 404？
**A:** 检查 Nginx 伪静态配置或 `.htaccess` 文件权限。

### Q: 支付回调没有接收到？
**A:** 
1. 检查服务器防火墙是否开放 443 端口
2. 确认微信/彩虹易支付后台配置的回调 URL 正确
3. 查看 `logs/` 目录的错误日志

### Q: 如何修改管理员密码？
**A:** 
```bash
# SSH 登录服务器，执行
mysql -u ajie_user -p ajie_shop
UPDATE admin_users SET password = MD5('new_password') WHERE username = 'admin';
```

### Q: 支付时 SSL 证书验证失败？
**A:** 这是正常的安全机制。确保：
1. 你的服务器能访问互联网
2. 域名 DNS 解析正确
3. 如果在本地测试，使用 `http://` 而非 `https://`

### Q: 如何导出订单数据？
**A:** 后台 → 订单列表 → 导出按钮（CSV 格式）

## 📞 技术支持

- 📧 Email: weijianao@gmail.com
- 📱 Telegram 群：https://t.me/+yK7diUyqmxI2MjZl
- 📺 YouTube 频道：https://www.youtube.com/@ajieshuo

## 🔄 版本更新

### v1.0 (2025-03-31)
- ✨ 项目首次发布
- 🔐 修复 TLS 证书验证漏洞 (CWE-295)
- 📝 优化开源协议为 AGPL 3.0
- 📚 完善项目文档

## 📜 开源协议

本项目采用 **GNU AGPL 3.0** 协议开源。详见 [LICENSE](./LICENSE) 文件。

### 简明版权说明

✅ **允许**：个人学习、企业自建部署、代码修改

❌ **禁止**：商业倒卖、SaaS 服务（除非完全开源）

⚠️ **关键条款**：任何网络服务必须提供完整源代码

## 📈 贡献指南

欢迎提交 Bug 报告和功能建议！

```bash
# 1. Fork 本仓库
# 2. 创建特性分支
git checkout -b feature/your-feature

# 3. 提交更改
git commit -am 'Add new feature'

# 4. 推送到分支
git push origin feature/your-feature

# 5. 创建 Pull Request
```

## 📝 更新日志

详见 [CHANGELOG.md](./CHANGELOG.md)（如果存在）

---

**Last Updated**: 2025-06-18
**License**: GNU AGPL 3.0
