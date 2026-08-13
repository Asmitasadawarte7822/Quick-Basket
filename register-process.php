<?php
// register-process.php
require_once 'config.php';

// Grab fields (no checks, no trimming)
$name     = $_POST['name'] ?? '';
$email    = $_POST['email'] ?? '';
$phone   = $_POST['phone'];
$password = $_POST['password'] ?? '';   // stored as plain text!


// Build the INSERT query – phone is required but not in form, set to empty
$sql = "INSERT INTO users (name, email, phone, password_hash, status, created_at, updated_at)
        VALUES ('$name', '$email', '$phone', '$password', 'active', NOW(), NOW())";

// Run it
if (mysqli_query($conn, $sql)) {
    // Success – go to login page
    header('Location: login.php');
    exit;
} else {
    // If something fails (e.g. duplicate email), show the error
    die('Registration failed: ' . mysqli_error($conn));
}

mysqli_close($conn);
?>


    header('Location: user/dashboard.php');