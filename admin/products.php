<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

// Search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query with search
$where = '';
if (!empty($search)) {
    $search_esc = mysqli_real_escape_string($conn, $search);
    $where = "WHERE name LIKE '%$search_esc%' OR description LIKE '%$search_esc%'";
}

// Count total
$count_sql = "SELECT COUNT(*) AS total FROM products $where";
$count_result = mysqli_query($conn, $count_sql);
$total_row = mysqli_fetch_assoc($count_result);
$total_products = $total_row['total'];
$total_pages = ceil($total_products / $limit);

// Fetch products
$sql = "SELECT id, name, description, price, stock, image, status, created_at 
        FROM products $where 
        ORDER BY id DESC 
        LIMIT $offset, $limit";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .admin-wrap { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .admin-header .search-form {
            display: flex;
            gap: 8px;
        }
        .admin-header .search-form input {
            padding: 8px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            width: 250px;
        }
        .admin-header .search-form button {
            padding: 8px 18px;
            background: #2874f0;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-add {
            background: #27ae60;
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-add:hover { background: #219a52; }
        .table-wrap {
            background: #fff;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #eee;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        tr:hover td { background: #f8f9ff; }
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }
        .status-badge {
            padding: 3px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active { background: #d5f5e3; color: #27ae60; }
        .status-inactive { background: #fdebd0; color: #e67e22; }
        .action-btns a {
            padding: 4px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            margin-right: 5px;
        }
        .btn-edit { background: #2874f0; color: #fff; }
        .btn-edit:hover { background: #0052cc; }
        .btn-delete { background: #e74c3c; color: #fff; }
        .btn-delete:hover { background: #c0392b; }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            padding: 20px;
        }
        .pagination a {
            padding: 6px 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .pagination a.active {
            background: #2874f0;
            color: #fff;
            border-color: #2874f0;
        }
        .empty-msg { text-align: center; padding: 40px; color: #888; }
        @media (max-width: 768px) {
            .admin-header { flex-direction: column; gap: 10px; }
            .admin-header .search-form input { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="admin-wrap">
        <div class="admin-header">
            <h2><i class="fa-solid fa-cube"></i> Manage Products</h2>
            <div>
                <a href="add-product.php" class="btn-add"><i class="fa-solid fa-plus"></i> Add Product</a>
                <form method="GET" class="search-form" style="display:inline-flex; margin-left:10px;">
                    <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fa-solid fa-search"></i></button>
                    <?php if (!empty($search)): ?>
                        <a href="products.php" style="background:#e74c3c; color:#fff; padding:8px 14px; border-radius:6px; text-decoration:none;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($row['image']); ?>" class="product-img" alt="Product">
                                </td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td>₹<?php echo number_format($row['price'], 2); ?></td>
                                <td><?php echo $row['stock']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="edit-product.php?id=<?php echo $row['id']; ?>" class="btn-edit">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>
                                        <a href="delete-product.php?id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete this product?')">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="empty-msg">No products found.</td></tr>
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
    </div>
</body>
</html>