<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$cat_result = mysqli_query($conn, "SELECT id, name FROM product_categories ORDER BY name");
$seller_result = mysqli_query($conn, "SELECT id, store_name FROM sellers WHERE status='active' ORDER BY store_name");

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $image = mysqli_real_escape_string($conn, $_POST['image']);
    $category_id = intval($_POST['category_id']);
    $seller_id = intval($_POST['seller_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $insert_sql = "INSERT INTO products (name, description, price, stock, image, category_id, seller_id, status)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt, "ssdissis", $name, $description, $price, $stock, $image, $category_id, $seller_id, $status);

    if (mysqli_stmt_execute($stmt)) {
        $product_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        if (isset($_POST['variants']) && is_array($_POST['variants'])) {
            foreach ($_POST['variants'] as $index => $variant) {
                if (!empty($variant['name'])) {
                    $v_name = mysqli_real_escape_string($conn, $variant['name']);
                    $v_image = mysqli_real_escape_string($conn, $variant['image'] ?? '');
                    $v_price = !empty($variant['price']) ? floatval($variant['price']) : NULL;
                    $v_stock = !empty($variant['stock']) ? intval($variant['stock']) : 0;
                    $v_sku = mysqli_real_escape_string($conn, $variant['sku'] ?? '');
                    $is_default = isset($variant['is_default']) ? 1 : 0;
                    $sort_order = $index;

                    $v_sql = "INSERT INTO product_variants (product_id, variant_name, variant_image, variant_price, variant_stock, sku, is_default, sort_order)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $v_stmt = mysqli_prepare($conn, $v_sql);
                    mysqli_stmt_bind_param($v_stmt, "issdissi", $product_id, $v_name, $v_image, $v_price, $v_stock, $v_sku, $is_default, $sort_order);
                    mysqli_stmt_execute($v_stmt);
                    mysqli_stmt_close($v_stmt);
                }
            }
        }

        $variant_count = isset($_POST['variants']) ? count($_POST['variants']) : 0;
        $message = '✅ Product added with ' . $variant_count . ' variants!';
        $message_type = 'success';
    } else {
        $message = '❌ Error: ' . mysqli_error($conn);
        $message_type = 'error';
    }
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product with Variants</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>

/* ---------- BASE ---------- */
body {
    background: #f1f3f6;
    font-family: 'Segoe UI', sans-serif;
}

/* ---------- WRAPPER ---------- */
.admin-wrap {
    max-width: 1000px;
    margin: 30px auto;
    padding: 0 20px;
}

/* ---------- CARD ---------- */
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

/* ---------- FORM ELEMENTS ---------- */
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

/* ---------- STATUS TOGGLE ---------- */
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

/* ---------- ALERT ---------- */
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

/* ---------- VARIANTS ---------- */
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

/* ---------- VARIANT CARD ---------- */
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

/* ---------- VARIANT FORM ROW ---------- */
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

/* ---------- BUTTONS ---------- */
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

/* ---------- RESPONSIVE ---------- */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    .variant-form-row {
        grid-template-columns: 1fr 1fr;
    }
    .variant-form-row .form-group:last-child {
        grid-column: 1 / -1;
    }
}

@media (max-width: 576px) {
    .admin-card {
        padding: 20px;
    }
    .variant-form-row {
        grid-template-columns: 1fr;
    }
    .variants-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .btn-add-variant {
        width: 100%;
        justify-content: center;
    }
}
    </style>
</head>
<body>
    <!-- Include your header and category nav (same as before) -->
    <header class="top-header">...</header>
    <nav class="category-bar">...</nav>

    <div class="admin-wrap">
        <div class="admin-card">
            <h1 class="page-title"><i class="fa-solid fa-plus-circle" style="color:#2874f0;"></i> Add <span>Product</span></h1>
            <p class="page-subtitle">Add main product details and variants (with images) – Amazon style.</p>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST">
                <!-- Main product fields -->
                <div class="form-row">
                    <div class="form-group"><label>Product Name <span class="required">*</span></label><input type="text" name="name" required></div>
                    <div class="form-group"><label>Price (₹) <span class="required">*</span></label><input type="number" name="price" step="0.01" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Stock <span class="required">*</span></label><input type="number" name="stock" required></div>
                    <div class="form-group"><label>Category <span class="required">*</span></label><select name="category_id" required>
                        <option value="">Select</option>
                        <?php while($c = mysqli_fetch_assoc($cat_result)) echo "<option value='{$c['id']}'>{$c['name']}</option>"; ?>
                    </select></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Main Image URL <span class="required">*</span></label><input type="url" name="image" placeholder="https://example.com/image.jpg" required></div>
                    <div class="form-group"><label>Seller <span class="required">*</span></label><select name="seller_id" required>
                        <option value="">Select Seller</option>
                        <?php while($s = mysqli_fetch_assoc($seller_result)) echo "<option value='{$s['id']}'>{$s['store_name']}</option>"; ?>
                    </select></div>
                </div>
                <div class="form-group"><label>Description</label><textarea name="description"></textarea></div>
                <div class="form-group">
                    <label>Status</label>
                    <div class="status-toggle">
                        <label><input type="radio" name="status" value="active" checked> <span>Active</span></label>
                        <label><input type="radio" name="status" value="inactive"> <span>Inactive</span></label>
                    </div>
                </div>

                <!-- Variants section -->
                <div class="variants-section">
                    <div class="variants-header">
                        <h3><i class="fa-regular fa-images"></i> Variants (Sub‑Images) <span class="variant-count-badge" id="variantCountBadge">0</span></h3>
                        <button type="button" class="btn-add-variant" onclick="addVariant()"><i class="fa-solid fa-plus"></i> Add Variant</button>
                    </div>
                    <div id="variantsContainer"></div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit"><i class="fa-solid fa-floppy-disk"></i> Save Product</button>
                    <a href="products.php" class="btn-cancel"><i class="fa-solid fa-xmark"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">...</footer>

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
                    <div class="form-group"><label>Variant Name *</label><input type="text" name="variants[${variantCount}][name]" placeholder="e.g. Black" value="${data?data.name:''}" required></div>
                    <div class="form-group"><label>Image URL</label><input type="url" name="variants[${variantCount}][image]" placeholder="https://example.com/variant.jpg" value="${data?data.image:''}"></div>
                    <div class="form-group"><label>Price (₹)</label><input type="number" name="variants[${variantCount}][price]" step="0.01" placeholder="0.00" value="${data?data.price:''}"></div>
                    <div class="form-group"><label>Stock</label><input type="number" name="variants[${variantCount}][stock]" placeholder="0" value="${data?data.stock:'0'}"></div>
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
        function removeVariant(btn) { if(confirm('Remove?')) { btn.closest('.variant-card').remove(); updateVariantNumbers(); } }
        function moveVariant(btn, dir) {
            const card = btn.closest('.variant-card');
            const container = document.getElementById('variantsContainer');
            const cards = container.querySelectorAll('.variant-card');
            const idx = Array.from(cards).indexOf(card);
            if (dir === 'up' && idx > 0) container.insertBefore(card, cards[idx-1]);
            else if (dir === 'down' && idx < cards.length-1) container.insertBefore(card, cards[idx+2]);
            updateVariantNumbers();
        }
        function updateVariantNumbers() {
            document.querySelectorAll('.variant-card').forEach((card, i) => {
                card.querySelector('.variant-number').textContent = `Variant #${i+1}`;
                card.querySelectorAll('input, select').forEach(inp => {
                    inp.name = inp.name.replace(/\[\d+\]/, `[${i}]`);
                });
            });
            variantCount = document.querySelectorAll('.variant-card').length;
            document.getElementById('variantCountBadge').textContent = variantCount;
        }
        addVariant(); // Start with one empty variant
    </script>
</body>
</html>