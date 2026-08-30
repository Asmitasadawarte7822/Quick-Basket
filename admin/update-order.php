<?php
session_start();
require_once '../config.php';

// ---------- CHECK ADMIN LOGIN ----------
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// ---------- UPDATE ORDER STATUS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $note = mysqli_real_escape_string($conn, $_POST['note'] ?? '');

    // Check if status is valid
    $valid_statuses = ['pending', 'confirmed', 'processing', 'shipped', 'ontheway', 'delivered', 'cancelled', 'refunded'];
    if (!in_array($new_status, $valid_statuses)) {
        $message = 'Invalid status.';
        $message_type = 'error';
    } else {
        // Update order status
        $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $new_status, $order_id);
        if (mysqli_stmt_execute($update_stmt)) {
            // Add to history if table exists
            $check_table = "SHOW TABLES LIKE 'order_status_history'";
            $table_check = mysqli_query($conn, $check_table);
            if (mysqli_num_rows($table_check) > 0) {
                $history_sql = "INSERT INTO order_status_history (order_id, status, note) VALUES (?, ?, ?)";
                $history_stmt = mysqli_prepare($conn, $history_sql);
                mysqli_stmt_bind_param($history_stmt, "iss", $order_id, $new_status, $note);
                mysqli_stmt_execute($history_stmt);
                mysqli_stmt_close($history_stmt);
            }
            $message = 'Order status updated successfully.';
            $message_type = 'success';
        } else {
            $message = 'Error updating order: ' . mysqli_error($conn);
            $message_type = 'error';
        }
        mysqli_stmt_close($update_stmt);
    }
}

// ---------- FETCH ALL ORDERS WITH USER INFO ----------
$orders_sql = "SELECT o.*, u.name AS customer_name, u.email AS customer_email 
               FROM orders o 
               LEFT JOIN users u ON o.user_id = u.id 
               ORDER BY o.id DESC";
