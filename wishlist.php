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

// ---------- SESSION WISHLIST ----------
// Initialize wishlist in session if not exists
if (!isset($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

// ---------- ADD TO WISHLIST ----------
if (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $product_id = (int)$_GET['add'];
    if (!in_array($product_id, $_SESSION['wishlist'])) {
        $_SESSION['wishlist'][] = $product_id;
        header('Location: wishlist.php?added=' . $product_id);
        exit;
    }
}

// ---------- REMOVE FROM WISHLIST ----------
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    if (($key = array_search($product_id, $_SESSION['wishlist'])) !== false) {
        unset($_SESSION['wishlist'][$key]);
        $_SESSION['wishlist'] = array_values($_SESSION['wishlist']); // Re-index
        header('Location: wishlist.php?removed=' . $product_id);
        exit;
    }
}

// ---------- MOVE TO CART ----------
if (isset($_GET['move_to_cart']) && is_numeric($_GET['move_to_cart'])) {
    $product_id = (int)$_GET['move_to_cart'];
    // Add to cart
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]++;
    } else {
        $_SESSION['cart'][$product_id] = 1;
    }
    // Remove from wishlist
    if (($key = array_search($product_id, $_SESSION['wishlist'])) !== false) {
        unset($_SESSION['wishlist'][$key]);
        $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
    }
    header('Location: wishlist.php?moved=1');
    exit;
}

// ---------- FETCH WISHLIST PRODUCTS ----------
$wishlist_items = [];
if (!empty($_SESSION['wishlist'])) {
    $ids_str = implode(',', $_SESSION['wishlist']);
    $sql = "SELECT p.*, s.store_name 
            FROM products p 
            LEFT JOIN sellers s ON p.seller_id = s.id 
            WHERE p.id IN ($ids_str) AND p.status = 'active'";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $wishlist_items[] = $row;
    }
}

