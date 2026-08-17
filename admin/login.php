<?php
session_start();

// Check if logout was successful
$logout_message = '';
$logout_type = '';
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $logout_message = 'You have been logged out successfully.';
    $logout_type = 'success';
}

// Check if login error
$error_message = '';
if (isset($_GET['error'])) {
    $error_message = 'Invalid email or password. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Quick Basket</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .admin-login-wrap {
            max-width: 400px;
            margin: 80px auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: relative;
        }
        .admin-login-wrap .logo {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .admin-login-wrap .logo span { color: #2874f0; }
        .admin-login-wrap h2 {
            text-align: center;
            color: #222;
            margin-bottom: 25px;
            font-size: 22px;
        }

        /* ---------- Notification Styles ---------- */
        .notification {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.5s ease-out;
        }
        .notification i {
            font-size: 20px;
            flex-shrink: 0;
        }
        .notification.success {
            background: #d5f5e3;
            color: #27ae60;
            border-left: 4px solid #27ae60;
        }
        .notification.error {
            background: #fadbd8;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }
        .notification .close-btn {
            margin-left: auto;
            cursor: pointer;
            font-size: 18px;
            opacity: 0.6;
            transition: 0.3s;
            background: none;
            border: none;
            color: inherit;
        }
        .notification .close-btn:hover {
            opacity: 1;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: 0.3s;
        }
        .form-group input:focus {
            border-color: #2874f0;
            outline: none;
            box-shadow: 0 0 0 3px rgba(40,116,240,0.1);
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #2874f0;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-login:hover {
            background: #0052cc;
            transform: scale(1.01);
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #555;
            text-decoration: none;
            transition: 0.3s;
        }
        .back-link:hover {
            color: #2874f0;
        }
    </style>
</head>
<body>
<div class="admin-login-wrap">
    <div class="logo">Quick<span>Basket</span></div>
    <h2>Admin Login</h2>

    <!-- Logout Success Notification -->
    <?php if (!empty($logout_message)): ?>
        <div class="notification success" id="logoutNotification">
            <i class="fa-regular fa-circle-check"></i>
            <span><?php echo $logout_message; ?></span>
            <button class="close-btn" onclick="document.getElementById('logoutNotification').style.display='none'">
                <i class="fa-regular fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Error Notification -->
    <?php if (!empty($error_message)): ?>
        <div class="notification error" id="errorNotification">
            <i class="fa-regular fa-circle-xmark"></i>
            <span><?php echo $error_message; ?></span>
            <button class="close-btn" onclick="document.getElementById('errorNotification').style.display='none'">
                <i class="fa-regular fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <form action="login-process.php" method="POST">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Admin email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Admin password" required>
        </div>
        <button type="submit" class="btn-login">Login</button>
    </form>

    <a href="../index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Website</a>
</div>

<script>
    // Auto-hide notifications after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(function(notif) {
            setTimeout(function() {
                notif.style.transition = 'opacity 0.5s ease';
                notif.style.opacity = '0';
                setTimeout(function() {
                    notif.style.display = 'none';
                }, 500);
            }, 5000);
        });
    });
</script>
</body>
</html>