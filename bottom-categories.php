<?php
// bottom-categories.php - Bottom Categories Section
// Fetch categories from database
$bottom_cat_sql = "SELECT c.*, COUNT(p.id) AS product_count 
                   FROM product_categories c
                   LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
                   GROUP BY c.id
                   ORDER BY c.name";
$bottom_cat_result = mysqli_query($conn, $bottom_cat_sql);

// Category icons mapping (reuse from your categories.php)
$category_icons = [
    'Mobiles' => 'fa-solid fa-mobile-screen',
    'Laptops' => 'fa-solid fa-laptop',
    'Fashion' => 'fa-solid fa-shirt',
    'Watches' => 'fa-solid fa-clock',
    'Audio' => 'fa-solid fa-headphones',
    'Gaming' => 'fa-solid fa-gamepad',
    'Furniture' => 'fa-solid fa-couch',
    'Jewellery' => 'fa-solid fa-gem',
    'Books' => 'fa-solid fa-book',
    'Electronics' => 'fa-solid fa-microchip',
    'Beauty' => 'fa-solid fa-wand-magic-sparkles',
    'Sports' => 'fa-solid fa-football',
    'Home' => 'fa-solid fa-house',
    'Appliances' => 'fa-solid fa-blender',
    'Automotive' => 'fa-solid fa-car',
    'Toys' => 'fa-solid fa-robot',
    'Grocery' => 'fa-solid fa-basket-shopping',
    'Health' => 'fa-solid fa-heart-pulse'
];
?>
<!-- ======== BOTTOM CATEGORIES SECTION ======== -->
<section class="bottom-categories">
    <div class="bottom-categories-inner">
        <div class="bottom-categories-header">
            <h3><i class="fa-regular fa-folder-open"></i> Shop by Category</h3>
            <a href="categories.php">View All →</a>
        </div>
        <div class="bottom-categories-grid">
            <?php if ($bottom_cat_result && mysqli_num_rows($bottom_cat_result) > 0): ?>
                <?php while ($cat = mysqli_fetch_assoc($bottom_cat_result)): 
                    $icon = $category_icons[$cat['name']] ?? 'fa-regular fa-tag';
                ?>
                    <a href="category-products.php?slug=<?php echo $cat['slug']; ?>" class="bottom-category-item">
                        <div class="bottom-cat-icon">
                            <i class="<?php echo $icon; ?>"></i>
                        </div>
                        <span class="bottom-cat-name"><?php echo htmlspecialchars($cat['name']); ?></span>
                        <span class="bottom-cat-count">(<?php echo $cat['product_count']; ?>)</span>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="grid-column:1/-1; text-align:center; color:#888;">No categories available.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    /* ============================================
       BOTTOM CATEGORIES - BLUE, WHITE & YELLOW
       ============================================ */
    .bottom-categories {
        background: #fff;
        padding: 30px 20px;
        margin: 30px auto 0;
        border-top: 1px solid #e0e0e0;
        border-bottom: 1px solid #e0e0e0;
    }
    .bottom-categories-inner {
        max-width: 1200px;
        margin: 0 auto;
    }
    .bottom-categories-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    .bottom-categories-header h3 {
        font-size: 20px;
        font-weight: 700;
        color: #222;
    }
    .bottom-categories-header h3 i {
        color: #2874f0;
        margin-right: 8px;
    }
    .bottom-categories-header a {
        color: #2874f0;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
    }
    .bottom-categories-header a:hover {
        text-decoration: underline;
    }

    .bottom-categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 16px;
    }
    .bottom-category-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        background: #f8f9fa;
        border-radius: 8px;
        text-decoration: none;
        color: #333;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }
    .bottom-category-item:hover {
        background: #eef4ff;
        border-color: #2874f0;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    .bottom-cat-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        border-radius: 6px;
        color: #2874f0;
        font-size: 16px;
        flex-shrink: 0;
        border: 1px solid #e5e5e5;
    }
    .bottom-category-item:hover .bottom-cat-icon {
        background: #2874f0;
        color: #fff;
        border-color: #2874f0;
    }
    .bottom-cat-name {
        font-size: 14px;
        font-weight: 500;
        flex: 1;
    }
    .bottom-cat-count {
        font-size: 12px;
        color: #888;
    }

    /* ---------- Responsive ---------- */
    @media (max-width: 768px) {
        .bottom-categories-grid {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
        }
        .bottom-category-item {
            padding: 8px 12px;
        }
        .bottom-cat-name {
            font-size: 13px;
        }
        .bottom-categories-header h3 {
            font-size: 18px;
        }
    }
    @media (max-width: 576px) {
        .bottom-categories-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .bottom-category-item {
            flex-direction: column;
            text-align: center;
            padding: 12px;
        }
        .bottom-cat-icon {
            width: 40px;
            height: 40px;
            font-size: 20px;
        }
        .bottom-cat-count {
            font-size: 11px;
        }
    }
</style>