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

// ---------- SESSION WISHLIST ----------
if (!isset($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

// ---------- ADD TO WISHLIST ----------
if (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $product_id = (int)$_GET['add'];
    if (!in_array($product_id, $_SESSION['wishlist'])) {
        $_SESSION['wishlist'][] = $product_id;
        header('Location: wishlist.php?added=' . $product_id);
        exit;
    }
}

// ---------- REMOVE FROM WISHLIST ----------
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    if (($key = array_search($product_id, $_SESSION['wishlist'])) !== false) {
        unset($_SESSION['wishlist'][$key]);
        $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
        header('Location: wishlist.php?removed=1');
        exit;
    }
}

// ---------- MOVE TO CART ----------
if (isset($_GET['move_to_cart']) && is_numeric($_GET['move_to_cart'])) {
    $product_id = (int)$_GET['move_to_cart'];
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]++;
    } else {
        $_SESSION['cart'][$product_id] = 1;
    }
    if (($key = array_search($product_id, $_SESSION['wishlist'])) !== false) {
        unset($_SESSION['wishlist'][$key]);
        $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
    }
    header('Location: wishlist.php?moved=1');
    exit;
}

// ---------- FETCH WISHLIST PRODUCTS ----------
$wishlist_items = [];
if (!empty($_SESSION['wishlist'])) {
    $ids_str = implode(',', $_SESSION['wishlist']);
    $sql = "SELECT p.*, s.store_name 
            FROM products p 
            LEFT JOIN sellers s ON p.seller_id = s.id 
            WHERE p.id IN ($ids_str) AND p.status = 'active'";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $wishlist_items[] = $row;
    }
}

