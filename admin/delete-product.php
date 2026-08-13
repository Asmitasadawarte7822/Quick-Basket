<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header('Location: products.php');
    exit;
}

$sql = "DELETE FROM products WHERE id = $product_id";
if (mysqli_query($conn, $sql)) {
    header('Location: products.php?deleted=1');
} else {
    header('Location: products.php?error=delete_failed');
}
exit;
?>