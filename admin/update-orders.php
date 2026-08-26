<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders.php?error=1');
    exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

$allowed_statuses = [
    'pending',
    'confirmed',
    'processing',
    'shipped',
    'ontheway',
    'delivered',
    'cancelled',
    'refunded'
];

if ($order_id <= 0 || !in_array($status, $allowed_statuses, true)) {
    header('Location: orders.php?error=1');
    exit;
}

$sql = "UPDATE orders 
        SET status = ?, updated_at = NOW()
        WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    header('Location: orders.php?error=1');
    exit;
}

mysqli_stmt_bind_param($stmt, "si", $status, $order_id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    header('Location: orders.php?updated=1');
    exit;
}

mysqli_stmt_close($stmt);

header('Location: orders.php?error=1');
exit;
?>