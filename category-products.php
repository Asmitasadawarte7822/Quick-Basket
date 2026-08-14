<?php
session_start();
require_once 'config.php';

// ---------- GET CATEGORY SLUG ----------
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// ---------- FETCH CATEGORY ----------
$cat_sql = "SELECT id, name FROM product_categories WHERE slug = ? OR name = ?";
$cat_stmt = mysqli_prepare($conn, $cat_sql);
mysqli_stmt_bind_param($cat_stmt, "ss", $slug, $slug);
mysqli_stmt_execute($cat_stmt);
$cat_result = mysqli_stmt_get_result($cat_stmt);
$category = mysqli_fetch_assoc($cat_result);

if (!$category) {
    header('Location: index.php');
    exit;
}
mysqli_stmt_close($cat_stmt);

$category_id = $category['id'];
$category_name = $category['name'];

// ---------- PAGINATION ----------
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Count total products in category
$count_sql = "SELECT COUNT(*) AS total FROM products WHERE category_id = ? AND status = 'active'";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, "i", $category_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_row = mysqli_fetch_assoc($count_result);
$total_products = $total_row['total'];
$total_pages = ceil($total_products / $limit);
mysqli_stmt_close($count_stmt);

// Fetch products in this category
$product_sql = "SELECT p.*, s.store_name 
                FROM products p 
                LEFT JOIN sellers s ON p.seller_id = s.id 
                WHERE p.category_id = ? AND p.status = 'active' 
                ORDER BY p.id DESC 
                LIMIT ? OFFSET ?";
