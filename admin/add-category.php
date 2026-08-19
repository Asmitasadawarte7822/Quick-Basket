<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'active');

    // Validate
    if (empty($name)) {
        $error = 'Category name is required.';
    } else {
        // Check if category already exists
        $check_sql = "SELECT id FROM product_categories WHERE name = '$name'";
        $check_result = mysqli_query($conn, $check_sql);
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Category "' . htmlspecialchars($name) . '" already exists.';
        } else {
            // Generate slug
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)));
            
            // Insert category
            $sql = "INSERT INTO product_categories (name, description, slug, status, created_at) 
                    VALUES ('$name', '$description', '$slug', '$status', NOW())";
            
            if (mysqli_query($conn, $sql)) {
                $success = 'Category added successfully!';
                // Clear form
                $_POST = [];
            } else {
                $error = 'Error: ' . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category - Admin</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ---------- Layout ---------- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        body {
            background: #f1f3f6;
        }
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        /* ---------- Sidebar ---------- */
        .sidebar {
            width: 240px;
            background: #2c3e50;
            color: #fff;
            padding: 20px 0;
            min-height: 100vh;
            flex-shrink: 0;
        }
        .admin-logo {
            font-size: 28px;
            font-weight: 700;
            padding: 0 20px 20px;
            border-bottom: 1px solid #34495e;
            margin-bottom: 20px;
        }
        .admin-logo span {
            color: #ffd700;
        }
        .sidebar nav a {
            display: block;
            padding: 14px 25px;
            color: #ccc;
            text-decoration: none;
            transition: 0.2s;
        }
        .sidebar nav a:hover {
            background: #34495e;
            color: #fff;
        }
        .sidebar nav a.active {
            background: #1a3a5c;
            color: #fff;
            border-left: 3px solid #2874f0;
        }
        .sidebar nav a i {
            margin-right: 10px;
            width: 20px;
        }
        .sidebar nav a.logout-link {
            margin-top: 20px;
            border-top: 1px solid #34495e;
            color: #e74c3c;
        }
        .sidebar nav a.logout-link:hover {
            background: #e74c3c;
            color: #fff;
        }

        /* ---------- Main Content ---------- */
        .admin-main {
            flex: 1;
            padding: 25px;
        }
        .admin-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            flex-wrap: wrap;
            gap: 10px;
        }
        .admin-topbar h1 {
            font-size: 24px;
            color: #222;
        }
        .admin-topbar h1 i {
            color: #2874f0;
        }

        /* ---------- Form ---------- */
        .form-wrap {
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            max-width: 600px;
        }
        .form-wrap h2 {
            font-size: 20px;
            color: #222;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .form-group label .required {
            color: #e74c3c;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: 0.3s;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #2874f0;
            outline: none;
            box-shadow: 0 0 0 3px rgba(40,116,240,0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-group .help-text {
            font-size: 12px;
            color: #888;
            margin-top: 4px;
        }
        .btn-submit {
            padding: 12px 30px;
            background: #2874f0;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-submit:hover {
            background: #0052cc;
        }
        .btn-submit i {
            margin-right: 8px;
        }
        .btn-cancel {
            padding: 12px 25px;
            background: #f1f3f6;
            color: #333;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        .btn-cancel:hover {
            background: #e5e7eb;
        }

        /* ---------- Alert Messages ---------- */
        .alert {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success {
            background: #d5f5e3;
            color: #27ae60;
            border-left: 4px solid #27ae60;
        }
        .alert-success i {
            margin-right: 8px;
        }
        .alert-error {
            background: #fadbd8;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }
        .alert-error i {
            margin-right: 8px;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 768px) {
            .admin-layout {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                min-height: auto;
            }
            .admin-main {
                padding: 15px;
            }
            .form-wrap {
                padding: 20px;
            }
            .admin-topbar {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="admin-logo">Quick<span>Basket</span></div>
            <nav>
                <a href="dashboard.php">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
                <a href="products.php">
                    <i class="fa-solid fa-cube"></i> Products
                </a>
                <a href="categories.php" class="active">
                    <i class="fa-solid fa-tags"></i> Categories
                </a>
                <a href="users.php">
                    <i class="fa-solid fa-users"></i> Users
                </a>
                <a href="orders.php">
                    <i class="fa-solid fa-box"></i> Orders
                </a>
                <a href="sellers.php">
                    <i class="fa-solid fa-store"></i> Sellers
                </a>
                <a href="logout.php" class="logout-link">
                    <i class="fa-solid fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-topbar">
                <h1><i class="fa-solid fa-plus"></i> Add Category</h1>
                <div>
                    <a href="categories.php" class="btn-cancel">
                        <i class="fa-solid fa-arrow-left"></i> Back to Categories
                    </a>
                </div>
            </div>

            <div class="form-wrap">
                <h2><i class="fa-regular fa-folder-open"></i> Category Details</h2>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fa-regular fa-circle-check"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <i class="fa-regular fa-circle-xmark"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Category Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                               placeholder="Enter category name" required>
                        <div class="help-text">e.g., Electronics, Fashion, Mobiles</div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" 
                                  placeholder="Enter category description (optional)"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <div class="help-text">Active categories will appear on the frontend.</div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fa-regular fa-floppy-disk"></i> Save Category
                    </button>
                    <a href="categories.php" class="btn-cancel">Cancel</a>
                </form>
            </div>
        </main>
    </div>
</body>
</html>