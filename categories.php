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

// ---------- FETCH CATEGORIES WITH PRODUCT COUNTS ----------
$cat_sql = "SELECT c.*, COUNT(p.id) AS product_count 
            FROM product_categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
            GROUP BY c.id
            ORDER BY c.name";
$cat_result = mysqli_query($conn, $cat_sql);

// ---------- FETCH CATEGORIES FOR NAV ----------
$nav_sql = "SELECT slug, name FROM product_categories ORDER BY name LIMIT 6";
$nav_result = mysqli_query($conn, $nav_sql);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Categories - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ============================================
           CATEGORIES - BLUE, WHITE & YELLOW THEME
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

        /* ---------- Categories Page ---------- */
        .categories-page {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Hero */
        .categories-hero {
            background: linear-gradient(135deg, #2874f0, #1a5bc7);
            border-radius: 16px;
            padding: 35px 30px;
            margin-bottom: 30px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .categories-hero::before {
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
        .categories-hero h1 {
            font-size: 32px;
            font-weight: 700;
            position: relative;
            z-index: 2;
        }
        .categories-hero h1 i {
            margin-right: 10px;
        }
        .categories-hero h1 span {
            color: #ffd700;
        }
        .categories-hero p {
            font-size: 16px;
            opacity: 0.85;
            margin-top: 6px;
            position: relative;
            z-index: 2;
        }

        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        .category-card {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            padding: 24px 20px;
            text-align: center;
            transition: 0.3s;
            text-decoration: none;
            color: #333;
            cursor: pointer;
        }
        .category-card:hover {
            transform: translateY(-6px);
            border-color: #2874f0;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        .category-card .icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 14px;
            background: #eef4ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #2874f0;
            transition: 0.3s;
        }
        .category-card:hover .icon {
            background: #2874f0;
            color: #fff;
        }
        .category-card h3 {
            font-size: 18px;
            font-weight: 600;
            color: #222;
            margin-bottom: 4px;
        }
        .category-card .count {
            font-size: 14px;
            color: #888;
        }
        .category-card .count span {
            color: #2874f0;
            font-weight: 600;
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
            .categories-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; }
            .category-card { padding: 16px 14px; }
            .category-card .icon { width: 48px; height: 48px; font-size: 20px; }
            .category-card h3 { font-size: 15px; }
            .categories-hero h1 { font-size: 24px; }
        }
        @media (max-width: 576px) {
            .categories-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .category-card .icon { width: 40px; height: 40px; font-size: 16px; }
            .category-card h3 { font-size: 13px; }
            .categories-hero { padding: 24px 16px; }
            .categories-hero h1 { font-size: 20px; }
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
            <!-- <a href="logout.php" style="color:#ffd700;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a> -->
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
        <a href="categories.php" class="active">Categories</a>
        <?php if ($nav_result && mysqli_num_rows($nav_result) > 0): ?>
            <?php while ($cat = mysqli_fetch_assoc($nav_result)): ?>
                <a href="category-products.php?slug=<?php echo $cat['slug']; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</nav>

<!-- ======== CATEGORIES CONTENT ======== -->
<div class="categories-page">

    <!-- Hero -->
    <div class="categories-hero">
        <h1><i class="fa-regular fa-folder-open"></i> All <span>Categories</span></h1>
        <p>Browse products by category – find what you love!</p>
    </div>

    <!-- Categories Grid -->
    <div class="categories-grid">
        <?php if ($cat_result && mysqli_num_rows($cat_result) > 0): ?>
            <?php while ($cat = mysqli_fetch_assoc($cat_result)): ?>
                <a href="category-products.php?slug=<?php echo $cat['slug']; ?>" class="category-card">
                    <div class="icon"><i class="fa-regular fa-tag"></i></div>
                    <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                    <div class="count"><span><?php echo $cat['product_count']; ?></span> products</div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-msg">
                <i class="fa-regular fa-folder-open"></i>
                <h3>No categories yet</h3>
                <p>Categories will appear here once added by the admin.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- After categories grid -->
<?php include 'why-choose.php'; ?>

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
            <p>Your trusted online shopping destination for fashion, electronics, home essentials and much more.</p>
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