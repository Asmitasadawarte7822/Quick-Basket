<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$current_page = 'dashboard';

// ---------- STATS QUERIES (using total_amount) ----------
// Total Products
$product_count = 0;
$prod_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products");
if ($prod_result) {
    $product_count = mysqli_fetch_assoc($prod_result)['total'];
}

// Total Users
$user_count = 0;
$user_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
if ($user_result) {
    $user_count = mysqli_fetch_assoc($user_result)['total'];
}

// Total Orders
$order_count = 0;
$order_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders");
if ($order_result) {
    $order_count = mysqli_fetch_assoc($order_result)['total'];
}

// Total Revenue (delivered orders) – using total_amount
$revenue = 0;
$rev_result = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE status = 'delivered'");
if ($rev_result) {
    $revenue = mysqli_fetch_assoc($rev_result)['total'];
}

// Recent Orders (last 5) – using total_amount
$recent_orders = [];
$recent_sql = "SELECT o.*, u.name AS customer_name 
               FROM orders o 
               LEFT JOIN users u ON o.user_id = u.id 
               ORDER BY o.id DESC 
               LIMIT 5";
$recent_result = mysqli_query($conn, $recent_sql);
if ($recent_result) {
    while ($row = mysqli_fetch_assoc($recent_result)) {
        $recent_orders[] = $row;
    }
}

