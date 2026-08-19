<?php
session_start();
require_once 'config.php';

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

// ---------- CART & WISHLIST COUNT ----------
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Same as manage-address.php - Blue, White & Yellow Theme */
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

        .payments-wrap {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 24px;
        }

        .payments-sidebar {
            background: #fff;
            border-radius: 12px;
            padding: 24px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .payments-sidebar .user-section {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
            margin-bottom: 16px;
        }
        .payments-sidebar .user-section .avatar {
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
        .payments-sidebar .user-section .user-name { font-weight: 600; color: #222; font-size: 16px; }
        .payments-sidebar .user-section .user-email { font-size: 13px; color: #888; }
        .payments-sidebar .menu-section { margin-bottom: 12px; }
        .payments-sidebar .menu-section .menu-title {
            font-size: 11px;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .payments-sidebar .menu-section a {
            display: block;
            padding: 8px 12px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
            border-radius: 8px;
        }
        .payments-sidebar .menu-section a:hover { background: #f0f7ff; color: #2874f0; }
        .payments-sidebar .menu-section a.active { background: #e6f0ff; color: #2874f0; font-weight: 600; }
        .payments-sidebar .menu-section a i { width: 20px; margin-right: 8px; color: #888; }
        .payments-sidebar .menu-section a:hover i { color: #2874f0; }
        .payments-sidebar .logout-link {
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
        .payments-sidebar .logout-link:hover { background: #fee2e2; }
        .payments-sidebar .logout-link i { margin-right: 8px; }

        .payments-main {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .payments-main h2 {
            font-size: 24px;
            color: #222;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }
        .payments-main h2 i { color: #2874f0; }

        .payment-option {
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: 0.3s;
            cursor: pointer;
        }
        .payment-option:hover {
            border-color: #2874f0;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            transform: translateY(-2px);
        }
        .payment-option .icon {
            width: 48px;
            height: 48px;
            background: #e6f0ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2874f0;
            font-size: 22px;
            flex-shrink: 0;
        }
        .payment-option .info {
            flex: 1;
        }
        .payment-option .info h4 {
            color: #222;
            font-size: 16px;
        }
        .payment-option .info p {
            color: #888;
            font-size: 13px;
        }
        .payment-option .status {
            color: #27ae60;
            font-size: 13px;
            font-weight: 600;
        }
        .payment-option .status i { margin-right: 4px; }

        .empty-msg {
            text-align: center;
            padding: 40px 20px;
            color: #888;
        }
        .empty-msg i {
            font-size: 60px;
            display: block;
            margin-bottom: 16px;
            color: #ddd;
        }
        .empty-msg h3 { color: #222; font-size: 20px; margin-bottom: 8px; }

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

        @media (max-width: 992px) {
            .payments-wrap { grid-template-columns: 1fr; }
            .payments-sidebar {
                position: static;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                padding: 16px;
            }
            .payments-sidebar .user-section { grid-column: 1 / -1; }
            .payments-sidebar .menu-section { margin-bottom: 0; }
            .payments-sidebar .logout-link { grid-column: 1 / -1; margin-top: 0; }
            .top-header { flex-direction: column; }
            .search-box { width: 100%; max-width: 100%; }
            .header-icons { justify-content: center; flex-wrap: wrap; }
        }
        @media (max-width: 768px) {
            .payments-sidebar { grid-template-columns: 1fr; }
            .payments-main { padding: 16px; }
            .payments-main h2 { font-size: 20px; }
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
        <a href="dashboard.php">Dashboard</a>
    </div>
</nav>

<!-- ======== PAYMENTS CONTENT ======== -->
<div class="payments-wrap">

    <!-- Sidebar -->
    <aside class="payments-sidebar">
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
            <a href="orders.php"><i class="fa-solid fa-box"></i> My Orders</a>
            <a href="manage-address.php"><i class="fa-solid fa-location-dot"></i> Manage Addresses</a>
            <a href="payments.php" class="active"><i class="fa-regular fa-credit-card"></i> Payments</a>
            <a href="wishlist.php"><i class="fa-regular fa-heart"></i> Wishlist</a>
            <a href="#"><i class="fa-solid fa-ticket"></i> Coupons</a>
        </div>

        <a href="logout.php" class="logout-link">
            <i class="fa-solid fa-sign-out-alt"></i> Logout
        </a>
    </aside>

    <!-- Main Content -->
    <main class="payments-main">
        <h2><i class="fa-regular fa-credit-card"></i> Payment Methods</h2>

        <div class="payment-option">
            <div class="icon"><i class="fa-regular fa-credit-card"></i></div>
            <div class="info">
                <h4>Cash on Delivery</h4>
                <p>Pay when you receive your order</p>
            </div>
            <div class="status"><i class="fa-regular fa-circle-check"></i> Available</div>
        </div>

        <div class="payment-option">
            <div class="icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
            <div class="info">
                <h4>UPI (Google Pay, PhonePe, Paytm)</h4>
                <p>Instant payment via UPI</p>
            </div>
            <div class="status"><i class="fa-regular fa-circle-check"></i> Available</div>
        </div>

        <div class="payment-option">
            <div class="icon"><i class="fa-regular fa-credit-card"></i></div>
            <div class="info">
                <h4>Credit / Debit Card</h4>
                <p>Visa, Mastercard, RuPay, American Express</p>
            </div>
            <div class="status"><i class="fa-regular fa-circle-check"></i> Available</div>
        </div>

        <div class="payment-option">
            <div class="icon"><i class="fa-solid fa-building-columns"></i></div>
            <div class="info">
                <h4>Net Banking</h4>
                <p>All major banks supported</p>
            </div>
            <div class="status"><i class="fa-regular fa-circle-check"></i> Available</div>
        </div>

        <div class="payment-option">
            <div class="icon"><i class="fa-regular fa-gift"></i></div>
            <div class="info">
                <h4>Gift Cards</h4>
                <p>Redeem your gift cards</p>
            </div>
            <div class="status" style="color:#888;">Coming Soon</div>
        </div>

        <div style="margin-top:20px; padding:16px; background:#e6f0ff; border:1px solid #2874f0; border-radius:8px;">
            <p style="color:#555; font-size:14px;">
                <i class="fa-solid fa-shield-halved" style="color:#2874f0;"></i>
                <strong style="color:#222;">Secure Payments</strong><br>
                All transactions are encrypted and secure. Your payment information is safe with us.
            </p>
        </div>
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