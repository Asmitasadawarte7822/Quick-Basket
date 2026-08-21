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

// ---------- CART COUNT ----------
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}

// ---------- FETCH CATEGORIES FOR NAV ----------
$nav_sql = "SELECT slug, name FROM product_categories ORDER BY name";
$nav_result = mysqli_query($conn, $nav_sql);

// ---------- FETCH FEATURED PRODUCTS ----------
$product_sql = "SELECT p.*, s.store_name 
                FROM products p 
                LEFT JOIN sellers s ON p.seller_id = s.id 
                WHERE p.status = 'active' 
                ORDER BY p.id DESC 
                LIMIT 8";
$product_result = mysqli_query($conn, $product_sql);
if (!$product_result) {
    $product_result = null;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .add-to-cart-btn {
            width: 100%;
            border: none;
            background: #2874f0;
            color: #fff;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }
        .add-to-cart-btn:hover {
            background: #0052cc;
        }
        .add-to-cart-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        /* Category nav styles - keep consistent with your design */
        .category-nav {
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
            padding: 10px 0;
            overflow-x: auto;
        }
        .category-nav-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            gap: 20px;
            flex-wrap: nowrap;
        }
        .category-nav-inner a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            font-size: 14px;
            padding: 5px 10px;
            white-space: nowrap;
            transition: 0.3s;
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

<!-- ======== HEADER ======== -->
<header class="top-header">
    <div class="logo">
        <h1>Quick<span>Basket</span></h1>
    </div>
    <div class="search-box">
        <input type="text" placeholder="Search for Products, Brands and More">
        <button>SEARCH</button>
    </div>
    <div class="header-icons">
        <?php if ($is_logged_in): ?>
            <a href="dashboard.php" style="display:flex; align-items:center; gap:6px;">
                <i class="fa-regular fa-user"></i> <?php echo $user_name; ?>
            </a>
            <!-- <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a> -->
        <?php else: ?>
            <a href="login.php"><i class="fa-regular fa-user"></i> Login</a>
        <?php endif; ?>
        <a href="wishlist.php"><i class="fa-regular fa-heart"></i> Wishlist</a>
        <a href="cart.php">
            <i class="fa-solid fa-cart-shopping"></i> Cart
            <span style="background:#ff3b30; color:#fff; border-radius:50%; padding:3px 7px; font-size:11px; margin-left:4px;">
                <?php echo $cart_count; ?>
            </span>
        </a>
    </div>
</header>

<!-- ======== CATEGORY NAV (DYNAMIC) ======== -->
<nav class="category-nav">
    <div class="category-nav-inner">
        <a href="index.php" class="active">All Categories</a>
        <a href="categories.php">Browse All</a>
        <?php if ($nav_result && mysqli_num_rows($nav_result) > 0): ?>
            <?php while ($cat = mysqli_fetch_assoc($nav_result)): ?>
                <a href="category-products.php?slug=<?php echo $cat['slug']; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</nav>

<!-- ======== HERO BANNER ======== -->
<section class="hero-banner">
    <button class="slider-btn left-btn"><i class="fa-solid fa-chevron-left"></i></button>
    <div class="hero-content">
        <div class="hero-text">
            <h4>MEGA SALE</h4>
            <h1>THE BIG <br>BILLION DAYS</h1>
            <h2>UP TO 70% OFF</h2>
            <p>On Fashion, Electronics, Mobiles, Home & Living and More</p>
            <a href="#" class="shop-now-btn">SHOP NOW</a>
        </div>
        <div class="hero-image">
            <img src="images/mobile.jpg" alt="Mobile">
            <img src="images/laptop.png" alt="Laptop">
            <img src="images/watch.png" alt="Watch">
            <img src="images/shoes.png" alt="Shoes">
        </div>
    </div>
    <button class="slider-btn right-btn"><i class="fa-solid fa-chevron-right"></i></button>
</section>

<!-- ======== TOP CATEGORIES (CLICKABLE) ======== -->
<section class="top-categories">
    <a href="category-products.php?slug=mobiles" class="category-circle">
        <div class="circle-icon"><i class="fa-solid fa-mobile-screen"></i></div>
        <h4>Mobiles</h4>
    </a>
    <a href="category-products.php?slug=laptops" class="category-circle">
        <div class="circle-icon"><i class="fa-solid fa-laptop"></i></div>
        <h4>Laptops</h4>
    </a>
    <a href="category-products.php?slug=fashion" class="category-circle">
        <div class="circle-icon"><i class="fa-solid fa-shirt"></i></div>
        <h4>Fashion</h4>
    </a>
    <a href="category-products.php?slug=watches" class="category-circle">
        <div class="circle-icon"><i class="fa-solid fa-clock"></i></div>
        <h4>Watches</h4>
    </a>
    <a href="category-products.php?slug=audio" class="category-circle">
        <div class="circle-icon"><i class="fa-solid fa-headphones"></i></div>
        <h4>Audio</h4>
    </a>
    <a href="category-products.php?slug=gaming" class="category-circle">
        <div class="circle-icon"><i class="fa-solid fa-gamepad"></i></div>
        <h4>Gaming</h4>
    </a>
    <a href="category-products.php?slug=furniture" class="category-circle">
        <div class="circle-icon"><i class="fa-solid fa-couch"></i></div>
        <h4>Furniture</h4>
    </a>
    <a href="category-products.php?slug=jewellery" class="category-circle">
        <div class="circle-icon"><i class="fa-solid fa-gem"></i></div>
        <h4>Jewellery</h4>
    </a>

    </section>




<!-- ======== FEATURED PRODUCTS ======== -->
<section class="featured-products">
    <div class="section-heading">
        <h2>Featured Products</h2>
        <a href="categories.php">View All</a>
    </div>
    <div class="products-grid">
        <?php if ($product_result && mysqli_num_rows($product_result) > 0): ?>
            <?php while ($product = mysqli_fetch_assoc($product_result)): ?>
                <div class="product-card">
                    <?php if ($product['stock'] > 0): ?>
                        <span class="discount-badge">In Stock</span>
                    <?php else: ?>
                        <span class="discount-badge" style="background:#e74c3c;">Out of Stock</span>
                    <?php endif; ?>
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
                        <small style="color:#888;">by <?php echo htmlspecialchars($product['store_name'] ?? 'Quick Basket'); ?></small>

                        <form action="add-to-cart.php" method="POST" style="margin-top:10px;">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="add-to-cart-btn" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-cart-plus"></i> Add to Cart
                            </button>
                        </form>

                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn-view" style="display:block; text-align:center; background:#f0f0f0; color:#333; padding:8px; border-radius:6px; margin-top:5px; text-decoration:none; font-size:13px;">
                            View Details
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="grid-column:1/-1; text-align:center; color:#888; padding:40px;">No products available at the moment.</p>
        <?php endif; ?>
    </div>
</section>

<!-- ======== BEST DEALS ======== -->
<section class="best-deals">
    <div class="section-heading">
        <h2>Best Deals For You</h2>
        <a href="deals.php">View All</a>
    </div>
    <div class="deals-container">
        <div class="deal-card deal-blue">
            <div class="deal-content">
                <h3>Smartphones</h3>
                <p>Up To 40% OFF</p>
                <a href="deals.php?category=mobiles">Shop Now</a>
            </div>
            <img src="images/mobile.png" alt="Mobile">
        </div>
        <div class="deal-card deal-orange">
            <div class="deal-content">
                <h3>Fashion Sale</h3>
                <p>Up To 70% OFF</p>
                <a href="deals.php?category=fashion">Explore</a>
            </div>
            <img src="images/fashion.png" alt="Fashion">
        </div>
        <div class="deal-card deal-green">
            <div class="deal-content">
                <h3>Laptops</h3>
                <p>Special Student Offers</p>
                <a href="deals.php?category=laptops">Buy Now</a>
            </div>
            <img src="images/laptop.png" alt="Laptop">
        </div>
    </div>
</section>

<!-- ======== WHY CHOOSE US ======== -->
<section class="why-choose-section">
    <div class="section-header-center">
        <h2>Why Choose <span>Quick Basket</span>?</h2>
        <p>We provide the best online shopping experience.</p>
    </div>
    <div class="features-grid">
        <div class="feature-box">
            <div class="feature-icon">
                <i class="fa-solid fa-truck-fast"></i>
            </div>
            <h3>Fast Delivery</h3>
            <p>Get your orders delivered quickly and safely.</p>
        </div>
        <div class="feature-box">
            <div class="feature-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h3>Secure Payment</h3>
            <p>100% secure and trusted payment methods.</p>
        </div>
        <div class="feature-box">
            <div class="feature-icon">
                <i class="fa-solid fa-rotate-left"></i>
            </div>
            <h3>Easy Returns</h3>
            <p>Simple return and refund process.</p>
        </div>
        <div class="feature-box">
            <div class="feature-icon">
                <i class="fa-solid fa-headset"></i>
            </div>
            <h3>24/7 Support</h3>
            <p>Our support team is always ready to help.</p>
        </div>
    </div>
</section>

<!-- ======== TESTIMONIALS ======== -->
<section class="testimonials">
    <div class="section-heading center-heading"><h2>What Our Customers Say</h2><p>Trusted by thousands of happy shoppers.</p></div>
    <div class="testimonial-container">
        <div class="testimonial-card"><img src="images/user1.jpg" alt="Customer"><h3>Rahul Sharma</h3><div class="stars">★★★★★</div><p>Amazing shopping experience. Fast delivery and excellent product quality.</p></div>
        <div class="testimonial-card"><img src="images/user2.jpg" alt="Customer"><h3>Priya Patel</h3><div class="stars">★★★★★</div><p>Great offers and secure payment system. Highly recommended.</p></div>
        <div class="testimonial-card"><img src="images/user3.jpg" alt="Customer"><h3>Amit Verma</h3><div class="stars">★★★★★</div><p>Best online shopping platform with excellent customer support.</p></div>
    </div>
</section>

<!-- ======== NEWSLETTER ======== -->
<section class="newsletter">
    <div class="newsletter-content">
        <h2>Subscribe To Our Newsletter</h2>
        <p>Get updates about new products, special offers and exclusive discounts.</p>
        <form class="newsletter-form" method="POST">
            <input type="email" name="email" placeholder="Enter Your Email Address" required>
            <button type="submit">Subscribe</button>
        </form>
    </div>
</section>

<!-- ======== FOOTER ======== -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-box">
            <h3>Quick Basket</h3>
            <p>Your trusted online shopping destination for fashion, electronics, home essentials and much more.</p>
        </div>
        <div class="footer-box">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="#">Shop</a></li>
                <li><a href="#">Offers</a></li>
            </ul>
        </div>
        <div class="footer-box">
            <h3>Customer Support</h3>
            <ul>
                <li><a href="#">Contact Us</a></li>
                <li><a href="#">Track Order</a></li>
                <li><a href="#">Returns</a></li>
                <li><a href="#">FAQ</a></li>
            </ul>
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