<?php

require_once "config.php";

$email = "asmita@gmail.com";
$new_password = "admin123";

$hashed_password = password_hash(
    $new_password,
    PASSWORD_DEFAULT
);

$stmt = $conn->prepare(
    "UPDATE users
     SET password = ?
     WHERE email = ?
     AND role = 'admin'"
);

$stmt->bind_param(
    "ss",
    $hashed_password,
    $email
);

if ($stmt->execute()) {

    if ($stmt->affected_rows > 0) {

        echo "<h2>Admin password reset successfully!</h2>";

        echo "<p>Email: <b>asmita@gmail.com</b></p>";

        echo "<p>Password: <b>admin123</b></p>";

        echo "<p style='color:red;'>
        IMPORTANT: Delete reset-admin.php after this.
        </p>";

    } else {

        echo "Admin account not found.";

    }

} else {

    echo "Password reset failed: " . $conn->error;

}

$stmt->close();

?>