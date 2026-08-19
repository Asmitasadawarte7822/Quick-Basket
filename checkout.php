<?php
session_start();
require_once 'config.php';

// ---------- USER LOGIN CHECK ----------
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ---------- GET PRODUCT FOR BUY NOW ----------
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($product_id > 0) {
    // Buy Now - single product
    $sql = "SELECT p.*, s.store_name 
            FROM products p 
            LEFT JOIN sellers s ON p.seller_id = s.id 
            WHERE p.id = ? AND p.status = 'active'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    
    if ($product) {
        $product['quantity'] = $quantity;
        $product['subtotal'] = $product['price'] * $quantity;
        $cart_items = [$product];
        $total = $product['subtotal'];
        $is_cart_checkout = false;
    } else {
        header('Location: index.php');
        exit;
    }
} else {
    // Cart Checkout - multiple products
    $cart_items = [];
    $total = 0;
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $ids = array_keys($_SESSION['cart']);
        $ids_str = implode(',', $ids);
        $sql = "SELECT p.*, s.store_name 
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
        $is_cart_checkout = true;
    } else {
        header('Location: cart.php');
        exit;
    }
}

// ---------- FETCH USER ADDRESSES ----------
$addresses = [];
$addr_sql = "SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC";
$stmt = mysqli_prepare($conn, $addr_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$addr_result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($addr_result)) {
    $addresses[] = $row;
}
mysqli_stmt_close($stmt);

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ============================================
           CHECKOUT PAGE
           ============================================ */
        .checkout-wrap {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        /* ---------- Checkout Form ---------- */
        .checkout-form {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .checkout-form h2 {
            font-size: 22px;
            color: #222;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }
        .checkout-form h2 i {
            color: #2874f0;
        }
        .checkout-form .section {
            margin-bottom: 24px;
        }
        .checkout-form .section h3 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
        }
        .checkout-form .section h3 i {
            color: #2874f0;
            margin-right: 8px;
        }
        .form-group {
            margin-bottom: 14px;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
            font-size: 14px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2874f0;
            outline: none;
            box-shadow: 0 0 0 3px rgba(40,116,240,0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        /* ---------- Address Selection ---------- */
        .address-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 12px;
        }
        .address-options .address-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border: 1px solid #eee;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }
        .address-options .address-option:hover {
            border-color: #2874f0;
            background: #f8faff;
        }
        .address-options .address-option input[type="radio"] {
            accent-color: #2874f0;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .address-options .address-option .address-text {
            font-size: 14px;
            color: #333;
        }
        .address-options .address-option .address-text .default-badge {
            background: #27ae60;
            color: #fff;
            padding: 1px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }
        .address-options .address-option .address-text .name {
            font-weight: 600;
        }

        /* ---------- Order Summary ---------- */
        .order-summary {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .order-summary h3 {
            font-size: 18px;
            color: #222;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
            margin-bottom: 16px;
        }
        .order-summary .item {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .order-summary .item img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            background: #f8f9fa;
            border-radius: 6px;
            padding: 4px;
        }
        .order-summary .item .info {
            flex: 1;
        }
        .order-summary .item .info .name {
            font-size: 14px;
            font-weight: 500;
            color: #222;
        }
        .order-summary .item .info .qty {
            font-size: 13px;
            color: #888;
        }
        .order-summary .item .price {
            font-weight: 600;
            color: #2874f0;
        }
        .order-summary .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 14px;
            color: #555;
        }
        .order-summary .total-row.grand-total {
            border-top: 2px solid #2874f0;
            padding-top: 14px;
            margin-top: 6px;
            font-size: 20px;
            font-weight: 700;
            color: #222;
        }
        .order-summary .total-row.grand-total .value {
            color: #2874f0;
        }
        .btn-place-order {
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
            margin-top: 12px;
        }
        .btn-place-order:hover {
            background: #e55a12;
        }
        .btn-place-order:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .btn-place-order i {
            margin-right: 8px;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 992px) {
            .checkout-wrap {
                grid-template-columns: 1fr;
            }
            .order-summary {
                position: static;
            }
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .checkout-form {
                padding: 16px;
            }
            .order-summary {
                padding: 16px;
            }
        }
    </style>
</head>
<body>

<!-- ======== HEADER ======== -->
<header class="top-header" style="background:#2874f0; padding:14px 4%; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
    <div class="logo">
        <a href="index.php" style="color:#fff; text-decoration:none; font-size:30px; font-weight:700;">
            Quick<span style="color:#ffd700;">Basket</span>
        </a>
    </div>
    <div style="color:#fff; display:flex; gap:20px; align-items:center; flex-wrap:wrap;">
        <a href="index.php" style="color:#fff; text-decoration:none;"><i class="fa-solid fa-house"></i> Home</a>
        <a href="cart.php" style="color:#fff; text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to Cart</a>
        <a href="logout.php" style="color:#ffd700; text-decoration:none;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </div>
</header>

<!-- ======== CHECKOUT CONTENT ======== -->
<div class="checkout-wrap">

    <!-- Checkout Form -->
    <div class="checkout-form">
        <h2><i class="fa-regular fa-credit-card"></i> Checkout</h2>

        <!-- Address Section -->
        <div class="section">
            <h3><i class="fa-solid fa-location-dot"></i> Shipping Address</h3>
            
            <?php if (!empty($addresses)): ?>
                <div class="address-options">
                    <?php foreach ($addresses as $addr): ?>
                        <label class="address-option">
                            <input type="radio" name="address_id" value="<?php echo $addr['id']; ?>" <?php echo ($addr['is_default'] == 1) ? 'checked' : ''; ?>>
                            <div class="address-text">
                                <span class="name"><?php echo htmlspecialchars($addr['full_name']); ?></span>
                                <?php if ($addr['is_default'] == 1): ?>
                                    <span class="default-badge">Default</span>
                                <?php endif; ?>
                                <br>
                                <?php echo htmlspecialchars($addr['address']); ?>,
                                <?php echo htmlspecialchars($addr['city']); ?>,
                                <?php echo htmlspecialchars($addr['state']); ?> -
                                <?php echo htmlspecialchars($addr['pincode']); ?>
                                <br>
                                <span style="color:#888; font-size:13px;">📞 <?php echo htmlspecialchars($addr['phone']); ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="margin-top:12px;">
                <a href="#" onclick="toggleNewAddress()" style="color:#2874f0; text-decoration:none; font-weight:500;">
                    <i class="fa-solid fa-plus"></i> Add New Address
                </a>
            </div>

            <!-- New Address Form (hidden by default) -->
            <div id="newAddressForm" style="display:none; margin-top:14px; padding:16px; background:#f8f9fa; border-radius:8px;">
                <h4 style="margin-bottom:12px;">Add New Address</h4>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" id="full_name" placeholder="Enter full name">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" id="phone" placeholder="Enter phone number">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" id="address" placeholder="Enter full address"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" id="city" placeholder="City">
                    </div>
                    <div class="form-group">
                        <label>State</label>
                        <input type="text" name="state" id="state" placeholder="State">
                    </div>
                </div>
                <div class="form-group">
                    <label>Pincode</label>
                    <input type="text" name="pincode" id="pincode" placeholder="Pincode">
                </div>
                <button type="button" onclick="saveAddress()" class="btn-place-order" style="background:#2874f0; padding:10px;">
                    <i class="fa-regular fa-floppy-disk"></i> Save Address
                </button>
            </div>
        </div>

        <!-- Payment Section -->
        <div class="section">
            <h3><i class="fa-regular fa-credit-card"></i> Payment Method</h3>
            <div class="form-group">
                <select name="payment_method" id="payment_method">
                    <option value="COD">Cash on Delivery</option>
                    <option value="UPI">UPI (Google Pay, PhonePe, Paytm)</option>
                    <option value="Card">Credit / Debit Card</option>
                    <option value="Net Banking">Net Banking</option>
                </select>
            </div>
        </div>

        <button type="button" onclick="placeOrder()" class="btn-place-order" id="placeOrderBtn">
            <i class="fa-solid fa-bolt"></i> Place Order
        </button>
    </div>

    <!-- Order Summary -->
    <div class="order-summary">
        <h3>Order Summary</h3>
        
        <?php foreach ($cart_items as $item): ?>
            <div class="item">
                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                <div class="info">
                    <div class="name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="qty">Qty: <?php echo $item['quantity']; ?></div>
                </div>
                <div class="price">₹<?php echo number_format($item['subtotal'], 2); ?></div>
            </div>
        <?php endforeach; ?>

        <div class="total-row">
            <span>Subtotal</span>
            <span>₹<?php echo number_format($total, 2); ?></span>
        </div>
        <div class="total-row">
            <span>Shipping</span>
            <span>₹0.00</span>
        </div>
        <div class="total-row">
            <span>Discount</span>
            <span style="color:#27ae60;">-₹0.00</span>
        </div>
        <div class="total-row grand-total">
            <span>Grand Total</span>
            <span class="value">₹<?php echo number_format($total, 2); ?></span>
        </div>
    </div>
</div>

<script>
    function toggleNewAddress() {
        const form = document.getElementById('newAddressForm');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }

    function saveAddress() {
        const full_name = document.getElementById('full_name').value;
        const phone = document.getElementById('phone').value;
        const address = document.getElementById('address').value;
        const city = document.getElementById('city').value;
        const state = document.getElementById('state').value;
        const pincode = document.getElementById('pincode').value;

        if (!full_name || !phone || !address || !city || !state || !pincode) {
            alert('Please fill all address fields.');
            return;
        }

        // Send AJAX request to save address
        fetch('save-address.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ full_name, phone, address, city, state, pincode })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Address saved successfully! Please refresh the page.');
                location.reload();
            } else {
                alert('Failed to save address: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error saving address.');
            console.error(error);
        });
    }

    function placeOrder() {
        const addressRadio = document.querySelector('input[name="address_id"]:checked');
        const paymentMethod = document.getElementById('payment_method').value;

        if (!addressRadio) {
            alert('Please select a shipping address.');
            return;
        }

        const addressId = addressRadio.value;

        // Check if it's a cart checkout or buy now
        const isCartCheckout = <?php echo $is_cart_checkout ? 'true' : 'false'; ?>;
        const productId = <?php echo $product_id ?? 0; ?>;
        const quantity = <?php echo $quantity ?? 1; ?>;

        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'place-order.php';

        const fields = {
            address_id: addressId,
            payment_method: paymentMethod,
            is_cart_checkout: isCartCheckout ? '1' : '0',
            product_id: productId,
            quantity: quantity
        };

        for (const [key, value] of Object.entries(fields)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
    }
</script>

<!-- ======== FOOTER ======== -->
<footer class="footer" style="margin-top:40px;">
    <div class="footer-container" style="max-width:1200px; margin:0 auto; padding:40px 20px; display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:30px;">
        <div class="footer-box">
            <h3>Quick Basket</h3>
            <p style="color:#ccc; line-height:1.7;">Your trusted online shopping destination.</p>
        </div>
        <div class="footer-box">
            <h3>Quick Links</h3>
            <ul style="list-style:none;">
                <li style="margin-bottom:8px;"><a href="index.php" style="color:#ccc; text-decoration:none;">Home</a></li>
                <li style="margin-bottom:8px;"><a href="deals.php" style="color:#ccc; text-decoration:none;">Best Deals</a></li>
                <li style="margin-bottom:8px;"><a href="categories.php" style="color:#ccc; text-decoration:none;">Categories</a></li>
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
            </div>
        </div>
    </div>
    <div style="border-top:1px solid rgba(255,255,255,0.1); text-align:center; padding:20px; color:#ccc;">
        <p>© 2026 Quick Basket. All Rights Reserved.</p>
    </div>
</footer>

</body>
</html>