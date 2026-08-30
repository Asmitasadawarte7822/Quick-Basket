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
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($order_id <= 0) {
    header('Location: my-orders.php');
    exit;
}

// ---------- FETCH ORDER DETAILS ----------
$order_sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);

if ($order = mysqli_fetch_assoc($order_result)) {
    $order_number = str_pad($order['id'], 6, '0', STR_PAD_LEFT);
    $total = $order['total_amount'];
    $status = $order['status'];
    $payment_status = $order['payment_status'] ?? 'pending';
    $order_date = date('d M Y, h:i A', strtotime($order['order_date']));
    $shipping_address = nl2br(htmlspecialchars($order['shipping_address'] ?? 'Not provided'));
} else {
    header('Location: my-orders.php');
    exit;
}
mysqli_stmt_close($stmt);

// ---------- FETCH ORDER ITEMS ----------
$items_sql = "SELECT oi.*, p.name AS product_name, p.image 
              FROM order_items oi 
              LEFT JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = ?";
$items_stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

$items = [];
while ($row = mysqli_fetch_assoc($items_result)) {
    $items[] = $row;
}
mysqli_stmt_close($items_stmt);

// ---------- FETCH STATUS HISTORY (if table exists) ----------
$history = [];
$history_table_exists = false;

// Check if order_status_history table exists
$check_table = "SHOW TABLES LIKE 'order_status_history'";
$table_check = mysqli_query($conn, $check_table);
if (mysqli_num_rows($table_check) > 0) {
    $history_table_exists = true;
    $history_sql = "SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC";
    $history_stmt = mysqli_prepare($conn, $history_sql);
    mysqli_stmt_bind_param($history_stmt, "i", $order_id);
    mysqli_stmt_execute($history_stmt);
    $history_result = mysqli_stmt_get_result($history_stmt);
    while ($row = mysqli_fetch_assoc($history_result)) {
        $history[] = $row;
    }
    mysqli_stmt_close($history_stmt);
}

// If no history, create default history array
if (empty($history)) {
    $history[] = [
        'status' => $status,
        'created_at' => $order['order_date'],
        'note' => 'Order placed successfully.'
    ];
}

mysqli_close($conn);

// ---------- STATUS CONFIG ----------
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

$status_labels = [
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'processing' => 'Processing',
    'shipped' => 'Shipped',
    'ontheway' => 'On The Way',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
    'refunded' => 'Refunded'
];

// ---------- PROGRESS STEPS ----------
$progress_steps = ['pending', 'confirmed', 'processing', 'shipped', 'ontheway', 'delivered'];
$current_step_index = array_search($status, $progress_steps);
if ($current_step_index === false) $current_step_index = -1;

