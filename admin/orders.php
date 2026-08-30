<?php
session_start();
require_once '../config.php';

// ---------- CHECK ADMIN LOGIN ----------
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// ---------- FETCH ALL ORDERS ----------
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

$payment_methods = [
    'cod' => 'Cash on Delivery',
    'card' => 'Card Payment',
    'upi' => 'UPI',
    'netbanking' => 'Net Banking',
    'wallet' => 'Wallet'
];

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders – Quick Basket Admin</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ============================================
           ORDERS DASHBOARD – SIDEBAR + RIGHT PANEL
           WHITE · BLUE · YELLOW
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
            height: 100vh;
            overflow: hidden;
        }

        /* ---------- HEADER ---------- */
        .top-header {
            background: #2874f0;
            padding: 12px 4%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo h1 {
            color: #fff;
            font-size: 30px;
            font-weight: 700;
        }

        .logo span {
            color: #ffd700;
        }

        .search-box {
            flex: 1;
            display: flex;
            max-width: 500px;
            background: #fff;
            border-radius: 4px;
            overflow: hidden;
        }

        .search-box input {
            width: 100%;
            padding: 10px 16px;
            border: none;
            outline: none;
            font-size: 14px;
        }

        .search-box button {
            border: none;
            padding: 10px 20px;
            background: #ffd700;
            font-weight: 700;
            cursor: pointer;
        }

        .header-icons {
            display: flex;
            gap: 18px;
            align-items: center;
        }

        .header-icons a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: 0.3s;
        }

        .header-icons a:hover {
            color: #ffd700;
        }

        .header-icons i {
            margin-right: 6px;
        }

        /* ---------- CATEGORY NAV ---------- */
        .category-nav {
            background: #fff;
            border-bottom: 1px solid #e5e5e5;
            padding: 0;
            overflow-x: auto;
        }

        .category-nav-inner {
            max-width: 100%;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            gap: 4px;
            min-height: 44px;
            align-items: center;
        }

        .category-nav-inner a {
            text-decoration: none;
            color: #555;
            font-weight: 500;
            padding: 8px 14px;
            font-size: 13px;
            border-bottom: 2px solid transparent;
            transition: 0.3s;
            white-space: nowrap;
        }

        .category-nav-inner a:hover {
            color: #2874f0;
            border-bottom-color: #ffd700;
        }

        .category-nav-inner a.active {
            color: #2874f0;
            border-bottom-color: #2874f0;
            font-weight: 600;
        }

        /* ============================================
           MAIN LAYOUT – SIDEBAR + RIGHT PANEL
           ============================================ */
        .orders-dashboard {
            display: flex;
            height: calc(100vh - 120px);
            gap: 0;
        }

        /* ---------- LEFT SIDEBAR ---------- */
        .orders-sidebar {
            width: 380px;
            min-width: 380px;
            background: #fff;
            border-right: 1px solid #e5e5e5;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .orders-sidebar .sidebar-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e5e5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .orders-sidebar .sidebar-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #222;
        }

        .orders-sidebar .sidebar-header h3 span {
            color: #2874f0;
        }

        .orders-sidebar .sidebar-header .count {
            background: #e6f0ff;
            color: #2874f0;
            padding: 2px 12px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
        }

        /* ---------- ORDER LIST (scrollable) ---------- */
        .order-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px 0;
        }

        .order-list::-webkit-scrollbar {
            width: 6px;
        }

        .order-list::-webkit-scrollbar-thumb {
            background: #2874f0;
            border-radius: 4px;
        }

        .order-item {
            padding: 12px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 3px solid transparent;
        }

        .order-item:hover {
            background: #f8f9fa;
        }

        .order-item.active {
            background: #e6f0ff;
            border-left-color: #2874f0;
        }

        .order-item .order-info .order-id {
            font-weight: 700;
            color: #2874f0;
            font-size: 14px;
        }

        .order-item .order-info .customer {
            font-size: 13px;
            color: #555;
        }

        .order-item .order-info .date {
            font-size: 12px;
            color: #888;
        }

        .order-item .order-right {
            text-align: right;
        }

        .order-item .order-right .total {
            font-weight: 700;
            color: #222;
            font-size: 15px;
        }

        .order-item .order-right .status-badge {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 50px;
            color: #fff;
            font-weight: 600;
            font-size: 10px;
            text-transform: capitalize;
            margin-top: 2px;
        }

        /* ---------- RIGHT PANEL ---------- */
        .orders-panel {
            flex: 1;
            background: #f8f9fa;
            padding: 30px 35px;
            overflow-y: auto;
            transition: 0.3s;
        }

        .orders-panel::-webkit-scrollbar {
            width: 6px;
        }

        .orders-panel::-webkit-scrollbar-thumb {
            background: #2874f0;
            border-radius: 4px;
        }

        .panel-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #bbb;
            text-align: center;
        }

        .panel-placeholder i {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 16px;
        }

        .panel-placeholder h3 {
            color: #888;
            font-size: 22px;
            font-weight: 600;
        }

        .panel-placeholder p {
            color: #aaa;
            font-size: 15px;
        }

        /* ---------- ORDER DETAIL CARD ---------- */
        .order-detail-card {
            background: #fff;
            border-radius: 12px;
            padding: 25px 30px;
            border: 1px solid #e5e5e5;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .order-detail-card .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e5e5;
        }

        .order-detail-card .detail-header h2 {
            font-size: 22px;
            font-weight: 700;
            color: #222;
        }

        .order-detail-card .detail-header h2 span {
            color: #2874f0;
        }

        .order-detail-card .detail-header .status-badge {
            padding: 6px 18px;
            border-radius: 50px;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
        }

        /* ---------- INFO GRID ---------- */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 20px;
        }

        .detail-item {
            background: #f8f9fa;
            padding: 10px 14px;
            border-radius: 8px;
            border-left: 3px solid #2874f0;
        }

        .detail-item .label {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
            font-weight: 600;
        }

        .detail-item .value {
            font-size: 15px;
            font-weight: 500;
            color: #222;
            margin-top: 2px;
        }

        /* ---------- ITEMS LIST ---------- */
        .detail-items {
            margin: 16px 0;
            border-top: 1px solid #e5e5e5;
            padding-top: 16px;
        }

        .detail-items h4 {
            font-size: 15px;
            color: #222;
            margin-bottom: 10px;
        }

        .detail-items .item-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-items .item-row:last-child {
            border-bottom: none;
        }

        .detail-items .item-row img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            background: #f8f9fa;
            border-radius: 6px;
            padding: 4px;
        }

        .detail-items .item-row .item-name {
            flex: 1;
            font-weight: 500;
            font-size: 14px;
        }

        .detail-items .item-row .item-price {
            font-weight: 700;
            color: #2874f0;
            font-size: 14px;
        }

        /* ---------- STATUS HISTORY ---------- */
        .detail-history {
            border-top: 1px solid #e5e5e5;
            padding-top: 16px;
        }

        .detail-history h4 {
            font-size: 15px;
            color: #222;
            margin-bottom: 10px;
        }

        .detail-history .history-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 4px 0;
        }

        .detail-history .history-item .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #2874f0;
            flex-shrink: 0;
        }

        .detail-history .history-item .dot.done {
            background: #27ae60;
        }

        .detail-history .history-item .dot.cancelled {
            background: #e74c3c;
        }

        .detail-history .history-item .h-info {
            display: flex;
            justify-content: space-between;
            flex: 1;
            font-size: 13px;
            color: #555;
        }

        .detail-history .history-item .h-info .h-time {
            color: #888;
            font-size: 12px;
        }

        /* ---------- UPDATE FORM ---------- */
        .update-form {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid #e5e5e5;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .update-form select {
            padding: 8px 14px;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
            min-width: 150px;
        }

        .update-form select:focus {
            border-color: #2874f0;
            outline: none;
        }

        .update-form input[type="text"] {
            padding: 8px 14px;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            font-size: 14px;
            flex: 1;
            min-width: 150px;
        }

        .update-form input[type="text"]:focus {
            border-color: #2874f0;
            outline: none;
        }

        .btn-update {
            padding: 8px 24px;
            background: #2874f0;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-update:hover {
            background: #1a5bc7;
        }

        .btn-update:disabled {
            background: #e5e5e5;
            color: #888;
            cursor: not-allowed;
        }

        /* ---------- TOAST NOTIFICATION ---------- */
        .toast {
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 14px 24px;
            border-radius: 8px;
            color: #fff;
            font-weight: 600;
            z-index: 999;
            animation: slideInRight 0.4s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            max-width: 400px;
        }

        .toast-success {
            background: #27ae60;
        }

        .toast-error {
            background: #e74c3c;
        }

        .toast-info {
            background: #2874f0;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 992px) {
            .orders-sidebar {
                width: 320px;
                min-width: 320px;
            }

            .orders-panel {
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .orders-dashboard {
                flex-direction: column;
                height: auto;
            }

            .orders-sidebar {
                width: 100%;
                min-width: 100%;
                max-height: 400px;
                border-right: none;
                border-bottom: 1px solid #e5e5e5;
            }

            .orders-panel {
                padding: 16px;
                min-height: 400px;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .update-form {
                flex-direction: column;
            }

            .update-form select,
            .update-form input[type="text"],
            .btn-update {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .top-header {
                flex-direction: column;
                padding: 10px 3%;
            }

            .search-box {
                width: 100%;
                max-width: 100%;
            }

            .header-icons {
                flex-wrap: wrap;
                justify-content: center;
                gap: 12px;
            }

            .orders-panel {
                padding: 12px;
            }

            .order-detail-card {
                padding: 16px;
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
            <input type="text" placeholder="Search orders...">
            <button><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
        <div class="header-icons">
            <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="products.php"><i class="fa-solid fa-box"></i> Products</a>
            <a href="logout.php" style="color:#ffd700;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <!-- ======== CATEGORY NAV ======== -->
    <nav class="category-nav">
        <div class="category-nav-inner">
            <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="products.php"><i class="fa-solid fa-box"></i> Products</a>
            <a href="add-product.php"><i class="fa-solid fa-plus"></i> Add Product</a>
            <a href="orders.php" class="active"><i class="fa-solid fa-truck"></i> Orders</a>
            <a href="categories.php"><i class="fa-solid fa-tags"></i> Categories</a>
            <a href="sellers.php"><i class="fa-solid fa-store"></i> Sellers</a>
        </div>
    </nav>

    <!-- ======== ORDERS DASHBOARD ======== -->
    <div class="orders-dashboard">

        <!-- LEFT: Sidebar -->
        <div class="orders-sidebar">
            <div class="sidebar-header">
                <h3><i class="fa-regular fa-receipt" style="color:#2874f0;"></i> <span>Orders</span></h3>
                <span class="count"><?php echo mysqli_num_rows($orders_result); ?></span>
            </div>
            <div class="order-list" id="orderList">
                <?php if (mysqli_num_rows($orders_result) > 0): ?>
                    <?php while ($order = mysqli_fetch_assoc($orders_result)):
                        $order_number = str_pad($order['id'], 6, '0', STR_PAD_LEFT);
                        $status_color = $status_colors[$order['status']] ?? '#888';
                        $status_label = $status_labels[$order['status']] ?? ucfirst($order['status']);
                    ?>
                        <div class="order-item" data-order-id="<?php echo $order['id']; ?>" onclick="loadOrder(<?php echo $order['id']; ?>, this)">
                            <div class="order-info">
                                <div class="order-id">#<?php echo $order_number; ?></div>
                                <div class="customer"><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></div>
                                <div class="date"><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></div>
                            </div>
                            <div class="order-right">
                                <div class="total">₹<?php echo number_format($order['total_amount'], 2); ?></div>
                                <span class="status-badge" style="background:<?php echo $status_color; ?>;"><?php echo $status_label; ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align:center; padding:40px 20px; color:#888;">
                        <i class="fa-regular fa-receipt" style="font-size:40px; display:block; margin-bottom:10px;"></i>
                        No orders found.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: Panel -->
        <div class="orders-panel" id="ordersPanel">
            <!-- Placeholder -->
            <div class="panel-placeholder" id="panelPlaceholder">
                <i class="fa-regular fa-hand-pointer"></i>
                <h3>Select an Order</h3>
                <p>Click on any order from the left sidebar to view details and update status.</p>
            </div>

            <!-- Order Detail (hidden by default) -->
            <div id="orderDetail" style="display:none;">
                <div class="order-detail-card" id="orderDetailCard">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- ======== FOOTER ======== -->
    <footer class="footer" style="background:#172337; color:#fff; padding:15px 0 5px;">
        <div style="max-width:1200px; margin:0 auto; padding:0 20px; text-align:center; color:#ccc; font-size:13px;">
            <p>© 2026 Quick Basket. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        // ---------- LOAD ORDER DETAILS ----------
        function loadOrder(orderId, element) {
            // Highlight clicked order
            document.querySelectorAll('.order-item').forEach(el => el.classList.remove('active'));
            if (element) element.classList.add('active');

            // Show loading state
            const detailContainer = document.getElementById('orderDetailCard');
            document.getElementById('panelPlaceholder').style.display = 'none';
            document.getElementById('orderDetail').style.display = 'block';
            detailContainer.innerHTML = '<div style="text-align:center; padding:40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:40px; color:#2874f0;"></i><p style="margin-top:12px; color:#888;">Loading order details...</p></div>';

            // Fetch order details via AJAX
            fetch('get-order-details.php?id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderOrderDetail(data.order);
                    } else {
                        detailContainer.innerHTML = '<div style="text-align:center; padding:40px; color:#e74c3c;"><i class="fa-solid fa-circle-exclamation" style="font-size:40px;"></i><p>Error loading order.</p></div>';
                    }
                })
                .catch(error => {
                    detailContainer.innerHTML = '<div style="text-align:center; padding:40px; color:#e74c3c;"><i class="fa-solid fa-circle-exclamation" style="font-size:40px;"></i><p>Error loading order.</p></div>';
                });
        }

        // ---------- RENDER ORDER DETAIL ----------
        function renderOrderDetail(order) {
            const container = document.getElementById('orderDetailCard');

            const statusColors = {
                'pending': '#f39c12',
                'confirmed': '#3498db',
                'processing': '#9b59b6',
                'shipped': '#1abc9c',
                'ontheway': '#2ecc71',
                'delivered': '#27ae60',
                'cancelled': '#e74c3c',
                'refunded': '#95a5a6'
            };

            const statusLabels = {
                'pending': 'Pending',
                'confirmed': 'Confirmed',
                'processing': 'Processing',
                'shipped': 'Shipped',
                'ontheway': 'On The Way',
                'delivered': 'Delivered',
                'cancelled': 'Cancelled',
                'refunded': 'Refunded'
            };

            const paymentMethods = {
                'cod': 'Cash on Delivery',
                'card': 'Card Payment',
                'upi': 'UPI',
                'netbanking': 'Net Banking',
                'wallet': 'Wallet'
            };

            const statusColor = statusColors[order.status] || '#888';
            const statusLabel = statusLabels[order.status] || order.status;
            const paymentMethod = paymentMethods[order.payment_method] || order.payment_method;

            // Build items HTML
            let itemsHtml = '';
            if (order.items && order.items.length > 0) {
                order.items.forEach(item => {
                    itemsHtml += `
                    <div class="item-row">
                        <img src="${item.image || 'https://via.placeholder.com/50'}" alt="${item.product_name}">
                        <div class="item-name">${item.product_name} <span style="color:#888; font-weight:400;">x${item.quantity}</span></div>
                        <div class="item-price">₹${parseFloat(item.price).toFixed(2)}</div>
                    </div>
                `;
                });
            }

            // Build history HTML
            let historyHtml = '';
            if (order.history && order.history.length > 0) {
                order.history.forEach(h => {
                    const isCancelled = (h.status === 'cancelled' || h.status === 'refunded');
                    const dotClass = isCancelled ? 'cancelled' : 'done';
                    const label = statusLabels[h.status] || h.status;
                    historyHtml += `
                    <div class="history-item">
                        <div class="dot ${dotClass}"></div>
                        <div class="h-info">
                            <span>${label}</span>
                            <span class="h-time">${new Date(h.created_at).toLocaleString('en-IN', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })}</span>
                        </div>
                    </div>
                `;
                });
            }

            // Build status options (all statuses)
            let statusOptions = '';
            for (const [key, label] of Object.entries(statusLabels)) {
                const selected = (key === order.status) ? 'selected' : '';
                statusOptions += `<option value="${key}" ${selected}>${label}</option>`;
            }

            container.innerHTML = `
            <div class="detail-header">
                <h2>Order <span>#${String(order.id).padStart(6, '0')}</span></h2>
                <span class="status-badge" style="background:${statusColor};">${statusLabel}</span>
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <div class="label">Order Date</div>
                    <div class="value">${new Date(order.order_date).toLocaleString('en-IN', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Total Amount</div>
                    <div class="value">₹${parseFloat(order.total_amount).toFixed(2)}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Customer</div>
                    <div class="value">${order.customer_name || 'Guest'}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Payment</div>
                    <div class="value">${paymentMethod} <small style="color:#888;">(${order.payment_status || 'pending'})</small></div>
                </div>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <div class="label">Shipping Address</div>
                    <div class="value" style="font-size:14px;">${order.shipping_address || 'Not provided'}</div>
                </div>
            </div>

            ${itemsHtml ? `
            <div class="detail-items">
                <h4><i class="fa-regular fa-box" style="color:#2874f0;"></i> Items</h4>
                ${itemsHtml}
            </div>` : ''}

            ${historyHtml ? `
            <div class="detail-history">
                <h4><i class="fa-regular fa-clock" style="color:#2874f0;"></i> Order Timeline</h4>
                ${historyHtml}
            </div>` : ''}

            <form class="update-form" onsubmit="updateOrder(event, ${order.id})">
                <select name="status" id="statusSelect_${order.id}">
                    ${statusOptions}
                </select>
                <input type="text" name="note" placeholder="Add a note (optional)">
                <button type="submit" class="btn-update"><i class="fa-solid fa-check"></i> Update Status</button>
            </form>
        `;
        }

        // ---------- UPDATE ORDER STATUS (with Toast) ----------
        function updateOrder(event, orderId) {
            event.preventDefault();

            const form = event.target;
            const status = form.querySelector('select[name="status"]').value;
            const note = form.querySelector('input[name="note"]').value;
            const button = form.querySelector('.btn-update');
            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';

            fetch('update-order-ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `order_id=${orderId}&status=${status}&note=${encodeURIComponent(note)}`
                })
                .then(response => response.json())
                .then(data => {
                    button.disabled = false;
                    button.innerHTML = '<i class="fa-solid fa-check"></i> Update Status';

                    if (data.success) {
                        let message = data.message;
                        if (data.email_sent) {
                            message += ' 📧 Email sent!';
                        } else if (data.email_sent === false && data.email_error) {
                            message += ' ⚠️ Email failed: ' + data.email_error;
                        }
                        showToast(message, 'success');

                        // Update sidebar status badge
                        const sidebarItem = document.querySelector(`.order-item[data-order-id="${orderId}"]`);
                        if (sidebarItem) {
                            const badge = sidebarItem.querySelector('.status-badge');
                            const statusColors = {
                                'pending': '#f39c12',
                                'confirmed': '#3498db',
                                'processing': '#9b59b6',
                                'shipped': '#1abc9c',
                                'ontheway': '#2ecc71',
                                'delivered': '#27ae60',
                                'cancelled': '#e74c3c',
                                'refunded': '#95a5a6'
                            };
                            const statusLabels = {
                                'pending': 'Pending',
                                'confirmed': 'Confirmed',
                                'processing': 'Processing',
                                'shipped': 'Shipped',
                                'ontheway': 'On The Way',
                                'delivered': 'Delivered',
                                'cancelled': 'Cancelled',
                                'refunded': 'Refunded'
                            };
                            badge.style.background = statusColors[status] || '#888';
                            badge.textContent = statusLabels[status] || status;
                        }

                        // Reload order details
                        loadOrder(orderId, document.querySelector(`.order-item[data-order-id="${orderId}"]`));
                    } else {
                        showToast(data.message || 'Error updating order', 'error');
                    }
                })
                .catch(error => {
                    button.disabled = false;
                    button.innerHTML = '<i class="fa-solid fa-check"></i> Update Status';
                    showToast('Error updating order. Please try again.', 'error');
                });
        }
        // ---------- TOAST NOTIFICATION ----------
        function showToast(message, type = 'info') {
            const existingToast = document.querySelector('.toast');
            if (existingToast) existingToast.remove();

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.5s';
                setTimeout(() => toast.remove(), 500);
            }, 5000);
        }

        // Load first order by default if any
        document.addEventListener('DOMContentLoaded', function() {
            const firstOrder = document.querySelector('.order-item');
            if (firstOrder) {
                firstOrder.click();
            }
        });
    </script>

</body>

</html>