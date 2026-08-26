<?php
session_start();
require_once 'config.php';

// ---------- CHECK LOGIN ----------
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ---------- GET ORDER ID ----------
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) {
    header('Location: orders.php');
    exit;
}

// ---------- FETCH ORDER ----------
$order_sql = "SELECT o.*, u.name AS customer_name, u.email AS customer_email 
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              WHERE o.id = ? AND o.user_id = ?";
$order_stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($order_stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);

if (mysqli_num_rows($order_result) === 0) {
    header('Location: orders.php');
    exit;
}
$order = mysqli_fetch_assoc($order_result);
mysqli_stmt_close($order_stmt);

// ---------- FETCH ORDER ITEMS ----------
$items_sql = "SELECT oi.*, p.name AS product_name, p.image AS product_image 
              FROM order_items oi 
              LEFT JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = ?";
$items_stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

$order_items = [];
$subtotal = 0;
while ($row = mysqli_fetch_assoc($items_result)) {
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $subtotal += $row['subtotal'];
    $order_items[] = $row;
}
mysqli_stmt_close($items_stmt);

// ---------- CALCULATE TOTALS ----------
$grand_total = $order['grand_total'] ?? $order['total_amount'];

// ---------- STATUS STEP MAPPING ----------
$status_steps = [
    'pending' => ['step' => 1, 'label' => 'Order Placed', 'icon' => 'fa-regular fa-clock', 'color' => '#f39c12'],
    'confirmed' => ['step' => 2, 'label' => 'Confirmed', 'icon' => 'fa-regular fa-circle-check', 'color' => '#3498db'],
    'processing' => ['step' => 2, 'label' => 'Processing', 'icon' => 'fa-solid fa-gear', 'color' => '#9b59b6'],
    'shipped' => ['step' => 3, 'label' => 'Shipped', 'icon' => 'fa-solid fa-truck', 'color' => '#1abc9c'],
    'ontheway' => ['step' => 4, 'label' => 'On the Way', 'icon' => 'fa-solid fa-truck-fast', 'color' => '#2ecc71'],
    'delivered' => ['step' => 5, 'label' => 'Delivered', 'icon' => 'fa-regular fa-circle-check', 'color' => '#27ae60'],
    'cancelled' => ['step' => 0, 'label' => 'Cancelled', 'icon' => 'fa-regular fa-circle-xmark', 'color' => '#e74c3c'],
    'refunded' => ['step' => 0, 'label' => 'Refunded', 'icon' => 'fa-regular fa-credit-card', 'color' => '#95a5a6']
];

$current_status = $order['status'];
$current_step = $status_steps[$current_status]['step'] ?? 1;
$is_cancelled = ($current_status == 'cancelled' || $current_status == 'refunded');

