<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header('Location: products.php');
    exit;
}

// Fetch product
$sql = "SELECT * FROM products WHERE id = $product_id";
$result = mysqli_query($conn, $sql);
if (mysqli_num_rows($result) == 0) {
    header('Location: products.php');
    exit;
}
$product = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $image = mysqli_real_escape_string($conn, $_POST['image']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $category_id = (int)$_POST['category_id'];

    $sql = "UPDATE products SET 
            name = '$name',
            description = '$description',
            price = $price,
            stock = $stock,
            image = '$image',
            status = '$status',
            category_id = $category_id
            WHERE id = $product_id";

    if (mysqli_query($conn, $sql)) {
        header('Location: products.php?success=updated');
        exit;
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// Fetch categories
$categories = [];
$cat_sql = "SELECT id, name FROM product_categories ORDER BY name";
$cat_result = mysqli_query($conn, $cat_sql);
while ($row = mysqli_fetch_assoc($cat_result)) {
    $categories[] = $row;
}

// Fetch sellers
$sellers = [];
$seller_sql = "SELECT id, Store_name FROM sellers WHERE status = 'active' ORDER BY Store_name";
$seller_result = mysqli_query($conn, $seller_sql);
while ($row = mysqli_fetch_assoc($seller_result)) {
    $sellers[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .form-wrap {
            max-width: 600px;
            margin: 30px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }

        .form-wrap h2 {
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #2874f0;
            outline: none;
        }

        .btn-submit {
            background: #2874f0;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-submit:hover {
            background: #0052cc;
        }

        .back-link {
            display: block;
            margin-top: 15px;
            color: #2874f0;
            text-decoration: none;
        }

        .alert-error {
            background: #fadbd8;
            color: #e74c3c;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .current-img {
            max-width: 150px;
            margin: 10px 0;
            border-radius: 6px;
        }
    </style>
</head>

<body>
    <div class="form-wrap">
        <h2><i class="fa-regular fa-pen-to-square"></i> Edit Product</h2>

        <?php if (isset($error)): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($product['image'])): ?>
            <img src="<?php echo htmlspecialchars($product['image']); ?>" class="current-img" alt="Current image">
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="price">Price (₹)</label>
                <input type="number" id="price" name="price" step="0.01" value="<?php echo $product['price']; ?>" required>
            </div>
            <div class="form-group">
                <label for="stock">Stock</label>
                <input type="number" id="stock" name="stock" value="<?php echo $product['stock']; ?>" required>
            </div>
            <div class="form-group">
                <label for="image">Image URL</label>
                <input type="text" id="image" name="image" value="<?php echo htmlspecialchars($product['image']); ?>">
            </div>
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="0">None</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $product['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" <?php echo ($product['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($product['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="form-group">
                <label for="seller_id">Seller</label>
                <select id="seller_id" name="seller_id" required>
                    <option value="">Select Seller</option>
                    <?php foreach ($sellers as $seller): ?>
                        <option value="<?php echo $seller['id']; ?>" <?php echo ($seller['id'] == $product['seller_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($seller['Store_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-submit"><i class="fa-regular fa-floppy-disk"></i> Update Product</button>
        </form>
        <a href="products.php" class="back-link">← Back to Products</a>
    </div>
</body>

</html>