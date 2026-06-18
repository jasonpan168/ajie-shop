# 🔧 Ajie Shop 完整安装指南

本指南提供详细的安装步骤，适用于各种部署场景。

## 目录

1. [环境要求](#环境要求)
2. [本地开发环境](#本地开发环境)
3. [Linux 服务器部署](#linux-服务器部署)
4. [宝塔面板部署](#宝塔面板部署)
5. [Docker 部署](#docker-部署)
6. [常见问题](#常见问题)

---

## 环境要求

### 必需

- **PHP**: 8.0+ (需要扩展: PDO, cURL, JSON, mb_string)
- **MySQL**: 5.6+ 或 MariaDB 10.0+
- **Web Server**: Nginx 1.15+ 或 Apache 2.4+
- **磁盘**: 至少 100MB 可用空间
- **内存**: 至少 256MB

### 推荐

- PHP 8.1+
- MySQL 8.0+
- Nginx 1.20+
- 1GB+ 内存
- SSD 磁盘

### 验证环境

```bash
# 检查 PHP 版本
php -v

# 检查 PHP 扩展
php -m | grep -E "PDO|curl|json|mbstring"

# 检查 MySQL
mysql --version
mysql -u root -p -e "SELECT VERSION();"
```

---

## 本地开发环境

### macOS (使用 Homebrew)

```bash
# 1. 安装 PHP
brew install php@8.1
brew install mysql

# 2. 启动 MySQL
brew services start mysql

# 3. 创建数据库
mysql -u root << EOF
CREATE DATABASE ajie_shop DEFAULT CHARSET=utf8mb4;
CREATE USER 'ajie_user'@'localhost' IDENTIFIED BY 'password123';
GRANT ALL ON ajie_shop.* TO 'ajie_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# 4. 导入数据库结构
mysql -u ajie_user -p ajie_shop < database.sql

# 5. 启动 PHP 内置服务器
cd /path/to/ajie-shop
php -S localhost:8000

# 6. 访问
# http://localhost:8000/install/
```

### Windows (使用 XAMPP)

1. **下载并安装** [XAMPP](https://www.apachefriends.org/)
2. **启动 Apache 和 MySQL**
   - 打开 XAMPP Control Panel
   - 点击 Apache 和 MySQL 的 Start 按钮
3. **克隆项目**
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/jasonpan168/ajie-shop.git
   ```
4. **创建数据库**
   - 打开 http://localhost/phpmyadmin
   - 导入 `database.sql` 文件
5. **编辑配置**
   - 修改 `config.php`：
   ```php
   $db_host = 'localhost';
   $db_name = 'ajie_shop';
   $db_user = 'root';
   $db_pass = '';
   ```
6. **访问应用**
   - http://localhost/ajie-shop/install/

### Linux (Ubuntu/Debian)

```bash
# 1. 更新系统
sudo apt update
sudo apt upgrade -y

# 2. 安装 PHP 8.1
sudo apt install php8.1 php8.1-fpm php8.1-mysql php8.1-curl php8.1-mbstring -y

# 3. 安装 Nginx
sudo apt install nginx -y

# 4. 安装 MySQL
sudo apt install mysql-server -y

# 5. 启动服务
sudo systemctl start php8.1-fpm
sudo systemctl start nginx
sudo systemctl start mysql
sudo systemctl enable php8.1-fpm nginx mysql

# 6. 配置 MySQL
sudo mysql_secure_installation

# 7. 创建数据库
sudo mysql -u root -p << EOF
CREATE DATABASE ajie_shop DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE USER 'ajie_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON ajie_shop.* TO 'ajie_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
EOF

# 8. 导入数据库
mysql -u ajie_user -p ajie_shop < /path/to/database.sql

# 9. 克隆项目
cd /var/www
sudo git clone https://github.com/jasonpan168/ajie-shop.git
sudo chown -R www-data:www-data ajie-shop

# 10. 配置 Nginx （见下一章节）
```

---

## Linux 服务器部署

### 完整部署流程

```bash
# 1. SSH 登录服务器
ssh root@your.server.ip

# 2. 安装依赖（以 Ubuntu 22.04 为例）
apt update && apt upgrade -y
apt install -y curl wget git vim
apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-curl php8.1-mbstring php8.1-json
apt install -y nginx
apt install -y mysql-server mysql-client

# 3. 启动服务
systemctl start php8.1-fpm nginx mysql
systemctl enable php8.1-fpm nginx mysql

# 4. 配置 MySQL
mysql -u root << 'EOF'
ALTER USER 'root'@'localhost' IDENTIFIED BY 'root_password';
CREATE DATABASE ajie_shop DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE USER 'ajie_user'@'localhost' IDENTIFIED BY 'ajie_password';
GRANT ALL PRIVILEGES ON ajie_shop.* TO 'ajie_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# 5. 项目部署
cd /var/www
git clone https://github.com/jasonpan168/ajie-shop.git
cd ajie-shop
chown -R www-data:www-data .
chmod -R 755 .
chmod -R 777 logs admin/uploads

# 6. 导入数据库
mysql -u ajie_user -p ajie_shop < database.sql

# 7. 配置环境变量
cat > .env << 'EOF'
DB_HOST=localhost
DB_NAME=ajie_shop
DB_USER=ajie_user
DB_PASS=ajie_password
EOF
chmod 600 .env

# 8. 配置 Nginx
sudo tee /etc/nginx/sites-available/ajie-shop > /dev/null <<'EOF'
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/ajie-shop;
    index index.php;

    # 安全头
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # 日志
    access_log /var/log/nginx/ajie-shop-access.log;
    error_log /var/log/nginx/ajie-shop-error.log warn;

    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 静态文件缓存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 7d;
        add_header Cache-Control "public, immutable";
    }

    # 隐藏敏感文件
    location ~ /\. {
        deny all;
    }

    location ~ /config\.php$ {
        deny all;
    }
}
EOF

# 9. 启用站点
sudo ln -s /etc/nginx/sites-available/ajie-shop /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# 10. 访问安装向导
# 打开浏览器: http://your-domain.com/install/
```

### HTTPS 配置 (使用 Let's Encrypt)

```bash
# 安装 Certbot
apt install -y certbot python3-certbot-nginx

# 获取证书
certbot certonly --nginx -d your-domain.com

# 编辑 Nginx 配置，添加以下内容：
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # ... 其他配置同上
}

# HTTP 重定向到 HTTPS
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

# 自动更新证书
certbot renew --quiet --nginx
```

---

## 宝塔面板部署

宝塔面板简化了服务器管理，推荐新手使用。

### 安装宝塔面板

```bash
# Ubuntu/Debian
wget -O install.sh http://download.bt.cn/install/install_lts.sh && sudo bash install.sh

# CentOS
wget -O install.sh http://download.bt.cn/install/install_lts.sh && bash install.sh
```

### 通过宝塔部署 Ajie Shop

1. **打开宝塔面板**
   - 访问 http://服务器IP:8888
   - 使用账户登录

2. **创建网站**
   - 网站 → 添加站点
   - 域名：your-domain.com
   - 根目录：/www/wwwroot/ajie-shop
   - PHP 版本：8.1+
   - 数据库：MySQL 8.0+

3. **创建数据库**
   - 数据库 → 添加数据库
   - 数据库名：ajie_shop
   - 用户名：ajie_user
   - 密码：生成强密码

4. **部署项目**
   - 在宝塔终端执行：
   ```bash
   cd /www/wwwroot/ajie-shop
   git clone https://github.com/jasonpan168/ajie-shop.git .
   chmod -R 755 .
   chmod -R 777 logs admin/uploads
   ```

5. **导入数据库**
   - 数据库 → 管理 → 导入
   - 选择 database.sql 文件

6. **配置环境变量**
   - 宝塔 → 软件管理 → Nginx → 配置修改
   - 在 PHP 块添加：
   ```nginx
   fastcgi_param DB_HOST localhost;
   fastcgi_param DB_NAME ajie_shop;
   fastcgi_param DB_USER ajie_user;
   fastcgi_param DB_PASS your_password;
   ```

7. **访问安装向导**
   - http://your-domain.com/install/

---

## Docker 部署

### 使用 Docker Compose

1. **创建 docker-compose.yml**

```yaml
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    container_name: ajie-shop-mysql
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: ajie_shop
      MYSQL_USER: ajie_user
      MYSQL_PASSWORD: ajie_password
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./database.sql:/docker-entrypoint-initdb.d/database.sql
    networks:
      - ajie-shop

  php:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: ajie-shop-php
    environment:
      DB_HOST: mysql
      DB_NAME: ajie_shop
      DB_USER: ajie_user
      DB_PASS: ajie_password
    ports:
      - "80:80"
    volumes:
      - ./:/var/www/html
    depends_on:
      - mysql
    networks:
      - ajie-shop

volumes:
  mysql_data:

networks:
  ajie-shop:
```

2. **创建 Dockerfile**

```dockerfile
FROM php:8.1-fpm-alpine

# 安装扩展
RUN docker-php-ext-install pdo_mysql curl mbstring

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 设置工作目录
WORKDIR /var/www/html

# 暴露端口
EXPOSE 80

CMD ["php-fpm"]
```

3. **启动容器**

```bash
docker-compose up -d
```

4. **访问应用**

```
http://localhost/install/
```

---

## 常见问题

### Q: 如何重置管理员密码？

```bash
mysql -u ajie_user -p ajie_shop -e \
  "UPDATE admin_users SET password = MD5('newpassword') WHERE id = 1;"
```

### Q: 部署后显示 404 错误？

检查以下项：

1. Nginx 配置中的 root 路径是否正确
2. PHP-FPM 是否正在运行：`systemctl status php8.1-fpm`
3. 文件权限：`chmod 755 .`

### Q: 数据库连接失败？

检查：
1. MySQL 是否运行：`systemctl status mysql`
2. 用户密码是否正确
3. 防火墙是否开放 3306 端口

### Q: 支付接口测试失败？

1. 确保服务器能访问外网
2. 检查防火墙设置（开放 443 端口）
3. 验证 SSL 证书有效性

### Q: 如何备份数据库？

```bash
# 完全备份
mysqldump -u ajie_user -p ajie_shop > backup_$(date +%Y%m%d).sql

# 恢复
mysql -u ajie_user -p ajie_shop < backup_20250618.sql
```

### Q: 日志文件在哪里？

- PHP 错误日志：`logs/` 目录
- Nginx 日志：`/var/log/nginx/`
- MySQL 日志：`/var/log/mysql/`

---

**最后修改**: 2025-06-18
