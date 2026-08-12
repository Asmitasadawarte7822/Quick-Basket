
<?php

// session_start();
require_once "../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$result = $conn->query(
    "SELECT * FROM products ORDER BY id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Products - Quick Basket</title>

    <link rel="stylesheet" href="admin.css">

    <style>

        .page-header {
            background: #fff;
            padding: 20px 25px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .add-btn {
            background: #2874f0;
            color: #fff;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 6px;
            font-weight: 600;
        }

        .table-box {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .product-table th,
        .product-table td {
            padding: 14px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .product-table th {
            background: #f5f7fb;
            color: #333;
        }

        .product-table img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }

        .edit-btn {
            background: #2874f0;
            color: #fff;
            padding: 7px 12px;
            border-radius: 5px;
            text-decoration: none;
        }

        .delete-btn {
            background: #d00000;
            color: #fff;
            padding: 7px 12px;
            border-radius: 5px;
            text-decoration: none;
        }

        .stock-good {
            color: #008f12;
            font-weight: 600;
        }

        .stock-low {
            color: #d00000;
            font-weight: 600;
        }

    </style>

</head>

<body>

<div class="admin-layout">

    <aside class="sidebar">

        <div class="admin-logo">
            Quick<span>Basket</span>
        </div>

        <nav>

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="products.php" class="active">
                Products
            </a>

            <a href="categories.php">
                Categories
            </a>

            <a href="users.php">
                Users
            </a>

            <a href="orders.php">
                Orders
            </a>

            <a href="logout.php">
                Logout
            </a>

        </nav>

    </aside>


    <main class="admin-main">

        <div class="page-header">

            <h1>
                Manage Products
            </h1>

            <a
                href="add-product.php"
                class="add-btn">

                <i class="fa-solid fa-plus"></i>
                Add Product

            </a>

        </div>


        <div class="table-box">

            <table class="product-table">

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Image</th>

                        <th>Product</th>

                        <th>Category</th>

                        <th>Price</th>

                        <th>Old Price</th>

                        <th>Discount</th>

                        <th>Stock</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($product = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo $product["id"]; ?>
                            </td>


                            <td>

                                <img
                                    src="../images/<?php
                                    echo htmlspecialchars(
                                        $product["image"]
                                    );
                                    ?>"
                                    alt="Product">

                            </td>


                            <td>

                                <strong>
                                    <?php echo htmlspecialchars(
                                        $product["name"]
                                    ); ?>
                                </strong>

                            </td>


                            <td>
                                <?php echo htmlspecialchars(
                                    $product["category"]
                                ); ?>
                            </td>


                            <td>
                                ₹<?php echo number_format(
                                    $product["price"],
                                    2
                                ); ?>
                            </td>


                            <td>
                                ₹<?php echo number_format(
                                    $product["old_price"],
                                    2
                                ); ?>
                            </td>


                            <td>
                                <?php echo $product["discount"]; ?>%
                            </td>


                            <td>

                                <?php if ($product["stock"] <= 5): ?>

                                    <span class="stock-low">
                                        <?php echo $product["stock"]; ?>
                                        Low Stock
                                    </span>

                                <?php else: ?>

                                    <span class="stock-good">
                                        <?php echo $product["stock"]; ?>
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <a
                                    href="edit-product.php?id=<?php
                                    echo $product["id"];
                                    ?>"
                                    class="edit-btn">

                                    Edit

                                </a>


                                <a
                                    href="delete-product.php?id=<?php
                                    echo $product["id"];
                                    ?>"
                                    class="delete-btn"
                                    onclick="return confirm(
                                        'Are you sure you want to delete this product?'
                                    );">

                                    Delete

                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="9"
                            style="text-align:center;padding:30px;">

                            No products found.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</div>

</body>
</html>
```
