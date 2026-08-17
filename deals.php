<?php
session_start();
require_once 'config.php';

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

// ---------- FETCH DEALS (Products with discounts) ----------
// Show products that have discount (price > 1000 to simulate discount)
$deals_sql = "SELECT p.*, s.store_name,
              (p.price * 1.25) AS original_price,
              ROUND(((p.price * 1.25 - p.price) / (p.price * 1.25)) * 100) AS discount_percent
              FROM products p 
              LEFT JOIN sellers s ON p.seller_id = s.id 
              WHERE p.status = 'active' AND p.price > 100
              ORDER BY p.id DESC 
              LIMIT 12";
$deals_result = mysqli_query($conn, $deals_sql);

// ---------- FETCH FEATURED CATEGORIES FOR DEALS ----------
$categories = ['Electronics', 'Fashion', 'Mobiles', 'Home & Living'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Deals - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ============================================
           DEALS PAGE - PROFESSIONAL
           ============================================ */

        .deals-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }

        /* ---------- Hero Banner ---------- */
        .deals-hero {
            background: linear-gradient(135deg, #1a56db, #3b82f6);
            border-radius: 16px;
            padding: 50px 40px;
            margin: 20px 0 30px;
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

        .deals-hero h1 {
            font-size: 38px;
            font-weight: 800;
            position: relative;
            z-index: 2;
        }

        .deals-hero h1 span {
            color: #fcd34d;
        }

        .deals-hero p {
            font-size: 18px;
            opacity: 0.85;
            margin-top: 8px;
            position: relative;
            z-index: 2;
        }

        .deals-hero .hero-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.15);
            padding: 6px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            position: relative;
            z-index: 2;
        }


        /* ============================================
   CATEGORY NAVIGATION - CLEAN HORIZONTAL BAR
   ============================================ */

        /* ---------- Container ---------- */
        .category-nav {
            background: #ffffff;
            border-bottom: 1px solid #e8e8e8;
            padding: 0;
            width: 100%;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .category-nav-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            min-height: 48px;
        }

        /* ---------- Hide Scrollbar ---------- */
        .category-nav-inner::-webkit-scrollbar {
            display: none;
        }

        .category-nav-inner {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* ---------- Navigation Links ---------- */
        .category-nav-inner a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            color: #333333;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            transition: all 0.25s ease;
            border-bottom: 2px solid transparent;
            position: relative;
            flex-shrink: 0;
        }

        /* ---------- Hover Effect ---------- */
        .category-nav-inner a:hover {
            color: #2874f0;
            border-bottom-color: #2874f0;
        }

        /* ---------- Active Link ---------- */
        .category-nav-inner a.active {
            color: #2874f0;
            border-bottom-color: #2874f0;
            font-weight: 600;
        }

        /* ---------- First Link (Home) ---------- */
        .category-nav-inner a:first-child {
            font-weight: 600;
            color: #1a1a1a;
        }

        .category-nav-inner a:first-child:hover {
            color: #2874f0;
        }

        /* ---------- Icons ---------- */
        .category-nav-inner a i {
            font-size: 14px;
            color: #888;
            transition: color 0.25s ease;
        }

        .category-nav-inner a:hover i {
            color: #2874f0;
        }

        .category-nav-inner a.active i {
            color: #2874f0;
        }

        /* ---------- Separator (optional) ---------- */
        .category-nav-inner .separator {
            color: #d0d0d0;
            font-size: 14px;
            user-select: none;
            flex-shrink: 0;
        }

        /* ---------- Mobile Responsive ---------- */
        @media (max-width: 768px) {
            .category-nav-inner {
                padding: 0 12px;
                gap: 4px;
                min-height: 44px;
            }

            .category-nav-inner a {
                padding: 8px 12px;
                font-size: 13px;
            }

            .category-nav-inner a i {
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .category-nav-inner {
                padding: 0 8px;
                gap: 2px;
                min-height: 40px;
            }

            .category-nav-inner a {
                padding: 6px 10px;
                font-size: 12px;
            }

            .category-nav-inner a i {
                font-size: 12px;
            }
        }



   .category-nav-inner a:hover {
    background: #f0f7ff;
    border-radius: 6px;
    border-bottom-color: transparent;
}

        /* ---------- Category Deal Cards ---------- */
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
            cursor: pointer;
            position: relative;
            overflow: hidden;
            min-height: 160px;
        }

        .category-deal-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
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
            color: #1a56db;
        }

        .category-deal-card img {
            width: 100px;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 16px rgba(0, 0, 0, 0.2));
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

        /* ---------- Section Header ---------- */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px 0 20px;
        }

        .section-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
        }

        .section-header h2 span {
            color: #2874f0;
        }

        .section-header a {
            color: #2874f0;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: 0.3s;
        }

        .section-header a:hover {
            text-decoration: underline;
        }

        /* ---------- Products Grid ---------- */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 24px;
        }

        .product-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            transition: 0.3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.04);
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
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

        /* Discount Badge on Image */
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
            color: #1e293b;
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
            color: #94a3b8;
            text-decoration: line-through;
        }

        .product-card .info .price-wrap .discount {
            font-size: 13px;
            color: #27ae60;
            font-weight: 600;
        }

        /* Buttons inside card */
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
            color: #94a3b8;
            grid-column: 1/-1;
        }

        .empty-msg i {
            font-size: 60px;
            display: block;
            margin-bottom: 16px;
            color: #d1d5db;
        }

        .empty-msg h3 {
            font-size: 22px;
            color: #1e293b;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 992px) {
            .deals-hero {
                padding: 35px 25px;
            }

            .deals-hero h1 {
                font-size: 30px;
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

            .product-card .info .btn-group .btn-cart,
            .product-card .info .btn-group .btn-buy {
                padding: 8px;
                font-size: 12px;
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
                font-size: 24px;
            }

            .deals-hero p {
                font-size: 15px;
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
    <header class="top-header" style="background:#2874f0; padding:14px 4%; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
        <div class="logo">
            <h1 style="color:#fff; font-size:30px;">Quick<span style="color:#ffd700;">Basket</span></h1>
        </div>
        <div class="search-box" style="flex:1; max-width:500px; display:flex; margin:0 20px;">
            <input type="text" placeholder="Search for Products, Brands and More" style="flex:1; padding:10px; border:none; border-radius:4px 0 0 4px;">
            <button style="padding:10px 20px; border:none; background:#ffd700; font-weight:700; border-radius:0 4px 4px 0; cursor:pointer;">SEARCH</button>
        </div>
        <div style="color:#fff; display:flex; gap:20px; align-items:center; flex-wrap:wrap;">
            <?php if ($is_logged_in && $user_name): ?>
                <a href="dashboard.php" style="color:#fff; text-decoration:none;"><i class="fa-regular fa-user"></i> <?php echo $user_name; ?></a>
                <a href="logout.php" style="color:#ffd700; text-decoration:none;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="login.php" style="color:#fff; text-decoration:none;"><i class="fa-regular fa-user"></i> Login</a>
            <?php endif; ?>
            <a href="wishlist.php" style="color:#fff; text-decoration:none; position:relative;">
                <i class="fa-regular fa-heart"></i> Wishlist
                <span style="background:#ff3b30; color:#fff; border-radius:50%; padding:2px 7px; font-size:11px; margin-left:4px;">
                    <?php echo isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0; ?>
                </span>
            </a>
            <a href="cart.php" style="color:#fff; text-decoration:none; position:relative;">
                <i class="fa-solid fa-cart-shopping"></i> Cart
                <span style="background:#ff3b30; color:#fff; border-radius:50%; padding:2px 7px; font-size:11px; margin-left:4px;"><?php echo $cart_count; ?></span>
            </a>
        </div>
    </header>

    <!-- ======== CATEGORY NAVIGATION ======== -->
    <!-- ======== DYNAMIC CATEGORY NAV ======== -->
    <nav class="category-nav">
        <div class="category-nav-inner">
            <a href="index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-house"></i> Home
            </a>
            <a href="deals.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'deals.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-fire"></i> Best Deals
            </a>
            <a href="categories.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'categories.php') ? 'active' : ''; ?>">
                <i class="fa-regular fa-folder-open"></i> Categories
            </a>
            <?php
            // Fetch categories from database (limit to 5 for clean look)
            $nav_sql = "SELECT slug, name FROM product_categories ORDER BY name LIMIT 5";
            $nav_result = mysqli_query($conn, $nav_sql);
            while ($cat = mysqli_fetch_assoc($nav_result)):
                $is_active = (isset($_GET['slug']) && $_GET['slug'] == $cat['slug']);
            ?>
                <a href="category-products.php?slug=<?php echo $cat['slug']; ?>" class="<?php echo $is_active ? 'active' : ''; ?>">
                    <i class="fa-regular fa-tag"></i> <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endwhile; ?>
        </div>
    </nav>

    <!-- ======== DEALS PAGE ======== -->
    <div class="deals-page">

        <!-- Hero Banner -->
        <div class="deals-hero">
            <div class="hero-badge">🔥 Limited Time Offer</div>
            <h1>Best <span>Deals</span> For You</h1>
            <p>Shop the hottest deals with up to 70% off on top products.</p>
        </div>

        <!-- Category Deals -->
        <div class="category-deals">
            <div class="category-deal-card deal-blue">
                <div class="deal-content">
                    <h3>Smartphones</h3>
                    <p>Up To 40% OFF</p>
                    <a href="category-products.php?slug=mobiles" class="btn-deal">Shop Now <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <img src="images/mobile.png" alt="Smartphones">
            </div>
            <div class="category-deal-card deal-orange">
                <div class="deal-content">
                    <h3>Fashion Sale</h3>
                    <p>Up To 70% OFF</p>
                    <a href="category-products.php?slug=fashion" class="btn-deal">Explore <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <img src="images/fashion.png" alt="Fashion">
            </div>
            <div class="category-deal-card deal-green">
                <div class="deal-content">
                    <h3>Laptops</h3>
                    <p>Special Student Offers</p>
                    <a href="category-products.php?slug=laptops" class="btn-deal">Buy Now <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <img src="images/laptop.png" alt="Laptops">
            </div>
        </div>

        <!-- Featured Deals Section -->
        <div class="section-header">
            <h2>🔥 Featured <span>Deals</span></h2>
            <a href="products.php">View All →</a>
        </div>

        <div class="products-grid">
            <?php if ($deals_result && mysqli_num_rows($deals_result) > 0): ?>
                <?php while ($product = mysqli_fetch_assoc($deals_result)): ?>
                    <div class="product-card">
                        <?php
                        $badge_class = 'badge-deal';
                        $badge_text = '🔥 Deal';
                        if ($product['discount_percent'] > 40) {
                            $badge_class = 'badge-hot';
                            $badge_text = '🔥 Hot Deal';
                        } elseif ($product['stock'] > 50) {
                            $badge_class = 'badge-sale';
                            $badge_text = '⚡ Sale';
                        }
                        ?>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                        <div class="image-wrap">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <span class="discount-flag">-<?php echo $product['discount_percent']; ?>%</span>
                        </div>
                        <div class="info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="seller">by <?php echo htmlspecialchars($product['store_name'] ?? 'Quick Basket'); ?></div>
                            <div class="rating">★★★★★</div>
                            <div class="price-wrap">
                                <span class="current">₹<?php echo number_format($product['price'], 2); ?></span>
                                <span class="old">₹<?php echo number_format($product['original_price'], 2); ?></span>
                                <span class="discount">(<?php echo $product['discount_percent']; ?>% OFF)</span>
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
                    <h3>No deals available</h3>
                    <p>Check back later for exciting offers!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ======== WHY CHOOSE US ======== -->
    <section style="max-width:1200px; margin:40px auto; padding:0 20px;">
        <div style="text-align:center; margin-bottom:30px;">
            <h2 style="font-size:30px; font-weight:700; color:#1e293b;">Why Choose <span style="color:#2874f0;">Quick Basket</span>?</h2>
            <p style="color:#94a3b8;">We provide the best online shopping experience.</p>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:24px;">
            <div style="background:#fff; padding:30px 20px; border-radius:12px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.06); border:1px solid #eee; transition:0.3s;">
                <div style="width:56px; height:56px; margin:0 auto 14px; background:#eef4ff; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-truck-fast" style="font-size:24px; color:#2874f0;"></i>
                </div>
                <h3 style="font-size:18px; margin-bottom:8px;">Fast Delivery</h3>
                <p style="color:#94a3b8; font-size:14px; line-height:1.7;">Get your orders delivered quickly and safely.</p>
            </div>
            <div style="background:#fff; padding:30px 20px; border-radius:12px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.06); border:1px solid #eee; transition:0.3s;">
                <div style="width:56px; height:56px; margin:0 auto 14px; background:#eef4ff; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-shield-halved" style="font-size:24px; color:#2874f0;"></i>
                </div>
                <h3 style="font-size:18px; margin-bottom:8px;">Secure Payment</h3>
                <p style="color:#94a3b8; font-size:14px; line-height:1.7;">100% secure and trusted payment methods.</p>
            </div>
            <div style="background:#fff; padding:30px 20px; border-radius:12px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.06); border:1px solid #eee; transition:0.3s;">
                <div style="width:56px; height:56px; margin:0 auto 14px; background:#eef4ff; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-rotate-left" style="font-size:24px; color:#2874f0;"></i>
                </div>
                <h3 style="font-size:18px; margin-bottom:8px;">Easy Returns</h3>
                <p style="color:#94a3b8; font-size:14px; line-height:1.7;">Simple return and refund process.</p>
            </div>
            <div style="background:#fff; padding:30px 20px; border-radius:12px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.06); border:1px solid #eee; transition:0.3s;">
                <div style="width:56px; height:56px; margin:0 auto 14px; background:#eef4ff; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-headset" style="font-size:24px; color:#2874f0;"></i>
                </div>
                <h3 style="font-size:18px; margin-bottom:8px;">24/7 Support</h3>
                <p style="color:#94a3b8; font-size:14px; line-height:1.7;">Our support team is always ready to help.</p>
            </div>
        </div>
    </section>

    <!-- ======== FOOTER ======== -->
    <footer class="footer" style="margin-top:40px;">
        <div class="footer-container" style="max-width:1200px; margin:0 auto; padding:40px 20px; display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:30px;">
            <div class="footer-box">
                <h3>Quick Basket</h3>
                <p style="color:#ccc; line-height:1.7;">Your trusted online shopping destination for fashion, electronics, home essentials and much more.</p>
            </div>
            <div class="footer-box">
                <h3>Quick Links</h3>
                <ul style="list-style:none;">
                    <li style="margin-bottom:8px;"><a href="index.php" style="color:#ccc; text-decoration:none;">Home</a></li>
                    <li style="margin-bottom:8px;"><a href="deals.php" style="color:#ccc; text-decoration:none;">Best Deals</a></li>
                    <li style="margin-bottom:8px;"><a href="categories.php" style="color:#ccc; text-decoration:none;">Categories</a></li>
                </ul>
            </div>
            <div class="footer-box">
                <h3>Customer Support</h3>
                <ul style="list-style:none;">
                    <li style="margin-bottom:8px;"><a href="#" style="color:#ccc; text-decoration:none;">Contact Us</a></li>
                    <li style="margin-bottom:8px;"><a href="#" style="color:#ccc; text-decoration:none;">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-box">
                <h3>Follow Us</h3>
                <div style="display:flex; gap:12px;">
                    <a href="#" style="width:40px; height:40px; background:#2874f0; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; text-decoration:none;"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" style="width:40px; height:40px; background:#2874f0; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; text-decoration:none;"><i class="fab fa-instagram"></i></a>
                    <a href="#" style="width:40px; height:40px; background:#2874f0; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; text-decoration:none;"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </div>
        <div style="border-top:1px solid rgba(255,255,255,0.1); text-align:center; padding:20px; color:#ccc;">
            <p>© 2026 Quick Basket. All Rights Reserved.</p>
        </div>
    </footer>

</body>

</html>