$is_cancelled = ($status === 'cancelled' || $status === 'refunded');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?php echo $order_number; ?> – Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ============================================
           TRACK ORDER – WHITE/BLUE/YELLOW
           ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        body {
            background: #f1f3f6;
            color: #222;
        }

        .track-wrap {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .track-card {
            background: #fff;
            border-radius: 12px;
            padding: 30px 35px;
            border: 1px solid #e5e5e5;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }

        .track-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e5e5;
        }

        .track-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #222;
        }

        .track-header h2 span {
            color: #2874f0;
        }

        .track-header .order-status-badge {
            padding: 6px 18px;
            border-radius: 50px;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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

        /* ---------- ORDER INFO ---------- */
        .order-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 25px;
        }

        .order-info-item {
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 8px;
            border-left: 3px solid #2874f0;
        }

        .order-info-item .label {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            font-weight: 600;
        }

        .order-info-item .value {
            font-size: 16px;
            font-weight: 500;
            color: #222;
            margin-top: 2px;
        }

        /* ---------- PROGRESS BAR ---------- */
        .progress-container {
            margin: 25px 0 30px;
            padding: 20px 0;
            border-top: 1px solid #e5e5e5;
            border-bottom: 1px solid #e5e5e5;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            padding: 0 10px;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 10%;
            right: 10%;
            height: 4px;
            background: #e5e5e5;
            transform: translateY(-50%);
            z-index: 1;
        }

        .progress-steps .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .progress-steps .step .circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e5e5e5;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            transition: 0.3s;
        }

        .progress-steps .step .circle.active {
            background: #2874f0;
            box-shadow: 0 0 0 4px rgba(40, 116, 240, 0.15);
        }

        .progress-steps .step .circle.done {
            background: #27ae60;
        }

        .progress-steps .step .circle.cancelled {
            background: #e74c3c;
        }

        .progress-steps .step .step-label {
            font-size: 11px;
            font-weight: 600;
            color: #888;
            margin-top: 8px;
            text-align: center;
            text-transform: capitalize;
        }

        .progress-steps .step .step-label.active {
            color: #2874f0;
        }

        .progress-steps .step .step-label.done {
            color: #27ae60;
        }

        /* ---------- ITEMS ---------- */
        .order-items {
            margin: 20px 0;
        }

        .order-items h3 {
            font-size: 18px;
            color: #222;
            margin-bottom: 12px;
        }

        .order-items .item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-items .item:last-child {
            border-bottom: none;
        }

        .order-items .item img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 6px;
            background: #f8f9fa;
            padding: 4px;
        }

        .order-items .item .item-details {
            flex: 1;
        }

        .order-items .item .item-details .name {
            font-weight: 600;
            color: #222;
            font-size: 14px;
        }

        .order-items .item .item-details .qty {
            color: #888;
            font-size: 13px;
        }

        .order-items .item .item-price {
            font-weight: 700;
            color: #2874f0;
            font-size: 16px;
        }

        /* ---------- STATUS HISTORY ---------- */
        .status-history {
            margin-top: 20px;
            border-top: 1px solid #e5e5e5;
            padding-top: 20px;
        }

        .status-history h3 {
            font-size: 18px;
            color: #222;
            margin-bottom: 12px;
        }

        .status-history .timeline {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .status-history .timeline .event {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 6px 0;
        }

        .status-history .timeline .event .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #2874f0;
            flex-shrink: 0;
        }

        .status-history .timeline .event .dot.done {
            background: #27ae60;
        }

        .status-history .timeline .event .dot.cancelled {
            background: #e74c3c;
        }

        .status-history .timeline .event .info {
            display: flex;
            justify-content: space-between;
            flex: 1;
            font-size: 14px;
            color: #555;
        }

        .status-history .timeline .event .info .time {
            color: #888;
            font-size: 13px;
        }

        /* ---------- BUTTONS ---------- */
        .track-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            flex-wrap: wrap;
        }

        .btn-back {
            padding: 12px 28px;
            background: #f8f9fa;
            color: #555;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .btn-back:hover {
            background: #e5e5e5;
        }

        .btn-cancel-order {
            padding: 12px 28px;
            background: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cancel-order:hover {
            background: #c0392b;
        }

        .btn-cancel-order:disabled {
            background: #e5e5e5;
            color: #888;
            cursor: not-allowed;
        }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 768px) {
            .track-card {
                padding: 20px;
            }

            .order-info-grid {
                grid-template-columns: 1fr;
            }

            .progress-steps {
                flex-wrap: nowrap;
                overflow-x: auto;
                gap: 5px;
                padding: 0;
            }

            .progress-steps .step {
                flex: 0 0 auto;
                min-width: 50px;
            }

            .progress-steps .step .step-label {
                font-size: 9px;
            }

            .progress-steps .step .circle {
                width: 30px;
                height: 30px;
                font-size: 11px;
            }

            .track-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .track-actions {
                flex-direction: column;
            }

            .btn-back,
            .btn-cancel-order {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .track-card {
                padding: 15px;
            }

            .track-header h2 {
                font-size: 20px;
            }

            .order-items .item {
                flex-wrap: wrap;
            }

            .order-items .item img {
                width: 50px;
                height: 50px;
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
        <input type="text" placeholder="Search for Products, Brands and More...">
        <button>SEARCH</button>
    </div>
    <div class="header-icons">
        <a href="dashboard.php"><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($user['name'] ?? 'User'); ?></a>
        <a href="logout.php" style="color:#ffd700;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart</a>
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

<!-- ======== TRACK ORDER CONTENT ======== -->
<div class="track-wrap">
    <div class="track-card">

        <!-- Header -->
        <div class="track-header">
            <h2><i class="fa-regular fa-receipt" style="color:#2874f0;"></i> Order <span>#<?php echo $order_number; ?></span></h2>
            <span class="order-status-badge" style="background:<?php echo $status_colors[$status] ?? '#888'; ?>;">
                <?php echo $status_labels[$status] ?? ucfirst($status); ?>
            </span>
        </div>

        <!-- Order Info -->
        <div class="order-info-grid">
            <div class="order-info-item">
                <div class="label">Order Date</div>
                <div class="value"><?php echo $order_date; ?></div>
            </div>
            <div class="order-info-item">
                <div class="label">Total Amount</div>
                <div class="value">₹<?php echo number_format($total, 2); ?></div>
            </div>
            <div class="order-info-item">
                <div class="label">Payment Status</div>
                <div class="value"><?php echo ucfirst($payment_status); ?></div>
            </div>
            <div class="order-info-item">
                <div class="label">Shipping Address</div>
                <div class="value" style="font-size:14px;"><?php echo $shipping_address; ?></div>
            </div>
        </div>

        <!-- Progress Timeline -->
        <?php if (!$is_cancelled): ?>
        <div class="progress-container">
            <div class="progress-steps">
                <?php foreach ($progress_steps as $index => $step): 
                    $step_status = '';
                    if ($index < $current_step_index) $step_status = 'done';
                    elseif ($index == $current_step_index) $step_status = 'active';
                    $label = $status_labels[$step] ?? ucfirst($step);
                    $circle_icon = '';
                    if ($step_status === 'done') $circle_icon = '<i class="fa-solid fa-check"></i>';
                    else $circle_icon = $index + 1;
                ?>
                <div class="step">
                    <div class="circle <?php echo $step_status; ?>"><?php echo $circle_icon; ?></div>
                    <span class="step-label <?php echo $step_status; ?>"><?php echo $label; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="progress-container" style="text-align:center; padding:15px 0;">
            <p style="color:#e74c3c; font-weight:600; font-size:18px;">
                <i class="fa-solid fa-circle-xmark"></i> This order has been cancelled.
            </p>
        </div>
        <?php endif; ?>

        <!-- Order Items -->
        <div class="order-items">
            <h3><i class="fa-regular fa-box" style="color:#2874f0;"></i> Items</h3>
            <?php foreach ($items as $item): ?>
            <div class="item">
                <img src="<?php echo htmlspecialchars($item['image'] ?? 'https://via.placeholder.com/60'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                <div class="item-details">
                    <div class="name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                    <div class="qty">Qty: <?php echo $item['quantity']; ?></div>
                </div>
                <div class="item-price">₹<?php echo number_format($item['price'], 2); ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Status History -->
        <div class="status-history">
            <h3><i class="fa-regular fa-clock" style="color:#2874f0;"></i> Order Timeline</h3>
            <div class="timeline">
                <?php foreach ($history as $h): 
                    $is_cancelled_status = ($h['status'] === 'cancelled' || $h['status'] === 'refunded');
                    $dot_class = $is_cancelled_status ? 'cancelled' : 'done';
                    $status_label = $status_labels[$h['status']] ?? ucfirst($h['status']);
                ?>
                <div class="event">
                    <div class="dot <?php echo $dot_class; ?>"></div>
                    <div class="info">
                        <span><?php echo $status_label; ?></span>
                        <span class="time"><?php echo date('d M Y, h:i A', strtotime($h['created_at'])); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Actions -->
        <div class="track-actions">
            <a href="my-orders.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Orders</a>
            <?php if (in_array($status, ['pending', 'confirmed']) && !$is_cancelled): ?>
            <form method="POST" action="cancel-order.php" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                <button type="submit" class="btn-cancel-order"><i class="fa-solid fa-ban"></i> Cancel Order</button>
            </form>
            <?php elseif ($status === 'delivered'): ?>
            <form method="POST" action="return-order.php">
                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                <button type="submit" class="btn-cancel-order" style="background:#f39c12;"><i class="fa-solid fa-rotate-left"></i> Return Order</button>
            </form>
            <?php else: ?>
            <button class="btn-cancel-order" disabled><i class="fa-solid fa-ban"></i> Cannot Cancel</button>
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