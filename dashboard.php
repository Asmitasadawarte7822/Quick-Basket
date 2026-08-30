<?php
session_start();
require_once 'config.php';

// ---------- CHECK LOGIN ----------
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
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


$user_id = $_SESSION['user_id'];

// ---------- FETCH USER INFO ----------
$user_sql = "SELECT id, name, email, phone, status, created_at FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    $name = htmlspecialchars($user['name']);
    $email = htmlspecialchars($user['email']);
    $phone = htmlspecialchars($user['phone']);
    $status = htmlspecialchars($user['status']);
    $joined = date('F j, Y', strtotime($user['created_at']));
} else {
    session_destroy();
    header('Location: login.php');
    exit;
}
mysqli_stmt_close($stmt);

// ---------- FETCH ORDER COUNT ----------
$order_sql = "SELECT COUNT(*) AS total FROM orders WHERE user_id = ?";
$order_stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($order_stmt, "i", $user_id);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);
$order_count = mysqli_fetch_assoc($order_result)['total'];
mysqli_stmt_close($order_stmt);

// ---------- FETCH RECENT ORDERS ----------
$recent_sql = "SELECT id, total_amount, status, order_date FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 3";
$recent_stmt = mysqli_prepare($conn, $recent_sql);
mysqli_stmt_bind_param($recent_stmt, "i", $user_id);
mysqli_stmt_execute($recent_stmt);
$recent_result = mysqli_stmt_get_result($recent_stmt);

$recent_orders = [];
while ($row = mysqli_fetch_assoc($recent_result)) {
    $recent_orders[] = $row;
}
mysqli_stmt_close($recent_stmt);

// ---------- CART COUNT ----------
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;

