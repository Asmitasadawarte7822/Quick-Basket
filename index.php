<?php
session_start();
require_once 'config.php'; // adjust path if needed

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
</head>

<body>

    <!-- Header -->
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
                <!-- Logged in: show user name + logout -->
                <a href="dashboard.php" style="display:flex; align-items:center; gap:6px;">
                    <i class="fa-regular fa-user"></i>
                    <?php echo $user_name; ?> 
                </a>
            <?php else: ?>
                <!-- Not logged in: show Login link -->
                <a href="login.php">
                    <i class="fa-regular fa-user"></i>
                    Login
                </a>
            <?php endif; ?>

            <a href="wishlist.php">
                <i class="fa-regular fa-heart"></i>
                Wishlist
            </a>

            <a href="cart.php">
                <i class="fa-solid fa-cart-shopping"></i>
                Cart
                <span style="background:#ff3b30; color:#fff; border-radius:50%; padding:3px 7px; font-size:11px; margin-left:4px;">
                    <!-- you can add cart count here -->
                </span>
            </a>

        </div>

    </header>

    <!-- ========== The rest of your page (unchanged) ========== -->

    <!-- Categories Menu -->
    <section class="categories-menu">
        <div class="category-item"><i class="fa-solid fa-bars"></i><span>All Categories</span></div>
        <div class="category-item"><i class="fa-solid fa-laptop"></i><span>Electronics</span></div>
        <div class="category-item"><i class="fa-solid fa-shirt"></i><span>Fashion</span></div>
        <div class="category-item"><i class="fa-solid fa-mobile-screen"></i><span>Mobiles</span></div>
        <div class="category-item"><i class="fa-solid fa-house"></i><span>Home & Living</span></div>
        <div class="category-item"><i class="fa-solid fa-wand-magic-sparkles"></i><span>Beauty</span></div>
        <div class="category-item"><i class="fa-solid fa-blender"></i><span>Appliances</span></div>
        <div class="category-item"><i class="fa-solid fa-football"></i><span>Sports</span></div>
        <div class="category-item"><i class="fa-solid fa-book"></i><span>Books</span></div>
    </section>

    <!-- Hero Banner -->
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
                <img src="images/mobile.png" alt="Mobile">
                <img src="images/laptop.png" alt="Laptop">
                <img src="images/watch.png" alt="Watch">
                <img src="images/shoes.png" alt="Shoes">
            </div>
        </div>
        <button class="slider-btn right-btn"><i class="fa-solid fa-chevron-right"></i></button>
    </section>

    <!-- Top Categories -->
    <section class="top-categories">
        <div class="category-circle"><div class="circle-icon"><i class="fa-solid fa-mobile-screen"></i></div><h4>Mobiles</h4></div>
        <div class="category-circle"><div class="circle-icon"><i class="fa-solid fa-laptop"></i></div><h4>Laptops</h4></div>
        <div class="category-circle"><div class="circle-icon"><i class="fa-solid fa-shirt"></i></div><h4>Fashion</h4></div>
        <div class="category-circle"><div class="circle-icon"><i class="fa-solid fa-clock"></i></div><h4>Watches</h4></div>
        <div class="category-circle"><div class="circle-icon"><i class="fa-solid fa-headphones"></i></div><h4>Audio</h4></div>
        <div class="category-circle"><div class="circle-icon"><i class="fa-solid fa-gamepad"></i></div><h4>Gaming</h4></div>
        <div class="category-circle"><div class="circle-icon"><i class="fa-solid fa-couch"></i></div><h4>Furniture</h4></div>
        <div class="category-circle"><div class="circle-icon"><i class="fa-solid fa-gem"></i></div><h4>Jewellery</h4></div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products">
        <div class="section-heading"><h2>Featured Products</h2><a href="checkout.php">View All</a></div>
        <div class="products-grid">
            <!-- Product 1 -->
            <div class="product-card">
                <span class="discount-badge">20% OFF</span>
                <img src="images/headphone.png" alt="Headphone">
                <div class="product-info">
                    <h3>Wireless Headphones</h3>
                    <div class="rating">★★★★★</div>
                    <div class="price"><span class="new-price">₹1,999</span><span class="old-price">₹2,499</span></div>
                    <button>Add To Cart</button>
                </div>
            </div>
            <!-- Product 2 -->
            <div class="product-card">
                <span class="discount-badge">30% OFF</span>
                <img src="images/watch.png" alt="Watch">
                <div class="product-info">
                    <h3>Smart Watch</h3>
                    <div class="rating">★★★★★</div>
                    <div class="price"><span class="new-price">₹2,499</span><span class="old-price">₹3,499</span></div>
                    <button>Add To Cart</button>
                </div>
            </div>
            <!-- Product 3 -->
            <div class="product-card">
                <span class="discount-badge">15% OFF</span>
                <img src="images/shoes.png" alt="Shoes">
                <div class="product-info">
                    <h3>Sports Shoes</h3>
                    <div class="rating">★★★★★</div>
                    <div class="price"><span class="new-price">₹1,799</span><span class="old-price">₹2,199</span></div>
                    <button>Add To Cart</button>
                </div>
            </div>
            <!-- Product 4 -->
            <div class="product-card">
                <span class="discount-badge">25% OFF</span>
                <img src="images/laptop.png" alt="Laptop">
                <div class="product-info">
                    <h3>Gaming Laptop</h3>
                    <div class="rating">★★★★★</div>
                    <div class="price"><span class="new-price">₹59,999</span><span class="old-price">₹79,999</span></div>
                    <button>Add To Cart</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Best Deals -->
    <section class="best-deals">
        <div class="section-heading"><h2>Best Deals For You</h2><a href="#">View All</a></div>
        <div class="deals-container">
            <div class="deal-card deal-blue">
                <div class="deal-content"><h3>Smartphones</h3><p>Up To 40% OFF</p><a href="product-details.php">Shop Now</a></div>
                <img src="images/mobile.png" alt="Mobile">
            </div>
            <div class="deal-card deal-orange">
                <div class="deal-content"><h3>Fashion Sale</h3><p>Up To 70% OFF</p><a href="#">Explore</a></div>
                <img src="images/fashion.png" alt="Fashion">
            </div>
            <div class="deal-card deal-green">
                <div class="deal-content"><h3>Laptops</h3><p>Special Student Offers</p><a href="#">Buy Now</a></div>
                <img src="images/laptop.png" alt="Laptop">
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="why-choose">
        <div class="section-heading center-heading"><h2>Why Choose Quick Basket?</h2><p>We provide the best online shopping experience.</p></div>
        <div class="features-container">
            <div class="feature-box"><i class="fa-solid fa-truck-fast"></i><h3>Fast Delivery</h3><p>Get your orders delivered quickly and safely.</p></div>
            <div class="feature-box"><i class="fa-solid fa-shield-halved"></i><h3>Secure Payment</h3><p>100% secure and trusted payment methods.</p></div>
            <div class="feature-box"><i class="fa-solid fa-rotate-left"></i><h3>Easy Returns</h3><p>Simple return and refund process.</p></div>
            <div class="feature-box"><i class="fa-solid fa-headset"></i><h3>24/7 Support</h3><p>Our support team is always ready to help.</p></div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials">
        <div class="section-heading center-heading"><h2>What Our Customers Say</h2><p>Trusted by thousands of happy shoppers.</p></div>
        <div class="testimonial-container">
            <div class="testimonial-card">
                <img src="images/user1.jpg" alt="Customer">
                <h3>Rahul Sharma</h3>
                <div class="stars">★★★★★</div>
                <p>Amazing shopping experience. Fast delivery and excellent product quality.</p>
            </div>
            <div class="testimonial-card">
                <img src="images/user2.jpg" alt="Customer">
                <h3>Priya Patel</h3>
                <div class="stars">★★★★★</div>
                <p>Great offers and secure payment system. Highly recommended.</p>
            </div>
            <div class="testimonial-card">
                <img src="images/user3.jpg" alt="Customer">
                <h3>Amit Verma</h3>
                <div class="stars">★★★★★</div>
                <p>Best online shopping platform with excellent customer support.</p>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
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

    <!-- Footer -->
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
                    <li><a href="#">Shop</a></li>
                    <li><a href="#">Categories</a></li>
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