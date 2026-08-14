<?php
session_start();
require_once 'config.php';

// Get cart items
$cart_items = [];
$total = 0;

// ✅ Check if cart exists and is not empty
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $ids_str = implode(',', $ids);
    $sql = "SELECT id, name, price, image FROM products WHERE id IN ($ids_str) AND status = 'active'";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['quantity'] = $_SESSION['cart'][$row['id']];
        $row['subtotal'] = $row['price'] * $row['quantity'];
        $total += $row['subtotal'];
        $cart_items[] = $row;
    }
}

// ✅ Check for success/error messages
$success = isset($_GET['added']) ? 'Product added to cart!' : '';
$error = isset($_GET['error']) ? 'Error adding product.' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .cart-wrap {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .cart-wrap h2 { margin-bottom: 25px; }
        .alert {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d5f5e3; color: #27ae60; }
        .alert-error { background: #fadbd8; color: #e74c3c; }
        .cart-item {
            display: flex;
            gap: 20px;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        .cart-item .details { flex: 1; }
        .cart-item .details h4 { margin-bottom: 5px; }
        .cart-item .details .price { color: #2874f0; font-weight: 600; }
        .cart-item .qty {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .cart-item .qty button {
            background: #f0f0f0;
            border: none;
            padding: 4px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .cart-item .qty button:hover { background: #ddd; }
        .cart-total {
            text-align: right;
            padding: 20px 0;
            font-size: 20px;
            font-weight: 700;
            border-top: 2px solid #2874f0;
            margin-top: 10px;
        }
        .btn-checkout {
            background: #2874f0;
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-checkout:hover { background: #0052cc; }
        .empty-cart {
            text-align: center;
            padding: 40px;
            color: #888;
        }
        .empty-cart i { font-size: 60px; color: #ddd; margin-bottom: 15px; display: block; }
        .back-link { display: inline-block; margin-top: 15px; color: #2874f0; text-decoration: none; }
        .remove-link { color: #e74c3c; text-decoration: none; font-size: 14px; }
        .remove-link:hover { text-decoration: underline; }
        .continue-shopping {
            display: inline-block;
            margin-top: 15px;
            color: #2874f0;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <!-- Simple Header -->
    <header class="top-header" style="background:#2874f0; padding:14px 4%; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
        <div class="logo">
            <h1 style="color:#fff; font-size:30px;">Quick<span style="color:#ffd700;">Basket</span></h1>
        </div>
        <div style="color:#fff; display:flex; gap:20px;">
            <a href="index.php" style="color:#fff; text-decoration:none;"><i class="fa-solid fa-house"></i> Home</a>
            <a href="logout.php" style="color:#ffd700; text-decoration:none;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="cart-wrap">
        <h2><i class="fa-solid fa-cart-shopping"></i> Your Cart</h2>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">❌ <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($cart_items)): ?>
            <?php foreach ($cart_items as $item): ?>
                <div class="cart-item">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <div class="details">
                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                        <div class="price">₹<?php echo number_format($item['price'], 2); ?></div>
                    </div>
                    <div class="qty">
                        <form action="update-cart.php" method="POST" style="display:inline;">
                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                            <input type="hidden" name="action" value="decrease">
                            <button type="submit">-</button>
                        </form>
                        <span style="min-width:25px; text-align:center;"><?php echo $item['quantity']; ?></span>
                        <form action="update-cart.php" method="POST" style="display:inline;">
                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                            <input type="hidden" name="action" value="increase">
                            <button type="submit">+</button>
                        </form>
                    </div>
                    <div>
                        <strong>₹<?php echo number_format($item['subtotal'], 2); ?></strong>
                        <br>
                        <a href="update-cart.php?remove=<?php echo $item['id']; ?>" class="remove-link" onclick="return confirm('Remove this item?')">Remove</a>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="cart-total">
                Total: ₹<?php echo number_format($total, 2); ?>
                <br><br>
                <button class="btn-checkout" onclick="alert('Checkout coming soon!')">Proceed to Checkout</button>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fa-solid fa-cart-plus"></i>
                <h3>Your cart is empty</h3>
                <p>Start shopping to add items.</p>
                <a href="index.php" style="color:#2874f0; text-decoration:none; font-weight:600;">Continue Shopping</a>
            </div>
        <?php endif; ?>

        <a href="index.php" class="continue-shopping">← Continue Shopping</a>
    </div>

</body>
</html>