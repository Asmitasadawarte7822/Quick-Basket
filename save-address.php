<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name'] ?? '');
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    $city = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
    $state = mysqli_real_escape_string($conn, $_POST['state'] ?? '');
    $pincode = mysqli_real_escape_string($conn, $_POST['pincode'] ?? '');
    $address_id = isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0;

    if (empty($full_name) || empty($phone) || empty($address) || empty($city) || empty($state) || empty($pincode)) {
        header('Location: manage-address.php?error=1');
        exit;
    }

    if ($address_id > 0) {
        $sql = "UPDATE addresses SET full_name = ?, phone = ?, address = ?, city = ?, state = ?, pincode = ? 
                WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssii", $full_name, $phone, $address, $city, $state, $pincode, $address_id, $user_id);
    } else {
        $check_sql = "SELECT COUNT(*) AS total FROM addresses WHERE user_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $check_row = mysqli_fetch_assoc($check_result);
        $is_default = ($check_row['total'] == 0) ? 1 : 0;
        mysqli_stmt_close($check_stmt);

        $sql = "INSERT INTO addresses (user_id, full_name, phone, address, city, state, pincode, is_default) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issssssi", $user_id, $full_name, $phone, $address, $city, $state, $pincode, $is_default);
    }

    if (mysqli_stmt_execute($stmt)) {
        header('Location: manage-address.php?saved=1');
        exit;
    } else {
        header('Location: manage-address.php?error=1');
        exit;
    }
    mysqli_stmt_close($stmt);
} else {
    header('Location: manage-address.php');
    exit;
}
?>