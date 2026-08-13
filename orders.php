<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ----------------------------
// Sample order data (replace with database queries later)
// ----------------------------
$orders = [
    [
        'id' => 1001,
        'date' => '2025-01-16',
        'total' => 264,
        'status' => 'Delivered',
        'items' => [
            [
                'name' => 'VANESA Tingle Skin Friendly Perfume Body...',
                'price' => 264,
                'image' => 'https://via.placeholder.com/100/2874f0/fff?text=Perfume'
            ]
        ]
    ],
    [
        'id' => 1002,
        'date' => '2024-11-24',
        'total' => 11558,
        'status' => 'Delivered',
        'items' => [
            [
                'name' => 'SAMSUNG Galaxy A14 5G (Dark Red, 128 GB)',
                'price' => 11558,
                'image' => 'https://via.placeholder.com/100/2874f0/fff?text=Samsung'
            ]
        ]
    ],
    [
        'id' => 1003,
        'date' => '2024-01-01',
        'total' => 15999,
        'status' => 'Delivered',
        'items' => [
            [
                'name' => 'MOTOROLA g54 5G (Mint Green, 256 GB)',
                'price' => 15999,
                'image' => 'https://via.placeholder.com/100/2874f0/fff?text=Motorola'
            ]
        ]
    ],
    [
        'id' => 1004,
        'date' => '2024-01-01',
        'total' => 0,
        'status' => 'Delivered',
        'items' => [
            [
                'name' => 'Spotify Premium - 12M at Rs 699',
                'price' => 0,
                'image' => 'https://via.placeholder.com/100/2874f0/fff?text=Spotify'
            ]
        ]
    ],
    [
        'id' => 1005,
        'date' => '2024-01-01',
        'total' => 15999,
        'status' => 'Refunded',
        'items' => [
            [
                'name' => 'MOTOROLA g54 5G (Mint Green, 256 GB)',
                'price' => 15999,
                'image' => 'https://via.placeholder.com/100/2874f0/fff?text=Motorola'
            ]
        ]
    ]
];

// ----------------------------
// Filter logic (status & time)
// ----------------------------
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$time_filter   = isset($_GET['time']) ? $_GET['time'] : 'all';
$search_query  = isset($_GET['search']) ? trim($_GET['search']) : '';

// Apply filters to the orders array (for demo)
$filtered_orders = array_filter($orders, function($order) use ($status_filter, $time_filter, $search_query) {
    // Status filter
    if ($status_filter !== 'all' && strtolower($order['status']) !== strtolower($status_filter)) {
        return false;
    }
    // Time filter (example: last 30 days, 2024, 2023, older)
    $order_year = date('Y', strtotime($order['date']));
    if ($time_filter === 'last30') {
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
        if ($order['date'] < $thirty_days_ago) return false;
    } elseif ($time_filter === '2024') {
        if ($order_year != 2024) return false;
    } elseif ($time_filter === '2023') {
        if ($order_year != 2023) return false;
    } elseif ($time_filter === 'older') {
        if ($order_year >= 2024) return false;
    }
    // Search filter
    if (!empty($search_query)) {
        $found = false;
        foreach ($order['items'] as $item) {
            if (stripos($item['name'], $search_query) !== false) {
                $found = true;
                break;
            }
        }
        if (!$found) return false;
    }
    return true;
});

