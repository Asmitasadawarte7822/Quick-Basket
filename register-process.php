<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

// Get form data
$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

// Basic validation
$errors = [];

if (empty($name)) {
    $errors[] = "Full name is required.";
}
if (empty($email)) {
    $errors[] = "Email is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
}
if (empty($phone)) {
    $errors[] = "Phone number is required.";
}
if (empty($password)) {
    $errors[] = "Password is required.";
} elseif (strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters.";
}
if ($password !== $confirm) {
    $errors[] = "Passwords do not match.";
}

// If validation fails, redirect back with errors
if (!empty($errors)) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_data'] = ['name' => $name, 'email' => $email, 'phone' => $phone];
    header('Location: register.php');
    exit;
}

// ---------- Check if email already exists ----------
$check_sql = "SELECT id FROM users WHERE email = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "s", $email);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

if (mysqli_stmt_num_rows($check_stmt) > 0) {
    // Email already registered
    $_SESSION['register_errors'] = ['This email is already registered. Please use a different email or login.'];
    $_SESSION['register_data'] = ['name' => $name, 'email' => $email, 'phone' => $phone];
    mysqli_stmt_close($check_stmt);
    header('Location: register.php');
    exit;
}
mysqli_stmt_close($check_stmt);

// ---------- Check if phone already exists (optional) ----------
$check_phone_sql = "SELECT id FROM users WHERE phone = ?";
$check_phone_stmt = mysqli_prepare($conn, $check_phone_sql);
mysqli_stmt_bind_param($check_phone_stmt, "s", $phone);
mysqli_stmt_execute($check_phone_stmt);
mysqli_stmt_store_result($check_phone_stmt);

if (mysqli_stmt_num_rows($check_phone_stmt) > 0) {
    $_SESSION['register_errors'] = ['This phone number is already registered. Please use a different number.'];
    $_SESSION['register_data'] = ['name' => $name, 'email' => $email, 'phone' => $phone];
    mysqli_stmt_close($check_phone_stmt);
    header('Location: register.php');
    exit;
}
mysqli_stmt_close($check_phone_stmt);

// ---------- Hash password (recommended) ----------
// If you want plain text, comment this line and use $password directly
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// ---------- Insert new user ----------
$insert_sql = "INSERT INTO users (name, email, phone, password_hash, status, created_at, updated_at) 
               VALUES (?, ?, ?, ?, 'active', NOW(), NOW())";
$insert_stmt = mysqli_prepare($conn, $insert_sql);
mysqli_stmt_bind_param($insert_stmt, "ssss", $name, $email, $phone, $password_hash);

if (mysqli_stmt_execute($insert_stmt)) {
    // Registration successful
    $_SESSION['registration_success'] = "Account created successfully! Please login.";
    mysqli_stmt_close($insert_stmt);
    mysqli_close($conn);
    header('Location: login.php');
    exit;
} else {
    // Other database error
    $_SESSION['register_errors'] = ['Registration failed. Please try again later.'];
    mysqli_stmt_close($insert_stmt);
    mysqli_close($conn);
    header('Location: register.php');
    exit;
}
?>