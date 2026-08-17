<?php
// admin/sidebar.php – Sidebar for all admin pages
// The $current_page variable should be set in each admin page before including this file.
// Example: $current_page = 'products';
?>
<aside class="sidebar">
    <div class="admin-logo">
        Quick<span>Basket</span>
    </div>
    <nav>
        <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>
        <a href="products.php" class="<?php echo ($current_page == 'products') ? 'active' : ''; ?>">
            <i class="fa-solid fa-cube"></i> Products
        </a>
        <a href="categories.php" class="<?php echo ($current_page == 'categories') ? 'active' : ''; ?>">
            <i class="fa-solid fa-tags"></i> Categories
        </a>
        <a href="users.php" class="<?php echo ($current_page == 'users') ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i> Users
        </a>
        <a href="orders.php" class="<?php echo ($current_page == 'orders') ? 'active' : ''; ?>">
            <i class="fa-solid fa-box"></i> Orders
        </a>
        <a href="sellers.php" class="<?php echo ($current_page == 'sellers') ? 'active' : ''; ?>">
            <i class="fa-solid fa-store"></i> Sellers
        </a>
        <!-- Logout link that triggers modal -->
        <a href="javascript:void(0)" class="logout-link" onclick="openLogoutModal()">
            <i class="fa-solid fa-sign-out-alt"></i> Logout
        </a>

        <!-- Logout Confirmation Modal -->
        <div id="logoutModal" class="logout-modal">
            <div class="logout-modal-content">
                <div class="modal-icon">
                    <i class="fa-regular fa-circle-question"></i>
                </div>
                <h3>Confirm Logout</h3>
                <p>Are you sure you want to logout from the admin panel?</p>
                <div class="modal-actions">
                    <a href="logout.php" class="btn-confirm">Yes, Logout</a>
                    <button onclick="closeLogoutModal()" class="btn-cancel">Cancel</button>
                </div>
            </div>
        </div>

        <style>
            /* Logout Modal Styles */
            .logout-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(4px);
                z-index: 9999;
                justify-content: center;
                align-items: center;
                animation: fadeIn 0.3s ease;
            }

            .logout-modal.show {
                display: flex;
            }

            .logout-modal-content {
                background: #fff;
                padding: 40px 35px;
                border-radius: 16px;
                max-width: 400px;
                width: 90%;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                animation: scaleIn 0.3s ease;
            }

            .modal-icon {
                width: 64px;
                height: 64px;
                margin: 0 auto 16px;
                background: #fef3c7;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .modal-icon i {
                font-size: 32px;
                color: #f59e0b;
            }

            .logout-modal-content h3 {
                font-size: 22px;
                color: #222;
                margin-bottom: 8px;
            }

            .logout-modal-content p {
                color: #666;
                margin-bottom: 24px;
            }

            .modal-actions {
                display: flex;
                gap: 12px;
                justify-content: center;
            }

            .modal-actions .btn-confirm {
                padding: 10px 30px;
                background: #e74c3c;
                color: #fff;
                border: none;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                transition: 0.3s;
            }

            .modal-actions .btn-confirm:hover {
                background: #c0392b;
                transform: scale(1.02);
            }

            .modal-actions .btn-cancel {
                padding: 10px 30px;
                background: #f1f3f6;
                color: #333;
                border: none;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                transition: 0.3s;
            }

            .modal-actions .btn-cancel:hover {
                background: #e5e7eb;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                }

                to {
                    opacity: 1;
                }
            }

            @keyframes scaleIn {
                from {
                    transform: scale(0.9);
                    opacity: 0;
                }

                to {
                    transform: scale(1);
                    opacity: 1;
                }
            }
        </style>

        <script>
            function openLogoutModal() {
                document.getElementById('logoutModal').classList.add('show');
                document.body.style.overflow = 'hidden';
            }

            function closeLogoutModal() {
                document.getElementById('logoutModal').classList.remove('show');
                document.body.style.overflow = 'auto';
            }

            // Close modal when clicking outside
            document.getElementById('logoutModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeLogoutModal();
                }
            });

            // Close modal with ESC key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeLogoutModal();
                }
            });
        </script>
    </nav>
</aside>