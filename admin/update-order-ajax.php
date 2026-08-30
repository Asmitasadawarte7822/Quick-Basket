<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

session_start();
require_once '../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$phpmailer_path = '../PHPMailer-master/src/';
require_once $phpmailer_path . 'Exception.php';
require_once $phpmailer_path . 'PHPMailer.php';
require_once $phpmailer_path . 'SMTP.php';

header('Content-Type: application/json');

// ============================================================
// ✅ APNA EMAIL AUR APP PASSWORD (BINA SPACES)
// ============================================================
$admin_email = 'vaibhavkalyankar747@gmail.com';
$admin_password = 'uevhumwondevlxcm';
// ============================================================

// Dynamic base URL
$base_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/Quick Basket/';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$new_status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : '';
$note = isset($_POST['note']) ? mysqli_real_escape_string($conn, $_POST['note']) : '';

if ($order_id <= 0 || empty($new_status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
    exit;
}

$valid_statuses = ['pending', 'confirmed', 'processing', 'shipped', 'ontheway', 'delivered', 'cancelled', 'refunded'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status.']);
    exit;
}

$check_sql = "SELECT o.status AS old_status, u.email AS user_email, u.name AS user_name 
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              WHERE o.id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "i", $order_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$order_data = mysqli_fetch_assoc($check_result);
mysqli_stmt_close($check_stmt);

if (!$order_data) {
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
    exit;
}

$old_status = $order_data['old_status'] ?? 'unknown';
$user_email = $order_data['user_email'] ?? '';
$user_name = $order_data['user_name'] ?? 'Customer';

// Update order
$update_sql = "UPDATE orders SET status = ? WHERE id = ?";
$update_stmt = mysqli_prepare($conn, $update_sql);
mysqli_stmt_bind_param($update_stmt, "si", $new_status, $order_id);
if (!mysqli_stmt_execute($update_stmt)) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . mysqli_error($conn)]);
    mysqli_stmt_close($update_stmt);
    mysqli_close($conn);
    exit;
}
mysqli_stmt_close($update_stmt);

// Add history
$check_table = "SHOW TABLES LIKE 'order_status_history'";
$table_check = mysqli_query($conn, $check_table);
if (mysqli_num_rows($table_check) > 0) {
    $history_note = $note ?: "Status changed from " . ucfirst($old_status) . " to " . ucfirst($new_status);
    $history_sql = "INSERT INTO order_status_history (order_id, status, note) VALUES (?, ?, ?)";
    $history_stmt = mysqli_prepare($conn, $history_sql);
    mysqli_stmt_bind_param($history_stmt, "iss", $order_id, $new_status, $history_note);
    mysqli_stmt_execute($history_stmt);
    mysqli_stmt_close($history_stmt);
}

// ---------- SEND EMAIL ----------
$mail_sent = false;
$mail_error = '';

if ($user_email) {
    $status_labels = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'ontheway' => 'On The Way',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded'
    ];
    $status_display = $status_labels[$new_status] ?? ucfirst($new_status);
    $order_number = str_pad($order_id, 6, '0', STR_PAD_LEFT);

    $subject = "Order #$order_number Status Updated – Quick Basket";
    $track_link = $base_url . 'track-order.php?id=' . $order_id;

    // ✅ SIMPLE TEXT EMAIL (Spam score kam)
    $plain_message = "Hello $user_name,\n\n";
    $plain_message .= "Your order #$order_number status has been updated.\n\n";
    $plain_message .= "New Status: $status_display\n";
    if ($note) $plain_message .= "Note: $note\n\n";
    $plain_message .= "Thank you for shopping with Quick Basket!\n";
    $plain_message .= "View your order: $track_link\n\n";
    $plain_message .= "Regards,\nQuick Basket Team";

       // ---------- PHPMailer ----------
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $admin_email;
        $mail->Password   = $admin_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // ⬇️ YE LINE YAHAN ADD KAREIN ⬇️
        $mail->SMTPDebug = 0; // 0 ka matlab hai debug log screen par nahi dikhega

        // ✅ Real name in From
        $mail->setFrom($admin_email, 'Vaibhav Kalyankar');
        $mail->addReplyTo($admin_email, 'Quick Basket Support');

        // Recipient
        $mail->addAddress($user_email, $user_name);

        // ✅ Plain text only (no HTML)
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $plain_message;

        $mail->send();
        $mail_sent = true;
    } catch (Exception $e) {
        // ... baaki ka code same rahega
        // Fallback to mail()
        $headers = "From: $admin_email\r\n";
        $headers .= "Reply-To: $admin_email\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        if (mail($user_email, $subject, $plain_message, $headers)) {
            $mail_sent = true;
            $mail_error = '';
        } else {
            $mail_sent = false;
            $mail_error = 'PHPMailer failed: ' . $mail_error . ' and mail() also failed.';
        }
    }
}

$response = [
    'success' => true,
    'message' => 'Order updated.',
    'email_sent' => $mail_sent,
    'email_error' => $mail_error
];

if (!$mail_sent && $user_email) {
    $response['message'] .= ' Email failed: ' . $mail_error;
} elseif ($mail_sent && $user_email) {
    $response['message'] .= ' Email sent to ' . $user_email;
}

echo json_encode($response);
mysqli_close($conn);
?>