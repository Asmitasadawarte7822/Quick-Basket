<?php
session_start();
require_once 'config.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get form data
$name     = $_POST['name'] ?? '';
$email    = $_POST['email'] ?? '';
$phone    = $_POST['phone'] ?? '';
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

// Basic check: if password is filled, it must match confirm
if (!empty($password) && $password !== $confirm) {
    header('Location: edit-profile.php?error=password_mismatch');
    exit;
}

// Escape all fields (simple)
$name  = mysqli_real_escape_string($conn, $name);
$email = mysqli_real_escape_string($conn, $email);
$phone = mysqli_real_escape_string($conn, $phone);

// Build the update query
if (!empty($password)) {
    // If password is provided, update it (plain text)
    $pass_escaped = mysqli_real_escape_string($conn, $password);
    $sql = "UPDATE users SET 
                name = '$name',
                email = '$email',
                phone = '$phone',
                password_hash = '$pass_escaped'
            WHERE id = $user_id";
} else {
    // No password change
    $sql = "UPDATE users SET 
                name = '$name',
                email = '$email',
                phone = '$phone'
            WHERE id = $user_id";
}

if (mysqli_query($conn, $sql)) {
    // Success – redirect with success flag
    header('Location: edit-profile.php?success=1');
    exit;
} else {
    // Error
    header('Location: edit-profile.php?error=db');
    exit;
}

mysqli_close($conn);
?>