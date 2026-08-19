<?php
session_start();
require_once 'config.php';

// ---------- USER LOGIN ----------
$user_name = null;
$is_logged_in = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT name, email FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($user = mysqli_fetch_assoc($result)) {
        $user_name = htmlspecialchars($user['name']);
        $user_email = htmlspecialchars($user['email']);
        $is_logged_in = true;
    }
    mysqli_stmt_close($stmt);
}

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

// ---------- FETCH PRODUCTS ----------
$product_sql = "SELECT p.*, s.store_name 
                FROM products p 
                LEFT JOIN sellers s ON p.seller_id = s.id 
                WHERE p.status = 'active' 
                ORDER BY p.id DESC 
                LIMIT 12";
$product_result = mysqli_query($conn, $product_sql);

// ---------- FETCH CATEGORIES FOR NAV ----------
$nav_sql = "SELECT slug, name FROM product_categories ORDER BY name LIMIT 6";
$nav_result = mysqli_query($conn, $nav_sql);

// ---------- FETCH CATEGORY DEALS ----------
$cat_deals_sql = "SELECT id, name, slug FROM product_categories LIMIT 3";
$cat_deals_result = mysqli_query($conn, $cat_deals_sql);
$cat_deals = [];
while ($row = mysqli_fetch_assoc($cat_deals_result)) {
    $cat_deals[] = $row;
}

