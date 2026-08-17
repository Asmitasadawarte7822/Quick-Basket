<?php
session_start();
require_once '../config.php';

// If admin is logged in, log them out
if (isset($_SESSION['admin_id'])) {
    session_unset();
    session_destroy();
    // Redirect to login page with logout success message
    header('Location: login.php?logout=success');
    exit;
} else {
    header('Location: login.php');
    exit;
}
?>