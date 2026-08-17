<?php
session_start();
require_once 'config.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// Fetch category
$cat_sql = "SELECT id, name FROM product_categories WHERE slug = ?";
$stmt = mysqli_prepare($conn, $cat_sql);
mysqli_stmt_bind_param($stmt, "s", $slug);
mysqli_stmt_execute($stmt);
$cat_result = mysqli_stmt_get_result($stmt);
$category = mysqli_fetch_assoc($cat_result);
if (!$category) {
    header('Location: index.php');
    exit;
}
$category_id = $category['id'];
$category_name = $category['name'];

// User login
$user_name = null; $is_logged_in = false;
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
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

// Fetch products in this category
$product_sql = "SELECT p.*, s.store_name 
                FROM products p 
                LEFT JOIN sellers s ON p.seller_id = s.id 
                WHERE p.category_id = ? AND p.status = 'active' 
                ORDER BY p.id DESC";
$stmt = mysqli_prepare($conn, $product_sql);
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$product_result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category_name); ?> - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .category-hero {
            background: linear-gradient(135deg, #0052cc, #2874f0);
            padding: 40px 20px;
            border-radius: 16px;
            margin: 20px auto 30px;
            max-width: 1200px;
            text-align: center;
            color: #fff;
        }
        .category-hero h1 { font-size: 36px; font-weight: 700; }
        .category-hero p { opacity: 0.8; margin-top: 6px; }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 24px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .product-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            transition: 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            border: 1px solid #eee;
        }
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border-color: #2874f0;
        }
        .product-card .badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            z-index: 2;
        }
        .badge-in { background: #d1fae5; color: #065f46; }
        .badge-out { background: #fee2e2; color: #991b1b; }
        .product-card .image-wrap {
            background: #f8fafc;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 180px;
            position: relative;
        }
        .product-card .image-wrap img {
            max-width: 100%;
            max-height: 160px;
            object-fit: contain;
        }
        .product-card .info {
            padding: 18px 20px 20px;
        }
        .product-card .info h3 {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
            line-height: 1.3;
        }
        .product-card .info .seller { font-size: 12px; color: #94a3b8; }
        .product-card .info .rating { color: #f59e0b; font-size: 13px; margin-bottom: 6px; }
        .product-card .info .price-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .product-card .info .price-wrap .current {
            font-size: 20px;
            font-weight: 700;
            color: #2874f0;
        }
        .product-card .info .price-wrap .old {
            font-size: 14px;
            color: #94a3b8;
            text-decoration: line-through;
        }
        .add-to-cart-btn {
            width: 100%;
            padding: 11px;
            border: none;
            border-radius: 50px;
            background: #2874f0;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.3s;
        }
        .add-to-cart-btn:hover { background: #0052cc; }
        .add-to-cart-btn:disabled { background: #e5e7eb; color: #9ca3af; cursor: not-allowed; }
        .btn-view {
            display: block;
            text-align: center;
            margin-top: 8px;
            padding: 7px;
            border-radius: 50px;
            background: #f1f5f9;
            color: #475569;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: 0.3s;
        }
        .btn-view:hover { background: #eff6ff; color: #2874f0; }
        .empty-msg {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
            grid-column: 1/-1;
        }
        .empty-msg i { font-size: 60px; display: block; margin-bottom: 16px; color: #ddd; }
        .back-link { display: inline-block; margin-top: 20px; color: #2874f0; text-decoration: none; font-weight: 600; }
        .category-nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            gap: 20px;
            overflow-x: auto;
        }
        .category-nav-inner a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            font-size: 14px;
            padding: 5px 10px;
            white-space: nowrap;
            border-bottom: 2px solid transparent;
        }
        .category-nav-inner a:hover,
        .category-nav-inner a.active {
            color: #2874f0;
            border-bottom-color: #2874f0;
        }
    </style>
</head>
<body>

<header class="top-header">
    <div class="logo"><h1>Quick<span>Basket</span></h1></div>
    <div class="search-box">
        <input type="text" placeholder="Search for Products, Brands and More">
        <button>SEARCH</button>
    </div>
    <div class="header-icons">
        <?php if ($is_logged_in): ?>
            <a href="dashboard.php"><i class="fa-regular fa-user"></i> <?php echo $user_name; ?></a>
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        <?php else: ?>
            <a href="login.php"><i class="fa-regular fa-user"></i> Login</a>
        <?php endif; ?>
        <a href="wishlist.php"><i class="fa-regular fa-heart"></i> Wishlist</a>
        <a href="cart.php">
            <i class="fa-solid fa-cart-shopping"></i> Cart
            <span style="background:#ff3b30; color:#fff; border-radius:50%; padding:3px 7px; font-size:11px; margin-left:4px;"><?php echo $cart_count; ?></span>
        </a>
    </div>
</header>

<nav class="category-nav">
    <div class="category-nav-inner">
        <a href="index.php">All Categories</a>
        <a href="categories.php">Browse All</a>
        <?php
        $nav_sql = "SELECT slug, name FROM product_categories ORDER BY name";
        $nav_result = mysqli_query($conn, $nav_sql);
        while ($c = mysqli_fetch_assoc($nav_result)): ?>
            <a href="category-products.php?slug=<?php echo $c['slug']; ?>" <?php echo ($c['slug'] == $slug) ? 'class="active"' : ''; ?>>
                <?php echo htmlspecialchars($c['name']); ?>
            </a>
        <?php endwhile; ?>
    </div>
</nav>

<div class="category-hero">
    <h1><?php echo htmlspecialchars($category_name); ?></h1>
    <p>Discover our collection of <?php echo htmlspecialchars($category_name); ?> products at best prices.</p>
</div>

<div class="products-grid">
    <?php if ($product_result && mysqli_num_rows($product_result) > 0): ?>
        <?php while ($product = mysqli_fetch_assoc($product_result)): ?>
            <div class="product-card">
                <div class="badge <?php echo ($product['stock'] > 0) ? 'badge-in' : 'badge-out'; ?>">
                    <?php echo ($product['stock'] > 0) ? 'In Stock' : 'Out of Stock'; ?>
                </div>
                <div class="image-wrap">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                <div class="info">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div class="seller">by <?php echo htmlspecialchars($product['store_name'] ?? 'Quick Basket'); ?></div>
                    <div class="rating">★★★★★</div>
                    <div class="price-wrap">
                        <span class="current">₹<?php echo number_format($product['price'], 2); ?></span>
                        <?php if ($product['price'] > 1000): ?>
                            <span class="old">₹<?php echo number_format($product['price'] * 1.2, 2); ?></span>
                        <?php endif; ?>
                    </div>
                    <form action="add-to-cart.php" method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="add-to-cart-btn" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                            <i class="fa-solid fa-bag-plus"></i> Add to Cart
                        </button>
                    </form>
                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn-view">View Details</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-msg">
            <i class="fa-regular fa-box-open"></i>
            <h3>No products in this category</h3>
            <p>Check back later for new arrivals!</p>
            <a href="categories.php" class="back-link">← Browse Other Categories</a>
        </div>
    <?php endif; ?>
</div>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-box"><h3>Quick Basket</h3><p>Your trusted online shopping destination.</p></div>
        <div class="footer-box"><h3>Quick Links</h3><ul><li><a href="index.php">Home</a></li><li><a href="categories.php">Categories</a></li></ul></div>
        <div class="footer-box"><h3>Customer Support</h3><ul><li><a href="#">Contact Us</a></li><li><a href="#">FAQ</a></li></ul></div>
        <div class="footer-box"><h3>Follow Us</h3><div class="social-icons"><a href="#"><i class="fab fa-facebook-f"></i></a><a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-twitter"></i></a><a href="#"><i class="fab fa-linkedin-in"></i></a></div></div>
    </div>
    <div class="footer-bottom"><p>© 2026 Quick Basket. All Rights Reserved.</p></div>
</footer>

</body>
</html>