// ---------- ESTIMATED DELIVERY DATE ----------
$delivery_date = date('d M Y', strtotime($order['order_date'] . ' + 5 days'));

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?> - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', sans-serif;
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
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .logo h1 {
            color: #fff;
            font-size: 32px;
            font-weight: 700;
        }
        .logo span { color: #ffd700; }
        .search-box {
            flex: 1;
            display: flex;
            max-width: 600px;
            background: #fff;
            border-radius: 4px;
            overflow: hidden;
        }
        .search-box input {
            flex: 1;
            padding: 10px 16px;
            border: none;
            outline: none;
            font-size: 14px;
        }
        .search-box input::placeholder { color: #999; }
        .search-box button {
            padding: 10px 24px;
            border: none;
            background: #ffd700;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }
        .search-box button:hover { background: #f5cf00; }
        .header-icons {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .header-icons a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .header-icons a:hover { color: #ffd700; }
        .header-icons .badge {
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
            min-height: 44px;
        }
        .category-nav-inner::-webkit-scrollbar { display: none; }
        .category-nav-inner a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 8px 14px;
            white-space: nowrap;
            font-size: 13px;
            border-bottom: 3px solid transparent;
            transition: 0.3s;
        }
        .category-nav-inner a:hover { color: #2874f0; border-bottom-color: #2874f0; }
        .category-nav-inner a.active { color: #2874f0; font-weight: 600; border-bottom-color: #2874f0; }

        /* ---------- Tracking Container ---------- */
        .tracking-wrap {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .tracking-header {
            background: #fff;
            padding: 20px 24px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }
        .tracking-header h2 {
            font-size: 22px;
            color: #222;
        }
        .tracking-header h2 i { color: #2874f0; }
        .tracking-header .order-status {
            padding: 6px 20px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 14px;
            color: #fff;
        }
        .tracking-header .order-date { color: #888; font-size: 14px; }
        .tracking-header a {
            color: #2874f0;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: 0.3s;
        }
        .tracking-header a:hover { color: #0052cc; text-decoration: underline; }

        /* ---------- Tracking Timeline ---------- */
        .tracking-timeline {
            background: #fff;
            padding: 30px 30px 40px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            margin-bottom: 24px;
        }
        .tracking-timeline .estimated {
            text-align: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
        }
        .tracking-timeline .estimated .date {
            font-size: 20px;
            font-weight: 700;
            color: #2874f0;
        }
        .tracking-timeline .estimated .label { color: #888; font-size: 14px; }

        /* ---------- Progress Steps ---------- */
        .tracking-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            padding: 0 10px;
            max-width: 700px;
            margin: 0 auto;
        }
        .tracking-steps::before {
            content: '';
            position: absolute;
            top: 32px;
            left: 35px;
            right: 35px;
            height: 4px;
            background: #e5e7eb;
            z-index: 0;
            border-radius: 4px;
        }
        .tracking-steps .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
            z-index: 1;
            cursor: default;
        }
        .tracking-steps .step .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #fff;
            background: #e5e7eb;
            transition: 0.3s;
            border: 3px solid #e5e7eb;
            margin-bottom: 8px;
        }
        .tracking-steps .step .step-label {
            font-size: 12px;
            color: #888;
            text-align: center;
            font-weight: 500;
        }
        .tracking-steps .step .step-date {
            font-size: 10px;
            color: #bbb;
            text-align: center;
            margin-top: 2px;
        }
        .tracking-steps .step.completed .step-icon {
            background: #27ae60;
            border-color: #27ae60;
        }
        .tracking-steps .step.active .step-icon {
            background: #2874f0;
            border-color: #2874f0;
            box-shadow: 0 0 0 8px rgba(40,116,240,0.15);
            animation: pulse 2s infinite;
        }
        .tracking-steps .step.active .step-label { color: #2874f0; font-weight: 600; }
        .tracking-steps .step.cancelled .step-icon {
            background: #e74c3c;
            border-color: #e74c3c;
        }
        .tracking-steps .step.cancelled .step-label { color: #e74c3c; font-weight: 600; }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(40,116,240,0.2); }
            70% { box-shadow: 0 0 0 12px rgba(40,116,240,0); }
            100% { box-shadow: 0 0 0 0 rgba(40,116,240,0); }
        }

        .tracking-steps .progress-fill {
            position: absolute;
            top: 32px;
            left: 35px;
            height: 4px;
            background: #2874f0;
            z-index: 0;
            border-radius: 4px;
            transition: width 0.8s ease;
        }

        /* ---------- Order Details ---------- */
        .order-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .detail-card {
            background: #fff;
            padding: 18px 22px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            border: 1px solid #e5e5e5;
        }
        .detail-card h4 {
            font-size: 14px;
            font-weight: 600;
            color: #888;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }
        .detail-card h4 i { color: #2874f0; margin-right: 6px; }
        .detail-card p { font-size: 14px; color: #555; line-height: 1.8; margin: 4px 0; }
        .detail-card .label { color: #888; }
        .detail-card .value { color: #222; font-weight: 500; }

        /* ---------- Items Table ---------- */
        .items-table-wrap {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            margin-bottom: 20px;
        }
        .items-table-wrap table { width: 100%; border-collapse: collapse; }
        .items-table-wrap th {
            background: #f8f9fa;
            padding: 12px 18px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #eee;
        }
        .items-table-wrap td {
            padding: 12px 18px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
            color: #333;
        }
        .items-table-wrap .product-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .items-table-wrap .product-cell img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 4px;
            border: 1px solid #eee;
        }
        .items-table-wrap .product-cell .pname { font-weight: 500; color: #222; }
        .items-table-wrap .total-row td {
            font-weight: 700;
            font-size: 16px;
            padding-top: 14px;
            border-top: 2px solid #2874f0;
        }
        .items-table-wrap .total-row .label { color: #888; font-weight: 400; }
        .items-table-wrap .total-row .value { color: #2874f0; }

        /* ---------- Actions ---------- */
        .tracking-actions {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 16px;
        }
        .tracking-actions a {
            padding: 10px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: 0.3s;
        }
        .btn-back {
            background: #f1f3f6;
            color: #555;
            border: 1px solid #ddd;
        }
        .btn-back:hover { background: #e5e7eb; color: #333; }
        .btn-cancel {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .btn-cancel:hover { background: #fecaca; }

        /* ---------- Footer ---------- */
        .footer {
            background: #172337;
            color: #fff;
            margin-top: 40px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 30px;
        }
        .footer-box h3 { font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #fff; }
        .footer-box p, .footer-box a {
            color: #ccc;
            font-size: 14px;
            line-height: 1.8;
            text-decoration: none;
            display: block;
            padding: 4px 0;
            transition: 0.3s;
        }
        .footer-box a:hover { color: #ffd700; }
        .social-icons {
            display: flex;
            gap: 12px;
        }
        .social-icons a {
            width: 38px;
            height: 38px;
            background: #2874f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            transition: 0.3s;
        }
        .social-icons a:hover { background: #ffd700; color: #000; }
        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.06);
            text-align: center;
            color: #ccc;
            font-size: 13px;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 992px) {
            .top-header { flex-direction: column; align-items: stretch; }
            .search-box { max-width: 100%; }
            .header-icons { justify-content: center; flex-wrap: wrap; }
            .order-details-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .tracking-header { flex-direction: column; align-items: stretch; text-align: center; }
            .tracking-header .order-status { align-self: center; }
            .tracking-steps { flex-wrap: wrap; gap: 12px; }
            .tracking-steps::before { display: none; }
            .tracking-steps .step { flex: 0 0 33%; }
            .items-table-wrap th, .items-table-wrap td { padding: 8px 12px; font-size: 13px; }
            .product-cell img { width: 40px; height: 40px; }
            .tracking-timeline { padding: 20px; }
        }
        @media (max-width: 576px) {
            .items-table-wrap th, .items-table-wrap td { padding: 6px 10px; font-size: 12px; }
            .product-cell .pname { font-size: 13px; }
            .tracking-actions { flex-direction: column; align-items: stretch; }
            .tracking-actions a { text-align: center; }
            .tracking-header h2 { font-size: 18px; }
            .tracking-steps .step { flex: 0 0 50%; }
        }
    </style>
</head>
<body>

<header class="top-header">
    <div class="logo">
        <h1>Quick<span>Basket</span></h1>
    </div>
    <form action="search-results.php" method="GET" class="search-box">
        <input type="text" name="query" placeholder="Search for Products, Brands and More..." autocomplete="off">
        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> SEARCH</button>
    </form>
    <div class="header-icons">
        <a href="dashboard.php"><i class="fa-regular fa-user"></i> Account</a>
        <a href="wishlist.php"><i class="fa-regular fa-heart"></i> Wishlist <span class="badge"><?php echo $wishlist_count; ?></span></a>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart <span class="badge"><?php echo $cart_count; ?></span></a>
        <a href="logout.php" style="color:#ffd700;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </div>
</header>

<nav class="category-nav">
    <div class="category-nav-inner">
        <a href="index.php">Home</a>
        <a href="deals.php">Best Deals</a>
        <a href="categories.php">Categories</a>
        <a href="orders.php" class="active">My Orders</a>
    </div>
</nav>

<div class="tracking-wrap">

    <div class="tracking-header">
        <div>
            <h2><i class="fa-solid fa-location-dot"></i> Track Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></h2>
            <span class="order-date">Placed on <?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></span>
        </div>
        <div>
            <?php if (!$is_cancelled): ?>
                <span class="order-status" style="background:<?php echo $status_steps[$current_status]['color'] ?? '#888'; ?>;">
                    <?php echo strtoupper($status_steps[$current_status]['label'] ?? $current_status); ?>
                </span>
            <?php else: ?>
                <span class="order-status" style="background:#e74c3c;">
                    <?php echo strtoupper($current_status); ?>
                </span>
            <?php endif; ?>
        </div>
        <a href="orders.php"><i class="fa-solid fa-arrow-left"></i> Back to Orders</a>
    </div>

    <?php if ($is_cancelled): ?>
        <div class="tracking-timeline" style="text-align:center; padding:40px;">
            <i class="fa-regular fa-circle-xmark" style="font-size:60px; color:#e74c3c; display:block; margin-bottom:16px;"></i>
            <h3 style="color:#222; font-size:24px; margin-bottom:8px;">Order Cancelled</h3>
            <p style="color:#888;">This order has been cancelled. If you have any questions, please contact support.</p>
            <div style="margin-top:20px;">
                <a href="orders.php" class="btn-back" style="display:inline-block; padding:10px 30px; border-radius:50px; background:#2874f0; color:#fff; text-decoration:none;">View All Orders</a>
            </div>
        </div>
    <?php else: ?>

        <div class="tracking-timeline">
            <div class="estimated">
                <span class="label">Estimated Delivery</span><br>
                <span class="date"><i class="fa-regular fa-calendar"></i> <?php echo $delivery_date; ?></span>
            </div>

            <div class="tracking-steps" style="position:relative;">
                <div class="progress-fill" style="width: <?php echo min(($current_step - 1) * 25, 100); ?>%;"></div>

                <div class="step <?php echo ($current_step >= 1) ? 'completed' : ''; ?> <?php echo ($current_step == 1 && !$is_cancelled) ? 'active' : ''; ?>">
                    <div class="step-icon"><i class="fa-regular fa-clock"></i></div>
                    <span class="step-label">Order Placed</span>
                    <span class="step-date"><?php echo date('d M', strtotime($order['order_date'])); ?></span>
                </div>

                <div class="step <?php echo ($current_step >= 2) ? 'completed' : ''; ?> <?php echo ($current_step == 2) ? 'active' : ''; ?>">
                    <div class="step-icon"><i class="fa-regular fa-circle-check"></i></div>
                    <span class="step-label">Confirmed</span>
                    <span class="step-date"><?php echo ($current_step >= 2) ? date('d M', strtotime($order['order_date'] . ' + 1 day')) : 'Pending'; ?></span>
                </div>

                <div class="step <?php echo ($current_step >= 3) ? 'completed' : ''; ?> <?php echo ($current_step == 3) ? 'active' : ''; ?>">
                    <div class="step-icon"><i class="fa-solid fa-truck"></i></div>
                    <span class="step-label">Shipped</span>
                    <span class="step-date"><?php echo ($current_step >= 3) ? date('d M', strtotime($order['order_date'] . ' + 2 days')) : 'Pending'; ?></span>
                </div>

                <div class="step <?php echo ($current_step >= 4) ? 'completed' : ''; ?> <?php echo ($current_step == 4) ? 'active' : ''; ?>">
                    <div class="step-icon"><i class="fa-solid fa-truck-fast"></i></div>
                    <span class="step-label">On the Way</span>
                    <span class="step-date"><?php echo ($current_step >= 4) ? date('d M', strtotime($order['order_date'] . ' + 3 days')) : 'Pending'; ?></span>
                </div>

                <div class="step <?php echo ($current_step >= 5) ? 'completed' : ''; ?> <?php echo ($current_step == 5) ? 'active' : ''; ?>">
                    <div class="step-icon"><i class="fa-regular fa-circle-check"></i></div>
                    <span class="step-label">Delivered</span>
                    <span class="step-date"><?php echo ($current_step >= 5) ? date('d M', strtotime($order['order_date'] . ' + 5 days')) : 'Pending'; ?></span>
                </div>
            </div>
        </div>

        <div class="order-details-grid">
            <div class="detail-card">
                <h4><i class="fa-regular fa-user"></i> Customer</h4>
                <p><span class="label">Name:</span> <span class="value"><?php echo htmlspecialchars($order['customer_name']); ?></span></p>
                <p><span class="label">Email:</span> <span class="value"><?php echo htmlspecialchars($order['customer_email']); ?></span></p>
                <p><span class="label">Payment:</span> <span class="value"><?php echo strtoupper(htmlspecialchars($order['payment_method'])); ?></span></p>
            </div>
            <div class="detail-card">
                <h4><i class="fa-solid fa-truck"></i> Shipping</h4>
                <p><span class="label">Method:</span> <span class="value"><?php echo strtoupper(htmlspecialchars($order['payment_method'])); ?></span></p>
                <p><span class="label">Status:</span> <span class="value"><?php echo ucfirst($order['payment_status'] ?? 'N/A'); ?></span></p>
                <?php if (!empty($order['shipping_address'])): ?>
                    <p style="margin-top:8px;"><span class="label">Address:</span><br>
                    <span class="value"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></span></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="items-table-wrap">
            <table>
                <thead>
                    <tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($order_items)): ?>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="product-cell">
                                        <img src="<?php echo htmlspecialchars($item['product_image'] ?? 'https://via.placeholder.com/50'); ?>" alt="Product">
                                        <span class="pname"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                    </div>
                                </td>
                                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₹<?php echo number_format($item['subtotal'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; color:#888; padding:20px;">No items found.</td></tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td colspan="3" class="label">Grand Total</td>
                        <td class="value">₹<?php echo number_format($grand_total, 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="tracking-actions">
            <a href="orders.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Orders</a>
            <?php if ($current_status == 'pending' || $current_status == 'confirmed'): ?>
                <a href="cancel-order.php?id=<?php echo $order_id; ?>" class="btn-cancel" onclick="return confirm('Are you sure you want to cancel this order?')">
                    <i class="fa-regular fa-circle-xmark"></i> Cancel Order
                </a>
            <?php endif; ?>
            <a href="index.php" style="background:#2874f0; color:#fff; padding:10px 24px; border-radius:50px; text-decoration:none; font-weight:600; transition:0.3s;" onmouseover="this.style.background='#0052cc'" onmouseout="this.style.background='#2874f0'">
                <i class="fa-solid fa-arrow-right"></i> Continue Shopping
            </a>
        </div>

    <?php endif; ?>

</div>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-box">
            <h3>Quick Basket</h3>
            <p>Your trusted online shopping destination.</p>
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