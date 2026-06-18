# AjieShop - 数字产品商城系统

一个现代化的数字产品在线商城系统，支持微信支付、易支付等多种支付方式。

## 🌟 主要特性

- ✅ 现代化的响应式设计（紫蓝渐变主题）
- ✅ 支持微信官方支付和易支付
- ✅ 完整的订单管理系统
- ✅ 优惠码/优惠券功能
- ✅ IP 限制和访问控制
- ✅ 专业的安全性防护
- ✅ 完整的中文界面
- ✅ 产品库存管理
- ✅ 订单查询功能
- ✅ 支付回调处理

## 📋 快速开始

### 系统要求

- PHP 8.0+
- MySQL 8.0+
- Nginx 或 Apache

### 本地部署

#### 方式 1: 使用启动脚本（推荐）

```bash
# 启动服务
bash start.sh

# 停止服务
bash stop.sh
```

#### 方式 2: 手动启动

```bash
# 启动 MySQL
docker run -d --name ajieshop-mysql \
  -e MYSQL_ALLOW_EMPTY_PASSWORD=yes \
  -e MYSQL_DATABASE="ajie_shop" \
  -p 3306:3306 \
  mysql:8.0

# 启动 PHP 开发服务器
cd /path/to/ajie-shop
php -S localhost:8888
```

## 🔐 默认账号密码

### 前台用户
无需注册，直接购买

### 后台管理员
- **账号**: `admin`
- **密码**: `123456`
- **访问地址**: http://localhost:8888/admin/

## 🌐 访问地址

| 页面 | 地址 |
|------|------|
| 前台首页 | http://localhost:8888/ |
| 商品详情 | http://localhost:8888/product.php?id=1 |
| 订单查询 | http://localhost:8888/order_query.php |
| 后台登录 | http://localhost:8888/admin/login.php |
| 后台仪表板 | http://localhost:8888/admin/dashboard.php |

## 📦 产品示例

系统预置了 6 个示例产品：

1. **Premium AI Writing Assistant** - ¥299.99
   - 高级 AI 写作工具，支持 50+ 语言

2. **Web Development Bootcamp** - ¥149.99
   - 完整的在线编程课程，100+ 小时视频

3. **Digital Marketing Strategy Bundle** - ¥79.99
   - 200+ 营销模板和工具包

4. **Graphic Design Masterclass** - ¥199.99
   - 专业设计师培训课程

5. **Business Analytics Pro Software** - ¥499.99
   - 企业级数据分析软件

6. **Complete Video Production Kit** - ¥399.99
   - 专业视频制作完整套件

## 🔧 配置支付方式

### 微信官方支付

1. 访问后台: http://localhost:8888/admin/
2. 登录账号: `admin` / `123456`
3. 点击左侧菜单 "微信支付配置"
4. 填写以下信息：
   - WeChat AppID
   - 商户号 (MchID)
   - API 密钥
5. 勾选"启用"并保存

### 易支付

1. 访问后台: http://localhost:8888/admin/
2. 点击左侧菜单 "易支付配置"
3. 填写以下信息：
   - API 地址
   - PID（商户 ID）
   - API 密钥
4. 选择启用的支付方式（支付宝/微信/USDT）
5. 保存配置

## 📖 功能说明

### 前台功能

- **浏览产品**: 查看所有产品和详情
- **在线购买**: 输入信息后选择支付方式
- **订单查询**: 使用订单号查询订单状态
- **多种支付**: 支持微信、支付宝、USDT 等

### 后台功能

- **产品管理**: 添加、编辑、删除产品
- **订单管理**: 查看所有订单及状态
- **优惠码管理**: 创建和管理优惠码
- **支付配置**: 配置微信支付和易支付
- **系统配置**: 系统参数设置

## 🔒 安全特性

- ✅ CSRF 防护
- ✅ XSS 防护
- ✅ SQL 注入防护
- ✅ 价格篡改防护
- ✅ 会话安全
- ✅ IP 限制
- ✅ 支付验证
- ✅ 审计日志

## 📝 数据库

默认数据库配置：

```
数据库名: ajie_shop
用户名: root
密码: (空)
主机: 127.0.0.1
端口: 3306
字符集: utf8mb4
```

## 🚀 部署到生产环境

### Linux 服务器部署

详见 `docs/INSTALLATION.md`

### Docker 部署

```bash
docker build -t ajieshop .
docker run -d -p 8888:8888 ajieshop
```

## 📞 支持

- 项目主页: https://github.com/jasonpan168/ajie-shop
- 问题报告: https://github.com/jasonpan168/ajie-shop/issues

## 📄 许可证

GNU Affero General Public License v3.0

详见 LICENSE 文件

## 🙏 感谢

感谢所有为这个项目做出贡献的开发者！

---

**版本**: 1.0.0  
**更新时间**: 2025-06-18  
**维护者**: Ajie