$wishlist_count = count($wishlist_items);
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ---------- Wishlist Layout ---------- */
        .wishlist-wrap {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 24px;
        }

        /* ---------- Sidebar ---------- */
        .wishlist-sidebar {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            height: fit-content;
        }
        .wishlist-sidebar .user-section {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
            margin-bottom: 16px;
        }
        .wishlist-sidebar .user-section .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #2874f0;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
            text-transform: uppercase;
        }
        .wishlist-sidebar .user-section .user-name {
            font-weight: 600;
            color: #222;
        }
        .wishlist-sidebar .user-section .user-email {
            font-size: 13px;
            color: #888;
        }
        .wishlist-sidebar .menu-section {
            margin-bottom: 16px;
        }
        .wishlist-sidebar .menu-section .menu-title {
            font-size: 13px;
            font-weight: 600;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .wishlist-sidebar .menu-section a {
            display: block;
            padding: 8px 0;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }
        .wishlist-sidebar .menu-section a:hover {
            color: #2874f0;
        }
        .wishlist-sidebar .menu-section a.active {
            color: #2874f0;
            font-weight: 600;
        }
        .wishlist-sidebar .menu-section a i {
            width: 24px;
            margin-right: 8px;
            color: #888;
        }
        .wishlist-sidebar .menu-section a:hover i {
            color: #2874f0;
        }

        /* ---------- Wishlist Items ---------- */
        .wishlist-items-section {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .wishlist-items-section .wishlist-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        .wishlist-items-section .wishlist-header h2 {
            font-size: 22px;
            color: #222;
        }
        .wishlist-items-section .wishlist-header h2 span {
            color: #888;
            font-weight: 400;
            font-size: 16px;
        }
        .wishlist-items-section .wishlist-header .share-link {
            color: #2874f0;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }
        .wishlist-items-section .wishlist-header .share-link:hover {
            text-decoration: underline;
        }

        /* ---------- Wishlist Item ---------- */
        .wishlist-item {
            display: flex;
            gap: 20px;
            padding: 16px 0;
            border-bottom: 1px solid #f0f0f0;
            position: relative;
        }
        .wishlist-item:last-child {
            border-bottom: none;
        }
        .wishlist-item .item-image {
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
        .wishlist-item .item-image img {
            max-width: 100%;
            max-height: 80px;
            object-fit: contain;
        }
        .wishlist-item .item-details {
            flex: 1;
        }
        .wishlist-item .item-details .item-title {
            font-size: 16px;
            font-weight: 500;
            color: #222;
            margin-bottom: 2px;
        }
        .wishlist-item .item-details .item-title a {
            color: #222;
            text-decoration: none;
        }
        .wishlist-item .item-details .item-title a:hover {
            color: #2874f0;
        }
        .wishlist-item .item-details .item-seller {
            font-size: 13px;
            color: #888;
            margin-bottom: 2px;
        }
        .wishlist-item .item-details .item-seller i {
            color: #2874f0;
        }
        .wishlist-item .item-details .item-assured {
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
        .wishlist-item .item-details .item-price {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin: 4px 0 10px;
        }
        .wishlist-item .item-details .item-price .current {
            font-size: 20px;
            font-weight: 700;
            color: #222;
        }
        .wishlist-item .item-details .item-price .old {
            font-size: 14px;
            color: #888;
            text-decoration: line-through;
        }
        .wishlist-item .item-details .item-price .discount-badge {
            font-size: 13px;
            color: #27ae60;
            font-weight: 600;
        }
        .wishlist-item .item-details .item-actions {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }
        .wishlist-item .item-details .item-actions .action-btn {
            background: none;
            border: none;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: 0.3s;
            padding: 6px 16px;
            border-radius: 4px;
        }
        .wishlist-item .item-details .item-actions .action-btn.move-cart {
            background: #2874f0;
            color: #fff;
        }
        .wishlist-item .item-details .item-actions .action-btn.move-cart:hover {
            background: #0052cc;
        }
        .wishlist-item .item-details .item-actions .action-btn.remove-btn {
            color: #e74c3c;
            border: 1px solid #e74c3c;
            background: #fff;
        }
        .wishlist-item .item-details .item-actions .action-btn.remove-btn:hover {
            background: #e74c3c;
            color: #fff;
        }

        /* ---------- Empty Wishlist ---------- */
        .empty-wishlist {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }
        .empty-wishlist i {
            font-size: 80px;
            color: #ddd;
            display: block;
            margin-bottom: 20px;
        }
        .empty-wishlist h3 {
            font-size: 24px;
            color: #222;
            margin-bottom: 8px;
        }
        .empty-wishlist p {
            color: #888;
            margin-bottom: 20px;
        }
        .empty-wishlist .btn-shop {
            display: inline-block;
            padding: 12px 36px;
            background: #2874f0;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .empty-wishlist .btn-shop:hover {
            background: #0052cc;
        }

        /* ---------- Alert Messages ---------- */
        .alert {
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: #d5f5e3;
            color: #27ae60;
            border-left: 4px solid #27ae60;
        }
        .alert-success i {
            font-size: 18px;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 992px) {
            .wishlist-wrap {
                grid-template-columns: 1fr;
            }
            .wishlist-sidebar {
                display: none;
            }
        }
        @media (max-width: 768px) {
            .wishlist-item {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .wishlist-item .item-image {
                width: 80px;
                height: 80px;
            }
            .wishlist-item .item-details .item-actions {
                justify-content: center;
            }
            .wishlist-items-section .wishlist-header {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
        }
        @media (max-width: 576px) {
            .wishlist-items-section {
                padding: 16px;
            }
            .wishlist-item .item-details .item-title {
                font-size: 14px;
            }
            .wishlist-item .item-details .item-price .current {
                font-size: 17px;
            }
            .wishlist-item .item-details .item-actions .action-btn {
                padding: 4px 12px;
                font-size: 12px;
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
        <a href="wishlist.php" style="color:#ffd700; text-decoration:none; position:relative;">
            <i class="fa-regular fa-heart"></i> Wishlist
            <span style="background:#ff3b30; color:#fff; border-radius:50%; padding:2px 7px; font-size:11px; margin-left:4px;"><?php echo $wishlist_count; ?></span>
        </a>
        <a href="cart.php" style="color:#fff; text-decoration:none; position:relative;">
            <i class="fa-solid fa-cart-shopping"></i> Cart
            <span style="background:#ff3b30; color:#fff; border-radius:50%; padding:2px 7px; font-size:11px; margin-left:4px;"><?php echo $cart_count; ?></span>
        </a>
    </div>
</header>

<!-- ======== WISHLIST CONTENT ======== -->
<div class="wishlist-wrap">
    <!-- Sidebar -->
    <aside class="wishlist-sidebar">
        <div class="user-section">
            <div class="avatar"><?php echo $user_name ? strtoupper(substr($user_name, 0, 1)) : 'G'; ?></div>
            <div>
                <div class="user-name"><?php echo $user_name ?: 'Guest User'; ?></div>
                <div class="user-email"><?php echo $_SESSION['user_email'] ?? 'guest@email.com'; ?></div>
            </div>
        </div>

        <div class="menu-section">
            <div class="menu-title">My Orders</div>
            <a href="#"><i class="fa-regular fa-circle"></i> Account Settings</a>
            <a href="#"><i class="fa-regular fa-circle"></i> Profile Information</a>
            <a href="#"><i class="fa-regular fa-circle"></i> Manage Addresses</a>
            <a href="#"><i class="fa-regular fa-circle"></i> PAN Card Information</a>
        </div>

        <div class="menu-section">
            <div class="menu-title">Payments</div>
            <a href="#"><i class="fa-regular fa-circle"></i> Gift Cards ₹0</a>
            <a href="#"><i class="fa-regular fa-circle"></i> Saved UPI</a>
            <a href="#"><i class="fa-regular fa-circle"></i> Saved Cards</a>
        </div>

        <div class="menu-section">
            <div class="menu-title">My Stuff</div>
            <a href="#" class="active"><i class="fa-regular fa-heart"></i> My Wishlist</a>
            <a href="#"><i class="fa-regular fa-circle"></i> My Coupons</a>
        </div>
    </aside>

    <!-- Wishlist Items -->
    <div class="wishlist-items-section">
        <div class="wishlist-header">
            <h2>My Wishlist <span>(<?php echo $wishlist_count; ?> items)</span></h2>
            <a href="#" class="share-link"><i class="fa-regular fa-share-from-square"></i> Share</a>
        </div>

        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success">
                <i class="fa-regular fa-circle-check"></i>
                Product added to wishlist!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['removed'])): ?>
            <div class="alert alert-success">
                <i class="fa-regular fa-circle-check"></i>
                Product removed from wishlist!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['moved'])): ?>
            <div class="alert alert-success">
                <i class="fa-regular fa-circle-check"></i>
                Product moved to cart!
            </div>
        <?php endif; ?>

        <?php if (!empty($wishlist_items)): ?>
            <?php foreach ($wishlist_items as $item): ?>
                <?php
                $discount_percent = 20;
                $item_old_price = $item['price'] * 1.25;
                ?>
                <div class="wishlist-item">
                    <div class="item-image">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    </div>
                    <div class="item-details">
                        <div class="item-title">
                            <a href="product-details.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                        </div>
                        <div class="item-seller"><i class="fa-regular fa-store"></i> Seller: <?php echo htmlspecialchars($item['store_name'] ?? 'Quick Basket'); ?></div>
                        <span class="item-assured"><i class="fa-regular fa-circle-check"></i> Assured</span>
                        <div class="item-price">
                            <span class="current">₹<?php echo number_format($item['price'], 2); ?></span>
                            <span class="old">₹<?php echo number_format($item_old_price, 2); ?></span>
                            <span class="discount-badge"><?php echo $discount_percent; ?>% Off</span>
                        </div>
                        <div class="item-actions">
                            <a href="wishlist.php?move_to_cart=<?php echo $item['id']; ?>" class="action-btn move-cart">
                                <i class="fa-solid fa-cart-plus"></i> Move to Cart
                            </a>
                            <a href="wishlist.php?remove=<?php echo $item['id']; ?>" class="action-btn remove-btn" onclick="return confirm('Remove this item from wishlist?')">
                                <i class="fa-regular fa-trash-can"></i> Remove
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-wishlist">
                <i class="fa-regular fa-heart"></i>
                <h3>Your wishlist is empty</h3>
                <p>Save your favorite items here to buy them later.</p>
                <a href="index.php" class="btn-shop"><i class="fa-solid fa-arrow-left"></i> Continue Shopping</a>
            </div>
        <?php endif; ?>
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

</body>
</html>