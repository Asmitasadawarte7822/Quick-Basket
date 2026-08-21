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
$cat_stmt = mysqli_prepare($conn, $cat_sql);
mysqli_stmt_bind_param($cat_stmt, "s", $slug);
mysqli_stmt_execute($cat_stmt);
$cat_result = mysqli_stmt_get_result($cat_stmt);
$category = mysqli_fetch_assoc($cat_result);

if (!$category) {
    header('Location: index.php');
    exit;
}
mysqli_stmt_close($cat_stmt);

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
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;

// ---------- FETCH PRODUCTS FOR THIS CATEGORY ----------
$product_sql = "SELECT p.*, s.store_name 
                FROM products p 
                LEFT JOIN sellers s ON p.seller_id = s.id 
                WHERE p.category_id = ? AND p.status = 'active' 
                ORDER BY p.id DESC";
$product_stmt = mysqli_prepare($conn, $product_sql);
mysqli_stmt_bind_param($product_stmt, "i", $category_id);
mysqli_stmt_execute($product_stmt);
$product_result = mysqli_stmt_get_result($product_stmt);

// ---------- FETCH CATEGORIES FOR NAV (keeping connection open) ----------
$nav_sql = "SELECT slug, name FROM product_categories ORDER BY name LIMIT 6";
$nav_result = mysqli_query($conn, $nav_sql);

