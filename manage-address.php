<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ---------- FETCH USER INFO ----------
$user_sql = "SELECT name, email FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($stmt);

// ---------- FETCH ADDRESSES ----------
$addr_sql = "SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC";
$stmt = mysqli_prepare($conn, $addr_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$addr_result = mysqli_stmt_get_result($stmt);

$addresses = [];
while ($row = mysqli_fetch_assoc($addr_result)) {
    $addresses[] = $row;
}
mysqli_stmt_close($stmt);

// ---------- DELETE ADDRESS ----------
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $addr_id = (int)$_GET['delete'];
    $del_sql = "DELETE FROM addresses WHERE id = ? AND user_id = ?";
    $del_stmt = mysqli_prepare($conn, $del_sql);
    mysqli_stmt_bind_param($del_stmt, "ii", $addr_id, $user_id);
    if (mysqli_stmt_execute($del_stmt)) {
        header('Location: manage-address.php?deleted=1');
        exit;
    }
    mysqli_stmt_close($del_stmt);
}

// ---------- SET DEFAULT ADDRESS ----------
if (isset($_GET['default']) && is_numeric($_GET['default'])) {
    $addr_id = (int)$_GET['default'];
    $reset_sql = "UPDATE addresses SET is_default = 0 WHERE user_id = ?";
    $reset_stmt = mysqli_prepare($conn, $reset_sql);
    mysqli_stmt_bind_param($reset_stmt, "i", $user_id);
    mysqli_stmt_execute($reset_stmt);
    mysqli_stmt_close($reset_stmt);
    
    $set_sql = "UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?";
    $set_stmt = mysqli_prepare($conn, $set_sql);
    mysqli_stmt_bind_param($set_stmt, "ii", $addr_id, $user_id);
    mysqli_stmt_execute($set_stmt);
    mysqli_stmt_close($set_stmt);
    
    header('Location: manage-address.php?defaulted=1');
    exit;
}

