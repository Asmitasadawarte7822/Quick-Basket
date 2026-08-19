<?php
session_start();
require_once 'config.php';

// ---------- CHECK LOGIN ----------
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id']; // ✅ This is critical!

// ---------- GET POST DATA ----------
$address_id = isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0;
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'COD';
$is_cart_checkout = isset($_POST['is_cart_checkout']) && $_POST['is_cart_checkout'] == '1';
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($address_id <= 0) {
    header('Location: checkout.php?error=address');
    exit;
}

// ---------- FETCH ADDRESS ----------
$addr_sql = "SELECT * FROM addresses WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $addr_sql);
mysqli_stmt_bind_param($stmt, "ii", $address_id, $user_id);
mysqli_stmt_execute($stmt);
$addr_result = mysqli_stmt_get_result($stmt);
$address = mysqli_fetch_assoc($addr_result);
mysqli_stmt_close($stmt);

if (!$address) {
    header('Location: checkout.php?error=address');
    exit;
}

// ---------- GET CART ITEMS ----------
$cart_items = [];
$total = 0;

if ($is_cart_checkout && isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $ids_str = implode(',', $ids);
    $sql = "SELECT * FROM products WHERE id IN ($ids_str) AND status = 'active'";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['quantity'] = $_SESSION['cart'][$row['id']];
        $row['subtotal'] = $row['price'] * $row['quantity'];
        $total += $row['subtotal'];
        $cart_items[] = $row;
    }
} elseif ($product_id > 0) {
    $sql = "SELECT * FROM products WHERE id = ? AND status = 'active'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    if ($product) {
        $product['quantity'] = $quantity;
        $product['subtotal'] = $product['price'] * $quantity;
        $total = $product['subtotal'];
        $cart_items[] = $product;
    }
    mysqli_stmt_close($stmt);
}

if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

// ---------- BUILD ADDRESS TEXT ----------
$address_text = $address['full_name'] . "\n" . $address['address'] . "\n" . 
                $address['city'] . ', ' . $address['state'] . ' - ' . $address['pincode'] . "\n" .
                '📞 ' . $address['phone'];

// ---------- INSERT ORDER (✅ user_id is saved) ----------
$sql = "INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method, payment_status, order_date) 
        VALUES (?, ?, 'pending', ?, ?, 'pending', NOW())";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "idss", $user_id, $total, $address_text, $payment_method);
mysqli_stmt_execute($stmt);
$order_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

if ($order_id <= 0) {
    header('Location: checkout.php?error=order');
    exit;
}

// ---------- INSERT ORDER ITEMS ----------
foreach ($cart_items as $item) {
    $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
            VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// ---------- UPDATE PRODUCT STOCK ----------
foreach ($cart_items as $item) {
    $new_stock = $item['stock'] - $item['quantity'];
    $stock_sql = "UPDATE products SET stock = ? WHERE id = ?";
    $stock_stmt = mysqli_prepare($conn, $stock_sql);
    mysqli_stmt_bind_param($stock_stmt, "ii", $new_stock, $item['id']);
    mysqli_stmt_execute($stock_stmt);
    mysqli_stmt_close($stock_stmt);
}

// ---------- CLEAR CART ----------
if ($is_cart_checkout) {
    unset($_SESSION['cart']);
}

// ---------- REDIRECT TO CONFIRMATION ----------
header('Location: order-confirmation.php?order_id=' . $order_id);
exit;
?>