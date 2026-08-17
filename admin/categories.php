<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$current_page = 'categories';

// ---------- SEARCH ----------
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $search_param = "%$search%";
    $where .= " AND name LIKE ?";
    $params[] = $search_param;
    $types .= "s";
}

// Count total
$count_sql = "SELECT COUNT(*) AS total FROM product_categories $where";
$stmt = mysqli_prepare($conn, $count_sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$total_row = mysqli_fetch_assoc($count_result);
$total_categories = $total_row['total'];
$total_pages = ceil($total_categories / $limit);

// Fetch categories
$sql = "SELECT id, name, slug FROM product_categories $where ORDER BY name LIMIT ? OFFSET ?";
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

// ---------- MESSAGE HANDLING ----------
$message = '';
$message_type = '';
if (isset($_GET['deleted'])) {
    $message = 'Category deleted successfully!';
    $message_type = 'success';
}
if (isset($_GET['updated'])) {
    $message = 'Category updated successfully!';
    $message_type = 'success';
}
if (isset($_GET['added'])) {
    $message = 'Category added successfully!';
    $message_type = 'success';
}
if (isset($_GET['error'])) {
    $message = 'Something went wrong. Please try again.';
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin</title>
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

        .right-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .btn-add {
            background: #27ae60;
            color: #fff;
            padding: 8px 18px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-add:hover {
            background: #219a52;
            transform: scale(1.02);
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
            width: 220px;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }
        .search-form input:focus {
            border-color: #2874f0;
            box-shadow: 0 0 0 3px rgba(40,116,240,0.1);
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
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

        .action-btn {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            margin: 0 2px;
            display: inline-block;
            transition: 0.3s;
        }
        .action-btn.edit {
            background: #2874f0;
            color: #fff;
        }
        .action-btn.edit:hover {
            background: #0052cc;
        }
        .action-btn.delete {
            background: #e74c3c;
            color: #fff;
        }
        .action-btn.delete:hover {
            background: #c0392b;
        }
        .action-btn.view {
            background: #17a2b8;
            color: #fff;
        }
        .action-btn.view:hover {
            background: #0f7a8a;
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

        /* ---------- Responsive ---------- */
        @media (max-width: 768px) {
            .admin-layout { flex-direction: column; }
            .sidebar { width: 100%; min-height: auto; }
            .admin-topbar { flex-direction: column; align-items: stretch; text-align: center; }
            .right-actions { flex-direction: column; align-items: stretch; }
            .search-form { flex-wrap: wrap; }
            .search-form input { width: 100%; }
            .admin-main { padding: 15px; }
        }
        @media (max-width: 576px) {
            .admin-topbar h1 { font-size: 20px; }
            th, td { padding: 8px 10px; font-size: 13px; }
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
            <h1><i class="fa-solid fa-tags"></i> Manage Categories</h1>
            <div class="right-actions">
                <a href="add-category.php" class="btn-add">
                    <i class="fa-solid fa-plus"></i> Add Category
                </a>
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Search categories..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fa-solid fa-search"></i></button>
                    <?php if (!empty($search)): ?>
                        <a href="categories.php" class="btn-clear">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['slug'] ?? ''); ?></td>
                                <td>
                                    <a href="edit-category.php?id=<?php echo $row['id']; ?>" class="action-btn edit" title="Edit">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    <a href="delete-category.php?id=<?php echo $row['id']; ?>" class="action-btn delete" onclick="return confirm('Delete this category?')" title="Delete">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </a>
                                    <a href="../category-products.php?slug=<?php echo $row['slug'] ?? $row['name']; ?>" class="action-btn view" target="_blank" title="View Products">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty-msg">
                                <i class="fa-regular fa-folder-open"></i>
                                No categories found.
                                <?php if (!empty($search)): ?>
                                    <br><small>Try adjusting your search terms.</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
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