<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$sql = "SELECT id, name, email, phone, status, created_at FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    $name       = htmlspecialchars($user['name']);
    $email      = htmlspecialchars($user['email']);
    $phone      = htmlspecialchars($user['phone']);
    $status     = htmlspecialchars($user['status']);
    $joined     = date('F j, Y', strtotime($user['created_at']));
} else {
    session_destroy();
    header('Location: login.php');
    exit;
}
mysqli_stmt_close($stmt);
mysqli_close($conn);

// Determine which section to show (default: profile)
$page = isset($_GET['page']) ? $_GET['page'] : 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* -------- Dashboard Layout -------- */
        .dashboard-wrap {
            display: flex;
            max-width: 1200px;
            margin: 30px auto;
            gap: 25px;
            padding: 0 20px;
        }

        /* -------- Sidebar -------- */
        .dashboard-sidebar {
            flex: 0 0 260px;
            background: #fff;
            border-radius: 12px;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .dashboard-sidebar .user-summary {
            padding: 0 20px 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
            text-align: center;
        }

        .dashboard-sidebar .user-summary .avatar {
            width: 70px;
            height: 70px;
            background: #2874f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: #fff;
            margin: 0 auto 10px;
            font-weight: 600;
        }

        .dashboard-sidebar .user-summary h3 {
            font-size: 18px;
            color: #222;
        }

        .dashboard-sidebar .user-summary p {
            color: #888;
            font-size: 14px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            padding: 12px 25px;
            cursor: pointer;
            transition: 0.2s;
            border-left: 3px solid transparent;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #333;
            font-size: 15px;
        }

        .sidebar-menu li i {
            width: 20px;
            color: #555;
            font-size: 16px;
        }

        .sidebar-menu li:hover {
            background: #f0f5ff;
            border-left-color: #2874f0;
        }

        .sidebar-menu li.active {
            background: #e6f0ff;
            border-left-color: #2874f0;
            font-weight: 600;
            color: #2874f0;
        }

        .sidebar-menu li.active i {
            color: #2874f0;
        }

        .sidebar-menu li.logout-item {
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 18px;
            color: #d32f2f;
        }

        .sidebar-menu li.logout-item i {
            color: #d32f2f;
        }

        /* -------- Main Content -------- */
        .dashboard-content {
            flex: 1;
            background: #fff;
            border-radius: 12px;
            padding: 30px 35px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            min-height: 500px;
        }

        .dashboard-content .section {
            display: none;
        }

        .dashboard-content .section.active {
            display: block;
        }

        .dashboard-content h2 {
            font-size: 26px;
            color: #222;
            margin-bottom: 25px;
            border-bottom: 2px solid #2874f0;
            padding-bottom: 12px;
            display: inline-block;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .info-item {
            background: aliceblue;
            padding: 18px 20px;
            border-radius: 8px;
            border-left: 4px solid #2874f0;
        }

        .info-item .label {
            font-size: 13px;
            text-transform: uppercase;
            color: #888;
            font-weight: 600;
        }

        .info-item .value {
            font-size: 18px;
            font-weight: 500;
            margin-top: 6px;
            color: #222;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-active { background: #d5f5e3; color: #27ae60; }
        .status-inactive { background: #fdebd0; color: #e67e22; }
        .status-suspended { background: #fadbd8; color: #e74c3c; }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .orders-table th,
        .orders-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .orders-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }

        .orders-table tr:hover {
            background: #f8f9fa;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 60px;
            margin-bottom: 15px;
            color: #ddd;
        }

        /* -------- Responsive -------- */
        @media (max-width: 768px) {
            .dashboard-wrap {
                flex-direction: column;
            }
            .dashboard-sidebar {
                flex: auto;
                position: static;
            }
            .dashboard-content {
                padding: 20px;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
            .orders-table {
                font-size: 14px;
            }
            .orders-table th,
            .orders-table td {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>

    <!-- Header (reuse your existing header style) -->
    <header class="top-header">
        <div class="logo">
            <h1>Quick<span>Basket</span></h1>
        </div>
        <div class="search-box">
            <input type="text" placeholder="Search for Products, Brands and More">
            <button>SEARCH</button>
        </div>
        <div class="header-icons">
            <a href="index.php">
                <i class="fa-solid fa-house"></i> Home
            </a>
            <a href="logout.php" style="color:#ffd700;">
                <i class="fa-solid fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <!-- Dashboard -->
    <div class="dashboard-wrap">

        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="user-summary">
                <div class="avatar"><?php echo strtoupper(substr($name, 0, 1)); ?></div>
                <h3><?php echo $name; ?></h3>
                <p><?php echo $email; ?></p>
            </div>
            <ul class="sidebar-menu">
                <li class="<?php echo ($page == 'profile') ? 'active' : ''; ?>" data-page="profile">
                    <i class="fa-regular fa-user"></i> My Profile
                </li>
                <li class="<?php echo ($page == 'orders') ? 'active' : ''; ?>" data-page="orders">
                    <i class="fa-solid fa-box"></i> My Orders
                </li>
                <li class="<?php echo ($page == 'addresses') ? 'active' : ''; ?>" data-page="addresses">
                    <i class="fa-solid fa-location-dot"></i> Manage Addresses
                </li>
                <li class="<?php echo ($page == 'payments') ? 'active' : ''; ?>" data-page="payments">
                    <i class="fa-regular fa-credit-card"></i> Payments
                </li>
                <li class="<?php echo ($page == 'wishlist') ? 'active' : ''; ?>" data-page="wishlist">
                    <i class="fa-regular fa-heart"></i> Wishlist
                </li>
                <li class="<?php echo ($page == 'coupons') ? 'active' : ''; ?>" data-page="coupons">
                    <i class="fa-solid fa-ticket"></i> Coupons
                </li>
                <li class="logout-item" onclick="window.location.href='logout.php'">
                    <i class="fa-solid fa-sign-out-alt"></i> Logout
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-content">

            <!-- ======== PROFILE SECTION ======== -->
            <div id="section-profile" class="section <?php echo ($page == 'profile') ? 'active' : ''; ?>">
                <h2><i class="fa-regular fa-user" style="color:#2874f0;"></i> Profile Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Full Name</div>
                        <div class="value"><?php echo $name; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Email Address</div>
                        <div class="value"><?php echo $email; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Mobile Number</div>
                        <div class="value"><?php echo $phone; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Account Status</div>
                        <div class="value">
                            <span class="status-badge status-<?php echo $status; ?>">
                                <?php echo $status; ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="label">Member Since</div>
                        <div class="value"><?php echo $joined; ?></div>
                    </div>
                </div>
                <div style="margin-top:30px;">
                    <a href="edit-profile.php" class="btn btn-primary" style="background:#2874f0; color:#fff; padding:10px 25px; border-radius:6px; text-decoration:none; display:inline-block;">
                        <i class="fa-regular fa-pen-to-square"></i> Edit Profile
                    </a>
                </div>
            </div>

            <!-- ======== ORDERS SECTION ======== -->
            <div id="section-orders" class="section <?php echo ($page == 'orders') ? 'active' : ''; ?>">
                <h2><i class="fa-solid fa-box" style="color:#2874f0;"></i> My Orders</h2>
                <div class="empty-state">
                    <i class="fa-solid fa-box-open"></i>
                    <h3>No orders yet</h3>
                    <p>Start shopping to see your orders here.</p>
                    <a href="index.php" style="color:#2874f0; text-decoration:none; font-weight:600;">Continue Shopping</a>
                </div>
                <!-- You can later replace with a dynamic orders table -->
            </div>

            <!-- ======== ADDRESSES SECTION ======== -->
            <div id="section-addresses" class="section <?php echo ($page == 'addresses') ? 'active' : ''; ?>">
                <h2><i class="fa-solid fa-location-dot" style="color:#2874f0;"></i> Manage Addresses</h2>
                <div class="empty-state">
                    <i class="fa-solid fa-location-dot"></i>
                    <h3>No addresses saved</h3>
                    <p>Add a new address for faster checkout.</p>
                    <a href="#" style="color:#2874f0; text-decoration:none; font-weight:600;">+ Add New Address</a>
                </div>
            </div>

            <!-- ======== PAYMENTS SECTION ======== -->
            <div id="section-payments" class="section <?php echo ($page == 'payments') ? 'active' : ''; ?>">
                <h2><i class="fa-regular fa-credit-card" style="color:#2874f0;"></i> Payment Methods</h2>
                <div class="empty-state">
                    <i class="fa-regular fa-credit-card"></i>
                    <h3>No saved cards</h3>
                    <p>Add a card or UPI for faster payments.</p>
                    <a href="#" style="color:#2874f0; text-decoration:none; font-weight:600;">+ Add Payment Method</a>
                </div>
            </div>

            <!-- ======== WISHLIST SECTION ======== -->
            <div id="section-wishlist" class="section <?php echo ($page == 'wishlist') ? 'active' : ''; ?>">
                <h2><i class="fa-regular fa-heart" style="color:#2874f0;"></i> Wishlist</h2>
                <div class="empty-state">
                    <i class="fa-regular fa-heart" style="color:#ff6b6b;"></i>
                    <h3>Your wishlist is empty</h3>
                    <p>Save items you love to your wishlist.</p>
                    <a href="index.php" style="color:#2874f0; text-decoration:none; font-weight:600;">Explore Products</a>
                </div>
            </div>

            <!-- ======== COUPONS SECTION ======== -->
            <div id="section-coupons" class="section <?php echo ($page == 'coupons') ? 'active' : ''; ?>">
                <h2><i class="fa-solid fa-ticket" style="color:#2874f0;"></i> My Coupons</h2>
                <div class="empty-state">
                    <i class="fa-solid fa-ticket"></i>
                    <h3>No coupons available</h3>
                    <p>Check back later for exclusive offers.</p>
                </div>
            </div>

        </main>
    </div>

    <!-- Footer (optional) -->
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
                    <li><a href="#">Categories</a></li>
                </ul>
            </div>
            <div class="footer-box">
                <h3>Customer Support</h3>
                <ul>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2026 Quick Basket. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- JavaScript for menu switching -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuItems = document.querySelectorAll('.sidebar-menu li[data-page]');
            const sections = {
                profile: document.getElementById('section-profile'),
                orders: document.getElementById('section-orders'),
                addresses: document.getElementById('section-addresses'),
                payments: document.getElementById('section-payments'),
                wishlist: document.getElementById('section-wishlist'),
                coupons: document.getElementById('section-coupons')
            };

            // Function to switch section
            function switchSection(page) {
                // Hide all sections
                Object.values(sections).forEach(section => {
                    section.classList.remove('active');
                });
                // Show selected
                if (sections[page]) {
                    sections[page].classList.add('active');
                }
                // Update active class on menu items
                menuItems.forEach(item => {
                    item.classList.remove('active');
                    if (item.dataset.page === page) {
                        item.classList.add('active');
                    }
                });
                // Update URL hash without reload
                if (history.pushState) {
                    history.pushState(null, null, '?page=' + page);
                }
            }

            // Click handler
            menuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    const page = this.dataset.page;
                    switchSection(page);
                });
            });

            // Handle back/forward browser buttons
            window.addEventListener('popstate', function() {
                const params = new URLSearchParams(window.location.search);
                const page = params.get('page') || 'profile';
                switchSection(page);
            });
        });
    </script>

</body>
</html> 