$wishlist_count = count($wishlist_items);
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ============================================
           WISHLIST - BLUE, WHITE & YELLOW THEME
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

        /* ---------- Layout ---------- */
        .wishlist-wrap {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 24px;
        }

        /* ---------- Sidebar ---------- */
        .wishlist-sidebar {
            background: #fff;
            border-radius: 12px;
            padding: 24px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .wishlist-sidebar .user-section {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
            margin-bottom: 16px;
        }
        .wishlist-sidebar .user-section .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #2874f0;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
            text-transform: uppercase;
            flex-shrink: 0;
        }
        .wishlist-sidebar .user-section .user-name { font-weight: 600; color: #222; font-size: 16px; }
        .wishlist-sidebar .user-section .user-email { font-size: 13px; color: #888; }
        .wishlist-sidebar .menu-section { margin-bottom: 12px; }
        .wishlist-sidebar .menu-section .menu-title {
            font-size: 11px;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .wishlist-sidebar .menu-section a {
            display: block;
            padding: 8px 12px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
            border-radius: 8px;
        }
        .wishlist-sidebar .menu-section a:hover { background: #f0f7ff; color: #2874f0; }
        .wishlist-sidebar .menu-section a.active { background: #e6f0ff; color: #2874f0; font-weight: 600; }
        .wishlist-sidebar .menu-section a i { width: 20px; margin-right: 8px; color: #888; }
        .wishlist-sidebar .menu-section a:hover i { color: #2874f0; }
        .wishlist-sidebar .logout-link {
            display: block;
            padding: 10px 12px;
            color: #e74c3c;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border-top: 1px solid #eee;
            margin-top: 16px;
            padding-top: 16px;
            border-radius: 8px;
            transition: 0.3s;
        }
        .wishlist-sidebar .logout-link:hover { background: #fee2e2; }
        .wishlist-sidebar .logout-link i { margin-right: 8px; }

        /* ---------- Main Content ---------- */
        .wishlist-main {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .wishlist-main h2 {
            font-size: 24px;
            color: #222;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }
        .wishlist-main h2 i { color: #2874f0; }
        .wishlist-main h2 span { color: #888; font-weight: 400; font-size: 16px; }

        /* ---------- Wishlist Item ---------- */
        .wishlist-item {
            display: flex;
            gap: 20px;
            padding: 16px 0;
            border-bottom: 1px solid #f0f0f0;
            align-items: center;
        }
        .wishlist-item:last-child {
            border-bottom: none;
        }
        .wishlist-item .item-image {
            width: 100px;
            height: 100px;
            flex-shrink: 0;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #eee;
            padding: 10px;
        }
        .wishlist-item .item-image img {
            max-width: 100%;
            max-height: 80px;
            object-fit: contain;
        }
        .wishlist-item .item-details {
            flex: 1;
        }
        .wishlist-item .item-details .item-title {
            font-size: 16px;
            font-weight: 600;
            color: #222;
            margin-bottom: 2px;
        }
        .wishlist-item .item-details .item-title a {
            color: #222;
            text-decoration: none;
        }
        .wishlist-item .item-details .item-title a:hover {
            color: #2874f0;
        }
        .wishlist-item .item-details .item-seller {
            font-size: 13px;
            color: #888;
            margin-bottom: 2px;
        }
        .wishlist-item .item-details .item-seller i { color: #2874f0; }
        .wishlist-item .item-details .item-assured {
            display: inline-block;
            background: #2874f0;
            color: #fff;
            padding: 0 10px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }
        .wishlist-item .item-details .item-price {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin: 4px 0 10px;
        }
        .wishlist-item .item-details .item-price .current {
            font-size: 20px;
            font-weight: 700;
            color: #2874f0;
        }
        .wishlist-item .item-details .item-price .old {
            font-size: 14px;
            color: #888;
            text-decoration: line-through;
        }
        .wishlist-item .item-details .item-price .discount-badge {
            font-size: 13px;
            color: #27ae60;
            font-weight: 600;
        }
        .wishlist-item .item-details .item-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .wishlist-item .item-details .item-actions .action-btn {
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .wishlist-item .item-details .item-actions .btn-move-cart {
            background: #2874f0;
            color: #fff;
        }
        .wishlist-item .item-details .item-actions .btn-move-cart:hover {
            background: #0052cc;
        }
        .wishlist-item .item-details .item-actions .btn-remove {
            background: #f1f3f6;
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }
        .wishlist-item .item-details .item-actions .btn-remove:hover {
            background: #e74c3c;
            color: #fff;
        }
        .wishlist-item .item-details .item-actions .btn-view {
            background: #f1f3f6;
            color: #555;
        }
        .wishlist-item .item-details .item-actions .btn-view:hover {
            background: #2874f0;
            color: #fff;
        }

        /* ---------- Empty State ---------- */
        .empty-wishlist {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }
        .empty-wishlist i {
            font-size: 80px;
            color: #ddd;
            display: block;
            margin-bottom: 20px;
        }
        .empty-wishlist h3 {
            font-size: 24px;
            color: #222;
            margin-bottom: 8px;
        }
        .empty-wishlist p {
            color: #888;
            margin-bottom: 20px;
        }
        .empty-wishlist .btn-shop {
            display: inline-block;
            padding: 12px 36px;
            background: #2874f0;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .empty-wishlist .btn-shop:hover {
            background: #0052cc;
        }

        /* ---------- Alert Messages ---------- */
        .alert {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: #d5f5e3;
            color: #27ae60;
            border-left: 4px solid #27ae60;
        }
        .alert-success i { font-size: 18px; }

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
            .wishlist-wrap { grid-template-columns: 1fr; }
            .wishlist-sidebar {
                position: static;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                padding: 16px;
            }
            .wishlist-sidebar .user-section { grid-column: 1 / -1; }
            .wishlist-sidebar .menu-section { margin-bottom: 0; }
            .wishlist-sidebar .logout-link { grid-column: 1 / -1; margin-top: 0; }
            .top-header { flex-direction: column; }
            .search-box { width: 100%; max-width: 100%; }
            .header-icons { justify-content: center; flex-wrap: wrap; }
        }
        @media (max-width: 768px) {
            .wishlist-sidebar { grid-template-columns: 1fr; }
            .wishlist-item { flex-direction: column; align-items: center; text-align: center; }
            .wishlist-item .item-image { width: 80px; height: 80px; }
            .wishlist-item .item-details .item-actions { justify-content: center; }
            .wishlist-main { padding: 16px; }
            .wishlist-main h2 { font-size: 20px; }
        }
        @media (max-width: 576px) {
            .wishlist-item .item-details .item-title { font-size: 14px; }
            .wishlist-item .item-details .item-price .current { font-size: 17px; }
            .wishlist-item .item-details .item-actions .action-btn {
                padding: 6px 14px;
                font-size: 12px;
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
        <a href="dashboard.php"><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($user_name ?? 'User'); ?></a>
        <a href="wishlist.php" style="color:#ffd700;"><i class="fa-regular fa-heart"></i> Wishlist <span class="badge"><?php echo $wishlist_count; ?></span></a>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart <span class="badge"><?php echo $cart_count; ?></span></a>
        <a href="logout.php" style="color:#ffd700;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </div>
</header>

<!-- ======== CATEGORY NAV ======== -->
<nav class="category-nav">
    <div class="category-nav-inner">
        <a href="index.php">Home</a>
        <a href="deals.php">Best Deals</a>
        <a href="categories.php">Categories</a>
        <a href="wishlist.php" class="active">Wishlist</a>
    </div>
</nav>

<!-- ======== WISHLIST CONTENT ======== -->
<div class="wishlist-wrap">

    <!-- Sidebar -->
    <aside class="wishlist-sidebar">
        <div class="user-section">
            <div class="avatar"><?php echo $user_name ? strtoupper(substr($user_name, 0, 1)) : 'G'; ?></div>
            <div>
                <div class="user-name"><?php echo $user_name ?: 'Guest User'; ?></div>
                <div class="user-email"><?php echo $user_email ?? 'guest@email.com'; ?></div>
            </div>
        </div>

        <div class="menu-section">
            <div class="menu-title">My Orders</div>
            <a href="#"><i class="fa-regular fa-circle"></i> Account Settings</a>
            <a href="#"><i class="fa-regular fa-circle"></i> Profile Information</a>
            <a href="#"><i class="fa-regular fa-circle"></i> Manage Addresses</a>
            <a href="#"><i class="fa-regular fa-circle"></i> PAN Card Information</a>
        </div>

        <div class="menu-section">
            <div class="menu-title">Payments</div>
            <a href="#"><i class="fa-regular fa-circle"></i> Gift Cards ₹0</a>
            <a href="#"><i class="fa-regular fa-circle"></i> Saved UPI</a>
            <a href="#"><i class="fa-regular fa-circle"></i> Saved Cards</a>
        </div>

        <div class="menu-section">
            <div class="menu-title">My Stuff</div>
            <a href="wishlist.php" class="active"><i class="fa-regular fa-heart"></i> My Wishlist</a>
            <a href="#"><i class="fa-regular fa-circle"></i> My Coupons</a>
        </div>

        <a href="logout.php" class="logout-link">
            <i class="fa-solid fa-sign-out-alt"></i> Logout
        </a>
    </aside>

    <!-- Wishlist Items -->
    <main class="wishlist-main">
        <h2><i class="fa-regular fa-heart"></i> My Wishlist <span>(<?php echo $wishlist_count; ?> items)</span></h2>

        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success">
                <i class="fa-regular fa-circle-check"></i>
                Product added to wishlist!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['removed'])): ?>
            <div class="alert alert-success">
                <i class="fa-regular fa-circle-check"></i>
                Product removed from wishlist!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['moved'])): ?>
            <div class="alert alert-success">
                <i class="fa-regular fa-circle-check"></i>
                Product moved to cart!
            </div>
        <?php endif; ?>

        <?php if (!empty($wishlist_items)): ?>
            <?php foreach ($wishlist_items as $item): ?>
                <?php
                $original_price = $item['price'] * 1.25;
                $discount_percent = round((($original_price - $item['price']) / $original_price) * 100);
                if ($discount_percent < 0) { $discount_percent = 0; }
                ?>
                <div class="wishlist-item">
                    <div class="item-image">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    </div>
                    <div class="item-details">
                        <div class="item-title">
                            <a href="product-details.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                        </div>
                        <div class="item-seller"><i class="fa-regular fa-store"></i> Seller: <?php echo htmlspecialchars($item['store_name'] ?? 'Quick Basket'); ?></div>
                        <span class="item-assured"><i class="fa-regular fa-circle-check"></i> Assured</span>
                        <div class="item-price">
                            <span class="current">₹<?php echo number_format($item['price'], 2); ?></span>
                            <?php if ($discount_percent > 0): ?>
                                <span class="old">₹<?php echo number_format($original_price, 2); ?></span>
                                <span class="discount-badge"><?php echo $discount_percent; ?>% Off</span>
                            <?php endif; ?>
                        </div>
                        <div class="item-actions">
                            <a href="wishlist.php?move_to_cart=<?php echo $item['id']; ?>" class="action-btn btn-move-cart">
                                <i class="fa-solid fa-cart-plus"></i> Move to Cart
                            </a>
                            <a href="wishlist.php?remove=<?php echo $item['id']; ?>" class="action-btn btn-remove" onclick="return confirm('Remove this item from wishlist?')">
                                <i class="fa-regular fa-trash-can"></i> Remove
                            </a>
                            <a href="product-details.php?id=<?php echo $item['id']; ?>" class="action-btn btn-view">
                                <i class="fa-regular fa-eye"></i> View
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-wishlist">
                <i class="fa-regular fa-heart"></i>
                <h3>Your wishlist is empty</h3>
                <p>Save your favorite items here to buy them later.</p>
                <a href="index.php" class="btn-shop"><i class="fa-solid fa-arrow-left"></i> Continue Shopping</a>
            </div>
        <?php endif; ?>
    </main>
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
                <li><a href="categories.php">Categories</a></li>
                <li><a href="deals.php">Best Deals</a></li>
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