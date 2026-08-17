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
        WHERE p.id = ? AND p.status = 'active'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) == 0) {
    header('Location: index.php');
    exit;
}
$product = mysqli_fetch_assoc($result);

// Get logged-in user info
$is_logged_in = isset($_SESSION['user_id']);
$user_name = null;
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT name FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user_result = mysqli_stmt_get_result($stmt);
    if ($user = mysqli_fetch_assoc($user_result)) {
        $user_name = htmlspecialchars($user['name']);
    }
    mysqli_stmt_close($stmt);
}

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

// Check if product is in wishlist
$in_wishlist = false;
if ($is_logged_in && !empty($_SESSION['wishlist'])) {
    $in_wishlist = in_array($product_id, $_SESSION['wishlist']);
}

// Fetch category name
$cat_name = '';
if (!empty($product['category_id'])) {
    $cat_sql = "SELECT name FROM product_categories WHERE id = ?";
    $cat_stmt = mysqli_prepare($conn, $cat_sql);
    mysqli_stmt_bind_param($cat_stmt, "i", $product['category_id']);
    mysqli_stmt_execute($cat_stmt);
    $cat_result = mysqli_stmt_get_result($cat_stmt);
    if ($cat_row = mysqli_fetch_assoc($cat_result)) {
        $cat_name = $cat_row['name'];
    }
    mysqli_stmt_close($cat_stmt);
}

// Sample product images (for gallery)
$product_images = [
    $product['image'],
    'https://via.placeholder.com/400/2874f0/fff?text=View+1',
    'https://via.placeholder.com/400/2874f0/fff?text=View+2',
    'https://via.placeholder.com/400/2874f0/fff?text=View+3',
];

