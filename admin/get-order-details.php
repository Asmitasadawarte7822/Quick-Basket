<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Fetch order details
$order_sql = "SELECT o.*, u.name AS customer_name, u.email AS customer_email 
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              WHERE o.id = ?";
$stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($order = mysqli_fetch_assoc($result)) {
    mysqli_stmt_close($stmt);

    // Fetch order items
    $items_sql = "SELECT oi.*, p.name AS product_name, p.image 
                  FROM order_items oi 
                  LEFT JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id = ?";
    $items_stmt = mysqli_prepare($conn, $items_sql);
    mysqli_stmt_bind_param($items_stmt, "i", $order_id);
    mysqli_stmt_execute($items_stmt);
    $items_result = mysqli_stmt_get_result($items_stmt);
    $items = [];
    while ($row = mysqli_fetch_assoc($items_result)) {
        $items[] = $row;
    }
    mysqli_stmt_close($items_stmt);

    // Fetch status history
    $history = [];
    $check_table = "SHOW TABLES LIKE 'order_status_history'";
    $table_check = mysqli_query($conn, $check_table);
    if (mysqli_num_rows($table_check) > 0) {
        $history_sql = "SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC";
        $history_stmt = mysqli_prepare($conn, $history_sql);
        mysqli_stmt_bind_param($history_stmt, "i", $order_id);
        mysqli_stmt_execute($history_stmt);
        $history_result = mysqli_stmt_get_result($history_stmt);
        while ($row = mysqli_fetch_assoc($history_result)) {
            $history[] = $row;
        }
        mysqli_stmt_close($history_stmt);
    }

    // If no history, add current status
    if (empty($history)) {
        $history[] = [
            'status' => $order['status'],
            'created_at' => $order['order_date'],
            'note' => 'Order placed.'
        ];
    }

    // Add shipping address if missing
    $order['shipping_address'] = $order['shipping_address'] ?? 'Not provided';

    echo json_encode([
        'success' => true,
        'order' => [
            'id' => $order['id'],
            'order_number' => str_pad($order['id'], 6, '0', STR_PAD_LEFT),
            'total_amount' => $order['total_amount'],
            'status' => $order['status'],
            'payment_method' => $order['payment_method'] ?? 'cod',
            'payment_status' => $order['payment_status'] ?? 'pending',
            'order_date' => $order['order_date'],
            'customer_name' => $order['customer_name'] ?? 'Guest',
            'customer_email' => $order['customer_email'] ?? '',
            'shipping_address' => $order['shipping_address'],
            'items' => $items,
            'history' => $history
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
}
mysqli_close($conn);
?>