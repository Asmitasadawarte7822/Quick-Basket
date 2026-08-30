<?php
session_start();
require_once '../config.php';  // $conn is now available

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// ============================================================
// FETCH categories & sellers for dropdowns
// ============================================================
$cat_result = mysqli_query($conn, "SELECT id, name FROM product_categories ORDER BY name");
$seller_result = mysqli_query($conn, "SELECT id, store_name FROM sellers WHERE status='active' ORDER BY store_name");

$message = '';
$message_type = '';

// ============================================================
// FORM SUBMISSION
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $price       = floatval($_POST['price'] ?? 0);
    $stock       = intval($_POST['stock'] ?? 0);
    $image       = mysqli_real_escape_string($conn, trim($_POST['image'] ?? ''));
    $category_id = intval($_POST['category_id'] ?? 0);
    $seller_id   = intval($_POST['seller_id'] ?? 0);
    $status      = in_array($_POST['status'], ['active','inactive']) ? $_POST['status'] : 'active';

    if (empty($name) || $price <= 0 || $stock < 0 || empty($image) || $category_id <= 0 || $seller_id <= 0) {
        $message = '⚠️ Please fill in all required fields correctly.';
        $message_type = 'error';
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Insert product
            $insert_sql = "INSERT INTO products (name, description, price, stock, image, category_id, seller_id, status)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($stmt, "ssdissis", $name, $description, $price, $stock, $image, $category_id, $seller_id, $status);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_error($conn));
            }
            $product_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Insert variants
            if (isset($_POST['variants']) && is_array($_POST['variants'])) {
                $sort = 0;
                foreach ($_POST['variants'] as $variant) {
                    $v_name  = mysqli_real_escape_string($conn, trim($variant['name'] ?? ''));
                    $v_image = mysqli_real_escape_string($conn, trim($variant['image'] ?? ''));
                    $v_price = isset($variant['price']) && $variant['price'] !== '' ? floatval($variant['price']) : NULL;
                    $v_stock = isset($variant['stock']) && $variant['stock'] !== '' ? intval($variant['stock']) : 0;
                    $is_default = isset($variant['is_default']) ? 1 : 0;

                    if (!empty($v_name)) {
                        $v_sql = "INSERT INTO product_variants (product_id, variant_name, variant_image, variant_price, variant_stock, is_default, sort_order)
                                  VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $v_stmt = mysqli_prepare($conn, $v_sql);
                        mysqli_stmt_bind_param($v_stmt, "issdissi", $product_id, $v_name, $v_image, $v_price, $v_stock, $is_default, $sort);
                        if (!mysqli_stmt_execute($v_stmt)) {
                            throw new Exception(mysqli_error($conn));
                        }
                        mysqli_stmt_close($v_stmt);
                        $sort++;
                    }
                }
            }

            mysqli_commit($conn);
            $variant_count = isset($_POST['variants']) ? count(array_filter($_POST['variants'], function($v) { return !empty($v['name']); })) : 0;
            $message = '✅ Product added successfully with ' . $variant_count . ' variants!';
            $message_type = 'success';
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = '❌ Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickBasket - Add Product</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ===== GLOBAL ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f1f3f6; }

        /* ===== HEADER ===== */
        .top-header {
            background: #1a1a2e;
            padding: 12px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        .top-header .logo h1 {
            color: #fff;
            font-size: 24px;
        }
        .top-header .logo h1 span {
            color: #ffd700;
        }
        .top-header .search-box {
            display: flex;
            background: #fff;
            border-radius: 6px;
            overflow: hidden;
            flex: 1 1 300px;
            max-width: 500px;
        }
        .top-header .search-box input {
            flex: 1;
            padding: 10px 16px;
            border: none;
            outline: none;
            font-size: 14px;
        }
        .top-header .search-box button {
            background: #ffd700;
            border: none;
            padding: 0 18px;
            cursor: pointer;
            color: #1a1a2e;
        }
        .top-header .header-icons {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .top-header .header-icons a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: 0.2s;
        }
        .top-header .header-icons a:hover {
            color: #ffd700;
        }

        /* ===== CATEGORY NAV ===== */
        .category-bar {
            background: #2c3e50;
            padding: 0 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px 25px;
            align-items: center;
        }
        .category-bar a {
            color: #ddd;
            text-decoration: none;
            padding: 12px 0;
            font-size: 14px;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: 0.2s;
        }
        .category-bar a:hover,
        .category-bar a.active {
            color: #fff;
            border-bottom-color: #ffd700;
        }

        /* ===== MAIN WRAPPER ===== */
        .admin-wrap {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* ===== CARD ===== */
        .admin-card {
            background: #fff;
            border-radius: 12px;
            padding: 35px 40px;
            border: 1px solid #e5e5e5;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .admin-card .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #222;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .admin-card .page-title span {
            color: #2874f0;
        }
        .admin-card .page-subtitle {
            color: #888;
            font-size: 14px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e5e5;
            margin-bottom: 25px;
        }

        /* ===== ALERT ===== */
        .alert {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success {
            background: #d5f5e3;
            color: #1a7a3a;
            border: 1px solid #a9dfbf;
        }
        .alert-error {
            background: #fde2e2;
            color: #991b1b;
            border: 1px solid #f5c6c6;
        }

        /* ===== FORM ===== */
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #222;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .form-group label .required {
            color: #e74c3c;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            font-size: 14px;
            background: #f8f9fa;
            transition: 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2874f0;
            outline: none;
            box-shadow: 0 0 0 3px rgba(40,116,240,0.08);
            background: #fff;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* ===== STATUS TOGGLE ===== */
        .status-toggle {
            display: flex;
            gap: 12px;
            padding-top: 6px;
        }
        .status-toggle label {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        .status-toggle label:has(input:checked) {
            border-color: #2874f0;
            background: #e6f0ff;
        }
        .status-toggle input[type="radio"] {
            display: none;
        }

        /* ===== VARIANTS ===== */
        .variants-section {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #e5e5e5;
        }
        .variants-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .variants-header h3 {
            font-size: 20px;
            color: #222;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .variants-header h3 i {
            color: #2874f0;
        }
        .variant-count-badge {
            background: #e6f0ff;
            color: #2874f0;
            padding: 2px 12px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
        }
        .btn-add-variant {
            background: #2874f0;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            transition: 0.2s;
        }
        .btn-add-variant:hover {
            background: #1a5bc7;
        }

        /* ===== VARIANT CARD ===== */
        .variant-card {
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 14px;
        }
        .variant-card:hover {
            border-color: #2874f0;
            background: #fafcff;
        }
        .variant-card .variant-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .variant-card .variant-header .variant-number {
            font-weight: 600;
            color: #2874f0;
            font-size: 13px;
            background: #e6f0ff;
            padding: 2px 14px;
            border-radius: 50px;
        }
        .variant-card .variant-header .variant-actions {
            display: flex;
            gap: 6px;
        }
        .variant-card .variant-header .variant-actions button {
            background: none;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            color: #888;
            cursor: pointer;
        }
        .variant-card .variant-header .variant-actions .btn-remove {
            color: #e74c3c;
        }
        .variant-card .variant-header .variant-actions .btn-remove:hover {
            background: #fde2e2;
        }
        .variant-card .variant-header .variant-actions button:hover {
            background: #e6f0ff;
            color: #2874f0;
        }

        /* ===== VARIANT FORM ROW ===== */
        .variant-form-row {
            display: grid;
            grid-template-columns: 1.2fr 1.2fr 0.8fr 0.6fr 0.5fr;
            gap: 12px;
            align-items: end;
        }
        .variant-form-row .form-group {
            margin-bottom: 0;
        }
        .variant-form-row .form-group label {
            font-size: 11px;
            font-weight: 600;
            color: #888;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .variant-form-row .form-group input {
            padding: 9px 12px;
            font-size: 13px;
            background: #fff;
        }
        .variant-default-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            padding-bottom: 8px;
        }
        .variant-default-wrap input[type="checkbox"] {
            width: 17px;
            height: 17px;
            accent-color: #2874f0;
            cursor: pointer;
        }
        .variant-default-wrap label {
            font-size: 12px;
            font-weight: 600;
            color: #555;
            cursor: pointer;
        }

        /* ===== BUTTONS ===== */
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            flex-wrap: wrap;
        }
        .btn-submit {
            background: #2874f0;
            color: #fff;
            border: none;
            padding: 14px 40px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-submit:hover {
            background: #1a5bc7;
        }
        .btn-cancel {
            background: #f8f9fa;
            color: #555;
            border: 1px solid #e5e5e5;
            padding: 14px 30px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-cancel:hover {
            background: #e5e5e5;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .variant-form-row { grid-template-columns: 1fr 1fr; }
            .variant-form-row .form-group:last-child { grid-column: 1 / -1; }
        }
        @media (max-width: 576px) {
            .admin-card { padding: 20px; }
            .variant-form-row { grid-template-columns: 1fr; }
            .variants-header { flex-direction: column; align-items: flex-start; }
            .btn-add-variant { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header class="top-header">
    <div class="logo">
        <h1>Quick<span>Basket</span></h1>
    </div>
    <div class="search-box">
        <input type="text" placeholder="Search orders...">
        <button><i class="fa-solid fa-magnifying-glass"></i></button>
    </div>
    <div class="header-icons">
        <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="products.php"><i class="fa-solid fa-box"></i> Products</a>
        <a href="logout.php" style="color:#ffd700;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </div>
</header>

<!-- ===== CATEGORY NAV ===== -->
<nav class="category-bar">
    <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="products.php"><i class="fa-solid fa-box"></i> Products</a>
    <a href="add-product.php" class="active"><i class="fa-solid fa-plus"></i> Add Product</a>
    <a href="orders.php"><i class="fa-solid fa-truck"></i> Orders</a>
    <a href="categories.php"><i class="fa-solid fa-tags"></i> Categories</a>
    <a href="sellers.php"><i class="fa-solid fa-store"></i> Sellers</a>
</nav>

<!-- ===== MAIN ===== -->
<div class="admin-wrap">
    <div class="admin-card">
        <h1 class="page-title">
            <i class="fa-solid fa-plus-circle" style="color:#2874f0;"></i> Add <span>Product</span>
        </h1>
        <p class="page-subtitle">Add main product details and variants (with images) – Amazon style.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST">
            <!-- ===== MAIN FIELDS ===== -->
            <div class="form-row">
                <div class="form-group">
                    <label>Product Name <span class="required">*</span></label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Price (₹) <span class="required">*</span></label>
                    <input type="number" name="price" step="0.01" min="0" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Stock <span class="required">*</span></label>
                    <input type="number" name="stock" min="0" required>
                </div>
                <div class="form-group">
                    <label>Category <span class="required">*</span></label>
                    <select name="category_id" required>
                        <option value="">Select</option>
                        <?php while($c = mysqli_fetch_assoc($cat_result)): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Main Image URL <span class="required">*</span></label>
                    <input type="url" name="image" placeholder="https://example.com/image.jpg" required>
                </div>
                <div class="form-group">
                    <label>Seller <span class="required">*</span></label>
                    <select name="seller_id" required>
                        <option value="">Select Seller</option>
                        <?php while($s = mysqli_fetch_assoc($seller_result)): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['store_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Status</label>
                <div class="status-toggle">
                    <label><input type="radio" name="status" value="active" checked> <span>Active</span></label>
                    <label><input type="radio" name="status" value="inactive"> <span>Inactive</span></label>
                </div>
            </div>

            <!-- ===== VARIANTS (THUMBNAILS) ===== -->
            <div class="variants-section">
                <div class="variants-header">
                    <h3><i class="fa-regular fa-images"></i> Variants (Sub‑Images) <span class="variant-count-badge" id="variantCountBadge">0</span></h3>
                    <button type="button" class="btn-add-variant" onclick="addVariant()">
                        <i class="fa-solid fa-plus"></i> Add Variant
                    </button>
                </div>
                <div id="variantsContainer"></div>
            </div>

            <!-- ===== FORM ACTIONS ===== -->
            <div class="form-actions">
                <button type="submit" class="btn-submit"><i class="fa-solid fa-floppy-disk"></i> Save Product</button>
                <a href="products.php" class="btn-cancel"><i class="fa-solid fa-xmark"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- ===== JAVASCRIPT ===== -->
<script>
    let variantCount = 0;

    function addVariant(data = null) {
        variantCount++;
        const container = document.getElementById('variantsContainer');
        const card = document.createElement('div');
        card.className = 'variant-card';
        card.dataset.index = variantCount;
        const checked = (data && data.is_default) ? 'checked' : '';
        card.innerHTML = `
            <div class="variant-header">
                <span class="variant-number">Variant #${variantCount}</span>
                <div class="variant-actions">
                    <button type="button" onclick="moveVariant(this,'up')"><i class="fa-solid fa-chevron-up"></i></button>
                    <button type="button" onclick="moveVariant(this,'down')"><i class="fa-solid fa-chevron-down"></i></button>
                    <button type="button" class="btn-remove" onclick="removeVariant(this)"><i class="fa-solid fa-trash-can"></i></button>
                </div>
            </div>
            <div class="variant-form-row">
                <div class="form-group">
                    <label>Variant Name *</label>
                    <input type="text" name="variants[${variantCount}][name]" placeholder="e.g. Black" value="${data?data.name:''}" required>
                </div>
                <div class="form-group">
                    <label>Image URL</label>
                    <input type="url" name="variants[${variantCount}][image]" placeholder="https://example.com/variant.jpg" value="${data?data.image:''}">
                </div>
                <div class="form-group">
                    <label>Price (₹)</label>
                    <input type="number" name="variants[${variantCount}][price]" step="0.01" placeholder="0.00" value="${data?data.price:''}">
                </div>
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="variants[${variantCount}][stock]" placeholder="0" value="${data?data.stock:'0'}">
                </div>
                <div class="form-group">
                    <div class="variant-default-wrap">
                        <input type="checkbox" name="variants[${variantCount}][is_default]" value="1" ${checked} id="default_${variantCount}">
                        <label for="default_${variantCount}">Default</label>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(card);
        updateVariantNumbers();
    }

    function removeVariant(btn) {
        if (confirm('Remove this variant?')) {
            btn.closest('.variant-card').remove();
            updateVariantNumbers();
        }
    }

    function moveVariant(btn, dir) {
        const card = btn.closest('.variant-card');
        const container = document.getElementById('variantsContainer');
        const cards = container.querySelectorAll('.variant-card');
        const idx = Array.from(cards).indexOf(card);
        if (dir === 'up' && idx > 0) {
            container.insertBefore(card, cards[idx-1]);
        } else if (dir === 'down' && idx < cards.length-1) {
            container.insertBefore(card, cards[idx+2]);
        }
        updateVariantNumbers();
    }

    function updateVariantNumbers() {
        const cards = document.querySelectorAll('.variant-card');
        cards.forEach((card, i) => {
            card.querySelector('.variant-number').textContent = `Variant #${i+1}`;
            card.querySelectorAll('input, select').forEach(inp => {
                inp.name = inp.name.replace(/\[\d+\]/, `[${i}]`);
            });
        });
        variantCount = cards.length;
        document.getElementById('variantCountBadge').textContent = variantCount;
    }

    // Start with one empty variant by default
    addVariant();
</script>

</body>
</html>