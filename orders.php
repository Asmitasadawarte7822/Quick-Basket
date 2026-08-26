<?php
session_start();
require_once 'config.php';

// ---------- CHECK LOGIN ----------
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ---------- FETCH USER INFO ----------
$user_sql = "SELECT name, email FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($stmt);

// ---------- FETCH ORDERS FOR THIS USER ----------
$orders_sql = "SELECT o.*, 
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
               FROM orders o 
               WHERE o.user_id = ? 
               ORDER BY o.id DESC";
$stmt = mysqli_prepare($conn, $orders_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);

$orders = [];
while ($row = mysqli_fetch_assoc($orders_result)) {
    $orders[] = $row;
}
mysqli_stmt_close($stmt);

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

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ============================================
           ORDERS - BLUE, WHITE & YELLOW THEME
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

        /* ---------- Layout ---------- */
        .orders-wrap {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 24px;
        }

        /* ---------- Sidebar ---------- */
        .orders-sidebar {
            background: #fff;
            border-radius: 12px;
            padding: 24px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .orders-sidebar .user-section {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
            margin-bottom: 16px;
        }

        .orders-sidebar .user-section .avatar {
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

        .orders-sidebar .user-section .user-name {
            font-weight: 600;
            color: #222;
            font-size: 16px;
        }

        .orders-sidebar .user-section .user-email {
            font-size: 13px;
            color: #888;
        }

        .orders-sidebar .menu-section {
            margin-bottom: 12px;
        }

        .orders-sidebar .menu-section .menu-title {
            font-size: 11px;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .orders-sidebar .menu-section a {
            display: block;
            padding: 8px 12px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
            border-radius: 8px;
        }

        .orders-sidebar .menu-section a:hover {
            background: #f0f7ff;
            color: #2874f0;
        }

        .orders-sidebar .menu-section a.active {
            background: #e6f0ff;
            color: #2874f0;
            font-weight: 600;
        }

        .orders-sidebar .menu-section a i {
            width: 20px;
            margin-right: 8px;
            color: #888;
        }

        .orders-sidebar .menu-section a:hover i {
            color: #2874f0;
        }

        .orders-sidebar .logout-link {
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

        .orders-sidebar .logout-link:hover {
            background: #fee2e2;
        }

        .orders-sidebar .logout-link i {
            margin-right: 8px;
        }

        /* ---------- Main Content ---------- */
        .orders-main {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }

        .orders-main h2 {
            font-size: 24px;
            color: #222;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }

        .orders-main h2 i {
            color: #2874f0;
        }

        .orders-main h2 span {
            color: #888;
            font-weight: 400;
            font-size: 16px;
        }

        /* ---------- Order Card ---------- */
        .order-card {
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            margin-bottom: 16px;
            overflow: hidden;
            transition: 0.3s;
        }

        .order-card:hover {
            border-color: #2874f0;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            transform: translateY(-2px);
        }

        .order-card .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 18px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            flex-wrap: wrap;
            gap: 8px;
        }

        .order-card .order-header .order-id {
            font-weight: 600;
            color: #2874f0;
            font-size: 15px;
        }

        .order-card .order-header .order-date {
            color: #888;
            font-size: 13px;
        }

        .order-card .order-header .order-status {
            padding: 4px 14px;
            border-radius: 30px;
            color: #fff;
            font-weight: 600;
            font-size: 12px;
        }

        .order-card .order-body {
            padding: 16px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .order-card .order-body .order-items {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-card .order-body .order-items .item-icon {
            width: 40px;
            height: 40px;
            background: #e6f0ff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2874f0;
        }

        .order-card .order-body .order-items .item-count {
            color: #888;
            font-size: 14px;
        }

        .order-card .order-body .order-total {
            text-align: right;
        }

        .order-card .order-body .order-total .amount {
            font-size: 20px;
            font-weight: 700;
            color: #2874f0;
        }

        .order-card .order-body .order-total .label {
            font-size: 12px;
            color: #888;
        }

        .order-card .order-footer {
            padding: 10px 18px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .order-card .order-footer a {
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: 0.3s;
            padding: 4px 12px;
            border-radius: 6px;
        }

        .order-card .order-footer .btn-view {
            color: #2874f0;
        }

        .order-card .order-footer .btn-view:hover {
            background: #e6f0ff;
        }

        .order-card .order-footer .btn-cancel {
            color: #e74c3c;
        }

        .order-card .order-footer .btn-cancel:hover {
            background: #fee2e2;
        }

        /* ---------- Empty State ---------- */
        .empty-orders {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-orders i {
            font-size: 80px;
            color: #ddd;
            display: block;
            margin-bottom: 16px;
        }

        .empty-orders h3 {
            font-size: 24px;
            color: #222;
            margin-bottom: 8px;
        }

        .empty-orders p {
            color: #888;
            margin-bottom: 20px;
        }

        .empty-orders .btn-shop {
            display: inline-block;
            padding: 12px 36px;
            background: #2874f0;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .empty-orders .btn-shop:hover {
            background: #0052cc;
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
            .orders-wrap {
                grid-template-columns: 1fr;
            }

            .orders-sidebar {
                position: static;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                padding: 16px;
            }

            .orders-sidebar .user-section {
                grid-column: 1 / -1;
            }

            .orders-sidebar .menu-section {
                margin-bottom: 0;
            }

            .orders-sidebar .logout-link {
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
            .orders-sidebar {
                grid-template-columns: 1fr;
            }

            .order-card .order-header {
                flex-direction: column;
                text-align: center;
            }

            .order-card .order-body {
                flex-direction: column;
                text-align: center;
            }

            .order-card .order-footer {
                justify-content: center;
            }

            .orders-main {
                padding: 16px;
            }

            .orders-main h2 {
                font-size: 20px;
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
            <a href="dashboard.php"><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($user['name'] ?? 'User'); ?></a>
            <a href="wishlist.php"><i class="fa-regular fa-heart"></i> Wishlist <span class="badge"><?php echo $wishlist_count; ?></span></a>
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
            <a href="orders.php" class="active">My Orders</a>
        </div>
    </nav>

    <!-- ======== ORDERS CONTENT ======== -->
    <div class="orders-wrap">

        <!-- Sidebar -->
        <aside class="orders-sidebar">
            <div class="user-section">
                <div class="avatar"><?php echo $user ? strtoupper(substr($user['name'], 0, 1)) : 'U'; ?></div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                </div>
            </div>

            <div class="menu-section">
                <div class="menu-title">Account</div>
                <a href="dashboard.php"><i class="fa-regular fa-user"></i> My Profile</a>
                <a href="orders.php" class="active"><i class="fa-solid fa-box"></i> My Orders</a>
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
        <main class="orders-main">
            <h2><i class="fa-solid fa-box"></i> My Orders <span>(<?php echo count($orders); ?> orders)</span></h2>

            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            <span class="order-date"><i class="fa-regular fa-calendar"></i> <?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></span>
                            <span class="order-status" style="background:<?php echo $status_colors[$order['status']] ?? '#888'; ?>;">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <div class="order-body">
                            <div class="order-items">
                                <div class="item-icon"><i class="fa-solid fa-box"></i></div>
                                <span class="item-count"><?php echo $order['item_count'] ?? 0; ?> item(s)</span>
                            </div>
                            <div class="order-total">
                                <div class="label">Total Amount</div>
                                <div class="amount">₹<?php echo number_format($order['total_amount'], 2); ?></div>
                            </div>
                        </div>
                        <!-- In the order-footer section -->
                        <div class="order-footer">
                            <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn-view">
                                <i class="fa-regular fa-eye"></i> View Details
                            </a>
                            <a href="order-tracking.php?id=<?php echo $order['id']; ?>" class="btn-track" style="color:#2874f0;">
                                <i class="fa-solid fa-location-dot"></i> Track Order
                            </a>
                            <?php if ($order['status'] == 'pending' || $order['status'] == 'confirmed'): ?>
                                <a href="cancel-order.php?id=<?php echo $order['id']; ?>" class="btn-cancel" onclick="return confirm('Cancel this order?')">
                                    <i class="fa-regular fa-circle-xmark"></i> Cancel Order
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-orders">
                    <i class="fa-regular fa-box-open"></i>
                    <h3>No orders yet</h3>
                    <p>Start shopping to see your orders here.</p>
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