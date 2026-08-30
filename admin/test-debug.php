<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../PHPMailer-master/src/Exception.php';
require_once '../PHPMailer-master/src/PHPMailer.php';
require_once '../PHPMailer-master/src/SMTP.php';

$admin_email = 'vaibhavkalyankar747@gmail.com';
$admin_password = 'uevhumwondevlxcm';

$mail = new PHPMailer(true);

try {
    // Enable debug output
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $admin_email;
    $mail->Password   = $admin_password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom($admin_email, 'Vaibhav Kalyankar');
    $mail->addAddress('sadavarteasmita@gmail.com', 'Asmita');  // Recipient
    $mail->addAddress($admin_email, 'Admin');                  // Also send to yourself

    $mail->Subject = 'Test from Quick Basket - Debug';
    $mail->Body    = "Hello Asmita,\n\nThis is a debug test.\n\nRegards,\nVaibhav";

    $mail->send();
    echo "<br>✅ Email sent to both recipients!";
} catch (Exception $e) {
    echo "<br>❌ Error: " . $mail->ErrorInfo;
}
?>