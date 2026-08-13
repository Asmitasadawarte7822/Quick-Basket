<?php
require_once '../config.php';

$email = 'your_admin_email@example.com'; // CHANGE THIS
$password = 'your_password';             // CHANGE THIS

echo "Testing login for: $email<br><br>";

$sql = "SELECT id, name, password_hash, role FROM users WHERE email = '$email'";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("SQL error: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) === 0) {
    die("❌ No user found with that email.");
}

$user = mysqli_fetch_assoc($result);
echo "User: " . $user['name'] . "<br>";
echo "Role: " . $user['role'] . "<br>";
echo "Hash: " . $user['password_hash'] . "<br><br>";

if ($user['role'] !== 'admin') {
    echo "❌ Role is not 'admin'. Current role: " . $user['role'];
    exit;
}

if (password_verify($password, $user['password_hash'])) {
    echo "✅ Password matches! Login would succeed.";
} else {
    echo "❌ Password does NOT match.";
    echo "<br>Tip: If the hash is plain text, use `if ($password === $user['password_hash'])` instead.";
}
?>