// ---------- CART COUNT ----------
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Addresses - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ============================================
           BLUE, WHITE & YELLOW THEME
           ============================================ */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        body {
            background: #f1f3f6;
            color: #333;
            min-height: 100vh;
        }

        /* ---------- Header (Flipkart Style) ---------- */
        .top-header {
            background: #2874f0;
            padding: 14px 4%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }
        .logo h1 {
            color: #fff;
            font-size: 38px;
            font-weight: 700;
        }
        .logo span {
            color: #ffd700;
        }
        .search-box {
            flex: 1;
            display: flex;
            max-width: 700px;
        }
        .search-box input {
            width: 100%;
            padding: 14px;
            border: none;
            outline: none;
            border-radius: 4px 0 0 4px;
            font-size: 15px;
        }
        .search-box button {
            border: none;
            padding: 14px 30px;
            background: #ffd700;
            font-weight: 700;
            cursor: pointer;
            border-radius: 0 4px 4px 0;
        }
        .search-box button:hover {
            background: #f5cf00;
        }
        .header-icons {
            display: flex;
            gap: 25px;
        }
        .header-icons a {
            text-decoration: none;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            transition: 0.3s;
        }
        .header-icons a:hover {
            color: #ffd700;
        }
        .header-icons i {
            margin-right: 8px;
        }
        .badge {
            background: #ff3b30;
            color: #fff;
            border-radius: 50%;
            padding: 2px 7px;
            font-size: 10px;
            font-weight: 700;
            margin-left: 4px;
        }

        /* ---------- Category Nav ---------- */
        .category-nav {
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
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
            color: #333;
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

        /* ---------- Layout ---------- */
        .address-wrap {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 24px;
        }

        /* ---------- Sidebar ---------- */
        .address-sidebar {
            background: #fff;
            border-radius: 12px;
            padding: 24px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .address-sidebar .user-section {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
            margin-bottom: 16px;
        }
        .address-sidebar .user-section .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #2874f0;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
            text-transform: uppercase;
            flex-shrink: 0;
        }
        .address-sidebar .user-section .user-name {
            font-weight: 600;
            color: #222;
            font-size: 16px;
        }
        .address-sidebar .user-section .user-email {
            font-size: 13px;
            color: #888;
        }
        .address-sidebar .menu-section {
            margin-bottom: 12px;
        }
        .address-sidebar .menu-section .menu-title {
            font-size: 11px;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .address-sidebar .menu-section a {
            display: block;
            padding: 8px 12px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
            border-radius: 8px;
        }
        .address-sidebar .menu-section a:hover {
            background: #f0f7ff;
            color: #2874f0;
        }
        .address-sidebar .menu-section a.active {
            background: #e6f0ff;
            color: #2874f0;
            font-weight: 600;
        }
        .address-sidebar .menu-section a i {
            width: 20px;
            margin-right: 8px;
            color: #888;
        }
        .address-sidebar .menu-section a:hover i {
            color: #2874f0;
        }
        .address-sidebar .logout-link {
            display: block;
            padding: 10px 12px;
            color: #e74c3c;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border-top: 1px solid #eee;
            margin-top: 16px;
            padding-top: 16px;
            border-radius: 8px;
            transition: 0.3s;
        }
        .address-sidebar .logout-link:hover {
            background: #fee2e2;
        }
        .address-sidebar .logout-link i {
            margin-right: 8px;
        }

        /* ---------- Main Content ---------- */
        .address-main {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .address-main h2 {
            font-size: 24px;
            color: #222;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }
        .address-main h2 i {
            color: #2874f0;
        }
        .address-main h2 span {
            color: #888;
            font-weight: 400;
            font-size: 16px;
        }

        /* ---------- Address Card ---------- */
        .address-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
        }
        .address-card {
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            padding: 18px 20px;
            transition: 0.3s;
        }
        .address-card:hover {
            border-color: #2874f0;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            transform: translateY(-2px);
        }
        .address-card .addr-name {
            font-weight: 600;
            color: #222;
            font-size: 16px;
        }
        .address-card .addr-phone {
            color: #888;
            font-size: 13px;
        }
        .address-card .addr-text {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
            margin: 8px 0 12px;
        }
        .address-card .addr-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .address-card .addr-actions a {
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 6px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .address-card .addr-actions .btn-default {
            color: #2874f0;
            background: #e6f0ff;
        }
        .address-card .addr-actions .btn-default:hover {
            background: #2874f0;
            color: #fff;
        }
        .address-card .addr-actions .btn-edit {
            color: #555;
        }
        .address-card .addr-actions .btn-edit:hover {
            background: #2874f0;
            color: #fff;
        }
        .address-card .addr-actions .btn-delete {
            color: #e74c3c;
        }
        .address-card .addr-actions .btn-delete:hover {
            background: #e74c3c;
            color: #fff;
        }
        .address-card .default-badge {
            display: inline-block;
            background: #2874f0;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 12px;
            border-radius: 30px;
            margin-left: 10px;
        }

        /* ---------- Add Address Button ---------- */
        .add-address-btn {
            display: inline-block;
            padding: 12px 24px;
            background: #2874f0;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            margin-bottom: 16px;
        }
        .add-address-btn:hover {
            background: #0052cc;
            transform: translateY(-2px);
        }
        .add-address-btn i {
            margin-right: 8px;
        }

        /* ---------- Modal ---------- */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .modal-content h3 {
            color: #222;
            font-size: 22px;
            margin-bottom: 20px;
        }
        .modal-content .form-group {
            margin-bottom: 14px;
        }
        .modal-content .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
            font-size: 14px;
        }
        .modal-content .form-group input,
        .modal-content .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            color: #333;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }
        .modal-content .form-group input:focus,
        .modal-content .form-group textarea:focus {
            border-color: #2874f0;
            box-shadow: 0 0 0 3px rgba(40,116,240,0.1);
        }
        .modal-content .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }
        .modal-content .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .modal-content .btn-save {
            width: 100%;
            padding: 12px;
            background: #2874f0;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }
        .modal-content .btn-save:hover {
            background: #0052cc;
        }
        .modal-content .btn-cancel {
            width: 100%;
            padding: 12px;
            background: #f1f3f6;
            color: #333;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }
        .modal-content .btn-cancel:hover {
            background: #e5e7eb;
        }

        /* ---------- Alert ---------- */
        .alert {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        .alert-success {
            background: #d5f5e3;
            color: #27ae60;
            border-left: 3px solid #27ae60;
        }
        .alert-success i {
            margin-right: 8px;
        }

        /* ---------- Empty State ---------- */
        .empty-msg {
            text-align: center;
            padding: 40px 20px;
            color: #888;
        }
        .empty-msg i {
            font-size: 60px;
            display: block;
            margin-bottom: 16px;
            color: #ddd;
        }
        .empty-msg h3 {
            color: #222;
            font-size: 20px;
            margin-bottom: 8px;
        }

        /* ---------- Footer ---------- */
        .footer {
            background: #172337;
            color: #fff;
            margin-top: 30px;
        }
        .footer-container {
            width: 95%;
            margin: auto;
            padding: 50px 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 30px;
        }
        .footer-box h3 {
            margin-bottom: 18px;
        }
        .footer-box p {
            color: #ccc;
            line-height: 1.7;
        }
        .footer-box ul {
            list-style: none;
        }
        .footer-box ul li {
            margin-bottom: 10px;
        }
        .footer-box ul li a {
            color: #ccc;
            text-decoration: none;
            transition: 0.3s;
        }
        .footer-box ul li a:hover {
            color: #ffd700;
        }
        .social-icons {
            display: flex;
            gap: 12px;
        }
        .social-icons a {
            width: 40px;
            height: 40px;
            background: #2874f0;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
            text-decoration: none;
            transition: 0.3s;
        }
        .social-icons a:hover {
            background: #ffd700;
            color: #000;
        }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            padding: 20px;
            color: #ccc;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 992px) {
            .address-wrap {
                grid-template-columns: 1fr;
            }
            .address-sidebar {
                position: static;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                padding: 16px;
            }
            .address-sidebar .user-section {
                grid-column: 1 / -1;
            }
            .address-sidebar .menu-section {
                margin-bottom: 0;
            }
            .address-sidebar .logout-link {
                grid-column: 1 / -1;
                margin-top: 0;
            }
            .top-header {
                flex-direction: column;
            }
            .search-box {
                width: 100%;
                max-width: 100%;
            }
            .header-icons {
                justify-content: center;
                flex-wrap: wrap;
            }
        }
        @media (max-width: 768px) {
            .address-sidebar {
                grid-template-columns: 1fr;
            }
            .address-grid {
                grid-template-columns: 1fr;
            }
            .address-main {
                padding: 16px;
            }
            .address-main h2 {
                font-size: 20px;
            }
            .modal-content .form-row {
                grid-template-columns: 1fr;
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
    <div class="search-box">
        <input type="text" placeholder="Search for Products, Brands and More">
        <button>SEARCH</button>
    </div>
    <div class="header-icons">
        <a href="dashboard.php"><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($user['name'] ?? 'User'); ?></a>
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
        <a href="dashboard.php">Dashboard</a>
    </div>
</nav>

<!-- ======== ADDRESS CONTENT ======== -->
<div class="address-wrap">

    <!-- Sidebar -->
    <aside class="address-sidebar">
        <div class="user-section">
            <div class="avatar"><?php echo $user ? strtoupper(substr($user['name'], 0, 1)) : 'U'; ?></div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
            </div>
        </div>

        <div class="menu-section">
            <div class="menu-title">Account</div>
            <a href="dashboard.php"><i class="fa-regular fa-user"></i> My Profile</a>
            <a href="orders.php"><i class="fa-solid fa-box"></i> My Orders</a>
            <a href="manage-address.php" class="active"><i class="fa-solid fa-location-dot"></i> Manage Addresses</a>
            <a href="payments.php"><i class="fa-regular fa-credit-card"></i> Payments</a>
            <a href="wishlist.php"><i class="fa-regular fa-heart"></i> Wishlist</a>
            <a href="#"><i class="fa-solid fa-ticket"></i> Coupons</a>
        </div>

        <a href="logout.php" class="logout-link">
            <i class="fa-solid fa-sign-out-alt"></i> Logout
        </a>
    </aside>

    <!-- Main Content -->
    <main class="address-main">
        <h2><i class="fa-solid fa-location-dot"></i> Manage Addresses <span>(<?php echo count($addresses); ?> addresses)</span></h2>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success"><i class="fa-regular fa-circle-check"></i> Address deleted successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['defaulted'])): ?>
            <div class="alert alert-success"><i class="fa-regular fa-circle-check"></i> Default address updated!</div>
        <?php endif; ?>

        <button class="add-address-btn" onclick="openModal()">
            <i class="fa-solid fa-plus"></i> Add New Address
        </button>

        <?php if (!empty($addresses)): ?>
            <div class="address-grid">
                <?php foreach ($addresses as $addr): ?>
                    <div class="address-card">
                        <div>
                            <span class="addr-name"><?php echo htmlspecialchars($addr['full_name']); ?></span>
                            <?php if ($addr['is_default'] == 1): ?>
                                <span class="default-badge">Default</span>
                            <?php endif; ?>
                        </div>
                        <div class="addr-phone">📞 <?php echo htmlspecialchars($addr['phone']); ?></div>
                        <div class="addr-text">
                            <?php echo htmlspecialchars($addr['address']); ?><br>
                            <?php echo htmlspecialchars($addr['city']); ?>, <?php echo htmlspecialchars($addr['state']); ?> - <?php echo htmlspecialchars($addr['pincode']); ?>
                        </div>
                        <div class="addr-actions">
                            <?php if ($addr['is_default'] != 1): ?>
                                <a href="?default=<?php echo $addr['id']; ?>" class="btn-default"><i class="fa-regular fa-check-circle"></i> Set Default</a>
                            <?php endif; ?>
                            <a href="#" class="btn-edit" onclick="editAddress(<?php echo $addr['id']; ?>)"><i class="fa-regular fa-pen-to-square"></i> Edit</a>
                            <a href="?delete=<?php echo $addr['id']; ?>" class="btn-delete" onclick="return confirm('Delete this address?')"><i class="fa-regular fa-trash-can"></i> Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-msg">
                <i class="fa-regular fa-location-dot"></i>
                <h3>No addresses saved</h3>
                <p>Add a new address for faster checkout.</p>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- ======== ADD ADDRESS MODAL ======== -->
<div class="modal" id="addressModal">
    <div class="modal-content">
        <h3 id="modalTitle">Add New Address</h3>
        <form id="addressForm" method="POST" action="save-address.php">
            <input type="hidden" name="address_id" id="address_id" value="0">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" id="full_name" placeholder="Enter full name" required>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" id="phone" placeholder="Enter phone number" required>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" id="address" placeholder="Enter full address" required></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" id="city" placeholder="City" required>
                </div>
                <div class="form-group">
                    <label>State</label>
                    <input type="text" name="state" id="state" placeholder="State" required>
                </div>
            </div>
            <div class="form-group">
                <label>Pincode</label>
                <input type="text" name="pincode" id="pincode" placeholder="Pincode" required>
            </div>
            <button type="submit" class="btn-save"><i class="fa-regular fa-floppy-disk"></i> Save Address</button>
            <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
        </form>
    </div>
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
    function openModal() {
        document.getElementById('modalTitle').textContent = 'Add New Address';
        document.getElementById('addressForm').reset();
        document.getElementById('address_id').value = '0';
        document.getElementById('addressModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('addressModal').classList.remove('show');
        document.body.style.overflow = 'auto';
    }

    function editAddress(id) {
        window.location.href = 'edit-address.php?id=' + id;
    }

    document.getElementById('addressModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
</script>

</body>
</html>