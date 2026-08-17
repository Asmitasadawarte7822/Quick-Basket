<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);

    $sql = "INSERT INTO product_categories (name) VALUES ('$name')";
    if (mysqli_query($conn, $sql)) {
        header('Location: categories.php?success=added');
        exit;
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>
<