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
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;

// ---------- GET SELECTED CATEGORY ----------
$selected_slug = isset($_GET['category']) ? trim($_GET['category']) : '';

// ---------- FETCH ALL CATEGORIES ----------
$cat_sql = "SELECT c.*, COUNT(p.id) AS product_count 
            FROM product_categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
            GROUP BY c.id
            ORDER BY c.name";
$cat_result = mysqli_query($conn, $cat_sql);

// ---------- FETCH PRODUCTS (filtered if category selected) ----------
$product_where = "p.status = 'active'";
$params = [];
$types = "";

if (!empty($selected_slug)) {
    $cat_id_sql = "SELECT id FROM product_categories WHERE slug = ?";
    $cat_id_stmt = mysqli_prepare($conn, $cat_id_sql);
    mysqli_stmt_bind_param($cat_id_stmt, "s", $selected_slug);
    mysqli_stmt_execute($cat_id_stmt);
    $cat_id_result = mysqli_stmt_get_result($cat_id_stmt);
    if ($cat_row = mysqli_fetch_assoc($cat_id_result)) {
        $category_id = $cat_row['id'];
        $product_where .= " AND p.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    mysqli_stmt_close($cat_id_stmt);
}

$product_sql = "SELECT p.*, s.store_name 
                FROM products p 
                LEFT JOIN sellers s ON p.seller_id = s.id 
                WHERE $product_where 
                ORDER BY p.id DESC 
                LIMIT 30";

$stmt = mysqli_prepare($conn, $product_sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$product_result = mysqli_stmt_get_result($stmt);

// ---------- GET CATEGORY NAME FOR HEADER ----------
$selected_category_name = 'All Products';
if (!empty($selected_slug)) {
    $name_sql = "SELECT name FROM product_categories WHERE slug = ?";
    $name_stmt = mysqli_prepare($conn, $name_sql);
    mysqli_stmt_bind_param($name_stmt, "s", $selected_slug);
    mysqli_stmt_execute($name_stmt);
    $name_result = mysqli_stmt_get_result($name_stmt);
    if ($name_row = mysqli_fetch_assoc($name_result)) {
        $selected_category_name = $name_row['name'];
    }
    mysqli_stmt_close($name_stmt);
}

mysqli_close($conn);

// ---------- CATEGORY ICONS ----------
$category_icons = [
    'Mobiles' => 'fa-solid fa-mobile-screen',
    'Laptops' => 'fa-solid fa-laptop',
    'Fashion' => 'fa-solid fa-shirt',
    'Watches' => 'fa-solid fa-clock',
    'Audio' => 'fa-solid fa-headphones',
    'Gaming' => 'fa-solid fa-gamepad',
    'Furniture' => 'fa-solid fa-couch',
    'Jewellery' => 'fa-solid fa-gem',
    'Books' => 'fa-solid fa-book',
    'Electronics' => 'fa-solid fa-microchip',
    'Beauty' => 'fa-solid fa-wand-magic-sparkles',
    'Sports' => 'fa-solid fa-football',
    'Home' => 'fa-solid fa-house',
    'Appliances' => 'fa-solid fa-blender',
    'Automotive' => 'fa-solid fa-car',
    'Toys' => 'fa-solid fa-robot',
    'Grocery' => 'fa-solid fa-basket-shopping',
    'Health' => 'fa-solid fa-heart-pulse'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ============================================
           CATEGORIES PAGE – DASHBOARD THEME
           White · Blue · Yellow
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

        /* ---------- Header (same as dashboard) ---------- */
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
            transition: 0.3s;
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

        /* ---------- Category Nav (same as dashboard) ---------- */
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
            border-bottom-color: #ffd700;
        }
        .category-nav-inner a.active {
            color: #2874f0;
            font-weight: 600;
            border-bottom-color: #2874f0;
        }

        /* ---------- Main Layout ---------- */
        .categories-wrap {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 24px;
        }

        /* ---------- Sidebar (white, like dashboard) ---------- */
        .category-sidebar {
            background: #fff;
            border-radius: 12px;
            padding: 16px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .category-sidebar .sidebar-title {
            padding: 0 20px 12px;
            font-size: 14px;
            font-weight: 700;
            color: #2874f0;
            border-bottom: 1px solid #e5e5e5;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .category-sidebar .sidebar-title i {
            color: #2874f0;
            margin-right: 8px;
        }
        .category-sidebar .category-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: #555;
            text-decoration: none;
            transition: 0.3s;
            border-left: 3px solid transparent;
        }
        .category-sidebar .category-link:hover {
            background: #fff3cd;
            color: #2874f0;
            border-left-color: #ffd700;
        }
        .category-sidebar .category-link.active {
            background: #e6f0ff;
            color: #2874f0;
            border-left-color: #2874f0;
            font-weight: 600;
        }
        .category-sidebar .category-link .cat-icon {
            width: 28px;
            font-size: 16px;
            color: #888;
            text-align: center;
        }
        .category-sidebar .category-link:hover .cat-icon {
            color: #2874f0;
        }
        .category-sidebar .category-link.active .cat-icon {
            color: #2874f0;
        }
        .category-sidebar .category-link .count {
            margin-left: auto;
            font-size: 12px;
            color: #888;
        }
        .category-sidebar .category-link:hover .count {
            color: #555;
        }

        /* ---------- Products Area (white) ---------- */
        .products-area {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e5e5;
        }
        .products-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: #222;
        }
        .products-header h2 span {
            color: #2874f0;
        }
        .products-header h2 i {
            color: #2874f0;
            margin-right: 6px;
        }
        .products-header .product-count {
            color: #888;
            font-size: 14px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }

        /* ---------- Product Card (white, blue border) ---------- */
        .product-card {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            overflow: hidden;
            transition: 0.3s;
            position: relative;
        }
        .product-card:hover {
            transform: translateY(-4px);
            border-color: #2874f0;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
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
        .badge-in {
            background: #ffd700;
            color: #003366;
        }
        .badge-out {
            background: #e5e5e5;
            color: #888;
        }
        .product-card .image-wrap {
            background: #f8f9fa;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 180px;
        }
        .product-card .image-wrap img {
            max-width: 100%;
            max-height: 160px;
            object-fit: contain;
            transition: 0.3s;
        }
        .product-card:hover .image-wrap img {
            transform: scale(1.04);
        }
        .product-card .info {
            padding: 16px 18px 18px;
        }
        .product-card .info h3 {
            font-size: 14px;
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
            color: #ffd700;
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
            font-size: 18px;
            font-weight: 700;
            color: #2874f0;
        }
        .product-card .info .price-wrap .old {
            font-size: 13px;
            color: #888;
            text-decoration: line-through;
        }
        .product-card .info .btn-group {
            display: flex;
            gap: 6px;
            margin-top: 8px;
        }
        .product-card .info .btn-group .btn-cart {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 6px;
            background: #2874f0;
            color: #fff;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: 0.3s;
        }
        .product-card .info .btn-group .btn-cart:hover {
            background: #1a5bc7;
        }
        .product-card .info .btn-group .btn-cart:disabled {
            background: #e5e5e5;
            color: #888;
            cursor: not-allowed;
        }
        .product-card .info .btn-group .btn-buy {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 6px;
            background: #ffd700;
            color: #003366;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: 0.3s;
        }
        .product-card .info .btn-group .btn-buy:hover {
            background: #f5cf00;
        }
        .product-card .info .btn-group .btn-buy:disabled {
            background: #e5e5e5;
            color: #888;
            cursor: not-allowed;
        }
        .product-card .info .btn-view {
            display: block;
            text-align: center;
            margin-top: 4px;
            padding: 4px;
            border-radius: 6px;
            background: #f8f9fa;
            color: #555;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: 0.3s;
        }
        .product-card .info .btn-view:hover {
            background: #e6f0ff;
            color: #2874f0;
        }

        /* ---------- Empty State ---------- */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            grid-column: 1/-1;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px dashed #e5e5e5;
        }
        .empty-state i {
            font-size: 60px;
            color: #ddd;
            display: block;
            margin-bottom: 16px;
        }
        .empty-state h3 {
            font-size: 24px;
            color: #222;
            margin-bottom: 8px;
        }
        .empty-state p {
            color: #888;
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
        .empty-state .btn-shop:hover {
            background: #1a5bc7;
        }

        /* ---------- Footer (same as dashboard) ---------- */
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
            border-top: 1px solid rgba(255,255,255,0.1);
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
            .categories-wrap {
                grid-template-columns: 1fr;
            }
            .category-sidebar {
                position: static;
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
                padding: 12px;
                border: none;
                background: transparent;
                box-shadow: none;
            }
            .category-sidebar .sidebar-title {
                display: none;
            }
            .category-sidebar .category-link {
                padding: 6px 14px;
                border-left: none;
                border-radius: 50px;
                background: #fff;
                border: 1px solid #e5e5e5;
                font-size: 13px;
            }
            .category-sidebar .category-link.active {
                background: #2874f0;
                color: #fff;
                border-left: none;
                border-color: #2874f0;
            }
            .category-sidebar .category-link .cat-icon {
                display: none;
            }
            .category-sidebar .category-link .count {
                display: none;
            }
            .products-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr 1fr;
                gap: 16px;
            }
            .product-card .info .btn-group {
                flex-direction: column;
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
                padding: 12px;
            }
            .product-card .info h3 {
                font-size: 13px;
            }
            .product-card .info .price-wrap .current {
                font-size: 16px;
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
    <form action="search-results.php" method="GET" class="search-box">
        <input type="text" name="query" placeholder="Search for Products, Brands and More..." autocomplete="off">
        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> SEARCH</button>
    </form>
    <div class="header-icons">
        <?php if ($is_logged_in && $user_name): ?>
            <a href="dashboard.php"><i class="fa-regular fa-user"></i> <?php echo $user_name; ?></a>
            <!-- <a href="logout.php" style="color:#ffd700;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a> -->
        <?php else: ?>
            <a href="login.php"><i class="fa-regular fa-user"></i> Login</a>
        <?php endif; ?>
        <a href="wishlist.php"><i class="fa-regular fa-heart"></i> Wishlist <span class="badge"><?php echo $wishlist_count; ?></span></a>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart <span class="badge"><?php echo $cart_count; ?></span></a>
    </div>
</header>

<!-- ======== TOP CATEGORY NAV ======== -->
<nav class="category-nav">
    <div class="category-nav-inner">
        <a href="index.php">Home</a>
        <a href="deals.php">Best Deals</a>
        <a href="categories.php" class="active">Categories</a>
        <a href="categories.php?category=mobiles">Mobiles</a>
        <a href="categories.php?category=laptops">Laptops</a>
        <a href="categories.php?category=fashion">Fashion</a>
        <a href="categories.php?category=watches">Watches</a>
        <a href="categories.php?category=audio">Audio</a>
        <a href="categories.php?category=gaming">Gaming</a>
        <a href="categories.php?category=furniture">Furniture</a>
        <a href="categories.php?category=jewellery">Jewellery</a>
    </div>
</nav>

<!-- ======== MAIN: SIDEBAR + PRODUCTS ======== -->
<div class="categories-wrap">

    <!-- LEFT: Sidebar -->
    <aside class="category-sidebar">
        <div class="sidebar-title"><i class="fa-regular fa-folder-open"></i> Categories</div>
        <a href="categories.php" class="category-link <?php echo empty($selected_slug) ? 'active' : ''; ?>">
            <span class="cat-icon"><i class="fa-regular fa-th-large"></i></span>
            All Categories
            <span class="count">(<?php 
                $total_products = 0;
                mysqli_data_seek($cat_result, 0);
                while ($cat = mysqli_fetch_assoc($cat_result)) $total_products += $cat['product_count'];
                echo $total_products; 
            ?>)</span>
        </a>
        <?php 
        mysqli_data_seek($cat_result, 0);
        while ($cat = mysqli_fetch_assoc($cat_result)):
            $icon = $category_icons[$cat['name']] ?? 'fa-regular fa-tag';
        ?>
            <a href="categories.php?category=<?php echo $cat['slug']; ?>" 
               class="category-link <?php echo ($selected_slug == $cat['slug']) ? 'active' : ''; ?>">
                <span class="cat-icon"><i class="<?php echo $icon; ?>"></i></span>
                <?php echo htmlspecialchars($cat['name']); ?>
                <span class="count">(<?php echo $cat['product_count']; ?>)</span>
            </a>
        <?php endwhile; ?>
    </aside>

    <!-- RIGHT: Products -->
    <div class="products-area">
        <div class="products-header">
            <h2>
                <?php if (!empty($selected_slug)): ?>
                    <i class="fa-regular fa-tag"></i> <?php echo htmlspecialchars($selected_category_name); ?>
                <?php else: ?>
                    <i class="fa-regular fa-th-large"></i> All <span>Products</span>
                <?php endif; ?>
            </h2>
            <span class="product-count"><?php echo mysqli_num_rows($product_result); ?> items</span>
        </div>

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
                                <i class="fa-regular fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-regular fa-box-open"></i>
                    <h3>No products found</h3>
                    <p><?php echo !empty($selected_slug) ? 'This category has no products yet.' : 'Check back later for new arrivals!'; ?></p>
                    <?php if (!empty($selected_slug)): ?>
                        <a href="categories.php" class="btn-shop"><i class="fa-solid fa-arrow-left"></i> View All Categories</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
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
                <li><a href="#">Returns</a></li>
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