// Generate rating stars
$rating = 4.5;
$full_stars = floor($rating);
$half_star = ($rating - $full_stars) >= 0.5;
$empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
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
        /* ============================================
           PRODUCT DETAIL PAGE - PROFESSIONAL
           ============================================ */

        /* ---------- Container ---------- */
        .product-detail-wrap {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            background: #fff;
            border-radius: 12px;
            padding: 30px 30px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }

        /* ---------- Breadcrumb ---------- */
        .breadcrumb {
            grid-column: 1 / -1;
            font-size: 14px;
            color: #888;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
            margin-bottom: 10px;
        }
        .breadcrumb a {
            color: #2874f0;
            text-decoration: none;
        }
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        .breadcrumb .separator {
            margin: 0 6px;
            color: #ccc;
        }

        /* ---------- Left Column: Gallery ---------- */
        .product-gallery {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .main-image {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            border: 1px solid #eee;
            position: relative;
        }
        .main-image img {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
            transition: 0.3s;
        }
        .main-image img:hover {
            transform: scale(1.02);
        }
        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .thumbnail-grid .thumb {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 80px;
        }
        .thumbnail-grid .thumb:hover {
            border-color: #2874f0;
            background: #eef4ff;
        }
        .thumbnail-grid .thumb.active {
            border-color: #2874f0;
            background: #eef4ff;
            box-shadow: 0 0 0 3px rgba(40,116,240,0.2);
        }
        .thumbnail-grid .thumb img {
            max-width: 100%;
            max-height: 70px;
            object-fit: contain;
        }

        /* ---------- Right Column: Product Info ---------- */
        .product-info {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .product-info h1 {
            font-size: 26px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.3;
            margin: 0;
        }

        /* Rating Section */
        .product-info .rating-section {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .product-info .rating-section .stars {
            color: #fbbf24;
            font-size: 18px;
            letter-spacing: 1px;
        }
        .product-info .rating-section .rating-text {
            color: #2874f0;
            font-weight: 600;
            font-size: 15px;
        }
        .product-info .rating-section .review-count {
            color: #94a3b8;
            font-size: 14px;
        }
        .product-info .rating-section .category-tag {
            display: inline-block;
            background: #eef4ff;
            color: #2874f0;
            padding: 3px 14px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 500;
        }
        .product-info .rating-section .category-tag i {
            margin-right: 4px;
        }

        /* Price Section */
        .product-info .price-section {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            padding: 12px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        .product-info .price-section .current-price {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
        }
        .product-info .price-section .old-price {
            font-size: 20px;
            color: #94a3b8;
            text-decoration: line-through;
        }
        .product-info .price-section .discount {
            font-size: 18px;
            color: #27ae60;
            font-weight: 600;
        }

        /* Wishlist Button */
        .product-info .wishlist-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .product-info .wishlist-section .wishlist-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            border: 2px solid #2874f0;
            border-radius: 50px;
            background: #fff;
            color: #2874f0;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
        }
        .product-info .wishlist-section .wishlist-btn:hover {
            background: #2874f0;
            color: #fff;
        }
        .product-info .wishlist-section .wishlist-btn.in-wishlist {
            background: #2874f0;
            color: #fff;
        }
        .product-info .wishlist-section .wishlist-btn.in-wishlist:hover {
            background: #e74c3c;
            border-color: #e74c3c;
        }
        .product-info .wishlist-section .wishlist-btn i {
            font-size: 18px;
        }

        /* Seller Info */
        .product-info .seller-info {
            background: #f8f9fa;
            padding: 14px 18px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        .product-info .seller-info .seller-name {
            font-weight: 600;
            color: #1e293b;
        }
        .product-info .seller-info .seller-name i {
            color: #2874f0;
        }
        .product-info .seller-info .seller-phone {
            color: #555;
        }
        .product-info .seller-info .seller-phone i {
            color: #27ae60;
        }

        /* Stock Status */
        .product-info .stock-status {
            padding: 4px 0;
        }
        .product-info .stock-status .in-stock {
            color: #27ae60;
            font-weight: 600;
        }
        .product-info .stock-status .in-stock i {
            margin-right: 6px;
        }
        .product-info .stock-status .out-of-stock {
            color: #e74c3c;
            font-weight: 600;
        }
        .product-info .stock-status .out-of-stock i {
            margin-right: 6px;
        }

        /* Description */
        .product-info .description {
            color: #475569;
            line-height: 1.8;
            padding: 6px 0;
            font-size: 15px;
        }

        /* Quick Specs */
        .product-info .quick-specs {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            background: #f8f9fa;
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 14px;
        }
        .product-info .quick-specs span {
            color: #475569;
        }
        .product-info .quick-specs strong {
            color: #1e293b;
        }

        /* Action Buttons */
        .product-info .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 6px;
        }
        .product-info .action-buttons .btn-add-cart {
            flex: 1;
            min-width: 180px;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            background: #ff9f00;
            color: #fff;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .product-info .action-buttons .btn-add-cart:hover {
            background: #e68a00;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255,159,0,0.3);
        }
        .product-info .action-buttons .btn-add-cart:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .product-info .action-buttons .btn-buy-now {
            flex: 1;
            min-width: 180px;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            background: #fb641b;
            color: #fff;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .product-info .action-buttons .btn-buy-now:hover {
            background: #e55a12;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(251,100,27,0.3);
        }
        .product-info .action-buttons .btn-buy-now:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .product-info .back-link {
            display: inline-block;
            margin-top: 8px;
            color: #2874f0;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
        }
        .product-info .back-link:hover {
            text-decoration: underline;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 992px) {
            .product-detail-wrap {
                grid-template-columns: 1fr;
                padding: 20px;
            }
            .main-image {
                min-height: 300px;
            }
            .main-image img {
                max-height: 300px;
            }
        }
        @media (max-width: 576px) {
            .product-detail-wrap {
                padding: 15px;
                margin: 15px auto;
                gap: 20px;
            }
            .product-info h1 {
                font-size: 20px;
            }
            .product-info .price-section .current-price {
                font-size: 24px;
            }
            .product-info .action-buttons {
                flex-direction: column;
            }
            .product-info .action-buttons .btn-add-cart,
            .product-info .action-buttons .btn-buy-now {
                min-width: 100%;
            }
            .thumbnail-grid .thumb {
                min-height: 60px;
            }
            .thumbnail-grid .thumb img {
                max-height: 50px;
            }
            .product-info .quick-specs {
                flex-direction: column;
                gap: 8px;
            }
            .product-info .wishlist-section {
                justify-content: center;
            }
        }
        @media (max-width: 400px) {
            .product-info .price-section {
                flex-direction: column;
                align-items: flex-start;
            }
            .product-info .seller-info {
                flex-direction: column;
                text-align: center;
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
        <a href="wishlist.php" style="color:#fff; text-decoration:none; position:relative;">
            <i class="fa-regular fa-heart"></i> Wishlist
            <span style="background:#ff3b30; color:#fff; border-radius:50%; padding:2px 7px; font-size:11px; margin-left:4px;">
                <?php echo isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0; ?>
            </span>
        </a>
        <a href="cart.php" style="color:#fff; text-decoration:none; position:relative;">
            <i class="fa-solid fa-cart-shopping"></i> Cart
            <span style="background:#ff3b30; color:#fff; border-radius:50%; padding:2px 7px; font-size:11px; margin-left:4px;"><?php echo $cart_count; ?></span>
        </a>
    </div>
</header>

<!-- ======== PRODUCT DETAIL ======== -->
<div class="product-detail-wrap">

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="index.php">Home</a>
        <span class="separator">/</span>
        <?php if (!empty($cat_name)): ?>
            <a href="category-products.php?slug=<?php echo strtolower(str_replace(' ', '-', $cat_name)); ?>"><?php echo htmlspecialchars($cat_name); ?></a>
            <span class="separator">/</span>
        <?php endif; ?>
        <span><?php echo htmlspecialchars($product['name']); ?></span>
    </div>

    <!-- Left Column: Gallery -->
    <div class="product-gallery">
        <div class="main-image" id="mainImageContainer">
            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="mainImage">
        </div>
        <div class="thumbnail-grid">
            <?php foreach ($product_images as $index => $img): ?>
                <div class="thumb <?php echo $index === 0 ? 'active' : ''; ?>" onclick="changeImage('<?php echo htmlspecialchars($img); ?>', this)">
                    <img src="<?php echo htmlspecialchars($img); ?>" alt="Thumbnail <?php echo $index + 1; ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Right Column: Product Info -->
    <div class="product-info">
        <!-- Title -->
        <h1><?php echo htmlspecialchars($product['name']); ?></h1>

        <!-- Rating -->
        <div class="rating-section">
            <span class="stars">
                <?php
                for ($i = 0; $i < $full_stars; $i++) echo '★';
                if ($half_star) echo '☆';
                for ($i = 0; $i < $empty_stars; $i++) echo '☆';
                ?>
            </span>
            <span class="rating-text"><?php echo number_format($rating, 1); ?> ★</span>
            <span class="review-count">(358 reviews)</span>
            <span class="category-tag"><i class="fa-regular fa-tag"></i> <?php echo htmlspecialchars($cat_name ?: 'Uncategorized'); ?></span>
        </div>

        <!-- Price -->
        <div class="price-section">
            <span class="current-price">₹<?php echo number_format($product['price'], 2); ?></span>
            <?php if ($product['price'] > 1000): ?>
                <span class="old-price">₹<?php echo number_format($product['price'] * 1.25, 2); ?></span>
                <span class="discount">(<?php echo round((($product['price'] * 1.25 - $product['price']) / ($product['price'] * 1.25)) * 100); ?>% OFF)</span>
            <?php endif; ?>
        </div>

        <!-- Wishlist Button -->
        <div class="wishlist-section">
            <?php if ($in_wishlist): ?>
                <a href="wishlist.php?remove=<?php echo $product['id']; ?>" class="wishlist-btn in-wishlist">
                    <i class="fa-solid fa-heart"></i> Remove from Wishlist
                </a>
            <?php else: ?>
                <a href="wishlist.php?add=<?php echo $product['id']; ?>" class="wishlist-btn">
                    <i class="fa-regular fa-heart"></i> Add to Wishlist
                </a>
            <?php endif; ?>
        </div>

        <!-- Seller Info -->
        <div class="seller-info">
            <div>
                <span class="seller-name"><i class="fa-regular fa-store"></i> Seller: <?php echo htmlspecialchars($product['store_name'] ?? 'Quick Basket'); ?></span>
            </div>
            <?php if (!empty($product['seller_phone'])): ?>
                <div class="seller-phone">
                    <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($product['seller_phone']); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Stock Status -->
        <div class="stock-status">
            <?php if ($product['stock'] > 0): ?>
                <span class="in-stock"><i class="fa-regular fa-circle-check"></i> In Stock (<?php echo $product['stock']; ?> units available)</span>
            <?php else: ?>
                <span class="out-of-stock"><i class="fa-regular fa-circle-xmark"></i> Out of Stock</span>
            <?php endif; ?>
        </div>

        <!-- Description -->
        <div class="description">
            <?php echo nl2br(htmlspecialchars($product['description'] ?: 'No description available for this product.')); ?>
        </div>

        <!-- Quick Specs -->
        <div class="quick-specs">
            <span><strong>Category:</strong> <?php echo htmlspecialchars($cat_name ?: 'N/A'); ?></span>
            <span><strong>Stock:</strong> <?php echo $product['stock']; ?></span>
            <span><strong>Status:</strong> <?php echo ucfirst($product['status']); ?></span>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <form action="add-to-cart.php" method="POST" style="flex:1; min-width:180px;">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="btn-add-cart" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-cart-plus"></i> Add to Cart
                </button>
            </form>
            <button class="btn-buy-now" onclick="alert('Proceed to checkout!')" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                <i class="fa-solid fa-bolt"></i> Buy Now
            </button>
        </div>

        <a href="index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Continue Shopping</a>
    </div>
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

<script>
    function changeImage(src, element) {
        // Update main image
        document.getElementById('mainImage').src = src;
        
        // Update active thumbnail
        document.querySelectorAll('.thumb').forEach(function(thumb) {
            thumb.classList.remove('active');
        });
        element.classList.add('active');
    }
</script>

</body>
</html>