<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch current user data
$sql = "SELECT id, name, email, phone FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    $name  = htmlspecialchars($user['name']);
    $email = htmlspecialchars($user['email']);
    $phone = htmlspecialchars($user['phone']);
} else {
    session_destroy();
    header('Location: login.php');
    exit;
}
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Quick Basket</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .edit-profile-wrap {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .edit-profile-wrap h2 {
            color: #222;
            margin-bottom: 25px;
            border-bottom: 2px solid #2874f0;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }
        .form-group input:focus {
            border-color: #2874f0;
            outline: none;
        }
        .btn-save {
            background: #2874f0;
            color: #fff;
            border: none;
            padding: 14px 30px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        .btn-save:hover {
            background: #0052cc;
        }
        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #2874f0;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-success {
            background: #d5f5e3;
            color: #27ae60;
        }
        .alert-error {
            background: #fadbd8;
            color: #e74c3c;
        }
        .password-hint {
            font-size: 13px;
            color: #888;
            margin-top: 4px;
        }
    </style>
</head>
<body>

    <!-- Simple header (reuse your style) -->
    <header class="top-header" style="background:#2874f0; padding:14px 4%;">
        <div class="logo">
            <h1 style="color:#fff;">Quick<span style="color:#ffd700;">Basket</span></h1>
        </div>
        <div style="color:#fff;">
            <a href="index.php" style="color:#fff; text-decoration:none; margin-right:20px;">
                <i class="fa-solid fa-house"></i> Home
            </a>
            <a href="logout.php" style="color:#fff; text-decoration:none;">
                <i class="fa-solid fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <div class="edit-profile-wrap">
        <h2><i class="fa-regular fa-pen-to-square" style="color:#2874f0;"></i> Edit Profile</h2>

        <!-- Show success/error messages if any -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">✅ Profile updated successfully!</div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-error">❌ Update failed. Please try again.</div>
        <?php endif; ?>

        <form action="update-profile.php" method="POST">
            <!-- Name -->
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo $name; ?>" required>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label for="phone">Mobile Number</label>
                <input type="text" id="phone" name="phone" value="<?php echo $phone; ?>" required>
            </div>

            <!-- Password (optional change) -->
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" placeholder="Leave blank to keep current">
                <div class="password-hint">Only fill this if you want to change your password.</div>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password">
            </div>

            <button type="submit" class="btn-save">
                <i class="fa-regular fa-floppy-disk"></i> Save Changes
            </button>
        </form>

        <a href="dashboard.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

</body>
</html>