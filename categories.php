<?php
session_start();
require_once 'config.php';

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

// Fetch categories with product counts
$cat_sql = "SELECT c.*, COUNT(p.id) AS product_count 
            FROM product_categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
            GROUP BY c.id
            ORDER BY c.name";
$cat_result = mysqli_query($conn, $cat_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Categories - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .categories-page {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .categories-page h1 {
            font-size: 32px;
            color: #222;
            margin-bottom: 8px;
        }
        .categories-page .subtitle {
            color: #888;
            margin-bottom: 30px;
        }
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 24px;
        }
        .category-card {
            background: #fff;
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            transition: 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            border: 1px solid #eee;
            text-decoration: none;
            color: #333;
        }
        .category-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            border-color: #2874f0;
        }
        .category-card .icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 14px;
            background: #eef4ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #2874f0;
            transition: 0.3s;
        }
        .category-card:hover .icon {
            background: #2874f0;
            color: #fff;
        }
        .category-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .category-card .count {
            font-size: 14px;
            color: #888;
        }
        .empty-msg {
            text-align: center;
            padding: 60px 20px;
            color: #888;
            grid-column: 1/-1;
        }
        .empty-msg i {
            font-size: 60px;
            display: block;
            margin-bottom: 16px;
            color: #ddd;
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
    <div class="category-nav-inner" style="max-width:1200px; margin:0 auto; padding:0 20px; display:flex; gap:20px; overflow-x:auto;">
        <a href="index.php">All Categories</a>
        <a href="categories.php" class="active" style="color:#2874f0; border-bottom:2px solid #2874f0;">Browse All</a>
    </div>
</nav>

<div class="categories-page">
    <h1><i class="fa-regular fa-folder-open"></i> All Categories</h1>
    <p class="subtitle">Browse products by category – find what you love!</p>
    <div class="categories-grid">
        <?php if ($cat_result && mysqli_num_rows($cat_result) > 0): ?>
            <?php while ($cat = mysqli_fetch_assoc($cat_result)): ?>
                <a href="category-products.php?slug=<?php echo $cat['slug']; ?>" class="category-card">
                    <div class="icon"><i class="fa-regular fa-tag"></i></div>
                    <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                    <div class="count"><?php echo $cat['product_count']; ?> products</div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-msg">
                <i class="fa-regular fa-folder-open"></i>
                <h3>No categories yet</h3>
                <p>Categories will appear here once added by the admin.</p>
            </div>
        <?php endif; ?>
    </div>
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