// Close connection only after all queries
// mysqli_close($conn); // DO NOT close here – we'll close at the end

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category_name); ?> - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ============================================
           CATEGORY PRODUCTS - BLACK & BLUE PROFESSIONAL
           ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #0a0e1a;
            color: #e8edf5;
            min-height: 100vh;
        }

        /* ---------- HEADER (Dark) ---------- */
        .dark-header {
            background: linear-gradient(135deg, #0a0a0a 0%, #0d1b2a 50%, #1a3a5c 100%);
            padding: 16px 4%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border-bottom: 2px solid #2874f0;
            box-shadow: 0 4px 30px rgba(0,0,0,0.5);
        }
        .dark-header .logo a {
            color: #fff;
            text-decoration: none;
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .dark-header .logo a span { color: #2874f0; }
        .dark-header .search-box {
            flex: 1;
            max-width: 500px;
            display: flex;
            margin: 0 20px;
            background: rgba(255,255,255,0.06);
            border-radius: 50px;
            border: 1px solid rgba(255,255,255,0.08);
            overflow: hidden;
            transition: 0.3s;
        }
        .dark-header .search-box:focus-within {
            border-color: #2874f0;
            box-shadow: 0 0 0 3px rgba(40,116,240,0.15);
        }
        .dark-header .search-box input {
            flex: 1;
            padding: 10px 18px;
            border: none;
            background: transparent;
            color: #fff;
            outline: none;
            font-size: 14px;
        }
        .dark-header .search-box input::placeholder { color: #94a3b8; }
        .dark-header .search-box button {
            padding: 10px 24px;
            border: none;
            background: #2874f0;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }
        .dark-header .search-box button:hover { background: #1a5bc7; }
        .dark-header .header-actions {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .dark-header .header-actions a {
            color: #94a3b8;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .dark-header .header-actions a:hover { color: #2874f0; }
        .dark-header .header-actions .badge {
            background: #2874f0;
            color: #fff;
            border-radius: 50%;
            padding: 2px 7px;
            font-size: 10px;
            font-weight: 700;
            margin-left: 4px;
        }
        .dark-header .header-actions .logout-link { color: #f87171; }
        .dark-header .header-actions .logout-link:hover { color: #ef4444; }

        /* ---------- NAV (Dark) ---------- */
        .dark-nav {
            background: rgba(10, 10, 10, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(40,116,240,0.1);
            padding: 0;
        }
        .dark-nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            gap: 4px;
            overflow-x: auto;
            align-items: center;
            min-height: 48px;
        }
        .dark-nav-inner::-webkit-scrollbar { display: none; }
        .dark-nav-inner a {
            text-decoration: none;
            color: #94a3b8;
            font-weight: 500;
            padding: 10px 16px;
            white-space: nowrap;
            font-size: 14px;
            border-bottom: 2px solid transparent;
            transition: 0.3s;
        }
        .dark-nav-inner a:hover { color: #2874f0; border-bottom-color: #2874f0; }
        .dark-nav-inner a.active { color: #2874f0; font-weight: 600; border-bottom-color: #2874f0; }

        /* ---------- CATEGORY HERO ---------- */
        .category-hero {
            max-width: 1200px;
            margin: 30px auto 20px;
            padding: 0 20px;
        }
        .category-hero .hero-card {
            background: linear-gradient(135deg, #0a0a0a, #0d1b2a, #1a3a5c);
            border-radius: 16px;
            padding: 35px 30px;
            border: 1px solid rgba(40,116,240,0.15);
            box-shadow: 0 8px 40px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }
        .category-hero .hero-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(40,116,240,0.03);
            border-radius: 50%;
            pointer-events: none;
        }
        .category-hero .hero-card h1 {
            font-size: 32px;
            font-weight: 700;
            color: #fff;
            position: relative;
            z-index: 2;
        }
        .category-hero .hero-card h1 i {
            color: #2874f0;
            margin-right: 10px;
        }
        .category-hero .hero-card p {
            color: #94a3b8;
            font-size: 16px;
            margin-top: 6px;
            position: relative;
            z-index: 2;
        }
        .category-hero .hero-card .back-link {
            display: inline-block;
            margin-top: 12px;
            color: #2874f0;
            text-decoration: none;
            font-weight: 500;
            position: relative;
            z-index: 2;
            transition: 0.3s;
        }
        .category-hero .hero-card .back-link:hover {
            color: #4a9eff;
            text-decoration: underline;
        }

        /* ---------- PRODUCTS GRID ---------- */
        .products-section {
            max-width: 1200px;
            margin: 0 auto 30px;
            padding: 0 20px;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 24px;
        }
        .product-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.4s ease;
            position: relative;
            backdrop-filter: blur(4px);
        }
        .product-card:hover {
            transform: translateY(-6px);
            border-color: rgba(40,116,240,0.3);
            box-shadow: 0 12px 40px rgba(0,0,0,0.4);
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
        .badge-in { background: rgba(52,211,153,0.9); color: #0a0e1a; }
        .badge-out { background: rgba(239,68,68,0.9); color: #fff; }
        .product-card .image-wrap {
            background: rgba(0,0,0,0.2);
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
        .product-card:hover .image-wrap img { transform: scale(1.04); }
        .product-card .info {
            padding: 18px 20px 20px;
        }
        .product-card .info h3 {
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 4px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-card .info .seller {
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .product-card .info .rating {
            color: #fbbf24;
            font-size: 13px;
            margin-bottom: 6px;
        }
        .product-card .info .price-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .product-card .info .price-wrap .current {
            font-size: 20px;
            font-weight: 700;
            color: #2874f0;
        }
        .product-card .info .price-wrap .old {
            font-size: 14px;
            color: #94a3b8;
            text-decoration: line-through;
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
        .product-card .info .btn-group .btn-cart:hover { background: #1a5bc7; }
        .product-card .info .btn-group .btn-cart:disabled {
            background: rgba(255,255,255,0.1);
            color: #94a3b8;
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
        .product-card .info .btn-group .btn-buy:hover { background: #e55a12; }
        .product-card .info .btn-group .btn-buy:disabled {
            background: rgba(255,255,255,0.1);
            color: #94a3b8;
            cursor: not-allowed;
        }
        .product-card .info .btn-view {
            display: block;
            text-align: center;
            margin-top: 6px;
            padding: 6px;
            border-radius: 6px;
            background: rgba(255,255,255,0.04);
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: 0.3s;
        }
        .product-card .info .btn-view:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }

        /* ---------- EMPTY STATE ---------- */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            grid-column: 1/-1;
            background: rgba(255,255,255,0.02);
            border-radius: 12px;
            border: 1px dashed rgba(255,255,255,0.06);
        }
        .empty-state i {
            font-size: 60px;
            color: rgba(255,255,255,0.06);
            display: block;
            margin-bottom: 16px;
        }
        .empty-state h3 {
            font-size: 24px;
            color: #fff;
            margin-bottom: 8px;
        }
        .empty-state p {
            color: #94a3b8;
            margin-bottom: 20px;
        }
        .empty-state .btn-shop {
            display: inline-block;
            padding: 10px 30px;
            background: #2874f0;
            color: #fff;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .empty-state .btn-shop:hover { background: #1a5bc7; }
        .empty-state .btn-shop i { margin-right: 6px; }

        /* ---------- FOOTER ---------- */
        .dark-footer {
            background: linear-gradient(180deg, #0a0a0a, #0d1b2a);
            border-top: 2px solid rgba(40,116,240,0.15);
            margin-top: 40px;
            padding: 40px 20px 20px;
        }
        .dark-footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 30px;
        }
        .dark-footer-inner .footer-box h3 {
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .dark-footer-inner .footer-box p {
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.8;
        }
        .dark-footer-inner .footer-box a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            display: block;
            padding: 4px 0;
            transition: 0.3s;
        }
        .dark-footer-inner .footer-box a:hover { color: #2874f0; }
        .dark-footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.05);
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
        }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 992px) {
            .dark-header { flex-direction: column; gap: 12px; }
            .dark-header .search-box { max-width: 100%; margin: 0; width: 100%; }
            .dark-header .header-actions { justify-content: center; }
            .category-hero .hero-card h1 { font-size: 26px; }
        }
        @media (max-width: 768px) {
            .products-grid { grid-template-columns: 1fr 1fr; gap: 16px; }
            .product-card .info .btn-group { flex-direction: column; }
            .product-card .info .btn-group .btn-cart,
            .product-card .info .btn-group .btn-buy { padding: 8px; font-size: 12px; }
            .dark-nav-inner { gap: 2px; }
            .dark-nav-inner a { font-size: 12px; padding: 8px 12px; }
            .category-hero .hero-card { padding: 24px 18px; }
            .category-hero .hero-card h1 { font-size: 22px; }
        }
        @media (max-width: 576px) {
            .products-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .product-card .image-wrap { min-height: 130px; padding: 14px; }
            .product-card .image-wrap img { max-height: 110px; }
            .product-card .info { padding: 14px; }
            .product-card .info h3 { font-size: 13px; }
            .product-card .info .price-wrap .current { font-size: 16px; }
            .category-hero .hero-card h1 { font-size: 18px; }
        }
    </style>
</head>
<body>

<!-- ======== HEADER ======== -->
<header class="dark-header">
    <div class="logo">
        <a href="index.php">Quick<span>Basket</span></a>
    </div>
    <div class="search-box">
        <input type="text" placeholder="Search for Products, Brands and More">
        <button><i class="fa-solid fa-magnifying-glass"></i> SEARCH</button>
    </div>
    <div class="header-actions">
        <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="deals.php"><i class="fa-solid fa-fire"></i> Best Deals</a>
        <a href="categories.php"><i class="fa-regular fa-folder-open"></i> Categories</a>
        <?php if ($is_logged_in && $user_name): ?>
            <a href="dashboard.php"><i class="fa-regular fa-user"></i> <?php echo $user_name; ?></a>
            <a href="logout.php" class="logout-link"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        <?php else: ?>
            <a href="login.php"><i class="fa-regular fa-user"></i> Login</a>
        <?php endif; ?>
        <a href="wishlist.php"><i class="fa-regular fa-heart"></i> Wishlist <span class="badge"><?php echo $wishlist_count; ?></span></a>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart <span class="badge"><?php echo $cart_count; ?></span></a>
    </div>
</header>

<!-- ======== NAV ======== -->
<nav class="dark-nav">
    <div class="dark-nav-inner">
        <a href="index.php">Home</a>
        <a href="deals.php">Best Deals</a>
        <a href="categories.php">Categories</a>
        <?php if ($nav_result && mysqli_num_rows($nav_result) > 0): ?>
            <?php while ($cat = mysqli_fetch_assoc($nav_result)): ?>
                <a href="category-products.php?slug=<?php echo $cat['slug']; ?>" class="<?php echo ($cat['slug'] == $slug) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</nav>

<!-- ======== CATEGORY HERO ======== -->
<div class="category-hero">
    <div class="hero-card">
        <h1><i class="fa-regular fa-tag"></i> <?php echo htmlspecialchars($category_name); ?></h1>
        <p>Discover our collection of <?php echo htmlspecialchars($category_name); ?> products at best prices.</p>
        <a href="categories.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Browse All Categories</a>
    </div>
</div>

<!-- ======== PRODUCTS ======== -->
<section class="products-section">
    <div class="products-grid">
        <?php if ($product_result && mysqli_num_rows($product_result) > 0): ?>
            <?php while ($product = mysqli_fetch_assoc($product_result)): ?>
                <div class="product-card">
                    <span class="badge <?php echo ($product['stock'] > 0) ? 'badge-in' : 'badge-out'; ?>">
                        <?php echo ($product['stock'] > 0) ? 'In Stock' : 'Out of Stock'; ?>
                    </span>
                    <div class="image-wrap">
                        <img src="<?php echo htmlspecialchars($product['image'] ?: 'https://via.placeholder.com/200/f0f0f0/888?text=No+Image'); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='https://via.placeholder.com/200/f0f0f0/888?text=No+Image'">
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
                            <i class="fa-regular fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-regular fa-box-open"></i>
                <h3>No products in this category</h3>
                <p>Check back later for new arrivals!</p>
                <a href="categories.php" class="btn-shop"><i class="fa-solid fa-arrow-left"></i> Browse Other Categories</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ======== FOOTER ======== -->
<footer class="dark-footer">
    <div class="dark-footer-inner">
        <div class="footer-box">
            <h3>Quick Basket</h3>
            <p>Your trusted online shopping destination for fashion, electronics, home essentials and much more.</p>
        </div>
        <div class="footer-box">
            <h3>Quick Links</h3>
            <a href="index.php">Home</a>
            <a href="deals.php">Best Deals</a>
            <a href="categories.php">Categories</a>
        </div>
        <div class="footer-box">
            <h3>Customer Support</h3>
            <a href="#">Contact Us</a>
            <a href="#">FAQ</a>
            <a href="#">Returns</a>
        </div>
        <div class="footer-box">
            <h3>Follow Us</h3>
            <div style="display:flex; gap:12px; margin-top:8px;">
                <a href="#" style="width:38px; height:38px; background:rgba(40,116,240,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#2874f0; transition:0.3s;">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" style="width:38px; height:38px; background:rgba(40,116,240,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#2874f0; transition:0.3s;">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="#" style="width:38px; height:38px; background:rgba(40,116,240,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#2874f0; transition:0.3s;">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" style="width:38px; height:38px; background:rgba(40,116,240,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#2874f0; transition:0.3s;">
                    <i class="fab fa-linkedin-in"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="dark-footer-bottom">
        <p>© 2026 Quick Basket. All Rights Reserved.</p>
    </div>
</footer>

<?php
// Close database connection at the very end
mysqli_close($conn);
?>

</body>
</html>