// ---------- STATUS COLORS ----------
$status_colors = [
    'pending' => '#f39c12',
    'confirmed' => '#3498db',
    'processing' => '#9b59b6',
    'shipped' => '#1abc9c',
    'ontheway' => '#2ecc71',
    'delivered' => '#27ae60',
    'cancelled' => '#e74c3c',
    'refunded' => '#95a5a6'
];

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ============================================
           DASHBOARD - BLUE, WHITE & YELLOW THEME
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

        /* ---------- Header (Flipkart Style) ---------- */
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

        /* ---------- Dashboard Layout ---------- */
        .dashboard-wrap {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 24px;
        }

        /* ---------- Sidebar ---------- */
        .dashboard-sidebar {
            background: #fff;
            border-radius: 12px;
            padding: 24px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .dashboard-sidebar .user-section {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
            margin-bottom: 16px;
        }
        .dashboard-sidebar .user-section .avatar {
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
        .dashboard-sidebar .user-section .user-name {
            font-weight: 600;
            color: #222;
            font-size: 16px;
        }
        .dashboard-sidebar .user-section .user-email {
            font-size: 13px;
            color: #888;
        }
        .dashboard-sidebar .menu-section {
            margin-bottom: 12px;
        }
        .dashboard-sidebar .menu-section .menu-title {
            font-size: 11px;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .dashboard-sidebar .menu-section a {
            display: block;
            padding: 8px 12px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
            border-radius: 8px;
        }
        .dashboard-sidebar .menu-section a:hover {
            background: #f0f7ff;
            color: #2874f0;
        }
        .dashboard-sidebar .menu-section a.active {
            background: #e6f0ff;
            color: #2874f0;
            font-weight: 600;
        }
        .dashboard-sidebar .menu-section a i {
            width: 20px;
            margin-right: 8px;
            color: #888;
        }
        .dashboard-sidebar .menu-section a:hover i {
            color: #2874f0;
        }
        .dashboard-sidebar .logout-link {
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
        .dashboard-sidebar .logout-link:hover {
            background: #fee2e2;
        }
        .dashboard-sidebar .logout-link i {
            margin-right: 8px;
        }

        /* ---------- Main Content ---------- */
        .dashboard-main {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .dashboard-main .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }
        .dashboard-main .welcome-section h2 {
            font-size: 24px;
            color: #222;
        }
        .dashboard-main .welcome-section h2 span {
            color: #2874f0;
        }
        .dashboard-main .welcome-section .date {
            color: #888;
            font-size: 14px;
        }

        /* ---------- Stats Cards ---------- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            padding: 16px 20px;
            text-align: center;
            transition: 0.3s;
        }
        .stat-card:hover {
            border-color: #2874f0;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            transform: translateY(-2px);
        }
        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
            color: #2874f0;
        }
        .stat-card .label {
            font-size: 12px;
            color: #888;
            margin-top: 4px;
        }

        /* ---------- Profile Info ---------- */
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .profile-item {
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            padding: 16px 18px;
            border-left: 3px solid #2874f0;
        }
        .profile-item .label {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            font-weight: 600;
        }
        .profile-item .value {
            font-size: 16px;
            font-weight: 500;
            color: #222;
            margin-top: 4px;
        }
        .profile-item .value .status-badge {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active { background: #d5f5e3; color: #27ae60; }
        .status-inactive { background: #fdebd0; color: #e67e22; }
        .status-suspended { background: #fee2e2; color: #991b1b; }

        /* ---------- Recent Orders ---------- */
        .recent-orders {
            margin-top: 20px;
        }
        .recent-orders h3 {
            font-size: 18px;
            color: #222;
            margin-bottom: 12px;
        }
        .recent-orders h3 span {
            color: #2874f0;
        }
        .recent-orders .order-item {
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            transition: 0.3s;
        }
        .recent-orders .order-item:hover {
            border-color: #2874f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .recent-orders .order-item .order-info .order-id {
            font-weight: 600;
            color: #2874f0;
            font-size: 14px;
        }
        .recent-orders .order-item .order-info .order-date {
            font-size: 12px;
            color: #888;
            margin-left: 10px;
        }
        .recent-orders .order-item .order-status {
            padding: 3px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            color: #fff;
        }
        .recent-orders .order-item .order-amount {
            font-weight: 700;
            color: #2874f0;
            font-size: 16px;
        }
        .recent-orders .view-all {
            display: inline-block;
            color: #2874f0;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            margin-top: 10px;
            transition: 0.3s;
        }
        .recent-orders .view-all:hover {
            color: #0052cc;
            text-decoration: underline;
        }
        .empty-orders-msg {
            text-align: center;
            padding: 30px 20px;
            color: #888;
        }
        .empty-orders-msg i {
            font-size: 40px;
            display: block;
            margin-bottom: 10px;
            color: #ddd;
        }

        /* ---------- Quick Actions ---------- */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-top: 20px;
        }
        .quick-actions a {
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            padding: 14px 16px;
            text-align: center;
            text-decoration: none;
            color: #555;
            font-weight: 500;
            font-size: 13px;
            transition: 0.3s;
        }
        .quick-actions a:hover {
            background: #e6f0ff;
            border-color: #2874f0;
            color: #2874f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .quick-actions a i {
            font-size: 20px;
            display: block;
            margin-bottom: 6px;
            color: #2874f0;
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
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            padding: 20px;
            color: #ccc;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 992px) {
            .dashboard-wrap {
                grid-template-columns: 1fr;
            }
            .dashboard-sidebar {
                position: static;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                padding: 16px;
            }
            .dashboard-sidebar .user-section {
                grid-column: 1 / -1;
            }
            .dashboard-sidebar .menu-section {
                margin-bottom: 0;
            }
            .dashboard-sidebar .logout-link {
                grid-column: 1 / -1;
                margin-top: 0;
            }
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
        }
        @media (max-width: 768px) {
            .dashboard-sidebar {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            .profile-grid {
                grid-template-columns: 1fr;
            }
            .dashboard-main .welcome-section {
                flex-direction: column;
                text-align: center;
            }
            .quick-actions {
                grid-template-columns: 1fr 1fr;
            }
            .recent-orders .order-item {
                flex-direction: column;
                text-align: center;
            }
        }
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .quick-actions {
                grid-template-columns: 1fr;
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
    <i class="fa-solid fa-magnifying-glass search-icon"></i>
    <input type="text" name="query" placeholder="Search for Products, Brands and More..." autocomplete="off">
    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> SEARCH</button>
</form>
    <div class="header-icons">
        <?php if ($is_logged_in): ?>
            <a href="dashboard.php"><i class="fa-regular fa-user"></i> <?php echo $user_name; ?></a>
            <!-- <a href="logout.php" style="color:#f87171;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a> -->
        <?php else: ?>
            <a href="login.php"><i class="fa-regular fa-user"></i> Login</a>
        <?php endif; ?>
        <!-- <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="deals.php"><i class="fa-solid fa-fire"></i> Best Deals</a>
        <a href="categories.php"><i class="fa-regular fa-folder-open"></i> Categories</a> -->
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart <span class="badge"><?php echo $cart_count; ?></span></a>
        
    </div>
</header>

<!-- ======== CATEGORY NAV ======== -->
<nav class="category-nav">
    <div class="category-nav-inner">
        <a href="index.php">Home</a>
        <a href="deals.php">Best Deals</a>
        <a href="categories.php">Categories</a>
        <a href="dashboard.php" class="active">Dashboard</a>
    </div>
</nav>

<!-- ======== DASHBOARD CONTENT ======== -->
<div class="dashboard-wrap">

    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <div class="user-section">
            <div class="avatar"><?php echo substr($name, 0, 1); ?></div>
            <div>
                <div class="user-name"><?php echo $name; ?></div>
                <div class="user-email"><?php echo $email; ?></div>
            </div>
        </div>

        <div class="menu-section">
            <div class="menu-title">Account</div>
            <a href="dashboard.php" class="active"><i class="fa-regular fa-user"></i> My Profile</a>
            <a href="orders.php"><i class="fa-solid fa-box"></i> My Orders</a>
            <a href="manage-address.php"><i class="fa-solid fa-location-dot"></i> Manage Addresses</a>
            <a href="payments.php"><i class="fa-regular fa-credit-card"></i> Payments</a>
            <a href="wishlist.php"><i class="fa-regular fa-heart"></i> Wishlist</a>
            <a href="#"><i class="fa-solid fa-ticket"></i> Coupons</a>
        </div>

        <a href="logout.php" class="logout-link">
            <i class="fa-solid fa-sign-out-alt"></i> Logout
        </a>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-main">
        <!-- Welcome -->
        <div class="welcome-section">
            <h2>Welcome, <span><?php echo $name; ?></span> 👋</h2>
            <span class="date"><?php echo date('l, F j, Y'); ?></span>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?php echo $order_count; ?></div>
                <div class="label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $cart_count; ?></div>
                <div class="label">Cart Items</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo date('Y'); ?></div>
                <div class="label">Member Since</div>
            </div>
        </div>

        <!-- Profile Info -->
        <div class="profile-grid">
            <div class="profile-item">
                <div class="label">Full Name</div>
                <div class="value"><?php echo $name; ?></div>
            </div>
            <div class="profile-item">
                <div class="label">Email Address</div>
                <div class="value"><?php echo $email; ?></div>
            </div>
            <div class="profile-item">
                <div class="label">Mobile Number</div>
                <div class="value"><?php echo $phone; ?></div>
            </div>
            <div class="profile-item">
                <div class="label">Account Status</div>
                <div class="value">
                    <span class="status-badge status-<?php echo $status; ?>">
                        <?php echo ucfirst($status); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="recent-orders">
            <h3>Recent <span>Orders</span></h3>
            <?php if (!empty($recent_orders)): ?>
                <?php foreach ($recent_orders as $order): ?>
                    <div class="order-item">
                        <div class="order-info">
                            <span class="order-id">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            <span class="order-date"><?php echo date('d M Y', strtotime($order['order_date'])); ?></span>
                        </div>
                        <span class="order-status" style="background:<?php echo $status_colors[$order['status']] ?? '#888'; ?>;">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                        <span class="order-amount">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
                <a href="orders.php" class="view-all">View All Orders →</a>
            <?php else: ?>
                <div class="empty-orders-msg">
                    <i class="fa-regular fa-box-open"></i>
                    <p>No orders yet. Start shopping!</p>
                    <a href="index.php" style="color:#2874f0; text-decoration:none; font-weight:600;">Continue Shopping →</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="edit-profile.php"><i class="fa-regular fa-pen-to-square"></i> Edit Profile</a>
            <a href="orders.php"><i class="fa-solid fa-box"></i> My Orders</a>
            <a href="manage-address.php"><i class="fa-solid fa-location-dot"></i> Addresses</a>
            <a href="wishlist.php"><i class="fa-regular fa-heart"></i> Wishlist</a>
        </div>
    </main>
</div>

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