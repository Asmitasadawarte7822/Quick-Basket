<?php
// ============================================================
// 1. DATABASE CONFIGURATION - CHANGE THESE TO YOUR OWN!
// ============================================================
$host = 'localhost';
$dbname = 'quick_basket';   // Your database name
$user = 'root';             // Your DB username
$pass = '';                 // Your DB password (default is empty for XAMPP)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// ============================================================
// 2. HANDLE FORM SUBMISSION (UPDATE ORDER)
// ============================================================
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $id        = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $city      = trim($_POST['city'] ?? '');
    $state     = trim($_POST['state'] ?? '');
    $pincode   = trim($_POST['pincode'] ?? '');
    $status    = trim($_POST['status'] ?? '');

    if (!$id || $id <= 0) {
        $message = "Invalid Order ID.";
        $messageType = "error";
    } else {
        try {
            // Update query - adjust column names to match your actual table!
            $sql = "UPDATE orders 
                    SET full_name = :full_name,
                        phone = :phone,
                        address = :address,
                        city = :city,
                        state = :state,
                        pincode = :pincode,
                        status = :status
                    WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':full_name' => $full_name,
                ':phone'     => $phone,
                ':address'   => $address,
                ':city'      => $city,
                ':state'     => $state,
                ':pincode'   => $pincode,
                ':status'    => $status,
                ':id'        => $id
            ]);

            if ($stmt->rowCount() > 0) {
                $message = "✅ Order #$id updated successfully!";
                $messageType = "success";
            } else {
                $message = "ℹ️ No changes were made to Order #$id.";
                $messageType = "info";
            }
        } catch (PDOException $e) {
            $message = "❌ Update failed: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// ============================================================
// 3. FETCH ORDER DATA (for editing)
// ============================================================
$order = null;
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->execute([':id' => $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $message = "⚠️ Order #$orderId not found.";
            $messageType = "error";
            $orderId = 0;
        }
    } catch (PDOException $e) {
        die("❌ Error fetching order: " . $e->getMessage());
    }
}

// ============================================================
// 4. FETCH ALL ORDERS (for the list view)
// ============================================================
try {
    $allOrders = $pdo->query("SELECT id, full_name, phone, total, status, created_at FROM orders ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allOrders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Basket - Update Order</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f4f7fa; padding: 30px; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        h1, h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .message { padding: 12px 18px; border-radius: 6px; margin: 15px 0; font-weight: 500; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #2c3e50; color: #fff; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f8f9fa; }
        .btn { display: inline-block; padding: 8px 16px; border-radius: 5px; text-decoration: none; color: #fff; font-weight: 600; border: none; cursor: pointer; }
        .btn-edit { background: #3498db; }
        .btn-edit:hover { background: #2176ae; }
        .btn-update { background: #27ae60; padding: 12px 28px; font-size: 16px; }
        .btn-update:hover { background: #1e8449; }
        .btn-back { background: #95a5a6; }
        .btn-back:hover { background: #7f8c8d; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: inline-block; width: 140px; font-weight: 600; color: #2c3e50; }
        .form-group input, .form-group select { padding: 8px 12px; border: 1px solid #ccc; border-radius: 5px; width: 300px; max-width: 100%; }
        .form-row { display: flex; flex-wrap: wrap; gap: 10px; }
        .form-row .form-group { flex: 1 1 45%; }
        .form-actions { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        .order-summary-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #3498db; }
        .order-summary-box strong { display: inline-block; width: 120px; }
        .no-orders { text-align: center; padding: 40px; color: #7f8c8d; }
        @media (max-width: 768px) {
            .form-group label { display: block; width: 100%; margin-bottom: 4px; }
            .form-group input, .form-group select { width: 100%; }
        }
    </style>
</head>
<body>

<div class="container">

    <h1>📦 Quick Basket - Update Order</h1>

    <?php if ($message): ?>
        <div class="message <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($orderId > 0 && $order): ?>
        <!-- ========================== EDIT FORM ========================== -->
        <h2>✏️ Edit Order #<?= htmlspecialchars($order['id']) ?></h2>

        <div class="order-summary-box">
            <strong>Current Total:</strong> ₹<?= number_format($order['total'] ?? 0, 2) ?><br>
            <strong>Placed On:</strong> <?= htmlspecialchars($order['created_at'] ?? 'N/A') ?><br>
            <strong>Payment:</strong> <?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?>
        </div>

        <form method="post" action="">
            <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($order['full_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($order['phone'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="<?= htmlspecialchars($order['address'] ?? '') ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="<?= htmlspecialchars($order['city'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" value="<?= htmlspecialchars($order['state'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="pincode">Pincode</label>
                <input type="text" id="pincode" name="pincode" value="<?= htmlspecialchars($order['pincode'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="status">Order Status</label>
                <select id="status" name="status">
                    <option value="Pending" <?= ($order['status'] ?? '') == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Processing" <?= ($order['status'] ?? '') == 'Processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="Shipped" <?= ($order['status'] ?? '') == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="Delivered" <?= ($order['status'] ?? '') == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="Cancelled" <?= ($order['status'] ?? '') == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" name="update_order" class="btn btn-update">💾 Update Order</button>
                <a href="update-order.php" class="btn btn-back">⬅ Back to Order List</a>
            </div>
        </form>

    <?php else: ?>
        <!-- ========================== ORDER LIST ========================== -->
        <h2>📋 All Orders</h2>
        <?php if (count($allOrders) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Placed On</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allOrders as $o): ?>
                        <tr>
                            <td><strong>#<?= htmlspecialchars($o['id']) ?></strong></td>
                            <td><?= htmlspecialchars($o['full_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($o['phone'] ?? 'N/A') ?></td>
                            <td>₹<?= number_format($o['total'] ?? 0, 2) ?></td>
                            <td><span style="background: <?= ($o['status'] == 'Delivered' ? '#27ae60' : ($o['status'] == 'Cancelled' ? '#e74c3c' : '#f39c12')); ?>; color:#fff; padding:3px 10px; border-radius:20px; font-size:12px;"><?= htmlspecialchars($o['status'] ?? 'Pending') ?></span></td>
                            <td><?= date('d M Y, h:i A', strtotime($o['created_at'] ?? 'now')) ?></td>
                            <td>
                                <a href="update-order.php?id=<?= $o['id'] ?>" class="btn btn-edit">✏️ Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-orders">
                <p>🚫 No orders found in the database.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

</body>
</html>