// Sort by date (newest first)
usort($filtered_orders, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
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
        /* -------- Orders Layout -------- */
        .orders-wrap {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
            display: flex;
            gap: 30px;
        }

        /* -------- Sidebar (Filters) -------- */
        .orders-sidebar {
            flex: 0 0 220px;
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .orders-sidebar h3 {
            font-size: 16px;
            color: #222;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .filter-group {
            margin-bottom: 20px;
        }

        .filter-group h4 {
            font-size: 14px;
            color: #555;
            margin-bottom: 8px;
        }

        .filter-group ul {
            list-style: none;
            padding: 0;
        }

        .filter-group ul li {
            padding: 6px 0;
            font-size: 14px;
            color: #333;
            cursor: pointer;
        }

        .filter-group ul li a {
            text-decoration: none;
            color: #333;
            display: block;
        }

        .filter-group ul li a:hover {
            color: #2874f0;
        }

        .filter-group ul li.active a {
            color: #2874f0;
            font-weight: 600;
        }

        /* -------- Main Content -------- */
        .orders-main {
            flex: 1;
        }

        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
            background: #fff;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .orders-header h2 {
            font-size: 22px;
            color: #222;
        }

        .orders-header .search-box-mini {
            display: flex;
            gap: 5px;
        }

        .orders-header .search-box-mini input {
            padding: 8px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            outline: none;
            font-size: 14px;
            width: 220px;
        }

        .orders-header .search-box-mini button {
            padding: 8px 18px;
            background: #2874f0;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .orders-header .search-box-mini button:hover {
            background: #0052cc;
        }

        /* -------- Order Card -------- */
        .order-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .order-card .order-summary {
            display: flex;
            justify-content: space-between;
            padding: 12px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            color: #555;
        }

        .order-card .order-summary .order-id {
            font-weight: 600;
        }

        .order-card .order-summary .order-status {
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-delivered { color: #27ae60; }
        .status-refunded { color: #e67e22; }
        .status-cancelled { color: #e74c3c; }
        .status-ontheway { color: #2874f0; }

        .order-card .order-item {
            display: flex;
            padding: 18px 20px;
            gap: 20px;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-card .order-item:last-child {
            border-bottom: none;
        }

        .order-card .order-item img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
        }

        .order-card .order-item .item-details {
            flex: 1;
        }

        .order-card .order-item .item-details h4 {
            font-size: 16px;
            color: #222;
            margin-bottom: 4px;
        }

        .order-card .order-item .item-details .item-price {
            font-size: 18px;
            font-weight: 600;
            color: #2874f0;
        }

        .order-card .order-item .item-details .delivery-info {
            font-size: 14px;
            color: #777;
            margin-top: 4px;
        }

        .order-card .order-item .item-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }

        .order-card .order-item .item-actions .rate-btn {
            background: transparent;
            border: 1px solid #2874f0;
            color: #2874f0;
            padding: 6px 16px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .order-card .order-item .item-actions .rate-btn:hover {
            background: #2874f0;
            color: #fff;
        }

        .order-card .order-item .item-actions .delivered-badge {
            background: #d5f5e3;
            color: #27ae60;
            padding: 2px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        /* -------- Empty state -------- */
        .empty-orders {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .empty-orders i {
            font-size: 60px;
            color: #ddd;
        }
        .empty-orders h3 {
            margin: 15px 0 10px;
            color: #222;
        }
        .empty-orders p {
            color: #888;
        }

        /* -------- Responsive -------- */
        @media (max-width: 768px) {
            .orders-wrap {
                flex-direction: column;
                padding: 0 10px;
            }
            .orders-sidebar {
                flex: auto;
                position: static;
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                padding: 15px;
            }
            .orders-sidebar .filter-group {
                flex: 1;
                min-width: 120px;
            }
            .orders-header {
                flex-direction: column;
                align-items: stretch;
            }
            .orders-header .search-box-mini {
                width: 100%;
            }
            .orders-header .search-box-mini input {
                width: 100%;
            }
            .order-card .order-item {
                flex-direction: column;
                align-items: stretch;
            }
            .order-card .order-item .item-actions {
                align-items: stretch;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>

    <!-- Header (reuse your existing header) -->
    <header class="top-header" style="background:#2874f0; padding:14px 4%; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
        <div class="logo">
            <h1 style="color:#fff; font-size:34px;">Quick<span style="color:#ffd700;">Basket</span></h1>
        </div>
        <div style="color:#fff; display:flex; gap:20px;">
            <a href="index.php" style="color:#fff; text-decoration:none;"><i class="fa-solid fa-house"></i> Home</a>
            <a href="dashboard.php" style="color:#fff; text-decoration:none;"><i class="fa-regular fa-user"></i> My Account</a>
            <a href="logout.php" style="color:#ffd700; text-decoration:none;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <!-- Orders Content -->
    <div class="orders-wrap">

        <!-- Sidebar Filters -->
        <aside class="orders-sidebar">
            <h3><i class="fa-solid fa-sliders-h"></i> Filters</h3>

            <!-- Order Status -->
            <div class="filter-group">
                <h4>ORDER STATUS</h4>
                <ul>
                    <li class="<?php echo ($status_filter=='all')?'active':''; ?>">
                        <a href="?status=all&time=<?php echo $time_filter; ?>&search=<?php echo urlencode($search_query); ?>">All</a>
                    </li>
                    <li class="<?php echo ($status_filter=='ontheway')?'active':''; ?>">
                        <a href="?status=ontheway&time=<?php echo $time_filter; ?>&search=<?php echo urlencode($search_query); ?>">On the way</a>
                    </li>
                    <li class="<?php echo ($status_filter=='delivered')?'active':''; ?>">
                        <a href="?status=delivered&time=<?php echo $time_filter; ?>&search=<?php echo urlencode($search_query); ?>">Delivered</a>
                    </li>
                    <li class="<?php echo ($status_filter=='cancelled')?'active':''; ?>">
                        <a href="?status=cancelled&time=<?php echo $time_filter; ?>&search=<?php echo urlencode($search_query); ?>">Cancelled</a>
                    </li>
                    <li class="<?php echo ($status_filter=='refunded')?'active':''; ?>">
                        <a href="?status=refunded&time=<?php echo $time_filter; ?>&search=<?php echo urlencode($search_query); ?>">Refunded</a>
                    </li>
                </ul>
            </div>

            <!-- Order Time -->
            <div class="filter-group">
                <h4>ORDER TIME</h4>
                <ul>
                    <li class="<?php echo ($time_filter=='all')?'active':''; ?>">
                        <a href="?status=<?php echo $status_filter; ?>&time=all&search=<?php echo urlencode($search_query); ?>">All</a>
                    </li>
                    <li class="<?php echo ($time_filter=='last30')?'active':''; ?>">
                        <a href="?status=<?php echo $status_filter; ?>&time=last30&search=<?php echo urlencode($search_query); ?>">Last 30 days</a>
                    </li>
                    <li class="<?php echo ($time_filter=='2024')?'active':''; ?>">
                        <a href="?status=<?php echo $status_filter; ?>&time=2024&search=<?php echo urlencode($search_query); ?>">2024</a>
                    </li>
                    <li class="<?php echo ($time_filter=='2023')?'active':''; ?>">
                        <a href="?status=<?php echo $status_filter; ?>&time=2023&search=<?php echo urlencode($search_query); ?>">2023</a>
                    </li>
                    <li class="<?php echo ($time_filter=='older')?'active':''; ?>">
                        <a href="?status=<?php echo $status_filter; ?>&time=older&search=<?php echo urlencode($search_query); ?>">Older</a>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Main Orders -->
        <main class="orders-main">

            <!-- Search & Heading -->
            <div class="orders-header">
                <h2>My Orders</h2>
                <form method="GET" class="search-box-mini">
                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                    <input type="hidden" name="time" value="<?php echo $time_filter; ?>">
                    <input type="text" name="search" placeholder="Search your orders here..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>

            <?php if (count($filtered_orders) > 0): ?>
                <!-- Order Cards -->
                <?php foreach ($filtered_orders as $order): ?>
                    <div class="order-card">
                        <div class="order-summary">
                            <span class="order-id">Order #<?php echo $order['id']; ?></span>
                            <span>Placed on <?php echo date('M d, Y', strtotime($order['date'])); ?></span>
                            <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </div>

                        <?php foreach ($order['items'] as $item): ?>
                            <div class="order-item">
                                <img src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <div class="item-price">₹<?php echo number_format($item['price'], 2); ?></div>
                                    <div class="delivery-info">
                                        Delivered on <?php echo date('M d, Y', strtotime($order['date'])); ?>
                                        <?php if ($order['status'] == 'Delivered'): ?>
                                            <span style="display:inline-block; margin-left:10px; color:#27ae60;">✓ Your item has been delivered</span>
                                        <?php elseif ($order['status'] == 'Refunded'): ?>
                                            <span style="display:inline-block; margin-left:10px; color:#e67e22;">Refund completed</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <?php if ($order['status'] == 'Delivered'): ?>
                                        <button class="rate-btn" onclick="alert('Rate & Review this product')">
                                            <i class="fa-regular fa-star"></i> Rate & Review Product
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-orders">
                    <i class="fa-regular fa-box-open"></i>
                    <h3>No orders found</h3>
                    <p>Try adjusting your filters or start shopping!</p>
                    <a href="index.php" style="color:#2874f0; text-decoration:none; font-weight:600;">Continue Shopping</a>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- Footer (optional) -->
    <footer class="footer" style="margin-top:40px; background:#172337; color:#fff; padding:30px 0;">
        <div style="max-width:1100px; margin:auto; text-align:center; color:#ccc;">
            <p>&copy; 2026 Quick Basket. All Rights Reserved.</p>
        </div>
    </footer>

</body>
</html>