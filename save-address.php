<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $address_id = isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0;

    // Validate
    if (empty($full_name) || empty($phone) || empty($address) || empty($city) || empty($state) || empty($pincode)) {
        $_SESSION['address_error'] = 'All fields are required.';
        header('Location: checkout.php?error=1');
        exit;
    }

    // Escape values
    $full_name = mysqli_real_escape_string($conn, $full_name);
    $phone = mysqli_real_escape_string($conn, $phone);
    $address = mysqli_real_escape_string($conn, $address);
    $city = mysqli_real_escape_string($conn, $city);
    $state = mysqli_real_escape_string($conn, $state);
    $pincode = mysqli_real_escape_string($conn, $pincode);

    if ($address_id > 0) {
        // Update existing address
        $sql = "UPDATE addresses SET 
                full_name = '$full_name', 
                phone = '$phone', 
                address = '$address', 
                city = '$city', 
                state = '$state', 
                pincode = '$pincode' 
                WHERE id = $address_id AND user_id = $user_id";
    } else {
        // Check if this is the first address (make it default)
        $check_sql = "SELECT COUNT(*) AS total FROM addresses WHERE user_id = $user_id";
        $check_result = mysqli_query($conn, $check_sql);
        $check_row = mysqli_fetch_assoc($check_result);
        $is_default = ($check_row['total'] == 0) ? 1 : 0;

        // Insert new address
        $sql = "INSERT INTO addresses (user_id, full_name, phone, address, city, state, pincode, is_default) 
                VALUES ($user_id, '$full_name', '$phone', '$address', '$city', '$state', '$pincode', $is_default)";
    }

    if (mysqli_query($conn, $sql)) {
        header('Location: checkout.php?address_saved=1');
        exit;
    } else {
        $_SESSION['address_error'] = 'Database error: ' . mysqli_error($conn);
        header('Location: checkout.php?error=1');
        exit;
    }
} else {
    header('Location: checkout.php');
    exit;
}
?>