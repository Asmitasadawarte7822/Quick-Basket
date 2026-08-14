<?php
session_start();
require_once 'config.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch product details
$sql = "SELECT p.*, s.store_name, s.email as seller_email, s.phone as seller_phone 
        FROM products p 
        LEFT JOIN sellers s ON p.seller_id = s.id 
        WHERE p.id = $product_id AND p.status = 'active'";
$result = mysqli_query($conn, $sql);
if (mysqli_num_rows($result) == 0) {
    header('Location: index.php');
    exit;
}
$product = mysqli_fetch_assoc($result);

// Get logged-in user info
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .product-detail-wrap {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
            display: flex;
            gap: 40px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            padding: 30px;
        }

        .product-detail-image {
            flex: 1;
            text-align: center;
        }

        .product-detail-image img {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
        }

        .product-detail-info {
            flex: 1;
        }

        .product-detail-info h1 {
            font-size: 28px;
            color: #222;
            margin-bottom: 10px;
        }

        .product-detail-info .price {
            font-size: 28px;
            font-weight: 700;
            color: #2874f0;
        }

        .product-detail-info .old-price {
            text-decoration: line-through;
            color: #999;
            font-size: 18px;
            margin-left: 10px;
        }

        .product-detail-info .seller {
            margin: 15px 0;
            color: #555;
        }

        .product-detail-info .seller strong {
            color: #222;
        }

        .product-detail-info .description {
            margin: 20px 0;
            color: #555;
            line-height: 1.8;
        }

        .product-detail-info .stock {
            font-weight: 600;
            color: #27ae60;
        }

        .product-detail-info .stock.out {
            color: #e74c3c;
        }

        .btn-add-cart {
            background: #2874f0;
            color: #fff;
            border: none;
            padding: 14px 40px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-add-cart:hover {
            background: #0052cc;
        }

        .btn-add-cart:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #2874f0;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .product-detail-wrap {
                flex-direction: column;
                padding: 20px;
            }

            .product-detail-image img {
                max-height: 250px;
            }
        }
    </style>
</head>

<body>

    <!-- Header (reuse) -->
    <header class="top-header" style="background:#2874f0; padding:14px 4%; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
        <div class="logo">
            <h1 style="color:#fff; font-size:34px;">Quick<span style="color:#ffd700;">Basket</span></h1>
        </div>
        <div style="color:#fff; display:flex; gap:20px;">
            <a href="index.php" style="color:#fff; text-decoration:none;"><i class="fa-solid fa-house"></i> Home</a>
            <a href="cart.php" style="color:#fff; text-decoration:none;"><i class="fa-solid fa-cart-shopping"></i> Cart</a>
            <?php if ($is_logged_in): ?>
                <a href="logout.php" style="color:#ffd700; text-decoration:none;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="login.php" style="color:#fff; text-decoration:none;"><i class="fa-regular fa-user"></i> Login</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Product Detail -->
    <div class="product-detail-wrap">
        <div class="product-detail-image">
            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        <div class="product-detail-info">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <div class="price">
                ₹<?php echo number_format($product['price'], 2); ?>
                <?php if ($product['price'] > 1000): ?>
                    <span class="old-price">₹<?php echo number_format($product['price'] * 1.2, 2); ?></span>
                <?php endif; ?>
            </div>
            <div class="seller">
                <strong>Seller:</strong> <?php echo htmlspecialchars($product['store_name'] ?? 'Quick Basket'); ?>
                <?php if (!empty($product['seller_phone'])): ?>
                    <span style="margin-left:15px; color:#888;">📞 <?php echo htmlspecialchars($product['seller_phone']); ?></span>
                <?php endif; ?>
            </div>
            <div class="stock <?php echo ($product['stock'] > 0) ? '' : 'out'; ?>">
                <?php echo ($product['stock'] > 0) ? '✅ In Stock' : '❌ Out of Stock'; ?>
                <span style="color:#888; font-weight:normal;">(<?php echo $product['stock']; ?> units available)</span>
            </div>
            <div class="description">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>

            <form action="add-to-cart.php" method="POST">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="btn-add-cart" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-cart-plus"></i> Add to Cart
                </button>
            </form>

            <a href="index.php" class="back-link">← Continue Shopping</a>
        </div>
    </div>

    <!-- Footer (reuse) -->
    <footer class="footer" style="margin-top:40px;">
        <div class="footer-container">
            <div class="footer-box">
                <h3>Quick Basket</h3>
                <p>Your trusted online shopping destination.</p>
            </div>
            <div class="footer-box">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#">Shop</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2026 Quick Basket. All Rights Reserved.</p>
        </div>
    </footer>

</body>

</html>