// Default categories if none exist
if (empty($cat_deals)) {
    $cat_deals = [
        ['name' => 'Mobiles', 'slug' => 'mobiles'],
        ['name' => 'Fashion', 'slug' => 'fashion'],
        ['name' => 'Laptops', 'slug' => 'laptops']
    ];
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Deals - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ============================================
           DEALS - BLUE, WHITE & YELLOW THEME
           ============================================ */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        body {
            background: #f1f3f6;
            color: #333;
            min-height: 100vh;
        }

        /* ---------- Header ---------- */
        .top-header {
            background: #2874f0;
            padding: 14px 4%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .logo h1 {
            color: #fff;
            font-size: 38px;
            font-weight: 700;
        }

        .logo span {
            color: #ffd700;
        }

        .search-box {
            flex: 1;
            display: flex;
            max-width: 700px;
        }

        .search-box input {
            width: 100%;
            padding: 14px;
            border: none;
            outline: none;
            border-radius: 4px 0 0 4px;
            font-size: 15px;
        }

        .search-box button {
            border: none;
            padding: 14px 30px;
            background: #ffd700;
            font-weight: 700;
            cursor: pointer;
            border-radius: 0 4px 4px 0;
        }

        .search-box button:hover {
            background: #f5cf00;
        }

        .header-icons {
            display: flex;
            gap: 25px;
        }

        .header-icons a {
            text-decoration: none;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            transition: 0.3s;
        }

        .header-icons a:hover {
            color: #ffd700;
        }

        .header-icons i {
            margin-right: 8px;
        }

        .badge {
            background: #ff3b30;
            color: #fff;
            border-radius: 50%;
            padding: 2px 7px;
            font-size: 10px;
            font-weight: 700;
            margin-left: 4px;
        }

        /* ---------- Category Nav ---------- */
        .category-nav {
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
            padding: 0;
        }

        .category-nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            gap: 4px;
            overflow-x: auto;
            align-items: center;
            min-height: 48px;
        }

        .category-nav-inner::-webkit-scrollbar {
            display: none;
        }

        .category-nav-inner a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 10px 16px;
            white-space: nowrap;
            font-size: 14px;
            border-bottom: 2px solid transparent;
            transition: 0.3s;
        }

        .category-nav-inner a:hover {
            color: #2874f0;
            border-bottom-color: #2874f0;
        }

        .category-nav-inner a.active {
            color: #2874f0;
            font-weight: 600;
            border-bottom-color: #2874f0;
        }

        /* ---------- Deals Page ---------- */
        .deals-page {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Hero Banner */
        .deals-hero {
            background: linear-gradient(135deg, #2874f0, #1a5bc7);
            border-radius: 16px;
            padding: 40px 30px;
            margin-bottom: 30px;
            text-align: center;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .deals-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            pointer-events: none;
        }

        .deals-hero .hero-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.15);
            color: #ffd700;
            padding: 4px 18px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .deals-hero h1 {
            font-size: 36px;
            font-weight: 700;
        }

        .deals-hero h1 span {
            color: #ffd700;
        }

        .deals-hero p {
            font-size: 16px;
            opacity: 0.85;
            margin-top: 6px;
        }

        /* Category Deal Cards */
        .category-deals {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .category-deal-card {
            border-radius: 14px;
            padding: 28px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: 0.4s;
            min-height: 160px;
            text-decoration: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .category-deal-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.1), transparent);
            pointer-events: none;
        }

        .category-deal-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .category-deal-card .deal-content {
            position: relative;
            z-index: 2;
        }

        .category-deal-card .deal-content h3 {
            font-size: 26px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }

        .category-deal-card .deal-content p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 16px;
            margin-bottom: 14px;
        }

        .category-deal-card .deal-content .btn-deal {
            display: inline-block;
            padding: 8px 24px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: 0.3s;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .category-deal-card .deal-content .btn-deal:hover {
            background: #fff;
            color: #2874f0;
        }

        .category-deal-card img {
            width: 100px;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 16px rgba(0, 0, 0, 0.2));
            position: relative;
            z-index: 2;
        }

        .deal-blue {
            background: linear-gradient(135deg, #0a3d6b, #2874f0);
        }

        .deal-orange {
            background: linear-gradient(135deg, #7a3a0a, #e67e22);
        }

        .deal-green {
            background: linear-gradient(135deg, #0a4a2a, #27ae60);
        }

        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px 0 20px;
        }

        .section-header h2 {
            font-size: 26px;
            font-weight: 700;
            color: #222;
        }

        .section-header h2 span {
            color: #2874f0;
        }

        .section-header a {
            color: #2874f0;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: 0.3s;
        }

        .section-header a:hover {
            text-decoration: underline;
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 24px;
        }

        .product-card {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            overflow: hidden;
            transition: 0.3s;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border-color: #2874f0;
        }

        .product-card .badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            z-index: 2;
        }

        .badge-hot {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-sale {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-deal {
            background: #fef3c7;
            color: #92400e;
        }

        .product-card .image-wrap {
            background: #f8fafc;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 180px;
            position: relative;
        }

        .product-card .image-wrap img {
            max-width: 100%;
            max-height: 160px;
            object-fit: contain;
            transition: 0.3s;
        }

        .product-card:hover .image-wrap img {
            transform: scale(1.05);
        }

        .product-card .discount-flag {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: #e74c3c;
            color: #fff;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 700;
        }

        .product-card .info {
            padding: 18px 20px 20px;
        }

        .product-card .info h3 {
            font-size: 15px;
            font-weight: 600;
            color: #222;
            margin-bottom: 4px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-card .info .seller {
            font-size: 12px;
            color: #888;
            margin-bottom: 4px;
        }

        .product-card .info .rating {
            color: #f59e0b;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .product-card .info .price-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .product-card .info .price-wrap .current {
            font-size: 20px;
            font-weight: 700;
            color: #2874f0;
        }

        .product-card .info .price-wrap .old {
            font-size: 14px;
            color: #888;
            text-decoration: line-through;
        }

        .product-card .info .price-wrap .discount-text {
            font-size: 13px;
            color: #27ae60;
            font-weight: 600;
        }

        .product-card .info .btn-group {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .product-card .info .btn-group .btn-cart {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            background: #2874f0;
            color: #fff;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .product-card .info .btn-group .btn-cart:hover {
            background: #0052cc;
        }

        .product-card .info .btn-group .btn-cart:disabled {
            background: #e5e7eb;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .product-card .info .btn-group .btn-buy {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            background: #fb641b;
            color: #fff;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .product-card .info .btn-group .btn-buy:hover {
            background: #e55a12;
        }

        .product-card .info .btn-group .btn-buy:disabled {
            background: #e5e7eb;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .product-card .info .btn-view {
            display: block;
            text-align: center;
            margin-top: 6px;
            padding: 6px;
            border-radius: 6px;
            background: #f1f5f9;
            color: #475569;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: 0.3s;
        }

        .product-card .info .btn-view:hover {
            background: #e2e8f0;
            color: #2874f0;
        }

        /* Empty State */
        .empty-msg {
            text-align: center;
            padding: 60px 20px;
            color: #888;
            grid-column: 1/-1;
        }

        .empty-msg i {
            font-size: 60px;
            display: block;
            margin-bottom: 16px;
            color: #ddd;
        }

        .empty-msg h3 {
            font-size: 22px;
            color: #222;
        }

        /* ---------- Footer ---------- */
        .footer {
            background: #172337;
            color: #fff;
            margin-top: 30px;
        }

        .footer-container {
            width: 95%;
            margin: auto;
            padding: 50px 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 30px;
        }

        .footer-box h3 {
            margin-bottom: 18px;
        }

        .footer-box p {
            color: #ccc;
            line-height: 1.7;
        }

        .footer-box ul {
            list-style: none;
        }

        .footer-box ul li {
            margin-bottom: 10px;
        }

        .footer-box ul li a {
            color: #ccc;
            text-decoration: none;
            transition: 0.3s;
        }

        .footer-box ul li a:hover {
            color: #ffd700;
        }

        .social-icons {
            display: flex;
            gap: 12px;
        }

        .social-icons a {
            width: 40px;
            height: 40px;
            background: #2874f0;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
            text-decoration: none;
            transition: 0.3s;
        }

        .social-icons a:hover {
            background: #ffd700;
            color: #000;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            padding: 20px;
            color: #ccc;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 992px) {
            .top-header {
                flex-direction: column;
            }

            .search-box {
                width: 100%;
                max-width: 100%;
            }

            .header-icons {
                justify-content: center;
                flex-wrap: wrap;
            }

            .category-deals {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .category-deals {
                grid-template-columns: 1fr;
            }

            .category-deal-card {
                padding: 20px;
                min-height: 130px;
            }

            .category-deal-card img {
                width: 70px;
            }

            .category-deal-card .deal-content h3 {
                font-size: 20px;
            }

            .products-grid {
                grid-template-columns: 1fr 1fr;
                gap: 16px;
            }

            .product-card .info .btn-group {
                flex-direction: column;
            }

            .deals-hero h1 {
                font-size: 28px;
            }
        }

        @media (max-width: 576px) {
            .products-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .product-card .image-wrap {
                min-height: 130px;
                padding: 14px;
            }

            .product-card .image-wrap img {
                max-height: 110px;
            }

            .product-card .info {
                padding: 14px;
            }

            .product-card .info h3 {
                font-size: 13px;
            }

            .product-card .info .price-wrap .current {
                font-size: 16px;
            }

            .deals-hero h1 {
                font-size: 22px;
            }

            .section-header h2 {
                font-size: 20px;
            }

            .category-deal-card .deal-content h3 {
                font-size: 18px;
            }

            .category-deal-card img {
                width: 60px;
            }
        }
    </style>
</head>

<body>

    <!-- ======== HEADER ======== -->
    <header class="top-header">
        <div class="logo">
            <h1>Quick<span>Basket</span></h1>
        </div>
        <div class="search-box">
            <input type="text" placeholder="Search for Products, Brands and More">
            <button>SEARCH</button>
        </div>
        <div class="header-icons">
            <?php if ($is_logged_in && $user_name): ?>
                <a href="dashboard.php"><i class="fa-regular fa-user"></i> <?php echo $user_name; ?></a>
                <a href="logout.php" style="color:#ffd700;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="login.php"><i class="fa-regular fa-user"></i> Login</a>
            <?php endif; ?>
            <a href="wishlist.php"><i class="fa-regular fa-heart"></i> Wishlist <span class="badge"><?php echo isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0; ?></span></a>
            <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart <span class="badge"><?php echo $cart_count; ?></span></a>
        </div>
    </header>

    <!-- ======== CATEGORY NAV ======== -->
    <nav class="category-nav">
        <div class="category-nav-inner">
            <a href="index.php">Home</a>
            <a href="deals.php" class="active">Best Deals</a>
            <a href="categories.php">Categories</a>
            <?php if ($nav_result && mysqli_num_rows($nav_result) > 0): ?>
                <?php while ($cat = mysqli_fetch_assoc($nav_result)): ?>
                    <a href="category-products.php?slug=<?php echo $cat['slug']; ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </nav>

    <!-- ======== DEALS CONTENT ======== -->
    <div class="deals-page">

        <!-- Hero Banner -->
        <div class="deals-hero">
            <div class="hero-badge">🔥 Limited Time Offer</div>
            <h1>Best <span>Deals</span> For You</h1>
            <p>Shop the hottest deals with up to 70% off on top products.</p>
        </div>

        <!-- Category Deal Cards -->
        <div class="category-deals">
            <!-- Smartphones card -->
            <a href="category-products.php?slug=mobiles" class="category-deal-card deal-blue">
                <div class="deal-content">
                    <h3>Smartphones</h3>
                    <p>Up To 40% OFF</p>
                    <span class="btn-deal">Shop Now <i class="fa-solid fa-arrow-right"></i></span>
                </div>
                <img src="images/mobile.png" alt="Mobiles">
            </a>

            <!-- Fashion card -->
            <a href="category-products.php?slug=fashion" class="category-deal-card deal-orange">
                ...
            </a>

            <!-- Laptops card -->
            <a href="category-products.php?slug=laptops" class="category-deal-card deal-green">
                ...
            </a>
        </div>

        <!-- Featured Deals -->
        <div class="section-header">
            <h2>🔥 Featured <span>Deals</span></h2>
            <a href="categories.php">View All →</a>
        </div>

        <div class="products-grid">
            <?php if ($product_result && mysqli_num_rows($product_result) > 0): ?>
                <?php while ($product = mysqli_fetch_assoc($product_result)): ?>
                    <?php
                    $original_price = $product['price'] * 1.25;
                    $discount_percent = round((($original_price - $product['price']) / $original_price) * 100);
                    if ($discount_percent < 0) {
                        $discount_percent = 0;
                    }
                    ?>
                    <div class="product-card">
                        <?php
                        $badge_class = 'badge-deal';
                        $badge_text = '💰 Deal';
                        if ($discount_percent > 40) {
                            $badge_class = 'badge-hot';
                            $badge_text = '🔥 Hot Deal';
                        } elseif ($discount_percent > 20) {
                            $badge_class = 'badge-sale';
                            $badge_text = '⚡ Sale';
                        }
                        ?>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                        <div class="image-wrap">
                            <img src="<?php echo htmlspecialchars($product['image'] ?: 'https://via.placeholder.com/200/f0f0f0/888?text=No+Image'); ?>"
                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                                onerror="this.src='https://via.placeholder.com/200/f0f0f0/888?text=No+Image'">
                            <?php if ($discount_percent > 0): ?>
                                <span class="discount-flag">-<?php echo $discount_percent; ?>%</span>
                            <?php endif; ?>
                        </div>
                        <div class="info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="seller">by <?php echo htmlspecialchars($product['store_name'] ?? 'Quick Basket'); ?></div>
                            <div class="rating">★★★★★</div>
                            <div class="price-wrap">
                                <span class="current">₹<?php echo number_format($product['price'], 2); ?></span>
                                <?php if ($discount_percent > 0): ?>
                                    <span class="old">₹<?php echo number_format($original_price, 2); ?></span>
                                    <span class="discount-text">(<?php echo $discount_percent; ?>% OFF)</span>
                                <?php endif; ?>
                            </div>
                            <div class="btn-group">
                                <form action="add-to-cart.php" method="POST" style="flex:1;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn-cart" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                        <i class="fa-solid fa-cart-plus"></i> Shop Now
                                    </button>
                                </form>
                                <button class="btn-buy" onclick="alert('Proceed to checkout!')" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                    <i class="fa-solid fa-bolt"></i> Buy Now
                                </button>
                            </div>
                            <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn-view">
                                <i class="fa-regular fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-msg">
                    <i class="fa-regular fa-box-open"></i>
                    <h3>No products available</h3>
                    <p>Check back later for exciting deals!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ======== WHY CHOOSE US ======== -->
    <section style="max-width:1200px; margin:40px auto; padding:0 20px;">
        <div style="text-align:center; margin-bottom:30px;">
            <h2 style="font-size:28px; font-weight:700; color:#222;">Why Choose <span style="color:#2874f0;">Quick Basket</span>?</h2>
            <p style="color:#888;">We provide the best online shopping experience.</p>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:24px;">
            <div style="background:#fff; padding:30px 20px; border-radius:12px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.06); border:1px solid #eee;">
                <div style="width:56px; height:56px; margin:0 auto 14px; background:#eef4ff; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-truck-fast" style="font-size:24px; color:#2874f0;"></i>
                </div>
                <h3 style="font-size:18px; margin-bottom:8px;">Fast Delivery</h3>
                <p style="color:#888; font-size:14px; line-height:1.7;">Get your orders delivered quickly and safely.</p>
            </div>
            <div style="background:#fff; padding:30px 20px; border-radius:12px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.06); border:1px solid #eee;">
                <div style="width:56px; height:56px; margin:0 auto 14px; background:#eef4ff; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-shield-halved" style="font-size:24px; color:#2874f0;"></i>
                </div>
                <h3 style="font-size:18px; margin-bottom:8px;">Secure Payment</h3>
                <p style="color:#888; font-size:14px; line-height:1.7;">100% secure and trusted payment methods.</p>
            </div>
            <div style="background:#fff; padding:30px 20px; border-radius:12px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.06); border:1px solid #eee;">
                <div style="width:56px; height:56px; margin:0 auto 14px; background:#eef4ff; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-rotate-left" style="font-size:24px; color:#2874f0;"></i>
                </div>
                <h3 style="font-size:18px; margin-bottom:8px;">Easy Returns</h3>
                <p style="color:#888; font-size:14px; line-height:1.7;">Simple return and refund process.</p>
            </div>
            <div style="background:#fff; padding:30px 20px; border-radius:12px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.06); border:1px solid #eee;">
                <div style="width:56px; height:56px; margin:0 auto 14px; background:#eef4ff; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-headset" style="font-size:24px; color:#2874f0;"></i>
                </div>
                <h3 style="font-size:18px; margin-bottom:8px;">24/7 Support</h3>
                <p style="color:#888; font-size:14px; line-height:1.7;">Our support team is always ready to help.</p>
            </div>
        </div>
    </section>

    <!-- ======== FOOTER ======== -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-box">
                <h3>Quick Basket</h3>
                <p>Your trusted online shopping destination.</p>
            </div>
            <div class="footer-box">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="deals.php">Best Deals</a></li>
                    <li><a href="categories.php">Categories</a></li>
                </ul>
            </div>
            <div class="footer-box">
                <h3>Customer Support</h3>
                <ul>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-box">
                <h3>Follow Us</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2026 Quick Basket. All Rights Reserved.</p>
        </div>
    </footer>

</body>

</html>