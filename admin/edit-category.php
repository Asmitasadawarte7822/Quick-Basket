<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$cat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($cat_id <= 0) {
    header('Location: categories.php');
    exit;
}

$sql = "SELECT * FROM product_categories WHERE id = $cat_id";
$result = mysqli_query($conn, $sql);
if (mysqli_num_rows($result) == 0) {
    header('Location: categories.php');
    exit;
}
$category = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $sql = "UPDATE product_categories SET name = '$name' WHERE id = $cat_id";
    if (mysqli_query($conn, $sql)) {
        header('Location: categories.php?success=updated');
        exit;
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category - Admin</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .form-wrap {
            max-width: 500px;
            margin: 30px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .form-wrap h2 { margin-bottom: 25px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }
        .form-group input:focus { border-color: #2874f0; outline: none; }
        .btn-submit {
            background: #2874f0;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-submit:hover { background: #0052cc; }
        .back-link { display: block; margin-top: 15px; color: #2874f0; text-decoration: none; }
        .alert-error { background: #fadbd8; color: #e74c3c; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="form-wrap">
        <h2><i class="fa-regular fa-pen-to-square"></i> Edit Category</h2>
        <?php if (isset($error)): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="name">Category Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
            </div>
            <button type="submit" class="btn-submit"><i class="fa-regular fa-floppy-disk"></i> Update Category</button>
        </form>
        <a href="categories.php" class="back-link">← Back to Categories</a>
    </div>
</body>
</html>