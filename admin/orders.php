<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$current_page = 'orders';

// ---------- FILTERS & SEARCH ----------
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($status_filter)) {
    $where .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $search_param = "%$search%";
    $where .= " AND (o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Count total orders
$count_sql = "SELECT COUNT(*) AS total 
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              $where";
$stmt = mysqli_prepare($conn, $count_sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$total_row = mysqli_fetch_assoc($count_result);
$total_orders = $total_row['total'];
$total_pages = ceil($total_orders / $limit);

// Fetch orders
$sql = "SELECT o.*, u.name AS customer_name, u.email AS customer_email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        $where 
        ORDER BY o.id DESC 
        LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    mysqli_stmt_bind_param($stmt, $types, ...$params);
} else {
    mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// ---------- STATS ----------
$stats_sql = "SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing,
                SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) AS shipped,
                SUM(CASE WHEN status = 'ontheway' THEN 1 ELSE 0 END) AS ontheway,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
                SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END) AS refunded
              FROM orders";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// ---------- MESSAGE HANDLING ----------
$message = '';
$message_type = '';
if (isset($_GET['updated'])) {
    $message = 'Order status updated successfully!';
    $message_type = 'success';
}
if (isset($_GET['error'])) {
    $message = 'Something went wrong. Please try again.';
    $message_type = 'error';
}

