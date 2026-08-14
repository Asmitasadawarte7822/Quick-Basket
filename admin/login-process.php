<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // TEMPORARY HARDCODED ADMIN - Remove after debugging
    if ($email === 'Asmita123@gmail.com' && $password === 'Asmita@123') {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_name'] = 'Admin';
        header('Location: dashboard.php');
        exit;
    }

    // Escape to prevent SQL errors
    $email = mysqli_real_escape_string($conn, $email);

    // Query for user with admin/manager role
    $sql = "SELECT id, name, password_hash, role FROM users WHERE email = '$email' AND role IN ('admin', 'manager')";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die('Query error: ' . mysqli_error($conn));
    }

    if (mysqli_num_rows($result) === 1) {
        $admin = mysqli_fetch_assoc($result);

        // Check if password matches (hashed)
        if (password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            header('Location: dashboard.php');
            exit;
        } else {
            // Try direct comparison (plain text fallback)
            if ($password === $admin['password_hash']) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                header('Location: dashboard.php');
                exit;
            }
        }
    }

    // If login fails
    header('Location: login.php?error=1');
    exit;
} else {
    header('Location: login.php');
    exit;
}