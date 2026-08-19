<?php
session_start();
require_once 'config.php';

// ---------- GET CATEGORY SLUG ----------
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// ---------- FETCH CATEGORY ----------
$cat_sql = "SELECT id, name FROM product_categories WHERE slug = ?";
$stmt = mysqli_prepare($conn, $cat_sql);
mysqli_stmt_bind_param($stmt, "s", $slug);
mysqli_stmt_execute($stmt);
$cat_result = mysqli_stmt_get_result($stmt);
$category = mysqli_fetch_assoc($cat_result);

if (!$category) {
    header('Location: index.php');
    exit;
}
mysqli_stmt_close($stmt);

$category_id = $category['id'];
$category_name = $category['name'];

// ---------- USER LOGIN ----------
$user_name = null;
$is_logged_in = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT name FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($user = mysqli_fetch_assoc($result)) {
        $user_name = htmlspecialchars($user['name']);
        $is_logged_in = true;
    }
    mysqli_stmt_close($stmt);
}

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

// ---------- FETCH PRODUCTS FOR THIS CATEGORY ----------
$product_sql = "SELECT p.*, s.store_name 
                FROM products p 
                LEFT JOIN sellers s ON p.seller_id = s.id 
                WHERE p.category_id = ? AND p.status = 'active' 
                ORDER BY p.id DESC";
$stmt = mysqli_prepare($conn, $product_sql);
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$product_result = mysqli_stmt_get_result($stmt);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category_name); ?> - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ============================================
           CATEGORY PRODUCTS - BLUE, WHITE & YELLOW THEME
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
        .logo span { color: #ffd700; }
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
        .search-box button:hover { background: #f5cf00; }
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
        .header-icons a:hover { color: #ffd700; }
        .header-icons i { margin-right: 8px; }
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
        .category-nav-inner::-webkit-scrollbar { display: none; }
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
        .category-nav-inner a:hover { color: #2874f0; border-bottom-color: #2874f0; }
        .category-nav-inner a.active { color: #2874f0; font-weight: 600; border-bottom-color: #2874f0; }

        /* ---------- Category Hero ---------- */
        .category-hero {
            background: linear-gradient(135deg, #2874f0, #1a5bc7);
            border-radius: 16px;
            padding: 35px 30px;
            margin: 20px auto 30px;
            max-width: 1200px;
            text-align: center;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .category-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            pointer-events: none;
        }
        .category-hero h1 {
            font-size: 32px;
            font-weight: 700;
        }
        .category-hero p {
            font-size: 16px;
            opacity: 0.85;
            margin-top: 6px;
        }

        /* ---------- Products Grid ---------- */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 24px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
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
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
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
        .badge-in { background: #d1fae5; color: #065f46; }
        .badge-out { background: #fee2e2; color: #991b1b; }
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

        /* ---------- Empty State ---------- */
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
        .empty-msg .btn-shop {
            display: inline-block;
            padding: 12px 36px;
            background: #2874f0;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 12px;
            transition: 0.3s;
        }
        .empty-msg .btn-shop:hover {
            background: #0052cc;
        }
        .empty-msg .back-link {
            display: inline-block;
            margin-top: 10px;
            color: #2874f0;
            text-decoration: none;
            font-weight: 500;
        }
        .empty-msg .back-link:hover {
            text-decoration: underline;
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
        .footer-box h3 { margin-bottom: 18px; }
        .footer-box p { color: #ccc; line-height: 1.7; }
        .footer-box ul { list-style: none; }
        .footer-box ul li { margin-bottom: 10px; }
        .footer-box ul li a { color: #ccc; text-decoration: none; transition: 0.3s; }
        .footer-box ul li a:hover { color: #ffd700; }
        .social-icons { display: flex; gap: 12px; }
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
        .social-icons a:hover { background: #ffd700; color: #000; }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            padding: 20px;
            color: #ccc;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 992px) {
            .top-header { flex-direction: column; }
            .search-box { width: 100%; max-width: 100%; }
            .header-icons { justify-content: center; flex-wrap: wrap; }
        }
        @media (max-width: 768px) {
            .products-grid { grid-template-columns: 1fr 1fr; gap: 16px; }
            .product-card .info .btn-group { flex-direction: column; }
            .category-hero h1 { font-size: 24px; }
        }
        @media (max-width: 576px) {
            .products-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .product-card .image-wrap { min-height: 130px; padding: 14px; }
            .product-card .image-wrap img { max-height: 110px; }
            .product-card .info { padding: 14px; }
            .product-card .info h3 { font-size: 13px; }
            .product-card .info .price-wrap .current { font-size: 16px; }
            .category-hero h1 { font-size: 20px; }
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
        <a href="deals.php">Best Deals</a>
        <a href="categories.php">Categories</a>
        <?php
        $nav_sql = "SELECT slug, name FROM product_categories ORDER BY name LIMIT 5";
        $nav_result = mysqli_query($conn, $nav_sql);
        while ($c = mysqli_fetch_assoc($nav_result)): ?>
            <a href="category-products.php?slug=<?php echo $c['slug']; ?>" class="<?php echo ($c['slug'] == $slug) ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($c['name']); ?>
            </a>
        <?php endwhile; ?>
    </div>
</nav>

<!-- ======== CATEGORY HERO ======== -->
<div class="category-hero">
    <h1><?php echo htmlspecialchars($category_name); ?></h1>
    <p>Discover our collection of <?php echo htmlspecialchars($category_name); ?> products at best prices.</p>
</div>

<!-- ======== PRODUCTS ======== -->
<div class="products-grid">
    <?php if ($product_result && mysqli_num_rows($product_result) > 0): ?>
        <?php while ($product = mysqli_fetch_assoc($product_result)): ?>
            <div class="product-card">
                <span class="badge <?php echo ($product['stock'] > 0) ? 'badge-in' : 'badge-out'; ?>">
                    <?php echo ($product['stock'] > 0) ? 'In Stock' : 'Out of Stock'; ?>
                </span>
                <div class="image-wrap">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                <div class="info">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div class="seller">by <?php echo htmlspecialchars($product['store_name'] ?? 'Quick Basket'); ?></div>
                    <div class="rating">★★★★★</div>
                    <div class="price-wrap">
                        <span class="current">₹<?php echo number_format($product['price'], 2); ?></span>
                        <?php if ($product['price'] > 1000): ?>
                            <span class="old">₹<?php echo number_format($product['price'] * 1.2, 2); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="btn-group">
                        <form action="add-to-cart.php" method="POST" style="flex:1;">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn-cart" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-cart-plus"></i> Add to Cart
                            </button>
                        </form>
                        <button class="btn-buy" onclick="alert('Proceed to checkout!')" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                            <i class="fa-solid fa-bolt"></i> Buy Now
                        </button>
                    </div>
                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn-view">
                        View Details
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-msg">
            <i class="fa-regular fa-box-open"></i>
            <h3>No products in this category</h3>
            <p>Check back later for new arrivals!</p>
            <a href="categories.php" class="back-link">← Browse Other Categories</a>
            <a href="deals.php" class="btn-shop"><i class="fa-solid fa-arrow-left"></i> Back to Deals</a>
        </div>
    <?php endif; ?>
</div>

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