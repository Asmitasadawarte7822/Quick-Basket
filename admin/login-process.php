<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // ---------- OPTION 1: Hardcoded admin (for testing) ----------
    // Remove this block once database login works
    if ($email === 'Asmita123@gmail.com' && $password === 'Asmita@123') {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_name'] = 'Admin';
        header('Location: dashboard.php');
        exit;
    }

    // ---------- OPTION 2: Database login ----------
    // Escape email to prevent SQL errors
    $email_escaped = mysqli_real_escape_string($conn, $email);
    
    // Query for user with admin/manager role
    $sql = "SELECT id, name, password_hash FROM users WHERE email = '$email_escaped' AND role IN ('admin', 'manager')";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($result) === 1) {
        $admin = mysqli_fetch_assoc($result);
        
        // Check if password is hashed (starts with $2y$)
        if (password_verify($password, $admin['password_hash'])) {
            // Password matches (hashed)
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            header('Location: dashboard.php');
            exit;
        } elseif ($password === $admin['password_hash']) {
            // Password matches (plain text - fallback)
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            header('Location: dashboard.php');
            exit;
        }
    }

    // If we reach here, login failed
    header('Location: login.php?error=1');
    exit;
} else {
    header('Location: login.php');
    exit;
}
?>