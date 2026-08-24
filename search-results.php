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

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;

// ---------- GET SEARCH QUERY ----------
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// ---------- SEARCH PRODUCTS ----------
$search_results = [];
$total_results = 0;

if (!empty($query)) {
    $search_term = "%$query%";

    // Count total results
    $count_sql = "SELECT COUNT(*) AS total 
                  FROM products p 
                  LEFT JOIN sellers s ON p.seller_id = s.id 
                  WHERE p.status = 'active' 
                  AND (p.name LIKE ? OR p.description LIKE ? OR s.store_name LIKE ?)";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($count_stmt, "sss", $search_term, $search_term, $search_term);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total_row = mysqli_fetch_assoc($count_result);
    $total_results = $total_row['total'];
    mysqli_stmt_close($count_stmt);

    $total_pages = ceil($total_results / $limit);

    // Fetch search results
    $sql = "SELECT p.*, s.store_name 
            FROM products p 
            LEFT JOIN sellers s ON p.seller_id = s.id 
            WHERE p.status = 'active' 
            AND (p.name LIKE ? OR p.description LIKE ? OR s.store_name LIKE ?) 
            ORDER BY p.id DESC 
            LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssii", $search_term, $search_term, $search_term, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $search_results[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// ---------- FETCH CATEGORIES FOR NAV ----------
$nav_sql = "SELECT slug, name FROM product_categories ORDER BY name LIMIT 6";
$nav_result = mysqli_query($conn, $nav_sql);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background: #0a0e1a;
            color: #e8edf5;
            min-height: 100vh;
        }

        /* ---------- Header ---------- */
        .top-header {
            background: linear-gradient(135deg, #0a0a0a 0%, #0d1b2a 50%, #1a3a5c 100%);
            padding: 14px 4%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            border-bottom: 2px solid #2874f0;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
        }

        .logo h1 {
            color: #fff;
            font-size: 32px;
            font-weight: 800;
        }

        .logo span {
            color: #2874f0;
        }

        .search-box {
            flex: 1;
            display: flex;
            max-width: 600px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            overflow: hidden;
            transition: 0.3s;
            align-items: center;
            padding: 0 4px 0 18px;
        }

        .search-box:focus-within {
            border-color: #2874f0;
            box-shadow: 0 0 0 4px rgba(40, 116, 240, 0.15);
        }

        .search-box .search-icon {
            color: #94a3b8;
            font-size: 16px;
            margin-right: 8px;
        }

        .search-box input {
            flex: 1;
            padding: 12px 0;
            border: none;
            background: transparent;
            color: #fff;
            outline: none;
            font-size: 14px;
        }

        .search-box input::placeholder {
            color: #94a3b8;
        }

        .search-box button {
            padding: 10px 24px;
            border: none;
            background: #2874f0;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .search-box button:hover {
            background: #1a5bc7;
        }

        .header-icons {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .header-icons a {
            color: #94a3b8;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .header-icons a:hover {
            color: #2874f0;
        }

        .header-icons .badge {
            background: #2874f0;
            color: #fff;
            border-radius: 50%;
            padding: 2px 7px;
            font-size: 10px;
            font-weight: 700;
            margin-left: 4px;
        }

        /* ---------- Category Nav ---------- */
        .category-nav {
            background: rgba(10, 10, 10, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(40, 116, 240, 0.1);
            padding: 0;
        }

        .category-nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            gap: 4px;
            overflow-x: auto;
            align-items: center;
            min-height: 48px;
        }

        .category-nav-inner::-webkit-scrollbar {
            display: none;
        }

        .category-nav-inner a {
            text-decoration: none;
            color: #94a3b8;
            font-weight: 500;
            padding: 10px 16px;
            white-space: nowrap;
            font-size: 14px;
            border-bottom: 2px solid transparent;
            transition: 0.3s;
        }

        .category-nav-inner a:hover {
            color: #2874f0;
            border-bottom-color: #2874f0;
        }

        .category-nav-inner a.active {
            color: #2874f0;
            font-weight: 600;
            border-bottom-color: #2874f0;
        }

        /* ---------- Search Results ---------- */
        .search-results-page {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .search-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .search-header h2 {
            font-size: 24px;
            color: #fff;
        }

        .search-header h2 span {
            color: #2874f0;
        }

        .search-header p {
            color: #94a3b8;
            margin-top: 4px;
            font-size: 14px;
        }

        /* ---------- Products Grid ---------- */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            overflow: hidden;
            transition: 0.4s;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-4px);
            border-color: rgba(40, 116, 240, 0.3);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }

        .discount-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #2874f0;
            color: #fff;
            padding: 4px 12px;
            font-size: 11px;
            border-radius: 50px;
            z-index: 2;
            font-weight: 600;
        }

        .discount-badge.out {
            background: #e74c3c;
        }

        .product-card img {
            width: 100%;
            height: 180px;
            object-fit: contain;
            padding: 16px;
            background: rgba(0, 0, 0, 0.2);
        }

        .product-info {
            padding: 14px 16px 16px;
        }

        .product-info h3 {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 38px;
        }

        .product-info .rating {
            color: #fbbf24;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .product-info .price {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .product-info .price .new-price {
            color: #2874f0;
            font-size: 18px;
            font-weight: 700;
        }

        .product-info .price .old-price {
            text-decoration: line-through;
            color: #94a3b8;
            font-size: 13px;
        }

        .product-info .seller {
            font-size: 12px;
            color: #94a3b8;
            display: block;
            margin-bottom: 8px;
        }

        .product-info .add-to-cart-btn {
            width: 100%;
            border: none;
            background: #2874f0;
            color: #fff;
            padding: 8px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: 0.3s;
        }

        .product-info .add-to-cart-btn:hover {
            background: #1a5bc7;
        }

        .product-info .add-to-cart-btn:disabled {
            background: rgba(255, 255, 255, 0.1);
            color: #94a3b8;
            cursor: not-allowed;
        }

        .product-info .btn-view {
            display: block;
            text-align: center;
            background: rgba(255, 255, 255, 0.04);
            color: #94a3b8;
            padding: 6px;
            border-radius: 6px;
            margin-top: 6px;
            text-decoration: none;
            font-size: 12px;
            transition: 0.3s;
        }

        .product-info .btn-view:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        /* ---------- Empty State ---------- */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            grid-column: 1/-1;
        }

        .empty-state i {
            font-size: 80px;
            color: rgba(255, 255, 255, 0.06);
            display: block;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 28px;
            color: #fff;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #94a3b8;
            margin-bottom: 20px;
        }

        .empty-state .btn-shop {
            display: inline-block;
            padding: 12px 36px;
            background: #2874f0;
            color: #fff;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .empty-state .btn-shop:hover {
            background: #1a5bc7;
        }

        .empty-state .btn-shop i {
            margin-right: 8px;
        }

        /* ---------- Pagination ---------- */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            padding: 30px 0 10px;
            flex-wrap: wrap;
        }

        .pagination a {
            padding: 8px 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 6px;
            text-decoration: none;
            color: #94a3b8;
            transition: 0.3s;
        }

        .pagination a:hover {
            background: rgba(40, 116, 240, 0.1);
            color: #2874f0;
        }

        .pagination a.active {
            background: #2874f0;
            color: #fff;
            border-color: #2874f0;
        }

        .pagination a.disabled {
            opacity: 0.3;
            pointer-events: none;
        }

        /* ---------- Footer ---------- */
        .footer {
            background: linear-gradient(180deg, #0a0a0a, #0d1b2a);
            color: #fff;
            margin-top: 40px;
            border-top: 2px solid rgba(40, 116, 240, 0.15);
            padding: 40px 20px 20px;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 30px;
        }

        .footer-box h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .footer-box p,
        .footer-box a {
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.8;
            text-decoration: none;
            display: block;
            padding: 4px 0;
            transition: 0.3s;
        }

        .footer-box a:hover {
            color: #2874f0;
        }

        .social-icons {
            display: flex;
            gap: 12px;
        }

        .social-icons a {
            width: 38px;
            height: 38px;
            background: rgba(40, 116, 240, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2874f0;
            transition: 0.3s;
            border: 1px solid rgba(40, 116, 240, 0.05);
        }

        .social-icons a:hover {
            background: #2874f0;
            color: #fff;
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 992px) {
            .top-header {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                max-width: 100%;
            }

            .header-icons {
                justify-content: center;
                flex-wrap: wrap;
            }
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr 1fr;
                gap: 16px;
            }

            .search-header h2 {
                font-size: 20px;
            }
        }

        @media (max-width: 576px) {
            .products-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .product-card img {
                height: 130px;
                padding: 12px;
            }

            .product-info {
                padding: 10px 12px 12px;
            }

            .product-info h3 {
                font-size: 13px;
            }

            .product-info .price .new-price {
                font-size: 16px;
            }

            .search-box button span {
                display: none;
            }
        }
    </style>
</head>

<body>

    <!-- ======== HEADER ======== -->
    <header class="top-header">
        <div class="logo">
            <h1>Quick<span>Basket</span></h1>
        </div>
        <form action="search-results.php" method="GET" class="search-box">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" name="query" placeholder="Search for Products, Brands and More..." autocomplete="off">
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> SEARCH</button>
        </form>
        <div class="header-icons">
            <?php if ($is_logged_in): ?>
                <a href="dashboard.php"><i class="fa-regular fa-user"></i> <?php echo $user_name; ?></a>
                <a href="logout.php" style="color:#f87171;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="login.php"><i class="fa-regular fa-user"></i> Login</a>
            <?php endif; ?>
            <a href="wishlist.php"><i class="fa-regular fa-heart"></i> Wishlist <span class="badge"><?php echo $wishlist_count; ?></span></a>
            <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart <span class="badge"><?php echo $cart_count; ?></span></a>
        </div>
    </header>

    <!-- ======== CATEGORY NAV ======== -->
    <nav class="category-nav">
        <div class="category-nav-inner">
            <a href="index.php">Home</a>
            <a href="deals.php">Best Deals</a>
            <a href="categories.php">Categories</a>
            <a href="search-results.php" class="active">Search</a>
        </div>
    </nav>

    <!-- ======== SEARCH RESULTS ======== -->
    <div class="search-results-page">

        <div class="search-header">
            <h2>
                <i class="fa-solid fa-magnifying-glass" style="color:#2874f0;"></i>
                Search Results for "<span><?php echo htmlspecialchars($query); ?></span>"
            </h2>
            <p><?php echo $total_results; ?> products found</p>
        </div>

        <?php if (!empty($query)): ?>
            <?php if (!empty($search_results)): ?>
                <div class="products-grid">
                    <?php foreach ($search_results as $product): ?>
                        <div class="product-card">
                            <span class="discount-badge <?php echo ($product['stock'] > 0) ? '' : 'out'; ?>">
                                <?php echo ($product['stock'] > 0) ? 'In Stock' : 'Out of Stock'; ?>
                            </span>
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <div class="rating">★★★★★</div>
                                <div class="price">
                                    <span class="new-price">₹<?php echo number_format($product['price'], 2); ?></span>
                                    <?php if ($product['price'] > 1000): ?>
                                        <span class="old-price">₹<?php echo number_format($product['price'] * 1.2, 2); ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="seller">by <?php echo htmlspecialchars($product['store_name'] ?? 'Quick Basket'); ?></span>
                                <form action="add-to-cart.php" method="POST">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="add-to-cart-btn" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                        <i class="fa-solid fa-cart-plus"></i> Add to Cart
                                    </button>
                                </form>
                                <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn-view">
                                    View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?query=<?php echo urlencode($query); ?>&page=<?php echo $page - 1; ?>">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        <?php else: ?>
                            <a class="disabled"><i class="fa-solid fa-chevron-left"></i></a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?query=<?php echo urlencode($query); ?>&page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?query=<?php echo urlencode($query); ?>&page=<?php echo $page + 1; ?>">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <a class="disabled"><i class="fa-solid fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-regular fa-face-frown"></i>
                    <h3>No results found</h3>
                    <p>We couldn't find any products matching "<strong><?php echo htmlspecialchars($query); ?></strong>"</p>
                    <p style="font-size:14px; color:#64748b;">Try adjusting your search terms or browse our categories.</p>
                    <a href="categories.php" class="btn-shop"><i class="fa-regular fa-folder-open"></i> Browse Categories</a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-regular fa-magnifying-glass"></i>
                <h3>Search for products</h3>
                <p>Enter a search term above to find products.</p>
                <a href="categories.php" class="btn-shop"><i class="fa-regular fa-folder-open"></i> Browse Categories</a>
            </div>
        <?php endif; ?>

    </div>

    <!-- ======== FOOTER ======== -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-box">
                <h3>Quick Basket</h3>
                <p>Your trusted online shopping destination.</p>
            </div>
            <div class="footer-box">
                <h3>Quick Links</h3>
                <a href="index.php">Home</a>
                <a href="deals.php">Best Deals</a>
                <a href="categories.php">Categories</a>
            </div>
            <div class="footer-box">
                <h3>Customer Support</h3>
                <a href="#">Contact Us</a>
                <a href="#">FAQ</a>
            </div>
            <div class="footer-box">
                <h3>Follow Us</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2026 Quick Basket. All Rights Reserved.</p>
        </div>
    </footer>

</body>

</html>