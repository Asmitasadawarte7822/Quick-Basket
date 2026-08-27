<?php
session_start();
require_once '../config.php';

// Admin login check
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Only POST request allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders.php?error=1');
    exit;
}

// Get values
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

// Allowed order statuses
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

// Validate order ID
if ($order_id <= 0) {
    header('Location: orders.php?error=1');
    exit;
}

// Validate status
if (!in_array($status, $allowed_statuses, true)) {
    header('Location: orders.php?error=1');
    exit;
}

// Check order exists
$check_sql = "SELECT id FROM orders WHERE id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);

if (!$check_stmt) {
    header('Location: orders.php?error=1');
    exit;
}

mysqli_stmt_bind_param($check_stmt, "i", $order_id);
mysqli_stmt_execute($check_stmt);

$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) === 0) {
    mysqli_stmt_close($check_stmt);
    header('Location: orders.php?error=1');
    exit;
}

mysqli_stmt_close($check_stmt);

// Update order status
$update_sql = "UPDATE orders 
               SET status = ?, updated_at = NOW()
               WHERE id = ?";

$update_stmt = mysqli_prepare($conn, $update_sql);

if (!$update_stmt) {
    header('Location: orders.php?error=1');
    exit;
}

mysqli_stmt_bind_param($update_stmt, "si", $status, $order_id);

if (mysqli_stmt_execute($update_stmt)) {
    mysqli_stmt_close($update_stmt);

    header('Location: orders.php?updated=1');
    exit;
} else {
    mysqli_stmt_close($update_stmt);
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
    header('Location: orders.php?error=1');
    exit;
}
?>