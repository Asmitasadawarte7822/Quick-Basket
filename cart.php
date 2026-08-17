<?php
session_start();
require_once 'config.php';

// ---------- USER LOGIN ----------
$user_name = null;
$is_logged_in = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT name FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($user = mysqli_fetch_assoc($result)) {
        $user_name = htmlspecialchars($user['name']);
        $is_logged_in = true;
    }
    mysqli_stmt_close($stmt);
}

// ---------- CART ----------
$cart_items = [];
$total = 0;
$discount = 0;
$coupon_discount = 227;
$platform_fee = 9;
$grand_total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $ids_str = implode(',', $ids);
    $sql = "SELECT p.id, p.name, p.price, p.image, p.description, p.stock, s.store_name 
            FROM products p 
            LEFT JOIN sellers s ON p.seller_id = s.id 
            WHERE p.id IN ($ids_str) AND p.status = 'active'";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['quantity'] = $_SESSION['cart'][$row['id']];
        $row['subtotal'] = $row['price'] * $row['quantity'];
        $total += $row['subtotal'];
        $cart_items[] = $row;
    }
}

// Calculate discount (20% off)
$discount = $total * 0.20;
$grand_total = $total - $discount - $coupon_discount + $platform_fee;
$total_savings = $total - $grand_total + $coupon_discount;

