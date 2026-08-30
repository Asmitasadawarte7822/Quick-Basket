<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

if ($order_id <= 0) {
    header('Location: my-orders.php');
    exit;
}

// Check if order belongs to user and is cancellable
$check_sql = "SELECT id, status FROM orders WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    // Update order status
    $update_sql = "UPDATE orders SET status = 'cancelled' WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "i", $order_id);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);

    // Add history if table exists
    $check_table = "SHOW TABLES LIKE 'order_status_history'";
    $table_check = mysqli_query($conn, $check_table);
    if (mysqli_num_rows($table_check) > 0) {
        $history_sql = "INSERT INTO order_status_history (order_id, status, note) VALUES (?, ?, ?)";
        $note = 'Order cancelled by user.';
        $history_stmt = mysqli_prepare($conn, $history_sql);
        mysqli_stmt_bind_param($history_stmt, "iss", $order_id, 'cancelled', $note);
        mysqli_stmt_execute($history_stmt);
        mysqli_stmt_close($history_stmt);
    }

    $_SESSION['message'] = 'Order cancelled successfully.';
} else {
    $_SESSION['message'] = 'Cannot cancel this order.';
}
mysqli_stmt_close($stmt);
mysqli_close($conn);

header('Location: track-order.php?id=' . $order_id);
exit;
?>