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

// ---------- CART & WISHLIST ----------
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;

// ---------- GET CATEGORY FILTER ----------
$selected_slug = isset($_GET['category']) ? trim($_GET['category']) : '';

// ---------- FETCH ALL CATEGORIES ----------
$all_cat_sql = "SELECT slug, name FROM product_categories ORDER BY name";
$all_cat_result = mysqli_query($conn, $all_cat_sql);
$all_categories = [];
while ($row = mysqli_fetch_assoc($all_cat_result)) {
    $all_categories[] = $row;
}

// ---------- FETCH PRODUCTS ----------
$product_where = "p.status = 'active'";
$params = [];
$types = "";

if (!empty($selected_slug)) {
    $cat_id_sql = "SELECT id FROM product_categories WHERE slug = ?";
    $cat_id_stmt = mysqli_prepare($conn, $cat_id_sql);
    mysqli_stmt_bind_param($cat_id_stmt, "s", $selected_slug);
    mysqli_stmt_execute($cat_id_stmt);
    $cat_id_result = mysqli_stmt_get_result($cat_id_stmt);
    if ($cat_row = mysqli_fetch_assoc($cat_id_result)) {
        $category_id = $cat_row['id'];
        $product_where .= " AND p.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    mysqli_stmt_close($cat_id_stmt);
}

$product_sql = "SELECT p.*, s.store_name 
                FROM products p 
                LEFT JOIN sellers s ON p.seller_id = s.id 
                WHERE $product_where 
                ORDER BY p.id DESC 
                LIMIT 12";

$stmt = mysqli_prepare($conn, $product_sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$product_result = mysqli_stmt_get_result($stmt);

// ---------- GET CATEGORY NAME ----------
$selected_category_name = 'All Products';
if (!empty($selected_slug)) {
    $name_sql = "SELECT name FROM product_categories WHERE slug = ?";
    $name_stmt = mysqli_prepare($conn, $name_sql);
    mysqli_stmt_bind_param($name_stmt, "s", $selected_slug);
    mysqli_stmt_execute($name_stmt);
    $name_result = mysqli_stmt_get_result($name_stmt);
    if ($name_row = mysqli_fetch_assoc($name_result)) {
        $selected_category_name = $name_row['name'];
    }
    mysqli_stmt_close($name_stmt);
}



// ---------- CATEGORY ICONS ----------
$category_icons = [
    'Mobiles' => 'fa-solid fa-mobile-screen',
    'Laptops' => 'fa-solid fa-laptop',
    'Fashion' => 'fa-solid fa-shirt',
    'Watches' => 'fa-solid fa-clock',
    'Audio' => 'fa-solid fa-headphones',
    'Gaming' => 'fa-solid fa-gamepad',
    'Furniture' => 'fa-solid fa-couch',
    'Jewellery' => 'fa-solid fa-gem',
    'Books' => 'fa-solid fa-book',
    'Electronics' => 'fa-solid fa-microchip',
    'Beauty' => 'fa-solid fa-wand-magic-sparkles',
    'Sports' => 'fa-solid fa-football',
    'Home' => 'fa-solid fa-house',
    'Appliances' => 'fa-solid fa-blender',
    'Automotive' => 'fa-solid fa-car',
    'Toys' => 'fa-solid fa-robot',
    'Grocery' => 'fa-solid fa-basket-shopping',
    'Health' => 'fa-solid fa-heart-pulse'
];

mysqli_close($conn);
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
    </style>
</head>
<body>

<!-- ======== HEADER ======== -->
<header class="top-header">
    <div class="logo">
        <h1>Quick<span>Basket</span></h1>
    </div>
    <!-- ======== SEARCH BAR (WORKING) ======== -->
<form action="search-results.php" method="GET" class="search-box">
    <i class="fa-solid fa-magnifying-glass search-icon"></i>
    <input type="text" name="query" placeholder="Search for Products, Brands and More..." autocomplete="off">
    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> SEARCH</button>
</form>
    <div class="header-icons">
        <?php if ($is_logged_in): ?>
            <a href="dashboard.php"><i class="fa-regular fa-user"></i> <?php echo $user_name; ?></a>
            <!-- <a href="logout.php" style="color:#f87171;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a> -->
        <?php else: ?>
            <a href="login.php"><i class="fa-regular fa-user"></i> Login</a>
        <?php endif; ?>
        <a href="wishlist.php"><i class="fa-regular fa-heart"></i> Wishlist <span class="badge"><?php echo $wishlist_count; ?></span></a>
        <a href="cart.php">
            <i class="fa-solid fa-cart-shopping"></i> Cart
            <span class="badge"><?php echo $cart_count; ?></span>
        </a>
    </div>
</header>

<!-- ======== CATEGORY NAV ======== -->
<!-- <nav class="category-nav">
    <div class="category-nav-inner">
        <a href="index.php" class="all-link <?php echo empty($selected_slug) ? 'active' : ''; ?>">
            <i class="fa-solid fa-th-large"></i> All
        </a>
        <?php foreach ($all_categories as $cat): ?>
            <a href="index.php?category=<?php echo $cat['slug']; ?>" 
               class="<?php echo ($selected_slug == $cat['slug']) ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($cat['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</nav> -->

<!-- ======== FLIPKART-STYLE CATEGORY BAR ======== -->
<div class="category-bar">
    <div class="category-bar-inner">
        <?php foreach ($all_categories as $cat): 
            $icon = $category_icons[$cat['name']] ?? 'fa-regular fa-tag';
        ?>
            <a href="index.php?category=<?php echo $cat['slug']; ?>" 
               class="category-bar-item <?php echo ($selected_slug == $cat['slug']) ? 'active' : ''; ?>">
                <i class="<?php echo $icon; ?>"></i>
                <span><?php echo htmlspecialchars($cat['name']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- ============================================ -->
<!-- ======== PROFESSIONAL MULTI-SLIDE BANNER ======== -->
<!-- ============================================ -->
<section class="hero-banner">
    <div class="hero-wrapper">
        <div class="hero-slider" id="heroSlider">
            
            <!-- Slide 1: Mega Sale -->
            <div class="hero-slide active">
                <div class="hero-badge">🔥 Limited Time Offer</div>
                <div class="hero-grid">
                    <div class="hero-left">
                        <span class="hero-subtitle">MEGA SALE</span>
                        <h1 class="hero-title">THE BIG <br><span>BILLION DAYS</span></h1>
                        <div class="hero-offer">
                            <span class="offer-number">70%</span>
                            <span class="offer-text">OFF</span>
                        </div>
                        <p class="hero-desc">On Fashion, Electronics, Mobiles,<br>Home & Living and More</p>
                        <div class="hero-buttons">
                            <a href="categories.php" class="btn-shop-now"><span>SHOP NOW</span> <i class="fa-solid fa-arrow-right"></i></a>
                            <a href="category-products.php" class="btn-explore"><i class="fa-regular fa-play-circle"></i> Explore More</a>
                        </div>
                    </div>
                    <div class="hero-right">
                        <div class="floating-image img-1"><img src="images/mobile phone.jpg" alt="Mobile"><span class="price-tag">₹11,999*</span></div>
                        <div class="floating-image img-2"><img src="images/laptop.png" alt="Laptop"><span class="price-tag">₹59,999*</span></div>
                        <div class="floating-image img-3"><img src=images/whatch.jpg alt="Watch"><span class="price-tag">₹2,499*</span></div>
                        <div class="floating-image img-4"><img src=images/shoes.jpg alt="Shoes"><span class="price-tag">₹1,799*</span></div>
                        <div class="hero-glow"></div>
                    </div>
                </div>
            </div>

            <!-- Slide 2: Electronics Festival -->
            <div class="hero-slide">
                <div class="hero-badge">🎉 Tech Festival</div>
                <div class="hero-grid">
                    <div class="hero-left">
                        <span class="hero-subtitle">ELECTRONICS SALE</span>
                        <h1 class="hero-title">UP TO <span>60% OFF</span><br>ON LAPTOPS</h1>
                        <div class="hero-offer">
                            <span class="offer-number">60%</span>
                            <span class="offer-text">OFF</span>
                        </div>
                        <p class="hero-desc">Premium Laptops, Accessories & More<br>Student Special Offers Available</p>
                        <div class="hero-buttons">
                            <a href="category-products.php?slug=laptops" class="btn-shop-now"><span>SHOP NOW</span> <i class="fa-solid fa-arrow-right"></i></a>
                            <a href="#" class="btn-explore"><i class="fa-regular fa-play-circle"></i> Learn More</a>
                        </div>
                    </div>
                    <div class="hero-right">
                        <div class="floating-image img-1"><img src="images/laptop.png" alt="Laptop"><span class="price-tag">₹39,999*</span></div>
                        <div class="floating-image img-2"><img src="images/headphone.png" alt="Headphone"><span class="price-tag">₹1,499*</span></div>
                        <div class="floating-image img-3"><img src="images/mobile phone.jpg" alt="Mobile"><span class="price-tag">₹15,999*</span></div>
                        <div class="hero-glow"></div>
                    </div>
                </div>
            </div>

            <!-- Slide 3: Fashion Sale -->
            <div class="hero-slide">
                <div class="hero-badge">👗 Fashion Week</div>
                <div class="hero-grid">
                    <div class="hero-left">
                        <span class="hero-subtitle">FASHION SALE</span>
                        <h1 class="hero-title"><span>STYLE</span> YOUR<br>DREAM LOOK</h1>
                        <div class="hero-offer">
                            <span class="offer-number">50%</span>
                            <span class="offer-text">OFF</span>
                        </div>
                        <p class="hero-desc">Latest Trends, Premium Quality<br>Mens, Women & Kids Collection</p>
                        <div class="hero-buttons">
                            <a href="category-products.php?slug=fashion" class="btn-shop-now"><span>SHOP NOW</span> <i class="fa-solid fa-arrow-right"></i></a>
                            <a href="#" class="btn-explore"><i class="fa-regular fa-play-circle"></i> Explore Collection</a>
                        </div>
                    </div>
                    <div class="hero-right">
                        <div class="floating-image img-1"><img src=images/shoes.jpg alt="Shoes"><span class="price-tag">₹1,299*</span></div>
                        <div class="floating-image img-2"><img src=images/fashion.png alt="Fashion"><span class="price-tag">₹799*</span></div>
                        <div class="floating-image img-3"><img src=images/whatch.jpg alt="Watch"><span class="price-tag">₹1,999*</span></div>
                        <div class="hero-glow"></div>
                    </div>
                </div>
            </div>

            <!-- Slide 4: Home & Living -->
            <div class="hero-slide">
                <div class="hero-badge">🏠 Home Special</div>
                <div class="hero-grid">
                    <div class="hero-left">
                        <span class="hero-subtitle">HOME & LIVING</span>
                        <h1 class="hero-title">MAKE YOUR<br><span>HOME BEAUTIFUL</span></h1>
                        <div class="hero-offer">
                            <span class="offer-number">40%</span>
                            <span class="offer-text">OFF</span>
                        </div>
                        <p class="hero-desc">Furniture, Decor, Kitchenware<br>& Home Essentials</p>
                        <div class="hero-buttons">
                            <a href="category-products.php?slug=home" class="btn-shop-now"><span>SHOP NOW</span> <i class="fa-solid fa-arrow-right"></i></a>
                            <a href="#" class="btn-explore"><i class="fa-regular fa-play-circle"></i> Explore More</a>
                        </div>
                    </div>
                    <div class="hero-right">
                        <div class="floating-image img-1"><img src=images/farniture.png alt="Furniture"><span class="price-tag">₹9,999*</span></div>
                        <div class="floating-image img-2"><img src=images/couch.png alt="couch"><span class="price-tag">₹14,999*</span></div>
                        <div class="floating-image img-3"><img src=images/light.png alt="Light"><span class="price-tag">₹499*</span></div>
                        <div class="hero-glow"></div>
                    </div>
                </div>
            </div>

            <!-- Slide 5: Gadget Fest -->
            <div class="hero-slide">
                <div class="hero-badge">⚡ Gadget Fest</div>
                <div class="hero-grid">
                    <div class="hero-left">
                        <span class="hero-subtitle">GADGET FEST</span>
                        <h1 class="hero-title"><span>TECH</span> UP YOUR<br>LIFESTYLE</h1>
                        <div class="hero-offer">
                            <span class="offer-number">55%</span>
                            <span class="offer-text">OFF</span>
                        </div>
                        <p class="hero-desc">Smartphones, Audio, Wearables<br>& Gaming Accessories</p>
                        <div class="hero-buttons">
                            <a href="category-products.php?slug=gaming" class="btn-shop-now"><span>SHOP NOW</span> <i class="fa-solid fa-arrow-right"></i></a>
                            <a href="#" class="btn-explore"><i class="fa-regular fa-play-circle"></i> Explore More</a>
                        </div>
                    </div>
                    <div class="hero-right">
                        <div class="floating-image img-1"><img src="images/mobile phone.jpg" alt="Mobile"><span class="price-tag">₹10,999*</span></div>
                        <div class="floating-image img-2"><img src=images/headphone.png alt="Headphone"><span class="price-tag">₹1,299*</span></div>
                        <div class="floating-image img-3"><img src=images/gaming.png alt="Gaming"><span class="price-tag">₹2,499*</span></div>
                        <div class="hero-glow"></div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Slider Controls -->
        <div class="hero-controls">
            <button class="slider-btn prev" onclick="changeSlide(-1)">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="slider-dots" id="sliderDots"></div>
            <button class="slider-btn next" onclick="changeSlide(1)">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>

<!-- ======== FEATURED PRODUCTS ======== -->
<section class="featured-products">
    <div class="section-heading">
        <h2>
            <?php if (!empty($selected_slug)): ?>
                <?php echo htmlspecialchars($selected_category_name); ?>
            <?php else: ?>
                Featured <span>Products</span>
            <?php endif; ?>
        </h2>
        <a href="categories.php">View All</a>
    </div>
    <div class="products-grid">
        <?php if ($product_result && mysqli_num_rows($product_result) > 0): ?>
            <?php while ($product = mysqli_fetch_assoc($product_result)): ?>
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
            <?php endwhile; ?>
        <?php else: ?>
            <p style="grid-column:1/-1; text-align:center; color:#94a3b8; padding:40px;">No products available.</p>
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
            <img src=images/mobile phone.jpg alt="Mobile">
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
            <div class="feature-icon"><i class="fa-solid fa-truck-fast"></i></div>
            <h3>Fast Delivery</h3>
            <p>Get your orders delivered quickly and safely.</p>
        </div>
        <div class="feature-box">
            <div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <h3>Secure Payment</h3>
            <p>100% secure and trusted payment methods.</p>
        </div>
        <div class="feature-box">
            <div class="feature-icon"><i class="fa-solid fa-rotate-left"></i></div>
            <h3>Easy Returns</h3>
            <p>Simple return and refund process.</p>
        </div>
        <div class="feature-box">
            <div class="feature-icon"><i class="fa-solid fa-headset"></i></div>
            <h3>24/7 Support</h3>
            <p>Our support team is always ready to help.</p>
        </div>
    </div>
</section>

<!-- ======== TESTIMONIALS ======== -->
<section class="testimonials">
    <div class="section-heading center-heading">
        <h2>What Our Customers Say</h2>
        <p>Trusted by thousands of happy shoppers.</p>
    </div>
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
            <p>Your trusted online shopping destination.</p>
        </div>
        <div class="footer-box">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="deals.php">Best Deals</a></li>
            </ul>
        </div>
        <div class="footer-box">
            <h3>Customer Support</h3>
            <ul>
                <li><a href="#">Contact Us</a></li>
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

<script>
    // ---------- HERO SLIDER ----------
    let currentSlide = 0;
    const slides = document.querySelectorAll('.hero-slide');
    let dotsContainer = document.getElementById('sliderDots');
    let autoSlideInterval;

    function goToSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.remove('active');
            if (dotsContainer && dotsContainer.children[i]) {
                dotsContainer.children[i].classList.remove('active');
            }
        });

        slides[index].classList.add('active');
        if (dotsContainer && dotsContainer.children[index]) {
            dotsContainer.children[index].classList.add('active');
        }
        currentSlide = index;
    }

    function changeSlide(direction) {
        let newIndex = currentSlide + direction;
        if (newIndex < 0) newIndex = slides.length - 1;
        if (newIndex >= slides.length) newIndex = 0;
        goToSlide(newIndex);
        resetAutoSlide();
    }

    function goToSlideByDot(index) {
        goToSlide(index);
        resetAutoSlide();
    }

    function resetAutoSlide() {
        clearInterval(autoSlideInterval);
        autoSlideInterval = setInterval(() => {
            changeSlide(1);
        }, 5000);
    }

    // Initialize dots
    if (dotsContainer) {
        slides.forEach((slide, index) => {
            const dot = document.createElement('span');
            dot.className = 'dot' + (index === 0 ? ' active' : '');
            dot.onclick = function() { goToSlideByDot(index); };
            dotsContainer.appendChild(dot);
        });
    }

    // Start auto-slide
    resetAutoSlide();

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') changeSlide(-1);
        if (e.key === 'ArrowRight') changeSlide(1);
    });

    // Pause on hover
    const slider = document.querySelector('.hero-wrapper');
    if (slider) {
        slider.addEventListener('mouseenter', function() {
            clearInterval(autoSlideInterval);
        });
        slider.addEventListener('mouseleave', resetAutoSlide);
    }
</script>

</body>
</html>