$orders_result = mysqli_query($conn, $orders_sql);

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

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Orders – Quick Basket Admin</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ============================================
           ADMIN ORDER UPDATE – WHITE/BLUE/YELLOW
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

        .admin-wrap {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .admin-card {
            background: #fff;
            border-radius: 12px;
            padding: 30px 35px;
            border: 1px solid #e5e5e5;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }

        .admin-card .page-title {
            font-size: 26px;
            font-weight: 700;
            color: #222;
            margin-bottom: 4px;
        }

        .admin-card .page-title span {
            color: #2874f0;
        }

        .admin-card .page-subtitle {
            color: #888;
            font-size: 14px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e5e5;
        }

        /* ---------- ALERT ---------- */
        .alert {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success {
            background: #d5f5e3;
            color: #1a7a3a;
            border: 1px solid #a9dfbf;
        }
        .alert-error {
            background: #fde2e2;
            color: #991b1b;
            border: 1px solid #f5c6c6;
        }

        /* ---------- TABLE ---------- */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        table thead {
            background: #f8f9fa;
        }

        table th {
            padding: 12px 14px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #e5e5e5;
        }

        table td {
            padding: 12px 14px;
            border-bottom: 1px solid #e5e5e5;
            vertical-align: middle;
        }

        table tr:hover {
            background: #fafcff;
        }

        .order-id {
            font-weight: 700;
            color: #2874f0;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 50px;
            color: #fff;
            font-weight: 600;
            font-size: 12px;
            text-transform: capitalize;
        }

        /* ---------- FORM INLINE ---------- */
        .status-form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .status-form select {
            padding: 6px 12px;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            background: #fff;
            font-size: 13px;
            cursor: pointer;
        }

        .status-form select:focus {
            border-color: #2874f0;
            outline: none;
        }

        .status-form input[type="text"] {
            padding: 6px 12px;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            font-size: 13px;
            min-width: 120px;
        }

        .status-form input[type="text"]:focus {
            border-color: #2874f0;
            outline: none;
        }

        .btn-update {
            padding: 6px 18px;
            background: #2874f0;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            font-size: 13px;
        }

        .btn-update:hover {
            background: #1a5bc7;
        }

        .btn-update:disabled {
            background: #e5e5e5;
            color: #888;
            cursor: not-allowed;
        }

        .btn-view {
            padding: 4px 12px;
            background: #f8f9fa;
            color: #2874f0;
            border: 1px solid #2874f0;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: 0.3s;
            display: inline-block;
        }

        .btn-view:hover {
            background: #2874f0;
            color: #fff;
        }

        /* ---------- EMPTY STATE ---------- */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #888;
        }

        .empty-state i {
            font-size: 60px;
            color: #ddd;
            display: block;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            color: #222;
            margin-bottom: 6px;
        }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 768px) {
            .admin-card {
                padding: 20px;
            }

            .status-form {
                flex-direction: column;
                align-items: stretch;
            }

            .status-form select,
            .status-form input[type="text"],
            .btn-update {
                width: 100%;
            }

            table {
                font-size: 13px;
            }

            table th,
            table td {
                padding: 8px 10px;
            }
        }

        @media (max-width: 576px) {
            .admin-card {
                padding: 15px;
            }

            .page-title {
                font-size: 22px;
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
        <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="products.php"><i class="fa-solid fa-box"></i> Products</a>
        <!-- <a href="logout.php" style="color:#ffd700;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a> -->
    </div>
</header>

<!-- ======== CATEGORY NAV ======== -->
<nav class="category-bar">
    <div class="category-bar-inner">
        <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="products.php"><i class="fa-solid fa-box"></i> Products</a>
        <a href="add-product.php"><i class="fa-solid fa-plus"></i> Add Product</a>
        <a href="orders.php" class="active"><i class="fa-solid fa-truck"></i> Orders</a>
        <a href="update-order.php"><i class="fa-solid fa-pen-to-square"></i> Update Order</a>
        <a href="categories.php"><i class="fa-solid fa-tags"></i> Categories</a>
        <a href="sellers.php"><i class="fa-solid fa-store"></i> Sellers</a>
    </div>
</nav>

<!-- ======== MAIN CONTENT ======== -->
<div class="admin-wrap">
    <div class="admin-card">

        <h1 class="page-title"><i class="fa-regular fa-pen-to-square" style="color:#2874f0;"></i> Update <span>Order Status</span></h1>
        <p class="page-subtitle">View all orders and change their status. Add notes for tracking history.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fa-solid <?php echo ($message_type == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($orders_result) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Note</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = mysqli_fetch_assoc($orders_result)): 
                            $order_number = str_pad($order['id'], 6, '0', STR_PAD_LEFT);
                            $status = $order['status'];
                            $status_color = $status_colors[$status] ?? '#888';
                        ?>
                        <tr>
                            <td><span class="order-id">#<?php echo $order_number; ?></span></td>
                            <td>
                                <strong><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></strong>
                                <br><small style="color:#888;"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></small>
                            </td>
                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></td>
                            <td>
                                <span class="status-badge" style="background:<?php echo $status_color; ?>;">
                                    <?php echo $status_labels[$status] ?? ucfirst($status); ?>
                                </span>
                            </td>
                            <form method="POST" action="">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <td>
                                    <input type="text" name="note" placeholder="Add note..." style="width:100%;">
                                </td>
                                <td>
                                    <div class="status-form">
                                        <select name="status">
                                            <?php foreach ($status_labels as $key => $label): ?>
                                                <option value="<?php echo $key; ?>" <?php echo ($key == $status) ? 'selected' : ''; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="update_order" class="btn-update">
                                            <i class="fa-solid fa-check"></i> Update
                                        </button>
                                        <a href="../track-order.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn-view">
                                            <i class="fa-regular fa-eye"></i> View
                                        </a>
                                    </div>
                                </td>
                            </form>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-regular fa-receipt"></i>
                <h3>No orders found</h3>
                <p>There are no orders in the database yet.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- ======== FOOTER ======== -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-box">
            <h3>Quick Basket</h3>
            <p>Admin panel for order management.</p>
        </div>
        <div class="footer-box">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="orders.php">Orders</a></li>
            </ul>
        </div>
        <div class="footer-box">
            <h3>Support</h3>
            <ul>
                <li><a href="#">Contact</a></li>
                <li><a href="#">FAQ</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© 2026 Quick Basket. All Rights Reserved.</p>
    </div>
</footer>

</body>
</html>