$product_stmt = mysqli_prepare($conn, $product_sql);
mysqli_stmt_bind_param($product_stmt, "iii", $category_id, $limit, $offset);
mysqli_stmt_execute($product_stmt);
$product_result = mysqli_stmt_get_result($product_stmt);

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

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category_name); ?> - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        .category-hero {
            background: linear-gradient(135deg, #1a56db, #3b82f6);
            padding: 40px 24px;
            border-radius: 16px;
            margin: 20px auto 30px;
            max-width: 1280px;
            text-align: center;
            color: #fff;
        }
        .category-hero h1 {
            font-size: 36px;
            font-weight: 700;
        }
        .category-hero p {
            opacity: 0.8;
            margin-top: 6px;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 24px;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }
        .product-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            transition: 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.04);
        }
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border-color: #1a56db;
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
            transition: 0.3s;
        }
        .product-card:hover .image-wrap img { transform: scale(1.04); }
        .product-card .info {
            padding: 18px 20px 20px;
        }
        .product-card .info h3 {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-card .info .seller {
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 4px;
        }
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
            color: #1a56db;
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
            background: #1a56db;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.3s;
        }
        .add-to-cart-btn:hover { background: #2563eb; transform: scale(1.02); }
        .add-to-cart-btn:disabled {
            background: #e5e7eb;
            color: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
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
        .btn-view:hover { background: #eff6ff; color: #1a56db; }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            padding: 30px 0 20px;
            flex-wrap: wrap;
        }
        .pagination a {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            transition: 0.3s;
        }
        .pagination a.active {
            background: #1a56db;
            color: #fff;
            border-color: #1a56db;
        }
        .pagination a:hover:not(.active) { background: #f1f5f9; }
        .empty-msg { text-align: center; padding: 60px 20px; color: #94a3b8; }
        .empty-msg i { font-size: 60px; display: block; margin-bottom: 16px; color: #d1d5db; }
        .empty-msg h3 { font-size: 22px; color: #1e293b; }
        .header-actions .cart-link { position: relative; }
        .cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ef4444;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 50%;
            min-width: 20px;
            text-align: center;
        }
        @media (max-width: 768px) {
            .products-grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; padding: 0 12px; }
            .category-hero { padding: 30px 16px; }
            .category-hero h1 { font-size: 28px; }
        }
        @media (max-width: 576px) {
            .products-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .product-card .info { padding: 14px; }
            .product-card .info h3 { font-size: 13px; }
            .product-card .info .price-wrap .current { font-size: 16px; }
            .product-card .image-wrap { min-height: 130px; padding: 14px; }
            .product-card .image-wrap img { max-height: 110px; }
        }
    </style>
</head>
<body>

<!-- ======== HEADER ======== -->
<header class="top-header">
    <div class="header-inner">
        <div class="logo">
            <h1>Quick<span>Basket</span></h1>
        </div>
        <div class="search-box">
            <input type="text" placeholder="Search for products, brands and more...">
            <button><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        </div>
        <div class="header-actions">
            <?php if ($is_logged_in): ?>
                <a href="dashboard.php"><i class="fa-regular fa-user"></i> <?php echo $user_name; ?></a>
                <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="login.php"><i class="fa-regular fa-user"></i> Login</a>
            <?php endif; ?>
            <a href="#"><i class="fa-regular fa-heart"></i> Wishlist</a>
            <a href="cart.php" class="cart-link">
                <i class="fa-solid fa-bag-shopping"></i> Cart
                <span class="cart-badge"><?php echo $cart_count; ?></span>
            </a>
        </div>
    </div>
</header>

<!-- ======== CATEGORY NAV ======== -->
<nav class="category-nav">
    <div class="category-nav-inner">
        <a href="index.php">All Categories</a>
        <a href="category-products.php?slug=electronics" <?php echo ($slug == 'electronics') ? 'class="active"' : ''; ?>><i class="fa-solid fa-laptop"></i> Electronics</a>
        <a href="category-products.php?slug=fashion" <?php echo ($slug == 'fashion') ? 'class="active"' : ''; ?>><i class="fa-solid fa-shirt"></i> Fashion</a>
        <a href="category-products.php?slug=mobiles" <?php echo ($slug == 'mobiles') ? 'class="active"' : ''; ?>><i class="fa-solid fa-mobile-screen"></i> Mobiles</a>
        <a href="category-products.php?slug=home" <?php echo ($slug == 'home') ? 'class="active"' : ''; ?>><i class="fa-solid fa-house"></i> Home</a>
        <a href="category-products.php?slug=beauty" <?php echo ($slug == 'beauty') ? 'class="active"' : ''; ?>><i class="fa-solid fa-wand-magic-sparkles"></i> Beauty</a>
        <a href="category-products.php?slug=appliances" <?php echo ($slug == 'appliances') ? 'class="active"' : ''; ?>><i class="fa-solid fa-blender"></i> Appliances</a>
        <a href="category-products.php?slug=sports" <?php echo ($slug == 'sports') ? 'class="active"' : ''; ?>><i class="fa-solid fa-football"></i> Sports</a>
        <a href="category-products.php?slug=books" <?php echo ($slug == 'books') ? 'class="active"' : ''; ?>><i class="fa-solid fa-book"></i> Books</a>
    </div>
</nav>

<!-- ======== CATEGORY HERO ======== -->
<div class="category-hero">
    <h1><?php echo htmlspecialchars($category_name); ?></h1>
    <p>Discover our collection of <?php echo htmlspecialchars($category_name); ?> products at best prices.</p>
</div>

<!-- ======== PRODUCTS ======== -->
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
        <div class="empty-msg" style="grid-column:1/-1;">
            <i class="fa-regular fa-box-open"></i>
            <h3>No products in this category</h3>
            <p>Check back later for new arrivals!</p>
            <a href="index.php" style="color:#1a56db; text-decoration:none; font-weight:600;">← Continue Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?slug=<?php echo urlencode($slug); ?>&page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<!-- ======== FOOTER ======== -->
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-box"><h3>Quick Basket</h3><p>Your trusted online shopping destination.</p></div>
        <div class="footer-box"><h3>Quick Links</h3><ul><li><a href="index.php">Home</a></li><li><a href="#">Shop</a></li><li><a href="#">Categories</a></li></ul></div>
        <div class="footer-box"><h3>Customer Support</h3><ul><li><a href="#">Contact Us</a></li><li><a href="#">FAQ</a></li></ul></div>
        <div class="footer-box"><h3>Follow Us</h3><div class="footer-social"><a href="#"><i class="fab fa-facebook-f"></i></a><a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-twitter"></i></a><a href="#"><i class="fab fa-linkedin-in"></i></a></div></div>
    </div>
    <div class="footer-bottom"><p>© 2026 Quick Basket. All Rights Reserved.</p></div>
</footer>

</body>
</html>