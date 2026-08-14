<?php
session_start();
require_once 'config.php';

// Get product ID from POST
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($product_id <= 0) {
    header('Location: index.php');
    exit;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if product already in cart
if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id] += $quantity;
} else {
    // Verify product exists and has stock
    $sql = "SELECT id, stock FROM products WHERE id = $product_id AND status = 'active'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);
        if ($product['stock'] >= $quantity) {
            $_SESSION['cart'][$product_id] = $quantity;
        } else {
            header('Location: index.php?error=stock');
            exit;
        }
    } else {
        header('Location: index.php');
        exit;
    }
}

// ✅ Success – redirect to cart page with success message
header('Location: cart.php?added=1');
exit;
?>