// Status colors for badges
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
    <title>Admin Dashboard - Quick Basket</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ---------- Global Layout ---------- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        body {
            background: #f1f3f6;
        }
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        /* ---------- Sidebar ---------- */
        .sidebar {
            width: 240px;
            background: #2c3e50;
            color: #fff;
            padding: 20px 0;
            min-height: 100vh;
            flex-shrink: 0;
        }
        .admin-logo {
            font-size: 28px;
            font-weight: 700;
            padding: 0 20px 20px;
            border-bottom: 1px solid #34495e;
            margin-bottom: 20px;
        }
        .admin-logo span {
            color: #ffd700;
        }
        .sidebar nav a {
            display: block;
            padding: 14px 25px;
            color: #ccc;
            text-decoration: none;
            transition: 0.2s;
        }
        .sidebar nav a:hover {
            background: #34495e;
            color: #fff;
        }
        .sidebar nav a.active {
            background: #1a3a5c;
            color: #fff;
            border-left: 3px solid #2874f0;
        }
        .sidebar nav a i {
            margin-right: 10px;
            width: 20px;
        }
        .sidebar nav a.logout-link {
            margin-top: 20px;
            border-top: 1px solid #34495e;
            color: #e74c3c;
        }
        .sidebar nav a.logout-link:hover {
            background: #e74c3c;
            color: #fff;
        }

        /* ---------- Main Content ---------- */
        .admin-main {
            flex: 1;
            padding: 25px;
        }
        .admin-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            flex-wrap: wrap;
            gap: 10px;
        }
        .admin-topbar h1 {
            font-size: 24px;
            color: #222;
        }
        .admin-topbar h1 i {
            color: #2874f0;
        }
        .admin-topbar .welcome {
            color: #555;
        }
        .admin-topbar .welcome strong {
            color: #2874f0;
        }

        /* ---------- Stats Cards ---------- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            padding: 20px 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 4px solid #2874f0;
            transition: 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        .stat-card .stat-label {
            font-size: 13px;
            color: #888;
            text-transform: uppercase;
            font-weight: 600;
        }
        .stat-card .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #222;
            margin-top: 4px;
        }
        .stat-card .stat-icon {
            float: right;
            font-size: 28px;
            color: #2874f0;
            opacity: 0.3;
        }
        .stat-card.blue { border-left-color: #2874f0; }
        .stat-card.green { border-left-color: #27ae60; }
        .stat-card.orange { border-left-color: #e67e22; }
        .stat-card.purple { border-left-color: #8e44ad; }

        /* ---------- Quick Actions ---------- */
        .quick-actions {
            background: #fff;
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 25px;
        }
        .quick-actions h3 {
            font-size: 18px;
            color: #222;
            margin-bottom: 15px;
        }
        .quick-actions .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
        }
        .quick-actions .action-grid a {
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            text-align: center;
            transition: 0.3s;
            border: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .quick-actions .action-grid a:hover {
            background: #2874f0;
            color: #fff;
            border-color: #2874f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40,116,240,0.2);
        }
        .quick-actions .action-grid a i {
            font-size: 18px;
        }

        /* ---------- Recent Orders Table ---------- */
        .recent-orders {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 20px 25px;
        }
        .recent-orders h3 {
            font-size: 18px;
            color: #222;
            margin-bottom: 15px;
        }
        .recent-orders .table-wrap {
            overflow-x: auto;
        }
        .recent-orders table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .recent-orders th {
            background: #f8f9fa;
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #eee;
            color: #555;
        }
        .recent-orders td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        .recent-orders tr:hover td {
            background: #f8f9ff;
        }
        .status-badge {
            padding: 3px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            color: #fff;
            display: inline-block;
        }
        .order-total {
            font-weight: 700;
            color: #2874f0;
        }
        .view-all-link {
            float: right;
            color: #2874f0;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: 0.3s;
        }
        .view-all-link:hover {
            color: #0052cc;
            text-decoration: underline;
        }
        .empty-msg {
            text-align: center;
            padding: 20px;
            color: #888;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 768px) {
            .admin-layout { flex-direction: column; }
            .sidebar { width: 100%; min-height: auto; }
            .admin-topbar { flex-direction: column; align-items: stretch; text-align: center; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .quick-actions .action-grid { grid-template-columns: 1fr 1fr; }
            .admin-main { padding: 15px; }
        }
        @media (max-width: 576px) {
            .stats-grid { grid-template-columns: 1fr; }
            .quick-actions .action-grid { grid-template-columns: 1fr; }
            .admin-topbar h1 { font-size: 20px; }
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="admin-logo">Quick<span>Basket</span></div>
        <nav>
            <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="products.php" class="<?php echo ($current_page == 'products') ? 'active' : ''; ?>">
                <i class="fa-solid fa-cube"></i> Products
            </a>
            <a href="categories.php" class="<?php echo ($current_page == 'categories') ? 'active' : ''; ?>">
                <i class="fa-solid fa-tags"></i> Categories
            </a>
            <a href="users.php" class="<?php echo ($current_page == 'users') ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i> Users
            </a>
            <a href="orders.php" class="<?php echo ($current_page == 'orders') ? 'active' : ''; ?>">
                <i class="fa-solid fa-box"></i> Orders
            </a>
            <a href="sellers.php" class="<?php echo ($current_page == 'sellers') ? 'active' : ''; ?>">
                <i class="fa-solid fa-store"></i> Sellers
            </a>
            <a href="logout.php" class="logout-link" onclick="return confirm('Are you sure you want to logout?')">
                <i class="fa-solid fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-topbar">
            <h1><i class="fa-solid fa-gauge-high"></i> Dashboard</h1>
            <span class="welcome">Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></strong></span>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fa-solid fa-cube"></i></div>
                <div class="stat-label">Total Products</div>
                <div class="stat-number"><?php echo number_format($product_count); ?></div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                <div class="stat-label">Total Users</div>
                <div class="stat-number"><?php echo number_format($user_count); ?></div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon"><i class="fa-solid fa-box"></i></div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-number"><?php echo number_format($order_count); ?></div>
            </div>
            <div class="stat-card purple">
                <div class="stat-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                <div class="stat-label">Revenue</div>
                <div class="stat-number">₹<?php echo number_format($revenue, 2); ?></div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3><i class="fa-regular fa-clock"></i> Quick Actions</h3>
            <div class="action-grid">
                <a href="add-product.php"><i class="fa-solid fa-plus"></i> Add Product</a>
                <a href="products.php"><i class="fa-solid fa-list"></i> Manage Products</a>
                <a href="users.php"><i class="fa-solid fa-users"></i> Manage Users</a>
                <a href="orders.php"><i class="fa-solid fa-box"></i> Manage Orders</a>
                <a href="categories.php"><i class="fa-solid fa-tags"></i> Manage Categories</a>
                <a href="sellers.php"><i class="fa-solid fa-store"></i> Manage Sellers</a>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="recent-orders">
            <h3>
                <i class="fa-regular fa-clock"></i> Recent Orders
                <a href="orders.php" class="view-all-link">View All →</a>
            </h3>
            <div class="table-wrap">
                <?php if (count($recent_orders) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($order['order_number'] ?? '#' . $order['id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                                    <td class="order-total">₹<?php echo number_format($order['total_amount'] ?? $order['grand_total'] ?? 0, 2); ?></td>
                                    <td>
                                        <span class="status-badge" style="background:<?php echo $status_colors[$order['status']] ?? '#888'; ?>;">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($order['order_date'] ?? $order['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-msg">No recent orders found.</div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>