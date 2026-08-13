<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$seller_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($seller_id <= 0) {
    header('Location: sellers.php');
    exit;
}

// Check if seller has products
$check_sql = "SELECT COUNT(*) AS total FROM products WHERE seller_id = $seller_id";
$check_result = mysqli_query($conn, $check_sql);
$check_row = mysqli_fetch_assoc($check_result);

if ($check_row['total'] > 0) {
    header('Location: sellers.php?error=has_products');
    exit;
}

$sql = "DELETE FROM sellers WHERE id = $seller_id";
if (mysqli_query($conn, $sql)) {
    header('Location: sellers.php?deleted=1');
} else {
    header('Location: sellers.php?error=delete_failed');
}
exit;
?>