$cart_count = count($cart_items);
$total_items = array_sum(array_column($cart_items, 'quantity'));
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
        /* ---------- Cart Layout ---------- */
        .cart-wrap {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        /* ---------- Cart Items Section ---------- */
        .cart-items-section {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .cart-items-section .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
            margin-bottom: 16px;
        }
        .cart-items-section .cart-header h2 {
            font-size: 20px;
            color: #222;
            font-weight: 600;
        }
        .cart-items-section .cart-header h2 span {
            color: #888;
            font-weight: 400;
            font-size: 14px;
        }

        /* ---------- Success Message ---------- */
        .alert-success {
            background: #d5f5e3;
            color: #27ae60;
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid #27ae60;
        }
        .alert-success i {
            font-size: 18px;
        }

        /* ---------- Cart Item ---------- */
        .cart-item {
            display: flex;
            gap: 20px;
            padding: 16px 0;
            border-bottom: 1px solid #f0f0f0;
            position: relative;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item .item-image {
            width: 100px;
            height: 100px;
            flex-shrink: 0;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #eee;
            padding: 10px;
        }
        .cart-item .item-image img {
            max-width: 100%;
            max-height: 80px;
            object-fit: contain;
        }
        .cart-item .item-details {
            flex: 1;
        }
        .cart-item .item-details .item-title {
            font-size: 16px;
            font-weight: 500;
            color: #222;
            margin-bottom: 2px;
        }
        .cart-item .item-details .item-seller {
            font-size: 13px;
            color: #888;
            margin-bottom: 2px;
        }
        .cart-item .item-details .item-seller i {
            color: #2874f0;
        }
        .cart-item .item-details .item-assured {
            display: inline-block;
            background: #2874f0;
            color: #fff;
            padding: 0 10px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }
        .cart-item .item-details .item-price {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin: 4px 0 8px;
        }
        .cart-item .item-details .item-price .current {
            font-size: 20px;
            font-weight: 700;
            color: #222;
        }
        .cart-item .item-details .item-price .old {
            font-size: 14px;
            color: #888;
            text-decoration: line-through;
        }
        .cart-item .item-details .item-price .discount-badge {
            font-size: 13px;
            color: #27ae60;
            font-weight: 600;
        }
        .cart-item .item-details .item-actions {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }
        .cart-item .item-details .item-actions .qty {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        .cart-item .item-details .item-actions .qty button {
            padding: 4px 14px;
            border: none;
            background: #f8f9fa;
            cursor: pointer;
            font-size: 14px;
            transition: 0.3s;
            font-weight: 600;
        }
        .cart-item .item-details .item-actions .qty button:hover {
            background: #eee;
        }
        .cart-item .item-details .item-actions .qty span {
            padding: 4px 16px;
            min-width: 30px;
            text-align: center;
            font-weight: 500;
        }
        .cart-item .item-details .item-actions .action-btn {
            background: none;
            border: none;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: 0.3s;
            padding: 4px 0;
        }
        .cart-item .item-details .item-actions .action-btn.save-btn {
            color: #2874f0;
        }
        .cart-item .item-details .item-actions .action-btn.save-btn:hover {
            color: #0052cc;
            text-decoration: underline;
        }
        .cart-item .item-details .item-actions .action-btn.remove-btn {
            color: #e74c3c;
        }
        .cart-item .item-details .item-actions .action-btn.remove-btn:hover {
            color: #c0392b;
            text-decoration: underline;
        }

        /* ---------- Price Details Sidebar ---------- */
        .price-details {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .price-details h3 {
            font-size: 18px;
            font-weight: 600;
            color: #222;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
            margin-bottom: 12px;
        }
        .price-details .price-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
            color: #555;
        }
        .price-details .price-row .label {
            color: #888;
        }
        .price-details .price-row .value {
            font-weight: 500;
        }
        .price-details .price-row .value.discount {
            color: #27ae60;
        }
        .price-details .price-row.total {
            border-top: 1px solid #eee;
            padding-top: 14px;
            margin-top: 6px;
            font-size: 18px;
            font-weight: 700;
            color: #222;
        }
        .price-details .price-row.total .value {
            color: #2874f0;
        }
        .price-details .savings {
            background: #d5f5e3;
            color: #27ae60;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 14px;
            margin: 12px 0;
            text-align: center;
        }
        .price-details .savings i {
            margin-right: 6px;
        }
        .price-details .btn-place-order {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #fb641b;
            color: #fff;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .price-details .btn-place-order:hover {
            background: #e55a12;
        }
        .price-details .btn-place-order:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .price-details .secure-note {
            text-align: center;
            color: #888;
            font-size: 13px;
            margin-top: 14px;
            line-height: 1.6;
        }
        .price-details .secure-note i {
            color: #27ae60;
        }

        /* ---------- Empty Cart ---------- */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: #888;
            grid-column: 1 / -1;
        }
        .empty-cart i {
            font-size: 80px;
            color: #ddd;
            display: block;
            margin-bottom: 20px;
        }
        .empty-cart h3 {
            font-size: 24px;
            color: #222;
            margin-bottom: 8px;
        }
        .empty-cart p {
            color: #888;
            margin-bottom: 20px;
        }
        .empty-cart .btn-shop {
            display: inline-block;
            padding: 12px 36px;
            background: #2874f0;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .empty-cart .btn-shop:hover {
            background: #0052cc;
        }

        /* ---------- Continue Shopping ---------- */
        .continue-shopping {
            display: inline-block;
            margin-top: 16px;
            color: #2874f0;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }
        .continue-shopping:hover {
            text-decoration: underline;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 992px) {
            .cart-wrap {
                grid-template-columns: 1fr;
            }
            .price-details {
                position: static;
            }
        }
        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .cart-item .item-image {
                width: 80px;
                height: 80px;
            }
            .cart-item .item-details .item-actions {
                justify-content: center;
            }
        }
        @media (max-width: 576px) {
            .cart-items-section {
                padding: 16px;
            }
            .price-details {
                padding: 16px;
            }
            .cart-items-section .cart-header h2 {
                font-size: 17px;
            }
            .cart-item .item-details .item-title {
                font-size: 14px;
            }
            .cart-item .item-details .item-price .current {
                font-size: 17px;
            }
            .cart-item .item-details .item-actions .qty button {
                padding: 2px 10px;
            }
            .cart-item .item-details .item-actions .qty span {
                padding: 2px 12px;
            }
        }
    </style>
</head>
<body>

<!-- ======== HEADER ======== -->
<header class="top-header" style="background:#2874f0; padding:14px 4%; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
    <div class="logo">
        <h1 style="color:#fff; font-size:30px;">Quick<span style="color:#ffd700;">Basket</span></h1>
    </div>
    <div class="search-box" style="flex:1; max-width:500px; display:flex; margin:0 20px;">
        <input type="text" placeholder="Search for Products, Brands and More" style="flex:1; padding:10px; border:none; border-radius:4px 0 0 4px;">
        <button style="padding:10px 20px; border:none; background:#ffd700; font-weight:700; border-radius:0 4px 4px 0; cursor:pointer;">SEARCH</button>
    </div>
    <div style="color:#fff; display:flex; gap:20px; align-items:center; flex-wrap:wrap;">
        <?php if ($is_logged_in && $user_name): ?>
            <a href="dashboard.php" style="color:#fff; text-decoration:none;"><i class="fa-regular fa-user"></i> <?php echo $user_name; ?></a>
            <a href="logout.php" style="color:#ffd700; text-decoration:none;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        <?php else: ?>
            <a href="login.php" style="color:#fff; text-decoration:none;"><i class="fa-regular fa-user"></i> Login</a>
        <?php endif; ?>
        <a href="wishlist.php" style="color:#fff; text-decoration:none;"><i class="fa-regular fa-heart"></i> Wishlist</a>
        <a href="cart.php" style="color:#fff; text-decoration:none; position:relative;">
            <i class="fa-solid fa-cart-shopping"></i> Cart
            <span style="background:#ff3b30; color:#fff; border-radius:50%; padding:2px 7px; font-size:11px; margin-left:4px;"><?php echo $cart_count; ?></span>
        </a>
    </div>
</header>

<!-- ======== CART CONTENT ======== -->
<div class="cart-wrap">
    <!-- Cart Items -->
    <div class="cart-items-section">
        <div class="cart-header">
            <h2>My Cart <span>(<?php echo $total_items; ?> items)</span></h2>
            <?php if ($cart_count > 0): ?>
                <span style="font-size:14px; color:#888;">Total: ₹<?php echo number_format($total, 2); ?></span>
            <?php endif; ?>
        </div>

        <?php if (isset($_GET['added'])): ?>
            <div class="alert-success">
                <i class="fa-regular fa-circle-check"></i>
                Product added to cart!
            </div>
        <?php endif; ?>

        <?php if (!empty($cart_items)): ?>
            <?php foreach ($cart_items as $item): ?>
                <?php
                $discount_percent = 20;
                $item_old_price = $item['price'] * 1.25;
                ?>
                <div class="cart-item">
                    <div class="item-image">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    </div>
                    <div class="item-details">
                        <div class="item-title"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="item-seller"><i class="fa-regular fa-store"></i> Seller: <?php echo htmlspecialchars($item['store_name'] ?? 'Quick Basket'); ?></div>
                        <span class="item-assured"><i class="fa-regular fa-circle-check"></i> Assured</span>
                        <div class="item-price">
                            <span class="current">₹<?php echo number_format($item['price'], 2); ?></span>
                            <span class="old">₹<?php echo number_format($item_old_price, 2); ?></span>
                            <span class="discount-badge"><?php echo $discount_percent; ?>% Off</span>
                        </div>
                        <div class="item-actions">
                            <form action="update-cart.php" method="POST" style="display:inline;">
                                <div class="qty">
                                    <button type="submit" name="action" value="decrease">
                                        <i class="fa-solid fa-minus"></i>
                                    </button>
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <span><?php echo $item['quantity']; ?></span>
                                    <button type="submit" name="action" value="increase">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </form>
                            <button class="action-btn save-btn" onclick="alert('Saved for later!')">
                                <i class="fa-regular fa-heart"></i> Save for Later
                            </button>
                            <a href="update-cart.php?remove=<?php echo $item['id']; ?>" class="action-btn remove-btn" onclick="return confirm('Remove this item from cart?')">
                                <i class="fa-regular fa-trash-can"></i> Remove
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fa-regular fa-cart-plus"></i>
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added anything to your cart yet.</p>
                <a href="index.php" class="btn-shop"><i class="fa-solid fa-arrow-left"></i> Continue Shopping</a>
            </div>
        <?php endif; ?>

        <a href="index.php" class="continue-shopping"><i class="fa-solid fa-arrow-left"></i> Continue Shopping</a>
    </div>

    <!-- Price Details -->
    <?php if (!empty($cart_items)): ?>
        <div class="price-details">
            <h3>PRICE DETAILS</h3>
            <div class="price-row">
                <span class="label">Price (<?php echo $total_items; ?> items)</span>
                <span class="value">₹<?php echo number_format($total, 2); ?></span>
            </div>
            <div class="price-row">
                <span class="label">Discount</span>
                <span class="value discount">-₹<?php echo number_format($discount, 2); ?></span>
            </div>
            <div class="price-row">
                <span class="label">Coupons for you</span>
                <span class="value discount">-₹<?php echo number_format($coupon_discount, 2); ?></span>
            </div>
            <div class="price-row">
                <span class="label">Platform Fee</span>
                <span class="value">₹<?php echo number_format($platform_fee, 2); ?></span>
            </div>
            <div class="price-row total">
                <span class="label">Total Amount</span>
                <span class="value">₹<?php echo number_format($grand_total, 2); ?></span>
            </div>

            <div class="savings">
                <i class="fa-regular fa-circle-check"></i>
                You will save ₹<?php echo number_format($total_savings, 2); ?> on this order
            </div>

            <button class="btn-place-order" onclick="alert('Proceed to checkout!')">
                <i class="fa-solid fa-bolt"></i> PLACE ORDER
            </button>

            <div class="secure-note">
                <i class="fa-regular fa-circle-check"></i>
                Safe and Secure Payments.<br>Easy returns. 100% Authentic products.
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ======== FOOTER ======== -->
<footer class="footer" style="margin-top:40px;">
    <div class="footer-container" style="max-width:1200px; margin:0 auto; padding:40px 20px; display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:30px;">
        <div class="footer-box">
            <h3>Quick Basket</h3>
            <p style="color:#ccc; line-height:1.7;">Your trusted online shopping destination for fashion, electronics, home essentials and much more.</p>
        </div>
        <div class="footer-box">
            <h3>Quick Links</h3>
            <ul style="list-style:none;">
                <li style="margin-bottom:8px;"><a href="index.php" style="color:#ccc; text-decoration:none;">Home</a></li>
                <li style="margin-bottom:8px;"><a href="categories.php" style="color:#ccc; text-decoration:none;">Categories</a></li>
                <li style="margin-bottom:8px;"><a href="#" style="color:#ccc; text-decoration:none;">Offers</a></li>
            </ul>
        </div>
        <div class="footer-box">
            <h3>Customer Support</h3>
            <ul style="list-style:none;">
                <li style="margin-bottom:8px;"><a href="#" style="color:#ccc; text-decoration:none;">Contact Us</a></li>
                <li style="margin-bottom:8px;"><a href="#" style="color:#ccc; text-decoration:none;">FAQ</a></li>
            </ul>
        </div>
        <div class="footer-box">
            <h3>Follow Us</h3>
            <div style="display:flex; gap:12px;">
                <a href="#" style="width:40px; height:40px; background:#2874f0; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; text-decoration:none;"><i class="fab fa-facebook-f"></i></a>
                <a href="#" style="width:40px; height:40px; background:#2874f0; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; text-decoration:none;"><i class="fab fa-instagram"></i></a>
                <a href="#" style="width:40px; height:40px; background:#2874f0; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; text-decoration:none;"><i class="fab fa-twitter"></i></a>
                <a href="#" style="width:40px; height:40px; background:#2874f0; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; text-decoration:none;"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
    </div>
    <div style="border-top:1px solid rgba(255,255,255,0.1); text-align:center; padding:20px; color:#ccc;">
        <p>© 2026 Quick Basket. All Rights Reserved.</p>
    </div>
</footer>

</body>
</html>