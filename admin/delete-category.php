<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$cat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($cat_id <= 0) {
    header('Location: categories.php');
    exit;
}

$sql = "DELETE FROM product_categories WHERE id = $cat_id";
if (mysqli_query($conn, $sql)) {
    header('Location: categories.php?deleted=1');
} else {
    header('Location: categories.php?error=delete_failed');
}
exit;
?>