<?php

session_start();

require_once "config.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Validate required fields
    if (
        empty($name) ||
        empty($email) ||
        empty($phone) ||
        empty($password) ||
        empty($confirm_password)
    ) {

        $message = "Please fill all fields.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {

        $message = "Please enter a valid phone number.";
        $message_type = "error";

    } elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";
        $message_type = "error";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";
        $message_type = "error";

    } else {

        // Check whether email already exists
        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        $check->bind_param("s", $email);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = "Email already registered. Please login.";
            $message_type = "error";

        } else {

            // Check whether phone already exists
            $phone_check = $conn->prepare(
                "SELECT id FROM users WHERE phone = ? LIMIT 1"
            );

            $phone_check->bind_param("s", $phone);
            $phone_check->execute();

            $phone_result = $phone_check->get_result();

            if ($phone_result->num_rows > 0) {

                $message = "Phone number already registered.";
                $message_type = "error";

            } else {

                // Hash password securely
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Insert user
                $stmt = $conn->prepare(
                    "INSERT INTO users
                    (name, email, phone, password_hash, status)
                    VALUES (?, ?, ?, ?, 'active')"
                );

                $stmt->bind_param(
                    "ssss",
                    $name,
                    $email,
                    $phone,
                    $hashed_password
                );

                if ($stmt->execute()) {

                    $message = "Registration successful! You can now login.";
                    $message_type = "success";

                    // Clear form values after successful registration
                    $name = "";
                    $email = "";
                    $phone = "";

                } else {

                    $message = "Something went wrong. Please try again.";
                    $message_type = "error";
                }

                $stmt->close();
            }

            $phone_check->close();
        }

        $check->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Create Account - Quick Basket</title>

    <link rel="stylesheet" href="style.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>

        .auth-page {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 15px;
            background: #f1f3f6;
        }

        .auth-box {
            width: 100%;
            max-width: 450px;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.10);
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .auth-logo h1 {
            color: #2874f0;
            font-size: 36px;
            margin: 0;
        }

        .auth-logo span {
            color: #ffb400;
        }

        .auth-box h2 {
            text-align: center;
            color: #222;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #333;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            box-sizing: border-box;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 6px;
            outline: none;
            font-size: 15px;
        }

        .form-group input:focus {
            border-color: #2874f0;
            box-shadow: 0 0 0 2px rgba(40, 116, 240, 0.1);
        }

        .auth-btn {
            width: 100%;
            padding: 14px;
            border: none;
            background: #2874f0;
            color: #fff;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .auth-btn:hover {
            background: #0052cc;
        }

        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }

        .message.error {
            background: #ffe5e5;
            color: #d00000;
        }

        .message.success {
            background: #e5ffe9;
            color: #008f12;
        }

        .auth-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .auth-link a {
            color: #2874f0;
            text-decoration: none;
            font-weight: 600;
        }

        .back-home {
            text-align: center;
            margin-top: 15px;
        }

        .back-home a {
            color: #555;
            text-decoration: none;
        }

        @media (max-width: 500px) {

            .auth-box {
                padding: 25px 20px;
            }

            .auth-logo h1 {
                font-size: 30px;
            }

        }

    </style>

</head>

<body>

<div class="auth-page">

    <div class="auth-box">

        <div class="auth-logo">

            <h1>
                Quick<span>Basket</span>
            </h1>

        </div>

        <h2>Create Account</h2>

        <?php if (!empty($message)): ?>

            <div class="message <?php echo htmlspecialchars($message_type); ?>">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <form method="POST" action="">

            <div class="form-group">

                <label for="name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter your full name"
                    value="<?php echo htmlspecialchars($name ?? ''); ?>"
                    maxlength="255"
                    required>

            </div>


            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    value="<?php echo htmlspecialchars($email ?? ''); ?>"
                    maxlength="255"
                    required>

            </div>


            <div class="form-group">

                <label for="phone">
                    Phone Number
                </label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    placeholder="Enter your phone number"
                    value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                    maxlength="20"
                    required>

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter password"
                    minlength="6"
                    required>

            </div>


            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Confirm password"
                    minlength="6"
                    required>

            </div>


            <button
                type="submit"
                class="auth-btn">

                Create Account

            </button>

        </form>


        <div class="auth-link">

            Already have an account?

            <a href="login.php">
                Login
            </a>

        </div>


        <div class="back-home">

            <a href="index.php">

                <i class="fa-solid fa-arrow-left"></i>

                Back to Home

            </a>

        </div>

    </div>

</div>

</body>

</html>