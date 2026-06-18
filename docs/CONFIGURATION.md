# ⚙️ Ajie Shop 配置指南

本文档详细说明所有配置选项和参数设置。

## 目录

1. [基础配置](#基础配置)
2. [数据库配置](#数据库配置)
3. [支付配置](#支付配置)
4. [消息推送配置](#消息推送配置)
5. [安全配置](#安全配置)

---

## 基础配置

编辑 `config.php` 文件进行基础设置。

### 调试模式

```php
// 开发环境启用，生产环境关闭
define('DEBUG_MODE', false);
```

- `true`: 显示详细错误信息（仅用于开发）
- `false`: 隐藏错误信息，记录到日志（生产环境）

### 应用信息

```php
define('APP_NAME', 'Ajie Shop');          // 应用名称
define('APP_VERSION', '1.0.0');            // 应用版本
define('APP_URL', 'https://your-domain.com');  // 应用 URL
```

---

## 数据库配置

### 环境变量配置（推荐）

在服务器配置或 `.env` 文件中设置：

```bash
# .env 文件示例
DB_HOST=localhost
DB_NAME=ajie_shop
DB_USER=ajie_user
DB_PASS=secure_password_here
DB_CHARSET=utf8mb4
DB_PORT=3306
```

**优点**：
- 敏感信息不存储在代码中
- 不同环境可用不同配置
- 更安全，适合生产环境

### 直接配置（仅用于开发）

```php
// config.php 中
$db_host = 'localhost';
$db_name = 'ajie_shop';
$db_user = 'root';
$db_pass = 'password';
$db_charset = 'utf8mb4';
$db_port = 3306;
```

### 数据库连接字符串

```php
// 自动从环境变量或直接值读取
$dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=$db_charset";
$pdo = new PDO($dsn, $db_user, $db_pass);
```

### 数据库用户权限

```sql
-- 最小权限设置（推荐）
GRANT SELECT, INSERT, UPDATE, DELETE 
  ON ajie_shop.* 
  TO 'ajie_user'@'localhost';

-- 完整权限（用于初始化）
GRANT ALL PRIVILEGES 
  ON ajie_shop.* 
  TO 'ajie_user'@'localhost';

FLUSH PRIVILEGES;
```

---

## 支付配置

### 微信支付配置

在 `config.php` 中配置：

```php
// 微信支付参数
$merchant_appid = '你的 AppID';
$merchant_mchid = '你的商户号';
$merchant_api_key = '你的 API 密钥';
$merchant_cert_path = '/path/to/apiclient_cert.pem';      // 证书路径（可选）
$merchant_key_path = '/path/to/apiclient_key.pem';        // 密钥路径（可选）
```

#### 获取微信支付参数步骤

1. **登录商户平台**
   - 访问：https://pay.weixin.qq.com/

2. **获取 AppID**
   - 账户中心 → 商户信息 → 基本信息 → 绑定的公众号

3. **获取商户号 (MCH_ID)**
   - 账户中心 → 商户信息 → 基本信息 → 商户号

4. **获取 API 密钥**
   - 账户中心 → API 安全 → 设置 API 密钥
   - 密钥长度 32 位

5. **下载证书**（如需要）
   - 账户中心 → API 安全 → 下载证书

#### 配置通知 URL

在微信商户平台中配置支付回调地址：

```
https://your-domain.com/notify.php
```

确保：
- 使用 HTTPS 协议
- URL 可被微信服务器访问
- 防火墙已开放 443 端口

#### 测试微信支付

```bash
# 使用沙箱测试环境
# 将接口 URL 改为：https://api.mch.weixin.qq.com/sandboxnew/

# 获取沙箱密钥
curl https://api.mch.weixin.qq.com/sandboxnew/pay/getsignkey \
  -d "mch_id=YOUR_MCH_ID&nonce_str=abc123&sign=SIGN"
```

### 彩虹易支付配置

```php
// 彩虹易支付参数
$epay_config = [
    'pid' => '你的 PID',
    'key' => '你的商户密钥',
    'apiurl' => 'https://vip.123863.com/api/',  // API 地址
    'notify_url' => 'https://your-domain.com/rainbow_notify.php'
];
```

#### 获取彩虹易支付参数

1. **注册账户**
   - 访问：https://vip.123863.com

2. **获取 PID 和密钥**
   - 后台 → 系统设置 → 商户信息
   - 记录 PID 和商户密钥

3. **配置回调 URL**
   - 后台 → 系统设置 → API 设置
   - 异步回调地址：`https://your-domain.com/rainbow_notify.php`
   - 同步回调地址：`https://your-domain.com/rainbow_return.php`

### 支付方式切换

在订单创建时指定支付方式：

```php
// GET 参数
?type=wxpay      // 微信支付（原生 Native）
?type=epay       // 彩虹易支付
?type=alipay     // 支付宝（如配置）
```

---

## 消息推送配置

### Telegram 机器人推送

#### 1. 创建机器人

```bash
# 向 @BotFather 发送以下命令
/start
/newbot
# 输入机器人名称和用户名
# 获得 Token 示例：123456789:ABCDEFGhijKLMNOPQRSTuvWXYZ1234567890
```

#### 2. 获取 Chat ID

```bash
# 方法 1：通过 userinfobot
# 向 @userinfobot 发送消息，获得 ID

# 方法 2：通过 API
# 向机器人发送消息后，访问：
# https://api.telegram.org/bot{TOKEN}/getUpdates
# 在响应中找 "chat"."id"
```

#### 3. 配置到系统

在后台 → 系统配置 → Telegram 中填入：

```
Bot Token: 123456789:ABCDEFGhijKLMNOPQRSTuvWXYZ1234567890
Chat ID: 987654321
```

或在 `config.php` 中配置：

```php
$telegram_bot_token = '你的 Token';
$telegram_chat_id = '你的 Chat ID';
```

### 企业微信推送

#### 1. 创建应用

在企业微信后台：
- 应用管理 → 创建应用
- 填写应用基本信息
- 记录 AgentID、Secret、CorpID

#### 2. 配置参数

```php
$wecom_config = [
    'corp_id' => '你的企业 ID',
    'agent_id' => '你的应用 AgentID',
    'secret' => '你的应用 Secret',
    'touser' => '@all',  // 接收人员
    'toparty' => '1',    // 接收部门
    'totag' => ''        // 接收标签
];
```

#### 3. 测试推送

```bash
curl -X POST 'https://qyapi.weixin.qq.com/cgi-bin/message/send' \
  -H 'Content-Type: application/json' \
  -d '{
    "touser": "@all",
    "msgtype": "text",
    "agentid": AGENTID,
    "text": {"content": "Test message"}
  }'
```

### WxPusher 微信推送

#### 1. 注册 WxPusher

访问：https://wxpusher.zjiecode.com

#### 2. 获取 API Token

- 扫描二维码关注公众号
- 复制 API Token

#### 3. 获取用户 UID

```php
// 发送测试消息来获取 UID
// 或在后台配置中手动添加
```

#### 4. 配置参数

```php
$wxpusher_config = [
    'token' => '你的 API Token',
    'uid' => '你的用户 UID',
    'topic_id' => 'optional_topic_id'  // 可选：主题 ID
];
```

---

## 安全配置

### SSL/TLS 证书验证

自动启用，无需额外配置。系统在所有支付请求中默认启用：

```php
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);  // 验证对等证书
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);    // 验证主机名
```

### IP 频率限制

系统默认启用 IP 限制，防止恶意下单：

```php
// 配置规则（在 clean_orders.php 中）
// 同一 IP：
// - 60 秒内最多 1 次下单
// - 10 分钟内最多 3 次下单
```

修改限制规则：

```php
// clean_orders.php 中修改这些变量
$interval_seconds = 60;        // 间隔秒数
$max_orders_10min = 3;         // 10 分钟最大订单数
```

### CORS 配置

```php
// 允许跨域请求（如需要）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
```

### 密钥管理

#### 签名密钥

微信和彩虹易支付都使用签名验证，确保：

```php
// 签名验证示例（微信支付）
function verifySign($data, $sign, $key) {
    $params = [];
    foreach ($data as $k => $v) {
        if ($v !== '' && $k != 'sign') {
            $params[$k] = $v;
        }
    }
    ksort($params);
    $signStr = '';
    foreach ($params as $k => $v) {
        $signStr .= $k . '=' . $v . '&';
    }
    $signStr .= 'key=' . $key;
    return strtoupper(md5($signStr)) === $sign;
}
```

#### 数据库密钥

如需加密敏感数据（如支付信息）：

```php
// 定义加密密钥（至少 32 位）
define('ENCRYPTION_KEY', 'your_32_character_long_key_here_!');

// 加密函数
function encrypt($data) {
    return openssl_encrypt($data, 'AES-256-CBC', ENCRYPTION_KEY, 0, 'initialization_vector');
}

// 解密函数
function decrypt($encrypted) {
    return openssl_decrypt($encrypted, 'AES-256-CBC', ENCRYPTION_KEY, 0, 'initialization_vector');
}
```

### 防火墙配置

#### 必需开放的端口

| 端口 | 协议 | 用途 |
|------|------|------|
| 80 | HTTP | Web 访问 |
| 443 | HTTPS | 安全访问 + 支付回调 |
| 3306 | TCP | MySQL 数据库（仅内部） |
| 9000 | TCP | PHP-FPM（仅内部） |

#### UFW (Ubuntu 防火墙)

```bash
# 允许 HTTP
sudo ufw allow 80/tcp

# 允许 HTTPS
sudo ufw allow 443/tcp

# 仅允许特定 IP 访问数据库
sudo ufw allow from 192.168.1.100 to any port 3306
```

#### iptables (通用)

```bash
# 允许 HTTP
iptables -A INPUT -p tcp --dport 80 -j ACCEPT

# 允许 HTTPS
iptables -A INPUT -p tcp --dport 443 -j ACCEPT

# 拒绝其他
iptables -P INPUT DROP
```

---

## 环境示例

### 开发环境 (.env.dev)

```bash
DB_HOST=localhost
DB_NAME=ajie_shop_dev
DB_USER=ajie_dev
DB_PASS=dev_password

DEBUG_MODE=true

WECHAT_APPID=test_appid
WECHAT_MCH_ID=test_mchid
WECHAT_API_KEY=test_api_key

TELEGRAM_ENABLED=false
```

### 生产环境 (.env.prod)

```bash
DB_HOST=db.internal.local
DB_NAME=ajie_shop
DB_USER=ajie_prod_user
DB_PASS=$(openssl rand -base64 32)

DEBUG_MODE=false

WECHAT_APPID=prod_appid
WECHAT_MCH_ID=prod_mchid
WECHAT_API_KEY=prod_api_key

TELEGRAM_ENABLED=true
TELEGRAM_TOKEN=prod_bot_token
TELEGRAM_CHAT_ID=prod_chat_id
```

---

## 常见配置问题

### Q: 支付接口返回 "验证失败"？
**A:** 检查 API 密钥是否正确，是否包含特殊字符。确保没有多余的空格。

### Q: Telegram 消息收不到？
**A:** 
1. 验证 Bot Token 和 Chat ID 是否正确
2. 检查机器人是否被 @BotFather 设置为内联模式
3. 测试 API 连接：`curl https://api.telegram.org/botYOUR_TOKEN/getMe`

### Q: SSL 证书错误？
**A:** 这通常表示证书不受信任。确保：
1. 使用有效的 CA 证书
2. 域名 DNS 解析正确
3. 防火墙允许 HTTPS (443) 连接

### Q: 如何在开发环境禁用 SSL 验证？
**A:** 仅用于开发！编辑 cURL 配置：
```php
if (DEBUG_MODE) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
}
```

---

**最后修改**: 2025-06-18
