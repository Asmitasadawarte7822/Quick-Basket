```php
<?php

session_start();
require_once "../config.php";


/* Admin check */

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}


/* Product ID check */

if (!isset($_GET["id"])) {
    header("Location: products.php");
    exit;
}

$id = intval($_GET["id"]);


/* Get product image before deleting */

$stmt = $conn->prepare(
    "SELECT image FROM products WHERE id = ? LIMIT 1"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();

    header("Location: products.php");
    exit;
}

$product = $result->fetch_assoc();

$stmt->close();


/* Delete product */

$stmt = $conn->prepare(
    "DELETE FROM products WHERE id = ?"
);

$stmt->bind_param("i", $id);


if ($stmt->execute()) {

    /* Delete product image */

    if (
        !empty($product["image"]) &&
        file_exists("../images/" . $product["image"])
    ) {
        unlink("../images/" . $product["image"]);
    }

}

$stmt->close();


/* Back to products */

header("Location: products.php");
exit;

?>
```
