<?php
// 检查是否已安装
if (!file_exists(__DIR__ . '/install.lock')) {
    header('Location: install/');
    exit;
}

require_once 'config.php';
require_once 'db.php';
require_once 'lib/SafeOutput.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudShop - 数字产品商城</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding-top: 70px;
        }

        /* ===== 导航栏 ===== */
        .navbar {
            background: linear-gradient(90deg, #2c3e50 0%, #3498db 100%) !important;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: -1px;
            background: linear-gradient(135deg, #fff 0%, #ecf0f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .navbar-nav .nav-link {
            font-weight: 500;
            margin: 0 10px;
            transition: all 0.3s ease;
            color: rgba(255,255,255,0.85) !important;
        }

        .navbar-nav .nav-link:hover {
            color: #fff !important;
            transform: translateY(-2px);
        }

        /* ===== 英雄区域 ===== */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 20px;
            text-align: center;
            margin-top: -70px;
            padding-top: 170px;
        }

        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .hero-section p {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        .hero-cta {
            display: inline-block;
            padding: 12px 40px;
            background: white;
            color: #667eea;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .hero-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            color: #764ba2;
        }

        /* ===== 主内容 ===== */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 50px;
            color: #2c3e50;
            position: relative;
        }

        .section-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            margin: 20px auto 0;
            border-radius: 2px;
        }

        /* ===== 商品网格 ===== */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: 700;
            overflow: hidden;
            position: relative;
        }

        .product-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="15" opacity="0.1" fill="white"/><circle cx="80" cy="80" r="20" opacity="0.1" fill="white"/></svg>');
            opacity: 0.3;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .product-desc {
            font-size: 0.95rem;
            color: #7f8c8d;
            margin-bottom: 15px;
            flex: 1;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #ecf0f1;
        }

        .product-price {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .product-buy {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .product-buy:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }

        /* ===== 订单查询 ===== */
        .query-section {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 60px;
        }

        .query-section h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 25px;
        }

        .query-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .query-form input {
            flex: 1;
            min-width: 250px;
            padding: 12px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .query-form input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .query-form button {
            padding: 12px 35px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .query-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* ===== 页脚 ===== */
        footer {
            background: #2c3e50;
            color: white;
            padding: 60px 20px 30px;
            margin-top: 80px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-section h4 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .footer-section p, .footer-section a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            line-height: 1.8;
            font-size: 0.95rem;
        }

        .footer-section a:hover {
            color: white;
        }

        .footer-divider {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 30px;
            text-align: center;
            color: rgba(255,255,255,0.6);
        }

        /* ===== 响应式 ===== */
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2.2rem;
            }

            .hero-section p {
                font-size: 1rem;
            }

            .section-title {
                font-size: 1.8rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 20px;
            }

            .query-form {
                flex-direction: column;
            }

            .query-form input,
            .query-form button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">
                <i class="fas fa-shopping-bag"></i> CloudShop
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#products"><i class="fas fa-box"></i> 产品</a></li>
                    <li class="nav-item"><a class="nav-link" href="#query"><i class="fas fa-search"></i> 订单查询</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/login.php"><i class="fas fa-lock"></i> 后台</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 英雄区域 -->
    <section class="hero-section">
        <h1>欢迎来到 CloudShop</h1>
        <p>发现优质数字产品，以最优价格享受</p>
        <a href="#products" class="hero-cta">立即购买</a>
    </section>

    <!-- 主内容 -->
    <div class="main-container">
        <!-- 商品列表 -->
        <section id="products">
            <h2 class="section-title">热门产品</h2>
            <div class="products-grid">
                <?php
                $stmt = $pdo->query("SELECT * FROM products WHERE status = 1 ORDER BY sort_order ASC LIMIT 12");
                $has_products = false;
                while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $has_products = true;
                    ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if (!empty($product['cover'])): ?>
                                <img src="<?php echo SafeOutput::attr($product['cover']); ?>" alt="<?php echo SafeOutput::attr($product['title']); ?>">
                            <?php else: ?>
                                <span style="font-size: 4rem;">📦</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo SafeOutput::text($product['title']); ?></h3>
                            <p class="product-desc"><?php echo SafeOutput::text($product['description']); ?></p>
                            <div class="product-footer">
                                <span class="product-price">¥<?php echo SafeOutput::text($product['price']); ?></span>
                                <a href="product.php?id=<?php echo SafeOutput::attr($product['id']); ?>" class="product-buy">
                                    <i class="fas fa-shopping-cart"></i> 购买
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </section>

        <!-- 订单查询 -->
        <section id="query" class="query-section">
            <h3><i class="fas fa-search"></i> 查询订单状态</h3>
            <form method="get" action="order_query.php" class="query-form">
                <input
                    type="text"
                    name="order_no"
                    placeholder="输入订单号查询..."
                    required
                >
                <button type="submit"><i class="fas fa-search"></i> 查询</button>
            </form>
        </section>
    </div>

    <!-- 页脚 -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4><i class="fas fa-shopping-bag"></i> CloudShop</h4>
                <p>您信赖的在线数字产品商城，为您提供优质的产品和服务。</p>
            </div>
            <div class="footer-section">
                <h4>快速链接</h4>
                <p><a href="#products">产品中心</a></p>
                <p><a href="#query">订单查询</a></p>
                <p><a href="admin/login.php">后台管理</a></p>
            </div>
            <div class="footer-section">
                <h4>信息</h4>
                <p><a href="#">关于我们</a></p>
                <p><a href="#">服务条款</a></p>
                <p><a href="#">隐私政策</a></p>
            </div>
            <div class="footer-section">
                <h4>帮助</h4>
                <p><a href="#">联系我们</a></p>
                <p><a href="#">常见问题</a></p>
                <p><a href="#">帮助中心</a></p>
            </div>
        </div>
        <div class="footer-divider">
            <p>&copy; 2025 CloudShop. 保留所有权利。| 由 CloudShop 平台驱动</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
