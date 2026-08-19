<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch order
$sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$order) {
    header('Location: index.php');
    exit;
}

// ---------- FETCH ORDER ITEMS (JOIN WITH PRODUCTS FOR NAMES) ----------
$items_sql = "SELECT oi.*, p.name as product_name, p.image 
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

// Status colors
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .confirmation-wrap {
            max-width: 600px;
            margin: 50px auto;
            padding: 0 20px;
            text-align: center;
        }
        .confirmation-card {
            background: #fff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .confirmation-card .icon {
            font-size: 80px;
            color: #27ae60;
            display: block;
            margin-bottom: 16px;
        }
        .confirmation-card h2 {
            font-size: 28px;
            color: #222;
            margin-bottom: 8px;
        }
        .confirmation-card p {
            color: #888;
            margin-bottom: 8px;
        }
        .confirmation-card .order-details {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        .confirmation-card .order-details .row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
        }
        .confirmation-card .order-details .row .label {
            color: #888;
        }
        .confirmation-card .order-details .row .value {
            font-weight: 500;
        }
        .confirmation-card .order-items {
            margin: 16px 0;
            text-align: left;
        }
        .confirmation-card .order-items .item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #f5f5f5;
            font-size: 14px;
        }
        .status-badge {
            padding: 2px 12px;
            border-radius: 30px;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
        }
        .btn-continue {
            display: inline-block;
            padding: 12px 36px;
            background: #2874f0;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-continue:hover {
            background: #0052cc;
        }
        .btn-orders {
            display: inline-block;
            padding: 12px 36px;
            background: #ff9f00;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-left: 10px;
            transition: 0.3s;
        }
        .btn-orders:hover {
            background: #e68a00;
        }
        @media (max-width: 576px) {
            .confirmation-card { padding: 20px; }
            .btn-orders { margin-left: 0; margin-top: 10px; }
        }
    </style>
</head>
<body>
    <div class="confirmation-wrap">
        <div class="confirmation-card">
            <span class="icon"><i class="fa-regular fa-circle-check"></i></span>
            <h2>Order Placed Successfully! 🎉</h2>
            <p>Thank you for your order. We'll notify you when it ships.</p>

            <div class="order-details">
                <div class="row">
                    <span class="label">Order #</span>
                    <span class="value">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="row">
                    <span class="label">Order Date</span>
                    <span class="value"><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></span>
                </div>
                <div class="row">
                    <span class="label">Total Amount</span>
                    <span class="value">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="row">
                    <span class="label">Payment Method</span>
                    <span class="value"><?php echo strtoupper(htmlspecialchars($order['payment_method'] ?? 'COD')); ?></span>
                </div>
                <div class="row">
                    <span class="label">Status</span>
                    <span class="value">
                        <span class="status-badge" style="background:<?php echo $status_colors[$order['status']] ?? '#888'; ?>;">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </span>
                </div>
            </div>

            <div class="order-items">
                <h4 style="margin-bottom:8px; color:#555;">Items Ordered</h4>
                <?php foreach ($items as $item): ?>
                    <div class="item">
                        <span><?php echo htmlspecialchars($item['product_name'] ?? 'Product #' . $item['product_id']); ?> (x<?php echo $item['quantity']; ?>)</span>
                        <span>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div>
                <a href="index.php" class="btn-continue"><i class="fa-solid fa-arrow-left"></i> Continue Shopping</a>
                <a href="orders.php" class="btn-orders"><i class="fa-solid fa-box"></i> My Orders</a>
            </div>
        </div>
    </div>
</body>
</html>