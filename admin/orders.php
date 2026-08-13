<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$sql = "SELECT o.*, u.name AS user_name 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.id DESC";
$result = mysqli_query($conn, $sql);
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
        .admin-wrap { padding: 20px; max-width: 1200px; margin: 0 auto; }
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
        }
        .status-badge {
            padding: 3px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #fdebd0; color: #e67e22; }
        .status-ontheway { background: #d4e6f1; color: #2874f0; }
        .status-delivered { background: #d5f5e3; color: #27ae60; }
        .status-cancelled { background: #fadbd8; color: #e74c3c; }
        .status-refunded { background: #fadbd8; color: #e67e22; }
        .empty-msg { text-align: center; padding: 40px; color: #888; }
    </style>
</head>
<body>
    <div class="admin-wrap">
        <h2><i class="fa-solid fa-box"></i> Manage Orders</h2>
        <div class="table-wrap">
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
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['order_number'] ?? '#' . $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($row['order_date'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="empty-msg">No orders found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>