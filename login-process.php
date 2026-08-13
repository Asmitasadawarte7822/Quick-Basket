<?php
// login-process.php
require_once 'config.php';

// Only process POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Grab fields (no trimming, no escaping)
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Build query – compare plain text directly
    $sql = "SELECT * FROM users WHERE email = '$email' AND password_hash = '$password'";
    $result = mysqli_query($conn, $sql);

    // If exactly one row matches, login succeeds
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        session_start();
        $_SESSION['user_id'] = $user['id'];
        header('Location: dashboard.php');
        exit;
    } else {
        // Otherwise fail – show a plain message
        die('Invalid email or password.');
    }
} else {
    // If not POST, go back to login page
    header('Location: login.php');
    exit;
}
?>