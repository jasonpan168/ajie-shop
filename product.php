<?php
require_once 'db.php';
require_once 'lib/SafeOutput.php';

if (!isset($_GET['id'])) {
    die("Product not found");
}
$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    die("Product not found");
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SafeOutput::text($product['title']); ?> - CloudShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            padding-top: 70px;
        }
        .navbar { background: linear-gradient(90deg, #2c3e50 0%, #3498db 100%) !important; box-shadow: 0 2px 20px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.8rem; font-weight: 700; color: white !important; }
        .navbar-nav .nav-link { color: rgba(255,255,255,0.85) !important; margin: 0 10px; font-weight: 500; }
        .navbar-nav .nav-link:hover { color: white !important; }
        .back-link { margin: 20px 0; }
        .back-link a { color: #667eea; text-decoration: none; font-weight: 600; }
        .back-link a:hover { color: #764ba2; }
        
        .product-container { max-width: 1200px; margin: 40px auto; background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); overflow: hidden; }
        
        .product-top { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; padding: 50px; }
        
        .product-image { width: 100%; aspect-ratio: 1; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 5rem; overflow: hidden; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; }
        
        .product-info-right { display: flex; flex-direction: column; }
        .product-title { font-size: 2.5rem; font-weight: 700; color: #2c3e50; margin-bottom: 15px; }
        .product-subtitle { font-size: 1.2rem; color: #7f8c8d; margin-bottom: 30px; }
        .price { font-size: 3rem; font-weight: 700; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 30px; }
        
        .form-control { padding: 12px 15px; border: 2px solid #ecf0f1; border-radius: 8px; }
        .form-control:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .qty-control { display: flex; gap: 10px; align-items: center; }
        .qty-btn { width: 40px; height: 40px; border: 2px solid #ecf0f1; background: white; border-radius: 8px; cursor: pointer; font-weight: 700; }
        .qty-btn:hover { background: #667eea; color: white; border-color: #667eea; }
        .buy-button { width: 100%; padding: 16px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease; }
        .buy-button:hover { transform: translateY(-2px); box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4); }
        
        .product-bottom { padding: 0 50px 50px 50px; }
        .product-detail-title { font-size: 1.5rem; font-weight: 700; color: #2c3e50; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #ecf0f1; }
        .product-detail-content { color: #555; line-height: 1.8; font-size: 1rem; }
        .product-detail-content h3 { font-size: 1.2rem; font-weight: 700; color: #2c3e50; margin: 25px 0 15px 0; }
        .product-detail-content ul, .product-detail-content p { margin-bottom: 15px; }
        .product-detail-content li { margin-bottom: 8px; }
        
        footer { background: #2c3e50; color: white; padding: 40px 20px 20px; text-align: center; margin-top: 60px; }
        
        @media (max-width: 768px) { 
            .product-top { grid-template-columns: 1fr; gap: 30px; padding: 30px; } 
            .product-title { font-size: 1.8rem; } 
            .price { font-size: 2rem; }
            .product-bottom { padding: 0 30px 30px 30px; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="/"><i class="fas fa-shopping-bag"></i> CloudShop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="/"><i class="fas fa-home"></i> Home</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="back-link"><a href="/"><i class="fas fa-arrow-left"></i> Back to Products</a></div>
        
        <div class="product-container">
            <!-- 上方：图片和购买信息 -->
            <div class="product-top">
                <!-- 左：图片 -->
                <div>
                    <div class="product-image">
                        <?php if (!empty($product['cover'])): ?>
                            <img src="<?php echo SafeOutput::attr($product['cover']); ?>" alt="<?php echo SafeOutput::attr($product['title']); ?>">
                        <?php else: ?>
                            📦
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 右：基本信息和购买 -->
                <div class="product-info-right">
                    <h1 class="product-title"><?php echo SafeOutput::text($product['title']); ?></h1>
                    <?php if (!empty($product['description'])): ?>
                        <p class="product-subtitle"><?php echo SafeOutput::text($product['description']); ?></p>
                    <?php endif; ?>
                    
                    <div style="margin-bottom: 20px;">
                        <small style="color: #7f8c8d;">Stock Available</small>
                        <div style="font-size: 1.5rem; font-weight: 700; color: #2c3e50;"><?php echo SafeOutput::text($product['stock']); ?> items</div>
                    </div>

                    <div class="price">¥<?php echo SafeOutput::text($product['price']); ?></div>

                    <form method="get" action="choose_pay.php">
                        <input type="hidden" name="id" value="<?php echo SafeOutput::attr($product['id']); ?>">
                        <input type="hidden" name="price" value="<?php echo SafeOutput::attr($product['price']); ?>">

                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" name="nickname" class="form-control" placeholder="Enter your name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-boxes"></i> Quantity</label>
                            <div class="qty-control">
                                <button type="button" class="qty-btn" onclick="document.getElementById('quantity').value = Math.max(1, parseInt(document.getElementById('quantity').value)-1)">−</button>
                                <input type="number" id="quantity" name="quantity" class="form-control" style="width: 80px; text-align: center;" value="1" min="1" max="<?php echo SafeOutput::attr($product['stock']); ?>" required>
                                <button type="button" class="qty-btn" onclick="document.getElementById('quantity').value = Math.min(<?php echo (int)$product['stock']; ?>, parseInt(document.getElementById('quantity').value)+1)">+</button>
                            </div>
                        </div>

                        <button type="submit" class="buy-button"><i class="fas fa-shopping-cart"></i> Proceed to Checkout</button>
                    </form>
                </div>
            </div>

            <!-- 下方：详细介绍 -->
            <?php if (isset($product['detail']) && !empty(trim($product['detail']))): ?>
                <div class="product-bottom">
                    <h2 class="product-detail-title"><i class="fas fa-info-circle"></i> Product Details</h2>
                    <div class="product-detail-content">
                        <?php
                        $detail = trim($product['detail']);
                        if ($detail === strip_tags($detail)) {
                            $detail = preg_replace('/(https?:\/\/[^\s]+?\.(jpg|jpeg|png|gif))/i', '<img src="$1" style="max-width:100%; margin:10px 0; border-radius:8px;" alt="Product image">', $detail);
                            echo nl2br($detail);
                        } else {
                            echo $detail;
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 CloudShop. All rights reserved. | Powered by CloudShop Platform</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
