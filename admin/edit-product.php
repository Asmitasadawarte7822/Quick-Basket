<?php

session_start();
require_once "../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET["id"])) {
    header("Location: products.php");
    exit;
}

$id = intval($_GET["id"]);

$message = "";
$error = "";


/* Get product */

$stmt = $conn->prepare(
    "SELECT * FROM products WHERE id = ? LIMIT 1"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Product not found.");
}

$product = $result->fetch_assoc();

$stmt->close();


/* Update product */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $category = trim($_POST["category"]);
    $description = trim($_POST["description"]);

    $price = floatval($_POST["price"]);
    $old_price = floatval($_POST["old_price"]);
    $discount = intval($_POST["discount"]);
    $stock = intval($_POST["stock"]);

    $image = $product["image"];


    /* New image */

    if (
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] === UPLOAD_ERR_OK
    ) {

        $allowed = [
            "jpg",
            "jpeg",
            "png",
            "webp"
        ];

        $file_name = $_FILES["image"]["name"];
        $tmp_name = $_FILES["image"]["tmp_name"];

        $extension = strtolower(
            pathinfo(
                $file_name,
                PATHINFO_EXTENSION
            )
        );

        if (!in_array($extension, $allowed)) {

            $error =
                "Only JPG, JPEG, PNG and WEBP images are allowed.";

        } else {

            $new_image =
                time() . "_" .
                preg_replace(
                    "/[^a-zA-Z0-9._-]/",
                    "_",
                    $file_name
                );

            $upload_path =
                "../images/" . $new_image;

            if (move_uploaded_file(
                $tmp_name,
                $upload_path
            )) {

                /* Delete old image */

                if (
                    !empty($product["image"]) &&
                    file_exists(
                        "../images/" . $product["image"]
                    )
                ) {

                    unlink(
                        "../images/" . $product["image"]
                    );
                }

                $image = $new_image;

            } else {

                $error =
                    "Image upload failed.";
            }
        }
    }


    if (
        empty($name) ||
        empty($category) ||
        $price <= 0 ||
        $stock < 0
    ) {

        $error =
            "Please fill all required fields.";

    }


    if (empty($error)) {

        $stmt = $conn->prepare(
            "UPDATE products SET
                name = ?,
                category = ?,
                description = ?,
                price = ?,
                old_price = ?,
                discount = ?,
                image = ?,
                stock = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "sssddisii",
            $name,
            $category,
            $description,
            $price,
            $old_price,
            $discount,
            $image,
            $stock,
            $id
        );


        if ($stmt->execute()) {

            $message =
                "Product updated successfully.";

            /* Refresh product data */

            $stmt2 = $conn->prepare(
                "SELECT * FROM products
                 WHERE id = ?"
            );

            $stmt2->bind_param("i", $id);
            $stmt2->execute();

            $product =
                $stmt2->get_result()->fetch_assoc();

            $stmt2->close();

        } else {

            $error =
                "Failed to update product.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Product - Quick Basket</title>

    <link rel="stylesheet" href="admin.css">

    <style>

        .form-card {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            max-width: 850px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            outline: none;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .current-image {
            width: 120px;
            height: 120px;
            object-fit: contain;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
        }

        .submit-btn {
            background: #2874f0;
            color: #fff;
            border: none;
            padding: 13px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .back-btn {
            display: inline-block;
            margin-left: 10px;
            padding: 12px 20px;
            background: #eee;
            color: #333;
            text-decoration: none;
            border-radius: 6px;
        }

        .success {
            background: #e5ffe9;
            color: #008f12;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .error {
            background: #ffe5e5;
            color: #d00000;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        @media(max-width:700px) {

            .form-row {
                grid-template-columns: 1fr;
            }

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

        <h1 style="margin-bottom:20px;">
            Edit Product
        </h1>


        <div class="form-card">

            <?php if ($message): ?>

                <div class="success">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>


            <?php if ($error): ?>

                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>


            <form
                method="POST"
                enctype="multipart/form-data">


                <div class="form-row">

                    <div class="form-group">

                        <label>
                            Product Name *
                        </label>

                        <input
                            type="text"
                            name="name"
                            value="<?php
                            echo htmlspecialchars(
                                $product["name"]
                            );
                            ?>"
                            required>

                    </div>


                    <div class="form-group">

                        <label>
                            Category *
                        </label>

                        <input
                            type="text"
                            name="category"
                            value="<?php
                            echo htmlspecialchars(
                                $product["category"]
                            );
                            ?>"
                            required>

                    </div>

                </div>


                <div class="form-group">

                    <label>
                        Description
                    </label>

                    <textarea
                        name="description"><?php
                        echo htmlspecialchars(
                            $product["description"]
                        );
                        ?></textarea>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label>
                            Price *
                        </label>

                        <input
                            type="number"
                            name="price"
                            step="0.01"
                            value="<?php
                            echo $product["price"];
                            ?>"
                            required>

                    </div>


                    <div class="form-group">

                        <label>
                            Old Price
                        </label>

                        <input
                            type="number"
                            name="old_price"
                            step="0.01"
                            value="<?php
                            echo $product["old_price"];
                            ?>">

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label>
                            Discount %
                        </label>

                        <input
                            type="number"
                            name="discount"
                            min="0"
                            max="100"
                            value="<?php
                            echo $product["discount"];
                            ?>">

                    </div>


                    <div class="form-group">

                        <label>
                            Stock *
                        </label>

                        <input
                            type="number"
                            name="stock"
                            min="0"
                            value="<?php
                            echo $product["stock"];
                            ?>"
                            required>

                    </div>

                </div>


                <div class="form-group">

                    <label>
                        Current Image
                    </label>

                    <?php if (!empty($product["image"])): ?>

                        <br>

                        <img
                            src="../images/<?php
                            echo htmlspecialchars(
                                $product["image"]
                            );
                            ?>"
                            class="current-image"
                            alt="Product">

                    <?php endif; ?>

                </div>


                <div class="form-group">

                    <label>
                        Change Image
                    </label>

                    <input
                        type="file"
                        name="image"
                        accept=".jpg,.jpeg,.png,.webp">

                </div>


                <button
                    type="submit"
                    class="submit-btn">

                    Update Product

                </button>


                <a
                    href="products.php"
                    class="back-btn">

                    Back

                </a>

            </form>

        </div>

    </main>

</div>

</body>

</html>