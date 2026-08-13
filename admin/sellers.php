<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

// Fetch all sellers
$sql = "SELECT * FROM sellers ORDER BY store_name";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sellers - Admin</title>
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
        .btn-add {
            background: #27ae60;
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
        }
        .btn-add:hover { background: #219a52; }
        .table-wrap {
            background: #fff;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        table { width: 100%; border-collapse: collapse; }
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
        .status-badge {
            padding: 3px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active { background: #d5f5e3; color: #27ae60; }
        .status-inactive { background: #fdebd0; color: #e67e22; }
        .status-pending { background: #fdebd0; color: #e67e22; }
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
        .empty-msg { text-align: center; padding: 40px; color: #888; }
    </style>
</head>
<body>
    <div class="admin-wrap">
        <div class="admin-header">
            <h2><i class="fa-solid fa-store"></i> Manage Sellers</h2>
            <a href="add-seller.php" class="btn-add"><i class="fa-solid fa-plus"></i> Add Seller</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Store Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['store_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="edit-seller.php?id=<?php echo $row['id']; ?>" class="btn-edit">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>
                                        <a href="delete-seller.php?id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete this seller?')">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="empty-msg">No sellers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>