// Status color mapping
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
    <title>Manage Orders - Admin</title>
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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
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

        .right-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .search-form input {
            padding: 8px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            width: 200px;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }

        .search-form input:focus {
            border-color: #2874f0;
            box-shadow: 0 0 0 3px rgba(40, 116, 240, 0.1);
        }

        .search-form select {
            padding: 8px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
        }

        .search-form select:focus {
            border-color: #2874f0;
        }

        .search-form button {
            padding: 8px 18px;
            background: #2874f0;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }

        .search-form button:hover {
            background: #0052cc;
        }

        .btn-clear {
            background: #e74c3c !important;
            padding: 8px 14px;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }

        .btn-clear:hover {
            background: #c0392b !important;
        }

        /* ---------- Stats Row ---------- */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: #fff;
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
            border-left: 4px solid #2874f0;
        }

        .stat-box .number {
            font-size: 22px;
            font-weight: 700;
            color: #222;
        }

        .stat-box .label {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
        }

        .stat-box.pending {
            border-left-color: #f39c12;
        }

        .stat-box.confirmed {
            border-left-color: #3498db;
        }

        .stat-box.processing {
            border-left-color: #9b59b6;
        }

        .stat-box.shipped {
            border-left-color: #1abc9c;
        }

        .stat-box.ontheway {
            border-left-color: #2ecc71;
        }

        .stat-box.delivered {
            border-left-color: #27ae60;
        }

        .stat-box.cancelled {
            border-left-color: #e74c3c;
        }

        .stat-box.refunded {
            border-left-color: #95a5a6;
        }

        /* ---------- Alerts ---------- */
        .alert {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d5f5e3;
            color: #27ae60;
            border-left: 4px solid #27ae60;
        }

        .alert-error {
            background: #fadbd8;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }

        /* ---------- Table ---------- */
        .table-wrap {
            background: #fff;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #eee;
            color: #555;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        tr:hover td {
            background: #f8f9ff;
        }

        .status-badge {
            padding: 4px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            color: #fff;
            display: inline-block;
        }

        .order-total {
            font-weight: 700;
            color: #2874f0;
        }

        .action-btn {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            margin: 0 2px;
            display: inline-block;
            transition: 0.3s;
        }

        .action-btn.view {
            background: #2874f0;
            color: #fff;
        }

        .action-btn.view:hover {
            background: #0052cc;
        }

        .action-btn.delete {
            background: #e74c3c;
            color: #fff;
        }

        .action-btn.delete:hover {
            background: #c0392b;
        }

        .empty-msg {
            text-align: center;
            padding: 40px;
            color: #888;
        }

        .empty-msg i {
            font-size: 40px;
            display: block;
            margin-bottom: 10px;
            color: #ddd;
        }

        /* ---------- Pagination ---------- */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            padding: 20px;
            flex-wrap: wrap;
        }

        .pagination a {
            padding: 6px 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            transition: 0.3s;
        }

        .pagination a.active {
            background: #2874f0;
            color: #fff;
            border-color: #2874f0;
        }

        .pagination a:hover:not(.active) {
            background: #eee;
        }

        /* ---------- Quick Status Update (inline) ---------- */
        .status-form {
            display: inline-block;
        }

        .status-form select {
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
            background: #fff;
        }

        .status-form button {
            padding: 4px 10px;
            border: none;
            border-radius: 4px;
            background: #2874f0;
            color: #fff;
            font-size: 11px;
            cursor: pointer;
        }

        .status-form button:hover {
            background: #0052cc;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 768px) {
            .admin-layout {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                min-height: auto;
            }

            .admin-topbar {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .right-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .search-form {
                flex-wrap: wrap;
            }

            .search-form input,
            .search-form select {
                width: 100%;
            }

            .admin-main {
                padding: 15px;
            }

            .stats-row {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 576px) {
            .admin-topbar h1 {
                font-size: 20px;
            }

            th,
            td {
                padding: 8px 10px;
                font-size: 13px;
            }

            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
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
                <a href="logout.php" class="logout-link">
                    <i class="fa-solid fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-topbar">
                <h1><i class="fa-solid fa-box"></i> Manage Orders</h1>
                <div class="right-actions">
                    <form method="GET" class="search-form">
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo ($status_filter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo ($status_filter == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="processing" <?php echo ($status_filter == 'processing') ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo ($status_filter == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                            <option value="ontheway" <?php echo ($status_filter == 'ontheway') ? 'selected' : ''; ?>>On the Way</option>
                            <option value="delivered" <?php echo ($status_filter == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo ($status_filter == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="refunded" <?php echo ($status_filter == 'refunded') ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                        <input type="text" name="search" placeholder="Search by order #, customer..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fa-solid fa-search"></i></button>
                        <?php if (!empty($status_filter) || !empty($search)): ?>
                            <a href="orders.php" class="btn-clear">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-box">
                    <div class="number"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="label">Total</div>
                </div>
                <div class="stat-box pending">
                    <div class="number"><?php echo $stats['pending'] ?? 0; ?></div>
                    <div class="label">Pending</div>
                </div>
                <div class="stat-box confirmed">
                    <div class="number"><?php echo $stats['confirmed'] ?? 0; ?></div>
                    <div class="label">Confirmed</div>
                </div>
                <div class="stat-box processing">
                    <div class="number"><?php echo $stats['processing'] ?? 0; ?></div>
                    <div class="label">Processing</div>
                </div>
                <div class="stat-box shipped">
                    <div class="number"><?php echo $stats['shipped'] ?? 0; ?></div>
                    <div class="label">Shipped</div>
                </div>
                <div class="stat-box ontheway">
                    <div class="number"><?php echo $stats['ontheway'] ?? 0; ?></div>
                    <div class="label">On Way</div>
                </div>
                <div class="stat-box delivered">
                    <div class="number"><?php echo $stats['delivered'] ?? 0; ?></div>
                    <div class="label">Delivered</div>
                </div>
                <div class="stat-box cancelled">
                    <div class="number"><?php echo $stats['cancelled'] ?? 0; ?></div>
                    <div class="label">Cancelled</div>
                </div>
                <div class="stat-box refunded">
                    <div class="number"><?php echo $stats['refunded'] ?? 0; ?></div>
                    <div class="label">Refunded</div>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['order_number'] ?? '#' . $row['id']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($row['customer_name'] ?? 'N/A'); ?>
                                        <br><small style="color:#888;"><?php echo htmlspecialchars($row['customer_email'] ?? ''); ?></small>
                                    </td>
                                    <td class="order-total">₹<?php echo number_format($row['grand_total'] ?? $row['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge" style="background:<?php echo $status_colors[$row['status']] ?? '#888'; ?>;">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                        <!-- Quick status update form inline -->
                                        <form method="POST" action="update-order.php" class="status-form" style="display:inline-block; margin-left:5px;">
                                            <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                            <select name="status" onchange="this.form.submit()">
                                                <option value="">Change</option>
                                                <option value="pending" <?php echo ($row['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo ($row['status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="processing" <?php echo ($row['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                                <option value="shipped" <?php echo ($row['status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="ontheway" <?php echo ($row['status'] == 'ontheway') ? 'selected' : ''; ?>>On the Way</option>
                                                <option value="delivered" <?php echo ($row['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="cancelled" <?php echo ($row['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                <option value="refunded" <?php echo ($row['status'] == 'refunded') ? 'selected' : ''; ?>>Refunded</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['payment_method'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['order_date'] ?? $row['created_at'])); ?></td>
                                    <td>
                                        <a href="order-detail.php?id=<?php echo $row['id']; ?>" class="action-btn view" title="View Details">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                        <a href="order-tracking.php?id=<?php echo $order['id']; ?>" class="btn-track" style="color:#2874f0;">
                                            <i class="fa-solid fa-location-dot"></i> Track Order
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-msg">
                                    <i class="fa-regular fa-box-open"></i>
                                    No orders found.
                                    <?php if (!empty($search) || !empty($status_filter)): ?>
                                        <br><small>Try adjusting your filters.</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>