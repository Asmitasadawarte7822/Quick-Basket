<?php
session_start();
// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
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
        }
        .admin-login-wrap h2 {
            text-align: center;
            color: #222;
            margin-bottom: 30px;
        }
        .admin-login-wrap .logo {
            text-align: center;
            font-size: 28px;
            color: #2874f0;
            margin-bottom: 20px;
        }
        .admin-login-wrap .logo span { color: #ffd700; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }
        .form-group input:focus { border-color: #2874f0; outline: none; }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #2874f0;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-login:hover { background: #0052cc; }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #2874f0;
            text-decoration: none;
        }
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-error { background: #fadbd8; color: #e74c3c; }
    </style>
</head>
<body>
    <div class="admin-login-wrap">
        <div class="logo">Quick<span>Basket</span></div>
        <h2>Admin Login</h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">Invalid email or password.</div>
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
</body>
</html>