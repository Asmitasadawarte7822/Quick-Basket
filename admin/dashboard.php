<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

// ---------- Safe query helper ----------
function safe_count($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result)["total"];
    }
    return 0;
}

// ---------- Products (if table exists) ----------
$product_count = safe_count($conn, "SELECT COUNT(*) AS total FROM products");

// ---------- Users (only non-admins) ----------
// Use role = 'user' if you have a role column, or just count all except admin
$user_count = safe_count($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'user'");

// ---------- Orders (if table exists) ----------
$order_count = safe_count($conn, "SELECT COUNT(*) AS total FROM orders");

// ---------- Revenue (if table exists) ----------
$revenue_sql = "SELECT COALESCE(SUM(total_amount),0) AS total FROM orders WHERE status != 'Cancelled'";
$revenue = safe_count($conn, $revenue_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="admin-logo">Quick<span>Basket</span></div>
        <nav>
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="products.php">Products</a>
            <a href="categories.php">Categories</a>
            <a href="users.php">Users</a>
            <a href="orders.php">Orders</a>
            <a href="logout.php">Logout</a>
            <a href="sellers.php">
            <i class="fa-solid fa-store"></i> Sellers</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <h1>Dashboard</h1>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION["admin_name"]); ?></span>
        </div>

        <div class="dashboard-cards">
            <div class="dashboard-card blue">
                <h3>Products</h3>
                <strong><?php echo $product_count; ?></strong>
            </div>
            <div class="dashboard-card green">
                <h3>Users</h3>
                <strong><?php echo $user_count; ?></strong>
            </div>
            <div class="dashboard-card orange">
                <h3>Orders</h3>
                <strong><?php echo $order_count; ?></strong>
            </div>
            <div class="dashboard-card purple">
                <h3>Revenue</h3>
                <strong>₹<?php echo number_format($revenue, 2); ?></strong>
            </div>
        </div>

        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="action-grid">
                <a href="add-product.php">Add Product</a>
                <a href="products.php">Manage Products</a>
                <a href="users.php">Manage Users</a>
                <a href="orders.php">Manage Orders</a>
            </div>
        </div>
    </main>
</div>
</body>
</html>